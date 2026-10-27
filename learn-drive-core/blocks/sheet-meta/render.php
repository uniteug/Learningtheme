<?php
/**
 * Bloc « Informations de la fiche ».
 *
 * @package LearnDriveCore
 *
 * @var \WP_Block $block Bloc.
 */

use LearnDrive\Core\Blocks;

defined( 'ABSPATH' ) || exit;

$learn_drive_post_id = Blocks\current_post_id( $block );
$learn_drive_code    = (string) get_post_meta( $learn_drive_post_id, '_ld_code', true );
$learn_drive_count   = count(
	get_posts(
		array(
			'post_type'      => \LearnDrive\Core\PT_QUESTION,
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_ld_sheet',
					'value' => $learn_drive_post_id,
					'type'  => 'NUMERIC',
				),
			),
		)
	)
);

$learn_drive_items = '';
if ( $learn_drive_code ) {
	$learn_drive_items .= sprintf( '<div><dt>%s</dt><dd>%s</dd></div>', esc_html__( 'Code officiel', 'learn-drive-core' ), esc_html( $learn_drive_code ) );
}
if ( $learn_drive_count ) {
	$learn_drive_items .= sprintf( '<div><dt>%s</dt><dd>%s</dd></div>', esc_html__( 'Questions liées', 'learn-drive-core' ), esc_html( number_format_i18n( $learn_drive_count ) ) );
}

if ( $learn_drive_items ) {
	printf( '<dl %s>%s</dl>', get_block_wrapper_attributes( array( 'class' => 'ld-facts' ) ), $learn_drive_items ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
