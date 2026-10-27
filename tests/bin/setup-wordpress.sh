#!/usr/bin/env bash
# Installe un WordPress de test (SQLite, sans MySQL) avec le thème, le plugin
# et le contenu de démonstration.
#
# Usage : tests/bin/setup-wordpress.sh <dossier> [version-wordpress]
# Exemple : tests/bin/setup-wordpress.sh /tmp/wp 7.1.3
set -euo pipefail

DIR="${1:?dossier cible}"
VERSION="${2:-7.1.3}"
REPO="$(cd "$(dirname "$0")/../.." && pwd)"
HOME_URL="${LD_HOME:-http://127.0.0.1:8080}"

rm -rf "$DIR" && mkdir -p "$DIR"
git clone -q --depth 1 --branch "$VERSION" https://github.com/johnpbloch/wordpress-core.git "$DIR"
rm -rf "$DIR/.git"

SQLITE_TMP="$(mktemp -d)"
git clone -q --depth 1 https://github.com/WordPress/sqlite-database-integration.git "$SQLITE_TMP"
cp -rL "$SQLITE_TMP/packages/plugin-sqlite-database-integration" "$DIR/wp-content/plugins/sqlite-database-integration"
rm -rf "$SQLITE_TMP"
sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$DIR/wp-content/plugins/sqlite-database-integration#" \
	-e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" \
	"$DIR/wp-content/plugins/sqlite-database-integration/db.copy" > "$DIR/wp-content/db.php"

ln -sfn "$REPO/learn-drive" "$DIR/wp-content/themes/learn-drive"
ln -sfn "$REPO/learn-drive-core" "$DIR/wp-content/plugins/learn-drive-core"

cat > "$DIR/wp-config.php" <<PHP
<?php
define( 'DB_NAME', 'wp' );
define( 'DB_USER', '' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', '' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
define( 'DB_DIR', __DIR__ . '/wp-content/database/' );
define( 'DB_FILE', 'wp.sqlite' );
foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ) as \$k ) {
	define( \$k, 'test-' . \$k );
}
\$table_prefix = 'wp_';
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', __DIR__ . '/debug.log' );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_ENVIRONMENT_TYPE', 'local' );
define( 'WPLANG', 'fr_FR' );
define( 'WP_HOME', '$HOME_URL' );
define( 'WP_SITEURL', '$HOME_URL' );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
PHP

cat > "$DIR/router.php" <<'PHP'
<?php
// Routeur du serveur PHP intégré : fichiers statiques, sinon WordPress (compressé).
$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$file = __DIR__ . $path;
if ( '/' !== $path && is_file( $file ) ) {
	if ( str_ends_with( $file, '.php' ) ) {
		require $file;
		return true;
	}
	return false;
}
if ( '/' !== $path && is_file( rtrim( $file, '/' ) . '/index.php' ) ) {
	chdir( rtrim( $file, '/' ) );
	require rtrim( $file, '/' ) . '/index.php';
	return true;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
ob_start( 'ob_gzhandler' );
require __DIR__ . '/index.php';
PHP

php "$REPO/tests/bin/install.php" "$DIR"
echo "WordPress prêt dans $DIR — serveur : php -S 127.0.0.1:8080 -t $DIR $DIR/router.php"
