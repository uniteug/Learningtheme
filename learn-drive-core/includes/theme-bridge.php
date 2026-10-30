<?php
/**
 * Passerelle avec le thème : adresses clés, textes du bandeau d'accueil et
 * couleur d'accent des pages piliers. Le thème garde des valeurs de repli
 * quand le plugin est absent.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Theme;

use function LearnDrive\Core\setting;
use function LearnDrive\Core\setting_url;
use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\PT_SHEET;
use const LearnDrive\Core\PT_GUIDE;
use const LearnDrive\Core\PT_GLOSSARY;
use const LearnDrive\Core\PT_QUIZ;

defined( 'ABSPATH' ) || exit;

/**
 * Catégorie principale : la plus fournie du pays choisi (ou de tout le site).
 *
 * @return \WP_Term|null
 */
function main_track() {
	$args    = array(
		'taxonomy'   => TAX_TRACK,
		'hide_empty' => false,
		'number'     => 1,
		'orderby'    => 'count',
		'order'      => 'DESC',
	);
	$country = (string) setting( 'default_country' );
	if ( $country ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'   => 'ld_country',
				'value' => $country,
			),
		);
	}
	$terms = get_terms( $args );
	return $terms && ! is_wp_error( $terms ) ? $terms[0] : null;
}

/**
 * Adresses clés pour le thème.
 *
 * @param array<string,string> $links Valeurs de repli du thème.
 * @return array<string,string>
 */
function links( $links ) {
	$track = main_track();
	$exam  = $track ? \LearnDrive\Core\Permalinks\exam_for_track( $track ) : 0;
	$news  = (int) get_option( 'page_for_posts' );

	$archive = static function ( $type, $fallback ) {
		$url = get_post_type_archive_link( $type );
		return $url ? $url : $fallback;
	};

	return array_merge(
		$links,
		array(
			'exam'        => $exam ? get_permalink( $exam ) : $archive( PT_QUIZ, $links['exam'] ),
			'learn'       => $track ? get_term_link( $track ) : $links['learn'],
			'quiz'        => $track ? home_url( user_trailingslashit( $track->slug . '/' . setting( 'slug_quiz' ) ) ) : $archive( PT_QUIZ, $links['quiz'] ),
			'signs'       => $archive( PT_SHEET, $links['signs'] ),
			'news'        => $news ? get_permalink( $news ) : $links['news'],
			'guides'      => $archive( PT_GUIDE, $links['guides'] ),
			'glossary'    => $archive( PT_GLOSSARY, $links['glossary'] ),
			'account'     => setting_url( 'account_url' ),
			'register'    => get_option( 'users_can_register' ) ? wp_registration_url() : setting_url( 'account_url' ),
			'premium'     => setting_url( 'premium_url' ),
			'app_ios'     => esc_url_raw( (string) setting( 'app_ios_url' ) ),
			'app_android' => esc_url_raw( (string) setting( 'app_android_url' ) ),
		)
	);
}
add_filter( 'learn_drive_links', __NAMESPACE__ . '\\links' );

/**
 * Textes du bandeau d'accueil saisis dans les réglages.
 *
 * @param array $text Textes par défaut du thème.
 * @return array
 */
function home_text( $text ) {
	foreach ( array(
		'eyebrow' => 'home_eyebrow',
		'title'   => 'home_title',
		'lead'    => 'home_lead',
	) as $key => $option ) {
		$value = trim( (string) setting( $option ) );
		if ( '' !== $value ) {
			$text[ $key ] = $value;
		}
	}
	return $text;
}
add_filter( 'learn_drive_home_text', __NAMESPACE__ . '\\home_text' );

/**
 * Couleur d'accent de la page pilier courante (bandeau, boutons).
 */
function page_accent() {
	if ( ! is_tax( TAX_TRACK ) ) {
		return;
	}
	$color = sanitize_hex_color( (string) get_term_meta( get_queried_object_id(), 'ld_color', true ) );
	if ( $color ) {
		printf( "<style id=\"ld-page-accent\">:root{--ld-page-accent:%s}</style>\n", esc_html( $color ) );
	}
}
add_action( 'wp_head', __NAMESPACE__ . '\\page_accent', 20 );

/**
 * Accueil : les boucles d'actualités et de guides privilégient le pays
 * principal (les contenus européens, sans pays, restent affichés).
 *
 * @param array     $query Arguments de requête.
 * @param \WP_Block $block Bloc Boucle.
 * @return array
 */
function front_country_query( $query, $block ) {
	unset( $block );
	$country = (string) setting( 'default_country' );
	$types   = (array) ( $query['post_type'] ?? 'post' );
	if ( '' === $country || ! is_front_page() || ! array_intersect( $types, array( 'post', PT_GUIDE ) ) || ! empty( $query['tax_query'] ) ) {
		return $query;
	}
	$query['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		'relation' => 'OR',
		array(
			'taxonomy' => \LearnDrive\Core\TAX_COUNTRY,
			'field'    => 'slug',
			'terms'    => $country,
		),
		array(
			'taxonomy' => \LearnDrive\Core\TAX_COUNTRY,
			'operator' => 'NOT EXISTS',
		),
	);
	return $query;
}
add_filter( 'query_loop_block_query_vars', __NAMESPACE__ . '\\front_country_query', 10, 2 );

/**
 * Accueil : retire les sections qui n'auraient rien à montrer sur ce site.
 *
 * @param string[] $sections Sections.
 * @return string[]
 */
function home_sections( $sections ) {
	$published = static function ( $type ) {
		$counts = wp_count_posts( $type );
		return (int) ( $counts->publish ?? 0 );
	};
	$terms     = static function ( $taxonomy ) {
		return (int) wp_count_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);
	};

	$guides_type = function_exists( 'learn_drive_query_args' ) ? learn_drive_query_args( 'guides' )['postType'] : PT_GUIDE;
	$empty       = array(
		'home-guides'              => ! $published( $guides_type ),
		'home-signs'               => ! $published( PT_SHEET ),
		'home-europe'              => $terms( \LearnDrive\Core\TAX_COUNTRY ) < 2,
		'home-topics'              => ! $terms( \LearnDrive\Core\TAX_TOPIC ),
		'home-tracks'              => ! $terms( TAX_TRACK ),
		'home-question-of-the-day' => ! $published( \LearnDrive\Core\PT_QUESTION ),
	);

	return array_values( array_filter( $sections, static fn( $section ) => empty( $empty[ $section ] ) ) );
}
add_filter( 'learn_drive_home_sections', __NAMESPACE__ . '\\home_sections' );

/**
 * Menu principal : retire les liens vers des rubriques vides.
 *
 * @param array $items Liste de [libellé, adresse].
 * @return array
 */
function nav_items( $items ) {
	$empty = array();
	foreach ( array( \LearnDrive\Core\PT_LESSON, PT_SHEET, PT_GUIDE, PT_GLOSSARY ) as $type ) {
		$counts = wp_count_posts( $type );
		$link   = get_post_type_archive_link( $type );
		if ( $link && ! (int) ( $counts->publish ?? 0 ) ) {
			$empty[] = untrailingslashit( $link );
		}
	}
	return array_values( array_filter( $items, static fn( $item ) => ! in_array( untrailingslashit( (string) $item[1] ), $empty, true ) ) );
}
add_filter( 'learn_drive_nav_items', __NAMESPACE__ . '\\nav_items' );
add_filter( 'learn_drive_hero_chips', __NAMESPACE__ . '\\nav_items', 30 );

/**
 * Pied de page : mêmes règles que le menu (pas de lien vers une rubrique vide).
 *
 * @param array $columns Titre => liens.
 * @return array
 */
function footer_columns( $columns ) {
	return array_map( __NAMESPACE__ . '\\nav_items', (array) $columns );
}
add_filter( 'learn_drive_footer_columns', __NAMESPACE__ . '\\footer_columns' );
