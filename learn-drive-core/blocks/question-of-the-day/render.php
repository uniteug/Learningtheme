<?php
/**
 * Bloc « Question du jour ».
 *
 * @package LearnDriveCore
 *
 * @var array $attributes Attributs.
 */

use LearnDrive\Core\Render;
use LearnDrive\Core\Access;

defined( 'ABSPATH' ) || exit;

$learn_drive_country = $attributes['track'] ? '' : (string) \LearnDrive\Core\setting( 'default_country' );
$learn_drive_key     = 'ld_qotd_' . wp_date( 'Ymd' ) . '_' . sanitize_key( $attributes['track'] . $learn_drive_country );
$learn_drive_id      = (int) get_transient( $learn_drive_key );

if ( ! $learn_drive_id || 'publish' !== get_post_status( $learn_drive_id ) ) {
	$learn_drive_args = array(
		'post_type'      => \LearnDrive\Core\PT_QUESTION,
		'post_status'    => 'publish',
		'posts_per_page' => 200,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'orderby'        => 'ID',
		'order'          => 'ASC',
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => '_ld_noindex',
				'compare' => 'NOT EXISTS',
			),
		),
	);
	if ( $attributes['track'] ) {
		$learn_drive_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => \LearnDrive\Core\TAX_TRACK,
				'field'    => 'slug',
				'terms'    => sanitize_title( $attributes['track'] ),
			),
		);
	} elseif ( $learn_drive_country ) {
		$learn_drive_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => \LearnDrive\Core\TAX_COUNTRY,
				'field'    => 'slug',
				'terms'    => $learn_drive_country,
			),
		);
	}
	$learn_drive_pool = array_values(
		array_filter(
			get_posts( $learn_drive_args ),
			static function ( $id ) {
				return 'free' === Access\rule( $id );
			}
		)
	);
	if ( ! $learn_drive_pool ) {
		return;
	}
	$learn_drive_id = $learn_drive_pool[ abs( crc32( wp_date( 'Y-m-d' ) ) ) % count( $learn_drive_pool ) ];
	set_transient( $learn_drive_key, $learn_drive_id, DAY_IN_SECONDS );
}

$learn_drive_html = Render\quiz(
	array(
		'question_ids'  => array( $learn_drive_id ),
		'layout'        => 'stack',
		'mode'          => 'practice',
		'compact'       => true,
		'link_stems'    => false,
		'more_url'      => get_permalink( $learn_drive_id ),
		'more_label'    => __( 'Voir l’explication complète', 'learn-drive-core' ),
		'class'         => 'is-qotd',
		'heading_level' => 0,
	)
);

printf( '<div %s>%s</div>', get_block_wrapper_attributes(), $learn_drive_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML échappé par le moteur de rendu.
