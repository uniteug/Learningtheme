<?php
/**
 * Images et médias : formats modernes (AVIF / WebP) avec original conservé,
 * qualité réglable, tailles utiles uniquement, noms de fichiers lisibles,
 * façade vidéo et compatibilité CDN.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Media;

use function LearnDrive\Core\setting;

defined( 'ABSPATH' ) || exit;

/**
 * Format de sortie des tailles générées (l'original JPEG / PNG est conservé).
 *
 * @param array $formats Correspondances.
 * @return array
 */
function output_format( $formats ) {
	$target = setting( 'image_format' );
	if ( ! in_array( $target, array( 'webp', 'avif' ), true ) ) {
		return $formats;
	}

	$mime = 'image/' . $target;
	if ( ! wp_image_editor_supports( array( 'mime_type' => $mime ) ) ) {
		if ( 'avif' === $target && wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
			$mime = 'image/webp';
		} else {
			return $formats;
		}
	}

	$formats['image/jpeg'] = $mime;
	$formats['image/png']  = $mime;
	return $formats;
}
add_filter( 'image_editor_output_format', __NAMESPACE__ . '\\output_format' );

/**
 * Qualité de compression (75 par défaut).
 *
 * @return int
 */
function quality() {
	return max( 40, min( 95, (int) setting( 'image_quality' ) ) );
}
add_filter( 'wp_editor_set_quality', __NAMESPACE__ . '\\quality' );
add_filter( 'jpeg_quality', __NAMESPACE__ . '\\quality' );

/**
 * Supprime les tailles jamais utilisées par les gabarits.
 *
 * @param array $sizes Tailles.
 * @return array
 */
function trim_sizes( $sizes ) {
	if ( setting( 'trim_image_sizes' ) ) {
		unset( $sizes['1536x1536'], $sizes['2048x2048'], $sizes['medium_large'] );
	}
	return $sizes;
}
add_filter( 'intermediate_image_sizes_advanced', __NAMESPACE__ . '\\trim_sizes' );

/**
 * Renomme le fichier à partir du titre du contenu (panneau-cedez-le-passage.jpg).
 *
 * @param array $file Fichier envoyé.
 * @return array
 */
function rename_upload( $file ) {
	if ( ! setting( 'rename_uploads' ) ) {
		return $file;
	}

	$extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
	$base      = pathinfo( $file['name'], PATHINFO_FILENAME );

	// phpcs:ignore WordPress.Security.NonceVerification -- le formulaire d'envoi est vérifié par WordPress.
	$parent = isset( $_REQUEST['post_id'] ) ? (int) $_REQUEST['post_id'] : 0;
	if ( $parent && get_post( $parent ) && '' !== get_the_title( $parent ) && preg_match( '/^(img|image|dsc|pxl|photo|screenshot|capture)[\W_]*\d*/i', $base ) ) {
		$base = get_the_title( $parent );
	}

	$file['name'] = sanitize_title( remove_accents( $base ) ) . '.' . $extension;
	return $file;
}
add_filter( 'wp_handle_upload_prefilter', __NAMESPACE__ . '\\rename_upload' );

/**
 * Remplace l'iframe YouTube / Vimeo par une façade : rien n'est chargé avant le clic.
 *
 * @param string $content Rendu du bloc.
 * @param array  $block   Bloc.
 * @return string
 */
function video_facade( $content, $block ) {
	if ( ! setting( 'video_facade' ) || is_admin() || wp_is_json_request() ) {
		return $content;
	}

	$url = $block['attrs']['url'] ?? '';
	$src = '';

	if ( preg_match( '#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/(?:embed|shorts)/)([A-Za-z0-9_-]{11})#', $url, $m ) ) {
		$src = 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0';
	} elseif ( preg_match( '#vimeo\.com/(?:video/)?(\d+)#', $url, $m ) ) {
		$src = 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&dnt=1';
	}

	if ( ! $src ) {
		return $content;
	}

	wp_enqueue_script_module( 'learn-drive-video' );
	wp_enqueue_style( 'learn-drive-core-blocks' );

	$title   = '';
	$caption = '';
	if ( preg_match( '#<figcaption[^>]*>(.*?)</figcaption>#is', $content, $cm ) ) {
		$caption = $cm[0];
		$title   = wp_strip_all_tags( $cm[1] );
	}
	if ( '' === $title ) {
		$title = __( 'Vidéo', 'learn-drive-core' );
	}

	$context = wp_json_encode(
		array(
			'src'   => $src,
			'title' => $title,
		)
	);

	return sprintf(
		'<figure class="wp-block-embed ld-video-figure"><div class="ld-video" data-wp-interactive="learn-drive/video" data-wp-context=\'%1$s\'><button type="button" class="ld-video__button" data-wp-on--click="actions.play"><span>%2$s</span></button><noscript><a href="%3$s">%4$s</a></noscript></div>%5$s</figure>',
		esc_attr( $context ),
		/* translators: %s: video title */
		esc_html( sprintf( __( 'Lire la vidéo : %s', 'learn-drive-core' ), $title ) ),
		esc_url( $url ),
		esc_html( $title ),
		wp_kses_post( $caption )
	);
}
add_filter( 'render_block_core/embed', __NAMESPACE__ . '\\video_facade', 10, 2 );

/**
 * Réécrit les URL des médias vers un CDN d'images (Cloudflare, Bunny…).
 *
 * @param string $url URL.
 * @return string
 */
function cdn_url( $url ) {
	$cdn = untrailingslashit( (string) setting( 'cdn_url' ) );
	if ( ! $cdn || is_admin() ) {
		return $url;
	}
	$uploads = wp_get_upload_dir();
	return str_replace( $uploads['baseurl'], $cdn, $url );
}
add_filter( 'wp_get_attachment_url', __NAMESPACE__ . '\\cdn_url' );

/**
 * Même réécriture pour les srcset.
 *
 * @param array $sources Sources.
 * @return array
 */
function cdn_srcset( $sources ) {
	foreach ( $sources as &$source ) {
		$source['url'] = cdn_url( $source['url'] );
	}
	return $sources;
}
add_filter( 'wp_calculate_image_srcset', __NAMESPACE__ . '\\cdn_srcset' );

/**
 * Supprime les scripts et styles d'émojis (requêtes inutiles).
 */
function disable_emojis() {
	if ( ! setting( 'disable_emojis' ) ) {
		return;
	}
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', __NAMESPACE__ . '\\disable_emojis' );

/**
 * Le texte alternatif est obligatoire pour les images de question et de fiche :
 * avertissement dans l'éditeur classique et l'écran de santé SEO.
 *
 * @param int $post_id Contenu.
 * @return bool
 */
function featured_alt_missing( $post_id ) {
	$thumb = get_post_thumbnail_id( $post_id );
	return $thumb && '' === trim( (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true ) );
}
