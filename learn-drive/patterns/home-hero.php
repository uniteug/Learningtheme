<?php
/**
 * Title: Accueil — Bandeau principal
 * Slug: learn-drive/home-hero
 * Categories: learn-drive-home, banner
 * Keywords: hero, bandeau, h1, examen blanc
 * Description: H1 explicite, sous-titre, appel à l'action prioritaire et chiffres de preuve calculés en direct. Pas de carrousel.
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"tagName":"section","className":"ld-hero","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull ld-hero" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)">
	<!-- wp:paragraph {"className":"ld-eyebrow","style":{"color":{"text":"#ffffff"}}} -->
	<p class="ld-eyebrow has-text-color" style="color:#ffffff"><?php esc_html_e( 'Code de la route · Permis · Examens blancs', 'learn-drive' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"fontSize":"huge"} -->
	<h1 class="wp-block-heading has-huge-font-size"><?php esc_html_e( 'Quiz et cours du permis de conduire, toutes catégories', 'learn-drive' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"ld-hero__lead","fontSize":"large"} -->
	<p class="ld-hero__lead has-large-font-size"><?php esc_html_e( 'Des milliers de questions corrigées et expliquées, des examens blancs aux conditions réelles et des fiches claires pour réviser à votre rythme, sur mobile comme sur ordinateur.', 'learn-drive' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">
		<!-- wp:button {"fontSize":"large"} -->
		<div class="wp-block-button has-custom-font-size has-large-font-size"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/quiz/' ) ); ?>"><?php esc_html_e( 'Lancer un examen blanc gratuit', 'learn-drive' ); ?></a></div>
		<!-- /wp:button -->

		<!-- wp:button {"className":"is-style-outline","fontSize":"large"} -->
		<div class="wp-block-button has-custom-font-size is-style-outline has-large-font-size"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/cours/' ) ); ?>"><?php esc_html_e( 'Voir les cours', 'learn-drive' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->

	<!-- wp:learn-drive/stats {"className":"ld-proof"} /-->
</section>
<!-- /wp:group -->
