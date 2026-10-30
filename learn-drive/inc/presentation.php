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
		// Page de toutes les catégories de permis (hub du marché), fournie par le plugin.
		'tracks'      => '',
		// Application mobile : affichée seulement si une adresse est renseignée.
		'app_ios'     => '',
		'app_android' => '',
		'about'       => home_url( '/qui-sommes-nous/' ),
		'editorial'   => home_url( '/politique-editoriale/' ),
		'legal'       => home_url( '/mentions-legales/' ),
		'privacy'     => get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/politique-de-confidentialite/' ),
		'contact'     => home_url( '/contact/' ),
		'sitemap'     => home_url( '/plan-du-site/' ),
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

/**
 * Liens du menu principal (en-tête). Un élément peut porter un sous-menu :
 * [libellé, adresse, [[libellé, adresse, description], …]].
 *
 * @return array<int,array> Libellé, adresse, sous-éléments éventuels.
 */
function learn_drive_nav_items() {
	$links = learn_drive_links();
	$base  = array(
		array( __( 'Cours', 'learn-drive' ), $links['learn'] ),
		array( __( 'Quiz', 'learn-drive' ), $links['quiz'] ),
		array( __( 'Panneaux', 'learn-drive' ), $links['signs'] ),
		array( __( 'Actualités', 'learn-drive' ), $links['news'] ),
		array( __( 'Infos & démarches', 'learn-drive' ), $links['guides'] ),
	);

	/**
	 * Liens du menu principal.
	 *
	 * @param array $items Liste de [libellé, adresse, sous-éléments éventuels].
	 */
	$items = array();
	foreach ( (array) apply_filters( 'learn_drive_nav_items', $base ) as $item ) {
		if ( ! empty( $item[2] ) ) {
			$item[2] = array_values( array_filter( (array) $item[2], static fn( $child ) => ! empty( $child[1] ) ) );
		}
		if ( ! empty( $item[1] ) || ! empty( $item[2] ) ) {
			$items[] = $item;
		}
	}
	return $items;
}

/**
 * Accès rapides du bandeau d'accueil.
 *
 * @return array<int,array{0:string,1:string}> Libellé, adresse.
 */
function learn_drive_hero_chips() {
	$links = learn_drive_links();
	$chips = array(
		array( __( 'Examen blanc', 'learn-drive' ), $links['exam'] ),
		array( __( 'Panneaux', 'learn-drive' ), $links['signs'] ),
		array( __( 'Démarches', 'learn-drive' ), $links['guides'] ),
		array( __( 'Lexique', 'learn-drive' ), $links['glossary'] ),
	);

	/**
	 * Accès rapides du bandeau d'accueil (les rubriques vides sont retirées par le plugin compagnon).
	 *
	 * @param array $chips Liste de [libellé, adresse].
	 */
	return array_values( array_filter( (array) apply_filters( 'learn_drive_hero_chips', $chips ), static fn( $item ) => ! empty( $item[1] ) ) );
}

/**
 * Colonnes de liens du pied de page.
 *
 * @return array<string,array<int,array{0:string,1:string}>> Titre => liens.
 */
function learn_drive_footer_columns() {
	$links   = learn_drive_links();
	$columns = array(
		__( 'Apprendre', 'learn-drive' )    => array(
			array( __( 'Tous les cours', 'learn-drive' ), $links['learn'] ),
			array( __( 'Quiz par thème', 'learn-drive' ), $links['quiz'] ),
			array( __( 'Panneaux de signalisation', 'learn-drive' ), $links['signs'] ),
			array( __( 'Lexique', 'learn-drive' ), $links['glossary'] ),
		),
		__( "S'informer", 'learn-drive' )   => array(
			array( __( 'Actualités', 'learn-drive' ), $links['news'] ),
			array( __( 'Infos & démarches', 'learn-drive' ), $links['guides'] ),
			array( __( 'Qui sommes-nous', 'learn-drive' ), $links['about'] ),
			array( __( 'Comment nous vérifions nos contenus', 'learn-drive' ), $links['editorial'] ),
		),
		__( 'Informations', 'learn-drive' ) => array(
			array( __( 'Mentions légales', 'learn-drive' ), $links['legal'] ),
			array( __( 'Confidentialité', 'learn-drive' ), $links['privacy'] ),
			array( __( 'Contact', 'learn-drive' ), $links['contact'] ),
		),
	);

	/**
	 * Colonnes de liens du pied de page.
	 *
	 * @param array $columns Titre => liste de [libellé, adresse].
	 */
	$columns = (array) apply_filters( 'learn_drive_footer_columns', $columns );
	foreach ( $columns as $title => $items ) {
		$columns[ $title ] = array_values( array_filter( (array) $items, static fn( $item ) => ! empty( $item[1] ) ) );
		if ( ! $columns[ $title ] ) {
			unset( $columns[ $title ] );
		}
	}
	return $columns;
}

/**
 * Arguments des boucles de l'accueil (type de contenu et filtre de taxonomie).
 *
 * @param string $key news|guides.
 * @return array{postType:string,taxQuery:array}
 */
function learn_drive_query_args( $key ) {
	$defaults = array(
		'news'   => array(
			'postType' => 'post',
			'taxQuery' => array(),
		),
		'guides' => array(
			'postType' => learn_drive_has_core_plugin() ? 'ld_guide' : 'page',
			'taxQuery' => array(),
		),
	);

	/**
	 * Arguments d'une boucle de l'accueil (ex. actualités limitées à une rubrique).
	 *
	 * @param array  $args { postType, taxQuery }.
	 * @param string $key  news|guides.
	 */
	return apply_filters( 'learn_drive_query_args', $defaults[ $key ] ?? $defaults['news'], $key );
}

/**
 * Attributs JSON d'une boucle Query pour un usage donné.
 *
 * @param string $key   news|guides.
 * @param array  $query Attributs de base de « query ».
 * @return string JSON pour le commentaire du bloc.
 */
function learn_drive_query_json( $key, array $query ) {
	$args              = learn_drive_query_args( $key );
	$query['postType'] = $args['postType'];
	if ( ! empty( $args['taxQuery'] ) ) {
		$query['taxQuery'] = $args['taxQuery'];
	}
	return wp_json_encode( $query );
}

/**
 * Affiche les attributs JSON d'une boucle Query (compositions de l'accueil).
 *
 * @param string $key   news|guides.
 * @param array  $query Attributs de base de « query ».
 */
function learn_drive_the_query_json( $key, array $query ) {
	echo learn_drive_query_json( $key, $query ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON d'attributs de bloc construit par wp_json_encode().
}
