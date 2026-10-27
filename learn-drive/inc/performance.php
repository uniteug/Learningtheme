<?php
/**
 * Réglages de performance propres à l'affichage.
 *
 * @package LearnDrive
 */

defined( 'ABSPATH' ) || exit;

/**
 * Précharge la police des titres pour éviter un saut de mise en page tardif.
 *
 * La police réellement utilisée est lue dans les styles globaux, de sorte que
 * le préchargement suit la variation de style ou le choix fait dans l'éditeur.
 */
function learn_drive_preload_heading_font() {
	$files = array(
		'atkinson'          => 'assets/fonts/atkinson-hyperlegible/atkinson-hyperlegible-latin-700-normal.woff2',
		'inter'             => 'assets/fonts/inter/inter-latin-wght-normal.woff2',
		'plus-jakarta-sans' => 'assets/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2',
		'source-serif'      => 'assets/fonts/source-serif-4/source-serif-4-latin-wght-normal.woff2',
		'lexend'            => 'assets/fonts/lexend/lexend-latin-wght-normal.woff2',
	);

	$family = wp_get_global_styles( array( 'elements', 'heading', 'typography', 'fontFamily' ) );

	if ( ! is_string( $family ) || ! preg_match( '/font-family--([a-z0-9-]+)/', $family, $matches ) ) {
		$family = wp_get_global_styles( array( 'typography', 'fontFamily' ) );
		if ( ! is_string( $family ) || ! preg_match( '/font-family--([a-z0-9-]+)/', $family, $matches ) ) {
			return;
		}
	}

	$slug = $matches[1];
	if ( empty( $files[ $slug ] ) ) {
		return;
	}

	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( get_theme_file_uri( $files[ $slug ] ) )
	);
}
add_action( 'wp_head', 'learn_drive_preload_heading_font', 2 );

/**
 * Précharge les pages au survol (Speculation Rules) pour une navigation
 * quasi instantanée entre questions, leçons et articles.
 *
 * @param array|null $config Configuration proposée par WordPress.
 * @return array|null
 */
function learn_drive_speculation_rules( $config ) {
	if ( ! is_array( $config ) || is_user_logged_in() ) {
		return $config;
	}

	$config['mode']      = 'prefetch';
	$config['eagerness'] = 'moderate';

	return $config;
}
add_filter( 'wp_speculation_rules_configuration', 'learn_drive_speculation_rules' );

/**
 * Exclut du préchargement les URL d'examen et d'actions de compte.
 *
 * @param string[] $paths Chemins exclus.
 * @return string[]
 */
function learn_drive_speculation_exclusions( $paths ) {
	$paths[] = '/*\\?*ld_session=*';
	$paths[] = '/*\\?*ld_action=*';
	$paths[] = '/wp-login.php*';
	return $paths;
}
add_filter( 'wp_speculation_rules_href_exclude_paths', 'learn_drive_speculation_exclusions' );

/**
 * Les feuilles du thème et des blocs sont petites : on les insère en ligne
 * (CSS critique) plutôt que de bloquer le rendu avec des requêtes.
 *
 * @param int $limit Taille maximale insérée en ligne.
 * @return int
 */
function learn_drive_inline_styles_limit( $limit ) {
	return max( (int) $limit, 100000 );
}
add_filter( 'styles_inline_size_limit', 'learn_drive_inline_styles_limit' );
