<?php
/**
 * Sitemaps XML : on s'appuie sur les sitemaps natifs de WordPress (découpés
 * par 2 000 URL, un fichier par type), en ajoutant un lastmod fiable, en
 * excluant les contenus en noindex, et en publiant un sitemap Google News des
 * actualités des dernières 48 h.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\SEO\Sitemaps;

use const LearnDrive\Core\TAX_EXAM;
use const LearnDrive\Core\TAX_LEVEL;
use function LearnDrive\Core\setting;
use function LearnDrive\Core\seo_plugin_active;

defined( 'ABSPATH' ) || exit;

/**
 * Date de dernière modification de chaque URL.
 *
 * @param array    $entry Entrée.
 * @param \WP_Post $post  Contenu.
 * @return array
 */
function post_entry( $entry, $post ) {
	$entry['lastmod'] = get_post_modified_time( 'c', true, $post );
	return $entry;
}
add_filter( 'wp_sitemaps_posts_entry', __NAMESPACE__ . '\\post_entry', 10, 2 );

/**
 * Exclut les contenus en noindex (questions trop minces, doublons, réglage manuel).
 *
 * @param array $args Arguments de requête.
 * @return array
 */
function exclude_noindex( $args ) {
	$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		'relation' => 'AND',
		array(
			'key'     => '_ld_noindex',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_ld_seo_noindex',
			'compare' => 'NOT EXISTS',
		),
	);
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', __NAMESPACE__ . '\\exclude_noindex' );

/**
 * Retire les taxonomies sans page publique utile.
 *
 * @param array $taxonomies Taxonomies.
 * @return array
 */
function taxonomies( $taxonomies ) {
	unset( $taxonomies[ TAX_EXAM ], $taxonomies[ TAX_LEVEL ], $taxonomies['post_format'] );
	return $taxonomies;
}
add_filter( 'wp_sitemaps_taxonomies', __NAMESPACE__ . '\\taxonomies' );

/**
 * Route /news-sitemap.xml.
 */
function rewrite() {
	add_rewrite_rule( '^news-sitemap\.xml$', 'index.php?ld_news_sitemap=1', 'top' );
}
add_action( 'init', __NAMESPACE__ . '\\rewrite' );

/**
 * Variable de requête.
 *
 * @param string[] $vars Variables.
 * @return string[]
 */
function query_vars( $vars ) {
	$vars[] = 'ld_news_sitemap';
	return $vars;
}
add_filter( 'query_vars', __NAMESPACE__ . '\\query_vars' );

/**
 * Sitemap Google News : actualités publiées dans les 48 dernières heures.
 */
function render_news_sitemap() {
	if ( ! get_query_var( 'ld_news_sitemap' ) ) {
		return;
	}

	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1000,
			'no_found_rows'  => true,
			'date_query'     => array(
				array(
					'after'     => '48 hours ago',
					'inclusive' => true,
					'column'    => 'post_date_gmt',
				),
			),
		)
	);

	$name     = (string) setting( 'news_name' );
	$name     = '' !== $name ? $name : get_bloginfo( 'name' );
	$language = substr( get_bloginfo( 'language' ), 0, 2 );

	status_header( 200 );
	header( 'Content-Type: application/xml; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex, follow' );

	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";
	foreach ( $posts as $post ) {
		if ( get_post_meta( $post->ID, '_ld_seo_noindex', true ) ) {
			continue;
		}
		printf(
			"<url><loc>%s</loc><news:news><news:publication><news:name>%s</news:name><news:language>%s</news:language></news:publication><news:publication_date>%s</news:publication_date><news:title>%s</news:title></news:news></url>\n",
			esc_url( get_permalink( $post ) ),
			esc_xml( $name ),
			esc_xml( $language ),
			esc_xml( get_post_time( 'c', true, $post ) ),
			esc_xml( wp_strip_all_tags( get_the_title( $post ) ) )
		);
	}
	echo '</urlset>';
	exit;
}
add_action( 'template_redirect', __NAMESPACE__ . '\\render_news_sitemap', 0 );

/**
 * Déclare le sitemap News dans robots.txt.
 *
 * @param string $output Contenu de robots.txt.
 * @param bool   $public Site public.
 * @return string
 */
function robots_txt( $output, $public ) {
	if ( $public && ! seo_plugin_active() ) {
		$output .= "\nSitemap: " . home_url( '/news-sitemap.xml' ) . "\n";
	}
	return $output;
}
add_filter( 'robots_txt', __NAMESPACE__ . '\\robots_txt', 20, 2 );
