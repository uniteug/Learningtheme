<?php
/**
 * Gestionnaire de redirections et journal des erreurs 404.
 *
 * Les changements de slug sont déjà redirigés en 301 par WordPress ; ce module
 * gère les redirections manuelles (migration, fusion de contenus) et garde la
 * trace des 404 pour les corriger depuis le tableau de bord SEO.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\SEO\Redirects;

use function LearnDrive\Core\setting;

defined( 'ABSPATH' ) || exit;

const OPTION_REDIRECTS = 'ld_redirects';
const OPTION_LOG       = 'ld_404_log';

/**
 * Chemin normalisé d'une URL.
 *
 * @param string $url URL ou chemin.
 * @return string
 */
function normalize( $url ) {
	$path = wp_parse_url( $url, PHP_URL_PATH );
	$path = '/' . trim( (string) $path, '/' ) . '/';
	return '//' === $path ? '/' : strtolower( $path );
}

/**
 * Redirections enregistrées { chemin source : cible }.
 *
 * @return array<string,string>
 */
function all() {
	$redirects = get_option( OPTION_REDIRECTS, array() );
	return is_array( $redirects ) ? $redirects : array();
}

/**
 * Ajoute ou remplace une redirection.
 *
 * @param string $from Chemin source.
 * @param string $to   URL cible.
 */
function add( $from, $to ) {
	$redirects                     = all();
	$redirects[ normalize( $from ) ] = esc_url_raw( $to );
	update_option( OPTION_REDIRECTS, $redirects, false );

	$log = get_option( OPTION_LOG, array() );
	if ( is_array( $log ) ) {
		unset( $log[ normalize( $from ) ] );
		update_option( OPTION_LOG, $log, false );
	}
}

/**
 * Supprime une redirection.
 *
 * @param string $from Chemin source.
 */
function remove( $from ) {
	$redirects = all();
	unset( $redirects[ normalize( $from ) ] );
	update_option( OPTION_REDIRECTS, $redirects, false );
}

/**
 * Applique les redirections, sinon journalise la 404.
 */
function handle() {
	if ( ! is_404() ) {
		return;
	}

	$request = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$path    = normalize( $request );
	$targets = all();

	if ( isset( $targets[ $path ] ) ) {
		wp_safe_redirect( $targets[ $path ], 301, 'Learn & Drive' );
		exit;
	}

	if ( ! setting( 'log_404' ) || preg_match( '#\.(php|env|git|sql|bak|zip|xml\.gz)|/wp-(admin|includes|content)/#i', $path ) ) {
		return;
	}

	$log = get_option( OPTION_LOG, array() );
	$log = is_array( $log ) ? $log : array();

	$referer       = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
	$entry         = $log[ $path ] ?? array( 'count' => 0 );
	$entry['count']++;
	$entry['last'] = time();
	if ( $referer ) {
		$entry['referer'] = $referer;
	}
	$log[ $path ] = $entry;

	if ( count( $log ) > 200 ) {
		uasort(
			$log,
			static function ( $a, $b ) {
				return $b['last'] <=> $a['last'];
			}
		);
		$log = array_slice( $log, 0, 200, true );
	}

	update_option( OPTION_LOG, $log, false );
}
add_action( 'template_redirect', __NAMESPACE__ . '\\handle', 99 );
