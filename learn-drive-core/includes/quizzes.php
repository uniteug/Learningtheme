<?php
/**
 * Moteur de quiz : règles d'examen par pays et catégorie, tirage des
 * questions, sessions d'examen blanc et correction côté serveur.
 *
 * Aucune ligne de code ne change pour ajouter un pays : ses règles sont des
 * métadonnées du terme « Pays » (et éventuellement de la catégorie), que le
 * quiz peut à son tour surcharger.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Quizzes;

use const LearnDrive\Core\PT_QUIZ;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\PT_LESSON;
use const LearnDrive\Core\TAX_COUNTRY;
use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\TAX_TOPIC;
use LearnDrive\Core\Questions;
use function LearnDrive\Core\primary_term;

defined( 'ABSPATH' ) || exit;

const MODES = array( 'practice', 'thematic', 'exam' );

/**
 * Règles par défaut.
 *
 * @return array
 */
function default_rules() {
	return array(
		'count'        => 20,
		'duration'     => 0,
		'per_question' => 0,
		'pass_type'    => 'percent',
		'pass_value'   => 80,
		'allow_back'   => true,
		'shuffle'      => false,
		'negative'     => 0,
	);
}

/**
 * Nettoie un jeu de règles (les clés absentes restent héritées).
 *
 * @param mixed $rules Règles brutes.
 * @return array
 */
function sanitize_rules( $rules ) {
	if ( ! is_array( $rules ) ) {
		return array();
	}

	$clean = array();
	foreach ( array( 'count', 'duration', 'per_question', 'pass_value' ) as $key ) {
		if ( isset( $rules[ $key ] ) && '' !== $rules[ $key ] && null !== $rules[ $key ] ) {
			$clean[ $key ] = max( 0, (int) $rules[ $key ] );
		}
	}
	if ( isset( $rules['negative'] ) && '' !== $rules['negative'] ) {
		$clean['negative'] = max( 0, min( 5, (float) $rules['negative'] ) );
	}
	if ( isset( $rules['pass_type'] ) && in_array( $rules['pass_type'], array( 'percent', 'min_correct', 'max_errors' ), true ) ) {
		$clean['pass_type'] = $rules['pass_type'];
	}
	if ( isset( $rules['format'] ) && in_array( $rules['format'], Questions\FORMATS, true ) ) {
		$clean['format'] = $rules['format'];
	}
	foreach ( array( 'allow_back', 'shuffle' ) as $key ) {
		if ( isset( $rules[ $key ] ) && '' !== $rules[ $key ] ) {
			$clean[ $key ] = filter_var( $rules[ $key ], FILTER_VALIDATE_BOOLEAN );
		}
	}

	return $clean;
}

/**
 * Déclare les métadonnées des quiz.
 */
function register_meta() {
	$auth = static function () {
		return current_user_can( 'edit_posts' );
	};

	register_post_meta(
		PT_QUIZ,
		'_ld_mode',
		array(
			'type'          => 'string',
			'single'        => true,
			'default'       => 'thematic',
			'show_in_rest'  => true,
			'auth_callback' => $auth,
		)
	);
	register_post_meta(
		PT_QUIZ,
		'_ld_question_ids',
		array(
			'type'          => 'array',
			'single'        => true,
			'default'       => array(),
			'auth_callback' => $auth,
			'show_in_rest'  => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
			),
		)
	);
	register_post_meta(
		PT_QUIZ,
		'_ld_rules',
		array(
			'type'              => 'object',
			'single'            => true,
			'default'           => array(),
			'auth_callback'     => $auth,
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_rules',
			'show_in_rest'      => array(
				'schema' => array(
					'type'                 => 'object',
					'additionalProperties' => true,
				),
			),
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register_meta', 6 );

/**
 * Mode d'un quiz.
 *
 * @param int $quiz_id Quiz.
 * @return string
 */
function mode( $quiz_id ) {
	$mode = (string) get_post_meta( $quiz_id, '_ld_mode', true );
	return in_array( $mode, MODES, true ) ? $mode : 'thematic';
}

/**
 * Règles effectives d'un quiz : défaut < pays < catégorie < quiz.
 *
 * @param int $quiz_id Quiz.
 * @return array
 */
function rules( $quiz_id ) {
	$rules = default_rules();

	$country = primary_term( $quiz_id, TAX_COUNTRY );
	$track   = primary_term( $quiz_id, TAX_TRACK );
	$is_exam = 'exam' === mode( $quiz_id );

	// Les règles du pays et de la catégorie décrivent l'examen officiel :
	// elles s'appliquent entièrement aux examens blancs, et seulement pour le
	// format et le seuil aux autres quiz (qui gardent leur propre nombre de questions).
	foreach ( array( $country, $track ) as $term ) {
		if ( ! $term ) {
			continue;
		}
		$term_rules = sanitize_rules( get_term_meta( $term->term_id, 'ld_rules', true ) );
		if ( ! $is_exam ) {
			$term_rules = array_intersect_key( $term_rules, array_flip( array( 'format', 'pass_type', 'pass_value' ) ) );
		}
		$rules = array_merge( $rules, $term_rules );
	}

	$rules = array_merge( $rules, sanitize_rules( get_post_meta( $quiz_id, '_ld_rules', true ) ) );

	if ( ! $is_exam ) {
		// Entraînement et quiz thématiques : correction immédiate, sans chronomètre imposé.
		$rules['duration']     = 0;
		$rules['per_question'] = 0;
		$rules['allow_back']   = true;
	}

	/**
	 * Filtre les règles effectives d'un quiz.
	 *
	 * @param array $rules   Règles.
	 * @param int   $quiz_id Quiz.
	 */
	return apply_filters( 'learn_drive_quiz_rules', $rules, $quiz_id );
}

/**
 * Texte du seuil de réussite.
 *
 * @param array $rules Règles.
 * @return string
 */
function pass_label( array $rules ) {
	switch ( $rules['pass_type'] ) {
		case 'min_correct':
			/* translators: 1: minimum correct answers, 2: total */
			return sprintf( __( '%1$d bonnes réponses sur %2$d minimum', 'learn-drive-core' ), $rules['pass_value'], $rules['count'] );
		case 'max_errors':
			/* translators: %d: maximum number of errors */
			return sprintf( _n( '%d erreur maximum', '%d erreurs maximum', $rules['pass_value'], 'learn-drive-core' ), $rules['pass_value'] );
		default:
			/* translators: %d: percentage */
			return sprintf( __( '%d %% de bonnes réponses', 'learn-drive-core' ), $rules['pass_value'] );
	}
}

/**
 * Ordre déterministe pseudo-aléatoire à partir d'une graine.
 *
 * @param int[]  $ids  Identifiants.
 * @param string $seed Graine.
 * @return int[]
 */
function seeded_shuffle( array $ids, $seed ) {
	usort(
		$ids,
		static function ( $a, $b ) use ( $seed ) {
			return strcmp( md5( $seed . ':' . $a ), md5( $seed . ':' . $b ) );
		}
	);
	return $ids;
}

/**
 * Banque de questions d'un quiz (avant tirage).
 *
 * Mode manuel : liste choisie. Mode automatique : toutes les questions
 * publiées partageant les pays, catégories et thèmes du quiz.
 *
 * @param int $quiz_id Quiz.
 * @return int[]
 */
function pool( $quiz_id ) {
	$manual = get_post_meta( $quiz_id, '_ld_question_ids', true );
	if ( is_array( $manual ) && $manual ) {
		$manual = array_map( 'intval', $manual );
		$status = array_filter(
			$manual,
			static function ( $id ) {
				return 'publish' === get_post_status( $id ) && PT_QUESTION === get_post_type( $id );
			}
		);
		return array_values( $status );
	}

	$cache_key = 'ld_pool_' . $quiz_id . '_' . wp_cache_get_last_changed( 'posts' ) . '_' . wp_cache_get_last_changed( 'terms' );
	$cached    = wp_cache_get( $cache_key, 'learn-drive' );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$tax_query = array( 'relation' => 'AND' );
	foreach ( array( TAX_COUNTRY, TAX_TRACK, TAX_TOPIC ) as $taxonomy ) {
		$terms = wp_get_post_terms( $quiz_id, $taxonomy, array( 'fields' => 'ids' ) );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$tax_query[] = array(
				'taxonomy'         => $taxonomy,
				'terms'            => $terms,
				'include_children' => true,
			);
		}
	}

	$ids = get_posts(
		array(
			'post_type'      => PT_QUESTION,
			'post_status'    => 'publish',
			'posts_per_page' => 2000,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'ID'         => 'ASC',
			),
			'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		)
	);

	wp_cache_set( $cache_key, $ids, 'learn-drive', HOUR_IN_SECONDS );

	return $ids;
}

/**
 * Questions d'un quiz.
 *
 * Sans graine : ordre éditorial stable (c'est la liste publiée en HTML sur la
 * page du quiz). Avec graine (session d'examen blanc) : tirage réparti par
 * thème officiel, reproductible côté serveur pour la correction.
 *
 * @param int         $quiz_id Quiz.
 * @param string|null $seed    Graine de session.
 * @return int[]
 */
function question_ids( $quiz_id, $seed = null ) {
	$rules = rules( $quiz_id );
	$pool  = pool( $quiz_id );
	$count = $rules['count'] > 0 ? $rules['count'] : count( $pool );

	if ( null === $seed || '' === $seed ) {
		return array_slice( $pool, 0, $count );
	}

	// Tirage réparti : on regroupe par thème puis on pioche à tour de rôle.
	update_object_term_cache( $pool, PT_QUESTION );
	$by_topic = array();
	foreach ( seeded_shuffle( $pool, $seed ) as $id ) {
		$topic              = primary_term( $id, TAX_TOPIC );
		$key                = $topic ? $topic->term_id : 0;
		$by_topic[ $key ][] = $id;
	}
	ksort( $by_topic );
	$by_topic = seeded_shuffle_groups( $by_topic, $seed );

	$picked = array();
	while ( count( $picked ) < $count && $by_topic ) {
		foreach ( array_keys( $by_topic ) as $key ) {
			if ( count( $picked ) >= $count ) {
				break;
			}
			$picked[] = array_shift( $by_topic[ $key ] );
			if ( ! $by_topic[ $key ] ) {
				unset( $by_topic[ $key ] );
			}
		}
	}

	return seeded_shuffle( $picked, $seed . 'order' );
}

/**
 * Ordre des groupes de thèmes selon la graine.
 *
 * @param array  $groups Groupes.
 * @param string $seed   Graine.
 * @return array
 */
function seeded_shuffle_groups( array $groups, $seed ) {
	$keys   = seeded_shuffle( array_keys( $groups ), $seed . 'groups' );
	$sorted = array();
	foreach ( $keys as $key ) {
		$sorted[ $key ] = $groups[ $key ];
	}
	return $sorted;
}

/**
 * Crée un jeton de session d'examen signé (aucun stockage serveur).
 *
 * @param int $quiz_id Quiz.
 * @return string
 */
function create_session( $quiz_id ) {
	$payload = wp_json_encode(
		array(
			'q' => (int) $quiz_id,
			's' => wp_generate_password( 12, false ),
			't' => time(),
		)
	);
	$data    = rtrim( strtr( base64_encode( $payload ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- jeton URL.
	return $data . '.' . substr( hash_hmac( 'sha256', $data, wp_salt( 'nonce' ) ), 0, 32 );
}

/**
 * Lit et vérifie un jeton de session.
 *
 * @param string $token   Jeton.
 * @param int    $quiz_id Quiz attendu.
 * @return array|null { q, s, t }
 */
function read_session( $token, $quiz_id ) {
	if ( ! is_string( $token ) || ! preg_match( '/^([A-Za-z0-9_-]+)\.([a-f0-9]{32})$/', $token, $m ) ) {
		return null;
	}
	if ( ! hash_equals( substr( hash_hmac( 'sha256', $m[1], wp_salt( 'nonce' ) ), 0, 32 ), $m[2] ) ) {
		return null;
	}
	$data = json_decode( base64_decode( strtr( $m[1], '-_', '+/' ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- jeton URL.
	if ( ! is_array( $data ) || (int) ( $data['q'] ?? 0 ) !== (int) $quiz_id ) {
		return null;
	}
	if ( time() - (int) $data['t'] > 6 * HOUR_IN_SECONDS ) {
		return null;
	}
	return $data;
}

/**
 * Leçon de révision liée à une question (méta, sinon même thème et catégorie).
 *
 * @param array $question Question normalisée.
 * @return int
 */
function lesson_for_question( array $question ) {
	if ( $question['lesson'] ) {
		return $question['lesson'];
	}
	if ( ! $question['topic'] ) {
		return 0;
	}

	static $cache = array();
	$key          = $question['topic']->term_id . '-' . ( $question['track'] ? $question['track']->term_id : 0 );
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$tax_query = array(
		array(
			'taxonomy' => TAX_TOPIC,
			'terms'    => $question['topic']->term_id,
		),
	);

	$ids = get_posts(
		array(
			'post_type'      => PT_LESSON,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		)
	);

	$cache[ $key ] = $ids ? (int) $ids[0] : 0;
	return $cache[ $key ];
}

/**
 * Corrige un quiz complet.
 *
 * @param int    $quiz_id Quiz.
 * @param array  $answers Réponses { id_question: réponse }.
 * @param string $seed    Graine de session (examen blanc) ou chaîne vide.
 * @param int    $user_id Utilisateur (pour les droits d'accès aux corrections).
 * @param bool   $record  Enregistrer les statistiques des questions (déjà faites
 *                        question par question en mode entraînement).
 * @return array
 */
function grade_quiz( $quiz_id, array $answers, $seed, $user_id, $record = true ) {
	$rules  = rules( $quiz_id );
	$ids    = question_ids( $quiz_id, $seed ? $seed : null );
	$total  = count( $ids );
	$points = 0.0;
	$errors = 0;
	$fatal  = false;

	$details  = array();
	$by_topic = array();

	_prime_post_caches( $ids, true, true );

	foreach ( $ids as $id ) {
		$question = Questions\get( $id );
		if ( ! $question ) {
			continue;
		}

		$given    = array_key_exists( $id, $answers ) ? Questions\sanitize_answer( $answers[ $id ] ) : null;
		$answered = null !== $given && array() !== $given;
		$correct  = $answered && Questions\grade( $question, $given );

		if ( $correct ) {
			$points += 1;
		} else {
			++$errors;
			if ( $answered ) {
				$points -= (float) $rules['negative'];
			}
			if ( $question['eliminatory'] ) {
				$fatal = true;
			}
		}

		if ( $answered && $record ) {
			Questions\record_answer( $id, $given, $correct );
		}

		$topic_key = $question['topic'] ? $question['topic']->term_id : 0;
		if ( ! isset( $by_topic[ $topic_key ] ) ) {
			$lesson                 = lesson_for_question( $question );
			$by_topic[ $topic_key ] = array(
				'name'   => $question['topic'] ? $question['topic']->name : __( 'Divers', 'learn-drive-core' ),
				'link'   => $lesson ? get_permalink( $lesson ) : ( $question['topic'] ? get_term_link( $question['topic'] ) : '' ),
				'total'  => 0,
				'errors' => 0,
			);
		}
		++$by_topic[ $topic_key ]['total'];
		if ( ! $correct ) {
			++$by_topic[ $topic_key ]['errors'];
		}

		$can_see       = \LearnDrive\Core\Access\can_access( $id, $user_id );
		$details[ $id ] = array(
			'correct'  => $correct,
			'answered' => $answered,
			'solution' => $can_see ? Questions\solution( $question ) : null,
			'text'     => $can_see ? Questions\solution_text( $question ) : '',
			'url'      => $question['url'],
			'title'    => $question['title'],
			'locked'   => ! $can_see,
		);
	}

	$score   = max( 0, $points );
	$correct = $total - $errors;

	switch ( $rules['pass_type'] ) {
		case 'min_correct':
			$passed = $score >= $rules['pass_value'];
			break;
		case 'max_errors':
			$passed = $errors <= $rules['pass_value'];
			break;
		default:
			$passed = $total > 0 && ( 100 * $score / $total ) >= $rules['pass_value'];
	}
	if ( $fatal ) {
		$passed = false;
	}

	$topics = array_values(
		array_map(
			static function ( $topic ) {
				$topic['link'] = is_string( $topic['link'] ) ? $topic['link'] : '';
				return $topic;
			},
			$by_topic
		)
	);
	usort(
		$topics,
		static function ( $a, $b ) {
			return $b['errors'] <=> $a['errors'];
		}
	);

	return array(
		'quiz'      => (int) $quiz_id,
		'total'     => $total,
		'correct'   => $correct,
		'errors'    => $errors,
		'score'     => round( $score, 2 ),
		'percent'   => $total ? (int) round( 100 * $correct / $total ) : 0,
		'passed'    => $passed,
		'fatal'     => $fatal,
		'threshold' => pass_label( array_merge( $rules, array( 'count' => $total ) ) ),
		'topics'    => $topics,
		'details'   => $details,
	);
}
