<?php
/**
 * Bloc « Leçon précédente / suivante ».
 *
 * @package LearnDriveCore
 *
 * @var \WP_Block $block Bloc.
 */

use LearnDrive\Core\Blocks;

defined( 'ABSPATH' ) || exit;

$learn_drive_post_id = Blocks\current_post_id( $block );
if ( \LearnDrive\Core\PT_LESSON !== get_post_type( $learn_drive_post_id ) ) {
	return;
}

$learn_drive_prev = Blocks\adjacent_lesson( $learn_drive_post_id, true );
$learn_drive_next = Blocks\adjacent_lesson( $learn_drive_post_id, false );
if ( ! $learn_drive_prev && ! $learn_drive_next ) {
	return;
}

$learn_drive_html = '';
if ( $learn_drive_prev ) {
	$learn_drive_html .= sprintf( '<a class="ld-lesson-nav__prev" href="%s" rel="prev"><span>%s</span>%s</a>', esc_url( get_permalink( $learn_drive_prev ) ), esc_html__( '← Leçon précédente', 'learn-drive-core' ), esc_html( get_the_title( $learn_drive_prev ) ) );
}
if ( $learn_drive_next ) {
	$learn_drive_html .= sprintf( '<a class="ld-lesson-nav__next" href="%s" rel="next"><span>%s</span>%s</a>', esc_url( get_permalink( $learn_drive_next ) ), esc_html__( 'Leçon suivante →', 'learn-drive-core' ), esc_html( get_the_title( $learn_drive_next ) ) );
}

printf( '<nav %s aria-label="%s">%s</nav>', get_block_wrapper_attributes( array( 'class' => 'ld-lesson-nav' ) ), esc_attr__( 'Navigation entre les leçons', 'learn-drive-core' ), $learn_drive_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
