<?php
/**
 * Title: Accueil — Réviser par thème
 * Slug: learn-drive/home-topics
 * Categories: learn-drive-home, learn-drive-learning
 * Keywords: thèmes, programme officiel, carrousel
 * Description: Les thèmes du programme officiel en carrousel défilant (tactile, clavier, boutons).
 *
 * @package LearnDrive
 */

$learn_drive_links = learn_drive_links();
$learn_drive_tax   = learn_drive_has_core_plugin() ? 'ld_topic' : 'category';
?>
<!-- wp:group {"tagName":"section","className":"ld-reveal","align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull ld-reveal has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"className":"ld-section-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group ld-section-head">
		<!-- wp:group {"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"className":"ld-eyebrow"} -->
			<p class="ld-eyebrow"><?php esc_html_e( 'Programme officiel', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Révisez thème par thème', 'learn-drive' ); ?></h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->
		<!-- wp:paragraph {"className":"ld-section-link"} -->
		<p class="ld-section-link"><a href="<?php echo esc_url( $learn_drive_links['quiz'] ); ?>"><?php esc_html_e( 'Tous les quiz', 'learn-drive' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:terms-query {"termQuery":{"perPage":16,"taxonomy":"<?php echo esc_attr( $learn_drive_tax ); ?>","order":"desc","orderBy":"count","include":[],"hideEmpty":true,"showNested":false,"inherit":false},"className":"ld-topics ld-rail"} -->
	<div class="wp-block-terms-query ld-topics ld-rail">
		<!-- wp:term-template -->
			<!-- wp:term-name {"isLink":true,"level":3} /-->
			<!-- wp:term-description /-->
		<!-- /wp:term-template -->
	</div>
	<!-- /wp:terms-query -->
</section>
<!-- /wp:group -->
