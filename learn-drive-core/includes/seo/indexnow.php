<?php
/**
 * IndexNow (Bing, Yandex, Seznam…) : notification à chaque publication ou mise à jour.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\SEO\IndexNow;

use function LearnDrive\Core\setting;
use function LearnDrive\Core\content_types;

defined( 'ABSPATH' ) || exit;

/**
 * Crée la clé IndexNow si nécessaire.
 *
 * @return string
 */
function ensure_key() {
	$key = (string) get_option( 'ld_indexnow_key' );
	if ( ! preg_match( '/^[a-f0-9]{32}$/', $key ) ) {
		$key = md5( wp_generate_password( 32, false ) );
		update_option( 'ld_indexnow_key', $key, true );
	}
	return $key;
}

/**
 * Sert le fichier de vérification /{clé}.txt.
 */
function serve_key() {
	$key  = (string) get_option( 'ld_indexnow_key' );
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	if ( $key && '/' . $key . '.txt' === $path ) {
		header( 'Content-Type: text/plain; charset=UTF-8' );
		echo esc_html( $key );
		exit;
	}
}
add_action( 'parse_request', __NAMESPACE__ . '\\serve_key', 0 );

/**
 * Notifie IndexNow (requête non bloquante).
 *
 * @param string   $new_status Nouveau statut.
 * @param string   $old_status Ancien statut.
 * @param \WP_Post $post       Contenu.
 */
function ping( $new_status, $old_status, $post ) {
	if ( 'publish' !== $new_status || ! setting( 'indexnow' ) || ! get_option( 'blog_public' ) ) {
		return;
	}
	if ( ! in_array( $post->post_type, array_merge( content_types(), array( 'page' ) ), true ) || wp_is_post_revision( $post ) ) {
		return;
	}
	if ( in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) || get_post_meta( $post->ID, '_ld_noindex', true ) ) {
		return;
	}

	$url = get_permalink( $post );
	wp_remote_get(
		add_query_arg(
			array(
				'url' => rawurlencode( $url ),
				'key' => ensure_key(),
			),
			'https://api.indexnow.org/indexnow'
		),
		array(
			'blocking' => false,
			'timeout'  => 2,
		)
	);
}
add_action( 'transition_post_status', __NAMESPACE__ . '\\ping', 10, 3 );
