<?php
/**
 * Mode sombre : automatique (préférence système) et bascule manuelle.
 *
 * Les couleurs sombres sont des jetons de theme.json (settings.custom.dark) :
 * chaque variation de style déclare sa propre palette sombre.
 *
 * @package LearnDrive
 */

defined( 'ABSPATH' ) || exit;

/**
 * Applique le choix mémorisé avant le premier rendu (aucun flash).
 */
function learn_drive_dark_mode_head_script() {
	$script = "(function(){try{var t=localStorage.getItem('ld-theme');if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t);}}catch(e){}})();";
	wp_print_inline_script_tag( $script, array( 'id' => 'learn-drive-theme-init' ) );
}
add_action( 'wp_head', 'learn_drive_dark_mode_head_script', 1 );

/**
 * Script de la bascule clair / sombre (quelques centaines d'octets, différé).
 */
function learn_drive_dark_mode_toggle_script() {
	wp_enqueue_script(
		'learn-drive-theme-toggle',
		get_template_directory_uri() . '/assets/js/theme-toggle.js',
		array(),
		LEARN_DRIVE_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'learn_drive_dark_mode_toggle_script' );
