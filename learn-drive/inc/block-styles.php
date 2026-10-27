<?php
/**
 * Styles de blocs (variations) du thème.
 *
 * Les règles CSS correspondantes sont dans assets/css/theme.css afin d'être
 * disponibles dans l'éditeur comme sur le site.
 *
 * @package LearnDrive
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enregistre les variations de style.
 */
function learn_drive_register_block_styles() {
	$styles = array(
		'core/group'         => array(
			'card'          => __( 'Carte', 'learn-drive' ),
			'callout'       => __( 'Encadré', 'learn-drive' ),
			'key-changes'   => __( 'Ce qui change pour vous', 'learn-drive' ),
			'section-brand' => __( 'Section couleur', 'learn-drive' ),
		),
		'core/column'        => array(
			'card' => __( 'Carte', 'learn-drive' ),
		),
		'core/list'          => array(
			'checklist' => __( 'Liste à cocher', 'learn-drive' ),
			'steps'     => __( 'Étapes numérotées', 'learn-drive' ),
		),
		'core/post-terms'    => array(
			'pill' => __( 'Pastille', 'learn-drive' ),
		),
		'core/table'         => array(
			'costs' => __( 'Tableau de coûts', 'learn-drive' ),
		),
		'core/details'       => array(
			'faq' => __( 'Question de FAQ', 'learn-drive' ),
		),
		'core/image'         => array(
			'framed' => __( 'Encadrée', 'learn-drive' ),
		),
		'core/navigation'    => array(
			'pills' => __( 'Onglets', 'learn-drive' ),
		),
		'core/post-template' => array(
			'divided' => __( 'Liste séparée', 'learn-drive' ),
		),
	);

	foreach ( $styles as $block => $variations ) {
		foreach ( $variations as $name => $label ) {
			register_block_style(
				$block,
				array(
					'name'  => $name,
					'label' => $label,
				)
			);
		}
	}
}
add_action( 'init', 'learn_drive_register_block_styles' );
