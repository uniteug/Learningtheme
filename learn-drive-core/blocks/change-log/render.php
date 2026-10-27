<?php
/**
 * Bloc « Historique des mises à jour ».
 *
 * @package LearnDriveCore
 *
 * @var \WP_Block $block Bloc.
 */

use LearnDrive\Core\Blocks;

defined( 'ABSPATH' ) || exit;

$learn_drive_post_id = Blocks\current_post_id( $block );
$learn_drive_log     = get_post_meta( $learn_drive_post_id, '_ld_changelog', true );
if ( ! is_array( $learn_drive_log ) || ! $learn_drive_log ) {
	return;
}

usort(
	$learn_drive_log,
	static function ( $a, $b ) {
		return strcmp( (string) $b['date'], (string) $a['date'] );
	}
);

$learn_drive_items = '';
foreach ( $learn_drive_log as $learn_drive_entry ) {
	$learn_drive_items .= sprintf(
		'<li><time datetime="%1$s">%2$s</time> — %3$s</li>',
		esc_attr( $learn_drive_entry['date'] ),
		esc_html( wp_date( get_option( 'date_format' ), strtotime( $learn_drive_entry['date'] ) ) ),
		esc_html( $learn_drive_entry['note'] )
	);
}

printf(
	'<details %1$s><summary>%2$s</summary><ul>%3$s</ul></details>',
	get_block_wrapper_attributes( array( 'class' => 'ld-changelog' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	esc_html__( 'Historique des mises à jour', 'learn-drive-core' ),
	$learn_drive_items // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
