<?php
/**
 * Title: Tableau de coûts
 * Slug: learn-drive/costs-table
 * Categories: learn-drive-editorial
 * Keywords: prix, coût, tarif, tableau
 * Description: Tableau de coûts avec total, lisible sur mobile.
 *
 * @package LearnDrive
 */

?>
<!-- wp:table {"className":"is-style-costs"} -->
<figure class="wp-block-table is-style-costs"><table><thead><tr><th><?php esc_html_e( 'Poste', 'learn-drive' ); ?></th><th><?php esc_html_e( 'Prix moyen', 'learn-drive' ); ?></th></tr></thead><tbody><tr><td><?php esc_html_e( 'Inscription et frais de dossier', 'learn-drive' ); ?></td><td>— €</td></tr><tr><td><?php esc_html_e( 'Examen du code', 'learn-drive' ); ?></td><td>— €</td></tr><tr><td><?php esc_html_e( 'Heures de conduite (20 h)', 'learn-drive' ); ?></td><td>— €</td></tr></tbody><tfoot><tr><td><?php esc_html_e( 'Total estimé', 'learn-drive' ); ?></td><td>— €</td></tr></tfoot></table><figcaption class="wp-element-caption"><?php esc_html_e( 'Prix indicatifs, vérifiés le JJ/MM/AAAA. Source : …', 'learn-drive' ); ?></figcaption></figure>
<!-- /wp:table -->
