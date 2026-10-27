<?php
/**
 * Bloc « Règles de l'examen » (page pilier d'une catégorie).
 *
 * @package LearnDriveCore
 */

use LearnDrive\Core\Quizzes;
use LearnDrive\Core\Permalinks;
use function LearnDrive\Core\human_duration;
use function LearnDrive\Core\format_label;

defined( 'ABSPATH' ) || exit;

$learn_drive_term = is_tax( \LearnDrive\Core\TAX_TRACK ) ? get_queried_object() : null;
if ( ! $learn_drive_term ) {
	return;
}

$learn_drive_items = array();
$learn_drive_age   = (string) get_term_meta( $learn_drive_term->term_id, 'ld_min_age', true );
if ( $learn_drive_age ) {
	/* translators: %s: minimum age */
	$learn_drive_items[ __( 'Âge minimum', 'learn-drive-core' ) ] = sprintf( __( '%s ans', 'learn-drive-core' ), $learn_drive_age );
}

$learn_drive_exam = Permalinks\exam_for_track( $learn_drive_term );
if ( $learn_drive_exam ) {
	$learn_drive_rules = Quizzes\rules( $learn_drive_exam );
	if ( ! empty( $learn_drive_rules['format'] ) ) {
		$learn_drive_items[ __( 'Format', 'learn-drive-core' ) ] = format_label( $learn_drive_rules['format'] );
	}
	$learn_drive_items[ __( 'Questions', 'learn-drive-core' ) ] = number_format_i18n( $learn_drive_rules['count'] );
	if ( $learn_drive_rules['duration'] ) {
		$learn_drive_items[ __( 'Durée', 'learn-drive-core' ) ] = human_duration( $learn_drive_rules['duration'] );
	} elseif ( $learn_drive_rules['per_question'] ) {
		/* translators: %s: duration */
		$learn_drive_items[ __( 'Durée', 'learn-drive-core' ) ] = sprintf( __( '%s par question', 'learn-drive-core' ), human_duration( $learn_drive_rules['per_question'] ) );
	}
	$learn_drive_items[ __( 'Réussite', 'learn-drive-core' ) ] = Quizzes\pass_label( $learn_drive_rules );
}

if ( ! $learn_drive_items ) {
	return;
}

$learn_drive_html = '';
foreach ( $learn_drive_items as $learn_drive_label => $learn_drive_value ) {
	$learn_drive_html .= sprintf( '<div><dt>%s</dt><dd>%s</dd></div>', esc_html( $learn_drive_label ), esc_html( $learn_drive_value ) );
}

printf(
	'<section %1$s><h2 class="ld-facts__heading">%2$s</h2><dl class="ld-facts">%3$s</dl></section>',
	get_block_wrapper_attributes( array( 'class' => 'ld-track-facts' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	/* translators: %s: category name */
	esc_html( sprintf( __( 'L’examen du %s en bref', 'learn-drive-core' ), $learn_drive_term->name ) ),
	$learn_drive_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
