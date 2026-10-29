<?php
/**
 * Title: Accueil — Démarches essentielles
 * Slug: learn-drive/home-guides
 * Categories: learn-drive-home, learn-drive-editorial
 * Keywords: démarches, guides, infos pratiques
 * Description: Les six guides pratiques les plus récents en cartes numérotées.
 *
 * @package LearnDrive
 */

$learn_drive_links = learn_drive_links();
$learn_drive_type  = learn_drive_has_core_plugin() ? 'ld_guide' : 'page';
?>
<!-- wp:group {"tagName":"section","className":"ld-reveal","align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull ld-reveal has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"className":"ld-section-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group ld-section-head">
		<!-- wp:group {"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"className":"ld-eyebrow"} -->
			<p class="ld-eyebrow"><?php esc_html_e( 'Infos & démarches', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Les démarches, pas à pas', 'learn-drive' ); ?></h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->
		<!-- wp:paragraph {"className":"ld-section-link"} -->
		<p class="ld-section-link"><a href="<?php echo esc_url( $learn_drive_links['guides'] ); ?>"><?php esc_html_e( 'Tous les guides', 'learn-drive' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:query {"queryId":14,"query":{"perPage":6,"pages":0,"offset":0,"postType":"<?php echo esc_attr( $learn_drive_type ); ?>","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"ld-guides"} -->
	<div class="wp-block-query ld-guides">
		<!-- wp:post-template {"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"18rem"}} -->
			<!-- wp:post-title {"level":3,"isLink":true} /-->
			<!-- wp:post-excerpt {"excerptLength":20} /-->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->
</section>
<!-- /wp:group -->
