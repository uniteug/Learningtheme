<?php
/**
 * Politique d'indexation : seules les pages qui apportent une vraie réponse
 * sont proposées aux moteurs de recherche.
 *
 * Exclues automatiquement (noindex, follow + retrait des sitemaps) :
 * - contenus trop courts (seuil de mots par type, réglable) ;
 * - catégories, rubriques, étiquettes et thèmes avec trop peu de contenus et
 *   sans texte d'introduction suffisant ;
 * - auteurs avec trop peu d'articles ou sans biographie ;
 * - archives par date, pages de fichiers joints, archives vides, flux.
 *
 * Les questions gardent leur propre règle (explication obligatoire, voir
 * questions.php) ; l'accueil et la page des actualités ne sont jamais exclus.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\SEO\Index;

use function LearnDrive\Core\setting;
use function LearnDrive\Core\word_count;
use const LearnDrive\Core\PT_COURSE;
use const LearnDrive\Core\PT_LESSON;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\PT_SHEET;
use const LearnDrive\Core\PT_GUIDE;
use const LearnDrive\Core\PT_GLOSSARY;
use const LearnDrive\Core\PT_QUIZ;
use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\TAX_TOPIC;
use const LearnDrive\Core\TAX_FAMILY;
use const LearnDrive\Core\TAX_COUNTRY;

defined( 'ABSPATH' ) || exit;

/**
 * Nombre minimal de mots par type de contenu pour être indexé.
 *
 * Les fiches et le lexique sont courts par nature ; un quiz se juge sur ses
 * questions (voir is_thin_post()).
 *
 * @return array<string,int>
 */
function min_words() {
	$base = max( 0, (int) setting( 'index_min_words' ) );
	$min  = array(
		'post'      => $base,
		PT_GUIDE    => $base,
		PT_LESSON   => $base,
		PT_COURSE   => (int) round( $base * 0.6 ),
		'page'      => (int) round( $base * 0.6 ),
		PT_SHEET    => (int) round( $base * 0.3 ),
		PT_GLOSSARY => (int) round( $base * 0.2 ),
	);

	/**
	 * Seuils de mots par type de contenu en dessous desquels la page n'est pas indexée.
	 *
	 * @param array<string,int> $min Seuils.
	 */
	return apply_filters( 'learn_drive_index_min_words', $min );
}

/**
 * La politique est-elle active ?
 *
 * @return bool
 */
function enabled() {
	return (bool) setting( 'index_policy' );
}

/**
 * Un contenu est-il trop mince pour être indexé ?
 *
 * @param int|\WP_Post $post Contenu.
 * @return bool
 */
function is_thin_post( $post ) {
	$post = get_post( $post );
	if ( ! $post || ! enabled() ) {
		return false;
	}

	if ( 'page' === $post->post_type && in_array( (int) $post->ID, array( (int) get_option( 'page_on_front' ), (int) get_option( 'page_for_posts' ) ), true ) ) {
		return false;
	}

	if ( PT_QUIZ === $post->post_type ) {
		return count( \LearnDrive\Core\Quizzes\question_ids( $post->ID ) ) < 5;
	}

	// Un cours se juge aussi sur son programme : ses leçons s'affichent sur sa page.
	if ( PT_COURSE === $post->post_type && count( \LearnDrive\Core\Blocks\course_lessons( $post->ID ) ) >= 3 ) {
		return false;
	}

	$min = min_words();
	if ( ! isset( $min[ $post->post_type ] ) ) {
		return false;
	}

	return word_count( $post->post_content ) < $min[ $post->post_type ];
}

/**
 * Met à jour le drapeau noindex d'un contenu (hors questions) à l'enregistrement.
 *
 * @param int $post_id Contenu.
 */
function update_flag( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || wp_is_post_revision( $post_id ) || 'publish' !== $post->post_status || ! in_array( $post->post_type, array_merge( array_keys( min_words() ), array( PT_QUIZ ) ), true ) ) {
		return;
	}
	$thin = is_thin_post( $post );
	update_post_meta( $post_id, '_ld_words', word_count( $post->post_content ) );

	if ( $thin || get_post_meta( $post_id, '_ld_seo_noindex', true ) || get_post_meta( $post_id, '_ld_canonical', true ) ) {
		update_post_meta( $post_id, '_ld_noindex', 1 );
		update_post_meta( $post_id, '_ld_thin', $thin ? 1 : 0 );
	} else {
		delete_post_meta( $post_id, '_ld_noindex' );
		delete_post_meta( $post_id, '_ld_thin' );
	}
}
add_action( 'save_post', __NAMESPACE__ . '\\update_flag', 30 );

/**
 * Recalcule les drapeaux de tous les contenus (changement de seuil, mise à jour).
 *
 * @return int Nombre de contenus exclus.
 */
function recompute() {
	$types = array_merge( array_keys( min_words() ), array( PT_QUIZ ) );
	$ids   = get_posts(
		array(
			'post_type'      => $types,
			'post_status'    => 'publish',
			'posts_per_page' => -1, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- tâche ponctuelle d'administration.
			'fields'         => 'ids',
		)
	);
	$count = 0;
	foreach ( $ids as $id ) {
		update_flag( $id );
		$count += (int) (bool) get_post_meta( $id, '_ld_thin', true );
	}
	return $count;
}
add_action( 'update_option_' . \LearnDrive\Core\OPTION, __NAMESPACE__ . '\\schedule_recompute' );

/**
 * Programme un recalcul après une modification des réglages.
 */
function schedule_recompute() {
	if ( ! wp_next_scheduled( 'learn_drive_index_recompute' ) ) {
		wp_schedule_single_event( time() + 10, 'learn_drive_index_recompute' );
	}
}
add_action( 'learn_drive_index_recompute', __NAMESPACE__ . '\\recompute' );

/**
 * Nombre de mots d'introduction d'un terme (description + texte de la page pilier).
 *
 * @param \WP_Term $term Terme.
 * @return int
 */
function term_words( $term ) {
	return word_count( $term->description . ' ' . (string) get_term_meta( $term->term_id, 'ld_content', true ) );
}

/**
 * Une archive de terme est-elle trop mince pour être indexée ?
 *
 * Un terme est indexé s'il réunit assez de contenus OU s'il porte un vrai
 * texte d'introduction (page pilier).
 *
 * @param \WP_Term $term Terme.
 * @return bool
 */
function is_thin_term( $term ) {
	if ( ! $term instanceof \WP_Term || ! enabled() ) {
		return false;
	}
	$min_items = max( 1, (int) setting( 'index_min_items' ) );
	if ( 'post_tag' === $term->taxonomy ) {
		$min_items *= 2;
	}
	return (int) $term->count < $min_items && term_words( $term ) < 120;
}

/**
 * Taxonomies concernées par la règle des termes minces.
 *
 * @return string[]
 */
function taxonomies() {
	return array( 'category', 'post_tag', TAX_TRACK, TAX_TOPIC, TAX_FAMILY, TAX_COUNTRY );
}

/**
 * Un auteur est-il trop mince (peu d'articles, pas de biographie) ?
 *
 * @param int $user_id Auteur.
 * @return bool
 */
function is_thin_author( $user_id ) {
	if ( ! enabled() ) {
		return false;
	}
	$posts = (int) count_user_posts( $user_id, array( 'post', PT_GUIDE, PT_LESSON ), true );
	$bio   = word_count( (string) get_the_author_meta( 'description', $user_id ) );
	return $posts < max( 1, (int) setting( 'index_min_items' ) ) || $bio < 20;
}

/**
 * Décision pour la page courante (branchée sur le filtre learn_drive_noindex).
 *
 * @param bool $noindex Décision précédente.
 * @return bool
 */
function current_page( $noindex ) {
	if ( $noindex || ! enabled() || is_front_page() || is_home() ) {
		return $noindex;
	}
	if ( is_date() || is_attachment() || is_feed() ) {
		return true;
	}
	if ( is_singular() && ! is_singular( PT_QUESTION ) ) {
		return (bool) get_post_meta( get_queried_object_id(), '_ld_thin', true ) || is_thin_post( get_queried_object() );
	}
	if ( is_category() || is_tag() || is_tax( taxonomies() ) ) {
		return is_thin_term( get_queried_object() );
	}
	if ( is_author() ) {
		return is_thin_author( get_queried_object_id() );
	}
	if ( is_post_type_archive() && ! have_posts() ) {
		return true;
	}
	return $noindex;
}
add_filter( 'learn_drive_noindex', __NAMESPACE__ . '\\current_page' );

/**
 * Flux RSS : jamais dans l'index (en-tête HTTP, envoyé avant le flux).
 */
function feed_header() {
	if ( enabled() && is_feed() && ! headers_sent() ) {
		header( 'X-Robots-Tag: noindex, follow', true );
	}
}
add_action( 'template_redirect', __NAMESPACE__ . '\\feed_header', 0 );

/**
 * Sitemaps : retire les termes minces.
 *
 * @param array  $args     Arguments de requête.
 * @param string $taxonomy Taxonomie.
 * @return array
 */
function sitemap_terms( $args, $taxonomy ) {
	if ( ! enabled() || ! in_array( $taxonomy, taxonomies(), true ) ) {
		return $args;
	}
	$terms   = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		)
	);
	$exclude = array();
	foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
		if ( is_thin_term( $term ) ) {
			$exclude[] = $term->term_id;
		}
	}
	if ( $exclude ) {
		$args['exclude'] = array_merge( (array) ( $args['exclude'] ?? array() ), $exclude );
	}
	return $args;
}
add_filter( 'wp_sitemaps_taxonomies_query_args', __NAMESPACE__ . '\\sitemap_terms', 10, 2 );

/**
 * Sitemaps : retire les auteurs minces.
 *
 * @param array $args Arguments de requête.
 * @return array
 */
function sitemap_users( $args ) {
	if ( ! enabled() ) {
		return $args;
	}
	$authors = get_users(
		array(
			'has_published_posts' => true,
			'fields'              => 'ID',
		)
	);
	$exclude = array_values( array_filter( array_map( 'intval', $authors ), __NAMESPACE__ . '\\is_thin_author' ) );
	if ( $exclude ) {
		$args['exclude'] = array_merge( (array) ( $args['exclude'] ?? array() ), $exclude );
	}
	return $args;
}
add_filter( 'wp_sitemaps_users_query_args', __NAMESPACE__ . '\\sitemap_users' );

/**
 * Sitemaps : pas de pages en sitemap pour les types sans page utile.
 *
 * @param array $post_types Types.
 * @return array
 */
function sitemap_post_types( $post_types ) {
	unset( $post_types['attachment'] );
	return $post_types;
}
add_filter( 'wp_sitemaps_post_types', __NAMESPACE__ . '\\sitemap_post_types' );

/**
 * Liste des pages exclues (écran Santé SEO).
 *
 * @return array{posts:int[],terms:\WP_Term[]}
 */
function report() {
	$posts = get_posts(
		array(
			'post_type'      => 'any',
			'posts_per_page' => 200,
			'fields'         => 'ids',
			'meta_key'       => '_ld_thin', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => 1, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	$terms = array();
	foreach ( taxonomies() as $taxonomy ) {
		$list = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);
		foreach ( is_wp_error( $list ) ? array() : $list as $term ) {
			if ( is_thin_term( $term ) ) {
				$terms[] = $term;
			}
		}
	}
	return compact( 'posts', 'terms' );
}
