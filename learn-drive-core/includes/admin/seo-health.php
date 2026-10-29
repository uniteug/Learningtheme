<?php
/**
 * Écran « Santé SEO » : questions sans explication, images sans texte
 * alternatif, titres trop longs, contenus sans lien entrant, doublons,
 * signalements, erreurs 404 et redirections.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Admin\Health;

use LearnDrive\Core\SEO\Redirects;
use function LearnDrive\Core\content_types;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\PT_SHEET;

defined( 'ABSPATH' ) || exit;

/**
 * Menu.
 */
function menu() {
	add_submenu_page( 'learn-drive', __( 'Santé SEO', 'learn-drive-core' ), __( 'Santé SEO', 'learn-drive-core' ), 'edit_others_posts', 'learn-drive-health', __NAMESPACE__ . '\\render' );
}
add_action( 'admin_menu', __NAMESPACE__ . '\\menu', 20 );

/**
 * Lien de modification.
 *
 * @param int $post_id Contenu.
 * @return string
 */
function edit_link( $post_id ) {
	return sprintf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $post_id ) ), esc_html( get_the_title( $post_id ) ? get_the_title( $post_id ) : '#' . $post_id ) );
}

/**
 * Diagnostics (limités pour rester rapides sur les gros sites).
 *
 * @return array
 */
function checks() {
	global $wpdb;

	$thin = get_posts(
		array(
			'post_type'      => PT_QUESTION,
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'meta_key'       => '_ld_noindex', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		)
	);

	$long_titles = array();
	$no_alt      = array();
	$posts       = get_posts(
		array(
			'post_type'      => content_types(),
			'posts_per_page' => 500,
			'orderby'        => 'modified',
		)
	);
	foreach ( $posts as $post ) {
		$title = \LearnDrive\Core\SEO\post_title( $post->ID );
		if ( mb_strlen( $title ) > 65 ) {
			$long_titles[ $post->ID ] = mb_strlen( $title );
		}
		if ( in_array( $post->post_type, array( PT_QUESTION, PT_SHEET, 'post' ), true ) && \LearnDrive\Core\Media\featured_alt_missing( $post->ID ) ) {
			$no_alt[] = $post->ID;
		}
	}

	$duplicates = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"SELECT post_title, GROUP_CONCAT(ID) AS ids FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' GROUP BY post_title HAVING COUNT(*) > 1 LIMIT 50",
			PT_QUESTION
		)
	);

	// Contenus sans lien entrant (échantillon des 150 derniers contenus).
	$orphans = array();
	foreach ( array_slice( $posts, 0, 150 ) as $post ) {
		$path = wp_parse_url( get_permalink( $post ), PHP_URL_PATH );
		if ( ! $path || '/' === $path ) {
			continue;
		}
		$found = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND ID <> %d AND post_content LIKE %s",
				$post->ID,
				'%' . $wpdb->esc_like( $path ) . '%'
			)
		);
		if ( ! $found && ! has_term( '', \LearnDrive\Core\TAX_TOPIC, $post ) && ! has_term( '', \LearnDrive\Core\TAX_TRACK, $post ) ) {
			$orphans[] = $post->ID;
		}
	}

	$reported = get_posts(
		array(
			'post_type'      => PT_QUESTION,
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'meta_key'       => '_ld_report_count', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
		)
	);

	return compact( 'thin', 'long_titles', 'no_alt', 'duplicates', 'orphans', 'reported' );
}

/**
 * Affiche l'écran.
 */
function render() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- vérifié par check_admin_referer.
	if ( isset( $_POST['ld_redirect_from'], $_POST['ld_redirect_to'] ) && check_admin_referer( 'ld_redirect' ) && current_user_can( 'manage_options' ) ) {
		Redirects\add( sanitize_text_field( wp_unslash( $_POST['ld_redirect_from'] ) ), esc_url_raw( wp_unslash( $_POST['ld_redirect_to'] ) ) );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Redirection enregistrée.', 'learn-drive-core' ) . '</p></div>';
	}
	if ( isset( $_POST['ld_redirect_remove'] ) && check_admin_referer( 'ld_redirect' ) && current_user_can( 'manage_options' ) ) {
		Redirects\remove( sanitize_text_field( wp_unslash( $_POST['ld_redirect_remove'] ) ) );
	}
	// phpcs:enable

	$checks = checks();
	$total  = count( $checks['thin'] ) + count( $checks['long_titles'] ) + count( $checks['no_alt'] ) + count( $checks['duplicates'] ) + count( $checks['orphans'] );
	$log    = get_option( Redirects\OPTION_LOG, array() );
	$log    = is_array( $log ) ? $log : array();
	uasort(
		$log,
		static function ( $a, $b ) {
			return $b['count'] <=> $a['count'];
		}
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Santé SEO', 'learn-drive-core' ); ?></h1>
		<div class="ld-cards">
			<div class="card"><p class="ld-health-score"><?php echo esc_html( (string) $total ); ?></p><p><?php esc_html_e( 'points à corriger', 'learn-drive-core' ); ?></p></div>
			<div class="card"><p class="ld-health-score"><?php echo esc_html( (string) count( $checks['thin'] ) ); ?></p><p><?php esc_html_e( 'questions non indexées (explication trop courte ou doublon)', 'learn-drive-core' ); ?></p></div>
			<div class="card"><p class="ld-health-score"><?php echo esc_html( (string) count( $log ) ); ?></p><p><?php esc_html_e( 'URL en erreur 404', 'learn-drive-core' ); ?></p></div>
		</div>
		<p><?php esc_html_e( 'Connectez Google Search Console et Bing Webmaster Tools en y déclarant /wp-sitemap.xml et /news-sitemap.xml pour suivre clics et impressions par contenu.', 'learn-drive-core' ); ?></p>

		<?php
		section(
			__( 'Questions sans explication suffisante (noindex automatique)', 'learn-drive-core' ),
			array_map(
				static function ( $id ) {
					/* translators: %d: number of words */
					return edit_link( $id ) . ' — ' . esc_html( sprintf( __( '%d mots', 'learn-drive-core' ), (int) get_post_meta( $id, '_ld_words', true ) ) );
				},
				$checks['thin']
			)
		);
		$excluded = \LearnDrive\Core\SEO\Index\report();
		section(
			__( 'Pages exclues de l’index : contenu trop court (complétez-les pour qu’elles soient indexées)', 'learn-drive-core' ),
			array_map(
				static function ( $id ) {
					/* translators: %d: number of words */
					return edit_link( $id ) . ' — ' . esc_html( sprintf( __( '%d mots', 'learn-drive-core' ), (int) get_post_meta( $id, '_ld_words', true ) ) );
				},
				$excluded['posts']
			)
		);
		section(
			__( 'Rubriques, thèmes et catégories exclus de l’index : trop peu de contenus et pas de texte d’introduction', 'learn-drive-core' ),
			array_map(
				static function ( $term ) {
					/* translators: %d: number of items */
					return '<a href="' . esc_url( get_edit_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a> — ' . esc_html( sprintf( _n( '%d contenu', '%d contenus', $term->count, 'learn-drive-core' ), $term->count ) );
				},
				$excluded['terms']
			)
		);
		section( __( 'Images principales sans texte alternatif', 'learn-drive-core' ), array_map( __NAMESPACE__ . '\\edit_link', $checks['no_alt'] ) );
		section(
			__( 'Titres SEO trop longs (plus de 65 caractères)', 'learn-drive-core' ),
			array_map(
				static function ( $id, $length ) {
					/* translators: %d: characters */
					return edit_link( $id ) . ' — ' . esc_html( sprintf( __( '%d caractères', 'learn-drive-core' ), $length ) );
				},
				array_keys( $checks['long_titles'] ),
				$checks['long_titles']
			)
		);
		section(
			__( 'Quasi-doublons (même énoncé) : choisissez une question canonique', 'learn-drive-core' ),
			array_map(
				static function ( $row ) {
					return esc_html( $row->post_title ) . ' — ' . implode( ', ', array_map( __NAMESPACE__ . '\\edit_link', array_map( 'intval', explode( ',', $row->ids ) ) ) );
				},
				$checks['duplicates']
			)
		);
		section( __( 'Pages orphelines (aucun lien entrant ni rattachement)', 'learn-drive-core' ), array_map( __NAMESPACE__ . '\\edit_link', $checks['orphans'] ) );
		section(
			__( 'Questions signalées par les apprenants', 'learn-drive-core' ),
			array_map(
				static function ( $id ) {
					/* translators: %d: number of reports */
					return edit_link( $id ) . ' — ' . esc_html( sprintf( __( '%d signalement(s)', 'learn-drive-core' ), (int) get_post_meta( $id, '_ld_report_count', true ) ) );
				},
				$checks['reported']
			)
		);
		?>

		<h2><?php esc_html_e( 'Erreurs 404 et redirections', 'learn-drive-core' ); ?></h2>
		<?php if ( current_user_can( 'manage_options' ) ) : ?>
			<form method="post">
				<?php wp_nonce_field( 'ld_redirect' ); ?>
				<input type="text" name="ld_redirect_from" placeholder="/ancienne-url/" required>
				→ <input type="url" name="ld_redirect_to" class="regular-text" placeholder="<?php echo esc_attr( home_url( '/nouvelle-url/' ) ); ?>" required>
				<?php submit_button( __( 'Ajouter la redirection 301', 'learn-drive-core' ), 'secondary', 'submit', false ); ?>
			</form>
		<?php endif; ?>
		<table class="widefat striped ld-health-table">
			<thead><tr><th><?php esc_html_e( 'URL', 'learn-drive-core' ); ?></th><th><?php esc_html_e( 'Visites', 'learn-drive-core' ); ?></th><th><?php esc_html_e( 'Dernière', 'learn-drive-core' ); ?></th><th><?php esc_html_e( 'Provenance', 'learn-drive-core' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( array_slice( $log, 0, 50, true ) as $path => $entry ) : ?>
				<tr><td><code><?php echo esc_html( $path ); ?></code></td><td><?php echo esc_html( (string) $entry['count'] ); ?></td><td><?php echo esc_html( wp_date( 'd/m/Y H:i', $entry['last'] ) ); ?></td><td><?php echo esc_html( $entry['referer'] ?? '—' ); ?></td></tr>
			<?php endforeach; ?>
			<?php if ( ! $log ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'Aucune erreur 404 enregistrée.', 'learn-drive-core' ); ?></td></tr>
			<?php endif; ?>
			</tbody>
		</table>
		<?php $redirects = Redirects\all(); ?>
		<?php if ( $redirects ) : ?>
			<h3><?php esc_html_e( 'Redirections actives', 'learn-drive-core' ); ?></h3>
			<form method="post">
				<?php wp_nonce_field( 'ld_redirect' ); ?>
				<ul>
					<?php foreach ( $redirects as $from => $to ) : ?>
						<li><code><?php echo esc_html( $from ); ?></code> → <a href="<?php echo esc_url( $to ); ?>"><?php echo esc_html( $to ); ?></a> <button class="button-link-delete" name="ld_redirect_remove" value="<?php echo esc_attr( $from ); ?>"><?php esc_html_e( 'Supprimer', 'learn-drive-core' ); ?></button></li>
					<?php endforeach; ?>
				</ul>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Une section de diagnostic.
 *
 * @param string   $title Titre.
 * @param string[] $items Éléments (HTML échappé).
 */
function section( $title, array $items ) {
	printf( '<h2>%s <span class="ld-tag">%d</span></h2>', esc_html( $title ), count( $items ) );
	if ( ! $items ) {
		echo '<p>✓ ' . esc_html__( 'Rien à signaler.', 'learn-drive-core' ) . '</p>';
		return;
	}
	echo '<ul>';
	foreach ( array_slice( $items, 0, 50 ) as $item ) {
		echo '<li>' . wp_kses_post( $item ) . '</li>';
	}
	echo '</ul>';
}
