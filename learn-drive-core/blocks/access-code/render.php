<?php
/**
 * Bloc « Code d'accès ».
 *
 * @package LearnDriveCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	printf(
		'<p %s><a href="%s" rel="nofollow">%s</a></p>',
		get_block_wrapper_attributes( array( 'class' => 'ld-access-code' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_url( wp_login_url( get_permalink() ) ),
		esc_html__( 'Connectez-vous pour activer votre code d’accès.', 'learn-drive-core' )
	);
	return;
}

wp_interactivity_config(
	'learn-drive/account',
	array(
		'restUrl' => esc_url_raw( rest_url( 'learn/v1/' ) ),
		'nonce'   => wp_create_nonce( 'wp_rest' ),
	)
);
?>
<form <?php echo get_block_wrapper_attributes( array( 'class' => 'ld-access-code ld-inline-form' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-wp-interactive="learn-drive/account" data-wp-context='{"message":"","busy":false}' data-wp-on--submit="actions.redeem">
	<label for="ld-code"><?php esc_html_e( 'Code d’accès', 'learn-drive-core' ); ?></label>
	<input id="ld-code" name="code" type="text" autocomplete="off" autocapitalize="characters" required pattern="[A-Za-z0-9-]{4,40}" placeholder="XXXX-XXXX-XXXX">
	<button type="submit" class="ld-btn ld-btn--primary" data-wp-bind--disabled="context.busy"><?php esc_html_e( 'Activer', 'learn-drive-core' ); ?></button>
	<span class="ld-access-code__message" role="status" data-wp-text="context.message"></span>
</form>
