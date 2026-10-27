<?php
/**
 * Title: Accueil — Infos & démarches
 * Slug: learn-drive/home-guides
 * Categories: learn-drive-home, learn-drive-editorial, query
 * Keywords: guides, démarches, prix, documents
 * Description: Les guides pratiques (coûts, documents, délais) en liste compacte.
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"backgroundColor":"surface","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"className":"ld-section-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group ld-section-head">
		<!-- wp:group {"layout":{"type":"flex","orientation":"vertical"},"style":{"spacing":{"blockGap":"0.25rem"}}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"className":"ld-eyebrow"} -->
			<p class="ld-eyebrow"><?php esc_html_e( 'Guides pratiques', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Infos & démarches', 'learn-drive' ); ?></h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->
		<!-- wp:paragraph {"className":"ld-section-link"} -->
		<p class="ld-section-link"><a href="<?php echo esc_url( home_url( '/infos/' ) ); ?>"><?php esc_html_e( 'Tous les guides', 'learn-drive' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:query {"queryId":13,"query":{"perPage":6,"pages":0,"offset":0,"postType":"ld_guide","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
	<div class="wp-block-query">
		<!-- wp:post-template {"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"16rem"}} -->
			<!-- wp:group {"className":"ld-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"0.5rem"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group ld-card" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
				<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"medium"} /-->
				<!-- wp:post-excerpt {"excerptLength":18,"fontSize":"small"} /-->
			</div>
			<!-- /wp:group -->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->
</section>
<!-- /wp:group -->
