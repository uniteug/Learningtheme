<?php
/**
 * Bloc « Contenu de la page pilier ».
 *
 * @package LearnDriveCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_tax() ) {
	return;
}

$learn_drive_term    = get_queried_object();
$learn_drive_content = (string) get_term_meta( $learn_drive_term->term_id, 'ld_content', true );
if ( '' === trim( $learn_drive_content ) ) {
	return;
}

printf( '<div %s>%s</div>', get_block_wrapper_attributes( array( 'class' => 'ld-term-content' ) ), do_blocks( wp_kses_post( $learn_drive_content ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- contenu filtré par wp_kses_post.
