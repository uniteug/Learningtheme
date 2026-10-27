<?php
/**
 * Bloc « Badge d'accès ».
 *
 * @package LearnDriveCore
 *
 * @var \WP_Block $block Bloc.
 */

use LearnDrive\Core\Blocks;
use LearnDrive\Core\Access;

defined( 'ABSPATH' ) || exit;

$learn_drive_post_id = Blocks\current_post_id( $block );
if ( ! $learn_drive_post_id ) {
	return;
}

$learn_drive_rule = Access\rule( $learn_drive_post_id );
if ( 'free' === $learn_drive_rule ) {
	return;
}

if ( Access\can_access( $learn_drive_post_id ) ) {
	$learn_drive_label = __( 'Débloqué', 'learn-drive-core' );
	$learn_drive_class = 'ld-badge--updated';
} elseif ( 'registered' === $learn_drive_rule ) {
	$learn_drive_label = __( 'Gratuit avec compte', 'learn-drive-core' );
	$learn_drive_class = '';
} else {
	$learn_drive_label = __( 'Premium', 'learn-drive-core' );
	$learn_drive_class = 'ld-badge--premium';
}

printf( '<span %s>%s</span>', get_block_wrapper_attributes( array( 'class' => trim( 'ld-badge ' . $learn_drive_class ) ) ), esc_html( $learn_drive_label ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
