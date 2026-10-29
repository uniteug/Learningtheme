<?php
/**
 * Bloc « Pays » : une carte par pays couvert (drapeau, catégories, questions).
 *
 * @package LearnDriveCore
 *
 * @var array $attributes Attributs.
 */

use const LearnDrive\Core\TAX_COUNTRY;
use const LearnDrive\Core\TAX_TRACK;

defined( 'ABSPATH' ) || exit;

$learn_drive_countries = get_terms(
	array(
		'taxonomy'   => TAX_COUNTRY,
		'hide_empty' => false,
		'number'     => max( 1, min( 40, (int) $attributes['count'] ) ),
		'orderby'    => 'count',
		'order'      => 'DESC',
	)
);
if ( ! $learn_drive_countries || is_wp_error( $learn_drive_countries ) ) {
	return;
}

// Le pays principal du site en premier.
$learn_drive_main = (string) \LearnDrive\Core\setting( 'default_country' );
usort(
	$learn_drive_countries,
	static fn( $a, $b ) => (int) ( $b->slug === $learn_drive_main ) <=> (int) ( $a->slug === $learn_drive_main )
);

$learn_drive_items = '';
foreach ( $learn_drive_countries as $learn_drive_country ) {
	$learn_drive_tracks = get_terms(
		array(
			'taxonomy'   => TAX_TRACK,
			'hide_empty' => false,
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => 'ld_country',
					'value' => $learn_drive_country->slug,
				),
			),
		)
	);
	$learn_drive_tracks = is_wp_error( $learn_drive_tracks ) ? array() : $learn_drive_tracks;

	$learn_drive_flag   = '';
	$learn_drive_colors = array_filter( array_map( 'sanitize_hex_color', array_map( 'trim', explode( ',', (string) get_term_meta( $learn_drive_country->term_id, 'ld_flag', true ) ) ) ) );
	foreach ( $learn_drive_colors as $learn_drive_color ) {
		$learn_drive_flag .= '<span style="background:' . esc_attr( $learn_drive_color ) . '"></span>';
	}

	$learn_drive_links = '';
	foreach ( array_slice( $learn_drive_tracks, 0, 4 ) as $learn_drive_track ) {
		$learn_drive_links .= '<li><a href="' . esc_url( get_term_link( $learn_drive_track ) ) . '">' . esc_html( $learn_drive_track->name ) . '</a></li>';
	}

	$learn_drive_items .= sprintf(
		'<li class="ld-country">%1$s<h3 class="ld-country__name">%2$s</h3><p class="ld-country__meta">%3$s</p>%4$s</li>',
		$learn_drive_flag ? '<span class="ld-country__flag" aria-hidden="true">' . $learn_drive_flag . '</span>' : '',
		esc_html( $learn_drive_country->name ),
		/* translators: %d: number of questions */
		esc_html( sprintf( _n( '%d question', '%d questions', $learn_drive_country->count, 'learn-drive-core' ), $learn_drive_country->count ) ),
		$learn_drive_links ? '<ul class="ld-country__tracks">' . $learn_drive_links . '</ul>' : ''
	);
}

printf(
	'<div %1$s><ul class="ld-countries__list">%2$s</ul></div>',
	get_block_wrapper_attributes( array( 'class' => 'ld-countries' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$learn_drive_items // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
);
