<?php
/**
 * Title: Pied de page
 * Slug: learn-drive/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 * Description: Plan du site par rubrique, pages légales et politique éditoriale.
 *
 * @package LearnDrive
 */

$learn_drive_columns = array(
	__( 'Apprendre', 'learn-drive' )    => array(
		array( __( 'Tous les cours', 'learn-drive' ), '/cours/' ),
		array( __( 'Quiz par thème', 'learn-drive' ), '/quiz/' ),
		array( __( 'Panneaux de signalisation', 'learn-drive' ), '/panneaux/' ),
		array( __( 'Lexique', 'learn-drive' ), '/lexique/' ),
	),
	__( "S'informer", 'learn-drive' )   => array(
		array( __( 'Actualités', 'learn-drive' ), '/actualites/' ),
		array( __( 'Infos & démarches', 'learn-drive' ), '/infos/' ),
		array( __( 'Qui sommes-nous', 'learn-drive' ), '/qui-sommes-nous/' ),
		array( __( 'Comment nous vérifions nos contenus', 'learn-drive' ), '/politique-editoriale/' ),
	),
	__( 'Informations', 'learn-drive' ) => array(
		array( __( 'Mentions légales', 'learn-drive' ), '/mentions-legales/' ),
		array( __( 'Confidentialité', 'learn-drive' ), '/politique-de-confidentialite/' ),
		array( __( 'Contact', 'learn-drive' ), '/contact/' ),
	),
);
?>
<!-- wp:group {"className":"ld-footer","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-group ld-footer" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--40)">
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column {"width":"34%"} -->
		<div class="wp-block-column" style="flex-basis:34%">
			<!-- wp:site-title {"level":0} /-->
			<!-- wp:site-tagline {"style":{"color":{"text":"var:preset|color|muted"}}} /-->
			<!-- wp:paragraph {"style":{"color":{"text":"var:preset|color|muted"}}} -->
			<p class="has-text-color" style="color:var(--wp--preset--color--muted)"><?php esc_html_e( 'Cours, quiz et examens blancs rédigés et vérifiés par des enseignants, mis à jour à chaque évolution de la réglementation.', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<?php foreach ( $learn_drive_columns as $learn_drive_title => $learn_drive_links ) : ?>
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"level":2} -->
			<h2 class="wp-block-heading"><?php echo esc_html( $learn_drive_title ); ?></h2>
			<!-- /wp:heading -->
			<!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","orientation":"vertical"},"style":{"spacing":{"blockGap":"0.5rem"}}} -->
			<?php foreach ( $learn_drive_links as $learn_drive_link ) : ?>
				<!-- wp:navigation-link {"label":"<?php echo esc_attr( $learn_drive_link[0] ); ?>","url":"<?php echo esc_url( home_url( $learn_drive_link[1] ) ); ?>","kind":"custom","isTopLevelLink":true} /-->
			<?php endforeach; ?>
			<!-- /wp:navigation -->
		</div>
		<!-- /wp:column -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:columns -->

	<!-- wp:group {"className":"ld-footer__legal","style":{"spacing":{"padding":{"top":"var:preset|spacing|30"},"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group ld-footer__legal" style="margin-top:var(--wp--preset--spacing--50);padding-top:var(--wp--preset--spacing--30)">
		<!-- wp:paragraph {"fontSize":"x-small"} -->
		<p class="has-x-small-font-size">© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
		<!-- /wp:paragraph -->
		<!-- wp:paragraph {"fontSize":"x-small"} -->
		<p class="has-x-small-font-size"><a href="<?php echo esc_url( home_url( '/plan-du-site/' ) ); ?>"><?php esc_html_e( 'Plan du site', 'learn-drive' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
