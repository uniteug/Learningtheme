<?php
/**
 * Title: Offre — Comparatif gratuit / premium
 * Slug: learn-drive/home-premium
 * Categories: learn-drive-home, learn-drive-marketing, call-to-action
 * Keywords: premium, tarifs, abonnement, gratuit
 * Description: Comparatif clair entre l'accès gratuit et l'accès premium.
 *
 * @package LearnDrive
 */

$learn_drive_free    = array(
	__( 'Toutes les leçons d’introduction', 'learn-drive' ),
	__( 'Quiz par thème avec correction', 'learn-drive' ),
	__( '1 examen blanc par jour', 'learn-drive' ),
	__( 'Fiches panneaux et lexique', 'learn-drive' ),
);
$learn_drive_premium = array(
	__( 'Toutes les questions et explications détaillées', 'learn-drive' ),
	__( 'Examens blancs illimités aux conditions réelles', 'learn-drive' ),
	__( 'Révision intelligente de vos erreurs', 'learn-drive' ),
	__( 'Statistiques par thème et prédiction de réussite', 'learn-drive' ),
	__( 'Sans publicité, utilisable hors ligne', 'learn-drive' ),
);
?>
<!-- wp:group {"tagName":"section","anchor":"offres","className":"ld-reveal","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"backgroundColor":"surface","layout":{"type":"constrained","contentSize":"980px"}} -->
<section id="offres" class="wp-block-group alignfull ld-reveal has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:heading {"textAlign":"center"} -->
	<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Commencez gratuitement, allez plus loin quand vous voulez', 'learn-drive' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"},"blockGap":{"left":"var:preset|spacing|40","top":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-columns" style="margin-top:var(--wp--preset--spacing--50)">
		<!-- wp:column {"className":"is-style-card ld-plan"} -->
		<div class="wp-block-column is-style-card ld-plan">
			<!-- wp:heading {"level":3} -->
			<h3 class="wp-block-heading"><?php esc_html_e( 'Gratuit', 'learn-drive' ); ?></h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"fontSize":"x-large","style":{"typography":{"fontWeight":"700"}}} -->
			<p class="has-x-large-font-size" style="font-weight:700">0 €</p>
			<!-- /wp:paragraph -->
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
				<!-- wp:button {"className":"is-style-outline","width":100} -->
				<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( learn_drive_links()['register'] ); ?>"><?php esc_html_e( 'Commencer gratuitement', 'learn-drive' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"className":"is-style-card ld-plan ld-plan--premium"} -->
		<div class="wp-block-column is-style-card ld-plan ld-plan--premium">
			<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading"><?php esc_html_e( 'Premium', 'learn-drive' ); ?></h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph -->
				<p><span class="ld-badge ld-badge--premium"><?php esc_html_e( 'Recommandé', 'learn-drive' ); ?></span></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
			<!-- wp:paragraph {"fontSize":"x-large","style":{"typography":{"fontWeight":"700"}}} -->
			<p class="has-x-large-font-size" style="font-weight:700"><?php esc_html_e( '9,90 € / mois', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:list {"className":"is-style-checklist"} -->
			<ul class="wp-block-list is-style-checklist">
				<?php foreach ( $learn_drive_premium as $learn_drive_item ) : ?>
				<!-- wp:list-item -->
				<li><?php echo esc_html( $learn_drive_item ); ?></li>
				<!-- /wp:list-item -->
				<?php endforeach; ?>
			</ul>
			<!-- /wp:list -->
			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"width":100} -->
				<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( learn_drive_links()['premium'] ); ?>"><?php esc_html_e( 'Passer premium', 'learn-drive' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
