<?php
/**
 * Title: Titre (lecteurs d'écran) de la liste des actualités
 * Slug: learn-drive/news-latest-heading
 * Inserter: no
 *
 * Garde une hiérarchie de titres continue (H1 → H2 → titres des cartes en H3).
 *
 * @package LearnDrive
 */

?>
<!-- wp:heading {"className":"screen-reader-text"} -->
<h2 class="wp-block-heading screen-reader-text"><?php esc_html_e( 'Derniers articles', 'learn-drive' ); ?></h2>
<!-- /wp:heading -->
