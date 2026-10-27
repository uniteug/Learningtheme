<?php
/**
 * Bloc « Introduction d'archive ».
 *
 * @package LearnDriveCore
 */

defined( 'ABSPATH' ) || exit;

$learn_drive_text = '';
if ( is_post_type_archive() ) {
	$learn_drive_type   = get_query_var( 'post_type' );
	$learn_drive_object = get_post_type_object( is_array( $learn_drive_type ) ? reset( $learn_drive_type ) : $learn_drive_type );
	if ( ! $learn_drive_object ) {
		return;
	}
	$learn_drive_custom = get_option( 'ld_archive_intro_' . ( $learn_drive_object->name ?? '' ) );
	$learn_drive_text   = $learn_drive_custom ? $learn_drive_custom : ( $learn_drive_object->description ?? '' );
	if ( get_query_var( \LearnDrive\Core\TAX_TRACK ) ) {
		$learn_drive_term = get_term_by( 'slug', get_query_var( \LearnDrive\Core\TAX_TRACK ), \LearnDrive\Core\TAX_TRACK );
		if ( $learn_drive_term ) {
			/* translators: 1: content type, 2: category */
			$learn_drive_text = sprintf( __( '%1$s — %2$s.', 'learn-drive-core' ), $learn_drive_object->labels->name, $learn_drive_term->name ) . ' ' . $learn_drive_text;
		}
	}
} elseif ( is_tax() || is_category() || is_tag() ) {
	$learn_drive_text = wp_strip_all_tags( term_description() );
}

if ( $learn_drive_text ) {
	printf( '<p %s>%s</p>', get_block_wrapper_attributes( array( 'class' => 'ld-standfirst' ) ), esc_html( $learn_drive_text ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
