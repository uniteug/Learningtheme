<?php
/**
 * Présentation du site et composants dynamiques de l'interface.
 *
 * - Volet « commercial » (site payant) ou « editorial » (gratuit, freemium) :
 *   l'accueil et les appels à l'action s'adaptent. Le plugin compagnon fournit
 *   la valeur via le filtre learn_drive_presentation ; sans lui, le site est
 *   présenté comme un portail.
 * - Carrousels : le script n'est chargé que si la page en contient un.
 *
 * @package LearnDrive
 */

defined( 'ABSPATH' ) || exit;

/**
 * Volet de présentation.
 *
 * @return string commercial|editorial
 */
function learn_drive_presentation() {
	/**
	 * Volet de présentation du site.
	 *
	 * @param string $mode commercial|editorial.
	 */
	$mode = apply_filters( 'learn_drive_presentation', 'editorial' );
	return 'commercial' === $mode ? 'commercial' : 'editorial';
}

/**
 * Le site est-il présenté comme une offre payante ?
 *
 * @return bool
 */
function learn_drive_is_commercial() {
	return 'commercial' === learn_drive_presentation();
}

/**
 * Classe du volet sur <body> (styles et tests).
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function learn_drive_presentation_body_class( $classes ) {
	$classes[] = 'ld-mode-' . learn_drive_presentation();
	return $classes;
}
add_filter( 'body_class', 'learn_drive_presentation_body_class' );

/**
 * Enregistre le script des carrousels.
 */
function learn_drive_register_carousel() {
	wp_register_script(
		'learn-drive-carousel',
		get_template_directory_uri() . '/assets/js/carousel' . ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.js' : '.min.js' ),
		array(),
		LEARN_DRIVE_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	wp_add_inline_script(
		'learn-drive-carousel',
		'window.ldCarousel=' . wp_json_encode(
			array(
				'prev' => __( 'Précédent', 'learn-drive' ),
				'next' => __( 'Suivant', 'learn-drive' ),
			)
		) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'learn_drive_register_carousel' );

/**
 * Charge le script des carrousels dès qu'un bloc en affiche un.
 *
 * @param string $content Rendu du bloc.
 * @return string
 */
function learn_drive_maybe_enqueue_carousel( $content ) {
	if ( ! is_admin() && ( str_contains( $content, 'ld-carousel' ) || str_contains( $content, 'ld-tracks__list' ) || str_contains( $content, 'ld-rail' ) || str_contains( $content, 'is-style-carousel' ) ) && ! wp_script_is( 'learn-drive-carousel' ) ) {
		wp_enqueue_script( 'learn-drive-carousel' );
	}
	return $content;
}
add_filter( 'render_block', 'learn_drive_maybe_enqueue_carousel' );

/**
 * Adresses clés utilisées par les compositions (accueil, appels à l'action).
 *
 * Le plugin compagnon les précise (catégorie principale du pays choisi, examen
 * blanc, page de l'offre…) ; sans lui, les archives standard servent de repli.
 *
 * @return array<string,string>
 */
function learn_drive_links() {
	$links = array(
		'exam'        => home_url( '/quiz/' ),
		'learn'       => home_url( '/cours/' ),
		'quiz'        => home_url( '/quiz/' ),
		'signs'       => home_url( '/panneaux/' ),
		'news'        => home_url( '/actualites/' ),
		'guides'      => home_url( '/infos/' ),
		'glossary'    => home_url( '/lexique/' ),
		'account'     => home_url( '/mon-compte/' ),
		'register'    => wp_registration_url(),
		'premium'     => home_url( '/premium/' ),
		// Application mobile : affichée seulement si une adresse est renseignée.
		'app_ios'     => '',
		'app_android' => '',
	);

	/**
	 * Adresses clés de l'interface.
	 *
	 * @param array<string,string> $links Adresses.
	 */
	return apply_filters( 'learn_drive_links', $links );
}

/**
 * Textes du bandeau d'accueil : titre H1, chapeau et surtitre.
 *
 * @return array{eyebrow:string,title:string,lead:string}
 */
function learn_drive_home_text() {
	$tagline = wp_strip_all_tags( (string) get_bloginfo( 'description' ) );
	$text    = array(
		'eyebrow' => __( 'Cours · Quiz · Examens blancs', 'learn-drive' ),
		'title'   => $tagline ? $tagline : __( 'Apprenez, entraînez-vous, réussissez votre examen', 'learn-drive' ),
		'lead'    => __( 'Des cours clairs, des questions corrigées et expliquées, des examens blancs aux conditions réelles et toute l’actualité de votre formation, sur mobile comme sur ordinateur.', 'learn-drive' ),
	);

	/**
	 * Textes du bandeau d'accueil.
	 *
	 * @param array{eyebrow:string,title:string,lead:string} $text Textes.
	 */
	return apply_filters( 'learn_drive_home_text', $text );
}

/**
 * Supprime les sections devenues vides (ex. aucun quiz lié à ce thème) pour
 * ne pas laisser de bande colorée sans contenu.
 *
 * @param string $content Rendu du bloc.
 * @param array  $block   Bloc.
 * @return string
 */
function learn_drive_drop_empty_sections( $content, $block ) {
	if ( is_admin() || 'section' !== ( $block['attrs']['tagName'] ?? '' ) ) {
		return $content;
	}
	if ( '' === trim( wp_strip_all_tags( $content ) ) && ! preg_match( '/<(img|svg|iframe|video|input|form|hr)\b/i', $content ) ) {
		return '';
	}
	return $content;
}
add_filter( 'render_block_core/group', 'learn_drive_drop_empty_sections', 10, 2 );
