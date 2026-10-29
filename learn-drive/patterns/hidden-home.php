<?php
/**
 * Title: Accueil (assemblage selon le volet de présentation)
 * Slug: learn-drive/home
 * Inserter: no
 * Description: Portail d'information et d'apprentissage pour un site gratuit ou freemium ; vitrine commerciale (offres, essai, témoignages) pour un site payant.
 *
 * @package LearnDrive
 */

if ( learn_drive_is_commercial() ) {
	$learn_drive_sections = array( 'home-hero', 'home-tracks', 'home-free', 'home-steps', 'home-testimonials', 'home-premium', 'home-topics', 'home-news', 'home-app', 'faq', 'home-cta' );
} else {
	$learn_drive_sections = array( 'home-hero', 'home-tracks', 'home-news', 'home-steps', 'home-topics', 'home-question-of-the-day', 'home-guides', 'home-signs', 'home-europe', 'home-app', 'faq' );
}

foreach ( $learn_drive_sections as $learn_drive_section ) {
	echo '<!-- wp:pattern {"slug":"learn-drive/' . esc_attr( $learn_drive_section ) . '"} /-->' . "\n";
}
