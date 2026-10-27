<?php
/**
 * Import / export des banques de questions : CSV, JSON et JSON Lines.
 *
 * Lecture ligne à ligne (CSV, JSONL) pour importer des milliers de questions
 * sans dépassement mémoire sur un hébergement mutualisé. Une question existante
 * (même slug ou même identifiant officiel) est mise à jour, pas dupliquée.
 *
 * Colonnes CSV : title, slug, format, answers (« A*|B|C », * = bonne réponse),
 * explanation, tracks (séparées par des virgules), topic, country, difficulty,
 * access, source, official_id, sheet, numeric_value, numeric_tolerance,
 * numeric_unit, image (URL ou chemin relatif), image_alt.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Import;

use LearnDrive\Core\Questions;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\PT_SHEET;
use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\TAX_TOPIC;
use const LearnDrive\Core\TAX_COUNTRY;

defined( 'ABSPATH' ) || exit;

/**
 * Identifiant d'un terme (créé au besoin) à partir d'un slug ou d'un nom.
 *
 * @param string $value    Slug ou nom.
 * @param string $taxonomy Taxonomie.
 * @return int
 */
function term_id( $value, $taxonomy ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return 0;
	}
	$term = get_term_by( 'slug', sanitize_title( $value ), $taxonomy );
	if ( ! $term ) {
		$term = get_term_by( 'name', $value, $taxonomy );
	}
	if ( $term ) {
		return (int) $term->term_id;
	}
	$created = wp_insert_term( $value, $taxonomy, array( 'slug' => sanitize_title( $value ) ) );
	return is_wp_error( $created ) ? 0 : (int) $created['term_id'];
}

/**
 * Propositions à partir d'un tableau ou d'une chaîne « A*|B|C ».
 *
 * @param mixed  $answers Propositions.
 * @param string $format  Format.
 * @return array
 */
function parse_answers( $answers, $format ) {
	if ( is_string( $answers ) ) {
		$answers = array_map(
			static function ( $text ) {
				$text    = trim( $text );
				$correct = str_ends_with( $text, '*' ) || str_starts_with( $text, '*' );
				return array(
					'text'    => trim( $text, '* ' ),
					'correct' => $correct,
				);
			},
			array_filter( explode( '|', $answers ), 'strlen' )
		);
	}

	$clean = array();
	foreach ( (array) $answers as $answer ) {
		if ( is_array( $answer ) && isset( $answer['text'] ) && '' !== trim( (string) $answer['text'] ) ) {
			$clean[] = array(
				'text'    => sanitize_text_field( $answer['text'] ),
				'correct' => ! empty( $answer['correct'] ),
			);
		}
	}

	return 'numeric' === $format ? array() : array_slice( $clean, 0, 6 );
}

/**
 * Attache une image (URL distante ou fichier local).
 *
 * @param string $source   URL ou chemin.
 * @param int    $post_id  Contenu.
 * @param string $alt      Texte alternatif.
 * @param string $base_dir Dossier du fichier importé.
 * @return int
 */
function attach_image( $source, $post_id, $alt, $base_dir = '' ) {
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$source = trim( (string) $source );
	if ( '' === $source ) {
		return 0;
	}

	if ( preg_match( '#^https?://#', $source ) ) {
		$id = media_sideload_image( esc_url_raw( $source ), $post_id, $alt, 'id' );
	} else {
		$path = $base_dir ? trailingslashit( $base_dir ) . ltrim( $source, '/' ) : $source;
		$real = realpath( $path );
		if ( ! $real || ( $base_dir && ! str_starts_with( $real, realpath( $base_dir ) ) ) || ! is_readable( $real ) ) {
			return 0;
		}
		$tmp = wp_tempnam( basename( $real ) );
		copy( $real, $tmp );
		$id = media_handle_sideload(
			array(
				'name'     => basename( $real ),
				'tmp_name' => $tmp,
			),
			$post_id,
			$alt
		);
	}

	if ( is_wp_error( $id ) ) {
		return 0;
	}

	update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
	set_post_thumbnail( $post_id, $id );
	return (int) $id;
}

/**
 * Crée ou met à jour une question.
 *
 * @param array  $row      Données.
 * @param string $base_dir Dossier des images locales.
 * @param bool   $dry_run  Simulation.
 * @return int|\WP_Error ID de la question.
 */
function import_question( array $row, $base_dir = '', $dry_run = false ) {
	$title = trim( wp_strip_all_tags( (string) ( $row['title'] ?? '' ) ) );
	if ( '' === $title ) {
		return new \WP_Error( 'ld_import', __( 'Titre (énoncé) manquant.', 'learn-drive-core' ) );
	}

	$format  = in_array( $row['format'] ?? '', Questions\FORMATS, true ) ? $row['format'] : 'single';
	$answers = parse_answers( $row['answers'] ?? array(), $format );

	if ( in_array( $format, array( 'tf', 'single', 'multiple' ), true ) && ( count( $answers ) < 2 || ! array_filter( wp_list_pluck( $answers, 'correct' ) ) ) ) {
		/* translators: %s: question */
		return new \WP_Error( 'ld_import', sprintf( __( 'Propositions invalides : %s', 'learn-drive-core' ), $title ) );
	}

	if ( $dry_run ) {
		return 0;
	}

	$slug     = sanitize_title( $row['slug'] ?? '' );
	$existing = 0;
	if ( $slug ) {
		$found    = get_page_by_path( $slug, OBJECT, PT_QUESTION );
		$existing = $found ? $found->ID : 0;
	}
	if ( ! $existing && ! empty( $row['official_id'] ) ) {
		$found    = get_posts(
			array(
				'post_type'      => PT_QUESTION,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_ld_official_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => sanitize_text_field( $row['official_id'] ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		$existing = $found ? (int) $found[0] : 0;
	}

	$explanation = (string) ( $row['explanation'] ?? '' );
	if ( '' !== $explanation && ! str_contains( $explanation, '<!-- wp:' ) ) {
		$explanation = implode(
			"\n\n",
			array_map(
				static function ( $paragraph ) {
					return "<!-- wp:paragraph -->\n<p>" . wp_kses_post( trim( $paragraph ) ) . "</p>\n<!-- /wp:paragraph -->";
				},
				array_filter( preg_split( '/\n\s*\n/', $explanation ), 'strlen' )
			)
		);
	}

	$postarr = array(
		'post_type'    => PT_QUESTION,
		'post_title'   => $title,
		'post_name'    => $slug ? $slug : sanitize_title( wp_trim_words( $title, 10, '' ) ),
		'post_content' => $explanation,
		'post_status'  => $row['status'] ?? 'publish',
		'menu_order'   => (int) ( $row['order'] ?? 0 ),
		'ld_import'    => true,
	);
	if ( $existing ) {
		$postarr['ID'] = $existing;
	}

	$id = wp_insert_post( wp_slash( $postarr ), true );
	if ( is_wp_error( $id ) ) {
		return $id;
	}

	update_post_meta( $id, '_ld_format', $format );
	update_post_meta( $id, '_ld_answers', $answers );
	if ( 'numeric' === $format ) {
		$numeric = $row['numeric'] ?? array(
			'value'     => $row['numeric_value'] ?? 0,
			'tolerance' => $row['numeric_tolerance'] ?? 0,
			'unit'      => $row['numeric_unit'] ?? '',
		);
		update_post_meta(
			$id,
			'_ld_numeric',
			array(
				'value'     => (float) ( $numeric['value'] ?? 0 ),
				'tolerance' => (float) ( $numeric['tolerance'] ?? 0 ),
				'unit'      => sanitize_text_field( $numeric['unit'] ?? '' ),
			)
		);
	}

	foreach ( array( 'source', 'official_id', 'verified' ) as $key ) {
		if ( isset( $row[ $key ] ) ) {
			update_post_meta( $id, '_ld_' . $key, sanitize_text_field( $row[ $key ] ) );
		}
	}
	update_post_meta( $id, '_ld_difficulty', max( 1, min( 3, (int) ( $row['difficulty'] ?? 1 ) ) ) );
	update_post_meta( $id, '_ld_access', in_array( $row['access'] ?? '', array( 'free', 'registered', 'premium' ), true ) ? $row['access'] : 'inherit' );
	if ( ! empty( $row['eliminatory'] ) ) {
		update_post_meta( $id, '_ld_eliminatory', 1 );
	}

	if ( ! empty( $row['sheet'] ) ) {
		$sheet = get_page_by_path( sanitize_title( $row['sheet'] ), OBJECT, PT_SHEET );
		if ( $sheet ) {
			update_post_meta( $id, '_ld_sheet', $sheet->ID );
		}
	}

	$tracks = $row['tracks'] ?? ( $row['track'] ?? array() );
	$tracks = is_array( $tracks ) ? $tracks : explode( ',', (string) $tracks );
	wp_set_object_terms( $id, array_filter( array_map( static fn( $t ) => term_id( $t, TAX_TRACK ), $tracks ) ), TAX_TRACK );
	if ( ! empty( $row['topic'] ) ) {
		wp_set_object_terms( $id, array( term_id( $row['topic'], TAX_TOPIC ) ), TAX_TOPIC );
	}
	if ( ! empty( $row['country'] ) ) {
		wp_set_object_terms( $id, array( term_id( $row['country'], TAX_COUNTRY ) ), TAX_COUNTRY );
	}

	if ( ! empty( $row['image'] ) && ! get_post_thumbnail_id( $id ) ) {
		attach_image( $row['image'], $id, $row['image_alt'] ?? $title, $base_dir );
	}

	Questions\update_seo_flags( $id );

	return (int) $id;
}

/**
 * Lit un fichier ligne à ligne et importe chaque question.
 *
 * @param string        $file     Chemin.
 * @param array         $args     dry_run (bool), limit (int).
 * @param callable|null $progress Rappel après chaque ligne.
 * @return array{created:int,updated:int,errors:string[]}
 */
function import_file( $file, array $args = array(), $progress = null ) {
	$args   = wp_parse_args(
		$args,
		array(
			'dry_run' => false,
			'limit'   => 0,
		)
	);
	$report = array(
		'created' => 0,
		'updated' => 0,
		'errors'  => array(),
	);

	if ( ! is_readable( $file ) ) {
		$report['errors'][] = __( 'Fichier illisible.', 'learn-drive-core' );
		return $report;
	}

	$base_dir  = dirname( $file );
	$extension = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );

	wp_defer_term_counting( true );
	wp_suspend_cache_addition( true );
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 0 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- import long.
	}

	$handle_row = static function ( $row, $line ) use ( &$report, $args, $base_dir, $progress ) {
		$before = ! empty( $row['slug'] ) && get_page_by_path( sanitize_title( $row['slug'] ), OBJECT, PT_QUESTION );
		$result = import_question( $row, $base_dir, $args['dry_run'] );
		if ( is_wp_error( $result ) ) {
			/* translators: 1: line number, 2: error */
			$report['errors'][] = sprintf( __( 'Ligne %1$d : %2$s', 'learn-drive-core' ), $line, $result->get_error_message() );
		} elseif ( $before ) {
			++$report['updated'];
		} else {
			++$report['created'];
		}
		if ( $progress ) {
			call_user_func( $progress, $line );
		}
		// Libère la mémoire régulièrement.
		if ( 0 === $line % 200 ) {
			wp_cache_flush_runtime();
		}
	};

	$line = 0;
	if ( 'csv' === $extension ) {
		$fh     = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$header = fgetcsv( $fh, 0, detect_delimiter( $file ), '"', '\\' );
		$header = array_map( static fn( $h ) => sanitize_key( preg_replace( '/^\xEF\xBB\xBF/', '', (string) $h ) ), (array) $header );
		while ( ( $cells = fgetcsv( $fh, 0, detect_delimiter( $file ), '"', '\\' ) ) !== false ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			++$line;
			if ( array( null ) === $cells ) {
				continue;
			}
			$handle_row( array_combine( $header, array_pad( array_slice( $cells, 0, count( $header ) ), count( $header ), '' ) ), $line );
			if ( $args['limit'] && $line >= $args['limit'] ) {
				break;
			}
		}
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	} elseif ( 'jsonl' === $extension || 'ndjson' === $extension ) {
		$fh = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		while ( ( $raw = fgets( $fh ) ) !== false ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			++$line;
			$row = json_decode( $raw, true );
			if ( is_array( $row ) ) {
				$handle_row( $row, $line );
			}
			if ( $args['limit'] && $line >= $args['limit'] ) {
				break;
			}
		}
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	} else {
		$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$rows = isset( $data['questions'] ) ? $data['questions'] : $data;
		foreach ( (array) $rows as $row ) {
			++$line;
			if ( is_array( $row ) ) {
				$handle_row( $row, $line );
			}
			if ( $args['limit'] && $line >= $args['limit'] ) {
				break;
			}
		}
	}

	wp_suspend_cache_addition( false );
	wp_defer_term_counting( false );

	return $report;
}

/**
 * Séparateur d'un CSV (virgule ou point-virgule, fréquent en France).
 *
 * @param string $file Fichier.
 * @return string
 */
function detect_delimiter( $file ) {
	static $cache = array();
	if ( ! isset( $cache[ $file ] ) ) {
		$fh             = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$first          = (string) fgets( $fh );
		$cache[ $file ] = substr_count( $first, ';' ) > substr_count( $first, ',' ) ? ';' : ',';
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}
	return $cache[ $file ];
}

/**
 * Exporte toutes les questions (JSON Lines ou CSV), par lots.
 *
 * @param string $file   Chemin de sortie.
 * @param string $format jsonl|csv.
 * @return int Nombre de questions exportées.
 */
function export_file( $file, $format = 'jsonl' ) {
	$fh    = fopen( $file, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	$page  = 1;
	$count = 0;

	$columns = array( 'title', 'slug', 'format', 'answers', 'explanation', 'tracks', 'topic', 'country', 'difficulty', 'access', 'source', 'official_id', 'sheet', 'numeric_value', 'numeric_tolerance', 'numeric_unit', 'image', 'image_alt' );
	if ( 'csv' === $format ) {
		fputcsv( $fh, $columns, ',', '"', '\\' );
	}

	do {
		$ids = get_posts(
			array(
				'post_type'      => PT_QUESTION,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'paged'          => $page,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);
		foreach ( $ids as $id ) {
			$q     = Questions\get( $id );
			$image = $q['image_id'] ? wp_get_attachment_url( $q['image_id'] ) : '';
			$row   = array(
				'title'             => $q['title'],
				'slug'              => get_post_field( 'post_name', $id ),
				'format'            => $q['format'],
				'answers'           => $q['answers'],
				'explanation'       => $q['content'],
				'tracks'            => wp_get_post_terms( $id, TAX_TRACK, array( 'fields' => 'slugs' ) ),
				'topic'             => $q['topic'] ? $q['topic']->slug : '',
				'country'           => $q['country'] ? $q['country']->slug : '',
				'difficulty'        => $q['difficulty'],
				'access'            => get_post_meta( $id, '_ld_access', true ),
				'source'            => $q['source'],
				'official_id'       => $q['official_id'],
				'sheet'             => $q['sheet'] ? get_post_field( 'post_name', $q['sheet'] ) : '',
				'numeric_value'     => 'numeric' === $q['format'] ? $q['numeric']['value'] : '',
				'numeric_tolerance' => 'numeric' === $q['format'] ? $q['numeric']['tolerance'] : '',
				'numeric_unit'      => 'numeric' === $q['format'] ? $q['numeric']['unit'] : '',
				'image'             => $image,
				'image_alt'         => $q['image_id'] ? get_post_meta( $q['image_id'], '_wp_attachment_image_alt', true ) : '',
			);
			if ( 'csv' === $format ) {
				$row['answers'] = implode(
					'|',
					array_map(
						static fn( $a ) => $a['text'] . ( $a['correct'] ? '*' : '' ),
						$q['answers']
					)
				);
				$row['tracks']  = implode( ',', $row['tracks'] );
				fputcsv( $fh, array_values( $row ), ',', '"', '\\' );
			} else {
				fwrite( $fh, wp_json_encode( $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			}
			++$count;
		}
		++$page;
		wp_cache_flush_runtime();
		$batch = count( $ids );
	} while ( 200 === $batch );

	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	return $count;
}
