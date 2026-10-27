<?php
/**
 * Désinstallation : supprime les réglages et tables techniques.
 *
 * Les contenus (questions, quiz, leçons…) et la progression des apprenants
 * sont conservés, sauf si la constante LEARN_DRIVE_CORE_PURGE vaut true.
 *
 * @package LearnDriveCore
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

foreach ( array( 'learn_drive_core', 'ld_track_slugs', 'ld_flush_rewrite', 'ld_indexnow_key', 'ld_db_version', 'ld_demo_imported', 'ld_404_log', 'ld_redirects' ) as $learn_drive_option ) {
	delete_option( $learn_drive_option );
}

if ( defined( 'LEARN_DRIVE_CORE_PURGE' ) && LEARN_DRIVE_CORE_PURGE ) {
	global $wpdb;
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ld_access, {$wpdb->prefix}ld_codes, {$wpdb->prefix}ld_class_members" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	delete_metadata( 'user', 0, 'ld_progress', '', true );
	foreach ( get_posts(
		array(
			'post_type'   => 'ld_class',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
		)
	) as $learn_drive_class ) {
		wp_delete_post( $learn_drive_class, true );
	}
}

// Le rôle « Moniteur » disparaît avec le plugin (les comptes deviennent abonnés).
foreach ( get_users(
	array(
		'role'   => 'ld_instructor',
		'fields' => 'ID',
	)
) as $learn_drive_user ) {
	( new WP_User( $learn_drive_user ) )->set_role( 'subscriber' );
}
remove_role( 'ld_instructor' );
