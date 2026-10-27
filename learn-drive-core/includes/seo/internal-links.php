<?php
/**
 * Maillage interne automatique :
 * - ancres sur les titres H2/H3 (sommaire, liens profonds) ;
 * - liens du lexique dans les textes (première occurrence seulement, désactivable).
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\SEO\Links;

use const LearnDrive\Core\PT_GLOSSARY;
use const LearnDrive\Core\PT_LESSON;
use const LearnDrive\Core\PT_GUIDE;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\PT_COURSE;
use function LearnDrive\Core\setting;

defined( 'ABSPATH' ) || exit;

/**
 * Identifiant d'ancre d'un titre (unique dans la page).
 *
 * @param string $text Texte du titre.
 * @param array  $used Identifiants déjà utilisés (par référence).
 * @return string
 */
function anchor_id( $text, array &$used ) {
	$base = sanitize_title( wp_strip_all_tags( $text ) );
	$base = $base ? $base : 'section';
	$id   = $base;
	$i    = 2;
	while ( isset( $used[ $id ] ) ) {
		$id = $base . '-' . $i++;
	}
	$used[ $id ] = true;
	return $id;
}

/**
 * Titres H2 d'un contenu, avec leurs ancres (même ordre que add_heading_ids()).
 *
 * @param string $content Contenu brut.
 * @return array[] { id, text }
 */
function headings( $content ) {
	$html = do_blocks( $content );
	$used = array();
	$out  = array();

	if ( preg_match_all( '#<h([23])([^>]*)>(.*?)</h\1>#is', $html, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $match ) {
			$text = trim( wp_strip_all_tags( $match[3] ) );
			if ( preg_match( '/\sid=["\']([^"\']+)["\']/', $match[2], $id_match ) ) {
				$id          = $id_match[1];
				$used[ $id ] = true;
			} else {
				$id = anchor_id( $text, $used );
			}
			if ( '2' === $match[1] && '' !== $text ) {
				$out[] = array(
					'id'   => $id,
					'text' => $text,
				);
			}
		}
	}

	return $out;
}

/**
 * Ajoute des ancres aux titres du contenu.
 *
 * @param string $content Contenu rendu.
 * @return string
 */
function add_heading_ids( $content ) {
	if ( ! is_singular( array( 'post', PT_LESSON, PT_GUIDE, PT_COURSE, 'page' ) ) || ! str_contains( $content, '<h' ) ) {
		return $content;
	}

	$used      = array();
	$processor = new \WP_HTML_Tag_Processor( $content );
	$positions = array();

	// Première passe : réserver les identifiants existants.
	while ( $processor->next_tag() ) {
		if ( in_array( $processor->get_tag(), array( 'H2', 'H3' ), true ) && $processor->get_attribute( 'id' ) ) {
			$used[ $processor->get_attribute( 'id' ) ] = true;
		}
	}

	// Seconde passe : les titres sans identifiant reçoivent une ancre.
	return preg_replace_callback(
		'#<h([23])((?:(?!\sid=)[^>])*)>(.*?)</h\1>#is',
		static function ( $match ) use ( &$used ) {
			$id = anchor_id( $match[3], $used );
			return sprintf( '<h%1$s%2$s id="%3$s">%4$s</h%1$s>', $match[1], $match[2], esc_attr( $id ), $match[3] );
		},
		$content
	);
}
add_filter( 'the_content', __NAMESPACE__ . '\\add_heading_ids', 12 );

/**
 * Termes du lexique (titre et synonymes) → URL, mis en cache.
 *
 * @return array<string,array{url:string,id:int}>
 */
function glossary_map() {
	$cache_key = 'ld_glossary_map_' . wp_cache_get_last_changed( 'posts' );
	$map       = wp_cache_get( $cache_key, 'learn-drive' );
	if ( is_array( $map ) ) {
		return $map;
	}

	$map   = array();
	$terms = get_posts(
		array(
			'post_type'      => PT_GLOSSARY,
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'no_found_rows'  => true,
		)
	);
	foreach ( $terms as $term ) {
		$labels   = array( $term->post_title );
		$synonyms = (string) get_post_meta( $term->ID, '_ld_synonyms', true );
		if ( $synonyms ) {
			$labels = array_merge( $labels, array_map( 'trim', explode( ',', $synonyms ) ) );
		}
		foreach ( array_filter( $labels ) as $label ) {
			if ( mb_strlen( $label ) >= 4 ) {
				$map[ mb_strtolower( $label ) ] = array(
					'url' => get_permalink( $term ),
					'id'  => $term->ID,
				);
			}
		}
	}

	// Les expressions les plus longues d'abord (« distance d'arrêt » avant « arrêt »).
	uksort(
		$map,
		static function ( $a, $b ) {
			return mb_strlen( $b ) <=> mb_strlen( $a );
		}
	);

	wp_cache_set( $cache_key, $map, 'learn-drive', DAY_IN_SECONDS );
	return $map;
}

/**
 * Lie la première occurrence de chaque terme du lexique.
 *
 * @param string $content Contenu rendu.
 * @return string
 */
function glossary_links( $content ) {
	if ( ! setting( 'glossary_links' ) || ! is_singular( array( 'post', PT_LESSON, PT_GUIDE, PT_QUESTION ) ) || is_feed() ) {
		return $content;
	}

	$map = glossary_map();
	if ( ! $map ) {
		return $content;
	}

	$current = get_queried_object_id();
	$max     = (int) setting( 'glossary_max' );
	$done    = array();
	$count   = 0;
	$skip    = 0;
	$parts   = preg_split( '#(<[^>]+>)#', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	$pattern = '#(?<![\p{L}\p{N}])(' . implode( '|', array_map( static fn( $k ) => preg_quote( $k, '#' ), array_keys( $map ) ) ) . ')(?![\p{L}\p{N}])#iu';

	foreach ( $parts as $index => $part ) {
		if ( '' === $part ) {
			continue;
		}
		if ( '<' === $part[0] ) {
			if ( preg_match( '#^<(a|h[1-6]|button|code|pre|summary|figcaption|label|script|style)\b#i', $part ) ) {
				++$skip;
			} elseif ( preg_match( '#^</(a|h[1-6]|button|code|pre|summary|figcaption|label|script|style)>#i', $part ) ) {
				$skip = max( 0, $skip - 1 );
			}
			continue;
		}
		if ( $skip || $count >= $max ) {
			continue;
		}

		$parts[ $index ] = preg_replace_callback(
			$pattern,
			static function ( $match ) use ( $map, &$done, &$count, $max, $current ) {
				$key   = mb_strtolower( $match[1] );
				$entry = $map[ $key ] ?? null;
				if ( ! $entry || isset( $done[ $entry['id'] ] ) || $entry['id'] === $current || $count >= $max ) {
					return $match[1];
				}
				$done[ $entry['id'] ] = true;
				++$count;
				return sprintf( '<a class="ld-glossary-link" href="%s">%s</a>', esc_url( $entry['url'] ), $match[1] );
			},
			$part
		);
	}

	return implode( '', $parts );
}
add_filter( 'the_content', __NAMESPACE__ . '\\glossary_links', 13 );
