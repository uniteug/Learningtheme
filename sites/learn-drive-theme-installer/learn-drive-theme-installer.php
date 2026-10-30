<?php
/**
 * Plugin Name:       Learn & Drive — installazione del tema
 * Description:       Installa e aggiorna il tema a blocchi Learn & Drive contenuto in questo pacchetto (learn-drive.zip). Utile quando il tema non può essere caricato da Aspetto › Temi. Non attiva il tema: lo si attiva da Aspetto › Temi.
 * Version:           1.3.1
 * Requires at least: 7.0
 * Requires PHP:      8.1
 * Author:            Learn & Drive
 * License:           GPL-2.0-or-later
 * Text Domain:       learn-drive-theme-installer
 *
 * @package LearnDriveThemeInstaller
 */

namespace LearnDrive\ThemeInstaller;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.3.1';
const THEME   = 'learn-drive';

/**
 * Installa il tema se assente o se il pacchetto contiene una versione diversa.
 *
 * @return true|\WP_Error
 */
function install() {
	$zip = __DIR__ . '/' . THEME . '.zip';
	if ( ! is_readable( $zip ) ) {
		return new \WP_Error( 'ldti_missing', 'learn-drive.zip mancante.' );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	if ( ! WP_Filesystem() ) {
		return new \WP_Error( 'ldti_fs', 'Accesso ai file non disponibile.' );
	}
	global $wp_filesystem;

	// Estrazione in una cartella temporanea, poi sostituzione della cartella del tema.
	$root = get_theme_root();
	$tmp  = trailingslashit( $root ) . '.' . THEME . '-' . wp_generate_password( 8, false );
	$done = unzip_file( $zip, $tmp );
	if ( is_wp_error( $done ) ) {
		$wp_filesystem->delete( $tmp, true );
		return $done;
	}
	if ( ! $wp_filesystem->exists( $tmp . '/' . THEME . '/style.css' ) ) {
		$wp_filesystem->delete( $tmp, true );
		return new \WP_Error( 'ldti_invalid', 'Pacchetto del tema non valido.' );
	}

	$target = trailingslashit( $root ) . THEME;
	if ( $wp_filesystem->exists( $target ) ) {
		$wp_filesystem->delete( $target, true );
	}
	$moved = $wp_filesystem->move( $tmp . '/' . THEME, $target, true );
	$wp_filesystem->delete( $tmp, true );
	if ( ! $moved ) {
		return new \WP_Error( 'ldti_move', 'Impossibile copiare il tema.' );
	}

	wp_clean_themes_cache();
	if ( get_stylesheet() === THEME ) {
		// Il tema attivo è cambiato : modelli, stili e pagine in cache rigenerati.
		wp_cache_flush();
		do_action( 'learn_drive_purge_cache' );
	}
	return true;
}

/**
 * All'attivazione e a ogni aggiornamento del pacchetto.
 */
function maybe_install() {
	if ( get_option( 'ldti_version' ) === VERSION && is_dir( get_theme_root() . '/' . THEME ) ) {
		return;
	}
	$result = install();
	if ( is_wp_error( $result ) ) {
		update_option( 'ldti_error', $result->get_error_message(), false );
		return;
	}
	delete_option( 'ldti_error' );
	update_option( 'ldti_version', VERSION, false );
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\maybe_install' );
add_action( 'admin_init', __NAMESPACE__ . '\\maybe_install' );
add_action( 'rest_api_init', __NAMESPACE__ . '\\maybe_install' );

/**
 * Avviso in caso di errore.
 */
function notice() {
	$error = get_option( 'ldti_error' );
	if ( $error && current_user_can( 'install_themes' ) ) {
		printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( 'Learn & Drive : ' . $error ) );
	}
}
add_action( 'admin_notices', __NAMESPACE__ . '\\notice' );
