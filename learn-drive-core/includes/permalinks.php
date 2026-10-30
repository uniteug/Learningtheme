<?php
/**
 * Architecture d'URL en silos :
 *
 *   /permis-b/                          page pilier de la catégorie
 *   /permis-b/cours/{lecon}/            leçon
 *   /permis-b/formation/{cours}/        cours (parcours)
 *   /permis-b/quiz/{theme}/             quiz thématique
 *   /permis-b/examen-blanc/             examen blanc (une seule URL)
 *   /permis-b/questions/{slug}/         page d'une question
 *   /panneaux/{slug}/  /infos/{slug}/  /lexique/{slug}/
 *
 * Slash final constant, minuscules, sans date ni identifiant numérique.
 * Toute URL secondaire (ancienne, sans silo) redirige en 301 vers l'URL canonique.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Permalinks;

use LearnDrive\Core\Markets;

use function LearnDrive\Core\setting;
use function LearnDrive\Core\primary_term;
use const LearnDrive\Core\PT_COURSE;
use const LearnDrive\Core\PT_LESSON;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\PT_QUIZ;
use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\TAX_TOPIC;

defined( 'ABSPATH' ) || exit;

/**
 * Types de contenus rangés dans le silo d'une catégorie, avec leur segment
 * d'URL (traduit par marché : « domande », « questions », « preguntas »…).
 *
 * @param string $country Marché (vide = segments du site).
 * @return array<string,string>
 */
function silo_types( $country = '' ) {
	return array(
		PT_LESSON   => Markets\segment( 'slug_lesson', $country ),
		PT_COURSE   => Markets\segment( 'slug_course', $country ),
		PT_QUIZ     => Markets\segment( 'slug_quiz', $country ),
		PT_QUESTION => Markets\segment( 'slug_question', $country ),
	);
}

/**
 * Catégories (racines de silo) et leur marché.
 *
 * @return array<string,string> Slug => pays.
 */
function track_markets() {
	$map = get_option( 'ld_track_slugs' );

	if ( ! is_array( $map ) || ( $map && array_is_list( $map ) ) ) {
		$terms = get_terms(
			array(
				'taxonomy'   => TAX_TRACK,
				'hide_empty' => false,
				'lang'       => '', // Polylang : catégories de toutes les langues.
			)
		);
		$map   = array();
		foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
			$map[ $term->slug ] = Markets\of_term( $term );
		}
		update_option( 'ld_track_slugs', $map, true );
	}

	return $map;
}

/**
 * Slugs des catégories (racines de silo).
 *
 * @return string[]
 */
function track_slugs() {
	return array_map( 'strval', array_keys( track_markets() ) );
}

/**
 * Règles de réécriture des silos.
 */
function add_rules() {
	add_rewrite_tag( '%ld_exam_track%', '([^/]+)' );

	// Familles de fiches (/panneaux/famille/danger/) avant les règles des fiches elles-mêmes.
	$family = preg_quote( setting( 'slug_sheet' ) . '/' . setting( 'slug_family' ), '#' );
	add_rewrite_rule( "^{$family}/([^/]+)/?$", 'index.php?' . \LearnDrive\Core\TAX_FAMILY . '=$matches[1]', 'top' );
	add_rewrite_rule( "^{$family}/([^/]+)/page/([0-9]{1,})/?$", 'index.php?' . \LearnDrive\Core\TAX_FAMILY . '=$matches[1]&paged=$matches[2]', 'top' );

	// Une série de règles par marché : chacun a ses propres segments d'URL.
	$markets = array();
	foreach ( track_markets() as $slug => $country ) {
		$markets[ (string) $country ][] = (string) $slug;
	}

	foreach ( $markets as $country => $slugs ) {
		$group = '(' . implode( '|', array_map( 'preg_quote', $slugs ) ) . ')';

		add_rewrite_rule( "^{$group}/?$", 'index.php?' . TAX_TRACK . '=$matches[1]', 'top' );
		add_rewrite_rule( "^{$group}/page/([0-9]{1,})/?$", 'index.php?' . TAX_TRACK . '=$matches[1]&paged=$matches[2]', 'top' );
		add_rewrite_rule( "^{$group}/" . preg_quote( Markets\segment( 'slug_exam', $country ), '#' ) . '/?$', 'index.php?ld_exam_track=$matches[1]', 'top' );

		foreach ( silo_types( $country ) as $post_type => $segment ) {
			$segment = preg_quote( $segment, '#' );
			add_rewrite_rule( "^{$group}/{$segment}/?$", "index.php?post_type={$post_type}&" . TAX_TRACK . '=$matches[1]', 'top' );
			add_rewrite_rule( "^{$group}/{$segment}/page/([0-9]{1,})/?$", "index.php?post_type={$post_type}&" . TAX_TRACK . '=$matches[1]&paged=$matches[2]', 'top' );
			add_rewrite_rule( "^{$group}/{$segment}/([^/]+)/?$", "index.php?{$post_type}=\$matches[2]", 'top' );
		}
	}
}
add_action( 'init', __NAMESPACE__ . '\\add_rules', 20 );

/**
 * Régénère la liste des slugs et les règles quand une catégorie change.
 */
function schedule_flush() {
	delete_option( 'ld_track_slugs' );
	update_option( 'ld_flush_rewrite', 1, true );
}
add_action( 'created_' . TAX_TRACK, __NAMESPACE__ . '\\schedule_flush' );
add_action( 'edited_' . TAX_TRACK, __NAMESPACE__ . '\\schedule_flush' );
add_action( 'delete_' . TAX_TRACK, __NAMESPACE__ . '\\schedule_flush' );
add_action( 'update_option_learn_drive_core', __NAMESPACE__ . '\\schedule_flush' );

/**
 * Applique un vidage des règles programmé (une seule fois).
 */
function maybe_flush() {
	if ( get_option( 'ld_flush_rewrite' ) ) {
		delete_option( 'ld_flush_rewrite' );
		flush_rewrite_rules( false );
	}
}
add_action( 'init', __NAMESPACE__ . '\\maybe_flush', 99 );

/**
 * Quiz « examen blanc » de référence d'une catégorie.
 *
 * @param \WP_Term|int $track Catégorie.
 * @return int ID du quiz ou 0.
 */
function exam_for_track( $track ) {
	$term = get_term( $track, TAX_TRACK );
	if ( ! $term || is_wp_error( $term ) ) {
		return 0;
	}

	$cache_key = 'ld_exam_' . $term->term_id;
	$found     = wp_cache_get( $cache_key, 'learn-drive' );
	if ( false !== $found ) {
		return (int) $found;
	}

	$ids = get_posts(
		array(
			'post_type'        => PT_QUIZ,
			'post_status'      => 'publish',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'orderby'          => array(
			'menu_order' => 'ASC',
			'ID'         => 'ASC',
			),
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'meta_key'         => '_ld_mode', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'       => 'exam', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => TAX_TRACK,
					'terms'            => $term->term_id,
					'include_children' => false,
				),
			),
		)
	);

	$id = $ids ? (int) $ids[0] : 0;
	wp_cache_set( $cache_key, $id, 'learn-drive', HOUR_IN_SECONDS );

	return $id;
}

/**
 * Résout /permis-b/examen-blanc/ vers le quiz d'examen de la catégorie.
 *
 * @param array $vars Variables de requête.
 * @return array
 */
function resolve_exam_request( $vars ) {
	if ( empty( $vars['ld_exam_track'] ) ) {
		return $vars;
	}

	$term = get_term_by( 'slug', $vars['ld_exam_track'], TAX_TRACK );
	$id   = $term ? exam_for_track( $term ) : 0;

	if ( ! $id ) {
		return array( 'error' => '404' );
	}

	return array(
		PT_QUIZ     => get_post_field( 'post_name', $id ),
		'post_type' => PT_QUIZ,
		'name'      => get_post_field( 'post_name', $id ),
	);
}
add_filter( 'request', __NAMESPACE__ . '\\resolve_exam_request' );

/**
 * URL d'un contenu de silo.
 *
 * @param string   $link Lien proposé par WordPress.
 * @param \WP_Post $post Contenu.
 * @return string
 */
function post_link( $link, $post ) {
	if ( ! isset( silo_types()[ $post->post_type ] ) || ! in_array( $post->post_status, array( 'publish', 'private', 'future' ), true ) || ! $post->post_name ) {
		return $link;
	}

	$track   = primary_term( $post->ID, TAX_TRACK );
	$markets = track_markets();
	if ( ! $track || ! isset( $markets[ $track->slug ] ) ) {
		return $link;
	}
	$country = (string) $markets[ $track->slug ];

	if ( PT_QUIZ === $post->post_type && 'exam' === get_post_meta( $post->ID, '_ld_mode', true ) && exam_for_track( $track ) === $post->ID ) {
		return home_url( user_trailingslashit( $track->slug . '/' . Markets\segment( 'slug_exam', $country ) ) );
	}

	return home_url( user_trailingslashit( $track->slug . '/' . silo_types( $country )[ $post->post_type ] . '/' . $post->post_name ) );
}
add_filter( 'post_type_link', __NAMESPACE__ . '\\post_link', 10, 2 );

/**
 * URL d'une page pilier : /permis-b/.
 *
 * @param string   $link     Lien proposé.
 * @param \WP_Term $term     Terme.
 * @param string   $taxonomy Taxonomie.
 * @return string
 */
function term_link( $link, $term, $taxonomy ) {
	if ( TAX_TRACK === $taxonomy && in_array( $term->slug, track_slugs(), true ) ) {
		return home_url( user_trailingslashit( $term->slug ) );
	}
	return $link;
}
add_filter( 'term_link', __NAMESPACE__ . '\\term_link', 10, 3 );

/**
 * Redirection 301 vers l'URL canonique (ancienne URL, segment ou catégorie erronés).
 */
function canonical_redirect() {
	if ( is_admin() || is_preview() || ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture seule.
		return;
	}

	if ( is_tax( array( TAX_TRACK, TAX_TOPIC ) ) && get_query_var( 'paged' ) > 1 ) {
		wp_safe_redirect( get_term_link( get_queried_object() ), 301 );
		exit;
	}

	if ( ! is_singular( array_keys( silo_types() ) ) || get_query_var( 'page' ) > 1 ) {
		return;
	}

	$canonical = get_permalink( get_queried_object_id() );
	$request   = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	$expected  = wp_parse_url( $canonical, PHP_URL_PATH );

	if ( $request && $expected && untrailingslashit( $request ) !== untrailingslashit( $expected ) ) {
		wp_safe_redirect( $canonical, 301 );
		exit;
	}
}
add_action( 'template_redirect', __NAMESPACE__ . '\\canonical_redirect', 5 );

/**
 * Les pages de silo ne doivent jamais être interprétées comme des pages paginées internes.
 *
 * @param string $redirect_url URL proposée par redirect_canonical().
 * @return string|false
 */
function protect_silo_from_core_canonical( $redirect_url ) {
	if ( get_query_var( 'ld_exam_track' ) ) {
		return false;
	}
	return $redirect_url;
}
add_filter( 'redirect_canonical', __NAMESPACE__ . '\\protect_silo_from_core_canonical' );
