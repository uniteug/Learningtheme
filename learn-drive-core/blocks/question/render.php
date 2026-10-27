<?php
/**
 * Bloc « Question » (page d'une question).
 *
 * @package LearnDriveCore
 *
 * @var array     $attributes Attributs.
 * @var \WP_Block $block      Bloc.
 */

use LearnDrive\Core\Blocks;
use LearnDrive\Core\Render;

defined( 'ABSPATH' ) || exit;

$learn_drive_id = (int) $attributes['questionId'] ? (int) $attributes['questionId'] : Blocks\current_post_id( $block );
if ( \LearnDrive\Core\PT_QUESTION !== get_post_type( $learn_drive_id ) ) {
	return;
}

printf( '<div %s>%s</div>', get_block_wrapper_attributes(), Render\single_question( $learn_drive_id, $attributes['layout'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML échappé par le moteur de rendu.
