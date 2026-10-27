<?php
/**
 * Modèle de données des questions et correction.
 *
 * Énoncé = titre, explication rédigée = contenu, propositions et métadonnées
 * en méta. Les bonnes réponses ne quittent jamais le serveur pour une question
 * premium tant que l'utilisateur n'y a pas droit.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Questions;

use function LearnDrive\Core\setting;
use function LearnDrive\Core\word_count;
use function LearnDrive\Core\primary_term;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\TAX_TOPIC;
use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\TAX_COUNTRY;

defined( 'ABSPATH' ) || exit;

const FORMATS = array( 'tf', 'single', 'multiple', 'numeric', 'order', 'match', 'hotspot', 'flashcard' );

/**
 * Grille des images cliquables : chaque case est un vrai bouton (clavier,
 * lecteur d'écran, cible tactile), la zone attendue couvre une ou plusieurs cases.
 */
const HOTSPOT_COLS = 5;
const HOTSPOT_ROWS = 4;

/**
 * Déclare les métadonnées (lisibles par l'éditeur et l'API REST interne).
 */
function register_meta() {
	$auth = static function () {
		return current_user_can( 'edit_posts' );
	};

	$fields = array(
		'_ld_format'      => array(
			'type'    => 'string',
			'default' => 'multiple',
		),
		'_ld_source'      => array(
			'type'    => 'string',
			'default' => '',
		),
		'_ld_official_id' => array(
			'type'    => 'string',
			'default' => '',
		),
		'_ld_verified'    => array(
			'type'    => 'string',
			'default' => '',
		),
		'_ld_difficulty'  => array(
			'type'    => 'integer',
			'default' => 1,
		),
		'_ld_eliminatory' => array(
			'type'    => 'boolean',
			'default' => false,
		),
		'_ld_sheet'       => array(
			'type'    => 'integer',
			'default' => 0,
		),
		'_ld_lesson'      => array(
			'type'    => 'integer',
			'default' => 0,
		),
	);

	foreach ( $fields as $key => $args ) {
		register_post_meta(
			PT_QUESTION,
			$key,
			array(
				'type'          => $args['type'],
				'single'        => true,
				'default'       => $args['default'],
				'show_in_rest'  => true,
				'auth_callback' => $auth,
			)
		);
	}

	register_post_meta(
		PT_QUESTION,
		'_ld_answers',
		array(
			'type'          => 'array',
			'single'        => true,
			'default'       => array(),
			'auth_callback' => $auth,
			'show_in_rest'  => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'text'    => array( 'type' => 'string' ),
							'correct' => array( 'type' => 'boolean' ),
							'match'   => array( 'type' => 'string' ),
						),
					),
				),
			),
		)
	);

	register_post_meta(
		PT_QUESTION,
		'_ld_hotspots',
		array(
			'type'          => 'array',
			'single'        => true,
			'default'       => array(),
			'auth_callback' => $auth,
			'show_in_rest'  => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'x' => array( 'type' => 'number' ),
							'y' => array( 'type' => 'number' ),
							'w' => array( 'type' => 'number' ),
							'h' => array( 'type' => 'number' ),
						),
					),
				),
			),
		)
	);

	register_post_meta(
		PT_QUESTION,
		'_ld_numeric',
		array(
			'type'          => 'object',
			'single'        => true,
			'default'       => array(),
			'auth_callback' => $auth,
			'show_in_rest'  => array(
				'schema' => array(
					'type'       => 'object',
					'properties' => array(
						'value'     => array( 'type' => 'number' ),
						'tolerance' => array( 'type' => 'number' ),
						'unit'      => array( 'type' => 'string' ),
					),
				),
			),
		)
	);

	foreach ( \LearnDrive\Core\content_types() as $type ) {
		register_post_meta(
			$type,
			'_ld_access',
			array(
				'type'          => 'string',
				'single'        => true,
				'default'       => 'inherit',
				'show_in_rest'  => true,
				'auth_callback' => $auth,
			)
		);
	}
}
add_action( 'init', __NAMESPACE__ . '\\register_meta', 6 );

/**
 * Données normalisées d'une question.
 *
 * @param int|\WP_Post $post Question.
 * @return array|null
 */
function get( $post ) {
	$post = get_post( $post );
	if ( ! $post || PT_QUESTION !== $post->post_type ) {
		return null;
	}

	$format = (string) get_post_meta( $post->ID, '_ld_format', true );
	if ( ! in_array( $format, FORMATS, true ) ) {
		$format = 'multiple';
	}

	$answers = get_post_meta( $post->ID, '_ld_answers', true );
	$answers = is_array( $answers ) ? array_values( $answers ) : array();
	$answers = array_map(
		static function ( $answer ) {
			return array(
				'text'    => (string) ( $answer['text'] ?? '' ),
				'correct' => ! empty( $answer['correct'] ),
				'match'   => (string) ( $answer['match'] ?? '' ),
			);
		},
		$answers
	);

	if ( 'tf' === $format && count( $answers ) !== 2 ) {
		$answers = array(
			array(
				'text'    => __( 'Vrai', 'learn-drive-core' ),
				'correct' => true,
			),
			array(
				'text'    => __( 'Faux', 'learn-drive-core' ),
				'correct' => false,
			),
		);
	}

	$numeric = get_post_meta( $post->ID, '_ld_numeric', true );
	$numeric = is_array( $numeric ) ? wp_parse_args(
		$numeric,
		array(
			'value'     => 0,
			'tolerance' => 0,
			'unit'      => '',
		)
	) : array(
		'value'     => 0,
		'tolerance' => 0,
		'unit'      => '',
	);

	$hotspots = get_post_meta( $post->ID, '_ld_hotspots', true );
	$hotspots = is_array( $hotspots ) ? array_values( array_filter( array_map( __NAMESPACE__ . '\\sanitize_zone', $hotspots ) ) ) : array();

	$topic   = primary_term( $post->ID, TAX_TOPIC );
	$track   = primary_term( $post->ID, TAX_TRACK );
	$country = primary_term( $post->ID, TAX_COUNTRY );

	return array(
		'id'          => $post->ID,
		'title'       => get_the_title( $post ),
		'format'      => $format,
		'answers'     => $answers,
		'hotspots'    => $hotspots,
		'numeric'     => array(
			'value'     => (float) $numeric['value'],
			'tolerance' => (float) $numeric['tolerance'],
			'unit'      => (string) $numeric['unit'],
		),
		'source'      => (string) get_post_meta( $post->ID, '_ld_source', true ),
		'official_id' => (string) get_post_meta( $post->ID, '_ld_official_id', true ),
		'verified'    => (string) get_post_meta( $post->ID, '_ld_verified', true ),
		'difficulty'  => max( 1, min( 3, (int) get_post_meta( $post->ID, '_ld_difficulty', true ) ) ),
		'eliminatory' => (bool) get_post_meta( $post->ID, '_ld_eliminatory', true ),
		'sheet'       => (int) get_post_meta( $post->ID, '_ld_sheet', true ),
		'lesson'      => (int) get_post_meta( $post->ID, '_ld_lesson', true ),
		'image_id'    => (int) get_post_thumbnail_id( $post ),
		'content'     => $post->post_content,
		'url'         => get_permalink( $post ),
		'topic'       => $topic,
		'track'       => $track,
		'country'     => $country,
	);
}

/**
 * Solution d'une question : indices des bonnes propositions ou valeur attendue.
 *
 * @param array $question Question normalisée.
 * @return array|float
 */
function solution( array $question ) {
	if ( 'numeric' === $question['format'] ) {
		return $question['numeric']['value'];
	}

	if ( 'order' === $question['format'] || 'match' === $question['format'] ) {
		// Identifiants d'affichage (mélangés) de chaque position d'origine :
		// ordre juste pour « ordre », correspondance de chaque ligne pour « association ».
		$flip = array_flip( shuffled_indexes( $question ) );
		ksort( $flip );
		return array_values( array_map( 'intval', $flip ) );
	}

	if ( 'hotspot' === $question['format'] ) {
		return hotspot_cells( $question );
	}

	$correct = array();
	foreach ( $question['answers'] as $index => $answer ) {
		if ( $answer['correct'] ) {
			$correct[] = $index;
		}
	}
	return $correct;
}

/**
 * Lettre d'une proposition (A, B, C…).
 *
 * @param int $index Indice.
 * @return string
 */
function letter( $index ) {
	return chr( 65 + (int) $index );
}

/**
 * Texte lisible de la bonne réponse.
 *
 * @param array $question Question normalisée.
 * @return string
 */
function solution_text( array $question ) {
	if ( 'numeric' === $question['format'] ) {
		return trim( wp_sprintf( '%s %s', number_format_i18n( $question['numeric']['value'], floor( $question['numeric']['value'] ) == $question['numeric']['value'] ? 0 : 1 ), $question['numeric']['unit'] ) ); // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- comparaison volontaire float / entier.
	}

	if ( 'flashcard' === $question['format'] ) {
		return '';
	}

	if ( 'order' === $question['format'] ) {
		$parts = array();
		foreach ( $question['answers'] as $index => $answer ) {
			$parts[] = ( $index + 1 ) . '. ' . $answer['text'];
		}
		return implode( ' → ', $parts );
	}

	if ( 'match' === $question['format'] ) {
		$parts = array();
		foreach ( $question['answers'] as $answer ) {
			$parts[] = $answer['text'] . ' ↔ ' . $answer['match'];
		}
		return implode( ' · ', $parts );
	}

	if ( 'hotspot' === $question['format'] ) {
		return __( 'la zone surlignée sur l’image', 'learn-drive-core' );
	}

	$parts = array();
	foreach ( solution( $question ) as $index ) {
		$text    = $question['answers'][ $index ]['text'];
		$parts[] = 'tf' === $question['format'] ? $text : letter( $index ) . '. ' . $text;
	}
	return implode( ' · ', $parts );
}

/**
 * Corrige une réponse.
 *
 * @param array $question Question normalisée.
 * @param mixed $answer   Réponse (indice, liste d'indices, nombre ou « known »).
 * @return bool
 */
function grade( array $question, $answer ) {
	switch ( $question['format'] ) {
		case 'numeric':
			if ( ! is_numeric( $answer ) ) {
				return false;
			}
			return abs( (float) $answer - $question['numeric']['value'] ) <= max( 0, $question['numeric']['tolerance'] ) + 1e-9;

		case 'flashcard':
			return 'known' === $answer;

		case 'order':
		case 'match':
			// Les réponses utilisent les identifiants d'affichage (mélangés) :
			// on les ramène aux positions d'origine avant de comparer.
			$shuffled = shuffled_indexes( $question );
			if ( ! is_array( $answer ) || count( $answer ) !== count( $shuffled ) || count( $shuffled ) < 2 ) {
				return false;
			}
			$original = array();
			foreach ( array_values( $answer ) as $display ) {
				$display = (int) $display;
				if ( ! isset( $shuffled[ $display ] ) ) {
					return false;
				}
				$original[] = $shuffled[ $display ];
			}
			// Ordre : séquence 0, 1, 2… ; association : la ligne i pointe vers la correspondance i.
			return array_keys( $shuffled ) === $original;

		case 'hotspot':
			if ( is_array( $answer ) ) {
				$answer = count( $answer ) === 1 ? reset( $answer ) : -1;
			}
			return is_numeric( $answer ) && in_array( (int) $answer, hotspot_cells( $question ), true );

		case 'multiple':
			$given = array_map( 'intval', (array) $answer );
			sort( $given );
			$given    = array_values( array_unique( $given ) );
			$expected = solution( $question );
			sort( $expected );
			return $given === $expected;

		default:
			if ( is_array( $answer ) ) {
				$answer = count( $answer ) === 1 ? reset( $answer ) : -1;
			}
			if ( ! is_numeric( $answer ) ) {
				return false;
			}
			return in_array( (int) $answer, solution( $question ), true );
	}
}

/**
 * Normalise une réponse reçue de l'extérieur.
 *
 * @param mixed $answer Réponse brute.
 * @return mixed
 */
function sanitize_answer( $answer ) {
	if ( is_array( $answer ) ) {
		return array_slice( array_map( 'intval', $answer ), 0, 10 );
	}
	if ( 'known' === $answer || 'unknown' === $answer ) {
		return $answer;
	}
	if ( is_numeric( $answer ) ) {
		return 0 + $answer;
	}
	return null;
}

/**
 * Indique si la question est trop mince pour être indexée.
 *
 * @param int $post_id Question.
 * @return bool
 */
function is_thin( $post_id ) {
	$words = get_post_meta( $post_id, '_ld_words', true );
	if ( '' === $words ) {
		$words = word_count( get_post_field( 'post_content', $post_id ) );
	}
	return (int) $words < (int) setting( 'thin_words' );
}

/**
 * Calcule les indicateurs SEO d'une question à l'enregistrement.
 *
 * @param int $post_id Contenu.
 */
function update_seo_flags( $post_id ) {
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	$words = word_count( get_post_field( 'post_content', $post_id ) );
	update_post_meta( $post_id, '_ld_words', $words );

	$thin   = $words < (int) setting( 'thin_words' );
	$manual = (bool) get_post_meta( $post_id, '_ld_seo_noindex', true );

	if ( $thin || $manual || get_post_meta( $post_id, '_ld_canonical', true ) ) {
		update_post_meta( $post_id, '_ld_noindex', 1 );
	} else {
		delete_post_meta( $post_id, '_ld_noindex' );
	}
}
add_action( 'save_post_' . PT_QUESTION, __NAMESPACE__ . '\\update_seo_flags', 20 );

/**
 * L'explication est obligatoire pour publier une question (éditeur de blocs).
 *
 * @param \stdClass        $prepared Contenu préparé.
 * @param \WP_REST_Request $request  Requête.
 * @return \stdClass|\WP_Error
 */
function require_explanation_rest( $prepared, $request ) {
	$status  = $prepared->post_status ?? get_post_status( $request['id'] ?? 0 );
	$content = $prepared->post_content ?? get_post_field( 'post_content', $request['id'] ?? 0 );

	if ( 'publish' === $status && 0 === word_count( $content ) ) {
		return new \WP_Error(
			'ld_missing_explanation',
			__( 'Rédigez l’explication de la question (dans le contenu) avant de la publier.', 'learn-drive-core' ),
			array( 'status' => 400 )
		);
	}

	return $prepared;
}
add_filter( 'rest_pre_insert_' . PT_QUESTION, __NAMESPACE__ . '\\require_explanation_rest', 10, 2 );

/**
 * Même règle hors éditeur de blocs : la question reste en brouillon.
 *
 * @param array $data    Données du contenu.
 * @param array $postarr Données brutes.
 * @return array
 */
function require_explanation( $data, $postarr ) {
	if ( PT_QUESTION === $data['post_type'] && 'publish' === $data['post_status'] && 0 === word_count( wp_unslash( $data['post_content'] ) ) && empty( $postarr['ld_import'] ) ) {
		$data['post_status'] = 'draft';
	}
	return $data;
}
add_filter( 'wp_insert_post_data', __NAMESPACE__ . '\\require_explanation', 10, 2 );

/**
 * Enregistre les statistiques d'une réponse (taux de réussite, choix les plus fréquents).
 *
 * @param int   $question_id Question.
 * @param mixed $answer      Réponse.
 * @param bool  $correct     Bonne réponse.
 */
function record_answer( $question_id, $answer, $correct ) {
	$stats = get_post_meta( $question_id, '_ld_stats', true );
	$stats = is_array( $stats ) ? $stats : array(
		'attempts' => 0,
		'correct'  => 0,
		'choices'  => array(),
	);

	++$stats['attempts'];
	if ( $correct ) {
		++$stats['correct'];
	}

	foreach ( (array) $answer as $choice ) {
		if ( is_int( $choice ) && $choice >= 0 && $choice < 10 ) {
			$stats['choices'][ $choice ] = ( $stats['choices'][ $choice ] ?? 0 ) + 1;
		}
	}

	update_post_meta( $question_id, '_ld_stats', $stats );
}

/**
 * Taux de réussite global d'une question (null si trop peu de réponses).
 *
 * @param int $question_id Question.
 * @return int|null Pourcentage.
 */
function success_rate( $question_id ) {
	$stats = get_post_meta( $question_id, '_ld_stats', true );
	if ( ! is_array( $stats ) || ( $stats['attempts'] ?? 0 ) < 20 ) {
		return null;
	}
	return (int) round( 100 * $stats['correct'] / $stats['attempts'] );
}

/**
 * Ajoute un signalement d'erreur envoyé par un apprenant.
 *
 * @param int    $question_id Question.
 * @param string $message     Message.
 */
function add_report( $question_id, $message ) {
	$reports   = get_post_meta( $question_id, '_ld_reports', true );
	$reports   = is_array( $reports ) ? $reports : array();
	$reports[] = array(
		'date'    => time(),
		'user'    => get_current_user_id(),
		'message' => mb_substr( sanitize_textarea_field( $message ), 0, 1000 ),
	);
	update_post_meta( $question_id, '_ld_reports', array_slice( $reports, -30 ) );
	update_post_meta( $question_id, '_ld_report_count', count( $reports ) );

	/**
	 * Un apprenant a signalé une erreur sur une question.
	 *
	 * @param int    $question_id Question.
	 * @param string $message     Message.
	 */
	do_action( 'learn_drive_question_reported', $question_id, $message );
}

/**
 * Questions proches : même thème et même catégorie, hors question courante.
 *
 * @param int $question_id Question.
 * @param int $count       Nombre.
 * @return int[]
 */
function related_ids( $question_id, $count = 5 ) {
	$topic = primary_term( $question_id, TAX_TOPIC );
	$track = primary_term( $question_id, TAX_TRACK );

	$tax_query = array( 'relation' => 'AND' );
	if ( $topic ) {
		$tax_query[] = array(
			'taxonomy' => TAX_TOPIC,
			'terms'    => $topic->term_id,
		);
	}
	if ( $track ) {
		$tax_query[] = array(
			'taxonomy' => TAX_TRACK,
			'terms'    => $track->term_id,
		);
	}

	return get_posts(
		array(
			'post_type'      => PT_QUESTION,
			'posts_per_page' => $count,
			'post__not_in'   => array( $question_id ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- un seul ID.
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => 'menu_order date',
			'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		)
	);
}

/**
 * Nettoie une zone d'image cliquable (pourcentages).
 *
 * @param mixed $zone Zone { x, y, w, h }.
 * @return array|null
 */
function sanitize_zone( $zone ) {
	if ( ! is_array( $zone ) ) {
		return null;
	}
	$clean = array();
	foreach ( array( 'x', 'y', 'w', 'h' ) as $key ) {
		$clean[ $key ] = max( 0, min( 100, round( (float) ( $zone[ $key ] ?? 0 ), 2 ) ) );
	}
	if ( $clean['w'] <= 0 || $clean['h'] <= 0 ) {
		return null;
	}
	$clean['w'] = min( $clean['w'], 100 - $clean['x'] );
	$clean['h'] = min( $clean['h'], 100 - $clean['y'] );
	return $clean;
}

/**
 * Cases de la grille couvertes par les zones attendues.
 *
 * Une case est juste si une zone recouvre au moins 30 % de sa surface, ou si
 * elle contient le centre d'une zone (petites cibles).
 *
 * @param array $question Question normalisée.
 * @return int[]
 */
function hotspot_cells( array $question ) {
	$cells  = array();
	$cell_w = 100 / HOTSPOT_COLS;
	$cell_h = 100 / HOTSPOT_ROWS;

	foreach ( $question['hotspots'] as $zone ) {
		for ( $row = 0; $row < HOTSPOT_ROWS; $row++ ) {
			for ( $col = 0; $col < HOTSPOT_COLS; $col++ ) {
				$x0      = $col * $cell_w;
				$y0      = $row * $cell_h;
				$overlap = max( 0, min( $x0 + $cell_w, $zone['x'] + $zone['w'] ) - max( $x0, $zone['x'] ) ) * max( 0, min( $y0 + $cell_h, $zone['y'] + $zone['h'] ) - max( $y0, $zone['y'] ) );
				$cx      = $zone['x'] + $zone['w'] / 2;
				$cy      = $zone['y'] + $zone['h'] / 2;
				$center  = $cx >= $x0 && $cx < $x0 + $cell_w && $cy >= $y0 && $cy < $y0 + $cell_h;
				if ( $center || $overlap >= 0.3 * $cell_w * $cell_h ) {
					$cells[] = $row * HOTSPOT_COLS + $col;
				}
			}
		}
	}

	$cells = array_values( array_unique( $cells ) );
	sort( $cells );
	return $cells;
}

/**
 * Ordre d'affichage mélangé (stable pour une question donnée, jamais l'ordre juste).
 *
 * @param array $question Question normalisée.
 * @return int[]
 */
function shuffled_indexes( array $question ) {
	$indexes = array_keys( $question['answers'] );
	if ( count( $indexes ) < 2 ) {
		return $indexes;
	}
	$seed = 'ld-' . $question['id'];
	usort(
		$indexes,
		static function ( $a, $b ) use ( $seed ) {
			return strcmp( md5( $seed . $a ), md5( $seed . $b ) );
		}
	);
	if ( array_keys( $question['answers'] ) === $indexes ) {
		$indexes = array_reverse( $indexes );
	}
	return $indexes;
}
