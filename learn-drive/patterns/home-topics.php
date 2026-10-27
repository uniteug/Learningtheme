<?php
/**
 * Title: Accueil — Quiz par thème
 * Slug: learn-drive/home-topics
 * Categories: learn-drive-home, learn-drive-learning
 * Keywords: thèmes, programme officiel, quiz
 * Description: Les thèmes officiels du programme, chacun menant à ses quiz et leçons.
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"backgroundColor":"surface","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"className":"ld-section-head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group ld-section-head">
		<!-- wp:group {"layout":{"type":"flex","orientation":"vertical"},"style":{"spacing":{"blockGap":"0.25rem"}}} -->
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
		<p class="ld-section-link"><a href="<?php echo esc_url( home_url( '/quiz/' ) ); ?>"><?php esc_html_e( 'Tous les quiz', 'learn-drive' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:terms-query {"termQuery":{"perPage":12,"taxonomy":"ld_topic","order":"asc","orderBy":"name","include":[],"hideEmpty":true,"showNested":false,"inherit":false},"className":"ld-term-grid"} -->
	<div class="wp-block-terms-query ld-term-grid">
		<!-- wp:term-template -->
			<!-- wp:term-name {"isLink":true} /-->
			<!-- wp:term-count {"bracketType":"none"} /-->
		<!-- /wp:term-template -->
	</div>
	<!-- /wp:terms-query -->

	<!-- wp:learn-drive/topic-progress /-->
</section>
<!-- /wp:group -->
