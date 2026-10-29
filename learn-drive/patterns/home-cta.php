<?php
/**
 * Title: Offre — Appel final
 * Slug: learn-drive/home-cta
 * Categories: learn-drive-home, learn-drive-marketing, call-to-action
 * Keywords: inscription, appel à l'action, essai
 * Description: Bandeau d'appel final et bouton d'inscription collant sur mobile (volet commercial).
 *
 * @package LearnDrive
 */

$learn_drive_links = learn_drive_links();
?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)">
	<!-- wp:group {"className":"ld-cta-band ld-reveal","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
	<div class="wp-block-group ld-cta-band ld-reveal" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)">
		<!-- wp:group {"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Prêt à passer à la vitesse supérieure ?', 'learn-drive' ); ?></h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Créez votre compte en 30 secondes et retrouvez votre progression sur le site, depuis n’importe quel appareil.', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"fontSize":"large"} -->
			<div class="wp-block-button has-custom-font-size has-large-font-size"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $learn_drive_links['register'] ); ?>" rel="nofollow"><?php esc_html_e( 'Commencer gratuitement', 'learn-drive' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:buttons {"className":"ld-sticky-cta"} -->
	<div class="wp-block-buttons ld-sticky-cta">
		<!-- wp:button {"width":100} -->
		<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $learn_drive_links['register'] ); ?>" rel="nofollow"><?php esc_html_e( 'Commencer gratuitement', 'learn-drive' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</section>
<!-- /wp:group -->
