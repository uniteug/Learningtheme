<?php
/**
 * Title: Accueil — Comment ça marche
 * Slug: learn-drive/home-steps
 * Categories: learn-drive-home, learn-drive-learning
 * Keywords: étapes, méthode, comment ça marche
 * Description: La méthode en trois étapes : apprendre, s'entraîner, se tester.
 *
 * @package LearnDrive
 */

$learn_drive_links = learn_drive_links();
$learn_drive_steps = array(
	array( __( 'Apprendre', 'learn-drive' ), __( 'Des leçons courtes, illustrées et à jour de la réglementation, thème par thème.', 'learn-drive' ), __( 'Voir les cours', 'learn-drive' ), $learn_drive_links['learn'] ),
	array( __( 'S’entraîner', 'learn-drive' ), __( 'Des quiz par thème avec correction immédiate et l’explication de chaque réponse.', 'learn-drive' ), __( 'Faire un quiz', 'learn-drive' ), $learn_drive_links['quiz'] ),
	array( __( 'Se tester', 'learn-drive' ), __( 'Des examens blancs au format officiel : même nombre de questions, même temps, même seuil.', 'learn-drive' ), __( 'Lancer un examen blanc', 'learn-drive' ), $learn_drive_links['exam'] ),
);
?>
<!-- wp:group {"tagName":"section","className":"ld-reveal","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull ld-reveal" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:group {"className":"ld-section-head","layout":{"type":"default"}} -->
	<div class="wp-block-group ld-section-head">
		<!-- wp:paragraph {"className":"ld-eyebrow"} -->
		<p class="ld-eyebrow"><?php esc_html_e( 'La méthode', 'learn-drive' ); ?></p>
		<!-- /wp:paragraph -->
		<!-- wp:heading -->
		<h2 class="wp-block-heading"><?php esc_html_e( 'Trois étapes jusqu’au jour de l’examen', 'learn-drive' ); ?></h2>
		<!-- /wp:heading -->
	</div>
	<!-- /wp:group -->

	<!-- wp:html -->
	<ol class="ld-steps">
		<?php foreach ( $learn_drive_steps as $learn_drive_step ) : ?>
			<li><h3><?php echo esc_html( $learn_drive_step[0] ); ?></h3><p><?php echo esc_html( $learn_drive_step[1] ); ?></p><a href="<?php echo esc_url( $learn_drive_step[3] ); ?>"><?php echo esc_html( $learn_drive_step[2] ); ?></a></li>
		<?php endforeach; ?>
	</ol>
	<!-- /wp:html -->
</section>
<!-- /wp:group -->
