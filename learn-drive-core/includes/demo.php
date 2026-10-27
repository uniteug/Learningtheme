<?php
/**
 * Contenu de démonstration : 2 pays (Italie vrai/faux, France QCM), 5
 * catégories, 120 questions, quiz et examens blancs, leçons, fiches panneaux,
 * lexique, actualités, guides et pages éditoriales.
 *
 * Les images (panneaux, illustrations d'articles) sont dessinées localement
 * avec GD : aucune ressource externe n'est téléchargée.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Demo;

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
use const LearnDrive\Core\TAX_FAMILY;
use const LearnDrive\Core\TAX_LEVEL;
use const LearnDrive\Core\OPTION;
use LearnDrive\Core\Import;

defined( 'ABSPATH' ) || exit;

/**
 * Données de démonstration.
 *
 * @return array
 */
function data() {
	$file = LEARN_DRIVE_CORE_DIR . 'demo/content.json';
	$json = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return is_array( $json ) ? $json : array();
}

/**
 * Crée ou met à jour un contenu identifié par son slug.
 *
 * @param string $type    Type.
 * @param array  $postarr Données.
 * @return int
 */
function upsert( $type, array $postarr ) {
	$existing = get_page_by_path( $postarr['post_name'], OBJECT, $type );
	$postarr  = array_merge(
		array(
			'post_type'   => $type,
			'post_status' => 'publish',
			'post_author' => demo_author(),
		),
		$postarr
	);
	if ( $existing ) {
		$postarr['ID'] = $existing->ID;
	}
	$id = wp_insert_post( wp_slash( $postarr ), true );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

/**
 * Auteur des contenus de démonstration (premier administrateur).
 *
 * @return int
 */
function demo_author() {
	static $author = null;
	if ( null === $author ) {
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			)
		);
		$author = $admins ? (int) $admins[0] : 1;
	}
	return $author;
}

/**
 * Termes d'une liste de slugs.
 *
 * @param array|string $slugs    Slugs.
 * @param string       $taxonomy Taxonomie.
 * @return int[]
 */
function terms( $slugs, $taxonomy ) {
	$ids = array();
	foreach ( (array) $slugs as $slug ) {
		if ( ! $slug ) {
			continue;
		}
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( $term ) {
			$ids[] = (int) $term->term_id;
		}
	}
	return $ids;
}

/**
 * Enregistre une image générée comme pièce jointe.
 *
 * @param \GdImage $image   Image GD.
 * @param string   $name    Nom de fichier (sans extension).
 * @param string   $alt     Texte alternatif.
 * @param int      $parent  Contenu parent.
 * @param string   $format  png|jpg.
 * @return int
 */
function save_image( $image, $name, $alt, $parent, $format = 'png' ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';

	ob_start();
	if ( 'png' === $format ) {
		imagepng( $image, null, 9 );
	} else {
		imagejpeg( $image, null, 86 );
	}
	$bits = ob_get_clean();
	imagedestroy( $image );

	$upload = wp_upload_bits( $name . '.' . $format, null, $bits );
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}

	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'png' === $format ? 'image/png' : 'image/jpeg',
			'post_title'     => $alt,
			'post_status'    => 'inherit',
		),
		$upload['file'],
		$parent
	);
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}

	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	if ( $parent ) {
		set_post_thumbnail( $parent, $id );
	}
	return (int) $id;
}

/**
 * Couleur GD depuis un code hexadécimal.
 *
 * @param \GdImage $image Image.
 * @param string   $hex   Couleur.
 * @return int
 */
function color( $image, $hex ) {
	$hex = ltrim( $hex, '#' );
	return imagecolorallocate( $image, hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
}

/**
 * Polygone régulier (points).
 *
 * @param float $cx     Centre X.
 * @param float $cy     Centre Y.
 * @param float $radius Rayon.
 * @param int   $sides  Côtés.
 * @param float $angle  Angle de départ (degrés).
 * @return int[]
 */
function polygon( $cx, $cy, $radius, $sides, $angle ) {
	$points = array();
	for ( $i = 0; $i < $sides; $i++ ) {
		$a        = deg2rad( $angle + 360 * $i / $sides );
		$points[] = (int) round( $cx + $radius * cos( $a ) );
		$points[] = (int) round( $cy + $radius * sin( $a ) );
	}
	return $points;
}

/**
 * Texte centré, réduit pour tenir dans la largeur.
 *
 * @param \GdImage $image Image.
 * @param string   $text  Texte.
 * @param int      $cx    Centre X.
 * @param int      $cy    Centre Y.
 * @param int      $max   Largeur maximale.
 * @param int      $color Couleur.
 * @param int      $size  Taille de départ.
 */
function centered_text( $image, $text, $cx, $cy, $max, $color, $size = 150 ) {
	$font = LEARN_DRIVE_CORE_DIR . 'assets/fonts/atkinson-hyperlegible-700.ttf';
	do {
		$box    = imagettfbbox( $size, 0, $font, $text );
		$width  = $box[2] - $box[0];
		$height = $box[1] - $box[7];
		$size  -= 6;
	} while ( $width > $max && $size > 20 );
	imagettftext( $image, $size + 6, 0, (int) ( $cx - $width / 2 - $box[0] ), (int) ( $cy + $height / 2 ), $color, $font, $text );
}

/**
 * Dessine un panneau routier (600 × 600, fond transparent).
 *
 * @param string $shape  Forme.
 * @param string $symbol Symbole.
 * @return \GdImage
 */
function draw_sign( $shape, $symbol ) {
	$s     = 600;
	$image = imagecreatetruecolor( $s, $s );
	imagesavealpha( $image, true );
	imagealphablending( $image, true );
	imagefill( $image, 0, 0, imagecolorallocatealpha( $image, 0, 0, 0, 127 ) );
	imageantialias( $image, true );

	$red    = color( $image, '#C8102E' );
	$blue   = color( $image, '#1D4F91' );
	$white  = color( $image, '#FFFFFF' );
	$black  = color( $image, '#111111' );
	$yellow = color( $image, '#F2B705' );
	$grey   = color( $image, '#7A7A7A' );
	$c      = $s / 2;
	$text   = $black;
	$text_y = $c;

	switch ( $shape ) {
		case 'triangle':
		case 'triangle-inverted':
			$angle = 'triangle' === $shape ? -90 : 90;
			imagefilledpolygon( $image, polygon( $c, $c + ( 'triangle' === $shape ? 60 : -60 ), 300, 3, $angle ), $red );
			imagefilledpolygon( $image, polygon( $c, $c + ( 'triangle' === $shape ? 60 : -60 ), 215, 3, $angle ), $white );
			$text_y = 'triangle' === $shape ? $c + 90 : $c - 60;
			break;
		case 'octagon':
			imagefilledpolygon( $image, polygon( $c, $c, 295, 8, 22.5 ), $white );
			imagefilledpolygon( $image, polygon( $c, $c, 280, 8, 22.5 ), $red );
			$text = $white;
			break;
		case 'circle-blue':
			imagefilledellipse( $image, $c, $c, 590, 590, $white );
			imagefilledellipse( $image, $c, $c, 570, 570, $blue );
			$text = $white;
			break;
		case 'circle-white':
			imagefilledellipse( $image, $c, $c, 590, 590, $grey );
			imagefilledellipse( $image, $c, $c, 560, 560, $white );
			imagesetthickness( $image, 14 );
			for ( $i = -3; $i <= 3; $i++ ) {
				imageline( $image, 120 + $i * 22, 480 + $i * 22 - 44, 480 + $i * 22, 120 + $i * 22 - 44, $grey );
			}
			imagesetthickness( $image, 1 );
			$text = $grey;
			break;
		case 'square-blue':
			imagefilledrectangle( $image, 10, 10, 590, 590, $white );
			imagefilledrectangle( $image, 24, 24, 576, 576, $blue );
			$text = $white;
			break;
		case 'diamond':
			imagefilledpolygon( $image, polygon( $c, $c, 295, 4, 0 ), $black );
			imagefilledpolygon( $image, polygon( $c, $c, 285, 4, 0 ), $white );
			imagefilledpolygon( $image, polygon( $c, $c, 190, 4, 0 ), $yellow );
			$symbol = '';
			break;
		default: // circle-red.
			imagefilledellipse( $image, $c, $c, 590, 590, $red );
			imagefilledellipse( $image, $c, $c, 440, 440, $white );
	}

	if ( '' !== (string) $symbol ) {
		$max = in_array( $shape, array( 'triangle', 'triangle-inverted' ), true ) ? 230 : 360;
		centered_text( $image, (string) $symbol, (int) $c, (int) $text_y, $max, $text, 'octagon' === $shape ? 120 : 170 );
	}

	return $image;
}

/**
 * Illustration abstraite d'article (16:9, sans texte incrusté).
 *
 * @param int $seed Variation.
 * @return \GdImage
 */
function draw_cover( $seed ) {
	$w       = 1600;
	$h       = 900;
	$palette = array(
		array( '#1F4FD8', '#0B1F5C', '#FFC531' ),
		array( '#0F766E', '#053B37', '#FDE68A' ),
		array( '#6D28D9', '#2E1065', '#FACC15' ),
		array( '#C2410C', '#431407', '#FED7AA' ),
		array( '#334155', '#0F172A', '#38BDF8' ),
	);
	$colors  = $palette[ $seed % count( $palette ) ];
	$image   = imagecreatetruecolor( $w, $h );
	imageantialias( $image, true );

	$from = sscanf( $colors[0], '#%02x%02x%02x' );
	$to   = sscanf( $colors[1], '#%02x%02x%02x' );
	for ( $x = 0; $x < $w; $x += 4 ) {
		$t = $x / $w;
		$c = imagecolorallocate( $image, (int) ( $from[0] + ( $to[0] - $from[0] ) * $t ), (int) ( $from[1] + ( $to[1] - $from[1] ) * $t ), (int) ( $from[2] + ( $to[2] - $from[2] ) * $t ) );
		imagefilledrectangle( $image, $x, 0, $x + 4, $h, $c );
	}

	$soft   = imagecolorallocatealpha( $image, 255, 255, 255, 105 );
	$accent = color( $image, $colors[2] );
	imagefilledellipse( $image, 1250 + ( $seed * 37 ) % 200, 300, 900, 900, $soft );
	imagefilledellipse( $image, 200 + ( $seed * 53 ) % 300, 820, 600, 600, $soft );

	// Marquage routier stylisé.
	for ( $i = 0; $i < 7; $i++ ) {
		$x0 = 120 + $i * 230;
		imagefilledpolygon( $image, array( $x0, 640, $x0 + 140, 640, $x0 + 120, 680, $x0 - 20, 680 ), $accent );
	}

	return $image;
}

/**
 * Importe tout le contenu de démonstration.
 *
 * @param callable|null $log Rappel de progression.
 * @return array Compteurs.
 */
function import( $log = null ) {
	$data  = data();
	$count = array();
	$say   = static function ( $message ) use ( $log ) {
		if ( $log ) {
			call_user_func( $log, $message );
		}
	};

	if ( ! $data ) {
		return $count;
	}

	wp_defer_term_counting( true );

	// Réglages.
	$settings = get_option( OPTION, array() );
	$settings = array_merge(
		is_array( $settings ) ? $settings : array(),
		array(
			'org_name'        => get_bloginfo( 'name' ),
			'default_country' => 'france',
			'news_name'       => get_bloginfo( 'name' ),
		)
	);
	update_option( OPTION, $settings );

	// Pays et règles d'examen.
	foreach ( $data['countries'] as $country ) {
		$term = term_upsert( $country['name'], $country['slug'], TAX_COUNTRY );
		update_term_meta( $term, 'ld_rules', $country['rules'] );
	}

	// Catégories (pages piliers).
	$country_of = array();
	foreach ( $data['tracks'] as $index => $track ) {
		$term = term_upsert( $track['name'], $track['slug'], TAX_TRACK, $track['description'] );
		update_term_meta( $term, 'ld_color', $track['color'] );
		update_term_meta( $term, 'ld_content', $track['content'] );
		update_term_meta( $term, 'ld_country', $track['country'] );
		$country_of[ $track['slug'] ] = $track['country'];
	}
	$count['tracks'] = count( $data['tracks'] );

	foreach ( $data['topics'] as $topic ) {
		$term = term_upsert( $topic['name'], $topic['slug'], TAX_TOPIC, $topic['description'] );
		update_term_meta( $term, 'ld_country', $topic['country'] );
	}
	foreach ( $data['sheet_families'] as $family ) {
		term_upsert( $family['name'], $family['slug'], TAX_FAMILY );
	}
	foreach ( array( 'debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'examen' => 'Niveau examen' ) as $slug => $name ) {
		term_upsert( $name, $slug, TAX_LEVEL );
	}
	$say( 'Taxonomies' );

	// Fiches panneaux (avant les questions, qui s'y rattachent).
	foreach ( $data['sheets'] as $index => $sheet ) {
		$id = upsert(
			PT_SHEET,
			array(
				'post_name'    => $sheet['slug'],
				'post_title'   => $sheet['title'],
				'post_excerpt' => $sheet['excerpt'],
				'post_content' => $sheet['content'],
				'menu_order'   => $index,
			)
		);
		wp_set_object_terms( $id, terms( $sheet['family'], TAX_FAMILY ), TAX_FAMILY );
		update_post_meta( $id, '_ld_code', $sheet['code'] );
		if ( ! get_post_thumbnail_id( $id ) && function_exists( 'imagettftext' ) ) {
			/* translators: %s: road sign name */
			save_image( draw_sign( $sheet['shape'], $sheet['symbol'] ), 'panneau-' . $sheet['slug'], sprintf( __( 'Panneau « %s »', 'learn-drive-core' ), $sheet['title'] ), $id );
		}
	}
	$count['sheets'] = count( $data['sheets'] );
	$say( 'Panneaux' );

	// Cours.
	$course_ids = array();
	foreach ( $data['courses'] as $index => $course ) {
		$id = upsert(
			PT_COURSE,
			array(
				'post_name'    => $course['slug'],
				'post_title'   => $course['title'],
				'post_excerpt' => $course['excerpt'],
				'post_content' => $course['content'],
				'menu_order'   => $index,
			)
		);
		wp_set_object_terms( $id, terms( $course['tracks'], TAX_TRACK ), TAX_TRACK );
		wp_set_object_terms( $id, terms( $course['level'] ?? '', TAX_LEVEL ), TAX_LEVEL );
		wp_set_object_terms( $id, terms( 'france', TAX_COUNTRY ), TAX_COUNTRY );
		if ( ! get_post_thumbnail_id( $id ) && function_exists( 'imagettftext' ) ) {
			save_image( draw_cover( $index + 2 ), 'cours-' . $course['slug'], $course['title'], $id, 'jpg' );
		}
		$course_ids[ $course['slug'] ] = $id;
	}

	// Leçons.
	foreach ( $data['lessons'] as $lesson ) {
		$id = upsert(
			PT_LESSON,
			array(
				'post_name'    => $lesson['slug'],
				'post_title'   => $lesson['title'],
				'post_excerpt' => $lesson['excerpt'],
				'post_content' => $lesson['content'],
				'menu_order'   => (int) $lesson['order'],
			)
		);
		wp_set_object_terms( $id, terms( $lesson['tracks'], TAX_TRACK ), TAX_TRACK );
		wp_set_object_terms( $id, terms( $lesson['topic'], TAX_TOPIC ), TAX_TOPIC );
		wp_set_object_terms( $id, terms( 'france', TAX_COUNTRY ), TAX_COUNTRY );
		update_post_meta( $id, '_ld_course', $course_ids[ $lesson['course'] ] ?? 0 );
		update_post_meta( $id, '_ld_access', $lesson['access'] ?? 'inherit' );
	}
	$count['lessons'] = count( $data['lessons'] );
	$say( 'Cours et leçons' );

	// Questions.
	foreach ( $data['questions'] as $index => $question ) {
		$question['order'] = $index;
		$id                = Import\import_question( $question );
		if ( is_int( $id ) && $id && ! empty( $question['sheet'] ) && ! get_post_thumbnail_id( $id ) ) {
			$sheet = get_page_by_path( $question['sheet'], OBJECT, PT_SHEET );
			$thumb = $sheet ? get_post_thumbnail_id( $sheet ) : 0;
			if ( $thumb ) {
				set_post_thumbnail( $id, $thumb );
			}
		}
	}
	$count['questions'] = count( $data['questions'] );
	$say( 'Questions' );

	// Quiz et examens blancs.
	foreach ( $data['quizzes'] as $index => $quiz ) {
		$id = upsert(
			PT_QUIZ,
			array(
				'post_name'    => $quiz['slug'],
				'post_title'   => $quiz['title'],
				'post_excerpt' => $quiz['excerpt'],
				'post_content' => $quiz['content'],
				'menu_order'   => 'exam' === $quiz['mode'] ? 0 : $index + 1,
			)
		);
		update_post_meta( $id, '_ld_mode', $quiz['mode'] );
		update_post_meta( $id, '_ld_access', $quiz['access'] ?? 'inherit' );
		if ( 'exam' !== $quiz['mode'] ) {
			update_post_meta( $id, '_ld_rules', array( 'count' => (int) $quiz['count'] ) );
		}
		wp_set_object_terms( $id, terms( $quiz['tracks'], TAX_TRACK ), TAX_TRACK );
		wp_set_object_terms( $id, terms( $quiz['topic'], TAX_TOPIC ), TAX_TOPIC );
		wp_set_object_terms( $id, terms( $quiz['country'], TAX_COUNTRY ), TAX_COUNTRY );
	}
	$count['quizzes'] = count( $data['quizzes'] );
	$say( 'Quiz' );

	// Lexique.
	foreach ( $data['glossary'] as $term ) {
		upsert(
			PT_GLOSSARY,
			array(
				'post_name'    => $term['slug'],
				'post_title'   => $term['title'],
				'post_excerpt' => $term['excerpt'],
				'post_content' => $term['content'],
			)
		);
	}
	$count['glossary'] = count( $data['glossary'] );

	// Guides pratiques.
	foreach ( $data['guides'] as $index => $guide ) {
		$id = upsert(
			PT_GUIDE,
			array(
				'post_name'    => $guide['slug'],
				'post_title'   => $guide['title'],
				'post_excerpt' => $guide['excerpt'],
				'post_content' => $guide['content'],
				'menu_order'   => $index,
			)
		);
		wp_set_object_terms( $id, terms( $guide['country'], TAX_COUNTRY ), TAX_COUNTRY );
		wp_set_object_terms( $id, terms( $guide['tracks'], TAX_TRACK ), TAX_TRACK );
	}
	$count['guides'] = count( $data['guides'] );
	$say( 'Guides' );

	// Actualités (articles), dates étalées sur trois semaines.
	foreach ( $data['news'] as $index => $news ) {
		$category = term_upsert( $news['category'], sanitize_title( $news['category'] ), 'category' );
		$date     = gmdate( 'Y-m-d H:i:s', time() - ( $index * 2 * DAY_IN_SECONDS ) - ( $index + 1 ) * HOUR_IN_SECONDS );
		$id       = upsert(
			'post',
			array(
				'post_name'     => $news['slug'],
				'post_title'    => $news['title'],
				'post_excerpt'  => $news['excerpt'],
				'post_content'  => $news['content'],
				'post_date_gmt' => $date,
				'post_date'     => get_date_from_gmt( $date ),
				'post_category' => array( $category ),
				'tags_input'    => $news['tags'],
			)
		);
		wp_set_object_terms( $id, terms( $news['tracks'] ?? array(), TAX_TRACK ), TAX_TRACK );
		wp_set_object_terms( $id, terms( $news['topic'] ?? '', TAX_TOPIC ), TAX_TOPIC );
		wp_set_object_terms( $id, terms( 'france', TAX_COUNTRY ), TAX_COUNTRY );
		if ( ! get_post_thumbnail_id( $id ) && function_exists( 'imagettftext' ) ) {
			save_image( draw_cover( $index ), 'actualite-' . $news['slug'], $news['title'], $id, 'jpg' );
		}
	}
	$count['news'] = count( $data['news'] );
	$say( 'Actualités' );

	// Pages.
	foreach ( $data['pages'] as $page ) {
		upsert(
			'page',
			array(
				'post_name'    => $page['slug'],
				'post_title'   => $page['title'],
				'post_content' => $page['content'],
			)
		);
	}

	$home    = upsert(
		'page',
		array(
			'post_name'    => 'accueil',
			'post_title'   => __( 'Accueil', 'learn-drive-core' ),
			'post_content' => '',
		)
	);
	$news    = upsert(
		'page',
		array(
			'post_name'    => 'actualites',
			'post_title'   => __( 'Actualités', 'learn-drive-core' ),
			'post_content' => '',
		)
	);
	upsert(
		'page',
		array(
			'post_name'    => 'mon-compte',
			'post_title'   => __( 'Mon espace', 'learn-drive-core' ),
			'post_content' => '<!-- wp:learn-drive/dashboard /-->',
		)
	);
	upsert(
		'page',
		array(
			'post_name'    => 'plan-du-site',
			'post_title'   => __( 'Plan du site', 'learn-drive-core' ),
			'post_content' => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html__( 'Pages', 'learn-drive-core' ) . "</h2>\n<!-- /wp:heading -->\n\n<!-- wp:page-list /-->\n\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html__( 'Rubriques d’actualité', 'learn-drive-core' ) . "</h2>\n<!-- /wp:heading -->\n\n<!-- wp:categories /-->",
		)
	);
	upsert(
		'page',
		array(
			'post_name'    => 'contact',
			'post_title'   => __( 'Contact', 'learn-drive-core' ),
			'post_content' => "<!-- wp:paragraph -->\n<p>" . esc_html__( 'Une question sur nos contenus, une erreur à signaler ? Écrivez-nous à l’adresse indiquée dans les mentions légales : nous répondons sous 48 heures ouvrées.', 'learn-drive-core' ) . "</p>\n<!-- /wp:paragraph -->",
		)
	);
	if ( ! get_page_by_path( 'politique-de-confidentialite' ) ) {
		upsert(
			'page',
			array(
				'post_name'    => 'politique-de-confidentialite',
				'post_title'   => __( 'Politique de confidentialité', 'learn-drive-core' ),
				'post_content' => "<!-- wp:paragraph -->\n<p>" . esc_html__( 'Aucun cookie non essentiel n’est déposé avant votre consentement. Vos statistiques d’apprentissage sont pseudonymisées, exportables et supprimables à tout moment depuis votre espace.', 'learn-drive-core' ) . "</p>\n<!-- /wp:paragraph -->",
			)
		);
	}

	// Contenus d'exemple de WordPress, remplacés par la démo.
	foreach ( array( array( 'hello-world', 'post' ), array( 'sample-page', 'page' ), array( 'bonjour-tout-le-monde', 'post' ), array( 'page-d-exemple', 'page' ) ) as $sample ) {
		$found = get_page_by_path( $sample[0], OBJECT, $sample[1] );
		if ( $found ) {
			wp_delete_post( $found->ID, true );
		}
	}

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );
	update_option( 'page_for_posts', $news );
	update_option( 'posts_per_page', 12 );

	// Profil d'auteur (E-E-A-T).
	$author = demo_author();
	if ( ! get_user_meta( $author, 'ld_job_title', true ) ) {
		update_user_meta( $author, 'ld_job_title', __( 'Enseignant de la conduite et de la sécurité routière', 'learn-drive-core' ) );
		update_user_meta( $author, 'ld_expertise', __( 'Code de la route, Pédagogie, Sécurité routière', 'learn-drive-core' ) );
	}

	wp_defer_term_counting( false );
	delete_option( 'ld_track_slugs' );
	delete_transient( 'ld_stats' );
	update_option( 'ld_flush_rewrite', 1 );
	update_option( 'ld_demo_imported', time() );

	return $count;
}

/**
 * Crée ou met à jour un terme.
 *
 * @param string $name        Nom.
 * @param string $slug        Slug.
 * @param string $taxonomy    Taxonomie.
 * @param string $description Description.
 * @return int
 */
function term_upsert( $name, $slug, $taxonomy, $description = '' ) {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term ) {
		wp_update_term(
			$term->term_id,
			$taxonomy,
			array(
				'name'        => $name,
				'description' => $description,
			)
		);
		return (int) $term->term_id;
	}
	$created = wp_insert_term(
		$name,
		$taxonomy,
		array(
			'slug'        => $slug,
			'description' => $description,
		)
	);
	return is_wp_error( $created ) ? 0 : (int) $created['term_id'];
}
