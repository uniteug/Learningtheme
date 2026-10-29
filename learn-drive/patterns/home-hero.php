<?php
/**
 * Title: Accueil — Bandeau principal
 * Slug: learn-drive/home-hero
 * Categories: learn-drive-home, banner
 * Keywords: hero, bandeau, h1, examen blanc, recherche
 * Description: H1 explicite, recherche, accès rapides et visuel animé d'un examen en cours. Volet portail : recherche et accès directs ; volet commercial : essai gratuit et réassurance.
 *
 * @package LearnDrive
 */

$learn_drive_links = learn_drive_links();
$learn_drive_text  = learn_drive_home_text();
$learn_drive_shop  = learn_drive_is_commercial();
$learn_drive_chips = array(
	array( __( 'Examen blanc', 'learn-drive' ), $learn_drive_links['exam'] ),
	array( __( 'Panneaux', 'learn-drive' ), $learn_drive_links['signs'] ),
	array( __( 'Démarches', 'learn-drive' ), $learn_drive_links['guides'] ),
	array( __( 'Lexique', 'learn-drive' ), $learn_drive_links['glossary'] ),
);
?>
<!-- wp:group {"tagName":"section","className":"ld-hero ld-hero--home","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull ld-hero ld-hero--home" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)">
	<!-- wp:columns {"verticalAlignment":"center","className":"ld-hero__grid"} -->
	<div class="wp-block-columns are-vertically-aligned-center ld-hero__grid">
		<!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%">
			<!-- wp:paragraph {"className":"ld-eyebrow is-pill"} -->
			<p class="ld-eyebrow is-pill"><?php echo esc_html( $learn_drive_text['eyebrow'] ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":1,"fontSize":"huge"} -->
			<h1 class="wp-block-heading has-huge-font-size"><?php echo esc_html( $learn_drive_text['title'] ); ?></h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"ld-hero__lead","fontSize":"large"} -->
			<p class="ld-hero__lead has-large-font-size"><?php echo esc_html( $learn_drive_text['lead'] ); ?></p>
			<!-- /wp:paragraph -->

			<?php if ( $learn_drive_shop ) : ?>
				<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
				<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">
					<!-- wp:button {"fontSize":"large"} -->
					<div class="wp-block-button has-custom-font-size has-large-font-size"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $learn_drive_links['register'] ); ?>" rel="nofollow"><?php esc_html_e( 'Commencer gratuitement', 'learn-drive' ); ?></a></div>
					<!-- /wp:button -->
					<!-- wp:button {"className":"is-style-outline","fontSize":"large"} -->
					<div class="wp-block-button has-custom-font-size is-style-outline has-large-font-size"><a class="wp-block-button__link wp-element-button" href="#offres"><?php esc_html_e( 'Voir les offres', 'learn-drive' ); ?></a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->

				<!-- wp:list {"className":"ld-trust"} -->
				<ul class="wp-block-list ld-trust">
					<!-- wp:list-item -->
					<li><?php esc_html_e( 'Sans carte bancaire', 'learn-drive' ); ?></li>
					<!-- /wp:list-item -->
					<!-- wp:list-item -->
					<li><?php esc_html_e( 'Corrigés détaillés', 'learn-drive' ); ?></li>
					<!-- /wp:list-item -->
					<!-- wp:list-item -->
					<li><?php esc_html_e( 'Sur mobile et ordinateur', 'learn-drive' ); ?></li>
					<!-- /wp:list-item -->
				</ul>
				<!-- /wp:list -->
			<?php else : ?>
				<!-- wp:search {"label":"<?php echo esc_attr__( 'Rechercher sur le site', 'learn-drive' ); ?>","showLabel":false,"placeholder":"<?php echo esc_attr__( 'Une règle, un panneau, une démarche…', 'learn-drive' ); ?>","buttonText":"<?php echo esc_attr__( 'Rechercher', 'learn-drive' ); ?>"} /-->

				<!-- wp:html -->
				<ul class="ld-chips" aria-label="<?php echo esc_attr__( 'Accès rapides', 'learn-drive' ); ?>">
					<?php foreach ( $learn_drive_chips as $learn_drive_chip ) : ?>
						<li><a href="<?php echo esc_url( $learn_drive_chip[1] ); ?>"><?php echo esc_html( $learn_drive_chip[0] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
				<!-- /wp:html -->
			<?php endif; ?>
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"42%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:42%">
			<!-- wp:html -->
			<div class="ld-mock" aria-hidden="true">
				<svg class="ld-mock__sign" viewBox="0 0 100 90"><path d="M50 4 96 86H4Z" fill="#fff" stroke="#d81e1e" stroke-width="9" stroke-linejoin="round"/><path d="M50 34v26M50 68v2" stroke="#111" stroke-width="8" stroke-linecap="round"/></svg>
				<div class="ld-mock__card">
					<div class="ld-mock__top"><span class="ld-mock__badge"><?php esc_html_e( 'Examen blanc', 'learn-drive' ); ?></span><span class="ld-mock__timer">18:42</span></div>
					<div class="ld-mock__progress"><span></span></div>
					<p class="ld-mock__q"><?php esc_html_e( 'Question 12 / 30', 'learn-drive' ); ?></p>
					<p class="ld-mock__text"><?php esc_html_e( 'Sans signalisation, je cède le passage aux véhicules qui arrivent par la droite.', 'learn-drive' ); ?></p>
					<div class="ld-mock__answers"><span class="is-right"><?php esc_html_e( 'Vrai', 'learn-drive' ); ?></span><span><?php esc_html_e( 'Faux', 'learn-drive' ); ?></span></div>
				</div>
				<div class="ld-mock__chip ld-mock__chip--score">✓ <?php esc_html_e( '28 / 30 — réussi', 'learn-drive' ); ?></div>
				<div class="ld-mock__chip ld-mock__chip--lang"><?php esc_html_e( 'Corrigé expliqué', 'learn-drive' ); ?></div>
			</div>
			<!-- /wp:html -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:learn-drive/stats {"className":"ld-proof"} /-->
</section>
<!-- /wp:group -->
