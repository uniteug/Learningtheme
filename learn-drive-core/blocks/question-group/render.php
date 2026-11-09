<?php
/**
 * Bloc « Série de la question » : les affirmations de la même série officielle
 * (même quesito, même figure), chacune avec sa réponse. Pour l'élève, c'est la
 * révision complète d'une figure ; pour Google, une fiche riche plutôt qu'une
 * phrase isolée.
 *
 * @package LearnDriveCore
 *
 * @var WP_Block $block Bloc.
 */

use LearnDrive\Core\Questions;

defined( 'ABSPATH' ) || exit;

$learn_drive_id  = (int) ( $block->context['postId'] ?? get_the_ID() );
$learn_drive_ids = $learn_drive_id ? Questions\group_ids( $learn_drive_id ) : array();
if ( count( $learn_drive_ids ) < 2 ) {
	return;
}

_prime_post_caches( $learn_drive_ids, false, true );
$learn_drive_rows = '';
foreach ( $learn_drive_ids as $learn_drive_other ) {
	$learn_drive_answer = Questions\correct_answer( $learn_drive_other );
	$learn_drive_title  = esc_html( get_the_title( $learn_drive_other ) );
	$learn_drive_rows  .= sprintf(
		'<tr%1$s><td>%2$s</td><td class="ld-group__answer">%3$s</td></tr>',
		$learn_drive_other === $learn_drive_id ? ' class="is-current" aria-current="true"' : '',
		$learn_drive_other === $learn_drive_id ? '<strong>' . $learn_drive_title . '</strong>' : sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $learn_drive_other ) ), $learn_drive_title ),
		esc_html( $learn_drive_answer )
	);
}

printf(
	'<section %1$s><h2 class="ld-group__heading">%2$s</h2><p class="ld-group__intro">%3$s</p><div class="ld-group__table"><table><thead><tr><th scope="col">%4$s</th><th scope="col">%5$s</th></tr></thead><tbody>%6$s</tbody></table></div></section>',
	get_block_wrapper_attributes( array( 'class' => 'ld-group' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	esc_html__( 'Les autres affirmations de cette série', 'learn-drive-core' ),
	/* translators: %d: number of statements */
	esc_html( sprintf( _n( '%d affirmation sur la même figure et le même sujet, avec la bonne réponse : à l’examen, n’importe laquelle peut tomber.', '%d affirmations sur la même figure et le même sujet, avec la bonne réponse : à l’examen, n’importe laquelle peut tomber.', count( $learn_drive_ids ), 'learn-drive-core' ), count( $learn_drive_ids ) ) ),
	esc_html__( 'Affirmation', 'learn-drive-core' ),
	esc_html__( 'Réponse', 'learn-drive-core' ),
	$learn_drive_rows // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
);
