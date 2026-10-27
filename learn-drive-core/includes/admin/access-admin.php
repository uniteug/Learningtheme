<?php
/**
 * Écran « Accès » : arbre gratuit / payant, accès ouverts, attribution
 * manuelle et génération de codes d'accès (vente en agence, auto-écoles).
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Admin\Access_Admin;

use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\PT_COURSE;
use const LearnDrive\Core\PT_QUIZ;
use LearnDrive\Core\Access;

defined( 'ABSPATH' ) || exit;

/**
 * Menu.
 */
function menu() {
	add_submenu_page( 'learn-drive', __( 'Accès et codes', 'learn-drive-core' ), __( 'Accès et codes', 'learn-drive-core' ), 'manage_options', 'learn-drive-access', __NAMESPACE__ . '\\render' );
}
add_action( 'admin_menu', __NAMESPACE__ . '\\menu', 30 );

/**
 * Pastille de règle d'accès.
 *
 * @param string $rule Règle.
 * @return string
 */
function tag( $rule ) {
	$labels = array(
		'free'       => __( 'Gratuit', 'learn-drive-core' ),
		'registered' => __( 'Inscription', 'learn-drive-core' ),
		'premium'    => __( 'Premium', 'learn-drive-core' ),
	);
	return sprintf( '<span class="ld-tag ld-tag--%1$s">%2$s</span>', esc_attr( $rule ), esc_html( $labels[ $rule ] ?? $rule ) );
}

/**
 * Affiche l'écran.
 */
function render() {
	global $wpdb;

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$notice = '';
	$codes  = array();
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- vérifié par check_admin_referer.
	if ( isset( $_POST['ld_grant_user'] ) && check_admin_referer( 'ld_access' ) ) {
		$user = get_user_by( 'email', sanitize_email( wp_unslash( $_POST['ld_grant_user'] ) ) );
		if ( $user ) {
			Access\grant( $user->ID, sanitize_text_field( wp_unslash( $_POST['ld_offer'] ?? 'all' ) ), absint( $_POST['ld_days'] ?? 0 ), 'admin' );
			$notice = __( 'Accès ouvert.', 'learn-drive-core' );
		} else {
			$notice = __( 'Aucun compte avec cette adresse.', 'learn-drive-core' );
		}
	}
	if ( isset( $_POST['ld_codes_count'] ) && check_admin_referer( 'ld_access' ) ) {
		$codes  = Access\generate_codes( absint( $_POST['ld_codes_count'] ), sanitize_text_field( wp_unslash( $_POST['ld_offer'] ?? 'all' ) ), absint( $_POST['ld_days'] ?? 0 ), absint( $_POST['ld_uses'] ?? 1 ), sanitize_text_field( wp_unslash( $_POST['ld_group'] ?? '' ) ) );
		/* translators: %d: number of codes */
		$notice = sprintf( __( '%d codes générés.', 'learn-drive-core' ), count( $codes ) );
	}
	// phpcs:enable

	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC LIMIT 50', Access\table() ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Accès et codes', 'learn-drive-core' ); ?></h1>
		<?php if ( $notice ) : ?>
			<div class="notice notice-info"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>
		<?php if ( $codes ) : ?>
			<textarea class="large-text code" rows="6" readonly><?php echo esc_textarea( implode( "\n", $codes ) ); ?></textarea>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Ce qui est gratuit ou payant, et pourquoi', 'learn-drive-core' ); ?></h2>
		<ul class="ld-tree">
			<?php
			$tracks = get_terms(
				array(
					'taxonomy'   => TAX_TRACK,
					'hide_empty' => false,
				)
			);
			foreach ( is_wp_error( $tracks ) ? array() : $tracks as $track ) {
				$rule = (string) get_term_meta( $track->term_id, 'ld_access', true );
				printf( '<li><strong>%s</strong> %s', esc_html( $track->name ), $rule ? tag( $rule ) : '<span class="ld-tag">' . esc_html__( 'hérite du réglage global', 'learn-drive-core' ) . '</span>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$items = get_posts(
					array(
						'post_type'      => array( PT_COURSE, PT_QUIZ ),
						'posts_per_page' => 30,
						'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
							array(
								'taxonomy' => TAX_TRACK,
								'terms'    => $track->term_id,
							),
						),
					)
				);
				if ( $items ) {
					echo '<ul>';
					foreach ( $items as $item ) {
						printf( '<li><a href="%s">%s</a> %s</li>', esc_url( get_edit_post_link( $item ) ), esc_html( $item->post_title ), tag( Access\rule( $item->ID ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					echo '</ul>';
				}
				echo '</li>';
			}
			?>
		</ul>

		<div class="ld-cards">
			<form class="card" method="post">
				<h2><?php esc_html_e( 'Ouvrir un accès', 'learn-drive-core' ); ?></h2>
				<?php wp_nonce_field( 'ld_access' ); ?>
				<p><input type="email" name="ld_grant_user" class="regular-text" placeholder="<?php esc_attr_e( 'E-mail du compte', 'learn-drive-core' ); ?>" required></p>
				<p><input type="text" name="ld_offer" value="all" aria-label="<?php esc_attr_e( 'Offre', 'learn-drive-core' ); ?>"> <input type="number" name="ld_days" min="0" value="30" aria-label="<?php esc_attr_e( 'Jours', 'learn-drive-core' ); ?>"> <?php esc_html_e( 'jours (0 = illimité)', 'learn-drive-core' ); ?></p>
				<?php submit_button( __( 'Ouvrir', 'learn-drive-core' ), 'primary', 'submit', false ); ?>
			</form>
			<form class="card" method="post">
				<h2><?php esc_html_e( 'Générer des codes d’accès', 'learn-drive-core' ); ?></h2>
				<?php wp_nonce_field( 'ld_access' ); ?>
				<p><label><?php esc_html_e( 'Nombre', 'learn-drive-core' ); ?> <input type="number" name="ld_codes_count" min="1" max="500" value="10"></label></p>
				<p><label><?php esc_html_e( 'Offre', 'learn-drive-core' ); ?> <input type="text" name="ld_offer" value="all"></label></p>
				<p><label><?php esc_html_e( 'Durée (jours)', 'learn-drive-core' ); ?> <input type="number" name="ld_days" min="0" value="90"></label></p>
				<p><label><?php esc_html_e( 'Utilisations par code', 'learn-drive-core' ); ?> <input type="number" name="ld_uses" min="1" value="1"></label></p>
				<p><label><?php esc_html_e( 'Groupe (auto-école, classe)', 'learn-drive-core' ); ?> <input type="text" name="ld_group"></label></p>
				<?php submit_button( __( 'Générer', 'learn-drive-core' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>

		<h2><?php esc_html_e( 'Derniers accès ouverts', 'learn-drive-core' ); ?></h2>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Utilisateur', 'learn-drive-core' ); ?></th><th><?php esc_html_e( 'Offre', 'learn-drive-core' ); ?></th><th><?php esc_html_e( 'Début', 'learn-drive-core' ); ?></th><th><?php esc_html_e( 'Fin', 'learn-drive-core' ); ?></th><th><?php esc_html_e( 'Origine', 'learn-drive-core' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( (array) $rows as $row ) : ?>
					<?php $user = get_userdata( (int) $row->user_id ); ?>
					<tr>
						<td><?php echo esc_html( $user ? $user->user_email : '#' . $row->user_id ); ?></td>
						<td><code><?php echo esc_html( $row->offer ); ?></code></td>
						<td><?php echo esc_html( $row->starts_at ); ?></td>
						<td><?php echo esc_html( $row->ends_at ? $row->ends_at : '∞' ); ?></td>
						<td><?php echo esc_html( $row->source ); ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if ( ! $rows ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'Aucun accès pour le moment.', 'learn-drive-core' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}
