<?php
/**
 * Image à la une automatique des articles : un article sans image n'a ni
 * vignette dans les listes, ni image pour Google (données structurées,
 * Discover, Images) et les réseaux sociaux. On lui donne une vraie image
 * (1200 × 630, titre et rubrique aux couleurs du site), enregistrée dans la
 * médiathèque avec son texte alternatif : remplaçable à tout moment par une
 * photo.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\SEO\Featured;

use function LearnDrive\Core\setting;

defined( 'ABSPATH' ) || exit;

/**
 * Types concernés.
 *
 * @return string[]
 */
function post_types() {
	/**
	 * Types de contenus qui reçoivent une image à la une générée.
	 *
	 * @param string[] $types Types.
	 */
	return (array) apply_filters( 'learn_drive_auto_featured_types', array( 'post' ) );
}

/**
 * Donne une image à la une générée à un article qui n'en a pas.
 *
 * @param int $post_id Article.
 * @return int ID de l'image, 0 sinon.
 */
function ensure( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'publish' !== $post->post_status || ! in_array( $post->post_type, post_types(), true ) || has_post_thumbnail( $post ) || ! setting( 'auto_featured' ) ) {
		return 0;
	}
	$file = \LearnDrive\Core\SEO\OG\generate( $post->ID );
	if ( ! $file ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	// Copie (le fichier source reste le cache des images de partage).
	$tmp = wp_tempnam( 'ld-featured' );
	if ( ! $tmp || ! copy( $file, $tmp ) ) {
		return 0;
	}
	$title = html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' );
	$id    = media_handle_sideload(
		array(
			'name'     => sanitize_title( $post->post_name ? $post->post_name : $title ) . '.png',
			'tmp_name' => $tmp,
		),
		$post->ID,
		$title,
		array( 'post_author' => $post->post_author )
	);
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	update_post_meta( $id, '_wp_attachment_image_alt', $title );
	update_post_meta( $id, '_ld_generated', 1 );
	set_post_thumbnail( $post->ID, $id );
	return (int) $id;
}

/**
 * À la publication (après l'enregistrement des rubriques).
 *
 * @param int      $post_id Article.
 * @param \WP_Post $post    Article.
 */
function on_save( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'publish' !== $post->post_status ) {
		return;
	}
	ensure( $post_id );
}
add_action( 'wp_after_insert_post', __NAMESPACE__ . '\\on_save', 20, 2 );

/**
 * Articles déjà publiés sans image : traités par lots en arrière-plan.
 */
function backfill() {
	if ( ! setting( 'auto_featured' ) ) {
		return;
	}
	$ids = get_posts(
		array(
			'post_type'      => post_types(),
			'post_status'    => 'publish',
			'posts_per_page' => 15,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);
	$done = 0;
	foreach ( $ids as $id ) {
		$done += ensure( $id ) ? 1 : 0;
	}
	if ( $done && count( $ids ) === 15 ) {
		wp_schedule_single_event( time() + 30, 'learn_drive_featured_backfill' );
	} else {
		update_option( 'ld_featured_backfill', LEARN_DRIVE_CORE_VERSION, false );
	}
}
add_action( 'learn_drive_featured_backfill', __NAMESPACE__ . '\\backfill' );

/**
 * Lance le rattrapage une fois par version du plugin.
 */
function schedule_backfill() {
	if ( setting( 'auto_featured' ) && get_option( 'ld_featured_backfill' ) !== LEARN_DRIVE_CORE_VERSION && ! wp_next_scheduled( 'learn_drive_featured_backfill' ) ) {
		update_option( 'ld_featured_backfill', 'running', false );
		wp_schedule_single_event( time() + 10, 'learn_drive_featured_backfill' );
	}
}
add_action( 'admin_init', __NAMESPACE__ . '\\schedule_backfill' );
add_action( 'rest_api_init', __NAMESPACE__ . '\\schedule_backfill' );

/**
 * Article qui s'ouvre déjà sur une bannière (SVG ou image) : l'image générée
 * sert aux listes, au partage et à Google, pas en tête de l'article (doublon).
 *
 * @param string    $content  Rendu du bloc.
 * @param array     $block    Bloc.
 * @param \WP_Block $instance Instance (contexte : contenu affiché).
 * @return string
 */
function hide_duplicate_banner( $content, $block, $instance = null ) {
	unset( $block );
	if ( ! is_singular( post_types() ) ) {
		return $content;
	}
	$post_id = get_queried_object_id();
	$shown   = $instance instanceof \WP_Block && isset( $instance->context['postId'] ) ? (int) $instance->context['postId'] : $post_id;
	if ( $shown !== $post_id ) {
		return $content; // Vignette d'un autre article (liste, articles liés).
	}
	$thumb = get_post_thumbnail_id( $post_id );
	if ( ! $thumb || ! get_post_meta( $thumb, '_ld_generated', true ) ) {
		return $content;
	}
	$raw = ltrim( (string) get_post_field( 'post_content', $post_id ) );
	return preg_match( '#^<!-- wp:(html|image)[^>]*-->\s*(<svg|<figure|<img)#', $raw ) ? '' : $content;
}
add_filter( 'render_block_core/post-featured-image', __NAMESPACE__ . '\\hide_duplicate_banner', 10, 3 );
