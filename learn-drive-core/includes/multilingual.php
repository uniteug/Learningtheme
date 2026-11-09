<?php
/**
 * Polylang : langues publiées et langues en préparation.
 *
 * Une langue ajoutée dans Polylang peut être « en préparation » (indicateur
 * « active » de Polylang, le même que celui de Polylang Pro) tant que ses
 * traductions ne sont pas prêtes :
 * - visiteurs : 404 sur ses pages (les éditeurs les voient, en noindex) ;
 * - ni hreflang, ni sélecteur de langue, ni sitemaps vers elle.
 * On la publie dans Learn & Drive › Réglages › SEO quand le contenu est là.
 *
 * Évite qu'une version /fr/ ou /en/ vide soit explorée, jugée mince et
 * présentée à Google comme traduction des pages italiennes.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Multilingual;

defined( 'ABSPATH' ) || exit;

/**
 * Polylang en service (avec au moins une langue) ?
 *
 * @return bool
 */
function polylang() {
	return function_exists( 'PLL' ) && function_exists( 'pll_languages_list' ) && PLL() && PLL()->model->has_languages();
}

/**
 * Langues de Polylang.
 *
 * @return array<string,array{slug:string,name:string,locale:string,active:bool,default:bool}>
 */
function languages() {
	if ( ! polylang() ) {
		return array();
	}
	$default = (string) pll_default_language();
	$list    = array();
	foreach ( PLL()->model->get_languages_list() as $language ) {
		$list[ $language->slug ] = array(
			'slug'    => $language->slug,
			'name'    => $language->name,
			'locale'  => $language->locale,
			'active'  => $language->slug === $default || false !== $language->active,
			'default' => $language->slug === $default,
		);
	}
	return $list;
}

/**
 * Codes des langues en préparation.
 *
 * @return string[]
 */
function inactive() {
	return array_keys( wp_list_filter( languages(), array( 'active' => false ) ) );
}

/**
 * Langue de la page affichée, si elle est en préparation.
 *
 * @return string Code de la langue, ou chaîne vide.
 */
function current_inactive() {
	if ( ! polylang() || ! function_exists( 'pll_current_language' ) ) {
		return '';
	}
	$current = (string) pll_current_language();
	return in_array( $current, inactive(), true ) ? $current : '';
}

/**
 * Codes de langue qui préfixent les adresses (/fr/, /en/) : Polylang en mode
 * « dossier », langue par défaut sans préfixe si elle est masquée.
 *
 * @return string[]
 */
function url_prefixes() {
	if ( ! polylang() || 1 !== (int) PLL()->options['force_lang'] ) {
		return array();
	}
	$default  = (string) pll_default_language();
	$prefixes = array();
	foreach ( PLL()->model->get_languages_list( array( 'fields' => 'slug' ) ) as $slug ) {
		if ( $slug !== $default || empty( PLL()->options['hide_default'] ) ) {
			$prefixes[] = (string) $slug;
		}
	}
	return $prefixes;
}

/**
 * Règle de réécriture, doublée d'une version préfixée par la langue
 * (/fr/permis-b/questions/…) : Polylang ne préfixe pas les règles ajoutées
 * par add_rewrite_rule().
 *
 * @param string $regex Expression (commençant par ^).
 * @param string $query Requête (index.php?…).
 * @param string $after Position.
 */
function add_rule( $regex, $query, $after = 'top' ) {
	add_rewrite_rule( $regex, $query, $after );

	$languages = url_prefixes();
	// Polylang préfixe déjà les archives de types traduits (post_type=… sans name=).
	if ( ! $languages || ( str_contains( $query, 'post_type=' ) && ! str_contains( $query, 'name=' ) ) ) {
		return;
	}
	$prefix  = ( empty( PLL()->options['rewrite'] ) ? 'language/' : '' ) . '(' . implode( '|', array_map( 'preg_quote', $languages ) ) . ')/';
	$shifted = preg_replace_callback(
		'/\$matches\[(\d+)\]/',
		static function ( $match ) {
			return '$matches[' . ( (int) $match[1] + 1 ) . ']';
		},
		$query
	);
	add_rewrite_rule( '^' . $prefix . ltrim( $regex, '^' ), str_replace( 'index.php?', 'index.php?lang=$matches[1]&', $shifted ), $after );
}

/**
 * Publie ou met en préparation une langue (indicateur « active » de Polylang).
 *
 * @param string $slug   Code de la langue.
 * @param bool   $active Publiée (true) ou en préparation (false).
 * @return bool Modifiée.
 */
function set_active( $slug, $active ) {
	if ( ! polylang() ) {
		return false;
	}
	$language = PLL()->model->get_language( $slug );
	if ( ! $language || pll_default_language() === $language->slug ) {
		return false;
	}
	$is_active = false !== $language->active;
	if ( $is_active === (bool) $active ) {
		return false;
	}

	$flag = static function ( $add ) use ( $active ) {
		$add['active'] = (bool) $active;
		return $add;
	};
	add_filter( 'pll_language_metas', $flag );
	$result = PLL()->model->languages->update(
		array(
			'lang_id'    => $language->term_id,
			'name'       => $language->name,
			'slug'       => $language->slug,
			'locale'     => $language->locale,
			'rtl'        => (bool) $language->is_rtl,
			'term_group' => (int) $language->term_group,
			'flag'       => $language->flag_code,
		)
	);
	remove_filter( 'pll_language_metas', $flag );
	PLL()->model->clean_languages_cache();
	flush_rewrite_rules( false );

	return ! is_wp_error( $result );
}

/**
 * Applique la liste des langues publiées (réglages).
 *
 * @param string[] $published Codes des langues publiées.
 */
function apply_published( array $published ) {
	foreach ( languages() as $slug => $language ) {
		if ( ! $language['default'] ) {
			set_active( $slug, in_array( $slug, $published, true ) );
		}
	}
}

/**
 * Pas de lien vers une langue en préparation : hreflang, sélecteur de
 * Polylang et le nôtre passent tous par ce filtre.
 *
 * @param string|null $url  Adresse de la traduction.
 * @param string      $lang Code de la langue.
 * @return string|null
 */
function translation_url( $url, $lang ) {
	return in_array( $lang, inactive(), true ) ? null : $url;
}
add_filter( 'pll_translation_url', __NAMESPACE__ . '\\translation_url', 20, 2 );

/**
 * Langue en préparation : 404 pour les visiteurs, page visible des éditeurs.
 */
function guard() {
	if ( is_admin() || current_user_can( 'edit_posts' ) || '' === current_inactive() ) {
		return;
	}
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', __NAMESPACE__ . '\\guard', 1 );

/**
 * Jamais indexée tant qu'elle est en préparation.
 *
 * @param bool $noindex Décision.
 * @return bool
 */
function noindex( $noindex ) {
	return $noindex || '' !== current_inactive();
}
add_filter( 'learn_drive_noindex', __NAMESPACE__ . '\\noindex' );

/**
 * Sitemaps de WordPress (Yoast : Polylang s'en charge) : Polylang crée un
 * sous-sitemap par langue (/fr/wp-sitemap-posts-page-1.xml…). Ceux d'une
 * langue en préparation sont vides, donc absents de l'index (et en 404).
 *
 * @param string $hook Filtre des arguments de requête du fournisseur.
 * @return bool
 */
function sitemap_language_inactive( $hook ) {
	$inactive = inactive();
	if ( ! $inactive ) {
		return false;
	}
	// Polylang ajoute la langue du sous-sitemap aux arguments de requête.
	$args = apply_filters( $hook, array(), '' );
	$lang = (string) ( $args['lang'] ?? '' );
	if ( '' === $lang && function_exists( 'pll_current_language' ) && get_query_var( 'sitemap' ) ) {
		$lang = (string) pll_current_language();
	}
	return '' !== $lang && in_array( $lang, $inactive, true );
}

/**
 * Nombre de pages d'un sous-sitemap de contenus.
 *
 * @param int|null $pages Valeur court-circuitée.
 * @return int|null
 */
function sitemap_posts_pages( $pages ) {
	return sitemap_language_inactive( 'wp_sitemaps_posts_query_args' ) ? 0 : $pages;
}
add_filter( 'wp_sitemaps_posts_pre_max_num_pages', __NAMESPACE__ . '\\sitemap_posts_pages', 30 );

/**
 * Nombre de pages d'un sous-sitemap de termes.
 *
 * @param int|null $pages Valeur court-circuitée.
 * @return int|null
 */
function sitemap_terms_pages( $pages ) {
	return sitemap_language_inactive( 'wp_sitemaps_taxonomies_query_args' ) ? 0 : $pages;
}
add_filter( 'wp_sitemaps_taxonomies_pre_max_num_pages', __NAMESPACE__ . '\\sitemap_terms_pages', 30 );

/**
 * Adresses d'un sous-sitemap demandé directement.
 *
 * @param array|null $list Valeur court-circuitée.
 * @return array|null
 */
function sitemap_posts_list( $list ) {
	return sitemap_language_inactive( 'wp_sitemaps_posts_query_args' ) ? array() : $list;
}
add_filter( 'wp_sitemaps_posts_pre_url_list', __NAMESPACE__ . '\\sitemap_posts_list', 30 );

/**
 * Adresses d'un sous-sitemap de termes demandé directement.
 *
 * @param array|null $list Valeur court-circuitée.
 * @return array|null
 */
function sitemap_terms_list( $list ) {
	return sitemap_language_inactive( 'wp_sitemaps_taxonomies_query_args' ) ? array() : $list;
}
add_filter( 'wp_sitemaps_taxonomies_pre_url_list', __NAMESPACE__ . '\\sitemap_terms_list', 30 );

/**
 * Sélecteur de langue de la page affichée : langues publiées qui ont une
 * traduction de cette page (la page elle-même comprise).
 *
 * @return array<int,array{code:string,name:string,url:string,current:bool}>
 */
function switcher() {
	if ( ! polylang() || ! function_exists( 'pll_the_languages' ) || is_admin() ) {
		return array();
	}
	$raw   = pll_the_languages(
		array(
			'raw'                    => 1,
			'hide_if_no_translation' => 1,
			'hide_if_empty'          => 1,
			'echo'                   => 0,
		)
	);
	$items = array();
	foreach ( (array) $raw as $language ) {
		if ( empty( $language['url'] ) || in_array( $language['slug'], inactive(), true ) || ! empty( $language['no_translation'] ) ) {
			continue;
		}
		$items[] = array(
			'code'    => $language['slug'],
			'name'    => $language['name'],
			'url'     => $language['url'],
			'current' => ! empty( $language['current_lang'] ),
		);
	}
	return count( $items ) > 1 ? $items : array();
}

/**
 * Le thème affiche le sélecteur de langue.
 *
 * @param array $items Langues.
 * @return array
 */
function theme_languages( $items ) {
	$switcher = switcher();
	return $switcher ? $switcher : $items;
}
add_filter( 'learn_drive_languages', __NAMESPACE__ . '\\theme_languages' );
