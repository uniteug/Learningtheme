<?php
/**
 * Plugin Name:       Learn & Drive Core
 * Plugin URI:        https://github.com/uniteug/Learningtheme
 * Description:       Plugin compagnon du thème Learn & Drive : cours, leçons, questions, quiz et examens blancs par pays, fiches, guides, lexique, accès gratuit / premium, API REST et SEO intégré (données structurées, sitemaps, maillage interne).
 * Version:           1.2.4
 * Requires at least: 7.0
 * Requires PHP:      8.1
 * Author:            uniteug
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       learn-drive-core
 * Domain Path:       /languages
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.2.4';

define( 'LEARN_DRIVE_CORE_VERSION', VERSION );
define( 'LEARN_DRIVE_CORE_FILE', __FILE__ );
define( 'LEARN_DRIVE_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'LEARN_DRIVE_CORE_URL', plugin_dir_url( __FILE__ ) );

$learn_drive_core_files = array(
	'includes/helpers.php',
	'includes/post-types.php',
	'includes/markets.php',
	'includes/permalinks.php',
	'includes/questions.php',
	'includes/quizzes.php',
	'includes/access.php',
	'includes/progress.php',
	'includes/classes.php',
	'includes/render.php',
	'includes/blocks.php',
	'includes/rest.php',
	'includes/media.php',
	'includes/pwa.php',
	'includes/integrations.php',
	'includes/theme-bridge.php',
	'includes/import.php',
	'includes/demo.php',
	'includes/seo/context.php',
	'includes/seo/breadcrumbs.php',
	'includes/seo/meta.php',
	'includes/seo/schema.php',
	'includes/seo/sitemaps.php',
	'includes/seo/indexnow.php',
	'includes/seo/ai.php',
	'includes/seo/internal-links.php',
	'includes/seo/og-image.php',
	'includes/seo/redirects.php',
	'includes/seo/index-policy.php',
	'includes/admin/settings.php',
	'includes/admin/meta-boxes.php',
	'includes/admin/seo-health.php',
	'includes/admin/access-admin.php',
	'includes/admin/classes-admin.php',
	'includes/admin/setup.php',
);

foreach ( $learn_drive_core_files as $learn_drive_core_file ) {
	require_once LEARN_DRIVE_CORE_DIR . $learn_drive_core_file;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once LEARN_DRIVE_CORE_DIR . 'includes/cli.php';
}

/**
 * Activation : tables, rôles, clé IndexNow et règles de réécriture.
 */
function activate() {
	Access\install_tables();
	Classes\install();
	SEO\IndexNow\ensure_key();
	Post_Types\register();
	Permalinks\add_rules();
	flush_rewrite_rules();
	if ( ! get_option( 'ld_setup_done' ) ) {
		update_option( 'ld_setup_redirect', 1, false );
	}
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate' );

/**
 * Désactivation : on nettoie seulement les règles de réécriture.
 */
function deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate' );

/**
 * Traductions.
 */
function load_textdomain() {
	load_plugin_textdomain( 'learn-drive-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', __NAMESPACE__ . '\\load_textdomain', 0 );
