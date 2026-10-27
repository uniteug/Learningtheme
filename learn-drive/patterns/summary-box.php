<?php
/**
 * Title: Encadré « En résumé »
 * Slug: learn-drive/summary-box
 * Categories: learn-drive-editorial
 * Keywords: résumé, démarche, guide, réponse
 * Description: La réponse en une phrase, pour les lecteurs pressés et les moteurs de recherche génératifs.
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"className":"is-style-callout","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-callout">
	<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"}}} -->
	<p style="font-weight:700"><?php esc_html_e( 'En résumé', 'learn-drive' ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'Répondez ici à la question principale en une ou deux phrases autonomes : le montant, le délai, le document ou la règle à retenir, avec sa source.', 'learn-drive' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
