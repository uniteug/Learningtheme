<?php
/**
 * Title: Actualités du marché
 * Slug: learn-drive/market-news
 * Inserter: no
 * Description: Dernières actualités du pays affiché (hub d'un marché), en cartes.
 *
 * @package LearnDrive
 */

$learn_drive_query = array(
	'perPage'  => 6,
	'pages'    => 0,
	'offset'   => 0,
	'postType' => 'post',
	'order'    => 'desc',
	'orderBy'  => 'date',
	'author'   => '',
	'search'   => '',
	'exclude'  => array(),
	'sticky'   => '',
	'inherit'  => false,
);
?>
<!-- wp:group {"className":"ld-section-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group ld-section-head">
	<!-- wp:heading -->
	<h2 class="wp-block-heading"><?php esc_html_e( 'Dernières actualités', 'learn-drive' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph {"className":"ld-section-link"} -->
	<p class="ld-section-link"><a href="<?php echo esc_url( learn_drive_links()['news'] ); ?>"><?php esc_html_e( 'Toutes les actualités', 'learn-drive' ); ?></a></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:query {"queryId":21,"query":<?php learn_drive_the_query_json( 'news', $learn_drive_query ); ?>,"className":"ld-cards"} -->
<div class="wp-block-query ld-cards">
	<!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
		<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9","sizeSlug":"learn-drive-400"} /-->
		<!-- wp:post-terms {"term":"category","className":"is-style-pill"} /-->
		<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"large"} /-->
		<!-- wp:post-date {"format":"j F Y"} /-->
	<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
