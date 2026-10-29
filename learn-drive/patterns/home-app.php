<?php
/**
 * Title: Offre — Application mobile
 * Slug: learn-drive/home-app
 * Categories: learn-drive-home, learn-drive-marketing, call-to-action
 * Keywords: application, mobile, qr code
 * Description: Liens vers l'application sur l'App Store et Google Play (affichée seulement si les adresses sont renseignées).
 *
 * @package LearnDrive
 */

$learn_drive_links = learn_drive_links();
if ( empty( $learn_drive_links['app_ios'] ) && empty( $learn_drive_links['app_android'] ) ) {
	return;
}
?>
<!-- wp:group {"tagName":"section","align":"full","className":"is-style-section-brand ld-reveal","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1080px"}} -->
<section class="wp-block-group alignfull is-style-section-brand ld-reveal" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:heading -->
	<h2 class="wp-block-heading"><?php esc_html_e( 'Révisez aussi avec l’application mobile', 'learn-drive' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'Des quiz et des examens blancs dans votre poche, pour réviser partout, même quelques minutes.', 'learn-drive' ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:buttons -->
	<div class="wp-block-buttons">
		<?php if ( ! empty( $learn_drive_links['app_ios'] ) ) : ?>
		<!-- wp:button -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $learn_drive_links['app_ios'] ); ?>" rel="noopener"><?php esc_html_e( 'Télécharger sur l’App Store', 'learn-drive' ); ?></a></div>
		<!-- /wp:button -->
		<?php endif; ?>
		<?php if ( ! empty( $learn_drive_links['app_android'] ) ) : ?>
		<!-- wp:button {"className":"is-style-outline"} -->
		<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $learn_drive_links['app_android'] ); ?>" rel="noopener"><?php esc_html_e( 'Disponible sur Google Play', 'learn-drive' ); ?></a></div>
		<!-- /wp:button -->
		<?php endif; ?>
	</div>
	<!-- /wp:buttons -->
</section>
<!-- /wp:group -->
