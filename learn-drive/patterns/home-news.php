<?php
/**
 * Title: Accueil — À la une
 * Slug: learn-drive/home-news
 * Categories: learn-drive-home, learn-drive-editorial, query
 * Keywords: actualités, news, à la une, magazine
 * Description: Mise en page magazine : l'article à la une en grand, les quatre suivants en liste avec vignette.
 *
 * @package LearnDrive
 */

$learn_drive_links = learn_drive_links();
$learn_drive_lead  = array(
	'perPage' => 1,
	'pages'   => 0,
	'offset'  => 0,
	'order'   => 'desc',
	'orderBy' => 'date',
	'author'  => '',
	'search'  => '',
	'exclude' => array(),
	'sticky'  => '',
	'inherit' => false,
);
$learn_drive_list  = array(
	'perPage' => 4,
	'pages'   => 0,
	'offset'  => 1,
	'order'   => 'desc',
	'orderBy' => 'date',
	'author'  => '',
	'search'  => '',
	'exclude' => array(),
	'sticky'  => '',
	'inherit' => false,
);
?>
<!-- wp:group {"tagName":"section","className":"ld-reveal","align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull ld-reveal has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"className":"ld-section-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group ld-section-head">
		<!-- wp:group {"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"className":"ld-eyebrow"} -->
			<p class="ld-eyebrow"><?php esc_html_e( 'Actualités', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'À la une', 'learn-drive' ); ?></h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->
		<!-- wp:paragraph {"className":"ld-section-link"} -->
		<p class="ld-section-link"><a href="<?php echo esc_url( $learn_drive_links['news'] ); ?>"><?php esc_html_e( 'Toute l’actualité', 'learn-drive' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"className":"ld-mag"} -->
	<div class="wp-block-columns ld-mag">
		<!-- wp:column {"width":"58%"} -->
		<div class="wp-block-column" style="flex-basis:58%">
			<!-- wp:query {"queryId":11,"query":<?php learn_drive_the_query_json( 'news', $learn_drive_lead ); ?>
			,"className":"ld-mag__lead"} -->
			<div class="wp-block-query ld-mag__lead">
				<!-- wp:post-template -->
					<!-- wp:post-featured-image {"sizeSlug":"learn-drive-1200"} /-->
					<!-- wp:group {"className":"ld-mag__body","layout":{"type":"default"}} -->
					<div class="wp-block-group ld-mag__body">
						<!-- wp:post-terms {"term":"category","className":"is-style-pill"} /-->
						<!-- wp:post-title {"level":3,"isLink":true} /-->
						<!-- wp:post-excerpt {"excerptLength":24} /-->
					</div>
					<!-- /wp:group -->
				<!-- /wp:post-template -->
			</div>
			<!-- /wp:query -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"42%"} -->
		<div class="wp-block-column" style="flex-basis:42%">
			<!-- wp:query {"queryId":12,"query":<?php learn_drive_the_query_json( 'news', $learn_drive_list ); ?>
			,"className":"ld-mag__list"} -->
			<div class="wp-block-query ld-mag__list">
				<!-- wp:post-template -->
					<!-- wp:post-featured-image {"sizeSlug":"learn-drive-400"} /-->
					<!-- wp:group {"layout":{"type":"default"}} -->
					<div class="wp-block-group">
						<!-- wp:post-terms {"term":"category"} /-->
						<!-- wp:post-title {"level":3,"isLink":true} /-->
						<!-- wp:post-date {"format":"j F Y"} /-->
					</div>
					<!-- /wp:group -->
				<!-- /wp:post-template -->
			</div>
			<!-- /wp:query -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
