<?php
/**
 * Mode hors ligne : manifeste d'application, service worker et page « hors
 * ligne » qui liste les leçons et questions déjà consultées.
 *
 * Les URL /sw.js, /manifest.webmanifest et /hors-ligne/ sont servies sans
 * règle de réécriture (rien à vider) et ne sont jamais indexées.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\PWA;

use function LearnDrive\Core\setting;
use function LearnDrive\Core\learning_types;

defined( 'ABSPATH' ) || exit;

/**
 * Chemin du site (racine du service worker), avec barre finale.
 *
 * @return string
 */
function scope() {
	return trailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
}

/**
 * URL des ressources servies par le module.
 *
 * @param string $name sw|manifest|offline.
 * @return string
 */
function url( $name ) {
	$paths = array(
		'sw'       => 'sw.js',
		'manifest' => 'manifest.webmanifest',
		'offline'  => 'hors-ligne/',
	);
	return home_url( '/' . $paths[ $name ] );
}

/**
 * Couleur d'une teinte de la palette du thème actif.
 *
 * @param string $slug     Teinte.
 * @param string $fallback Couleur de repli.
 * @return string
 */
function palette_color( $slug, $fallback ) {
	$palette = wp_get_global_settings( array( 'color', 'palette', 'theme' ) );
	foreach ( is_array( $palette ) ? $palette : array() as $color ) {
		if ( isset( $color['slug'], $color['color'] ) && $slug === $color['slug'] ) {
			return $color['color'];
		}
	}
	return $fallback;
}

/**
 * Sert sw.js, le manifeste et la page hors ligne avant le routage de WordPress.
 */
function serve() {
	if ( ! setting( 'offline' ) || ! isset( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}
	$path = (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
	$base = scope();

	if ( $base . 'sw.js' === $path ) {
		send_service_worker();
	} elseif ( $base . 'manifest.webmanifest' === $path ) {
		send_manifest();
	} elseif ( untrailingslashit( $base . 'hors-ligne' ) === untrailingslashit( $path ) ) {
		send_offline_page();
	}
}
add_action( 'parse_request', __NAMESPACE__ . '\\serve', 0 );

/**
 * Service worker (à la racine pour couvrir tout le site).
 */
function send_service_worker() {
	$source = (string) file_get_contents( LEARN_DRIVE_CORE_DIR . 'assets/js/sw.js' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichier local du plugin.
	$source = strtr(
		$source,
		array(
			'[[VERSION]]'     => esc_js( \LearnDrive\Core\VERSION ),
			'[[SCOPE]]'       => esc_js( scope() ),
			'[[OFFLINE_URL]]' => esc_js( url( 'offline' ) ),
		)
	);
	status_header( 200 );
	header( 'Content-Type: application/javascript; charset=utf-8' );
	header( 'Cache-Control: no-cache' );
	header( 'X-Robots-Tag: noindex' );
	echo $source; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- script statique, valeurs échappées ci-dessus.
	exit;
}

/**
 * Manifeste d'application web.
 */
function send_manifest() {
	$name  = wp_strip_all_tags( html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ) );
	$icons = array();
	if ( has_site_icon() ) {
		foreach ( array( 192, 512 ) as $size ) {
			$icons[] = array(
				'src'   => get_site_icon_url( $size ),
				'sizes' => $size . 'x' . $size,
				'type'  => 'image/png',
			);
		}
	} else {
		$icons[] = array(
			'src'     => LEARN_DRIVE_CORE_URL . 'assets/img/app-icon.svg',
			'sizes'   => 'any',
			'type'    => 'image/svg+xml',
			'purpose' => 'any maskable',
		);
	}

	$manifest = array(
		'id'               => scope(),
		'name'             => $name,
		'short_name'       => strlen( $name ) <= 15 ? $name : strtok( $name, ' ' ),
		'description'      => wp_strip_all_tags( html_entity_decode( get_bloginfo( 'description' ), ENT_QUOTES, 'UTF-8' ) ),
		'lang'             => str_replace( '_', '-', get_locale() ),
		'dir'              => is_rtl() ? 'rtl' : 'ltr',
		'start_url'        => scope(),
		'scope'            => scope(),
		'display'          => 'standalone',
		'theme_color'      => palette_color( 'primary', '#1f4fd8' ),
		'background_color' => palette_color( 'base', '#ffffff' ),
		'icons'            => $icons,
		'categories'       => array( 'education' ),
	);

	/**
	 * Manifeste d'application (nom, couleurs, icônes, raccourcis…).
	 *
	 * @param array $manifest Manifeste.
	 */
	$manifest = apply_filters( 'learn_drive_manifest', $manifest );

	status_header( 200 );
	header( 'Content-Type: application/manifest+json; charset=utf-8' );
	header( 'Cache-Control: public, max-age=86400' );
	header( 'X-Robots-Tag: noindex' );
	echo wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON.
	exit;
}

/**
 * Page « hors ligne » : autonome (aucune ressource externe), mise en cache à
 * l'installation du service worker.
 */
function send_offline_page() {
	$primary  = palette_color( 'primary', '#1f4fd8' );
	$base     = palette_color( 'base', '#ffffff' );
	$contrast = palette_color( 'contrast', '#0f172a' );
	$muted    = palette_color( 'muted', '#475569' );
	$border   = palette_color( 'border', '#d5dde8' );

	status_header( 200 );
	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?php echo esc_html( __( 'Hors ligne', 'learn-drive-core' ) . ' | ' . get_bloginfo( 'name' ) ); ?></title>
<style>
:root{color-scheme:light dark;--p:<?php echo esc_html( $primary ); ?>;--b:<?php echo esc_html( $base ); ?>;--c:<?php echo esc_html( $contrast ); ?>;--m:<?php echo esc_html( $muted ); ?>;--r:<?php echo esc_html( $border ); ?>}
*{box-sizing:border-box}
body{margin:0;background:var(--b);color:var(--c);font:1.0625rem/1.6 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
main{max-width:40rem;margin:0 auto;padding:3rem 1rem}
h1{font-size:1.75rem;line-height:1.2;margin:0 0 .75rem}
p{margin:0 0 1rem;color:var(--m)}
ul{list-style:none;margin:1.5rem 0;padding:0}
li{border-top:1px solid var(--r)}
li a{display:block;padding:.75rem 0;color:var(--c);font-weight:600;text-decoration:none}
li a:hover,li a:focus-visible{color:var(--p);text-decoration:underline}
button{min-height:44px;padding:.5rem 1.25rem;border:0;border-radius:6px;background:var(--p);color:#fff;font:inherit;font-weight:600;cursor:pointer}
@media (prefers-color-scheme:dark){:root{--b:#0b1220;--c:#e2e8f0;--m:#94a3b8;--r:#1e293b}}
</style>
</head>
<body>
<main>
	<h1><?php esc_html_e( 'Vous êtes hors ligne', 'learn-drive-core' ); ?></h1>
	<p><?php esc_html_e( 'Pas de connexion pour le moment. Vous pouvez continuer à réviser les pages déjà consultées sur cet appareil.', 'learn-drive-core' ); ?></p>
	<button type="button" onclick="location.reload()"><?php esc_html_e( 'Réessayer', 'learn-drive-core' ); ?></button>
	<h2 id="ld-saved-title" hidden><?php esc_html_e( 'Disponibles hors ligne', 'learn-drive-core' ); ?></h2>
	<ul id="ld-saved" aria-labelledby="ld-saved-title"></ul>
	<p id="ld-empty" hidden><?php esc_html_e( 'Aucune page enregistrée pour l’instant : les leçons, questions et fiches que vous ouvrez avec une connexion restent ensuite disponibles ici.', 'learn-drive-core' ); ?></p>
</main>
<script>
(function () {
	var list = document.getElementById('ld-saved');
	var site = <?php echo wp_json_encode( wp_strip_all_tags( html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ) ) ); ?>;
	function decode(s) {
		var t = document.createElement('textarea'); t.innerHTML = s;
		var title = t.value, cut = title.lastIndexOf(' | ' + site);
		return cut > 0 ? title.slice(0, cut) : title;
	}
	function render(pages) {
		pages.forEach(function (page) {
			var li = document.createElement('li'), a = document.createElement('a');
			a.href = page.url; a.textContent = decode(page.title);
			li.appendChild(a); list.appendChild(li);
		});
		document.getElementById(pages.length ? 'ld-saved-title' : 'ld-empty').hidden = false;
	}
	if (!('serviceWorker' in navigator) || !navigator.serviceWorker.controller) { render([]); return; }
	navigator.serviceWorker.addEventListener('message', function (e) {
		if (e.data && e.data.type === 'ld-offline-pages') { render(e.data.pages); }
	});
	navigator.serviceWorker.controller.postMessage('ld-offline-pages');
	window.addEventListener('online', function () { location.reload(); });
}());
</script>
</body>
</html>
	<?php
	exit;
}

/**
 * Manifeste, couleur du navigateur et enregistrement du service worker.
 */
function head() {
	if ( ! setting( 'offline' ) ) {
		return;
	}
	printf( '<link rel="manifest" href="%s">' . "\n", esc_url( url( 'manifest' ) ) );
	printf( '<meta name="theme-color" content="%s">' . "\n", esc_attr( palette_color( 'primary', '#1f4fd8' ) ) );
}
add_action( 'wp_head', __NAMESPACE__ . '\\head', 2 );

/**
 * Enregistre le service worker après le chargement (n'entre pas en concurrence
 * avec l'affichage de la page).
 */
function register_script() {
	if ( ! setting( 'offline' ) ) {
		return;
	}
	$script = sprintf(
		"if('serviceWorker'in navigator){addEventListener('load',function(){navigator.serviceWorker.register(%s,{scope:%s})})}",
		wp_json_encode( url( 'sw' ) ),
		wp_json_encode( scope() )
	);
	wp_print_inline_script_tag( $script );
}
add_action( 'wp_footer', __NAMESPACE__ . '\\register_script', 100 );

/**
 * Indique au service worker les pages qu'il peut garder pour la révision hors
 * ligne : contenus publics d'apprentissage et actualités, jamais les pages
 * personnelles, la recherche, les 404 ni les sessions d'examen.
 */
function offline_header() {
	if ( ! setting( 'offline' ) || is_admin() || headers_sent() ) {
		return;
	}
	$cacheable = ( is_singular( array_merge( learning_types(), array( 'post' ) ) ) || is_post_type_archive( learning_types() ) || is_front_page() || is_home() || is_category() || is_tax() )
		&& ! is_search() && ! is_404() && ! is_preview() && ! post_password_required()
		&& ! defined( 'DONOTCACHEPAGE' )
		&& ! isset( $_GET['ld_session'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture seule.

	/**
	 * La page courante peut-elle être gardée pour la révision hors ligne ?
	 *
	 * @param bool $cacheable Décision.
	 */
	if ( apply_filters( 'learn_drive_offline_cacheable', $cacheable ) ) {
		header( 'X-LD-Offline: 1' );
	}
}
add_action( 'template_redirect', __NAMESPACE__ . '\\offline_header', 99 );

/**
 * À la déconnexion, efface les pages gardées sur l'appareil (appareil partagé).
 */
function clear_on_logout() {
	if ( setting( 'offline' ) && ! headers_sent() ) {
		header( 'Clear-Site-Data: "cache", "storage"' );
	}
}
add_action( 'wp_logout', __NAMESPACE__ . '\\clear_on_logout' );
