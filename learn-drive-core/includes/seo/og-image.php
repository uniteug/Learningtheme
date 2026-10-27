<?php
/**
 * Images Open Graph générées automatiquement (titre + catégorie + couleur du
 * site) pour les quiz, questions et leçons sans image : 1200 × 630, PNG, mises
 * en cache dans le dossier des médias.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\SEO\OG;

use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\TAX_TOPIC;
use function LearnDrive\Core\primary_term;
use function LearnDrive\Core\setting;

defined( 'ABSPATH' ) || exit;

/**
 * URL publique de l'image d'un contenu.
 *
 * @param int $post_id Contenu.
 * @return string
 */
function url( $post_id ) {
	$source  = $post_id ? get_the_title( $post_id ) . get_post_modified_time( 'U', true, $post_id ) : get_bloginfo( 'name' ) . get_bloginfo( 'description' );
	$version = substr( md5( $source . color() ), 0, 8 );
	return home_url( '/ld-og/' . (int) $post_id . '-' . $version . '.png' );
}

/**
 * Route /ld-og/{id}-{version}.png.
 */
function rewrite() {
	add_rewrite_rule( '^ld-og/([0-9]+)-([a-f0-9]{8})\.png$', 'index.php?ld_og=$matches[1]', 'top' );
}
add_action( 'init', __NAMESPACE__ . '\\rewrite' );

/**
 * Variable de requête.
 *
 * @param string[] $vars Variables.
 * @return string[]
 */
function query_vars( $vars ) {
	$vars[] = 'ld_og';
	return $vars;
}
add_filter( 'query_vars', __NAMESPACE__ . '\\query_vars' );

/**
 * Couleur primaire du site (styles globaux), avec repli.
 *
 * @return string
 */
function color() {
	$palette = function_exists( 'wp_get_global_settings' ) ? wp_get_global_settings( array( 'color', 'palette', 'theme' ) ) : array();
	foreach ( (array) $palette as $entry ) {
		if ( isset( $entry['slug'], $entry['color'] ) && 'primary' === $entry['slug'] && preg_match( '/^#[0-9a-f]{6}$/i', $entry['color'] ) ) {
			return $entry['color'];
		}
	}
	return '#1F4FD8';
}

/**
 * Découpe un texte en lignes pour une largeur donnée.
 *
 * @param string $text  Texte.
 * @param float  $size  Taille de police.
 * @param string $font  Fichier TTF.
 * @param int    $width Largeur maximale.
 * @return string[]
 */
function wrap( $text, $size, $font, $width ) {
	$words = preg_split( '/\s+/u', $text );
	$lines = array();
	$line  = '';
	foreach ( $words as $word ) {
		$test = '' === $line ? $word : $line . ' ' . $word;
		$box  = imagettfbbox( $size, 0, $font, $test );
		if ( $box[2] - $box[0] > $width && '' !== $line ) {
			$lines[] = $line;
			$line    = $word;
		} else {
			$line = $test;
		}
	}
	if ( '' !== $line ) {
		$lines[] = $line;
	}
	return $lines;
}

/**
 * Génère l'image PNG.
 *
 * @param int $post_id Contenu.
 * @return string|null Chemin du fichier.
 */
function generate( $post_id ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) || ! function_exists( 'imagettftext' ) ) {
		return null;
	}

	$uploads = wp_upload_dir();
	$dir     = trailingslashit( $uploads['basedir'] ) . 'learn-drive-og';
	$file    = $dir . '/' . basename( wp_parse_url( url( $post_id ), PHP_URL_PATH ) );

	if ( file_exists( $file ) ) {
		return $file;
	}

	wp_mkdir_p( $dir );

	$bold    = LEARN_DRIVE_CORE_DIR . 'assets/fonts/atkinson-hyperlegible-700.ttf';
	$regular = LEARN_DRIVE_CORE_DIR . 'assets/fonts/atkinson-hyperlegible-400.ttf';
	$image   = imagecreatetruecolor( 1200, 630 );

	$hex   = ltrim( color(), '#' );
	$track = $post_id ? primary_term( $post_id, TAX_TRACK ) : null;
	$term  = $track ? $track : ( $post_id ? primary_term( $post_id, TAX_TOPIC ) : null );
	$term_color = $track ? sanitize_hex_color( get_term_meta( $track->term_id, 'ld_color', true ) ) : '';
	if ( $term_color ) {
		$hex = ltrim( $term_color, '#' );
	}

	$bg     = imagecolorallocate( $image, hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
	$dark   = imagecolorallocatealpha( $image, 0, 0, 0, 80 );
	$white  = imagecolorallocate( $image, 255, 255, 255 );
	$soft   = imagecolorallocatealpha( $image, 255, 255, 255, 112 );
	$accent = imagecolorallocate( $image, 255, 197, 49 );

	imagefilledrectangle( $image, 0, 0, 1200, 630, $bg );
	imagefilledrectangle( $image, 0, 0, 1200, 630, $dark );
	imagefilledellipse( $image, 1120, 600, 520, 520, $soft );
	imagefilledrectangle( $image, 80, 120, 200, 130, $accent );

	if ( $term ) {
		imagettftext( $image, 28, 0, 80, 100, $white, $bold, mb_strtoupper( $term->name ) );
	}

	$title = $post_id ? get_the_title( $post_id ) : ( get_bloginfo( 'description' ) ? get_bloginfo( 'description' ) : get_bloginfo( 'name' ) );
	$title = html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES, 'UTF-8' );
	$size  = mb_strlen( $title ) > 90 ? 40 : ( mb_strlen( $title ) > 50 ? 50 : 60 );
	$lines = array_slice( wrap( $title, $size, $bold, 1040 ), 0, 5 );
	$y     = 210;
	foreach ( $lines as $line ) {
		imagettftext( $image, $size, 0, 80, $y, $white, $bold, $line );
		$y += (int) ( $size * 1.4 );
	}

	$site = (string) setting( 'org_name' );
	$site = '' !== $site ? $site : get_bloginfo( 'name' );
	imagettftext( $image, 26, 0, 80, 570, $white, $regular, html_entity_decode( $site, ENT_QUOTES, 'UTF-8' ) );

	imagepng( $image, $file, 9 );
	imagedestroy( $image );

	return file_exists( $file ) ? $file : null;
}

/**
 * Sert l'image demandée.
 */
function serve() {
	$raw = get_query_var( 'ld_og' );
	if ( '' === $raw || null === $raw ) {
		return;
	}
	$post_id = (int) $raw;
	if ( $post_id && 'publish' !== get_post_status( $post_id ) ) {
		status_header( 404 );
		exit;
	}

	$file = generate( $post_id );
	if ( ! $file ) {
		status_header( 404 );
		exit;
	}

	status_header( 200 );
	header( 'Content-Type: image/png' );
	header( 'Cache-Control: public, max-age=31536000, immutable' );
	header( 'Content-Length: ' . filesize( $file ) );
	readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
	exit;
}
add_action( 'template_redirect', __NAMESPACE__ . '\\serve', 0 );
