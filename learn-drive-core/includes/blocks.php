<?php
/**
 * Blocs dynamiques (rendu PHP) et ressources partagées.
 *
 * Les scripts ne sont chargés que sur les pages qui contiennent le bloc
 * concerné : un article sans quiz n'exécute aucun JavaScript du plugin.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Blocks;

use LearnDrive\Core\Questions;
use LearnDrive\Core\Quizzes;
use function LearnDrive\Core\primary_term;
use const LearnDrive\Core\VERSION;
use const LearnDrive\Core\PT_COURSE;
use const LearnDrive\Core\PT_LESSON;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\PT_QUIZ;
use const LearnDrive\Core\PT_SHEET;
use const LearnDrive\Core\PT_GUIDE;
use const LearnDrive\Core\PT_GLOSSARY;
use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\TAX_TOPIC;
use const LearnDrive\Core\TAX_FAMILY;

defined( 'ABSPATH' ) || exit;

/**
 * Ressources partagées entre blocs.
 */
function register_assets() {
	$url = LEARN_DRIVE_CORE_URL . 'assets/';
	$dir = LEARN_DRIVE_CORE_DIR . 'assets/';

	$debug  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	$js     = $debug || ! file_exists( $dir . 'js/quiz.min.js' ) ? '.js' : '.min.js';
	$suffix = $debug || ! file_exists( $dir . 'css/quiz.min.css' ) ? '.css' : '.min.css';

	wp_register_script_module( 'learn-drive-quiz', $url . 'js/quiz' . $js, array( '@wordpress/interactivity' ), VERSION );
	wp_register_script_module( 'learn-drive-account', $url . 'js/account' . $js, array( '@wordpress/interactivity' ), VERSION );
	wp_register_script_module( 'learn-drive-video', $url . 'js/video' . $js, array( '@wordpress/interactivity' ), VERSION );

	foreach ( array( 'quiz', 'blocks', 'account' ) as $name ) {
		wp_register_style( 'learn-drive-core-' . $name, $url . 'css/' . $name . $suffix, array(), VERSION );
		wp_style_add_data( 'learn-drive-core-' . $name, 'path', $dir . 'css/' . $name . $suffix );
	}

	wp_register_script(
		'learn-drive-core-editor',
		$url . 'js/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n' ),
		VERSION,
		true
	);
	wp_set_script_translations( 'learn-drive-core-editor', 'learn-drive-core', LEARN_DRIVE_CORE_DIR . 'languages' );
}
add_action( 'init', __NAMESPACE__ . '\\register_assets', 8 );

/**
 * Enregistre les blocs depuis leurs block.json.
 */
function register_blocks() {
	foreach ( glob( LEARN_DRIVE_CORE_DIR . 'blocks/*/block.json' ) as $file ) {
		register_block_type( dirname( $file ) );
	}
}
add_action( 'init', __NAMESPACE__ . '\\register_blocks', 9 );

/**
 * Catégorie « Learn & Drive » dans l'inserteur.
 *
 * @param array $categories Catégories.
 * @return array
 */
function block_category( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'learn-drive',
			'title' => __( 'Learn & Drive', 'learn-drive-core' ),
			'icon'  => null,
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', __NAMESPACE__ . '\\block_category' );

/**
 * Contenu courant d'un bloc (contexte de boucle ou page affichée).
 *
 * @param \WP_Block|null $block Bloc.
 * @return int
 */
function current_post_id( $block = null ) {
	if ( $block && ! empty( $block->context['postId'] ) ) {
		return (int) $block->context['postId'];
	}
	return is_singular() ? (int) get_queried_object_id() : 0;
}

/**
 * Titre par défaut d'une variante du bloc « Contenus liés ».
 *
 * @param string $variant   Variante.
 * @param string $post_type Type de contenu.
 * @return string
 */
function variant_heading( $variant, $post_type ) {
	$headings = array(
		'revise'          => __( 'À réviser', 'learn-drive-core' ),
		'same-topic'      => __( 'Sur le même sujet', 'learn-drive-core' ),
		'news'            => __( 'Actualités liées', 'learn-drive-core' ),
		'quizzes'         => __( 'Quiz du thème', 'learn-drive-core' ),
		'track-quizzes'   => __( 'Quiz par thème', 'learn-drive-core' ),
		'topic-questions' => __( 'Questions de ce thème', 'learn-drive-core' ),
		'more-quizzes'    => __( 'Autres quiz', 'learn-drive-core' ),
		'courses'         => __( 'Parcours de formation', 'learn-drive-core' ),
		'lessons'         => __( 'Cours et leçons', 'learn-drive-core' ),
		'lessons-topic'   => __( 'Leçons de ce thème', 'learn-drive-core' ),
		'questions'       => __( 'Questions proches', 'learn-drive-core' ),
		'sheet-questions' => __( 'Questions sur ce panneau', 'learn-drive-core' ),
		'sheets'          => __( 'Fiches liées', 'learn-drive-core' ),
		'same-family'     => __( 'Dans la même famille', 'learn-drive-core' ),
		'guides'          => __( 'Infos & démarches', 'learn-drive-core' ),
		'glossary'        => __( 'Voir aussi', 'learn-drive-core' ),
		'lesson'          => __( 'Leçon à revoir', 'learn-drive-core' ),
	);

	if ( isset( $headings[ $variant ] ) ) {
		return $headings[ $variant ];
	}

	$object = get_post_type_object( $post_type );
	return $object ? $object->labels->name : '';
}

/**
 * Termes communs servant au maillage : thème officiel, sinon catégorie.
 *
 * @param int $post_id Contenu.
 * @return array tax_query.
 */
function shared_terms_query( $post_id ) {
	foreach ( array( TAX_TOPIC, TAX_TRACK ) as $taxonomy ) {
		$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
		if ( $terms && ! is_wp_error( $terms ) ) {
			return array(
				array(
					'taxonomy' => $taxonomy,
					'terms'    => $terms,
				),
			);
		}
	}

	if ( 'post' === get_post_type( $post_id ) ) {
		$categories = wp_get_post_categories( $post_id );
		if ( $categories ) {
			return array(
				array(
					'taxonomy' => 'category',
					'terms'    => $categories,
				),
			);
		}
	}

	return array();
}

/**
 * Identifiants des contenus liés.
 *
 * @param string         $post_type Type recherché.
 * @param string         $variant   Variante.
 * @param int            $count     Nombre.
 * @param \WP_Block|null $block     Bloc.
 * @return int[]
 */
function related_ids( $post_type, $variant, $count, $block = null ) {
	$post_id = current_post_id( $block );
	$args    = array(
		'post_type'           => $post_type,
		'post_status'         => 'publish',
		'posts_per_page'      => $count,
		'fields'              => 'ids',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	);

	if ( 'post' === $post_type ) {
		$args['orderby'] = 'date';
		$args['order']   = 'DESC';
	} elseif ( in_array( $post_type, array( PT_SHEET, PT_GLOSSARY ), true ) ) {
		$args['orderby'] = 'title';
		$args['order']   = 'ASC';
	} else {
		$args['orderby'] = array(
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		);
	}

	// Page pilier (catégorie ou thème) : tous les contenus du terme. Le contexte postId
	// d'une archive vient du premier contenu de la boucle principale : on l'ignore.
	if ( ( is_tax( TAX_TRACK ) || is_tax( TAX_TOPIC ) ) && ! is_singular() && ! ( $block && isset( $block->context['queryId'] ) ) ) {
		$term              = get_queried_object();
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => $term->taxonomy,
				'terms'    => $term->term_id,
			),
		);
		return get_posts( $args );
	}

	if ( ! $post_id ) {
		return get_posts( $args );
	}

	$current_type = get_post_type( $post_id );

	if ( PT_QUESTION === $current_type && PT_QUESTION === $post_type ) {
		return Questions\related_ids( $post_id, $count );
	}

	if ( PT_QUESTION === $current_type && PT_LESSON === $post_type ) {
		$question = Questions\get( $post_id );
		$lesson   = $question ? Quizzes\lesson_for_question( $question ) : 0;
		if ( $lesson ) {
			return array( $lesson );
		}
	}

	if ( PT_QUESTION === $current_type && PT_SHEET === $post_type ) {
		$sheet = (int) get_post_meta( $post_id, '_ld_sheet', true );
		if ( $sheet && 'publish' === get_post_status( $sheet ) ) {
			return array( $sheet );
		}
		return array();
	}

	if ( PT_SHEET === $current_type && PT_QUESTION === $post_type ) {
		$args['meta_query']   = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'   => '_ld_sheet',
				'value' => $post_id,
				'type'  => 'NUMERIC',
			),
		);
		$args['post__not_in'] = array( $post_id ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
		return get_posts( $args );
	}

	if ( PT_SHEET === $current_type && PT_SHEET === $post_type ) {
		$families = wp_get_post_terms( $post_id, TAX_FAMILY, array( 'fields' => 'ids' ) );
		if ( $families && ! is_wp_error( $families ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => TAX_FAMILY,
					'terms'    => $families,
				),
			);
		}
		$args['post__not_in'] = array( $post_id ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
		return get_posts( $args );
	}

	if ( PT_LESSON === $post_type && 'revise' === $variant ) {
		// Leçon choisie à la main en priorité.
		$manual = (int) get_post_meta( $post_id, '_ld_revise', true );
		if ( $manual && 'publish' === get_post_status( $manual ) ) {
			return array( $manual );
		}
	}

	$tax_query = shared_terms_query( $post_id );
	if ( ! $tax_query && PT_GLOSSARY !== $post_type ) {
		return array();
	}
	if ( $tax_query ) {
		$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}
	$args['post__not_in'] = array( $post_id ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in

	if ( PT_GLOSSARY === $post_type && PT_GLOSSARY === $current_type ) {
		unset( $args['tax_query'] );
		$args['orderby'] = 'rand'; // phpcs:ignore WordPressVIPMinimum.Performance.OrderByRand.orderby_orderby -- petit volume, résultat mis en cache de page.
	}

	return get_posts( $args );
}

/**
 * Rend une liste de contenus.
 *
 * @param int[]  $ids     Contenus.
 * @param string $layout  list|cards|tiles|carousel.
 * @param string $heading Titre.
 * @param string $classes Classes supplémentaires.
 * @return string
 */
function render_list( array $ids, $layout, $heading, $classes = '' ) {
	if ( ! $ids ) {
		return '';
	}

	_prime_post_caches( $ids, true, true );

	$items = '';
	foreach ( $ids as $id ) {
		$title = get_the_title( $id );
		$url   = get_permalink( $id );

		if ( 'list' === $layout ) {
			$meta = '';
			if ( PT_QUIZ === get_post_type( $id ) ) {
				$count = count( Quizzes\question_ids( $id ) );
				/* translators: %d: number of questions */
				$meta = '<span class="ld-related__meta">' . esc_html( sprintf( _n( '%d question', '%d questions', $count, 'learn-drive-core' ), $count ) ) . '</span>';
			} elseif ( 'post' === get_post_type( $id ) ) {
				$meta = '<span class="ld-related__meta">' . esc_html( get_the_date( '', $id ) ) . '</span>';
			}
			$items .= sprintf( '<li><a href="%s">%s</a>%s</li>', esc_url( $url ), esc_html( $title ), $meta );
			continue;
		}

		$badge = '';
		if ( 'carousel' === $layout ) {
			$type = get_post_type( $id );
			if ( PT_QUIZ === $type ) {
				$count = count( Quizzes\question_ids( $id ) );
				/* translators: %d: number of questions */
				$badge = sprintf( _n( '%d question', '%d questions', $count, 'learn-drive-core' ), $count );
			} elseif ( 'post' === $type ) {
				$badge = get_the_date( 'j M Y', $id );
			} else {
				$badge = type_label( $type );
			}
			$badge = '<span class="ld-related__badge">' . esc_html( $badge ) . '</span>';
		}

		$image_size = 'tiles' === $layout ? 'learn-drive-400' : 'learn-drive-800';
		$image      = get_the_post_thumbnail(
			$id,
			$image_size,
			array(
				'loading'  => 'lazy',
				'decoding' => 'async',
				'sizes'    => 'tiles' === $layout ? '160px' : '(max-width: 700px) 100vw, 400px',
				'alt'      => '',
			)
		);

		$excerpt = in_array( $layout, array( 'cards', 'carousel' ), true ) ? '<p class="ld-related__excerpt">' . esc_html( wp_trim_words( get_the_excerpt( $id ), 'carousel' === $layout ? 16 : 20 ) ) . '</p>' : '';

		$items .= sprintf(
			'<li class="ld-related__item"><a class="ld-related__link" href="%1$s">%2$s%5$s<span class="ld-related__title">%3$s</span></a>%4$s</li>',
			esc_url( $url ),
			$image ? '<span class="ld-related__media">' . $image . '</span>' : '',
			esc_html( $title ),
			$excerpt,
			$badge
		);
	}

	$heading_html = $heading ? '<h2 class="ld-related__heading">' . esc_html( $heading ) . '</h2>' : '';

	$list_class = 'carousel' === $layout ? 'ld-related__list ld-carousel' : 'ld-related__list';

	return sprintf(
		'<section %1$s>%2$s<ul class="%4$s">%3$s</ul></section>',
		get_block_wrapper_attributes( array( 'class' => trim( 'ld-related is-' . $layout . ' ' . $classes ) ) ),
		$heading_html,
		$items,
		esc_attr( $list_class )
	);
}

/**
 * Questions à proposer dans un bloc « Testez-vous » lié au contenu courant.
 *
 * @param int $post_id Contenu.
 * @param int $count   Nombre.
 * @return array{ids:int[],quiz:int}
 */
function related_questions( $post_id, $count ) {
	$tax_query = shared_terms_query( $post_id );
	if ( ! $tax_query || 'category' === $tax_query[0]['taxonomy'] ) {
		return array(
			'ids'  => array(),
			'quiz' => 0,
		);
	}

	$ids = get_posts(
		array(
			'post_type'      => PT_QUESTION,
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'ID'         => 'ASC',
			),
			'post__not_in'   => array( $post_id ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
			'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		)
	);

	$quiz = get_posts(
		array(
			'post_type'      => PT_QUIZ,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_ld_mode',
					'value'   => 'exam',
					'compare' => '!=',
				),
			),
		)
	);

	return array(
		'ids'  => $ids,
		'quiz' => $quiz ? (int) $quiz[0] : 0,
	);
}

/**
 * Lien vers une leçon voisine (ordre du cours, sinon de la catégorie).
 *
 * @param int  $post_id  Leçon.
 * @param bool $previous Leçon précédente.
 * @return int
 */
function adjacent_lesson( $post_id, $previous ) {
	$course = (int) get_post_meta( $post_id, '_ld_course', true );
	$order  = (int) get_post_field( 'menu_order', $post_id );

	$args = array(
		'post_type'      => PT_LESSON,
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'post__not_in'   => array( $post_id ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
		'orderby'        => array(
			'menu_order' => $previous ? 'DESC' : 'ASC',
			'ID'         => $previous ? 'DESC' : 'ASC',
		),
	);

	if ( $course ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'   => '_ld_course',
				'value' => $course,
				'type'  => 'NUMERIC',
			),
		);
	} else {
		$track = primary_term( $post_id, TAX_TRACK );
		if ( ! $track ) {
			return 0;
		}
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => TAX_TRACK,
				'terms'    => $track->term_id,
			),
		);
	}

	$filter = static function ( $where ) use ( $order, $previous, $post_id ) {
		global $wpdb;
		$operator = $previous ? '<' : '>';
		return $where . $wpdb->prepare( " AND ( {$wpdb->posts}.menu_order {$operator} %d OR ( {$wpdb->posts}.menu_order = %d AND {$wpdb->posts}.ID {$operator} %d ) )", $order, $order, $post_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- opérateur maîtrisé.
	};

	add_filter( 'posts_where', $filter );
	$args['suppress_filters'] = false;
	$ids                      = get_posts( $args );
	remove_filter( 'posts_where', $filter );

	return $ids ? (int) $ids[0] : 0;
}

/**
 * Leçons d'un cours dans l'ordre.
 *
 * @param int $course_id Cours.
 * @return int[]
 */
function course_lessons( $course_id ) {
	return get_posts(
		array(
			'post_type'      => PT_LESSON,
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'ID'         => 'ASC',
			),
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_ld_course',
					'value' => $course_id,
					'type'  => 'NUMERIC',
				),
			),
		)
	);
}

/**
 * Libellé court d'un type de contenu.
 *
 * @param string $post_type Type.
 * @return string
 */
function type_label( $post_type ) {
	$labels = array(
		'post'      => __( 'Actualité', 'learn-drive-core' ),
		'page'      => __( 'Page', 'learn-drive-core' ),
		PT_COURSE   => __( 'Cours', 'learn-drive-core' ),
		PT_LESSON   => __( 'Leçon', 'learn-drive-core' ),
		PT_QUESTION => __( 'Question', 'learn-drive-core' ),
		PT_QUIZ     => __( 'Quiz', 'learn-drive-core' ),
		PT_SHEET    => \LearnDrive\Core\setting( 'label_sheet' ),
		PT_GUIDE    => __( 'Info & démarche', 'learn-drive-core' ),
		PT_GLOSSARY => __( 'Lexique', 'learn-drive-core' ),
	);
	return $labels[ $post_type ] ?? '';
}

/**
 * Pictogramme d'une catégorie, déduit de son adresse (am → cyclomoteur,
 * a → moto, c → poids lourd, d → autocar, sinon voiture).
 *
 * @param string $slug Adresse de la catégorie.
 * @return string SVG décoratif.
 */
function track_icon( $slug ) {
	$paths = array(
		'scooter' => 'M5 17a2.5 2.5 0 1 0 0 .01M19 17a2.5 2.5 0 1 0 0 .01M7.5 17h7l2-5h-4l-1-4H9M14 6h3l2 6M7.5 17 6 12h4',
		'moto'    => 'M5 17a3 3 0 1 0 0 .01M19 17a3 3 0 1 0 0 .01M5 17l4-6h5l3 6M13 6h3l3 5M9 11l-2-3H4',
		'truck'   => 'M2 6h12v10H2zM14 9h4l4 4v3h-8M6 18.5a1.5 1.5 0 1 0 0 .01M17 18.5a1.5 1.5 0 1 0 0 .01',
		'bus'     => 'M4 4h16v13H4zM4 10h16M8 20v-3M16 20v-3M7 14h.01M17 14h.01',
		'car'     => 'M3 13l2-5a2 2 0 0 1 1.9-1.3h10.2A2 2 0 0 1 19 8l2 5v4H3zM3 13h18M7 17v2M17 17v2M7 13.5h.01M17 13.5h.01',
	);
	$slug  = strtolower( (string) $slug );
	if ( preg_match( '/(^|-)am($|-)/', $slug ) ) {
		$key = 'scooter';
	} elseif ( preg_match( '/(^|-)a[12]?($|-)|moto/', $slug ) ) {
		$key = 'moto';
	} elseif ( preg_match( '/(^|-)c[1e]?($|-)|poids/', $slug ) ) {
		$key = 'truck';
	} elseif ( preg_match( '/(^|-)d[1e]?($|-)|bus|car$/', $slug ) ) {
		$key = 'bus';
	} else {
		$key = 'car';
	}
	return '<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false"><path d="' . esc_attr( $paths[ $key ] ) . '" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/**
 * Résumé d'une catégorie pour les cartes : règles d'examen et nombre de contenus.
 *
 * @param \WP_Term $term Catégorie.
 * @return array{facts:string[],lessons:int,quizzes:int,exam:int}
 */
function track_summary( $term ) {
	$key    = 'ld_track_summary_' . $term->term_id;
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$count = static function ( $post_type ) use ( $term ) {
		$query = new \WP_Query(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => TAX_TRACK,
						'terms'    => $term->term_id,
					),
				),
			)
		);
		return (int) $query->found_posts;
	};

	$exam  = \LearnDrive\Core\Permalinks\exam_for_track( $term );
	$facts = array();
	if ( $exam ) {
		$rules = Quizzes\rules( $exam );
		/* translators: %d: number of questions */
		$facts[] = sprintf( _n( '%d question', '%d questions', $rules['count'], 'learn-drive-core' ), $rules['count'] );
		if ( $rules['duration'] ) {
			/* translators: %d: minutes */
			$facts[] = sprintf( __( '%d min', 'learn-drive-core' ), (int) round( $rules['duration'] / 60 ) );
		} elseif ( $rules['per_question'] ) {
			/* translators: %d: seconds */
			$facts[] = sprintf( __( '%d s / question', 'learn-drive-core' ), $rules['per_question'] );
		}
		if ( 'max_errors' === $rules['pass_type'] ) {
			/* translators: %d: errors */
			$facts[] = sprintf( _n( '%d erreur max', '%d erreurs max', $rules['pass_value'], 'learn-drive-core' ), $rules['pass_value'] );
		} elseif ( 'min_correct' === $rules['pass_type'] ) {
			/* translators: 1: minimum correct answers, 2: total */
			$facts[] = sprintf( __( '%1$d/%2$d pour réussir', 'learn-drive-core' ), $rules['pass_value'], $rules['count'] );
		}
	}
	$age = (string) get_term_meta( $term->term_id, 'ld_min_age', true );
	if ( $age ) {
		/* translators: %s: minimum age */
		array_unshift( $facts, sprintf( __( 'dès %s ans', 'learn-drive-core' ), $age ) );
	}

	$summary = array(
		'facts'   => $facts,
		'lessons' => $count( PT_LESSON ),
		'quizzes' => $count( PT_QUIZ ),
		'exam'    => (int) $exam,
	);
	set_transient( $key, $summary, 12 * HOUR_IN_SECONDS );
	return $summary;
}

/**
 * Vide les résumés de catégories quand un contenu ou une catégorie change.
 */
function flush_track_summaries() {
	$terms = get_terms(
		array(
			'taxonomy'   => TAX_TRACK,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);
	foreach ( is_wp_error( $terms ) ? array() : $terms as $term_id ) {
		delete_transient( 'ld_track_summary_' . $term_id );
	}
}
add_action( 'save_post', __NAMESPACE__ . '\\flush_track_summaries' );
add_action( 'edited_' . TAX_TRACK, __NAMESPACE__ . '\\flush_track_summaries' );
