<?php
/**
 * Fil d'Ariane qui suit les silos : Accueil › Permis B › Cours › Leçon.
 *
 * Le même fil alimente le bloc « Fil d'Ariane » du cœur et le BreadcrumbList
 * des données structurées : affichage et balisage ne divergent jamais.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\SEO\Breadcrumbs;

use LearnDrive\Core\Permalinks;
use function LearnDrive\Core\primary_term;
use function LearnDrive\Core\setting;
use const LearnDrive\Core\PT_COURSE;
use const LearnDrive\Core\PT_LESSON;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\PT_QUIZ;
use const LearnDrive\Core\PT_SHEET;
use const LearnDrive\Core\PT_GUIDE;
use const LearnDrive\Core\PT_GLOSSARY;
use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\TAX_FAMILY;

defined( 'ABSPATH' ) || exit;

/**
 * Lien vers la rubrique Actualités.
 *
 * @return array
 */
function news_item() {
	$page = (int) get_option( 'page_for_posts' );
	return array(
		'label' => $page ? get_the_title( $page ) : __( 'Actualités', 'learn-drive-core' ),
		'url'   => $page ? get_permalink( $page ) : home_url( '/' ),
	);
}

/**
 * Élément d'archive d'un type de contenu.
 *
 * @param string $post_type Type.
 * @return array|null
 */
function archive_item( $post_type ) {
	$object = get_post_type_object( $post_type );
	$link   = get_post_type_archive_link( $post_type );
	if ( ! $object || ! $link ) {
		return null;
	}
	return array(
		'label' => $object->labels->name,
		'url'   => $link,
	);
}

/**
 * Fil d'Ariane de la page courante.
 *
 * @return array[] { label, url } — le dernier élément n'a pas d'URL.
 */
function trail() {
	static $trail = null;
	if ( null !== $trail ) {
		return $trail;
	}

	$trail = array(
		array(
			'label' => __( 'Accueil', 'learn-drive-core' ),
			'url'   => home_url( '/' ),
		),
	);

	if ( is_front_page() ) {
		return $trail;
	}

	if ( is_singular() ) {
		$post = get_queried_object();
		$type = $post->post_type;

		if ( in_array( $type, array( PT_COURSE, PT_LESSON, PT_QUIZ, PT_QUESTION ), true ) ) {
			$track = primary_term( $post->ID, TAX_TRACK );
			if ( $track ) {
				$trail[] = array(
					'label' => $track->name,
					'url'   => get_term_link( $track ),
				);
				$segment = Permalinks\silo_types( (string) ( Permalinks\track_markets()[ $track->slug ] ?? '' ) )[ $type ] ?? '';
				$object  = get_post_type_object( $type );
				$is_exam = PT_QUIZ === $type && Permalinks\exam_for_track( $track ) === $post->ID;
				if ( $segment && $object && ! $is_exam ) {
					$trail[] = array(
						'label' => $object->labels->name,
						'url'   => home_url( user_trailingslashit( $track->slug . '/' . $segment ) ),
					);
				}
			} else {
				$item = archive_item( $type );
				if ( $item ) {
					$trail[] = $item;
				}
			}

			if ( PT_LESSON === $type ) {
				$course = (int) get_post_meta( $post->ID, '_ld_course', true );
				if ( $course && 'publish' === get_post_status( $course ) ) {
					$trail[] = array(
						'label' => get_the_title( $course ),
						'url'   => get_permalink( $course ),
					);
				}
			}
		} elseif ( in_array( $type, array( PT_SHEET, PT_GUIDE, PT_GLOSSARY ), true ) ) {
			$item = archive_item( $type );
			if ( $item ) {
				$trail[] = $item;
			}
			if ( PT_SHEET === $type ) {
				$family = primary_term( $post->ID, TAX_FAMILY );
				if ( $family ) {
					$trail[] = array(
						'label' => $family->name,
						'url'   => get_term_link( $family ),
					);
				}
			}
		} elseif ( 'post' === $type ) {
			$trail[]  = news_item();
			$category = primary_term( $post->ID, 'category' );
			if ( $category && 'uncategorized' !== $category->slug ) {
				$trail[] = array(
					'label' => $category->name,
					'url'   => get_term_link( $category ),
				);
			}
		} elseif ( is_post_type_hierarchical( $type ) ) {
			foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor ) {
				$trail[] = array(
					'label' => get_the_title( $ancestor ),
					'url'   => get_permalink( $ancestor ),
				);
			}
		}

		$trail[] = array( 'label' => wp_strip_all_tags( get_the_title( $post ) ) );
	} elseif ( is_home() ) {
		$trail[] = array( 'label' => news_item()['label'] );
	} elseif ( is_category() || is_tag() ) {
		$trail[] = news_item();
		$trail[] = array( 'label' => single_term_title( '', false ) );
	} elseif ( is_tax() ) {
		$term = get_queried_object();
		if ( TAX_FAMILY === $term->taxonomy ) {
			$item = archive_item( PT_SHEET );
			if ( $item ) {
				$trail[] = $item;
			}
		}
		foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, $term->taxonomy );
			$trail[]  = array(
				'label' => $ancestor->name,
				'url'   => get_term_link( $ancestor ),
			);
		}
		$trail[] = array( 'label' => $term->name );
	} elseif ( is_post_type_archive() ) {
		$object = get_queried_object();
		$track  = get_query_var( TAX_TRACK ) ? get_term_by( 'slug', get_query_var( TAX_TRACK ), TAX_TRACK ) : null;
		if ( $track ) {
			$trail[] = array(
				'label' => $track->name,
				'url'   => get_term_link( $track ),
			);
		}
		$trail[] = array( 'label' => $object->labels->name ?? '' );
	} elseif ( is_author() ) {
		$trail[] = array( 'label' => get_queried_object()->display_name );
	} elseif ( is_search() ) {
		$trail[] = array( 'label' => __( 'Recherche', 'learn-drive-core' ) );
	} elseif ( is_404() ) {
		$trail[] = array( 'label' => __( 'Page introuvable', 'learn-drive-core' ) );
	}

	/**
	 * Filtre le fil d'Ariane.
	 *
	 * @param array[] $trail Éléments.
	 */
	$trail = apply_filters( 'learn_drive_breadcrumbs', $trail );

	return $trail;
}

/**
 * Remplace les éléments du bloc « Fil d'Ariane » du cœur.
 *
 * @param array[] $items Éléments proposés par le cœur.
 * @return array[]
 */
function core_block_items( $items ) {
	$trail = trail();
	if ( count( $trail ) < 2 ) {
		return $items;
	}
	return array_map(
		static function ( $item ) {
			$out = array( 'label' => $item['label'] );
			if ( ! empty( $item['url'] ) && ! is_wp_error( $item['url'] ) ) {
				$out['url'] = $item['url'];
			}
			return $out;
		},
		$trail
	);
}
add_filter( 'block_core_breadcrumbs_items', __NAMESPACE__ . '\\core_block_items' );
