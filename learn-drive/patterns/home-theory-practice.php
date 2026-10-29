<?php
/**
 * Title: Accueil — Théorie et pratique
 * Slug: learn-drive/home-theory-practice
 * Categories: learn-drive-home, learn-drive-learning
 * Keywords: théorie, pratique, cours, manœuvres, vidéos
 * Description: Deux colonnes : cours écrits et préparation à l'épreuve pratique.
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50","top":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column {"className":"is-style-card"} -->
		<div class="wp-block-column is-style-card">
			<!-- wp:paragraph {"className":"ld-eyebrow"} -->
			<p class="ld-eyebrow"><?php esc_html_e( 'Théorie', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Les cours écrits', 'learn-drive' ); ?></h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Chaque leçon explique une règle avec des schémas, puis vous fait vérifier que vous l’avez comprise.', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:query {"queryId":14,"query":{"perPage":5,"pages":0,"offset":0,"postType":"ld_lesson","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"ld-compact-list"} -->
			<div class="wp-block-query ld-compact-list">
				<!-- wp:post-template -->
					<!-- wp:post-title {"level":3,"isLink":true} /-->
				<!-- /wp:post-template -->
			</div>
			<!-- /wp:query -->
			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( learn_drive_links()['learn'] ); ?>"><?php esc_html_e( 'Tous les cours', 'learn-drive' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"className":"is-style-card"} -->
		<div class="wp-block-column is-style-card">
			<!-- wp:paragraph {"className":"ld-eyebrow"} -->
			<p class="ld-eyebrow"><?php esc_html_e( 'Pratique', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Manœuvres et vérifications', 'learn-drive' ); ?></h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Préparez l’épreuve de conduite : vérifications techniques, manœuvres pas à pas, parcours types et conseils d’examinateurs.', 'learn-drive' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:list {"className":"is-style-checklist"} -->
			<ul class="wp-block-list is-style-checklist">
				<!-- wp:list-item -->
				<li><?php esc_html_e( 'Vérifications intérieures et extérieures', 'learn-drive' ); ?></li>
				<!-- /wp:list-item -->
				<!-- wp:list-item -->
				<li><?php esc_html_e( 'Créneau, bataille, demi-tour', 'learn-drive' ); ?></li>
				<!-- /wp:list-item -->
				<!-- wp:list-item -->
				<li><?php esc_html_e( 'Insertion, dépassement, giratoires', 'learn-drive' ); ?></li>
				<!-- /wp:list-item -->
				<!-- wp:list-item -->
				<li><?php esc_html_e( 'Questions orales et premiers secours', 'learn-drive' ); ?></li>
				<!-- /wp:list-item -->
			</ul>
			<!-- /wp:list -->
			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( learn_drive_links()['learn'] ); ?>"><?php esc_html_e( 'Préparer la pratique', 'learn-drive' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->
