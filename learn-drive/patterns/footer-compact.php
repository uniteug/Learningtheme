<?php
/**
 * Title: Pied de page compact
 * Slug: learn-drive/footer-compact
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"className":"ld-footer","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-group ld-footer" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
	<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
	<div class="wp-block-group">
		<!-- wp:site-title {"level":0,"fontSize":"medium"} /-->
		<!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","flexWrap":"wrap"}} -->
			<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'Mentions légales', 'learn-drive' ); ?>","url":"<?php echo esc_url( learn_drive_links()['legal'] ); ?>","kind":"custom"} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'Confidentialité', 'learn-drive' ); ?>","url":"<?php echo esc_url( learn_drive_links()['privacy'] ); ?>","kind":"custom"} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'Politique éditoriale', 'learn-drive' ); ?>","url":"<?php echo esc_url( learn_drive_links()['editorial'] ); ?>","kind":"custom"} /-->
		<!-- /wp:navigation -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
