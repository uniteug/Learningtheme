<?php
/**
 * Title: Accueil — Actualités
 * Slug: learn-drive/home-news
 * Categories: learn-drive-home, learn-drive-editorial, query
 * Keywords: actualités, news, à la une
 * Description: Un article à la une et les quatre actualités les plus récentes.
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"className":"ld-section-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group ld-section-head">
		<!-- wp:heading -->
		<h2 class="wp-block-heading"><?php esc_html_e( 'Actualités', 'learn-drive' ); ?></h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"className":"ld-section-link"} -->
		<p class="ld-section-link"><a href="<?php echo esc_url( home_url( '/actualites/' ) ); ?>"><?php esc_html_e( 'Toute l’actualité', 'learn-drive' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:query {"queryId":11,"query":{"perPage":1,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"ld-featured"} -->
	<div class="wp-block-query ld-featured">
		<!-- wp:post-template -->
			<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
			<div class="wp-block-columns are-vertically-aligned-center">
				<!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
				<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%">
					<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9","sizeSlug":"learn-drive-1200"} /-->
				</div>
				<!-- /wp:column -->
				<!-- wp:column {"verticalAlignment":"center"} -->
				<div class="wp-block-column is-vertically-aligned-center">
					<!-- wp:post-terms {"term":"category","className":"is-style-pill"} /-->
					<!-- wp:post-title {"level":3,"isLink":true} /-->
					<!-- wp:post-excerpt {"excerptLength":36} /-->
					<!-- wp:post-date {"format":"j F Y"} /-->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->

	<!-- wp:query {"queryId":12,"query":{"perPage":4,"pages":0,"offset":1,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
	<div class="wp-block-query" style="margin-top:var(--wp--preset--spacing--50)">
		<!-- wp:post-template {"layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"14rem"}} -->
			<!-- wp:template-part {"slug":"card-post"} /-->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->
</section>
<!-- /wp:group -->
