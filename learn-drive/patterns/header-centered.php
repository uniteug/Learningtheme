<?php
/**
 * Title: En-tête centré
 * Slug: learn-drive/header-centered
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 * Description: Titre centré façon magazine, menu sur une seconde ligne.
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
<!-- wp:group {"className":"ld-header","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-group ld-header" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--20)">
	<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"},"style":{"spacing":{"blockGap":"0.5rem"}}} -->
	<div class="wp-block-group">
		<!-- wp:site-logo {"width":48,"shouldSyncIcon":true} /-->
		<!-- wp:site-title {"level":0,"textAlign":"center"} /-->
		<!-- wp:site-tagline {"textAlign":"center","fontSize":"small","style":{"color":{"text":"var:preset|color|muted"}}} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"style":{"border":{"top":{"color":"var:preset|color|contrast","width":"1px"}},"spacing":{"padding":{"top":"0.5rem"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
	<div class="wp-block-group" style="border-top-color:var(--wp--preset--color--contrast);border-top-width:1px;padding-top:0.5rem">
		<!-- wp:navigation {"overlayMenu":"mobile","layout":{"type":"flex","justifyContent":"center"}} -->
		<?php foreach ( $learn_drive_links as $learn_drive_link ) : ?>
			<!-- wp:navigation-link {"label":"<?php echo esc_attr( $learn_drive_link[0] ); ?>","url":"<?php echo esc_url( home_url( $learn_drive_link[1] ) ); ?>","kind":"custom","isTopLevelLink":true} /-->
		<?php endforeach; ?>
		<!-- /wp:navigation -->

		<!-- wp:search {"label":"<?php echo esc_attr__( 'Rechercher', 'learn-drive' ); ?>","showLabel":false,"buttonText":"<?php echo esc_attr__( 'Rechercher', 'learn-drive' ); ?>","buttonPosition":"button-only","buttonUseIcon":true,"isSearchFieldHidden":true} /-->

		<!-- wp:html -->
		<button type="button" class="ld-theme-toggle" aria-pressed="false" title="<?php echo esc_attr__( 'Mode sombre', 'learn-drive' ); ?>"><span class="ld-sr-only"><?php esc_html_e( 'Mode sombre', 'learn-drive' ); ?></span><svg class="ld-icon-moon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20.7 14.6A8.5 8.5 0 0 1 9.4 3.3a.75.75 0 0 0-1-.9A10 10 0 1 0 21.6 15.6a.75.75 0 0 0-.9-1Z"/></svg><svg class="ld-icon-sun" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="5" fill="currentColor"/></svg></button>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
