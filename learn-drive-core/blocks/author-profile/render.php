<?php
/**
 * Bloc « Profil d'auteur » (page auteur : ProfilePage).
 *
 * @package LearnDriveCore
 */

defined( 'ABSPATH' ) || exit;

$learn_drive_author = is_author() ? get_queried_object() : get_userdata( (int) get_post_field( 'post_author', get_the_ID() ) );
if ( ! $learn_drive_author instanceof \WP_User ) {
	return;
}

$learn_drive_job       = (string) get_user_meta( $learn_drive_author->ID, 'ld_job_title', true );
$learn_drive_expertise = array_filter( array_map( 'trim', explode( ',', (string) get_user_meta( $learn_drive_author->ID, 'ld_expertise', true ) ) ) );
$learn_drive_links     = array_filter( array_map( 'trim', preg_split( '/[\r\n]+/', (string) get_user_meta( $learn_drive_author->ID, 'ld_same_as', true ) ) ) );

$learn_drive_html  = '<div class="ld-author__photo">' . get_avatar( $learn_drive_author->ID, 120, '', $learn_drive_author->display_name, array( 'loading' => 'eager' ) ) . '</div>';
$learn_drive_html .= '<div class="ld-author__body">';
$learn_drive_html .= '<h1 class="ld-author__name">' . esc_html( $learn_drive_author->display_name ) . '</h1>';
if ( $learn_drive_job ) {
	$learn_drive_html .= '<p class="ld-author__job">' . esc_html( $learn_drive_job ) . '</p>';
}
if ( $learn_drive_author->description ) {
	$learn_drive_html .= '<p class="ld-author__bio">' . esc_html( $learn_drive_author->description ) . '</p>';
}
if ( $learn_drive_expertise ) {
	$learn_drive_html .= '<ul class="ld-author__skills">';
	foreach ( $learn_drive_expertise as $learn_drive_skill ) {
		$learn_drive_html .= '<li class="ld-badge">' . esc_html( $learn_drive_skill ) . '</li>';
	}
	$learn_drive_html .= '</ul>';
}
if ( $learn_drive_links ) {
	$learn_drive_html .= '<ul class="ld-author__links">';
	foreach ( $learn_drive_links as $learn_drive_link ) {
		$learn_drive_html .= sprintf( '<li><a href="%1$s" rel="me noopener">%2$s</a></li>', esc_url( $learn_drive_link ), esc_html( wp_parse_url( $learn_drive_link, PHP_URL_HOST ) ) );
	}
	$learn_drive_html .= '</ul>';
}
$learn_drive_html .= '</div>';

printf( '<section %s>%s</section>', get_block_wrapper_attributes( array( 'class' => 'ld-author' ) ), $learn_drive_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
