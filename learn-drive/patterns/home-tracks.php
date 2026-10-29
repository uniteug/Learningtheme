<?php
/**
 * Title: Accueil — Programmes phares
 * Slug: learn-drive/home-tracks
 * Categories: learn-drive-home, learn-drive-learning
 * Keywords: catégories, permis, programmes, formations
 * Description: Une carte par catégorie (permis, matière) avec les règles de l'examen, le nombre de leçons et de quiz et l'accès direct à l'examen blanc.
 *
 * @package LearnDrive
 */

$learn_drive_shop = learn_drive_is_commercial();
?>
<!-- wp:group {"tagName":"section","className":"ld-reveal","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull ld-reveal" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"className":"ld-section-head","layout":{"type":"default"}} -->
	<div class="wp-block-group ld-section-head">
		<!-- wp:paragraph {"className":"ld-eyebrow"} -->
		<p class="ld-eyebrow"><?php echo $learn_drive_shop ? esc_html__( 'Nos formations', 'learn-drive' ) : esc_html__( 'Programmes phares', 'learn-drive' ); ?></p>
		<!-- /wp:paragraph -->
		<!-- wp:heading -->
		<h2 class="wp-block-heading"><?php echo $learn_drive_shop ? esc_html__( 'Choisissez votre formation', 'learn-drive' ) : esc_html__( 'Quel permis préparez-vous ?', 'learn-drive' ); ?></h2>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"className":"ld-section-lead"} -->
		<p class="ld-section-lead"><?php esc_html_e( 'Chaque parcours réunit les cours, les quiz par thème et un examen blanc aux conditions réelles de l’épreuve.', 'learn-drive' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<?php if ( learn_drive_has_core_plugin() ) : ?>
		<!-- wp:learn-drive/track-cards /-->
	<?php else : ?>
		<!-- wp:terms-query {"termQuery":{"perPage":12,"taxonomy":"category","order":"asc","orderBy":"name","include":[],"hideEmpty":true,"showNested":false,"inherit":false},"className":"ld-term-grid"} -->
		<div class="wp-block-terms-query ld-term-grid">
			<!-- wp:term-template -->
				<!-- wp:term-name {"isLink":true} /-->
				<!-- wp:term-description /-->
			<!-- /wp:term-template -->
		</div>
		<!-- /wp:terms-query -->
	<?php endif; ?>
</section>
<!-- /wp:group -->
