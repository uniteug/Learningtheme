<?php
/**
 * Bloc « Date de mise à jour ».
 *
 * @package LearnDriveCore
 *
 * @var \WP_Block $block Bloc.
 */

use LearnDrive\Core\Blocks;

defined( 'ABSPATH' ) || exit;

$learn_drive_post_id = Blocks\current_post_id( $block );
if ( ! $learn_drive_post_id ) {
	return;
}

$learn_drive_published = (int) get_post_time( 'U', true, $learn_drive_post_id );
$learn_drive_modified  = (int) get_post_modified_time( 'U', true, $learn_drive_post_id );
$learn_drive_reviewed  = (string) get_post_meta( $learn_drive_post_id, '_ld_reviewed', true );

if ( $learn_drive_reviewed && strtotime( $learn_drive_reviewed ) > $learn_drive_modified ) {
	$learn_drive_modified = (int) strtotime( $learn_drive_reviewed );
}

// Une correction de coquille le jour même ne justifie pas un badge « Mis à jour ».
if ( $learn_drive_modified - $learn_drive_published < DAY_IN_SECONDS ) {
	return;
}

printf(
	'<p %1$s><span class="ld-badge ld-badge--updated">%2$s</span> <time datetime="%3$s">%4$s</time></p>',
	get_block_wrapper_attributes( array( 'class' => 'ld-updated' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	esc_html__( 'Mis à jour', 'learn-drive-core' ),
	esc_attr( gmdate( 'c', $learn_drive_modified ) ),
	/* translators: %s: date */
	esc_html( sprintf( __( 'le %s', 'learn-drive-core' ), wp_date( get_option( 'date_format' ), $learn_drive_modified ) ) )
);
