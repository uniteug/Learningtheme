<?php
/**
 * Title: Contenu de la page 404
 * Slug: learn-drive/page-404
 * Inserter: no
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"layout":{"type":"constrained","contentSize":"720px"}} -->
<div class="wp-block-group">
	<!-- wp:paragraph {"className":"ld-404-code"} -->
	<p class="ld-404-code">404</p>
	<!-- /wp:paragraph -->
	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading"><?php esc_html_e( 'Cette page a pris une autre route', 'learn-drive' ); ?></h1>
	<!-- /wp:heading -->
	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'Le lien est peut-être ancien ou la page a été déplacée. Cherchez ce dont vous avez besoin ou reprenez avec un quiz populaire.', 'learn-drive' ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:search {"label":"<?php echo esc_attr__( 'Rechercher', 'learn-drive' ); ?>","showLabel":false,"placeholder":"<?php echo esc_attr__( 'Rechercher une question, un panneau, un article…', 'learn-drive' ); ?>","buttonText":"<?php echo esc_attr__( 'Rechercher', 'learn-drive' ); ?>","buttonPosition":"button-inside"} /-->
</div>
<!-- /wp:group -->

<!-- wp:heading {"style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}}} -->
<h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--60)"><?php esc_html_e( 'Quiz populaires', 'learn-drive' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:query {"queryId":40,"query":{"perPage":6,"pages":0,"offset":0,"postType":"ld_quiz","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
<div class="wp-block-query">
	<!-- wp:post-template {"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"16rem"}} -->
		<!-- wp:group {"className":"ld-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
		<div class="wp-block-group ld-card" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<!-- wp:post-terms {"term":"ld_track","className":"is-style-pill"} /-->
			<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"medium"} /-->
		</div>
		<!-- /wp:group -->
	<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
