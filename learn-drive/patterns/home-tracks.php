<?php
/**
 * Title: Accueil — Choisir son permis
 * Slug: learn-drive/home-tracks
 * Categories: learn-drive-home, learn-drive-learning
 * Keywords: catégories, permis, matières, grille
 * Description: Grille de cartes des catégories (permis, matières, niveaux) avec leur nombre de contenus.
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--50)">
	<!-- wp:group {"className":"ld-section-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group ld-section-head">
		<!-- wp:heading -->
		<h2 class="wp-block-heading"><?php esc_html_e( 'Choisissez votre permis', 'learn-drive' ); ?></h2>
		<!-- /wp:heading -->
	</div>
	<!-- /wp:group -->

	<!-- wp:terms-query {"termQuery":{"perPage":20,"taxonomy":"ld_track","order":"asc","orderBy":"name","include":[],"hideEmpty":false,"showNested":false,"inherit":false},"className":"ld-term-grid"} -->
	<div class="wp-block-terms-query ld-term-grid">
		<!-- wp:term-template -->
			<!-- wp:term-name {"isLink":true} /-->
			<!-- wp:term-description /-->
		<!-- /wp:term-template -->
	</div>
	<!-- /wp:terms-query -->
</section>
<!-- /wp:group -->
