<?php
/**
 * Installe WordPress, active le thème et le plugin, règle les permaliens et
 * importe la démonstration (CLI).
 *
 * @package LearnDriveCore
 */

// phpcs:disable

$home                 = getenv( 'LD_HOME' ) ?: 'http://127.0.0.1:8080';
$_SERVER['HTTP_HOST'] = parse_url( $home, PHP_URL_HOST ) . ':' . ( parse_url( $home, PHP_URL_PORT ) ?: 80 );
define( 'WP_INSTALLING', true );
require rtrim( $argv[1], '/' ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Pas d'envoi d'e-mail pendant l'installation (pas de sendmail en CI).
add_filter( 'pre_wp_mail', '__return_false' );

if ( ! is_blog_installed() ) {
	wp_install( 'Learn & Drive', 'admin', 'admin@example.com', true, '', 'admin-password-123', 'fr_FR' );
}

wp_set_current_user( 1 );
switch_theme( 'learn-drive' );
$result = activate_plugin( 'learn-drive-core/learn-drive-core.php' );
if ( is_wp_error( $result ) ) {
	fwrite( STDERR, $result->get_error_message() . "\n" );
	exit( 1 );
}

update_option( 'blogdescription', 'Cours, quiz et examens blancs du permis de conduire' );
update_option( 'users_can_register', 1 );
update_option( 'permalink_structure', '/actualites/%postname%/' );
update_option( 'category_base', 'rubrique' );
update_option( 'tag_base', 'mot-cle' );

$count = LearnDrive\Core\Demo\import();
delete_option( 'ld_track_slugs' );
update_option( 'ld_flush_rewrite', 1 );
echo 'Démo : ' . wp_json_encode( $count ) . "\n";
