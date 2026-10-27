<?php
/**
 * Fonctions utilitaires et réglages.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core;

defined( 'ABSPATH' ) || exit;

const OPTION = 'learn_drive_core';

const PT_COURSE   = 'ld_course';
const PT_LESSON   = 'ld_lesson';
const PT_QUESTION = 'ld_question';
const PT_QUIZ     = 'ld_quiz';
const PT_SHEET    = 'ld_sheet';
const PT_GUIDE    = 'ld_guide';
const PT_GLOSSARY = 'ld_glossary';

const TAX_COUNTRY = 'ld_country';
const TAX_TRACK   = 'ld_track';
const TAX_TOPIC   = 'ld_topic';
const TAX_EXAM    = 'ld_exam_type';
const TAX_LEVEL   = 'ld_level';
const TAX_SUBJECT = 'ld_subject';
const TAX_FAMILY  = 'ld_sheet_family';

/**
 * Valeurs par défaut des réglages.
 *
 * @return array
 */
function defaults() {
	return array(
		// Accès.
		'access_mode'      => 'mixed',
		'mixed_default'    => 'free',
		'mixed_exam'       => 'free',
		'premium_url'      => '/premium/',
		'account_url'      => '/mon-compte/',
		'default_country'  => '',
		// Libellés renommables (le thème sert aussi hors permis).
		'label_track'      => __( 'Catégorie de permis', 'learn-drive-core' ),
		'label_track_pl'   => __( 'Catégories de permis', 'learn-drive-core' ),
		'label_topic'      => __( 'Thème officiel', 'learn-drive-core' ),
		'label_topic_pl'   => __( 'Thèmes officiels', 'learn-drive-core' ),
		'label_sheet'      => __( 'Panneau', 'learn-drive-core' ),
		'label_sheet_pl'   => __( 'Panneaux', 'learn-drive-core' ),
		// Slugs d'URL (traduisibles par langue).
		'slug_lesson'      => 'cours',
		'slug_course'      => 'formation',
		'slug_quiz'        => 'quiz',
		'slug_question'    => 'questions',
		'slug_exam'        => 'examen-blanc',
		'slug_sheet'       => 'panneaux',
		'slug_guide'       => 'infos',
		'slug_glossary'    => 'lexique',
		'slug_track'       => 'categorie',
		'slug_topic'       => 'theme',
		'slug_family'      => 'famille',
		'slug_country'     => 'pays',
		// SEO.
		'seo_titles'       => 1,
		'title_sep'        => '|',
		'tpl_question'     => '{title} – Quiz {track} {country} | {site}',
		'tpl_quiz'         => '{title} – {track} | {site}',
		'tpl_lesson'       => '{title} – Cours {track} | {site}',
		'tpl_sheet'        => '{title} : signification et règles | {site}',
		'tpl_guide'        => '{title} | {site}',
		'tpl_post'         => '{title} | {site}',
		'tpl_glossary'     => '{title} : définition | {site}',
		'tpl_track'        => '{title} : cours, quiz et examen blanc | {site}',
		'org_type'         => 'EducationalOrganization',
		'org_name'         => '',
		'org_logo'         => 0,
		'org_same_as'      => '',
		'news_name'        => '',
		'thin_words'       => 40,
		'legacy_schema'    => 0,
		'indexnow'         => 1,
		'llms_txt'         => 1,
		'ai_block'         => array(),
		'glossary_links'   => 1,
		'glossary_max'     => 8,
		'og_images'        => 1,
		'log_404'          => 1,
		// Performance & médias.
		'image_format'     => 'webp',
		'image_quality'    => 75,
		'trim_image_sizes' => 1,
		'rename_uploads'   => 1,
		'video_facade'     => 1,
		'disable_emojis'   => 1,
		'local_avatars'    => 1,
		'cdn_url'          => '',
		// API & intégrations.
		'api_keys'         => '',
		'rate_limit'       => 120,
		'webhook_url'      => '',
		'webhook_secret'   => '',
	);
}

/**
 * Tous les réglages, fusionnés avec les valeurs par défaut.
 *
 * @return array
 */
function settings() {
	static $cache = null;

	if ( null === $cache || doing_action( 'update_option_' . OPTION ) ) {
		$saved = get_option( OPTION, array() );
		$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), defaults() );
	}

	return $cache;
}

/**
 * Un réglage.
 *
 * @param string $key Clé.
 * @return mixed
 */
function setting( $key ) {
	$all = settings();
	return $all[ $key ] ?? null;
}

/**
 * Types de contenus d'apprentissage.
 *
 * @return string[]
 */
function learning_types() {
	return array( PT_COURSE, PT_LESSON, PT_QUESTION, PT_QUIZ, PT_SHEET, PT_GUIDE, PT_GLOSSARY );
}

/**
 * Types de contenus gérés par le plugin, plus les articles (actualités).
 *
 * @return string[]
 */
function content_types() {
	return array_merge( array( 'post' ), learning_types() );
}

/**
 * Terme principal d'un contenu pour une taxonomie.
 *
 * La méta « _ld_primary_{taxonomie} » permet de choisir le terme principal
 * quand un contenu appartient à plusieurs catégories (ex. permis A et B).
 *
 * @param int    $post_id  Contenu.
 * @param string $taxonomy Taxonomie.
 * @return \WP_Term|null
 */
function primary_term( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );

	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}

	$primary = (int) get_post_meta( $post_id, '_ld_primary_' . $taxonomy, true );
	if ( $primary ) {
		foreach ( $terms as $term ) {
			if ( $term->term_id === $primary ) {
				return $term;
			}
		}
	}

	usort(
		$terms,
		static function ( $a, $b ) {
			return $a->term_id <=> $b->term_id;
		}
	);

	return $terms[0];
}

/**
 * Nombre de mots d'un contenu HTML.
 *
 * @param string $html Contenu.
 * @return int
 */
function word_count( $html ) {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $html ) ) ) );
	if ( '' === $text ) {
		return 0;
	}
	return count( preg_split( '/[\s\x{2019}\']+/u', $text, -1, PREG_SPLIT_NO_EMPTY ) );
}

/**
 * Indique si un plugin SEO complet gère déjà les balises meta.
 *
 * @return bool
 */
function seo_plugin_active() {
	$active = defined( 'WPSEO_VERSION' )
		|| class_exists( '\RankMath' )
		|| defined( 'SEOPRESS_VERSION' )
		|| defined( 'AIOSEO_VERSION' )
		|| defined( 'THE_SEO_FRAMEWORK_VERSION' );

	/**
	 * Permet de forcer la détection d'un plugin SEO tiers.
	 *
	 * @param bool $active Plugin SEO détecté.
	 */
	return (bool) apply_filters( 'learn_drive_seo_plugin_active', $active );
}

/**
 * URL absolue à partir d'un réglage pouvant être relatif (« /premium/ »).
 *
 * @param string $key Clé du réglage.
 * @return string
 */
function setting_url( $key ) {
	$value = (string) setting( $key );
	if ( '' === $value ) {
		return home_url( '/' );
	}
	return str_starts_with( $value, 'http' ) ? $value : home_url( $value );
}

/**
 * Libellé d'un format de question.
 *
 * @param string $format Format.
 * @return string
 */
function format_label( $format ) {
	$labels = array(
		'tf'        => __( 'Vrai / faux', 'learn-drive-core' ),
		'single'    => __( 'Choix unique', 'learn-drive-core' ),
		'multiple'  => __( 'Choix multiple', 'learn-drive-core' ),
		'numeric'   => __( 'Réponse numérique', 'learn-drive-core' ),
		'flashcard' => __( 'Carte mémoire', 'learn-drive-core' ),
	);
	return $labels[ $format ] ?? $format;
}

/**
 * Format de durée lisible (« 20 min », « 45 s »).
 *
 * @param int $seconds Secondes.
 * @return string
 */
function human_duration( $seconds ) {
	$seconds = (int) $seconds;
	if ( $seconds >= 60 ) {
		/* translators: %d: minutes */
		return sprintf( _n( '%d minute', '%d minutes', (int) round( $seconds / 60 ), 'learn-drive-core' ), (int) round( $seconds / 60 ) );
	}
	/* translators: %d: seconds */
	return sprintf( _n( '%d seconde', '%d secondes', $seconds, 'learn-drive-core' ), $seconds );
}

/**
 * Adresse IP du visiteur, pseudonymisée (pour la limitation de débit).
 *
 * @return string
 */
function client_fingerprint() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	return substr( hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ), 0, 20 );
}
