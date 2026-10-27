<?php
/**
 * Données structurées JSON-LD : un seul graphe cohérent par page.
 *
 * Toutes les pages : WebSite, Organization (ou EducationalOrganization),
 * WebPage et BreadcrumbList. Selon le gabarit : NewsArticle, Article,
 * LearningResource, Course / ItemList, Quiz + Question (+ Answer),
 * DefinedTerm, ProfilePage + Person, VideoObject. Contenus payants :
 * isAccessibleForFree + hasPart.cssSelector.
 *
 * Avec Yoast ou Rank Math, nos nœuds sont fusionnés dans leur graphe au lieu
 * de le doubler.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\SEO\Schema;

use LearnDrive\Core\SEO;
use LearnDrive\Core\Questions;
use LearnDrive\Core\Quizzes;
use LearnDrive\Core\Access;
use LearnDrive\Core\SEO\Breadcrumbs;
use function LearnDrive\Core\setting;
use function LearnDrive\Core\primary_term;
use function LearnDrive\Core\seo_plugin_active;
use const LearnDrive\Core\PT_COURSE;
use const LearnDrive\Core\PT_LESSON;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\PT_QUIZ;
use const LearnDrive\Core\PT_SHEET;
use const LearnDrive\Core\PT_GUIDE;
use const LearnDrive\Core\PT_GLOSSARY;
use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\TAX_TOPIC;
use const LearnDrive\Core\TAX_LEVEL;

defined( 'ABSPATH' ) || exit;

/**
 * Identifiants stables des nœuds partagés.
 *
 * @param string $node website|organization.
 * @return string
 */
function id( $node ) {
	return home_url( '/#' . $node );
}

/**
 * Nœud Organization.
 *
 * @return array
 */
function organization() {
	$type = in_array( setting( 'org_type' ), array( 'Organization', 'EducationalOrganization', 'NewsMediaOrganization', 'DrivingSchool' ), true ) ? setting( 'org_type' ) : 'EducationalOrganization';
	$node = array(
		'@type' => $type,
		'@id'   => id( 'organization' ),
		'name'  => SEO\org_name(),
		'url'   => home_url( '/' ),
	);

	$logo = SEO\site_image();
	if ( $logo ) {
		$node['logo'] = array(
			'@type'  => 'ImageObject',
			'@id'    => id( 'logo' ),
			'url'    => $logo['url'],
			'width'  => $logo['width'],
			'height' => $logo['height'],
		);
	}

	$same_as = array_values( array_filter( array_map( 'esc_url_raw', array_map( 'trim', preg_split( '/[\r\n]+/', (string) setting( 'org_same_as' ) ) ) ) ) );
	if ( $same_as ) {
		$node['sameAs'] = $same_as;
	}

	return $node;
}

/**
 * Nœud WebSite avec action de recherche.
 *
 * @return array
 */
function website() {
	return array(
		'@type'           => 'WebSite',
		'@id'             => id( 'website' ),
		'url'             => home_url( '/' ),
		'name'            => get_bloginfo( 'name' ),
		'description'     => get_bloginfo( 'description' ),
		'inLanguage'      => get_bloginfo( 'language' ),
		'publisher'       => array( '@id' => id( 'organization' ) ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}' ),
			),
			'query-input' => 'required name=search_term_string',
		),
	);
}

/**
 * Nœud BreadcrumbList.
 *
 * @param string $url URL de la page.
 * @return array|null
 */
function breadcrumb( $url ) {
	$trail = Breadcrumbs\trail();
	if ( count( $trail ) < 2 ) {
		return null;
	}

	$items = array();
	foreach ( array_values( $trail ) as $index => $item ) {
		$element  = array(
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'name'     => wp_strip_all_tags( $item['label'] ),
		);
		$item_url = ! empty( $item['url'] ) && ! is_wp_error( $item['url'] ) ? $item['url'] : $url;
		if ( $item_url ) {
			$element['item'] = $item_url;
		}
		$items[] = $element;
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => $url . '#breadcrumb',
		'itemListElement' => $items,
	);
}

/**
 * Nœud Person d'un auteur.
 *
 * @param int $user_id Auteur.
 * @return array
 */
function person( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return array();
	}

	$node = array(
		'@type' => 'Person',
		'@id'   => get_author_posts_url( $user_id ) . '#person',
		'name'  => $user->display_name,
		'url'   => get_author_posts_url( $user_id ),
	);

	if ( $user->description ) {
		$node['description'] = wp_strip_all_tags( $user->description );
	}
	$job = (string) get_user_meta( $user_id, 'ld_job_title', true );
	if ( $job ) {
		$node['jobTitle'] = $job;
	}
	$expertise = array_values( array_filter( array_map( 'trim', explode( ',', (string) get_user_meta( $user_id, 'ld_expertise', true ) ) ) ) );
	if ( $expertise ) {
		$node['knowsAbout'] = $expertise;
	}
	$same_as = array_values( array_filter( array_map( 'esc_url_raw', array_map( 'trim', preg_split( '/[\r\n]+/', (string) get_user_meta( $user_id, 'ld_same_as', true ) ) ) ) ) );
	if ( $same_as ) {
		$node['sameAs'] = $same_as;
	}
	$avatar = get_avatar_url( $user_id, array( 'size' => 256 ) );
	if ( $avatar ) {
		$node['image'] = array(
			'@type' => 'ImageObject',
			'url'   => $avatar,
		);
	}

	return $node;
}

/**
 * Images en 16:9, 4:3 et 1:1 (recommandation Google pour les articles).
 *
 * @param int $post_id Contenu.
 * @return string[]
 */
function article_images( $post_id ) {
	$thumb = get_post_thumbnail_id( $post_id );
	if ( ! $thumb ) {
		$image = SEO\post_image( $post_id );
		return $image ? array( $image['url'] ) : array();
	}

	$urls = array();
	foreach ( array( 'learn-drive-16x9', 'learn-drive-4x3', 'learn-drive-1x1', 'full' ) as $size ) {
		$src = wp_get_attachment_image_src( $thumb, $size );
		if ( $src && ! in_array( $src[0], $urls, true ) ) {
			$urls[] = $src[0];
		}
	}
	return $urls;
}

/**
 * Balisage du contenu payant.
 *
 * @param array $node    Nœud principal.
 * @param int   $post_id Contenu.
 * @return array
 */
function paywall( array $node, $post_id ) {
	if ( 'free' === Access\rule( $post_id ) ) {
		return $node;
	}
	$node['isAccessibleForFree'] = false;
	$node['hasPart']             = array(
		'@type'               => 'WebPageElement',
		'isAccessibleForFree' => false,
		'cssSelector'         => '.ld-paywall',
	);
	return $node;
}

/**
 * Nœuds Question d'une liste de questions.
 *
 * @param int[] $ids         Questions.
 * @param bool  $with_answer Inclure la bonne réponse (seulement si elle est dans le HTML).
 * @return array[]
 */
function question_nodes( array $ids, $with_answer ) {
	$nodes = array();
	foreach ( $ids as $id ) {
		$question = Questions\get( $id );
		if ( ! $question || 'flashcard' === $question['format'] ) {
			continue;
		}

		$node = array(
			'@type'           => 'Question',
			'name'            => $question['title'],
			'text'            => $question['title'],
			'url'             => $question['url'],
			'eduQuestionType' => 'tf' === $question['format'] ? 'True/False' : ( 'numeric' === $question['format'] ? 'Short answer' : 'Multiple choice' ),
		);

		$structured = in_array( $question['format'], array( 'order', 'match', 'hotspot' ), true );
		if ( $structured ) {
			unset( $node['eduQuestionType'] );
		}

		$suggested = array();
		foreach ( $structured ? array() : $question['answers'] as $index => $answer ) {
			if ( $with_answer && $answer['correct'] ) {
				continue;
			}
			$suggested[] = array(
				'@type'    => 'Answer',
				'text'     => $answer['text'],
				'position' => $index,
			);
		}
		if ( $suggested ) {
			$node['suggestedAnswer'] = $suggested;
		}

		if ( $with_answer && Access\can_access( $id, 0 ) ) {
			$explanation = SEO\truncate( wp_strip_all_tags( excerpt_remove_blocks( $question['content'] ) ), 600 );
			$accepted    = array();
			if ( 'numeric' === $question['format'] || $structured ) {
				$accepted[] = array(
					'@type' => 'Answer',
					'text'  => Questions\solution_text( $question ),
				);
			} else {
				foreach ( $question['answers'] as $index => $answer ) {
					if ( $answer['correct'] ) {
						$accepted[] = array(
							'@type'    => 'Answer',
							'text'     => $answer['text'],
							'position' => $index,
						);
					}
				}
			}
			if ( $accepted && $explanation ) {
				$accepted[0]['answerExplanation'] = array(
					'@type' => 'Comment',
					'text'  => $explanation,
				);
			}
			if ( $accepted ) {
				$node['acceptedAnswer'] = 1 === count( $accepted ) ? $accepted[0] : $accepted;
			}
		}

		$nodes[] = $node;
	}
	return $nodes;
}

/**
 * Alignement pédagogique (thème officiel, catégorie).
 *
 * @param int $post_id Contenu.
 * @return array[]
 */
function alignment( $post_id ) {
	$out = array();
	foreach ( array( TAX_TOPIC, TAX_TRACK ) as $taxonomy ) {
		$term = primary_term( $post_id, $taxonomy );
		if ( $term ) {
			$out[] = array(
				'@type'         => 'AlignmentObject',
				'alignmentType' => 'educationalSubject',
				'targetName'    => $term->name,
				'targetUrl'     => get_term_link( $term ),
			);
		}
	}
	return $out;
}

/**
 * Vidéos YouTube / Vimeo du contenu.
 *
 * @param int $post_id Contenu.
 * @return array[]
 */
function videos( $post_id ) {
	$content = get_post_field( 'post_content', $post_id );
	$nodes   = array();

	if ( preg_match_all( '#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{11})#', $content, $matches ) ) {
		foreach ( array_unique( $matches[1] ) as $index => $video_id ) {
			$nodes[] = array(
				'@type'        => 'VideoObject',
				'@id'          => get_permalink( $post_id ) . '#video-' . ( $index + 1 ),
				'name'         => wp_strip_all_tags( get_the_title( $post_id ) ),
				'description'  => SEO\post_description( $post_id ),
				'thumbnailUrl' => 'https://i.ytimg.com/vi/' . $video_id . '/hqdefault.jpg',
				'uploadDate'   => get_post_time( 'c', true, $post_id ),
				'embedUrl'     => 'https://www.youtube-nocookie.com/embed/' . $video_id,
			);
		}
	}

	return $nodes;
}

/**
 * Nœuds propres au contenu affiché (sans les nœuds partagés).
 *
 * @param string $url URL de la page.
 * @return array[]
 */
function main_nodes( $url ) {
	$nodes = array();

	if ( is_singular() ) {
		$post   = get_queried_object();
		$id     = $post->ID;
		$type   = $post->post_type;
		$author = person( (int) $post->post_author );
		$image  = SEO\post_image( $id );
		$base   = array(
			'@id'              => $url . '#main',
			'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
			'headline'         => wp_strip_all_tags( get_the_title( $id ) ),
			'name'             => wp_strip_all_tags( get_the_title( $id ) ),
			'description'      => SEO\post_description( $id ),
			'url'              => $url,
			'datePublished'    => get_post_time( 'c', true, $id ),
			'dateModified'     => get_post_modified_time( 'c', true, $id ),
			'inLanguage'       => get_bloginfo( 'language' ),
			'publisher'        => array( '@id' => id( 'organization' ) ),
		);
		if ( $author && in_array( $type, array( 'post', PT_GUIDE, PT_LESSON, PT_COURSE, PT_QUESTION, PT_SHEET, PT_GLOSSARY ), true ) ) {
			$base['author'] = array( '@id' => $author['@id'] );
			$nodes[]        = $author;
		}

		switch ( $type ) {
			case 'post':
				$node     = array_merge(
					array( '@type' => 'NewsArticle' ),
					$base,
					array( 'image' => article_images( $id ) )
				);
				$category = primary_term( $id, 'category' );
				if ( $category ) {
					$node['articleSection'] = $category->name;
				}
				$nodes[] = paywall( $node, $id );
				break;

			case PT_GUIDE:
				$node = array_merge( array( '@type' => 'Article' ), $base );
				if ( $image ) {
					$node['image'] = article_images( $id );
				}
				$nodes[] = $node;
				break;

			case PT_LESSON:
				$node                         = array_merge( array( '@type' => array( 'Article', 'LearningResource' ) ), $base );
				$node['learningResourceType'] = 'Lesson';
				$node['educationalAlignment'] = alignment( $id );
				$node['isAccessibleForFree']  = 'free' === Access\rule( $id );
				$level                        = primary_term( $id, TAX_LEVEL );
				if ( $level ) {
					$node['educationalLevel'] = $level->name;
				}
				if ( $image ) {
					$node['image'] = $image['url'];
				}
				$course = (int) get_post_meta( $id, '_ld_course', true );
				if ( $course ) {
					$node['isPartOf'] = array(
						'@type' => 'Course',
						'name'  => get_the_title( $course ),
						'url'   => get_permalink( $course ),
					);
				}
				$nodes[] = paywall( $node, $id );
				break;

			case PT_COURSE:
				$node    = array(
					'@type'                => 'Course',
					'@id'                  => $url . '#main',
					'name'                 => $base['name'],
					'description'          => $base['description'],
					'url'                  => $url,
					'inLanguage'           => $base['inLanguage'],
					'provider'             => array( '@id' => id( 'organization' ) ),
					'educationalAlignment' => alignment( $id ),
					'isAccessibleForFree'  => 'free' === Access\rule( $id ),
				);
				$lessons = \LearnDrive\Core\Blocks\course_lessons( $id );
				if ( $lessons ) {
					$node['hasPart'] = array_map(
						static function ( $lesson ) {
							return array(
								'@type' => 'LearningResource',
								'name'  => get_the_title( $lesson ),
								'url'   => get_permalink( $lesson ),
							);
						},
						$lessons
					);
				}
				$nodes[] = $node;
				break;

			case PT_QUIZ:
				$ids     = Quizzes\question_ids( $id );
				$node    = array(
					'@type'                => 'Quiz',
					'@id'                  => $url . '#main',
					'name'                 => $base['name'],
					'description'          => $base['description'],
					'url'                  => $url,
					'inLanguage'           => $base['inLanguage'],
					'educationalAlignment' => alignment( $id ),
					'about'                => array_map(
						static function ( $alignment ) {
							return array(
								'@type' => 'Thing',
								'name'  => $alignment['targetName'],
							);
						},
						alignment( $id )
					),
					'hasPart'              => question_nodes( $ids, false ),
					'provider'             => array( '@id' => id( 'organization' ) ),
				);
				$nodes[] = paywall( $node, $id );
				break;

			case PT_QUESTION:
				$node    = array(
					'@type'                => 'Quiz',
					'@id'                  => $url . '#main',
					'name'                 => $base['name'],
					'url'                  => $url,
					'inLanguage'           => $base['inLanguage'],
					'educationalAlignment' => alignment( $id ),
					'hasPart'              => question_nodes( array( $id ), true ),
					'dateModified'         => $base['dateModified'],
				);
				$nodes[] = paywall( $node, $id );
				break;

			case PT_SHEET:
			case PT_GLOSSARY:
				$set_url = get_post_type_archive_link( $type );
				$node    = array(
					'@type'            => 'DefinedTerm',
					'@id'              => $url . '#main',
					'name'             => $base['name'],
					'description'      => $base['description'],
					'url'              => $url,
					'inDefinedTermSet' => array(
						'@type' => 'DefinedTermSet',
						'@id'   => $set_url . '#set',
						'name'  => get_post_type_object( $type )->labels->name,
						'url'   => $set_url,
					),
				);
				$code    = (string) get_post_meta( $id, '_ld_code', true );
				if ( $code ) {
					$node['termCode'] = $code;
				}
				if ( $image ) {
					$node['image'] = array(
						'@type'   => 'ImageObject',
						'url'     => $image['url'],
						'width'   => $image['width'],
						'height'  => $image['height'],
						'caption' => $image['alt'] ? $image['alt'] : $base['name'],
					);
				}
				$nodes[] = $node;
				break;
		}

		foreach ( videos( $id ) as $video ) {
			$nodes[] = $video;
		}
	} elseif ( is_author() ) {
		$nodes[] = person( get_queried_object_id() );
	} elseif ( is_post_type_archive( PT_COURSE ) || ( is_tax( TAX_TRACK ) ) ) {
		$ids = is_tax( TAX_TRACK ) ? \LearnDrive\Core\Blocks\related_ids( PT_COURSE, 'courses', 20 ) : wp_list_pluck( $GLOBALS['wp_query']->posts, 'ID' );
		if ( $ids ) {
			$items = array();
			foreach ( array_values( $ids ) as $index => $course_id ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $index + 1,
					'item'     => array(
						'@type'       => 'Course',
						'name'        => get_the_title( $course_id ),
						'description' => SEO\post_description( $course_id ),
						'url'         => get_permalink( $course_id ),
						'provider'    => array( '@id' => id( 'organization' ) ),
					),
				);
			}
			$nodes[] = array(
				'@type'           => 'ItemList',
				'@id'             => $url . '#courses',
				'itemListElement' => $items,
			);
		}
	} elseif ( is_post_type_archive( PT_GLOSSARY ) ) {
		// Cartes mémoire : carrousel Education Q&A (langues éligibles), balisage utile ailleurs.
		$cards = array();
		foreach ( $GLOBALS['wp_query']->posts as $term_post ) {
			$definition = wp_strip_all_tags( $term_post->post_excerpt ? $term_post->post_excerpt : SEO\first_sentence( $term_post->post_content ) );
			if ( ! $definition ) {
				continue;
			}
			$cards[] = array(
				'@type'           => 'Question',
				'eduQuestionType' => 'Flashcard',
				'text'            => $term_post->post_title,
				'acceptedAnswer'  => array(
					'@type' => 'Answer',
					'text'  => $definition,
				),
			);
		}
		if ( $cards ) {
			$nodes[] = array(
				'@type'   => 'Quiz',
				'@id'     => $url . '#flashcards',
				'name'    => post_type_archive_title( '', false ),
				'about'   => array(
					'@type' => 'Thing',
					'name'  => post_type_archive_title( '', false ),
				),
				'hasPart' => $cards,
			);
		}
	}

	/**
	 * Filtre les nœuds JSON-LD propres à la page.
	 *
	 * @param array[] $nodes Nœuds.
	 * @param string  $url   URL de la page.
	 */
	return apply_filters( 'learn_drive_schema_nodes', $nodes, $url );
}

/**
 * Nœud WebPage.
 *
 * @param string $url   URL.
 * @param array  $nodes Nœuds principaux.
 * @return array
 */
function webpage( $url, array $nodes ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- signature stable pour les filtres.
	$type = 'WebPage';
	if ( is_search() ) {
		$type = 'SearchResultsPage';
	} elseif ( is_author() ) {
		$type = 'ProfilePage';
	} elseif ( is_archive() || is_home() ) {
		$type = 'CollectionPage';
	} elseif ( is_singular( array( PT_QUIZ, PT_QUESTION ) ) ) {
		$type = 'WebPage';
	}

	$node = array(
		'@type'      => $type,
		'@id'        => $url . '#webpage',
		'url'        => $url,
		'name'       => SEO\page_title(),
		'isPartOf'   => array( '@id' => id( 'website' ) ),
		'inLanguage' => get_bloginfo( 'language' ),
	);

	$description = SEO\page_description();
	if ( $description ) {
		$node['description'] = $description;
	}

	if ( is_singular() ) {
		$id                    = get_queried_object_id();
		$node['datePublished'] = get_post_time( 'c', true, $id );
		$node['dateModified']  = get_post_modified_time( 'c', true, $id );
		$image                 = SEO\post_image( $id );
		if ( $image ) {
			$node['primaryImageOfPage'] = array(
				'@type' => 'ImageObject',
				'url'   => $image['url'],
			);
		}
	}

	if ( is_author() ) {
		$node['mainEntity'] = array( '@id' => get_author_posts_url( get_queried_object_id() ) . '#person' );
	}

	if ( count( Breadcrumbs\trail() ) > 1 ) {
		$node['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
	}

	return $node;
}

/**
 * URL de référence des identifiants (@id) de la page.
 *
 * @return string
 */
function page_url() {
	$canonical = SEO\canonical();
	if ( $canonical ) {
		return $canonical;
	}
	if ( is_singular() ) {
		return get_permalink( get_queried_object_id() );
	}
	return home_url( add_query_arg( array() ) );
}

/**
 * Graphe complet.
 *
 * @return array
 */
function graph() {
	$url   = page_url();
	$main  = main_nodes( $url );
	$graph = array( organization(), website(), webpage( $url, $main ) );

	$crumbs = breadcrumb( $url );
	if ( $crumbs ) {
		$graph[] = $crumbs;
	}

	return array_merge( $graph, $main );
}

/**
 * Affiche le JSON-LD (sans plugin SEO, ou avec un plugin non fusionnable).
 */
function output() {
	if ( is_404() || is_feed() ) {
		return;
	}

	if ( defined( 'WPSEO_VERSION' ) || class_exists( '\RankMath' ) ) {
		return; // Fusion dans leur graphe (voir plus bas).
	}

	if ( seo_plugin_active() ) {
		// SEOPress, AIOSEO, TSF : on ajoute seulement nos nœuds spécifiques.
		$nodes = main_nodes( page_url() );
		if ( ! $nodes ) {
			return;
		}
		$data = array(
			'@context' => 'https://schema.org',
			'@graph'   => $nodes,
		);
	} else {
		$data = array(
			'@context' => 'https://schema.org',
			'@graph'   => graph(),
		);
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP )
	);
}
add_action( 'wp_head', __NAMESPACE__ . '\\output', 30 );

/**
 * Fusion dans le graphe Yoast.
 *
 * @param array $graph Graphe Yoast.
 * @return array
 */
function yoast_graph( $graph ) {
	return array_merge( $graph, main_nodes( page_url() ) );
}
add_filter( 'wpseo_schema_graph', __NAMESPACE__ . '\\yoast_graph' );

/**
 * Fusion dans les données Rank Math.
 *
 * @param array $data Données Rank Math.
 * @return array
 */
function rank_math_graph( $data ) {
	foreach ( main_nodes( page_url() ) as $index => $node ) {
		$data[ 'learn_drive_' . $index ] = $node;
	}
	return $data;
}
add_filter( 'rank_math/json_ld', __NAMESPACE__ . '\\rank_math_graph', 99 );
