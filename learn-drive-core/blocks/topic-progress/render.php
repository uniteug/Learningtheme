<?php
/**
 * Bloc « Progression par thème » (apprenant connecté uniquement).
 *
 * @package LearnDriveCore
 */

use LearnDrive\Core\Progress;

defined( 'ABSPATH' ) || exit;

$learn_drive_user = get_current_user_id();
if ( ! $learn_drive_user ) {
	return;
}

$learn_drive_topics = Progress\get( $learn_drive_user )['topics'];
if ( ! $learn_drive_topics ) {
	return;
}

$learn_drive_html = '';
foreach ( $learn_drive_topics as $learn_drive_term_id => $learn_drive_stat ) {
	$learn_drive_term = get_term( (int) $learn_drive_term_id, \LearnDrive\Core\TAX_TOPIC );
	if ( ! $learn_drive_term || is_wp_error( $learn_drive_term ) || ! $learn_drive_stat['seen'] ) {
		continue;
	}
	$learn_drive_pct   = (int) round( 100 * $learn_drive_stat['correct'] / $learn_drive_stat['seen'] );
	$learn_drive_html .= sprintf(
		'<li><a href="%1$s">%2$s</a><span class="ld-meter" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="%3$d" aria-label="%2$s"><span style="width:%3$d%%"></span></span><span class="ld-meter__value">%3$d %%</span></li>',
		esc_url( get_term_link( $learn_drive_term ) ),
		esc_html( $learn_drive_term->name ),
		$learn_drive_pct
	);
}

if ( $learn_drive_html ) {
	printf(
		'<section %1$s><h2 class="ld-progress__heading">%2$s</h2><ul class="ld-progress__list">%3$s</ul></section>',
		get_block_wrapper_attributes( array( 'class' => 'ld-progress' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html__( 'Votre progression', 'learn-drive-core' ),
		$learn_drive_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}
