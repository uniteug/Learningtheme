<?php
/**
 * Bloc « Programme du cours ».
 *
 * @package LearnDriveCore
 *
 * @var \WP_Block $block Bloc.
 */

use LearnDrive\Core\Blocks;
use LearnDrive\Core\Access;

defined( 'ABSPATH' ) || exit;

$learn_drive_post_id = Blocks\current_post_id( $block );
if ( \LearnDrive\Core\PT_LESSON === get_post_type( $learn_drive_post_id ) ) {
	$learn_drive_post_id = (int) get_post_meta( $learn_drive_post_id, '_ld_course', true );
}
if ( \LearnDrive\Core\PT_COURSE !== get_post_type( $learn_drive_post_id ) ) {
	return;
}

$learn_drive_lessons = Blocks\course_lessons( $learn_drive_post_id );
if ( ! $learn_drive_lessons ) {
	return;
}

$learn_drive_items = '';
foreach ( $learn_drive_lessons as $learn_drive_lesson ) {
	$learn_drive_rule  = Access\rule( $learn_drive_lesson );
	$learn_drive_badge = '';
	if ( 'free' !== $learn_drive_rule ) {
		$learn_drive_open  = Access\can_access( $learn_drive_lesson );
		$learn_drive_badge = sprintf(
			'<span class="ld-badge %s">%s</span>',
			$learn_drive_open ? 'ld-badge--updated' : 'ld-badge--premium',
			esc_html( $learn_drive_open ? __( 'Débloqué', 'learn-drive-core' ) : ( 'registered' === $learn_drive_rule ? __( 'Gratuit avec compte', 'learn-drive-core' ) : __( 'Premium', 'learn-drive-core' ) ) )
		);
	}
	$learn_drive_items .= sprintf(
		'<li class="ld-outline__item"><a href="%1$s"><span class="ld-outline__title">%2$s</span>%3$s</a><span class="ld-outline__excerpt">%4$s</span></li>',
		esc_url( get_permalink( $learn_drive_lesson ) ),
		esc_html( get_the_title( $learn_drive_lesson ) ),
		$learn_drive_badge,
		esc_html( wp_trim_words( get_the_excerpt( $learn_drive_lesson ), 18 ) )
	);
}

printf(
	'<section %1$s><h2 class="ld-outline__heading">%2$s</h2><ol class="ld-outline__list">%3$s</ol></section>',
	get_block_wrapper_attributes( array( 'class' => 'ld-outline' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	/* translators: %d: number of lessons */
	esc_html( sprintf( _n( 'Programme : %d leçon', 'Programme : %d leçons', count( $learn_drive_lessons ), 'learn-drive-core' ), count( $learn_drive_lessons ) ) ),
	$learn_drive_items // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé ci-dessus.
);
