<?php
/**
 * Title: Accueil — Panneaux à connaître
 * Slug: learn-drive/home-signs
 * Categories: learn-drive-home, learn-drive-learning
 * Keywords: panneaux, signalisation, carrousel, fiches
 * Description: Les fiches (panneaux, mémos) en carrousel illustré.
 *
 * @package LearnDrive
 */

if ( ! learn_drive_has_core_plugin() ) {
	return;
}
$learn_drive_links = learn_drive_links();
?>
<!-- wp:group {"tagName":"section","className":"ld-reveal","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull ld-reveal" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"className":"ld-section-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group ld-section-head">
		<!-- wp:group {"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"className":"ld-eyebrow"} -->
			<p class="ld-eyebrow"><?php esc_html_e( 'Signalisation', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Les panneaux à reconnaître du premier coup d’œil', 'learn-drive' ); ?></h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->
		<!-- wp:paragraph {"className":"ld-section-link"} -->
		<p class="ld-section-link"><a href="<?php echo esc_url( $learn_drive_links['signs'] ); ?>"><?php esc_html_e( 'Tous les panneaux', 'learn-drive' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:query {"queryId":15,"query":{"perPage":14,"pages":0,"offset":0,"postType":"ld_sheet","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"ld-signs ld-rail"} -->
	<div class="wp-block-query ld-signs ld-rail">
		<!-- wp:post-template -->
			<!-- wp:post-featured-image {"sizeSlug":"learn-drive-400"} /-->
			<!-- wp:post-title {"level":3,"isLink":true} /-->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->
</section>
<!-- /wp:group -->
