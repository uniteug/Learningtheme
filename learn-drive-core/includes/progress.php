<?php
/**
 * Progression des apprenants connectés : statistiques par thème, historique
 * des examens blancs, révision espacée des erreurs (J+1, J+3, J+7) et séries
 * de jours. Les visiteurs gardent leur progression dans leur navigateur.
 *
 * Données pseudonymisées, exportables et supprimables par l'utilisateur (RGPD).
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Progress;

use LearnDrive\Core\Questions;
use LearnDrive\Core\Quizzes;

defined( 'ABSPATH' ) || exit;

const META = 'ld_progress';

const REVIEW_STEPS = array( 1, 3, 7 );

/**
 * Progression brute d'un utilisateur.
 *
 * @param int $user_id Utilisateur.
 * @return array
 */
function get( $user_id ) {
	$data = get_user_meta( $user_id, META, true );
	return wp_parse_args(
		is_array( $data ) ? $data : array(),
		array(
			'topics' => array(),
			'exams'  => array(),
			'review' => array(),
			'streak' => array(
				'last'  => '',
				'count' => 0,
				'best'  => 0,
			),
			'goal'   => array(
				'date' => '',
			),
		)
	);
}

/**
 * Enregistre la progression.
 *
 * @param int   $user_id Utilisateur.
 * @param array $data    Données.
 */
function save( $user_id, array $data ) {
	update_user_meta( $user_id, META, $data );
}

/**
 * Met à jour la série de jours consécutifs.
 *
 * @param array $data Progression.
 * @return array
 */
function touch_streak( array $data ) {
	$today     = wp_date( 'Y-m-d' );
	$yesterday = wp_date( 'Y-m-d', time() - DAY_IN_SECONDS );

	if ( $data['streak']['last'] === $today ) {
		return $data;
	}

	$data['streak']['count'] = $data['streak']['last'] === $yesterday ? $data['streak']['count'] + 1 : 1;
	$data['streak']['last']  = $today;
	$data['streak']['best']  = max( (int) $data['streak']['best'], (int) $data['streak']['count'] );

	return $data;
}

/**
 * Enregistre une réponse isolée (entraînement) pour la révision espacée.
 *
 * @param int  $user_id     Utilisateur.
 * @param int  $question_id Question.
 * @param bool $correct     Bonne réponse.
 */
function record_answer( $user_id, $question_id, $correct ) {
	if ( ! $user_id ) {
		return;
	}
	$data = get( $user_id );
	$data = apply_answer( $data, $question_id, $correct );
	$data = touch_streak( $data );
	save( $user_id, $data );
}

/**
 * Applique une réponse aux statistiques et au calendrier de révision.
 *
 * @param array $data        Progression.
 * @param int   $question_id Question.
 * @param bool  $correct     Bonne réponse.
 * @return array
 */
function apply_answer( array $data, $question_id, $correct ) {
	$topic = \LearnDrive\Core\primary_term( $question_id, \LearnDrive\Core\TAX_TOPIC );
	if ( $topic ) {
		$key                    = (string) $topic->term_id;
		$data['topics'][ $key ] = array(
			'seen'    => ( $data['topics'][ $key ]['seen'] ?? 0 ) + 1,
			'correct' => ( $data['topics'][ $key ]['correct'] ?? 0 ) + ( $correct ? 1 : 0 ),
		);
	}

	$key = (string) $question_id;
	if ( ! $correct ) {
		$data['review'][ $key ] = array(
			'step' => 0,
			'due'  => time() + REVIEW_STEPS[0] * DAY_IN_SECONDS,
		);
	} elseif ( isset( $data['review'][ $key ] ) && $data['review'][ $key ]['due'] <= time() ) {
		$step = (int) $data['review'][ $key ]['step'] + 1;
		if ( $step >= count( REVIEW_STEPS ) ) {
			unset( $data['review'][ $key ] );
		} else {
			$data['review'][ $key ] = array(
				'step' => $step,
				'due'  => time() + REVIEW_STEPS[ $step ] * DAY_IN_SECONDS,
			);
		}
	}

	// Plafond pour garder une méta raisonnable.
	if ( count( $data['review'] ) > 500 ) {
		$data['review'] = array_slice( $data['review'], -500, null, true );
	}

	return $data;
}

/**
 * Enregistre un quiz terminé.
 *
 * @param int   $user_id Utilisateur.
 * @param array $result  Résultat de Quizzes\grade_quiz().
 * @param int   $elapsed Durée en secondes.
 */
function record_quiz( $user_id, array $result, $elapsed ) {
	if ( ! $user_id ) {
		return;
	}

	$data = get( $user_id );
	foreach ( $result['details'] as $question_id => $detail ) {
		if ( $detail['answered'] ) {
			$data = apply_answer( $data, (int) $question_id, (bool) $detail['correct'] );
		}
	}

	if ( 'exam' === Quizzes\mode( $result['quiz'] ) ) {
		$data['exams'][] = array(
			'quiz'    => $result['quiz'],
			'correct' => $result['correct'],
			'total'   => $result['total'],
			'passed'  => $result['passed'],
			'time'    => (int) $elapsed,
			'date'    => time(),
		);
		$data['exams']   = array_slice( $data['exams'], -50 );
	}

	$data = touch_streak( $data );
	save( $user_id, $data );
}

/**
 * Questions à revoir aujourd'hui.
 *
 * @param int $user_id Utilisateur.
 * @param int $limit   Nombre maximum.
 * @return int[]
 */
function due_reviews( $user_id, $limit = 20 ) {
	$due = array();
	foreach ( get( $user_id )['review'] as $question_id => $item ) {
		if ( $item['due'] <= time() && 'publish' === get_post_status( (int) $question_id ) ) {
			$due[] = (int) $question_id;
		}
		if ( count( $due ) >= $limit ) {
			break;
		}
	}
	return $due;
}

/**
 * Prédiction « prêt pour l'examen » : moyenne des 5 derniers examens blancs.
 *
 * @param int $user_id Utilisateur.
 * @return array{ready:bool,percent:int,count:int}
 */
function readiness( $user_id ) {
	$exams = array_slice( get( $user_id )['exams'], -5 );
	if ( ! $exams ) {
		return array(
			'ready'   => false,
			'percent' => 0,
			'count'   => 0,
		);
	}

	$passed  = count( array_filter( wp_list_pluck( $exams, 'passed' ) ) );
	$percent = (int) round( 100 * $passed / count( $exams ) );

	return array(
		'ready'   => count( $exams ) >= 3 && $percent >= 80,
		'percent' => $percent,
		'count'   => count( $exams ),
	);
}

/**
 * Exportateur de données personnelles (Outils › Exporter les données personnelles).
 *
 * @param array $exporters Exportateurs.
 * @return array
 */
function register_exporter( $exporters ) {
	$exporters['learn-drive'] = array(
		'exporter_friendly_name' => __( 'Progression d’apprentissage', 'learn-drive-core' ),
		'callback'               => __NAMESPACE__ . '\\export_personal_data',
	);
	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', __NAMESPACE__ . '\\register_exporter' );

/**
 * Exporte la progression d'un utilisateur.
 *
 * @param string $email Adresse e-mail.
 * @return array
 */
function export_personal_data( $email ) {
	$user = get_user_by( 'email', $email );
	if ( ! $user ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$data = get( $user->ID );

	return array(
		'data' => array(
			array(
				'group_id'    => 'learn-drive',
				'group_label' => __( 'Progression d’apprentissage', 'learn-drive-core' ),
				'item_id'     => 'progress-' . $user->ID,
				'data'        => array(
					array(
						'name'  => __( 'Examens blancs', 'learn-drive-core' ),
						'value' => (string) count( $data['exams'] ),
					),
					array(
						'name'  => __( 'Questions à revoir', 'learn-drive-core' ),
						'value' => (string) count( $data['review'] ),
					),
					array(
						'name'  => __( 'Données complètes (JSON)', 'learn-drive-core' ),
						'value' => wp_json_encode( $data ),
					),
				),
			),
		),
		'done' => true,
	);
}

/**
 * Effaceur de données personnelles.
 *
 * @param array $erasers Effaceurs.
 * @return array
 */
function register_eraser( $erasers ) {
	$erasers['learn-drive'] = array(
		'eraser_friendly_name' => __( 'Progression d’apprentissage', 'learn-drive-core' ),
		'callback'             => __NAMESPACE__ . '\\erase_personal_data',
	);
	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', __NAMESPACE__ . '\\register_eraser' );

/**
 * Supprime la progression d'un utilisateur.
 *
 * @param string $email Adresse e-mail.
 * @return array
 */
function erase_personal_data( $email ) {
	$user    = get_user_by( 'email', $email );
	$removed = false;
	if ( $user ) {
		$removed = delete_user_meta( $user->ID, META );
	}
	return array(
		'items_removed'  => $removed,
		'items_retained' => false,
		'messages'       => array(),
		'done'           => true,
	);
}
