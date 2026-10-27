<?php
/**
 * Bloc « Quiz ».
 *
 * @package LearnDriveCore
 *
 * @var array     $attributes Attributs.
 * @var \WP_Block $block      Bloc.
 */

use LearnDrive\Core\Blocks;
use LearnDrive\Core\Render;

defined( 'ABSPATH' ) || exit;

$learn_drive_post_id = Blocks\current_post_id( $block );

if ( 'related' === $attributes['source'] ) {
	$learn_drive_related = Blocks\related_questions( $learn_drive_post_id, max( 1, min( 10, (int) $attributes['count'] ) ) );
	if ( ! $learn_drive_related['ids'] ) {
		return;
	}
	$learn_drive_html = Render\quiz(
		array(
			'question_ids' => $learn_drive_related['ids'],
			'layout'       => 'stack',
			'mode'         => 'practice',
			'compact'      => true,
			'heading'      => $attributes['heading'] ? $attributes['heading'] : __( 'Testez-vous', 'learn-drive-core' ),
			'more_url'     => $learn_drive_related['quiz'] ? get_permalink( $learn_drive_related['quiz'] ) : '',
			/* translators: %s: quiz title */
			'more_label'   => $learn_drive_related['quiz'] ? sprintf( __( 'Faire le quiz complet : %s', 'learn-drive-core' ), get_the_title( $learn_drive_related['quiz'] ) ) : '',
			'link_stems'   => true,
		)
	);
} else {
	$learn_drive_quiz_id = (int) $attributes['quizId'];
	if ( ! $learn_drive_quiz_id && \LearnDrive\Core\PT_QUIZ === get_post_type( $learn_drive_post_id ) ) {
		$learn_drive_quiz_id = $learn_drive_post_id;
	}
	if ( ! $learn_drive_quiz_id || 'publish' !== get_post_status( $learn_drive_quiz_id ) ) {
		return;
	}
	$learn_drive_session = ( get_queried_object_id() === $learn_drive_quiz_id && isset( $_GET['ld_session'] ) ) ? sanitize_text_field( wp_unslash( $_GET['ld_session'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- jeton signé vérifié par le moteur.
	$learn_drive_html    = Render\quiz(
		array(
			'quiz_id'       => $learn_drive_quiz_id,
			'session'       => $learn_drive_session,
			'compact'       => (bool) $attributes['compact'],
			'heading'       => $attributes['heading'],
			'heading_level' => $attributes['heading'] ? 3 : 2,
		)
	);
}

if ( '' === $learn_drive_html ) {
	return;
}

printf( '<div %s>%s</div>', get_block_wrapper_attributes( array( 'class' => 'ld-quiz-block' ) ), $learn_drive_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML échappé par le moteur de rendu.
