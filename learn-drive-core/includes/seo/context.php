<?php
/**
 * Contexte SEO de la page courante : titre, description, canonique, image,
 * indexabilité. Utilisé par les balises meta, Open Graph et le JSON-LD.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\SEO;

use LearnDrive\Core\Questions;
use function LearnDrive\Core\setting;
use function LearnDrive\Core\primary_term;
use const LearnDrive\Core\PT_COURSE;
use const LearnDrive\Core\PT_LESSON;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\PT_QUIZ;
use const LearnDrive\Core\PT_SHEET;
use const LearnDrive\Core\PT_GUIDE;
use const LearnDrive\Core\PT_GLOSSARY;
use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\TAX_TOPIC;
use const LearnDrive\Core\TAX_COUNTRY;

defined( 'ABSPATH' ) || exit;

/**
 * Variables disponibles dans les modèles de titre.
 *
 * @param int $post_id Contenu.
 * @return array
 */
function template_vars( $post_id ) {
	$track   = primary_term( $post_id, TAX_TRACK );
	$topic   = primary_term( $post_id, TAX_TOPIC );
	$country = primary_term( $post_id, TAX_COUNTRY );

	return array(
		'{title}'    => wp_strip_all_tags( get_the_title( $post_id ) ),
		'{question}' => wp_strip_all_tags( get_the_title( $post_id ) ),
		'{track}'    => $track ? $track->name : '',
		'{cat}'      => $track ? $track->name : '',
		'{topic}'    => $topic ? $topic->name : '',
		'{country}'  => $country ? $country->name : '',
		'{pays}'     => $country ? $country->name : '',
		'{site}'     => get_bloginfo( 'name' ),
		'{sep}'      => setting( 'title_sep' ),
	);
}

/**
 * Applique un modèle et nettoie les séparateurs orphelins.
 *
 * @param string $template Modèle.
 * @param array  $vars     Variables.
 * @return string
 */
function fill_template( $template, array $vars ) {
	$title = preg_replace( '/\s+/u', ' ', strtr( $template, $vars ) );
	$parts = preg_split( '/\s*([–|])\s*/u', $title, -1, PREG_SPLIT_DELIM_CAPTURE );
	$out   = '';
	$sep   = '';
	foreach ( $parts as $index => $part ) {
		if ( 1 === $index % 2 ) {
			// Séparateur : on garde le plus « fort » (|) entre deux segments.
			$sep = ( '|' === $part || '|' === $sep ) ? '|' : $part;
			continue;
		}
		$part = trim( $part );
		if ( '' === $part ) {
			continue;
		}
		$out .= ( '' === $out ? '' : ' ' . ( $sep ? $sep : '–' ) . ' ' ) . $part;
		$sep  = '';
	}
	return $out;
}

/**
 * Titre SEO d'un contenu.
 *
 * @param int $post_id Contenu.
 * @return string
 */
function post_title( $post_id ) {
	$custom = (string) get_post_meta( $post_id, '_ld_seo_title', true );
	if ( '' !== $custom ) {
		return fill_template( $custom, template_vars( $post_id ) );
	}

	$map  = array(
		PT_QUESTION => 'tpl_question',
		PT_QUIZ     => 'tpl_quiz',
		PT_LESSON   => 'tpl_lesson',
		PT_COURSE   => 'tpl_lesson',
		PT_SHEET    => 'tpl_sheet',
		PT_GUIDE    => 'tpl_guide',
		PT_GLOSSARY => 'tpl_glossary',
		'post'      => 'tpl_post',
	);
	$type = get_post_type( $post_id );
	$tpl  = isset( $map[ $type ] ) ? (string) setting( $map[ $type ] ) : '{title} | {site}';
	$vars = template_vars( $post_id );

	// Pas de répétition : « Examen blanc — permis B – Permis B ».
	foreach ( array( '{track}', '{cat}', '{country}', '{pays}', '{topic}' ) as $key ) {
		if ( '' !== $vars[ $key ] && false !== mb_stripos( $vars['{title}'], $vars[ $key ] ) ) {
			$vars[ $key ] = '';
		}
	}

	return fill_template( $tpl, $vars );
}

/**
 * Description SEO d'un contenu (155 caractères environ).
 *
 * @param int $post_id Contenu.
 * @return string
 */
function post_description( $post_id ) {
	$custom = (string) get_post_meta( $post_id, '_ld_seo_desc', true );
	if ( '' !== $custom ) {
		return truncate( $custom );
	}

	$post    = get_post( $post_id );
	$excerpt = $post->post_excerpt;

	if ( PT_QUESTION === $post->post_type ) {
		$question = Questions\get( $post );
		$vars     = template_vars( $post_id );
		/* translators: 1: question, 2: category */
		$intro = $vars['{track}'] ? sprintf( __( 'Quiz %2$s : %1$s', 'learn-drive-core' ), $question['title'], $vars['{track}'] ) : $question['title'];
		$first = first_sentence( $post->post_content );
		return truncate( $excerpt ? $excerpt : $intro . ' ' . __( 'Réponse et explication détaillée.', 'learn-drive-core' ) . ( $first ? ' ' . $first : '' ) );
	}

	if ( '' === trim( $excerpt ) ) {
		$excerpt = wp_strip_all_tags( excerpt_remove_blocks( $post->post_content ) );
	}

	return truncate( $excerpt );
}

/**
 * Première phrase d'un contenu.
 *
 * @param string $content Contenu.
 * @return string
 */
function first_sentence( $content ) {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( excerpt_remove_blocks( $content ) ) ) );
	if ( preg_match( '/^(.{20,200}?[.!?])(\s|$)/u', $text, $m ) ) {
		return $m[1];
	}
	return '';
}

/**
 * Coupe un texte proprement à ~155 caractères.
 *
 * @param string $text  Texte.
 * @param int    $limit Limite.
 * @return string
 */
function truncate( $text, $limit = 158 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $text ) ) );
	if ( mb_strlen( $text ) <= $limit ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $limit - 1 );
	$cut = preg_replace( '/\s+\S*$/u', '', $cut );
	return rtrim( $cut, ' ,;:–-' ) . '…';
}

/**
 * Titre de la page courante.
 *
 * @return string
 */
function page_title() {
	$site = get_bloginfo( 'name' );
	$sep  = ' ' . setting( 'title_sep' ) . ' ';

	if ( is_front_page() ) {
		$tagline = get_bloginfo( 'description' );
		return $tagline ? $site . $sep . $tagline : $site;
	}

	if ( is_singular() ) {
		return post_title( get_queried_object_id() ) . page_suffix();
	}

	if ( is_tax( TAX_TRACK ) ) {
		$term = get_queried_object();
		return fill_template(
			(string) setting( 'tpl_track' ),
			array(
				'{title}' => $term->name,
				'{site}'  => $site,
			)
		);
	}

	if ( is_post_type_archive() ) {
		$object = get_queried_object();
		$name   = $object->labels->name ?? '';
		$track  = get_query_var( TAX_TRACK ) ? get_term_by( 'slug', get_query_var( TAX_TRACK ), TAX_TRACK ) : null;
		if ( $track ) {
			$name .= ' ' . $track->name;
		}
		return $name . page_suffix() . $sep . $site;
	}

	if ( is_home() ) {
		$page = (int) get_option( 'page_for_posts' );
		return ( $page ? get_the_title( $page ) : __( 'Actualités', 'learn-drive-core' ) ) . page_suffix() . $sep . $site;
	}

	if ( is_category() || is_tag() || is_tax() ) {
		return single_term_title( '', false ) . page_suffix() . $sep . $site;
	}

	if ( is_author() ) {
		return get_queried_object()->display_name . page_suffix() . $sep . $site;
	}

	if ( is_search() ) {
		/* translators: %s: search query */
		return sprintf( __( 'Recherche : %s', 'learn-drive-core' ), get_search_query() ) . $sep . $site;
	}

	if ( is_404() ) {
		return __( 'Page introuvable', 'learn-drive-core' ) . $sep . $site;
	}

	return wp_get_document_title();
}

/**
 * Suffixe de pagination (« – Page 2 »).
 *
 * @return string
 */
function page_suffix() {
	$paged = max( (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	/* translators: %d: page number */
	return $paged > 1 ? ' – ' . sprintf( __( 'Page %d', 'learn-drive-core' ), $paged ) : '';
}

/**
 * Description de la page courante.
 *
 * @return string
 */
function page_description() {
	if ( is_front_page() ) {
		$front = (int) get_option( 'page_on_front' );
		if ( $front ) {
			$desc = post_description( $front );
			if ( $desc ) {
				return $desc;
			}
		}
		return truncate( get_bloginfo( 'description' ) );
	}

	if ( is_singular() ) {
		return post_description( get_queried_object_id() );
	}

	if ( is_category() || is_tag() || is_tax() ) {
		return truncate( term_description() );
	}

	if ( is_post_type_archive() ) {
		$object = get_queried_object();
		$custom = get_option( 'ld_archive_intro_' . ( $object->name ?? '' ) );
		return truncate( $custom ? $custom : ( $object->description ?? '' ) );
	}

	if ( is_author() ) {
		return truncate( get_queried_object()->description );
	}

	return '';
}

/**
 * URL canonique de la page courante (auto-référente, pagination comprise).
 *
 * @return string
 */
function canonical() {
	if ( is_search() || is_404() ) {
		return '';
	}

	if ( is_singular() ) {
		$id    = get_queried_object_id();
		$other = (int) get_post_meta( $id, '_ld_canonical', true );
		if ( $other && 'publish' === get_post_status( $other ) ) {
			return get_permalink( $other );
		}
		$url  = get_permalink( $id );
		$page = (int) get_query_var( 'page' );
		if ( $page > 1 ) {
			$url = trailingslashit( $url ) . user_trailingslashit( (string) $page, 'single_paged' );
		}
		return $url;
	}

	if ( is_front_page() ) {
		return home_url( '/' );
	}

	$paged = (int) get_query_var( 'paged' );
	$url   = '';

	if ( is_home() ) {
		$page = (int) get_option( 'page_for_posts' );
		$url  = $page ? get_permalink( $page ) : home_url( '/' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$url = get_term_link( get_queried_object() );
	} elseif ( is_post_type_archive() ) {
		$type  = get_query_var( 'post_type' );
		$type  = is_array( $type ) ? reset( $type ) : $type;
		$track = get_query_var( TAX_TRACK );
		$url   = $track ? home_url( user_trailingslashit( $track . '/' . ( \LearnDrive\Core\Permalinks\silo_types()[ $type ] ?? '' ) ) ) : get_post_type_archive_link( $type );
	} elseif ( is_author() ) {
		$url = get_author_posts_url( get_queried_object_id() );
	}

	if ( ! $url || is_wp_error( $url ) ) {
		return '';
	}

	if ( $paged > 1 ) {
		$url = trailingslashit( $url ) . user_trailingslashit( 'page/' . $paged, 'paged' );
	}

	return $url;
}

/**
 * La page courante doit-elle être exclue de l'index ?
 *
 * @return bool
 */
function is_noindex() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- lecture seule.
	$private_params = array( 'ld_session', 'ld_action', 'defi', 'redirect_to', 'replytocom' );
	foreach ( $private_params as $param ) {
		if ( isset( $_GET[ $param ] ) ) {
			return true;
		}
	}
	// phpcs:enable

	if ( is_search() || is_404() ) {
		return true;
	}

	if ( is_singular() ) {
		$id = get_queried_object_id();
		if ( get_post_meta( $id, '_ld_noindex', true ) || get_post_meta( $id, '_ld_seo_noindex', true ) ) {
			return true;
		}
		if ( has_block( 'learn-drive/dashboard', $id ) ) {
			return true;
		}
	}

	if ( is_tax( array( \LearnDrive\Core\TAX_EXAM, \LearnDrive\Core\TAX_LEVEL ) ) ) {
		return true;
	}

	/**
	 * Filtre l'exclusion de l'index de la page courante.
	 *
	 * @param bool $noindex Exclure.
	 */
	return (bool) apply_filters( 'learn_drive_noindex', false );
}

/**
 * Image principale d'un contenu pour le partage et les données structurées.
 *
 * @param int $post_id Contenu.
 * @return array|null { url, width, height, alt }
 */
function post_image( $post_id ) {
	$thumb = get_post_thumbnail_id( $post_id );
	if ( $thumb ) {
		foreach ( array( 'learn-drive-16x9', 'full' ) as $size ) {
			$src = wp_get_attachment_image_src( $thumb, $size );
			if ( $src && $src[1] >= 1200 ) {
				return array(
					'url'    => $src[0],
					'width'  => $src[1],
					'height' => $src[2],
					'alt'    => (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true ),
					'id'     => $thumb,
				);
			}
		}
		$src = wp_get_attachment_image_src( $thumb, 'full' );
		if ( $src ) {
			return array(
				'url'    => $src[0],
				'width'  => $src[1],
				'height' => $src[2],
				'alt'    => (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true ),
				'id'     => $thumb,
			);
		}
	}

	if ( setting( 'og_images' ) && in_array( get_post_type( $post_id ), array( PT_QUIZ, PT_QUESTION, PT_LESSON, PT_COURSE, PT_GUIDE, PT_GLOSSARY ), true ) ) {
		return array(
			'url'    => OG\url( $post_id ),
			'width'  => 1200,
			'height' => 630,
			'alt'    => wp_strip_all_tags( get_the_title( $post_id ) ),
			'id'     => 0,
		);
	}

	return default_share_image();
}

/**
 * Image par défaut du site : logo, icône, sinon carte de partage générée.
 *
 * @return array|null
 */
function default_share_image() {
	$image = site_image();
	if ( $image && $image['width'] >= 600 ) {
		return $image;
	}
	if ( setting( 'og_images' ) ) {
		return array(
			'url'    => OG\url( 0 ),
			'width'  => 1200,
			'height' => 630,
			'alt'    => get_bloginfo( 'name' ),
			'id'     => 0,
		);
	}
	return $image;
}

/**
 * Image par défaut du site (logo ou icône).
 *
 * @return array|null
 */
function site_image() {
	$logo = (int) setting( 'org_logo' );
	if ( ! $logo ) {
		$logo = (int) get_theme_mod( 'custom_logo' );
	}
	if ( ! $logo ) {
		$logo = (int) get_option( 'site_icon' );
	}
	if ( $logo ) {
		$src = wp_get_attachment_image_src( $logo, 'full' );
		if ( $src ) {
			return array(
				'url'    => $src[0],
				'width'  => $src[1],
				'height' => $src[2],
				'alt'    => get_bloginfo( 'name' ),
				'id'     => $logo,
			);
		}
	}
	return null;
}

/**
 * Nom de l'organisation éditrice.
 *
 * @return string
 */
function org_name() {
	$name = (string) setting( 'org_name' );
	return '' !== $name ? $name : get_bloginfo( 'name' );
}
