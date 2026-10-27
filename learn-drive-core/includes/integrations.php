<?php
/**
 * Intégrations : WooCommerce (offres d'accès), webhooks (CRM, e-mailing) et
 * profil auteur enrichi (E-E-A-T).
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Integrations;

use LearnDrive\Core\Access;
use function LearnDrive\Core\setting;

defined( 'ABSPATH' ) || exit;

/*
 * WooCommerce : chaque produit peut ouvrir une offre d'accès.
 */

/**
 * Champs « Offre d'accès » dans l'onglet Général du produit.
 */
function product_fields() {
	echo '<div class="options_group">';
	woocommerce_wp_text_input(
		array(
			'id'          => '_ld_offer',
			'label'       => __( 'Offre d’accès Learn & Drive', 'learn-drive-core' ),
			'placeholder' => 'all, track:permis-b, post:123',
			'desc_tip'    => true,
			'description' => __( '« all » pour tout le contenu premium, « track:slug » pour une catégorie, « post:ID » pour un cours.', 'learn-drive-core' ),
		)
	);
	woocommerce_wp_text_input(
		array(
			'id'                => '_ld_days',
			'label'             => __( 'Durée d’accès (jours)', 'learn-drive-core' ),
			'type'              => 'number',
			'custom_attributes' => array( 'min' => '0' ),
			'desc_tip'          => true,
			'description'       => __( '0 = accès illimité. Pour un abonnement, l’accès suit l’état de l’abonnement.', 'learn-drive-core' ),
		)
	);
	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', __NAMESPACE__ . '\\product_fields' );

/**
 * Enregistre les champs du produit.
 *
 * @param \WC_Product $product Produit.
 */
function save_product_fields( $product ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce vérifié par WooCommerce.
	if ( isset( $_POST['_ld_offer'] ) ) {
		$product->update_meta_data( '_ld_offer', sanitize_text_field( wp_unslash( $_POST['_ld_offer'] ) ) );
	}
	if ( isset( $_POST['_ld_days'] ) ) {
		$product->update_meta_data( '_ld_days', absint( $_POST['_ld_days'] ) );
	}
	// phpcs:enable
}
add_action( 'woocommerce_admin_process_product_object', __NAMESPACE__ . '\\save_product_fields' );

/**
 * Commande payée : ouverture des accès.
 *
 * @param int $order_id Commande.
 */
function order_paid( $order_id ) {
	$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
	if ( ! $order || ! $order->get_user_id() || $order->get_meta( '_ld_granted' ) ) {
		return;
	}

	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( ! $product ) {
			continue;
		}
		$offer = (string) $product->get_meta( '_ld_offer' );
		if ( '' === $offer && $product->get_parent_id() ) {
			$parent = wc_get_product( $product->get_parent_id() );
			$offer  = $parent ? (string) $parent->get_meta( '_ld_offer' ) : '';
		}
		if ( '' !== $offer ) {
			Access\grant( $order->get_user_id(), $offer, (int) $product->get_meta( '_ld_days' ), 'wc:' . $order_id );
		}
	}

	$order->update_meta_data( '_ld_granted', 1 );
	$order->save();
}
add_action( 'woocommerce_order_status_completed', __NAMESPACE__ . '\\order_paid' );
add_action( 'woocommerce_order_status_processing', __NAMESPACE__ . '\\order_paid' );

/**
 * Remboursement ou annulation : fermeture des accès.
 *
 * @param int $order_id Commande.
 */
function order_revoked( $order_id ) {
	Access\revoke_source( 'wc:' . (int) $order_id );
	$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
	if ( $order ) {
		$order->delete_meta_data( '_ld_granted' );
		$order->save();
	}
}
add_action( 'woocommerce_order_status_refunded', __NAMESPACE__ . '\\order_revoked' );
add_action( 'woocommerce_order_status_cancelled', __NAMESPACE__ . '\\order_revoked' );

/*
 * Webhooks : inscription, achat (accès ouvert), quiz terminé.
 */

/**
 * Envoie un webhook signé (HMAC SHA-256), sans bloquer la page.
 *
 * @param string $event   Événement.
 * @param array  $payload Données.
 */
function send_webhook( $event, array $payload ) {
	$url = (string) setting( 'webhook_url' );
	if ( '' === $url || ! wp_http_validate_url( $url ) ) {
		return;
	}

	$body = wp_json_encode(
		array(
			'event' => $event,
			'site'  => home_url( '/' ),
			'time'  => gmdate( 'c' ),
			'data'  => $payload,
		)
	);

	wp_remote_post(
		$url,
		array(
			'blocking' => false,
			'timeout'  => 3,
			'headers'  => array(
				'Content-Type'      => 'application/json',
				'X-Learn-Event'     => $event,
				'X-Learn-Signature' => hash_hmac( 'sha256', $body, (string) setting( 'webhook_secret' ) ),
			),
			'body'     => $body,
		)
	);
}

/**
 * Webhook d'inscription.
 *
 * @param int $user_id Utilisateur.
 */
function on_register( $user_id ) {
	$user = get_userdata( $user_id );
	send_webhook(
		'user.registered',
		array(
			'user_id' => $user_id,
			'email'   => $user ? $user->user_email : '',
		)
	);
}
add_action( 'user_register', __NAMESPACE__ . '\\on_register' );

/**
 * Webhook d'accès ouvert (achat, code).
 *
 * @param int    $user_id Utilisateur.
 * @param string $offer   Offre.
 * @param int    $days    Durée.
 * @param string $source  Origine.
 */
function on_access( $user_id, $offer, $days, $source ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- utilisés par compact().
	send_webhook( 'access.granted', compact( 'user_id', 'offer', 'days', 'source' ) );
}
add_action( 'learn_drive_access_granted', __NAMESPACE__ . '\\on_access', 10, 4 );

/**
 * Webhook de quiz terminé (seulement pour les comptes : pas de données de visiteurs).
 *
 * @param array $result  Résultat.
 * @param int   $user_id Utilisateur.
 */
function on_quiz( $result, $user_id ) {
	if ( ! $user_id ) {
		return;
	}
	send_webhook(
		'quiz.completed',
		array(
			'user_id' => $user_id,
			'quiz_id' => $result['quiz'],
			'correct' => $result['correct'],
			'total'   => $result['total'],
			'passed'  => $result['passed'],
		)
	);
}
add_action( 'learn_drive_quiz_completed', __NAMESPACE__ . '\\on_quiz', 10, 2 );

/*
 * Profil auteur : fonction, compétences, profils publics (sameAs).
 */

/**
 * Champs du profil.
 *
 * @param \WP_User $user Utilisateur.
 */
function profile_fields( $user ) {
	if ( ! current_user_can( 'edit_user', $user->ID ) ) {
		return;
	}
	?>
	<h2><?php esc_html_e( 'Profil d’auteur (Learn & Drive)', 'learn-drive-core' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="ld_job_title"><?php esc_html_e( 'Fonction', 'learn-drive-core' ); ?></label></th>
			<td><input type="text" class="regular-text" id="ld_job_title" name="ld_job_title" value="<?php echo esc_attr( get_user_meta( $user->ID, 'ld_job_title', true ) ); ?>" placeholder="<?php esc_attr_e( 'Moniteur diplômé, juriste…', 'learn-drive-core' ); ?>"></td>
		</tr>
		<tr>
			<th><label for="ld_expertise"><?php esc_html_e( 'Compétences', 'learn-drive-core' ); ?></label></th>
			<td><input type="text" class="regular-text" id="ld_expertise" name="ld_expertise" value="<?php echo esc_attr( get_user_meta( $user->ID, 'ld_expertise', true ) ); ?>"><p class="description"><?php esc_html_e( 'Séparées par des virgules.', 'learn-drive-core' ); ?></p></td>
		</tr>
		<tr>
			<th><label for="ld_same_as"><?php esc_html_e( 'Profils publics', 'learn-drive-core' ); ?></label></th>
			<td><textarea class="large-text" rows="3" id="ld_same_as" name="ld_same_as"><?php echo esc_textarea( get_user_meta( $user->ID, 'ld_same_as', true ) ); ?></textarea><p class="description"><?php esc_html_e( 'Une URL par ligne (LinkedIn, site professionnel…).', 'learn-drive-core' ); ?></p></td>
		</tr>
	</table>
	<?php
	wp_nonce_field( 'ld_profile', 'ld_profile_nonce' );
}
add_action( 'show_user_profile', __NAMESPACE__ . '\\profile_fields' );
add_action( 'edit_user_profile', __NAMESPACE__ . '\\profile_fields' );

/**
 * Enregistre le profil.
 *
 * @param int $user_id Utilisateur.
 */
function save_profile( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) || ! isset( $_POST['ld_profile_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ld_profile_nonce'] ), 'ld_profile' ) ) {
		return;
	}
	update_user_meta( $user_id, 'ld_job_title', sanitize_text_field( wp_unslash( $_POST['ld_job_title'] ?? '' ) ) );
	update_user_meta( $user_id, 'ld_expertise', sanitize_text_field( wp_unslash( $_POST['ld_expertise'] ?? '' ) ) );
	$urls = array_filter( array_map( 'esc_url_raw', array_map( 'trim', preg_split( '/[\r\n]+/', wp_unslash( $_POST['ld_same_as'] ?? '' ) ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- URL nettoyées une par une.
	update_user_meta( $user_id, 'ld_same_as', implode( "\n", $urls ) );
}
add_action( 'personal_options_update', __NAMESPACE__ . '\\save_profile' );
add_action( 'edit_user_profile_update', __NAMESPACE__ . '\\save_profile' );

/*
 * Avatars locaux : aucune requête vers Gravatar (domaine tiers).
 */

/**
 * Remplace Gravatar par la photo envoyée dans la médiathèque (méta
 * « ld_avatar ») ou, à défaut, par un avatar à initiales servi par le site.
 *
 * @param array $args        Arguments de l'avatar.
 * @param mixed $id_or_email Utilisateur, ID, e-mail ou commentaire.
 * @return array
 */
function local_avatar( $args, $id_or_email ) {
	if ( ! setting( 'local_avatars' ) ) {
		return $args;
	}

	$user = false;
	if ( $id_or_email instanceof \WP_User ) {
		$user = $id_or_email;
	} elseif ( is_numeric( $id_or_email ) ) {
		$user = get_userdata( (int) $id_or_email );
	} elseif ( $id_or_email instanceof \WP_Comment ) {
		$user = $id_or_email->user_id ? get_userdata( (int) $id_or_email->user_id ) : false;
	} elseif ( is_string( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
	}

	$photo = $user ? (int) get_user_meta( $user->ID, 'ld_avatar', true ) : 0;
	if ( $photo ) {
		$src = wp_get_attachment_image_src( $photo, array( (int) $args['size'] * 2, (int) $args['size'] * 2 ) );
		if ( $src ) {
			$args['url']          = $src[0];
			$args['found_avatar'] = true;
			return $args;
		}
	}

	$name                 = $user ? $user->display_name : ( $id_or_email instanceof \WP_Comment ? $id_or_email->comment_author : __( 'Invité', 'learn-drive-core' ) );
	$args['url']          = home_url( '/ld-avatar/' . rawurlencode( initials( $name ) ) . '-' . substr( md5( $name ), 0, 6 ) . '.svg' );
	$args['found_avatar'] = true;
	return $args;
}
add_filter( 'pre_get_avatar_data', __NAMESPACE__ . '\\local_avatar', 10, 2 );

/**
 * Initiales d'un nom (2 lettres maximum).
 *
 * @param string $name Nom.
 * @return string
 */
function initials( $name ) {
	$parts    = preg_split( '/[\s\-_.]+/u', trim( remove_accents( (string) $name ) ), -1, PREG_SPLIT_NO_EMPTY );
	$initials = '';
	foreach ( array_slice( $parts, 0, 2 ) as $part ) {
		$initials .= mb_strtoupper( mb_substr( $part, 0, 1 ) );
	}
	return preg_replace( '/[^A-Z0-9]/', '', $initials ) ?: 'LD'; // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
}

/**
 * Route /ld-avatar/{initiales}-{couleur}.svg.
 */
function avatar_rewrite() {
	add_rewrite_rule( '^ld-avatar/([A-Z0-9]{1,2})-([a-f0-9]{6})\.svg$', 'index.php?ld_avatar=$matches[1]&ld_avatar_color=$matches[2]', 'top' );
}
add_action( 'init', __NAMESPACE__ . '\\avatar_rewrite' );

/**
 * Variables de requête de l'avatar.
 *
 * @param string[] $vars Variables.
 * @return string[]
 */
function avatar_query_vars( $vars ) {
	$vars[] = 'ld_avatar';
	$vars[] = 'ld_avatar_color';
	return $vars;
}
add_filter( 'query_vars', __NAMESPACE__ . '\\avatar_query_vars' );

/**
 * Sert l'avatar SVG à initiales.
 */
function serve_avatar() {
	$letters = (string) get_query_var( 'ld_avatar' );
	if ( '' === $letters || ! preg_match( '/^[A-Z0-9]{1,2}$/', $letters ) ) {
		return;
	}
	$hue = hexdec( substr( (string) get_query_var( 'ld_avatar_color' ), 0, 2 ) ) * 360 / 255;
	status_header( 200 );
	header( 'Content-Type: image/svg+xml' );
	header( 'Cache-Control: public, max-age=31536000, immutable' );
	printf(
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96"><rect width="96" height="96" fill="hsl(%1$d 55%% 38%%)"/><text x="48" y="50" dy=".35em" text-anchor="middle" font-family="system-ui,sans-serif" font-size="38" font-weight="700" fill="#fff">%2$s</text></svg>',
		(int) $hue,
		esc_html( $letters )
	);
	exit;
}
add_action( 'template_redirect', __NAMESPACE__ . '\\serve_avatar', 0 );
