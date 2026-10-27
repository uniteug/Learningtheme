<?php
/**
 * Commandes WP-CLI : wp learn import|export|demo|og.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\CLI;

use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Gère les banques de questions Learn & Drive.
 */
class Command {

	/**
	 * Importe des questions (CSV, JSON, JSONL).
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Fichier à importer.
	 *
	 * [--dry-run]
	 * : Vérifie le fichier sans rien écrire.
	 *
	 * [--limit=<n>]
	 * : Nombre maximum de lignes.
	 *
	 * ## EXAMPLES
	 *
	 *     wp learn import questions.csv
	 *
	 * @param array $args       Arguments.
	 * @param array $assoc_args Options.
	 */
	public function import( $args, $assoc_args ) {
		$report = \LearnDrive\Core\Import\import_file(
			$args[0],
			array(
				'dry_run' => isset( $assoc_args['dry-run'] ),
				'limit'   => (int) ( $assoc_args['limit'] ?? 0 ),
			),
			static function ( $line ) {
				if ( 0 === $line % 100 ) {
					WP_CLI::log( "… {$line}" );
				}
			}
		);
		foreach ( $report['errors'] as $error ) {
			WP_CLI::warning( $error );
		}
		WP_CLI::success( sprintf( '%d créées, %d mises à jour, %d erreurs.', $report['created'], $report['updated'], count( $report['errors'] ) ) );
	}

	/**
	 * Exporte toutes les questions.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Fichier de sortie (.csv ou .jsonl).
	 *
	 * @param array $args Arguments.
	 */
	public function export( $args ) {
		$format = str_ends_with( $args[0], '.csv' ) ? 'csv' : 'jsonl';
		$count  = \LearnDrive\Core\Import\export_file( $args[0], $format );
		WP_CLI::success( "{$count} questions exportées." );
	}

	/**
	 * Importe le contenu de démonstration.
	 */
	public function demo() {
		$count = \LearnDrive\Core\Demo\import(
			static function ( $step ) {
				WP_CLI::log( $step );
			}
		);
		flush_rewrite_rules( false );
		WP_CLI::success( wp_json_encode( $count ) );
	}

	/**
	 * Régénère les images Open Graph des quiz et questions.
	 */
	public function og() {
		$ids = get_posts(
			array(
				'post_type'      => array( \LearnDrive\Core\PT_QUIZ, \LearnDrive\Core\PT_QUESTION, \LearnDrive\Core\PT_LESSON ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		foreach ( $ids as $id ) {
			\LearnDrive\Core\SEO\OG\generate( $id );
		}
		WP_CLI::success( count( $ids ) . ' images.' );
	}
}

WP_CLI::add_command( 'learn', __NAMESPACE__ . '\\Command' );
