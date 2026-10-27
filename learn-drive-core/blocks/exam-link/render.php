<?php
/**
 * Bloc « Bouton examen blanc » (page pilier).
 *
 * @package LearnDriveCore
 */

use LearnDrive\Core\Permalinks;

defined( 'ABSPATH' ) || exit;

$learn_drive_term = is_tax( \LearnDrive\Core\TAX_TRACK ) ? get_queried_object() : \LearnDrive\Core\primary_term( get_the_ID(), \LearnDrive\Core\TAX_TRACK );
if ( ! $learn_drive_term ) {
	return;
}

$learn_drive_exam   = Permalinks\exam_for_track( $learn_drive_term );
$learn_drive_quizzz = home_url( user_trailingslashit( $learn_drive_term->slug . '/' . \LearnDrive\Core\setting( 'slug_quiz' ) ) );

$learn_drive_html = '';
if ( $learn_drive_exam ) {
	/* translators: %s: category name */
	$learn_drive_html .= sprintf( '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%s">%s</a></div>', esc_url( get_permalink( $learn_drive_exam ) ), esc_html( sprintf( __( 'Lancer un examen blanc %s', 'learn-drive-core' ), $learn_drive_term->name ) ) );
}
$learn_drive_html .= sprintf( '<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="%s">%s</a></div>', esc_url( $learn_drive_quizzz ), esc_html__( 'Quiz par thème', 'learn-drive-core' ) );

printf( '<div %s>%s</div>', get_block_wrapper_attributes( array( 'class' => 'wp-block-buttons ld-exam-link' ) ), $learn_drive_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
