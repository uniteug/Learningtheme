<?php
/**
 * API REST /wp-json/learn/v1/ : utilisée par le site, l'application mobile et
 * les sites clients (mêmes banques de questions).
 *
 * - Correction toujours côté serveur ; les bonnes réponses premium ne sont
 *   jamais envoyées sans droit d'accès.
 * - Limitation de débit et pagination bornée contre l'aspiration des banques,
 *   levées pour les applications munies d'une clé d'API (en-tête X-Learn-Key).
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\REST;

use LearnDrive\Core\Questions;
use LearnDrive\Core\Quizzes;
use LearnDrive\Core\Access;
use LearnDrive\Core\Progress;
use function LearnDrive\Core\setting;
use function LearnDrive\Core\client_fingerprint;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\PT_QUIZ;
use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\TAX_TOPIC;
use const LearnDrive\Core\TAX_COUNTRY;

defined( 'ABSPATH' ) || exit;

const NS = 'learn/v1';

/**
 * Clé d'API valide présente dans la requête ?
 *
 * @param \WP_REST_Request $request Requête.
 * @return bool
 */
function has_api_key( $request ) {
	$given = (string) $request->get_header( 'x_learn_key' );
	if ( '' === $given ) {
		return false;
	}
	$keys = array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', (string) setting( 'api_keys' ) ) ) );
	foreach ( $keys as $key ) {
		if ( strlen( $key ) >= 20 && hash_equals( $key, $given ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Limitation de débit par visiteur (empreinte pseudonymisée de l'IP).
 *
 * @param string $bucket Compteur.
 * @param int    $max    Requêtes autorisées par minute.
 * @return true|\WP_Error
 */
function rate_limit( $bucket, $max ) {
	$key   = 'ld_rl_' . $bucket . '_' . client_fingerprint() . '_' . gmdate( 'YmdHi' );
	$count = (int) get_transient( $key );

	if ( $count >= $max ) {
		return new \WP_Error( 'ld_rate_limited', __( 'Trop de requêtes, réessayez dans une minute.', 'learn-drive-core' ), array( 'status' => 429 ) );
	}

	set_transient( $key, $count + 1, 2 * MINUTE_IN_SECONDS );
	return true;
}

/**
 * Permission publique avec limitation de débit.
 *
 * @param string $bucket Compteur.
 * @param int    $max    Maximum par minute.
 * @return callable
 */
function public_permission( $bucket, $max = 0 ) {
	return static function ( $request ) use ( $bucket, $max ) {
		if ( has_api_key( $request ) ) {
			return true;
		}
		return rate_limit( $bucket, $max ? $max : (int) setting( 'rate_limit' ) );
	};
}

/**
 * Permission : utilisateur connecté.
 *
 * @return bool|\WP_Error
 */
function logged_in_permission() {
	if ( is_user_logged_in() ) {
		return true;
	}
	return new \WP_Error( 'rest_forbidden', __( 'Connectez-vous pour accéder à votre progression.', 'learn-drive-core' ), array( 'status' => 401 ) );
}

/**
 * Déclare les routes.
 */
function register_routes() {
	register_rest_route(
		NS,
		'/check',
		array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => __NAMESPACE__ . '\\check',
			'permission_callback' => public_permission( 'check' ),
			'args'                => array(
				'question' => array(
					'type'     => 'integer',
					'required' => true,
				),
				'answer'   => array(
					'required' => true,
				),
				'quiz'     => array(
					'type'    => 'integer',
					'default' => 0,
				),
			),
		)
	);

	register_rest_route(
		NS,
		'/quiz/(?P<id>\d+)/submit',
		array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => __NAMESPACE__ . '\\submit',
			'permission_callback' => public_permission( 'submit', 20 ),
			'args'                => array(
				'answers' => array(
					'type'    => 'object',
					'default' => array(),
				),
				'session' => array(
					'type'    => 'string',
					'default' => '',
				),
				'elapsed' => array(
					'type'    => 'integer',
					'default' => 0,
				),
			),
		)
	);

	register_rest_route(
		NS,
		'/quiz/(?P<id>\d+)/session',
		array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => __NAMESPACE__ . '\\create_session',
			'permission_callback' => public_permission( 'session', 10 ),
		)
	);

	register_rest_route(
		NS,
		'/quiz/(?P<id>\d+)',
		array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => __NAMESPACE__ . '\\get_quiz',
			'permission_callback' => public_permission( 'read' ),
			'args'                => array(
				'session' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
		)
	);

	register_rest_route(
		NS,
		'/questions',
		array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => __NAMESPACE__ . '\\list_questions',
			'permission_callback' => public_permission( 'read' ),
			'args'                => array(
				'page'     => array(
					'type'    => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
				'per_page' => array(
					'type'    => 'integer',
					'default' => 20,
					'minimum' => 1,
					'maximum' => 100,
				),
				'track'    => array( 'type' => 'string' ),
				'topic'    => array( 'type' => 'string' ),
				'country'  => array( 'type' => 'string' ),
				'search'   => array( 'type' => 'string' ),
			),
		)
	);

	register_rest_route(
		NS,
		'/questions/(?P<id>\d+)',
		array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => __NAMESPACE__ . '\\get_question',
			'permission_callback' => public_permission( 'read' ),
		)
	);

	register_rest_route(
		NS,
		'/report',
		array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => __NAMESPACE__ . '\\report',
			'permission_callback' => public_permission( 'report', 5 ),
			'args'                => array(
				'question' => array(
					'type'     => 'integer',
					'required' => true,
				),
				'message'  => array(
					'type'      => 'string',
					'required'  => true,
					'maxLength' => 1000,
				),
			),
		)
	);

	register_rest_route(
		NS,
		'/me/progress',
		array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => __NAMESPACE__ . '\\my_progress',
				'permission_callback' => __NAMESPACE__ . '\\logged_in_permission',
			),
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => __NAMESPACE__ . '\\delete_my_progress',
				'permission_callback' => __NAMESPACE__ . '\\logged_in_permission',
			),
		)
	);

	register_rest_route(
		NS,
		'/me/goal',
		array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => __NAMESPACE__ . '\\save_goal',
			'permission_callback' => __NAMESPACE__ . '\\logged_in_permission',
			'args'                => array(
				'date' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
		)
	);

	register_rest_route(
		NS,
		'/me/access',
		array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => __NAMESPACE__ . '\\my_access',
			'permission_callback' => __NAMESPACE__ . '\\logged_in_permission',
		)
	);

	register_rest_route(
		NS,
		'/access/redeem',
		array(
			'methods'             => \WP_REST_Server::CREATABLE,
			'callback'            => __NAMESPACE__ . '\\redeem',
			'permission_callback' => static function () {
				$logged = logged_in_permission();
				return true === $logged ? rate_limit( 'redeem', 10 ) : $logged;
			},
			'args'                => array(
				'code' => array(
					'type'     => 'string',
					'required' => true,
				),
			),
		)
	);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\register_routes' );

/**
 * Explication rendue d'une question.
 *
 * @param array $question Question normalisée.
 * @return string
 */
function explanation_html( array $question ) {
	$html = apply_filters( 'the_content', $question['content'] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- filtre du cœur.
	if ( $question['source'] ) {
		$html .= '<p class="ld-fb__source"><strong>' . esc_html__( 'Source :', 'learn-drive-core' ) . '</strong> ' . esc_html( $question['source'] ) . '</p>';
	}
	return $html;
}

/**
 * La question est-elle consultable publiquement ?
 *
 * @param int $id Question.
 * @return bool
 */
function is_public_question( $id ) {
	return PT_QUESTION === get_post_type( $id ) && 'publish' === get_post_status( $id );
}

/**
 * POST /check — correction d'une question (entraînement).
 *
 * @param \WP_REST_Request $request Requête.
 * @return \WP_REST_Response|\WP_Error
 */
function check( $request ) {
	$id = (int) $request['question'];
	if ( ! is_public_question( $id ) ) {
		return new \WP_Error( 'ld_not_found', __( 'Question introuvable.', 'learn-drive-core' ), array( 'status' => 404 ) );
	}

	$question = Questions\get( $id );
	$user_id  = get_current_user_id();

	if ( ! Access\can_access( $id, $user_id ) && ! has_api_key( $request ) ) {
		return rest_ensure_response(
			array(
				'locked'     => true,
				'unlock_url' => Access\unlock_url( $id ),
			)
		);
	}

	$raw = $request['answer'];

	if ( 'reveal' === $raw ) {
		return rest_ensure_response(
			array(
				'locked'      => false,
				'explanation' => explanation_html( $question ),
			)
		);
	}

	$answer  = Questions\sanitize_answer( $raw );
	$correct = null !== $answer && Questions\grade( $question, $answer );

	Questions\record_answer( $id, $answer, $correct );
	Progress\record_answer( $user_id, $id, $correct );

	$lesson = Quizzes\lesson_for_question( $question );

	return rest_ensure_response(
		array(
			'locked'        => false,
			'correct'       => $correct,
			'solution'      => Questions\solution( $question ),
			'solution_text' => Questions\solution_text( $question ),
			'explanation'   => explanation_html( $question ),
			'lesson'        => $lesson ? array(
				'title' => get_the_title( $lesson ),
				'url'   => get_permalink( $lesson ),
			) : null,
			'success_rate'  => Questions\success_rate( $id ),
		)
	);
}

/**
 * Vérifie qu'un quiz est publié et accessible.
 *
 * @param int $id Quiz.
 * @return true|\WP_Error
 */
function check_quiz( $id ) {
	if ( PT_QUIZ !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
		return new \WP_Error( 'ld_not_found', __( 'Quiz introuvable.', 'learn-drive-core' ), array( 'status' => 404 ) );
	}
	if ( ! Access\can_access( $id ) ) {
		return new \WP_Error( 'ld_locked', __( 'Ce quiz fait partie de l’accès premium.', 'learn-drive-core' ), array( 'status' => 403 ) );
	}
	return true;
}

/**
 * POST /quiz/{id}/submit — correction complète d'un quiz ou examen blanc.
 *
 * @param \WP_REST_Request $request Requête.
 * @return \WP_REST_Response|\WP_Error
 */
function submit( $request ) {
	$id    = (int) $request['id'];
	$check = check_quiz( $id );
	if ( is_wp_error( $check ) ) {
		return $check;
	}

	$seed = '';
	if ( $request['session'] ) {
		$session = Quizzes\read_session( $request['session'], $id );
		if ( ! $session ) {
			return new \WP_Error( 'ld_session', __( 'Session d’examen expirée : relancez un examen blanc.', 'learn-drive-core' ), array( 'status' => 410 ) );
		}
		$seed = $session['s'];
	}

	$answers = array();
	foreach ( (array) $request['answers'] as $question_id => $answer ) {
		$answers[ (int) $question_id ] = $answer;
	}

	$user_id = get_current_user_id();
	$is_exam = 'exam' === Quizzes\mode( $id );
	$result  = Quizzes\grade_quiz( $id, $answers, $seed, $user_id, $is_exam );

	if ( $is_exam ) {
		foreach ( $result['details'] as $question_id => &$detail ) {
			if ( ! $detail['locked'] ) {
				$question              = Questions\get( $question_id );
				$detail['explanation'] = $question ? explanation_html( $question ) : '';
			} else {
				$detail['unlock_url'] = Access\unlock_url( $question_id );
			}
		}
		unset( $detail );
		Progress\record_quiz( $user_id, $result, (int) $request['elapsed'] );
	}

	/**
	 * Un quiz vient d'être terminé (CRM, e-mailing, statistiques).
	 *
	 * @param array $result  Résultat.
	 * @param int   $user_id Utilisateur (0 pour un visiteur).
	 */
	do_action( 'learn_drive_quiz_completed', $result, $user_id );

	return rest_ensure_response( $result );
}

/**
 * POST /quiz/{id}/session — nouveau tirage d'examen blanc (applications).
 *
 * @param \WP_REST_Request $request Requête.
 * @return \WP_REST_Response|\WP_Error
 */
function create_session( $request ) {
	$id    = (int) $request['id'];
	$check = check_quiz( $id );
	if ( is_wp_error( $check ) ) {
		return $check;
	}
	return rest_ensure_response( array( 'session' => Quizzes\create_session( $id ) ) );
}

/**
 * Données publiques d'une question (sans la solution).
 *
 * @param array $question Question normalisée.
 * @param bool  $full     Inclure solution et explication.
 * @return array
 */
function question_payload( array $question, $full = false ) {
	$image = $question['image_id'] ? wp_get_attachment_image_src( $question['image_id'], 'learn-drive-800' ) : false;

	$payload = array(
		'id'          => $question['id'],
		'url'         => $question['url'],
		'statement'   => $question['title'],
		'format'      => $question['format'],
		'answers'     => 'numeric' === $question['format'] ? array() : wp_list_pluck( $question['answers'], 'text' ),
		'unit'        => 'numeric' === $question['format'] ? $question['numeric']['unit'] : '',
		'image'       => $image ? array(
			'url'    => $image[0],
			'width'  => $image[1],
			'height' => $image[2],
			'alt'    => (string) get_post_meta( $question['image_id'], '_wp_attachment_image_alt', true ),
		) : null,
		'topic'       => $question['topic'] ? $question['topic']->slug : '',
		'track'       => $question['track'] ? $question['track']->slug : '',
		'country'     => $question['country'] ? $question['country']->slug : '',
		'difficulty'  => $question['difficulty'],
		'official_id' => $question['official_id'],
		'access'      => Access\rule( $question['id'] ),
		'modified'    => get_post_modified_time( 'c', true, $question['id'] ),
	);

	if ( $full ) {
		$payload['solution']    = Questions\solution( $question );
		$payload['explanation'] = explanation_html( $question );
		$payload['source']      = $question['source'];
	}

	return $payload;
}

/**
 * GET /questions — liste paginée (bornée sans clé d'API).
 *
 * @param \WP_REST_Request $request Requête.
 * @return \WP_REST_Response|\WP_Error
 */
function list_questions( $request ) {
	$full = has_api_key( $request );
	$page = (int) $request['page'];

	if ( ! $full && $page > 10 ) {
		return new \WP_Error( 'ld_page_limit', __( 'Pagination limitée : utilisez une clé d’API pour synchroniser la banque complète.', 'learn-drive-core' ), array( 'status' => 403 ) );
	}

	$tax_query = array();
	foreach ( array(
		'track'   => TAX_TRACK,
		'topic'   => TAX_TOPIC,
		'country' => TAX_COUNTRY,
	) as $param => $taxonomy ) {
		if ( ! empty( $request[ $param ] ) ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => sanitize_title( $request[ $param ] ),
			);
		}
	}

	$query = new \WP_Query(
		array(
			'post_type'      => PT_QUESTION,
			'post_status'    => 'publish',
			'posts_per_page' => $full ? (int) $request['per_page'] : min( 50, (int) $request['per_page'] ),
			'paged'          => $page,
			's'              => $request['search'] ? sanitize_text_field( $request['search'] ) : '',
			'orderby'        => array(
				'menu_order' => 'ASC',
				'ID'         => 'ASC',
			),
			'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		)
	);

	$items = array();
	foreach ( $query->posts as $post ) {
		$question = Questions\get( $post );
		if ( $question ) {
			$items[] = question_payload( $question, $full );
		}
	}

	$response = rest_ensure_response( $items );
	$response->header( 'X-WP-Total', (string) $query->found_posts );
	$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );
	return $response;
}

/**
 * GET /questions/{id}.
 *
 * @param \WP_REST_Request $request Requête.
 * @return \WP_REST_Response|\WP_Error
 */
function get_question( $request ) {
	$id = (int) $request['id'];
	if ( ! is_public_question( $id ) ) {
		return new \WP_Error( 'ld_not_found', __( 'Question introuvable.', 'learn-drive-core' ), array( 'status' => 404 ) );
	}
	return rest_ensure_response( question_payload( Questions\get( $id ), has_api_key( $request ) ) );
}

/**
 * GET /quiz/{id} — définition d'un quiz et de ses questions (sans solutions).
 *
 * @param \WP_REST_Request $request Requête.
 * @return \WP_REST_Response|\WP_Error
 */
function get_quiz( $request ) {
	$id = (int) $request['id'];
	if ( PT_QUIZ !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
		return new \WP_Error( 'ld_not_found', __( 'Quiz introuvable.', 'learn-drive-core' ), array( 'status' => 404 ) );
	}

	$seed = '';
	if ( $request['session'] ) {
		$session = Quizzes\read_session( $request['session'], $id );
		$seed    = $session ? $session['s'] : '';
	}

	$questions = array();
	foreach ( Quizzes\question_ids( $id, $seed ? $seed : null ) as $question_id ) {
		$question = Questions\get( $question_id );
		if ( $question ) {
			$questions[] = question_payload( $question, false );
		}
	}

	return rest_ensure_response(
		array(
			'id'        => $id,
			'title'     => get_the_title( $id ),
			'url'       => get_permalink( $id ),
			'mode'      => Quizzes\mode( $id ),
			'rules'     => Quizzes\rules( $id ),
			'access'    => Access\rule( $id ),
			'questions' => $questions,
		)
	);
}

/**
 * POST /report — signalement d'erreur.
 *
 * @param \WP_REST_Request $request Requête.
 * @return \WP_REST_Response|\WP_Error
 */
function report( $request ) {
	$id = (int) $request['question'];
	if ( ! is_public_question( $id ) ) {
		return new \WP_Error( 'ld_not_found', __( 'Question introuvable.', 'learn-drive-core' ), array( 'status' => 404 ) );
	}
	Questions\add_report( $id, (string) $request['message'] );
	return rest_ensure_response( array( 'ok' => true ) );
}

/**
 * GET /me/progress.
 *
 * @return \WP_REST_Response
 */
function my_progress() {
	$user_id = get_current_user_id();
	$data    = Progress\get( $user_id );

	return rest_ensure_response(
		array(
			'progress'  => $data,
			'due'       => Progress\due_reviews( $user_id ),
			'readiness' => Progress\readiness( $user_id ),
			'premium'   => Access\is_premium( $user_id ),
		)
	);
}

/**
 * DELETE /me/progress — suppression en un clic (RGPD).
 *
 * @return \WP_REST_Response
 */
function delete_my_progress() {
	delete_user_meta( get_current_user_id(), Progress\META );
	return rest_ensure_response( array( 'deleted' => true ) );
}

/**
 * POST /me/goal — date d'examen visée par l'apprenant.
 *
 * @param \WP_REST_Request $request Requête.
 * @return \WP_REST_Response|\WP_Error
 */
function save_goal( $request ) {
	$date = (string) $request['date'];
	if ( '' !== $date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return new \WP_Error( 'ld_bad_date', __( 'Date invalide.', 'learn-drive-core' ), array( 'status' => 400 ) );
	}
	update_user_meta( get_current_user_id(), 'ld_goal_date', $date );
	return rest_ensure_response( array( 'ok' => true ) );
}

/**
 * GET /me/access — offres actives (synchronisation avec l'application).
 *
 * @return \WP_REST_Response
 */
function my_access() {
	return rest_ensure_response( Access\active_offers( get_current_user_id() ) );
}

/**
 * POST /access/redeem — utiliser un code d'accès.
 *
 * @param \WP_REST_Request $request Requête.
 * @return \WP_REST_Response|\WP_Error
 */
function redeem( $request ) {
	$result = Access\redeem_code( (string) $request['code'], get_current_user_id() );
	if ( is_wp_error( $result ) ) {
		return $result;
	}
	return rest_ensure_response(
		array(
			'ok'      => true,
			'message' => __( 'Code validé : votre accès est ouvert.', 'learn-drive-core' ),
		)
	);
}

/**
 * Ouvre une session d'examen blanc depuis le site : ?ld_session=new crée un
 * tirage puis redirige vers son URL (jamais indexée ni mise en cache).
 */
function web_session() {
	if ( ! is_singular( PT_QUIZ ) || ! isset( $_GET['ld_session'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture seule.
		return;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- constante standard des plugins de cache.
	}
	nocache_headers();

	$quiz_id = get_queried_object_id();
	$value   = sanitize_text_field( wp_unslash( $_GET['ld_session'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( 'new' === $value ) {
		if ( 'exam' !== Quizzes\mode( $quiz_id ) || ! Access\can_access( $quiz_id ) ) {
			wp_safe_redirect( get_permalink( $quiz_id ) );
			exit;
		}
		$limited = rate_limit( 'session', 10 );
		if ( is_wp_error( $limited ) ) {
			wp_die( esc_html( $limited->get_error_message() ), '', array( 'response' => 429 ) );
		}
		wp_safe_redirect( add_query_arg( 'ld_session', Quizzes\create_session( $quiz_id ), get_permalink( $quiz_id ) ), 302 );
		exit;
	}

	if ( ! Quizzes\read_session( $value, $quiz_id ) ) {
		wp_safe_redirect( get_permalink( $quiz_id ), 302 );
		exit;
	}
}
add_action( 'template_redirect', __NAMESPACE__ . '\\web_session', 1 );
