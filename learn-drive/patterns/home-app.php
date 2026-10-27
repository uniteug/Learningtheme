<?php
/**
 * Title: Offre — Application mobile
 * Slug: learn-drive/home-app
 * Categories: learn-drive-home, learn-drive-marketing, call-to-action
 * Keywords: application, mobile, qr code
 * Description: Lien vers l'application avec emplacement pour un QR code.
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"tagName":"section","align":"full","className":"is-style-section-brand","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1080px"}} -->
<section class="wp-block-group alignfull is-style-section-brand" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:columns {"verticalAlignment":"center"} -->
	<div class="wp-block-columns are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"66%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:66%">
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Révisez partout avec l’application', 'learn-drive' ); ?></h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Même compte, même progression : commencez un quiz sur le site, terminez-le dans le métro. Défis entre amis et mode hors ligne inclus.', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/application/' ) ); ?>"><?php esc_html_e( 'Télécharger l’application', 'learn-drive' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:image {"width":"160px","height":"160px","sizeSlug":"full","linkDestination":"none","align":"center","className":"is-style-framed"} -->
			<figure class="wp-block-image aligncenter size-full is-resized is-style-framed"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/qr-placeholder.svg' ) ); ?>" alt="<?php esc_attr_e( 'QR code vers l’application (à remplacer)', 'learn-drive' ); ?>" style="width:160px;height:160px"/></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
