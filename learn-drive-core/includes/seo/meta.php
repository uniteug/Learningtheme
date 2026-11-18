<?php
/**
 * Balises meta : titre, description, canonique, robots, Open Graph, X/Twitter.
 *
 * Si Yoast, Rank Math, SEOPress, AIOSEO ou The SEO Framework est actif, le
 * plugin leur cède les balises meta ; il garde seulement ses règles
 * d'exclusion de l'index (sessions d'examen, questions trop minces, comptes).
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\SEO\Meta;

use LearnDrive\Core\SEO;
use function LearnDrive\Core\setting;
use function LearnDrive\Core\seo_plugin_active;

defined( 'ABSPATH' ) || exit;

/**
 * Branche les filtres une fois les plugins chargés.
 */
function boot() {
	add_filter( 'wp_robots', __NAMESPACE__ . '\\robots', 99 );
	add_filter( 'wpseo_robots_array', __NAMESPACE__ . '\\third_party_robots' );
	add_filter( 'rank_math/frontend/robots', __NAMESPACE__ . '\\third_party_robots' );

	if ( seo_plugin_active() ) {
		// Yoast : titres et descriptions des contenus d'apprentissage générés par le plugin
		// quand l'éditeur n'en a pas saisi (questions : énoncé, « vrai ou faux », réponse).
		add_filter( 'wpseo_title', __NAMESPACE__ . '\\yoast_title' );
		add_filter( 'wpseo_opengraph_title', __NAMESPACE__ . '\\yoast_title' );
		add_filter( 'wpseo_metadesc', __NAMESPACE__ . '\\yoast_description' );
		add_filter( 'wpseo_opengraph_desc', __NAMESPACE__ . '\\yoast_description' );
		// Archives de questions et de quiz (noindex) : ni canonical vers une autre
		// page, ni rel prev/next vers des adresses redirigées.
		add_filter( 'wpseo_canonical', __NAMESPACE__ . '\\yoast_archive_links' );
		add_filter( 'wpseo_next_rel_link', __NAMESPACE__ . '\\yoast_archive_links' );
		add_filter( 'wpseo_prev_rel_link', __NAMESPACE__ . '\\yoast_archive_links' );
		return;
	}

	if ( setting( 'seo_titles' ) ) {
		add_filter( 'pre_get_document_title', __NAMESPACE__ . '\\document_title', 20 );
	}
	remove_action( 'wp_head', 'rel_canonical' );
	add_action( 'wp_head', __NAMESPACE__ . '\\head', 2 );
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\\boot', 20 );

/**
 * Titre de la balise <title>.
 *
 * @param string $title Titre proposé.
 * @return string
 */
function document_title( $title ) {
	$custom = SEO\page_title();
	return $custom ? $custom : $title;
}

/**
 * Directives robots (API wp_robots du cœur).
 *
 * @param array $robots Directives.
 * @return array
 */
function robots( $robots ) {
	if ( ! get_option( 'blog_public' ) ) {
		return $robots;
	}

	if ( SEO\is_noindex() ) {
		unset( $robots['index'], $robots['max-image-preview'], $robots['max-snippet'], $robots['max-video-preview'] );
		$robots['noindex'] = true;
		$robots['follow']  = true;
		return $robots;
	}

	$robots['max-image-preview'] = 'large';
	$robots['max-snippet']       = '-1';
	$robots['max-video-preview'] = '-1';

	return $robots;
}

/**
 * Exclusion de l'index transmise aux plugins SEO tiers.
 *
 * @param array $robots Directives du plugin tiers.
 * @return array
 */
function third_party_robots( $robots ) {
	if ( SEO\is_noindex() ) {
		$robots['index'] = 'noindex';
	}
	return $robots;
}

/**
 * Affiche une balise meta.
 *
 * @param string $attribute name ou property.
 * @param string $key       Clé.
 * @param string $value     Valeur.
 */
function tag( $attribute, $key, $value ) {
	if ( '' === (string) $value ) {
		return;
	}
	printf( '<meta %s="%s" content="%s">' . "\n", esc_attr( $attribute ), esc_attr( $key ), esc_attr( $value ) );
}

/**
 * Balises de l'en-tête.
 */
function head() {
	$description = SEO\page_description();
	$canonical   = SEO\canonical();
	$title       = SEO\page_title();

	echo "<!-- Learn & Drive SEO -->\n";
	tag( 'name', 'description', $description );

	if ( $canonical && ! SEO\is_noindex() ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canonical ) );
	} elseif ( is_singular() && isset( $_GET['ld_session'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		// Une session d'examen pointe vers la page d'accueil de l'examen.
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( get_permalink( get_queried_object_id() ) ) );
	}

	// Open Graph.
	$type = is_singular( 'post' ) ? 'article' : ( is_author() ? 'profile' : 'website' );
	tag( 'property', 'og:locale', str_replace( '-', '_', get_bloginfo( 'language' ) ) );
	tag( 'property', 'og:type', $type );
	tag( 'property', 'og:site_name', get_bloginfo( 'name' ) );
	$og_title = $title;
	if ( is_singular() ) {
		$og_title = (string) get_post_meta( get_queried_object_id(), '_ld_og_title', true );
		$og_title = wp_strip_all_tags( '' !== $og_title ? $og_title : get_the_title( get_queried_object_id() ) );
	}
	tag( 'property', 'og:title', $og_title );
	tag( 'property', 'og:description', $description );
	tag( 'property', 'og:url', $canonical ? $canonical : home_url( add_query_arg( array() ) ) );

	$image = is_singular() ? SEO\post_image( get_queried_object_id() ) : SEO\default_share_image();
	if ( $image ) {
		tag( 'property', 'og:image', $image['url'] );
		tag( 'property', 'og:image:width', (string) $image['width'] );
		tag( 'property', 'og:image:height', (string) $image['height'] );
		tag( 'property', 'og:image:alt', $image['alt'] );
	}

	if ( is_singular() ) {
		$id = get_queried_object_id();
		if ( 'post' === get_post_type( $id ) ) {
			tag( 'property', 'article:published_time', get_post_time( 'c', true, $id ) );
			tag( 'property', 'article:modified_time', get_post_modified_time( 'c', true, $id ) );
			$category = \LearnDrive\Core\primary_term( $id, 'category' );
			if ( $category ) {
				tag( 'property', 'article:section', $category->name );
			}
		}
	}

	tag( 'name', 'twitter:card', $image && $image['width'] >= 600 ? 'summary_large_image' : 'summary' );
	echo "<!-- / Learn & Drive SEO -->\n";
}

/**
 * Page dont Yoast n'a qu'un titre ou une description par défaut (rien de
 * saisi) et que Learn & Drive sait mieux décrire : questions, quiz, accueil,
 * catégories de permis, hub des marchés, thèmes, rubriques — à la place des
 * « Home - Site » et « Patente B Archivi - Site » des modèles de Yoast.
 *
 * @param string $field title|metadesc.
 * @return bool
 */
function yoast_generated( $field ) {
	if ( is_singular( array( \LearnDrive\Core\PT_QUESTION, \LearnDrive\Core\PT_QUIZ ) ) ) {
		return '' === (string) get_post_meta( get_queried_object_id(), '_yoast_wpseo_' . $field, true );
	}
	if ( is_front_page() ) {
		$front = (int) get_option( 'page_on_front' );
		return ! $front || '' === (string) get_post_meta( $front, '_yoast_wpseo_' . $field, true );
	}
	if ( is_category() || is_tax( array( \LearnDrive\Core\TAX_TRACK, \LearnDrive\Core\TAX_COUNTRY, \LearnDrive\Core\TAX_TOPIC ) ) ) {
		$term = get_queried_object();
		if ( ! $term instanceof \WP_Term ) {
			return false;
		}
		$custom = class_exists( '\WPSEO_Taxonomy_Meta' ) ? \WPSEO_Taxonomy_Meta::get_term_meta( $term, $term->taxonomy, 'metadesc' === $field ? 'desc' : 'title' ) : '';
		return '' === (string) $custom;
	}
	return is_post_type_archive( array( \LearnDrive\Core\PT_QUESTION, \LearnDrive\Core\PT_QUIZ ) );
}

/**
 * Titre Yoast d'une question ou d'un quiz.
 *
 * @param string $title Titre de Yoast.
 * @return string
 */
function yoast_title( $title ) {
	if ( ! yoast_generated( 'title' ) ) {
		return $title;
	}
	$custom = is_singular() && ! is_front_page() ? SEO\post_title( get_queried_object_id() ) : SEO\page_title();
	return '' !== $custom ? $custom : $title;
}

/**
 * Description Yoast d'une question ou d'un quiz.
 *
 * @param string $description Description de Yoast.
 * @return string
 */
function yoast_description( $description ) {
	if ( ! yoast_generated( 'metadesc' ) ) {
		return $description;
	}
	$custom = is_singular() && ! is_front_page() ? SEO\post_description( get_queried_object_id() ) : SEO\page_description();
	return '' !== $custom ? $custom : $description;
}

/**
 * Pas de canonical ni de rel prev/next sur les archives de questions et de
 * quiz : Yoast y mettait la page de la catégorie (/patente-b/) et des pages
 * redirigées, contradictoires avec leur noindex.
 *
 * @param string|false $value Valeur de Yoast.
 * @return string|false
 */
function yoast_archive_links( $value ) {
	if ( \LearnDrive\Core\SEO\Index\is_listing_archive() && SEO\is_noindex() ) {
		return false;
	}
	return $value;
}
