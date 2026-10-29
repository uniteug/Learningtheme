<?php
/**
 * Title: Accueil — Le permis en Europe
 * Slug: learn-drive/home-europe
 * Categories: learn-drive-home, learn-drive-learning
 * Keywords: pays, europe, international
 * Description: Les pays couverts (drapeau, catégories, nombre de questions) et l'accès aux guides de conduite à l'étranger.
 *
 * @package LearnDrive
 */

if ( ! learn_drive_has_core_plugin() ) {
	return;
}
$learn_drive_links = learn_drive_links();
?>
<!-- wp:group {"tagName":"section","className":"ld-reveal","align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull ld-reveal has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"className":"ld-section-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group ld-section-head">
		<!-- wp:group {"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"className":"ld-eyebrow"} -->
			<p class="ld-eyebrow"><?php esc_html_e( 'Ailleurs en Europe', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Un permis, plusieurs pays', 'learn-drive' ); ?></h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"className":"ld-section-lead"} -->
			<p class="ld-section-lead"><?php esc_html_e( 'Chaque pays a ses règles d’examen : nous les appliquons à la lettre dans les examens blancs.', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
		<!-- wp:paragraph {"className":"ld-section-link"} -->
		<p class="ld-section-link"><a href="<?php echo esc_url( $learn_drive_links['guides'] ); ?>"><?php esc_html_e( 'Conduire à l’étranger', 'learn-drive' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:learn-drive/countries /-->
</section>
<!-- /wp:group -->
