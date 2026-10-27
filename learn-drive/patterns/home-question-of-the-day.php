<?php
/**
 * Title: Accueil — Question du jour
 * Slug: learn-drive/home-question-of-the-day
 * Categories: learn-drive-home, learn-drive-learning
 * Keywords: question, jour, quiz
 * Description: Une question jouable directement sur la page (nécessite le plugin Learn & Drive Core).
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"860px"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:paragraph {"className":"ld-eyebrow","align":"center"} -->
	<p class="ld-eyebrow has-text-align-center"><?php esc_html_e( 'Question du jour', 'learn-drive' ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Testez-vous en 30 secondes', 'learn-drive' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:learn-drive/question-of-the-day /-->
</section>
<!-- /wp:group -->
