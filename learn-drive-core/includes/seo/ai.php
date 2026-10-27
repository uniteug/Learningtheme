<?php
/**
 * Recherche générative : fichier /llms.txt listant les pages piliers et
 * réglage des robots d'IA (autoriser / bloquer par agent).
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\SEO\AI;

use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\PT_GUIDE;
use const LearnDrive\Core\PT_QUIZ;
use function LearnDrive\Core\setting;

defined( 'ABSPATH' ) || exit;

/**
 * Robots d'IA connus.
 *
 * @return array<string,string>
 */
function bots() {
	return array(
		'GPTBot'             => 'OpenAI (entraînement)',
		'OAI-SearchBot'      => 'OpenAI (recherche)',
		'ClaudeBot'          => 'Anthropic',
		'Google-Extended'    => 'Google (Gemini, entraînement)',
		'Applebot-Extended'  => 'Apple (entraînement)',
		'PerplexityBot'      => 'Perplexity',
		'CCBot'              => 'Common Crawl',
		'Bytespider'         => 'ByteDance',
		'meta-externalagent' => 'Meta',
	);
}

/**
 * Ajoute les blocages choisis dans robots.txt.
 *
 * @param string $output Contenu.
 * @param bool   $public Site public.
 * @return string
 */
function robots_txt( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}
	$blocked = array_intersect( (array) setting( 'ai_block' ), array_keys( bots() ) );
	foreach ( $blocked as $bot ) {
		$output .= "\nUser-agent: {$bot}\nDisallow: /\n";
	}
	return $output;
}
add_filter( 'robots_txt', __NAMESPACE__ . '\\robots_txt', 30, 2 );

/**
 * Route /llms.txt.
 */
function rewrite() {
	add_rewrite_rule( '^llms\.txt$', 'index.php?ld_llms=1', 'top' );
}
add_action( 'init', __NAMESPACE__ . '\\rewrite' );

/**
 * Variable de requête.
 *
 * @param string[] $vars Variables.
 * @return string[]
 */
function query_vars( $vars ) {
	$vars[] = 'ld_llms';
	return $vars;
}
add_filter( 'query_vars', __NAMESPACE__ . '\\query_vars' );

/**
 * Contenu Markdown de llms.txt.
 *
 * @return string
 */
function build() {
	$lines   = array( '# ' . get_bloginfo( 'name' ), '' );
	$tagline = get_bloginfo( 'description' );
	if ( $tagline ) {
		$lines[] = '> ' . $tagline;
		$lines[] = '';
	}

	$policy = get_page_by_path( 'politique-editoriale' );
	if ( $policy ) {
		$lines[] = sprintf( '- [%s](%s)', get_the_title( $policy ), get_permalink( $policy ) );
		$lines[] = '';
	}

	$tracks = get_terms(
		array(
			'taxonomy'   => TAX_TRACK,
			'hide_empty' => true,
		)
	);
	if ( $tracks && ! is_wp_error( $tracks ) ) {
		$lines[] = '## ' . setting( 'label_track_pl' );
		foreach ( $tracks as $term ) {
			$lines[] = sprintf( '- [%s](%s): %s', $term->name, get_term_link( $term ), wp_strip_all_tags( $term->description ) );
		}
		$lines[] = '';
	}

	foreach ( array( PT_GUIDE, PT_QUIZ ) as $type ) {
		$object = get_post_type_object( $type );
		$posts  = get_posts(
			array(
				'post_type'      => $type,
				'posts_per_page' => 30,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);
		if ( ! $posts ) {
			continue;
		}
		$lines[] = '## ' . $object->labels->name;
		foreach ( $posts as $post ) {
			$lines[] = sprintf( '- [%s](%s)', wp_strip_all_tags( get_the_title( $post ) ), get_permalink( $post ) );
		}
		$lines[] = '';
	}

	return implode( "\n", $lines );
}

/**
 * Sert /llms.txt.
 */
function serve() {
	if ( ! get_query_var( 'ld_llms' ) ) {
		return;
	}
	if ( ! setting( 'llms_txt' ) ) {
		status_header( 404 );
		exit;
	}
	status_header( 200 );
	header( 'Content-Type: text/plain; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex' );
	echo build(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- texte brut.
	exit;
}
add_action( 'template_redirect', __NAMESPACE__ . '\\serve', 0 );
