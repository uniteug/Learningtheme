<?php
/**
 * Title: Offre — Essayez gratuitement
 * Slug: learn-drive/home-free
 * Categories: learn-drive-home, learn-drive-marketing
 * Keywords: gratuit, essai, échantillon, question du jour
 * Description: Ce qui est gratuit, sans inscription, et une question jouable tout de suite (volet commercial).
 *
 * @package LearnDrive
 */

$learn_drive_links = learn_drive_links();
$learn_drive_free  = array(
	__( 'Une question du jour, corrigée et expliquée', 'learn-drive' ),
	__( 'Les leçons d’introduction de chaque thème', 'learn-drive' ),
	__( 'Toutes les fiches panneaux et le lexique', 'learn-drive' ),
	__( 'L’actualité et les guides de démarches', 'learn-drive' ),
);
?>
<!-- wp:group {"tagName":"section","className":"ld-reveal","align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull ld-reveal has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"42%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:42%">
			<!-- wp:paragraph {"className":"ld-eyebrow"} -->
			<p class="ld-eyebrow"><?php esc_html_e( 'Gratuit, sans inscription', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Essayez avant de vous décider', 'learn-drive' ); ?></h2>
			<!-- /wp:heading -->
			<!-- wp:list {"className":"is-style-checklist"} -->
			<ul class="wp-block-list is-style-checklist">
				<?php foreach ( $learn_drive_free as $learn_drive_item ) : ?>
				<!-- wp:list-item -->
				<li><?php echo esc_html( $learn_drive_item ); ?></li>
				<!-- /wp:list-item -->
				<?php endforeach; ?>
			</ul>
			<!-- /wp:list -->
			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $learn_drive_links['learn'] ); ?>"><?php esc_html_e( 'Voir les leçons gratuites', 'learn-drive' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%">
			<!-- wp:learn-drive/question-of-the-day /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
