<?php
/**
 * Bloc « Sommaire ».
 *
 * @package LearnDriveCore
 *
 * @var array     $attributes Attributs.
 * @var \WP_Block $block      Bloc.
 */

use LearnDrive\Core\Blocks;
use LearnDrive\Core\SEO\Links;

defined( 'ABSPATH' ) || exit;

$learn_drive_post_id = Blocks\current_post_id( $block );
if ( ! $learn_drive_post_id || ! \LearnDrive\Core\Access\can_access( $learn_drive_post_id ) && \LearnDrive\Core\PT_LESSON === get_post_type( $learn_drive_post_id ) ) {
	return;
}

$learn_drive_headings = Links\headings( get_post_field( 'post_content', $learn_drive_post_id ) );
if ( count( $learn_drive_headings ) < 2 ) {
	return;
}

$learn_drive_items = '';
foreach ( $learn_drive_headings as $learn_drive_heading ) {
	$learn_drive_items .= sprintf( '<li><a href="#%s">%s</a></li>', esc_attr( $learn_drive_heading['id'] ), esc_html( $learn_drive_heading['text'] ) );
}

$learn_drive_title = $attributes['title'] ? $attributes['title'] : __( 'Sommaire', 'learn-drive-core' );

printf(
	'<nav %1$s aria-labelledby="ld-toc-title"><p class="ld-toc__title" id="ld-toc-title">%2$s</p><ol class="ld-toc__list">%3$s</ol></nav>',
	get_block_wrapper_attributes( array( 'class' => 'ld-toc' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	esc_html( $learn_drive_title ),
	$learn_drive_items // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé ci-dessus.
);
