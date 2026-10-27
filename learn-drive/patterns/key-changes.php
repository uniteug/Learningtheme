<?php
/**
 * Title: Ce qui change pour vous
 * Slug: learn-drive/key-changes
 * Categories: learn-drive-editorial
 * Keywords: actualité, résumé, changement, réforme
 * Block Types: core/group
 * Description: Encadré de trois puces maximum en tête d'article d'actualité.
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"className":"is-style-key-changes","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-key-changes">
	<!-- wp:heading {"level":2,"fontSize":"medium"} -->
	<h2 class="wp-block-heading has-medium-font-size"><?php esc_html_e( 'Ce qui change pour vous', 'learn-drive' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:list -->
	<ul class="wp-block-list">
		<!-- wp:list-item -->
		<li><?php esc_html_e( 'Premier changement concret, en une phrase.', 'learn-drive' ); ?></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><?php esc_html_e( 'Qui est concerné et à partir de quand.', 'learn-drive' ); ?></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><?php esc_html_e( 'Ce que vous devez faire (ou ne pas faire).', 'learn-drive' ); ?></li>
		<!-- /wp:list-item -->
	</ul>
	<!-- /wp:list -->
</div>
<!-- /wp:group -->
