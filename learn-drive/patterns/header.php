<?php
/**
 * Title: En-tête
 * Slug: learn-drive/header
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 * Description: Logo, menu principal, recherche, mode sombre et compte.
 *
 * @package LearnDrive
 */

$learn_drive_links = array(
	array( __( 'Cours', 'learn-drive' ), '/cours/' ),
	array( __( 'Quiz', 'learn-drive' ), '/quiz/' ),
	array( __( 'Panneaux', 'learn-drive' ), '/panneaux/' ),
	array( __( 'Actualités', 'learn-drive' ), '/actualites/' ),
	array( __( 'Infos & démarches', 'learn-drive' ), '/infos/' ),
);
?>
<!-- wp:group {"className":"ld-header","style":{"spacing":{"padding":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-group ld-header" style="padding-top:0;padding-bottom:0">
	<!-- wp:group {"className":"ld-header__bar","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
	<div class="wp-block-group ld-header__bar">
		<!-- wp:group {"style":{"spacing":{"blockGap":"0.75rem"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:site-logo {"width":40,"shouldSyncIcon":true} /-->
			<!-- wp:site-title {"level":0} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center","justifyContent":"right"}} -->
		<div class="wp-block-group">
			<!-- wp:navigation {"overlayMenu":"mobile","layout":{"type":"flex","justifyContent":"right"},"style":{"spacing":{"blockGap":"1.25rem"}}} -->
			<?php foreach ( $learn_drive_links as $learn_drive_link ) : ?>
				<!-- wp:navigation-link {"label":"<?php echo esc_attr( $learn_drive_link[0] ); ?>","url":"<?php echo esc_url( home_url( $learn_drive_link[1] ) ); ?>","kind":"custom","isTopLevelLink":true} /-->
			<?php endforeach; ?>
			<!-- /wp:navigation -->

			<!-- wp:group {"className":"ld-header__tools","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
			<div class="wp-block-group ld-header__tools">
				<!-- wp:search {"label":"<?php echo esc_attr__( 'Rechercher', 'learn-drive' ); ?>","showLabel":false,"placeholder":"<?php echo esc_attr__( 'Rechercher une question, un panneau…', 'learn-drive' ); ?>","buttonText":"<?php echo esc_attr__( 'Rechercher', 'learn-drive' ); ?>","buttonPosition":"button-only","buttonUseIcon":true,"isSearchFieldHidden":true} /-->

				<!-- wp:html -->
				<button type="button" class="ld-theme-toggle" aria-pressed="false" title="<?php echo esc_attr__( 'Mode sombre', 'learn-drive' ); ?>"><span class="ld-sr-only"><?php esc_html_e( 'Mode sombre', 'learn-drive' ); ?></span><svg class="ld-icon-moon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20.7 14.6A8.5 8.5 0 0 1 9.4 3.3a.75.75 0 0 0-1-.9A10 10 0 1 0 21.6 15.6a.75.75 0 0 0-.9-1Z"/></svg><svg class="ld-icon-sun" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm0-5a1 1 0 0 1 1 1v1.5a1 1 0 1 1-2 0V3a1 1 0 0 1 1-1Zm0 17.5a1 1 0 0 1 1 1V21a1 1 0 1 1-2 0v-.5a1 1 0 0 1 1-1ZM4.2 4.2a1 1 0 0 1 1.4 0l1 1a1 1 0 0 1-1.4 1.4l-1-1a1 1 0 0 1 0-1.4Zm13.2 13.2a1 1 0 0 1 1.4 0l1 1a1 1 0 0 1-1.4 1.4l-1-1a1 1 0 0 1 0-1.4ZM2 12a1 1 0 0 1 1-1h1.5a1 1 0 1 1 0 2H3a1 1 0 0 1-1-1Zm17.5 0a1 1 0 0 1 1-1H21a1 1 0 1 1 0 2h-.5a1 1 0 0 1-1-1ZM4.2 19.8a1 1 0 0 1 0-1.4l1-1a1 1 0 1 1 1.4 1.4l-1 1a1 1 0 0 1-1.4 0ZM17.4 6.6a1 1 0 0 1 0-1.4l1-1a1 1 0 1 1 1.4 1.4l-1 1a1 1 0 0 1-1.4 0Z"/></svg></button>
				<!-- /wp:html -->

				<!-- wp:buttons {"className":"ld-account"} -->
				<div class="wp-block-buttons ld-account">
					<!-- wp:button {"className":"is-style-outline"} -->
					<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/mon-compte/' ) ); ?>" rel="nofollow"><?php esc_html_e( 'Mon espace', 'learn-drive' ); ?></a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"ld-roadline","layout":{"type":"default"}} -->
<div class="wp-block-group ld-roadline"></div>
<!-- /wp:group -->
