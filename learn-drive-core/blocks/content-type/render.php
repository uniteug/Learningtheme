<?php
/**
 * Bloc « Type de contenu ».
 *
 * @package LearnDriveCore
 *
 * @var \WP_Block $block Bloc.
 */

use LearnDrive\Core\Blocks;

defined( 'ABSPATH' ) || exit;

$learn_drive_label = Blocks\type_label( get_post_type( Blocks\current_post_id( $block ) ) );
if ( $learn_drive_label ) {
	printf( '<p %s>%s</p>', get_block_wrapper_attributes( array( 'class' => 'ld-eyebrow' ) ), esc_html( $learn_drive_label ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
