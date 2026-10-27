<?php
/**
 * Bloc « Contenus liés » : maillage interne automatique.
 *
 * @package LearnDriveCore
 *
 * @var array     $attributes Attributs.
 * @var \WP_Block $block      Bloc.
 */

use LearnDrive\Core\Blocks;

defined( 'ABSPATH' ) || exit;

$learn_drive_type = post_type_exists( $attributes['postType'] ) ? $attributes['postType'] : 'post';
$learn_drive_ids  = Blocks\related_ids( $learn_drive_type, $attributes['variant'], max( 1, min( 24, (int) $attributes['count'] ) ), $block );
$learn_drive_head = $attributes['heading'] ? $attributes['heading'] : Blocks\variant_heading( $attributes['variant'], $learn_drive_type );

echo Blocks\render_list( $learn_drive_ids, $attributes['layout'], $learn_drive_head, 'is-type-' . $learn_drive_type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML échappé à la construction.
