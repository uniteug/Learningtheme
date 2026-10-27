<?php
/**
 * Réglages de performance propres à l'affichage.
 *
 * @package LearnDrive
 */

defined( 'ABSPATH' ) || exit;

/**
 * Précharge les polices des titres et du texte pour éviter un saut de mise en
 * page tardif (texte long des leçons réaffiché à l'arrivée de la police).
 *
 * Les polices réellement utilisées sont lues dans les styles globaux, de sorte
 * que le préchargement suit la variation de style ou le choix fait dans l'éditeur.
 */
function learn_drive_preload_heading_font() {
	// Fichier par police : [texte, titres] (un seul fichier pour les polices variables).
	$files = array(
		'atkinson'          => array( 'assets/fonts/atkinson-hyperlegible/atkinson-hyperlegible-latin-400-normal.woff2', 'assets/fonts/atkinson-hyperlegible/atkinson-hyperlegible-latin-700-normal.woff2' ),
		'inter'             => array( 'assets/fonts/inter/inter-latin-wght-normal.woff2' ),
		'plus-jakarta-sans' => array( 'assets/fonts/plus-jakarta-sans/plus-jakarta-sans-latin-wght-normal.woff2' ),
		'source-serif'      => array( 'assets/fonts/source-serif-4/source-serif-4-latin-wght-normal.woff2' ),
		'lexend'            => array( 'assets/fonts/lexend/lexend-latin-wght-normal.woff2' ),
	);

	$slug_of = static function ( $family ) {
		return is_string( $family ) && preg_match( '/font-family--([a-z0-9-]+)/', $family, $matches ) ? $matches[1] : '';
	};
	$body    = $slug_of( wp_get_global_styles( array( 'typography', 'fontFamily' ) ) );
	$heading = $slug_of( wp_get_global_styles( array( 'elements', 'heading', 'typography', 'fontFamily' ) ) );
	$heading = $heading ? $heading : $body;

	$urls = array();
	if ( isset( $files[ $heading ] ) ) {
		$urls[] = end( $files[ $heading ] );
	}
	if ( isset( $files[ $body ] ) ) {
		$urls[] = $files[ $body ][0];
	}

	foreach ( array_unique( $urls ) as $file ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_theme_file_uri( $file ) )
		);
	}
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
