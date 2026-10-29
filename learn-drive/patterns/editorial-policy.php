<?php
/**
 * Title: Encadré politique éditoriale
 * Slug: learn-drive/editorial-policy
 * Categories: learn-drive-editorial
 * Keywords: confiance, éditorial, vérification, E-E-A-T
 * Description: Lien vers « Comment nous vérifions nos contenus », à placer en fin d'article.
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"className":"is-style-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-callout">
	<!-- wp:paragraph {"fontSize":"small"} -->
	<p class="has-small-font-size"><strong><?php esc_html_e( 'Contenu vérifié.', 'learn-drive' ); ?></strong> <?php esc_html_e( 'Nos articles sont rédigés par des spécialistes de la sécurité routière, relus avant publication et mis à jour à chaque évolution des textes.', 'learn-drive' ); ?> <a href="<?php echo esc_url( learn_drive_links()['editorial'] ); ?>"><?php esc_html_e( 'Comment nous vérifions nos contenus', 'learn-drive' ); ?></a></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
