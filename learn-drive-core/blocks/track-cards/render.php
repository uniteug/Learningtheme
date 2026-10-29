<?php
/**
 * Bloc « Programmes phares » : une carte par catégorie avec ses règles d'examen.
 *
 * @package LearnDriveCore
 *
 * @var array $attributes Attributs.
 */

use LearnDrive\Core\Blocks;
use function LearnDrive\Core\setting;
use const LearnDrive\Core\TAX_TRACK;

defined( 'ABSPATH' ) || exit;

$learn_drive_country = '' !== $attributes['country'] ? $attributes['country'] : (string) setting( 'default_country' );
$learn_drive_args    = array(
	'taxonomy'   => TAX_TRACK,
	'hide_empty' => false,
	'number'     => max( 1, min( 24, (int) $attributes['count'] ) ),
	'orderby'    => 'count',
	'order'      => 'DESC',
);
if ( $learn_drive_country && 'all' !== $learn_drive_country ) {
	$learn_drive_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		array(
			'key'   => 'ld_country',
			'value' => $learn_drive_country,
		),
	);
}
$learn_drive_terms = get_terms( $learn_drive_args );
if ( ! $learn_drive_terms || is_wp_error( $learn_drive_terms ) ) {
	return;
}

$learn_drive_items = '';
foreach ( $learn_drive_terms as $learn_drive_term ) {
	$learn_drive_summary = Blocks\track_summary( $learn_drive_term );
	$learn_drive_color   = sanitize_hex_color( (string) get_term_meta( $learn_drive_term->term_id, 'ld_color', true ) );
	$learn_drive_facts   = '';
	foreach ( $learn_drive_summary['facts'] as $learn_drive_fact ) {
		$learn_drive_facts .= '<li>' . esc_html( $learn_drive_fact ) . '</li>';
	}
	$learn_drive_stats = array();
	if ( $learn_drive_summary['lessons'] ) {
		/* translators: %d: number of lessons */
		$learn_drive_stats[] = sprintf( _n( '%d leçon', '%d leçons', $learn_drive_summary['lessons'], 'learn-drive-core' ), $learn_drive_summary['lessons'] );
	}
	if ( $learn_drive_summary['quizzes'] ) {
		/* translators: %d: number of quizzes */
		$learn_drive_stats[] = sprintf( _n( '%d quiz', '%d quiz', $learn_drive_summary['quizzes'], 'learn-drive-core' ), $learn_drive_summary['quizzes'] );
	}

	$learn_drive_items .= sprintf(
		'<li class="ld-track"%1$s><span class="ld-track__icon">%2$s</span><h3 class="ld-track__name"><a href="%3$s">%4$s</a></h3>%5$s%6$s%7$s%8$s</li>',
		$learn_drive_color ? ' style="--ld-track:' . esc_attr( $learn_drive_color ) . '"' : '',
		Blocks\track_icon( $learn_drive_term->slug ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG construit et échappé.
		esc_url( get_term_link( $learn_drive_term ) ),
		esc_html( $learn_drive_term->name ),
		$learn_drive_term->description ? '<p class="ld-track__desc">' . esc_html( wp_trim_words( $learn_drive_term->description, 22 ) ) . '</p>' : '',
		$learn_drive_facts ? '<ul class="ld-track__facts">' . $learn_drive_facts . '</ul>' : '',
		$learn_drive_stats ? '<p class="ld-track__stats">' . esc_html( implode( ' · ', $learn_drive_stats ) ) . '</p>' : '',
		$learn_drive_summary['exam'] ? '<a class="ld-track__exam" href="' . esc_url( get_permalink( $learn_drive_summary['exam'] ) ) . '">' . esc_html__( 'Examen blanc', 'learn-drive-core' ) . '<span aria-hidden="true"> →</span></a>' : ''
	);
}

printf(
	'<div %1$s><ul class="ld-tracks__list%2$s">%3$s</ul></div>',
	get_block_wrapper_attributes( array( 'class' => 'ld-tracks is-' . esc_attr( $attributes['layout'] ) ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'carousel' === $attributes['layout'] ? ' ld-carousel' : '',
	$learn_drive_items // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
);
