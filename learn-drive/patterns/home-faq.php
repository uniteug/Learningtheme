<?php
/**
 * Title: FAQ éditoriale
 * Slug: learn-drive/faq
 * Categories: learn-drive-home, learn-drive-editorial, text
 * Keywords: faq, questions fréquentes
 * Description: Questions fréquentes en accordéon accessible (balise details), lisibles par Google dès le chargement.
 *
 * @package LearnDrive
 */

$learn_drive_faq = array(
	array( __( 'Combien de temps faut-il pour préparer le code ?', 'learn-drive' ), __( 'La plupart des candidats sont prêts en quatre à huit semaines en s’entraînant 20 à 30 minutes par jour. Visez un score régulier au-dessus du seuil de réussite sur plusieurs examens blancs avant de vous présenter.', 'learn-drive' ) ),
	array( __( 'Les questions sont-elles celles de l’examen officiel ?', 'learn-drive' ), __( 'Les questions officielles ne sont pas publiques. Nos questions reprennent le format, les thèmes et le niveau de l’examen, et chaque explication cite la règle ou l’article de loi concerné.', 'learn-drive' ) ),
	array( __( 'Puis-je m’entraîner sur mobile ?', 'learn-drive' ), __( 'Oui. Le site est conçu d’abord pour le mobile : une question par écran, de grandes zones tactiles et la reprise automatique d’un quiz interrompu.', 'learn-drive' ) ),
	array( __( 'Que contient l’accès premium ?', 'learn-drive' ), __( 'Les examens blancs illimités, toutes les explications détaillées, la révision de vos erreurs et des statistiques par thème. Les cours d’introduction, les fiches et l’actualité restent gratuits.', 'learn-drive' ) ),
);
?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"820px"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:heading -->
	<h2 class="wp-block-heading"><?php esc_html_e( 'Questions fréquentes', 'learn-drive' ); ?></h2>
	<!-- /wp:heading -->
	<?php foreach ( $learn_drive_faq as $learn_drive_item ) : ?>
	<!-- wp:details {"className":"is-style-faq"} -->
	<details class="wp-block-details is-style-faq"><summary><?php echo esc_html( $learn_drive_item[0] ); ?></summary>
		<!-- wp:paragraph -->
		<p><?php echo esc_html( $learn_drive_item[1] ); ?></p>
		<!-- /wp:paragraph -->
	</details>
	<!-- /wp:details -->
	<?php endforeach; ?>
</section>
<!-- /wp:group -->
