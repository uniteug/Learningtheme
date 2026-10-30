<?php
/**
 * Marchés : un pays (taxonomie « Pays ») est un marché, avec sa langue, son
 * état (en ligne ou en préparation), sa page d'accueil (« hub ») et ses
 * segments d'URL.
 *
 * Ouvrir un nouveau marché ne demande que du contenu : créer le pays, régler
 * sa langue, ses segments et son hub, y ranger catégories de permis, quiz,
 * actualités et guides, puis le passer « en ligne ». Langue de l'interface,
 * attribut lang, hreflang, sitemaps et menus suivent d'eux-mêmes. Tant qu'il
 * est « en préparation », le marché n'est visible que des éditeurs (404 pour
 * les visiteurs, noindex, hors sitemaps).
 *
 * Réglages d'un pays (méta de terme) :
 *   ld_locale      langue de l'interface (it_IT, fr_FR, es_ES…), vide = langue du site
 *   ld_active      '0' = en préparation (absent = en ligne, compatibilité)
 *   ld_hub         chemin de la page d'accueil du marché (« patenti », « fr »…)
 *   ld_slugs       segments d'URL propres au marché (slug_quiz, slug_question…)
 * Réglage d'une catégorie de permis :
 *   ld_equivalent  clé commune aux catégories équivalentes d'un pays à l'autre
 *                  (« b » pour patente B, permis B, permiso B) : hreflang.
 * Réglage d'un contenu :
 *   _ld_translation_group  clé commune aux traductions d'un même article.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Markets;

use function LearnDrive\Core\setting;
use function LearnDrive\Core\primary_term;
use const LearnDrive\Core\TAX_COUNTRY;
use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\TAX_TOPIC;

defined( 'ABSPATH' ) || exit;

/**
 * Segments d'URL qu'un marché peut traduire.
 */
const SEGMENTS = array( 'slug_lesson', 'slug_course', 'slug_quiz', 'slug_question', 'slug_exam' );

/**
 * Marché par défaut (pays principal du site).
 *
 * @return string Slug du pays, ou chaîne vide.
 */
function default_market() {
	return (string) setting( 'default_country' );
}

/**
 * Tous les marchés (pays), avec leurs réglages.
 *
 * @param bool $refresh Relire les réglages (après une modification).
 * @return array<string,array{slug:string,name:string,term_id:int,locale:string,active:bool,hub:string,slugs:array}>
 */
function all( $refresh = false ) {
	static $cache = null;
	if ( null !== $cache && ! $refresh ) {
		return $cache;
	}
	$cache = array();
	if ( ! taxonomy_exists( TAX_COUNTRY ) ) {
		return $cache;
	}
	$terms = get_terms(
		array(
			'taxonomy'   => TAX_COUNTRY,
			'hide_empty' => false,
		)
	);
	foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
		$slugs                 = get_term_meta( $term->term_id, 'ld_slugs', true );
		$cache[ $term->slug ] = array(
			'slug'    => $term->slug,
			'name'    => $term->name,
			'term_id' => (int) $term->term_id,
			'locale'  => (string) get_term_meta( $term->term_id, 'ld_locale', true ),
			'active'  => default_market() === $term->slug || '0' !== (string) get_term_meta( $term->term_id, 'ld_active', true ),
			'hub'     => trim( (string) get_term_meta( $term->term_id, 'ld_hub', true ), '/' ),
			'slugs'   => is_array( $slugs ) ? array_filter( array_map( 'sanitize_title', $slugs ) ) : array(),
		);
	}
	return $cache;
}

/**
 * Relit les réglages quand un pays change.
 */
function refresh() {
	all( true );
}
add_action( 'edited_' . TAX_COUNTRY, __NAMESPACE__ . '\\refresh', 20 );
add_action( 'created_' . TAX_COUNTRY, __NAMESPACE__ . '\\refresh', 20 );
add_action( 'delete_' . TAX_COUNTRY, __NAMESPACE__ . '\\refresh', 20 );

/**
 * Réglages d'un marché.
 *
 * @param string $slug Pays.
 * @return array|null
 */
function get( $slug ) {
	$all = all();
	return $all[ $slug ] ?? null;
}

/**
 * Le marché est-il en ligne ? (Un pays inconnu ou vide l'est : contenu commun.)
 *
 * @param string $slug Pays.
 * @return bool
 */
function is_active( $slug ) {
	$market = get( (string) $slug );
	return ! $market || $market['active'];
}

/**
 * Marchés en ligne.
 *
 * @return array[]
 */
function active() {
	return array_filter( all(), static fn( $market ) => $market['active'] );
}

/**
 * Pays en préparation (slugs).
 *
 * @return string[]
 */
function inactive_slugs() {
	return array_keys( array_filter( all(), static fn( $market ) => ! $market['active'] ) );
}

/**
 * Segment d'URL d'un marché (ex. « quiz », « domande », « questions »).
 *
 * @param string $key     Clé du réglage (slug_quiz…).
 * @param string $country Pays.
 * @return string
 */
function segment( $key, $country = '' ) {
	$market = $country ? get( $country ) : null;
	if ( $market && ! empty( $market['slugs'][ $key ] ) ) {
		return $market['slugs'][ $key ];
	}
	return (string) setting( $key );
}

/**
 * Pays d'une catégorie de permis, d'un thème, d'une rubrique ou d'un pays.
 *
 * @param \WP_Term $term Terme.
 * @return string
 */
function of_term( $term ) {
	if ( ! $term instanceof \WP_Term ) {
		return '';
	}
	if ( TAX_COUNTRY === $term->taxonomy ) {
		return $term->slug;
	}
	$country = (string) get_term_meta( $term->term_id, 'ld_country', true );
	if ( '' === $country && $term->parent ) {
		return of_term( get_term( $term->parent, $term->taxonomy ) );
	}
	return $country;
}

/**
 * Pays d'un contenu : son pays, sinon celui de sa catégorie de permis, sinon
 * celui de sa rubrique (actualités), sinon aucun (contenu commun).
 *
 * @param \WP_Post|int $post Contenu.
 * @return string
 */
function of_post( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$country = primary_term( $post->ID, TAX_COUNTRY );
	if ( $country ) {
		return $country->slug;
	}
	$track = primary_term( $post->ID, TAX_TRACK );
	if ( $track ) {
		return of_term( $track );
	}
	if ( 'post' === $post->post_type ) {
		foreach ( (array) get_the_category( $post->ID ) as $category ) {
			$found = of_term( $category );
			if ( $found ) {
				return $found;
			}
		}
	}
	return '';
}

/**
 * Marché de la page demandée.
 *
 * @return string Pays, ou chaîne vide si la page est commune à tous les marchés.
 */
function current() {
	if ( is_front_page() || is_home() ) {
		return default_market();
	}
	if ( is_singular() ) {
		return of_post( get_queried_object() );
	}
	if ( is_category() || is_tax() ) {
		return of_term( get_queried_object() );
	}
	$track = get_query_var( TAX_TRACK );
	if ( $track ) {
		return of_term( get_term_by( 'slug', (string) $track, TAX_TRACK ) );
	}
	return '';
}

/**
 * Langue d'un marché (vide = langue du site).
 *
 * @param string $slug Pays.
 * @return string
 */
function locale( $slug ) {
	$market = get( (string) $slug );
	return $market ? $market['locale'] : '';
}

/**
 * Langues des marchés acceptées par le sélecteur de langue de WordPress sur
 * le site public, même si le paquet de langue de WordPress n'est pas installé
 * (les textes du thème et du plugin sont alors traduits, ceux du cœur non).
 *
 * @param string[] $languages Langues installées.
 * @return string[]
 */
function available_languages( $languages ) {
	if ( is_admin() || ! taxonomy_exists( TAX_COUNTRY ) ) {
		return $languages;
	}
	$locales = array_filter( wp_list_pluck( all(), 'locale' ) );
	return array_values( array_unique( array_merge( (array) $languages, $locales ) ) );
}
add_filter( 'get_available_languages', __NAMESPACE__ . '\\available_languages' );

/**
 * Interface dans la langue du marché de la page (textes du thème et du
 * plugin, dates, attribut lang, og:locale).
 */
function switch_locale() {
	if ( is_admin() ) {
		return;
	}
	$locale = locale( current() );
	if ( ! $locale || determine_locale() === $locale ) {
		return;
	}
	global $wp_locale_switcher;
	if ( $wp_locale_switcher instanceof \WP_Locale_Switcher && ! switch_to_locale( $locale ) ) {
		// Sélecteur créé avant le chargement des pays : on le recrée avec leurs langues.
		$wp_locale_switcher = new \WP_Locale_Switcher(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- sélecteur de WordPress, liste de langues à jour.
		$wp_locale_switcher->init();
		switch_to_locale( $locale );
	}
}
add_action( 'wp', __NAMESPACE__ . '\\switch_locale', 1 );

/**
 * Marché en préparation : 404 pour les visiteurs, page visible des éditeurs.
 */
function guard_inactive() {
	if ( is_admin() || current_user_can( 'edit_posts' ) ) {
		return;
	}
	$market = current();
	if ( '' === $market || is_active( $market ) ) {
		return;
	}
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', __NAMESPACE__ . '\\guard_inactive', 1 );

/**
 * Jamais indexé tant que le marché est en préparation.
 *
 * @param bool $noindex Décision.
 * @return bool
 */
function noindex_inactive( $noindex ) {
	$market = current();
	return $noindex || ( '' !== $market && ! is_active( $market ) );
}
add_filter( 'learn_drive_noindex', __NAMESPACE__ . '\\noindex_inactive' );

/**
 * Sitemaps de WordPress : contenus des marchés en préparation retirés.
 *
 * @param array $args Arguments de la requête.
 * @return array
 */
function sitemap_posts( $args ) {
	$inactive = inactive_slugs();
	if ( $inactive ) {
		$args['tax_query']   = (array) ( $args['tax_query'] ?? array() ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		$args['tax_query'][] = array(
			'taxonomy' => TAX_COUNTRY,
			'field'    => 'slug',
			'terms'    => $inactive,
			'operator' => 'NOT IN',
		);
	}
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', __NAMESPACE__ . '\\sitemap_posts' );

/**
 * Termes rattachés à un marché en préparation (pays, catégories, thèmes).
 *
 * @return int[]
 */
function inactive_term_ids() {
	$inactive = inactive_slugs();
	if ( ! $inactive ) {
		return array();
	}
	$ids = array();
	foreach ( $inactive as $slug ) {
		$ids[] = get( $slug )['term_id'];
	}
	$terms = get_terms(
		array(
			'taxonomy'   => array( TAX_TRACK, TAX_TOPIC, 'category' ),
			'hide_empty' => false,
			'fields'     => 'ids',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'ld_country',
					'value'   => $inactive,
					'compare' => 'IN',
				),
			),
		)
	);
	return array_map( 'intval', array_merge( $ids, is_wp_error( $terms ) ? array() : $terms ) );
}

/**
 * Sitemaps de WordPress : termes des marchés en préparation retirés.
 *
 * @param array $args Arguments.
 * @return array
 */
function sitemap_terms( $args ) {
	$ids = inactive_term_ids();
	if ( $ids ) {
		$args['exclude'] = array_merge( (array) ( $args['exclude'] ?? array() ), $ids );
	}
	return $args;
}
add_filter( 'wp_sitemaps_taxonomies_query_args', __NAMESPACE__ . '\\sitemap_terms', 20 );

/**
 * Yoast SEO : mêmes exclusions dans ses sitemaps.
 *
 * @param int[] $ids Contenus exclus.
 * @return int[]
 */
function yoast_posts( $ids ) {
	$inactive = inactive_slugs();
	if ( ! $inactive ) {
		return $ids;
	}
	$found = get_posts(
		array(
			'post_type'      => 'any',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => -1, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- identifiants seulement.
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => TAX_COUNTRY,
					'field'    => 'slug',
					'terms'    => $inactive,
				),
			),
		)
	);
	return array_values( array_unique( array_merge( (array) $ids, array_map( 'intval', $found ) ) ) );
}
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', __NAMESPACE__ . '\\yoast_posts' );

/**
 * Yoast SEO : termes des marchés en préparation.
 *
 * @param int[] $ids Termes exclus.
 * @return int[]
 */
function yoast_terms( $ids ) {
	return array_values( array_unique( array_merge( (array) $ids, inactive_term_ids() ) ) );
}
add_filter( 'wpseo_exclude_from_sitemap_by_term_ids', __NAMESPACE__ . '\\yoast_terms' );

/**
 * Page d'accueil d'un marché : l'accueil du site pour le marché par défaut
 * (sauf hub dédié), sinon son hub (/fr/) ou l'archive du pays.
 *
 * @param string $slug Pays.
 * @return string
 */
function home_link( $slug ) {
	if ( default_market() === $slug ) {
		return home_url( '/' );
	}
	$market = get( $slug );
	if ( ! $market ) {
		return '';
	}
	$link = get_term_link( $market['term_id'], TAX_COUNTRY );
	return is_wp_error( $link ) ? '' : $link;
}

/**
 * Hub d'un marché : /patenti/, /fr/… au lieu de /paese/italia/.
 */
function add_hub_rules() {
	foreach ( all() as $market ) {
		if ( '' !== $market['hub'] ) {
			$hub = preg_quote( $market['hub'], '#' );
			add_rewrite_rule( "^{$hub}/?$", 'index.php?' . TAX_COUNTRY . '=' . $market['slug'], 'top' );
		}
	}
}
add_action( 'init', __NAMESPACE__ . '\\add_hub_rules', 21 );

/**
 * Lien d'un pays : son hub s'il en a un.
 *
 * @param string   $link     Lien.
 * @param \WP_Term $term     Terme.
 * @param string   $taxonomy Taxonomie.
 * @return string
 */
function hub_link( $link, $term, $taxonomy ) {
	if ( TAX_COUNTRY === $taxonomy ) {
		$market = get( $term->slug );
		if ( $market && '' !== $market['hub'] ) {
			return home_url( user_trailingslashit( $market['hub'] ) );
		}
	}
	return $link;
}
add_filter( 'term_link', __NAMESPACE__ . '\\hub_link', 10, 3 );

/**
 * Code hreflang d'un marché (it-IT, fr-FR…), vide si sa langue n'est pas réglée.
 *
 * @param string $slug Pays.
 * @return string
 */
function hreflang( $slug ) {
	$locale = locale( $slug );
	return $locale ? str_replace( '_', '-', $locale ) : '';
}

/**
 * Versions de la page courante dans les autres marchés en ligne.
 *
 * @return array<string,string> hreflang => URL (avec x-default).
 */
function alternates() {
	$links = array();
	$add   = static function ( $country, $url ) use ( &$links ) {
		$code = hreflang( $country );
		if ( $code && $url && is_active( $country ) && ! isset( $links[ $code ] ) ) {
			$links[ $code ] = $url;
		}
	};

	if ( is_front_page() || is_tax( TAX_COUNTRY ) ) {
		foreach ( active() as $market ) {
			$add( $market['slug'], home_link( $market['slug'] ) );
		}
	} elseif ( is_tax( TAX_TRACK ) ) {
		$term = get_queried_object();
		$key  = (string) get_term_meta( $term->term_id, 'ld_equivalent', true );
		if ( '' !== $key ) {
			$terms = get_terms(
				array(
					'taxonomy'   => TAX_TRACK,
					'hide_empty' => false,
					'meta_key'   => 'ld_equivalent', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			);
			foreach ( is_wp_error( $terms ) ? array() : $terms as $equivalent ) {
				$add( of_term( $equivalent ), get_term_link( $equivalent ) );
			}
		}
	} elseif ( is_singular() ) {
		$group = (string) get_post_meta( get_queried_object_id(), '_ld_translation_group', true );
		if ( '' !== $group ) {
			$posts = get_posts(
				array(
					'post_type'      => get_post_type( get_queried_object_id() ),
					'post_status'    => 'publish',
					'posts_per_page' => 20,
					'meta_key'       => '_ld_translation_group', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => $group, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			);
			foreach ( $posts as $post ) {
				$add( of_post( $post ), get_permalink( $post ) );
			}
		}
	}

	if ( count( $links ) < 2 ) {
		return array();
	}
	$default = hreflang( default_market() );
	if ( $default && isset( $links[ $default ] ) ) {
		$links['x-default'] = $links[ $default ];
	}
	return $links;
}

/**
 * Balises hreflang (seulement quand une page existe dans au moins deux marchés).
 */
function print_alternates() {
	/** This filter is documented in includes/seo/meta.php */
	if ( is_paged() || apply_filters( 'learn_drive_noindex', false ) ) {
		return;
	}
	foreach ( alternates() as $code => $url ) {
		printf( '<link rel="alternate" hreflang="%s" href="%s" />' . "\n", esc_attr( $code ), esc_url( $url ) );
	}
}
add_action( 'wp_head', __NAMESPACE__ . '\\print_alternates', 2 );

/**
 * Liens vers les marchés en ligne (sélecteur), quand il y en a plusieurs.
 *
 * @return array<int,array{0:string,1:string}> Nom, adresse.
 */
function switcher() {
	$markets = active();
	if ( count( $markets ) < 2 ) {
		return array();
	}
	$items = array();
	foreach ( $markets as $market ) {
		$url = home_link( $market['slug'] );
		if ( $url ) {
			$items[] = array( $market['name'], $url );
		}
	}
	return $items;
}
