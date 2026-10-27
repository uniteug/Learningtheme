<?php
/**
 * Title: Appel à l'action premium
 * Slug: learn-drive/cta-premium
 * Categories: learn-drive-marketing, call-to-action
 * Keywords: premium, abonnement, appel à l'action
 * Description: Bandeau court qui mène à l'offre premium.
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"className":"is-style-card","style":{"border":{"left":{"color":"var:preset|color|premium","width":"4px"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group is-style-card" style="border-left-color:var(--wp--preset--color--premium);border-left-width:4px">
	<!-- wp:group {"layout":{"type":"flex","orientation":"vertical"},"style":{"spacing":{"blockGap":"0.25rem"}}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"}},"fontSize":"large"} -->
		<p class="has-large-font-size" style="font-weight:700"><?php esc_html_e( 'Prêt pour l’examen ?', 'learn-drive' ); ?></p>
		<!-- /wp:paragraph -->
		<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|muted"}}} -->
		<p class="has-text-color" style="color:var(--wp--preset--color--muted)"><?php esc_html_e( 'Examens blancs illimités et révision de vos erreurs avec l’accès premium.', 'learn-drive' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
	<!-- wp:buttons -->
	<div class="wp-block-buttons">
		<!-- wp:button {"style":{"color":{"background":"var:preset|color|premium"}}} -->
		<div class="wp-block-button"><a class="wp-block-button__link has-background wp-element-button" href="<?php echo esc_url( home_url( '/premium/' ) ); ?>" style="background-color:var(--wp--preset--color--premium)"><?php esc_html_e( 'Découvrir le premium', 'learn-drive' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
