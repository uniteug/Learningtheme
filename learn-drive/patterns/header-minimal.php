<?php
/**
 * Title: En-tête minimal (quiz)
 * Slug: learn-drive/header-minimal
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 * Description: En-tête sans distraction pour les quiz et examens blancs.
 *
 * @package LearnDrive
 */

?>
<!-- wp:group {"className":"ld-header","layout":{"type":"constrained","contentSize":"1240px"}} -->
<div class="wp-block-group ld-header">
	<!-- wp:group {"className":"ld-header__bar","style":{"dimensions":{"minHeight":"56px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
	<div class="wp-block-group ld-header__bar" style="min-height:56px">
		<!-- wp:site-title {"level":0,"fontSize":"medium"} /-->

		<!-- wp:html -->
		<button type="button" class="ld-theme-toggle" aria-pressed="false" title="<?php echo esc_attr__( 'Mode sombre', 'learn-drive' ); ?>"><span class="ld-sr-only"><?php esc_html_e( 'Mode sombre', 'learn-drive' ); ?></span><svg class="ld-icon-moon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20.7 14.6A8.5 8.5 0 0 1 9.4 3.3a.75.75 0 0 0-1-.9A10 10 0 1 0 21.6 15.6a.75.75 0 0 0-.9-1Z"/></svg><svg class="ld-icon-sun" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="5" fill="currentColor"/></svg></button>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
