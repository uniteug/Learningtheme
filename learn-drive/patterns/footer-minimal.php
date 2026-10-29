<?php
/**
 * Title: Pied de page minimal
 * Slug: learn-drive/footer-minimal
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">
	<!-- wp:paragraph {"align":"center","fontSize":"x-small","style":{"color":{"text":"var:preset|color|muted"}}} -->
	<p class="has-text-align-center has-text-color has-x-small-font-size" style="color:var(--wp--preset--color--muted)">© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?> · <a href="<?php echo esc_url( learn_drive_links()['privacy'] ); ?>"><?php esc_html_e( 'Confidentialité', 'learn-drive' ); ?></a></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
