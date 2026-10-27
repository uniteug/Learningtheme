<?php
/**
 * Bloc « Chiffres clés » : preuves calculées en direct, jamais inventées.
 *
 * @package LearnDriveCore
 */

defined( 'ABSPATH' ) || exit;

$learn_drive_stats = get_transient( 'ld_stats' );
if ( ! is_array( $learn_drive_stats ) ) {
	$learn_drive_tracks = wp_count_terms(
		array(
			'taxonomy'   => \LearnDrive\Core\TAX_TRACK,
			'hide_empty' => true,
		)
	);
	$learn_drive_stats  = array(
		'questions' => (int) wp_count_posts( \LearnDrive\Core\PT_QUESTION )->publish,
		'quizzes'   => (int) wp_count_posts( \LearnDrive\Core\PT_QUIZ )->publish,
		'lessons'   => (int) wp_count_posts( \LearnDrive\Core\PT_LESSON )->publish,
		'tracks'    => is_wp_error( $learn_drive_tracks ) ? 0 : (int) $learn_drive_tracks,
	);
	set_transient( 'ld_stats', $learn_drive_stats, 6 * HOUR_IN_SECONDS );
}

$learn_drive_items = array(
	array( $learn_drive_stats['questions'], _n( 'question expliquée', 'questions expliquées', $learn_drive_stats['questions'], 'learn-drive-core' ) ),
	array( $learn_drive_stats['quizzes'], _n( 'quiz et examen blanc', 'quiz et examens blancs', $learn_drive_stats['quizzes'], 'learn-drive-core' ) ),
	array( $learn_drive_stats['lessons'], _n( 'leçon', 'leçons', $learn_drive_stats['lessons'], 'learn-drive-core' ) ),
	array( $learn_drive_stats['tracks'], _n( 'catégorie', 'catégories', $learn_drive_stats['tracks'], 'learn-drive-core' ) ),
);

$learn_drive_html = '';
foreach ( $learn_drive_items as $learn_drive_item ) {
	if ( $learn_drive_item[0] > 0 ) {
		$learn_drive_html .= sprintf( '<li><span class="ld-stats__value">%s</span><span class="ld-stats__label">%s</span></li>', esc_html( number_format_i18n( $learn_drive_item[0] ) ), esc_html( $learn_drive_item[1] ) );
	}
}

if ( $learn_drive_html ) {
	printf( '<ul %s>%s</ul>', get_block_wrapper_attributes( array( 'class' => 'ld-stats' ) ), $learn_drive_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
