<?php
/**
 * Title: Accueil — Témoignages et preuves
 * Slug: learn-drive/home-testimonials
 * Categories: learn-drive-home, learn-drive-marketing, testimonials
 * Keywords: avis, témoignages, auto-écoles, experts
 * Description: Avis d'élèves et d'auto-écoles partenaires. Les textes fournis sont des exemples : remplacez-les par de vrais avis vérifiés (ne publiez jamais d'avis inventés).
 *
 * @package LearnDrive
 */

$learn_drive_reviews = array(
	array( __( '« Les explications après chaque erreur m’ont fait comprendre les priorités une bonne fois pour toutes. Code obtenu avec 38/40. »', 'learn-drive' ), __( 'Inès — patente B', 'learn-drive' ) ),
	array( __( '« Les examens blancs chronométrés sont exactement dans les conditions du jour J. Je révisais dans le bus sur mon téléphone. »', 'learn-drive' ), __( 'Malik — patente A2', 'learn-drive' ) ),
	array( __( '« Nous l’utilisons avec nos élèves : on voit tout de suite les thèmes à retravailler avant de présenter quelqu’un. »', 'learn-drive' ), __( 'Auto-école partenaire', 'learn-drive' ) ),
);
?>
<!-- wp:group {"tagName":"section","className":"ld-reveal","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull ld-reveal" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Ils ont réussi avec nous', 'learn-drive' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"},"blockGap":{"left":"var:preset|spacing|40","top":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-columns" style="margin-top:var(--wp--preset--spacing--50)">
		<?php foreach ( $learn_drive_reviews as $learn_drive_review ) : ?>
		<!-- wp:column {"className":"is-style-card ld-testimonial"} -->
		<div class="wp-block-column is-style-card ld-testimonial">
			<!-- wp:paragraph {"className":"ld-stars"} -->
			<p class="ld-stars"><span aria-hidden="true">★★★★★</span><span class="ld-sr-only"><?php esc_html_e( '5 sur 5', 'learn-drive' ); ?></span></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph -->
			<p><?php echo esc_html( $learn_drive_review[0] ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"fontSize":"small","style":{"typography":{"fontWeight":"700"}}} -->
			<p class="has-small-font-size" style="font-weight:700"><?php echo esc_html( $learn_drive_review[1] ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:columns -->

	<!-- wp:paragraph {"align":"center","fontSize":"small","style":{"color":{"text":"var:preset|color|muted"},"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
	<p class="has-text-align-center has-text-color has-small-font-size" style="color:var(--wp--preset--color--muted);margin-top:var(--wp--preset--spacing--40)"><?php esc_html_e( 'Contenus rédigés par des enseignants de la conduite et revérifiés à chaque évolution de la réglementation.', 'learn-drive' ); ?> <a href="<?php echo esc_url( learn_drive_links()['editorial'] ); ?>"><?php esc_html_e( 'Comment nous vérifions nos contenus', 'learn-drive' ); ?></a></p>
	<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
