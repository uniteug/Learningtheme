<?php
/**
 * Types de contenus et taxonomies.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Post_Types;

use function LearnDrive\Core\setting;
use const LearnDrive\Core\PT_COURSE;
use const LearnDrive\Core\PT_LESSON;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\PT_QUIZ;
use const LearnDrive\Core\PT_SHEET;
use const LearnDrive\Core\PT_GUIDE;
use const LearnDrive\Core\PT_GLOSSARY;
use const LearnDrive\Core\TAX_COUNTRY;
use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\TAX_TOPIC;
use const LearnDrive\Core\TAX_EXAM;
use const LearnDrive\Core\TAX_LEVEL;
use const LearnDrive\Core\TAX_SUBJECT;
use const LearnDrive\Core\TAX_FAMILY;

defined( 'ABSPATH' ) || exit;

/**
 * Libellés standard d'un type de contenu.
 *
 * @param string $singular Nom au singulier.
 * @param string $plural   Nom au pluriel.
 * @param bool   $feminine Accord au féminin.
 * @return array
 */
function labels( $singular, $plural, $feminine = false ) {
	return array(
		'name'                  => $plural,
		'singular_name'         => $singular,
		'menu_name'             => $plural,
		'all_items'             => $plural,
		/* translators: %s: content type name */
		'add_new_item'          => sprintf( $feminine ? __( 'Ajouter une %s', 'learn-drive-core' ) : __( 'Ajouter un %s', 'learn-drive-core' ), mb_strtolower( $singular ) ),
		'add_new'               => __( 'Ajouter', 'learn-drive-core' ),
		/* translators: %s: content type name */
		'edit_item'             => sprintf( __( 'Modifier : %s', 'learn-drive-core' ), mb_strtolower( $singular ) ),
		/* translators: %s: content type name */
		'view_item'             => sprintf( __( 'Voir : %s', 'learn-drive-core' ), mb_strtolower( $singular ) ),
		/* translators: %s: content type name, plural */
		'search_items'          => sprintf( __( 'Rechercher : %s', 'learn-drive-core' ), mb_strtolower( $plural ) ),
		'not_found'             => __( 'Aucun élément trouvé.', 'learn-drive-core' ),
		'not_found_in_trash'    => __( 'Aucun élément dans la corbeille.', 'learn-drive-core' ),
		'archives'              => $plural,
		'items_list'            => $plural,
		'featured_image'        => __( 'Image principale', 'learn-drive-core' ),
		'set_featured_image'    => __( 'Définir l’image principale', 'learn-drive-core' ),
		'remove_featured_image' => __( 'Retirer l’image principale', 'learn-drive-core' ),
		'use_featured_image'    => __( 'Utiliser comme image principale', 'learn-drive-core' ),
	);
}

/**
 * Enregistre les types de contenus et taxonomies.
 */
function register() {
	$common = array(
		'public'            => true,
		'show_in_rest'      => true,
		'menu_position'     => 21,
		'supports'          => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'custom-fields', 'page-attributes' ),
		'show_in_nav_menus' => true,
		'delete_with_user'  => false,
	);

	register_post_type(
		PT_COURSE,
		array_merge(
			$common,
			array(
				'labels'      => labels( __( 'Cours', 'learn-drive-core' ), __( 'Cours', 'learn-drive-core' ) ),
				'description' => __( 'Parcours complet par catégorie ou matière.', 'learn-drive-core' ),
				'menu_icon'   => 'dashicons-welcome-learn-more',
				'has_archive' => setting( 'slug_course' ),
				'rewrite'     => array(
					'slug'       => setting( 'slug_course' ),
					'with_front' => false,
				),
			)
		)
	);

	register_post_type(
		PT_LESSON,
		array_merge(
			$common,
			array(
				'labels'       => labels( __( 'Leçon', 'learn-drive-core' ), __( 'Leçons', 'learn-drive-core' ), true ),
				'description'  => __( 'Unité de cours, théorique ou pratique.', 'learn-drive-core' ),
				'menu_icon'    => 'dashicons-book-alt',
				'has_archive'  => setting( 'slug_lesson' ),
				'rewrite'      => array(
					'slug'       => setting( 'slug_lesson' ),
					'with_front' => false,
				),
				'show_in_menu' => 'edit.php?post_type=' . PT_COURSE,
			)
		)
	);

	register_post_type(
		PT_QUESTION,
		array_merge(
			$common,
			array(
				'labels'      => labels( __( 'Question', 'learn-drive-core' ), __( 'Questions', 'learn-drive-core' ), true ),
				'description' => __( 'Question avec propositions, correction et explication.', 'learn-drive-core' ),
				'menu_icon'   => 'dashicons-editor-help',
				'has_archive' => setting( 'slug_question' ),
				'rewrite'     => array(
					'slug'       => setting( 'slug_question' ),
					'with_front' => false,
				),
				'supports'    => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'custom-fields', 'page-attributes', 'comments' ),
			)
		)
	);

	register_post_type(
		PT_QUIZ,
		array_merge(
			$common,
			array(
				'labels'      => labels( __( 'Quiz', 'learn-drive-core' ), __( 'Quiz', 'learn-drive-core' ) ),
				'description' => __( 'Série de questions : thématique, entraînement ou examen blanc.', 'learn-drive-core' ),
				'menu_icon'   => 'dashicons-forms',
				'has_archive' => setting( 'slug_quiz' ),
				'rewrite'     => array(
					'slug'       => setting( 'slug_quiz' ),
					'with_front' => false,
				),
			)
		)
	);

	register_post_type(
		PT_SHEET,
		array_merge(
			$common,
			array(
				'labels'      => labels( setting( 'label_sheet' ), setting( 'label_sheet_pl' ) ),
				'description' => __( 'Fiche : panneau, manœuvre, notion, formule.', 'learn-drive-core' ),
				'menu_icon'   => 'dashicons-warning',
				'has_archive' => setting( 'slug_sheet' ),
				'rewrite'     => array(
					'slug'       => setting( 'slug_sheet' ),
					'with_front' => false,
				),
			)
		)
	);

	register_post_type(
		PT_GUIDE,
		array_merge(
			$common,
			array(
				'labels'      => labels( __( 'Info & démarche', 'learn-drive-core' ), __( 'Infos & démarches', 'learn-drive-core' ), true ),
				'description' => __( 'Contenu pratique mis à jour : coûts, documents, délais.', 'learn-drive-core' ),
				'menu_icon'   => 'dashicons-clipboard',
				'has_archive' => setting( 'slug_guide' ),
				'rewrite'     => array(
					'slug'       => setting( 'slug_guide' ),
					'with_front' => false,
				),
			)
		)
	);

	register_post_type(
		PT_GLOSSARY,
		array_merge(
			$common,
			array(
				'labels'      => labels( __( 'Définition', 'learn-drive-core' ), __( 'Lexique', 'learn-drive-core' ), true ),
				'description' => __( 'Définitions courtes, liées automatiquement dans les textes.', 'learn-drive-core' ),
				'menu_icon'   => 'dashicons-editor-spellcheck',
				'has_archive' => setting( 'slug_glossary' ),
				'rewrite'     => array(
					'slug'       => setting( 'slug_glossary' ),
					'with_front' => false,
				),
			)
		)
	);

	$learning = array( PT_COURSE, PT_LESSON, PT_QUESTION, PT_QUIZ, PT_SHEET, PT_GUIDE, 'post' );

	register_taxonomy(
		TAX_COUNTRY,
		$learning,
		array(
			'labels'            => taxonomy_labels( __( 'Pays', 'learn-drive-core' ), __( 'Pays', 'learn-drive-core' ) ),
			'description'       => __( 'Pays ou juridiction : règles d’examen, format des questions.', 'learn-drive-core' ),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'       => setting( 'slug_country' ),
				'with_front' => false,
			),
		)
	);

	register_taxonomy(
		TAX_TRACK,
		$learning,
		array(
			'labels'            => taxonomy_labels( setting( 'label_track' ), setting( 'label_track_pl' ) ),
			'description'       => __( 'Structure les silos : chaque terme a sa page pilier.', 'learn-drive-core' ),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'       => setting( 'slug_track' ),
				'with_front' => false,
			),
		)
	);

	register_taxonomy(
		TAX_TOPIC,
		$learning,
		array(
			'labels'            => taxonomy_labels( setting( 'label_topic' ), setting( 'label_topic_pl' ) ),
			'description'       => __( 'Thèmes du programme officiel de chaque pays.', 'learn-drive-core' ),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'       => setting( 'slug_topic' ),
				'with_front' => false,
			),
		)
	);

	register_taxonomy(
		TAX_EXAM,
		array( PT_COURSE, PT_LESSON, PT_QUESTION, PT_QUIZ ),
		array(
			'labels'            => taxonomy_labels( __( 'Type d’épreuve', 'learn-drive-core' ), __( 'Types d’épreuve', 'learn-drive-core' ) ),
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => false,
		)
	);

	register_taxonomy(
		TAX_LEVEL,
		array( PT_COURSE, PT_LESSON, PT_QUESTION, PT_QUIZ ),
		array(
			'labels'            => taxonomy_labels( __( 'Niveau', 'learn-drive-core' ), __( 'Niveaux', 'learn-drive-core' ) ),
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => false,
		)
	);

	register_taxonomy(
		TAX_SUBJECT,
		$learning,
		array(
			'labels'            => taxonomy_labels( __( 'Matière', 'learn-drive-core' ), __( 'Matières', 'learn-drive-core' ) ),
			'description'       => __( 'Rend le site utilisable hors permis (physique, langues…).', 'learn-drive-core' ),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => false,
			'rewrite'           => array(
				'slug'       => 'matiere',
				'with_front' => false,
			),
		)
	);

	register_taxonomy(
		TAX_FAMILY,
		array( PT_SHEET ),
		array(
			'labels'            => taxonomy_labels( __( 'Famille', 'learn-drive-core' ), __( 'Familles', 'learn-drive-core' ) ),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'       => setting( 'slug_sheet' ) . '/' . setting( 'slug_family' ),
				'with_front' => false,
			),
		)
	);

	register_term_meta_fields();
}
add_action( 'init', __NAMESPACE__ . '\\register', 5 );

/**
 * Libellés de taxonomie.
 *
 * @param string $singular Singulier.
 * @param string $plural   Pluriel.
 * @return array
 */
function taxonomy_labels( $singular, $plural ) {
	return array(
		'name'          => $plural,
		'singular_name' => $singular,
		'menu_name'     => $plural,
		'all_items'     => $plural,
		/* translators: %s: taxonomy name */
		'edit_item'     => sprintf( __( 'Modifier : %s', 'learn-drive-core' ), $singular ),
		/* translators: %s: taxonomy name */
		'add_new_item'  => sprintf( __( 'Ajouter : %s', 'learn-drive-core' ), $singular ),
		/* translators: %s: taxonomy name */
		'search_items'  => sprintf( __( 'Rechercher : %s', 'learn-drive-core' ), $plural ),
		'not_found'     => __( 'Aucun terme trouvé.', 'learn-drive-core' ),
		'back_to_items' => __( '← Retour', 'learn-drive-core' ),
	);
}

/**
 * Métadonnées de termes exposées à l'API REST.
 */
function register_term_meta_fields() {
	$string = array(
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_text_field',
	);

	register_term_meta( TAX_TRACK, 'ld_color', array_merge( $string, array( 'sanitize_callback' => 'sanitize_hex_color' ) ) );
	register_term_meta( TAX_TRACK, 'ld_access', $string );
	register_term_meta( TAX_TRACK, 'ld_min_age', $string );
	register_term_meta( TAX_TOPIC, 'ld_color', array_merge( $string, array( 'sanitize_callback' => 'sanitize_hex_color' ) ) );
	register_term_meta( TAX_TOPIC, 'ld_country', $string );
	register_term_meta( TAX_TRACK, 'ld_country', $string );

	register_term_meta(
		TAX_TRACK,
		'ld_content',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'wp_kses_post',
		)
	);

	foreach ( array( TAX_COUNTRY, TAX_TRACK ) as $taxonomy ) {
		register_term_meta(
			$taxonomy,
			'ld_rules',
			array(
				'type'              => 'object',
				'single'            => true,
				'sanitize_callback' => 'LearnDrive\\Core\\Quizzes\\sanitize_rules',
				'show_in_rest'      => array(
					'schema' => array(
						'type'                 => 'object',
						'additionalProperties' => true,
					),
				),
			)
		);
	}
}

/**
 * Couleurs par catégorie : reprises sur les cartes, badges et images Open Graph.
 */
function print_term_colors() {
	$terms = get_terms(
		array(
			'taxonomy'   => array( TAX_TRACK, TAX_TOPIC ),
			'hide_empty' => false,
			'meta_key'   => 'ld_color', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- petite table de termes.
			'number'     => 200,
		)
	);

	if ( is_wp_error( $terms ) || ! $terms ) {
		return;
	}

	$css = '';
	foreach ( $terms as $term ) {
		$color = sanitize_hex_color( get_term_meta( $term->term_id, 'ld_color', true ) );
		if ( $color ) {
			$css .= sprintf( '.term-%d{--ld-term-color:%s}', (int) $term->term_id, $color );
		}
	}

	if ( $css ) {
		wp_add_inline_style( 'learn-drive-core-term-colors', $css );
		wp_enqueue_style( 'learn-drive-core-term-colors' );
	}
}

/**
 * Feuille vide servant de support aux couleurs de catégories.
 */
function register_term_color_style() {
	wp_register_style( 'learn-drive-core-term-colors', false, array(), \LearnDrive\Core\VERSION );
	print_term_colors();
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\register_term_color_style' );

/**
 * Trie les archives : lexique et fiches par ordre alphabétique, cours et leçons par ordre défini.
 *
 * @param \WP_Query $query Requête principale.
 */
function archive_order( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_post_type_archive( array( PT_GLOSSARY, PT_SHEET ) ) || $query->is_tax( TAX_FAMILY ) ) {
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );
		$query->set( 'posts_per_page', $query->is_post_type_archive( PT_GLOSSARY ) ? 60 : 36 );
	} elseif ( $query->is_post_type_archive( array( PT_LESSON, PT_COURSE, PT_QUIZ, PT_GUIDE ) ) ) {
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			)
		);
	} elseif ( $query->is_tax( array( TAX_TRACK, TAX_TOPIC ) ) ) {
		// Les pages piliers composent leurs sections avec le bloc « Contenus liés ».
		$query->set( 'posts_per_page', 1 );
	}
}
add_action( 'pre_get_posts', __NAMESPACE__ . '\\archive_order' );

/**
 * Sur la page d'accueil, les grilles de catégories et de thèmes n'affichent
 * que ceux du pays principal du site (réglage « Pays principal »).
 *
 * @param array $args       Arguments de get_terms().
 * @param array $taxonomies Taxonomies.
 * @return array
 */
function front_country_terms( $args, $taxonomies ) {
	$country = (string) setting( 'default_country' );
	if ( '' === $country || is_admin() || wp_doing_ajax() || ! did_action( 'wp' ) || ! is_front_page() ) {
		return $args;
	}
	if ( ! array_intersect( (array) $taxonomies, array( TAX_TRACK, TAX_TOPIC ) ) || ! empty( $args['include'] ) || ! empty( $args['meta_query'] ) ) {
		return $args;
	}
	$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		array(
			'key'   => 'ld_country',
			'value' => $country,
		),
	);
	return $args;
}
add_filter( 'get_terms_args', __NAMESPACE__ . '\\front_country_terms', 10, 2 );
