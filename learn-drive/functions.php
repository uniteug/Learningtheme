<?php
/**
 * Learn & Drive — fonctions du thème.
 *
 * Le thème ne gère que l'affichage. Les contenus d'apprentissage, le moteur de
 * quiz et le SEO avancé sont portés par le plugin compagnon « Learn & Drive Core ».
 *
 * @package LearnDrive
 */

defined( 'ABSPATH' ) || exit;

define( 'LEARN_DRIVE_VERSION', wp_get_theme( get_template() )->get( 'Version' ) );

require_once get_template_directory() . '/inc/block-styles.php';
require_once get_template_directory() . '/inc/performance.php';
require_once get_template_directory() . '/inc/dark-mode.php';
require_once get_template_directory() . '/inc/presentation.php';

if ( ! function_exists( 'learn_drive_setup' ) ) :
	/**
	 * Déclare les fonctionnalités du thème.
	 */
	function learn_drive_setup() {
		load_theme_textdomain( 'learn-drive', get_template_directory() . '/languages' );

		add_theme_support( 'editor-styles' );
		add_editor_style( array( 'assets/css/theme.css', 'assets/css/editor.css' ) );

		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'html5', array( 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'search-form' ) );

		/*
		 * Tailles d'images calées sur les gabarits réels du thème : 400, 800, 1200, 1600
		 * de large, plus 1200 × 675 (16:9) pour Open Graph et Google Discover.
		 */
		add_image_size( 'learn-drive-400', 400, 0 );
		add_image_size( 'learn-drive-800', 800, 0 );
		add_image_size( 'learn-drive-1200', 1200, 0 );
		add_image_size( 'learn-drive-1600', 1600, 0 );
		add_image_size( 'learn-drive-16x9', 1200, 675, true );
		add_image_size( 'learn-drive-4x3', 1200, 900, true );
		add_image_size( 'learn-drive-1x1', 1200, 1200, true );
	}
endif;
add_action( 'after_setup_theme', 'learn_drive_setup' );

/**
 * Suffixe des fichiers CSS : versions minifiées hors mode débogage.
 *
 * @return string
 */
function learn_drive_css_suffix() {
	return ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.css' : '.min.css';
}

/**
 * Feuille de style globale du thème.
 *
 * Le fichier est petit : WordPress l'insère en ligne (CSS critique) grâce à la
 * donnée « path », sans requête supplémentaire.
 */
function learn_drive_enqueue_styles() {
	wp_register_style(
		'learn-drive',
		get_template_directory_uri() . '/assets/css/theme' . learn_drive_css_suffix(),
		array(),
		LEARN_DRIVE_VERSION
	);
	wp_style_add_data( 'learn-drive', 'path', get_template_directory() . '/assets/css/theme' . learn_drive_css_suffix() );
	wp_enqueue_style( 'learn-drive' );
}
add_action( 'wp_enqueue_scripts', 'learn_drive_enqueue_styles' );

/**
 * Styles chargés bloc par bloc : seulement si le bloc est présent dans la page.
 */
function learn_drive_enqueue_block_styles() {
	$blocks = array(
		'core/navigation',
		'core/post-template',
		'core/query-pagination',
		'core/search',
		'core/details',
		'core/table',
		'core/post-terms',
		'core/post-featured-image',
		'core/terms-query',
		'core/accordion',
		'core/comments',
		'core/list',
		'core/breadcrumbs',
		'core/pullquote',
	);

	foreach ( $blocks as $block_name ) {
		$slug = str_replace( 'core/', '', $block_name );
		$file = $slug . learn_drive_css_suffix();
		$path = get_template_directory() . "/assets/css/blocks/{$file}";

		if ( ! file_exists( $path ) ) {
			continue;
		}

		wp_enqueue_block_style(
			$block_name,
			array(
				'handle' => "learn-drive-{$slug}",
				'src'    => get_template_directory_uri() . "/assets/css/blocks/{$file}",
				'path'   => $path,
				'ver'    => LEARN_DRIVE_VERSION,
			)
		);
	}
}
add_action( 'init', 'learn_drive_enqueue_block_styles' );

/**
 * Catégories de compositions (patterns).
 */
function learn_drive_register_pattern_categories() {
	register_block_pattern_category(
		'learn-drive-home',
		array(
			'label'       => __( 'Learn & Drive — Accueil', 'learn-drive' ),
			'description' => __( "Sections de la page d'accueil.", 'learn-drive' ),
		)
	);
	register_block_pattern_category(
		'learn-drive-learning',
		array(
			'label'       => __( 'Learn & Drive — Apprentissage', 'learn-drive' ),
			'description' => __( 'Blocs pour les cours, leçons et quiz.', 'learn-drive' ),
		)
	);
	register_block_pattern_category(
		'learn-drive-editorial',
		array(
			'label'       => __( 'Learn & Drive — Éditorial', 'learn-drive' ),
			'description' => __( 'Blocs pour les actualités et les guides pratiques.', 'learn-drive' ),
		)
	);
	register_block_pattern_category(
		'learn-drive-marketing',
		array(
			'label'       => __( 'Learn & Drive — Offre', 'learn-drive' ),
			'description' => __( 'Tarifs, témoignages, application, appel à l’action.', 'learn-drive' ),
		)
	);
}
add_action( 'init', 'learn_drive_register_pattern_categories' );

/**
 * Indique si le plugin compagnon est actif.
 *
 * @return bool
 */
function learn_drive_has_core_plugin() {
	return defined( 'LEARN_DRIVE_CORE_VERSION' );
}
