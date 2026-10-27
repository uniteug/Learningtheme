<?php
/**
 * Espace auto-école (B2B) : un moniteur crée des classes, les élèves les
 * rejoignent avec un code d'invitation, le moniteur assigne des quiz (devoirs)
 * et suit la progression de ses élèves (statistiques, export CSV).
 *
 * - Rôle « Moniteur » (capacité ld_manage_classes), donnée aussi aux administrateurs.
 * - Une classe est un contenu privé (ld_class) dont l'auteur est le moniteur.
 * - Les inscriptions sont dans la table {prefix}ld_class_members.
 * - L'élève rejoint volontairement une classe et peut la quitter à tout moment ;
 *   le moniteur ne voit que les élèves de ses propres classes.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Classes;

use LearnDrive\Core\Progress;
use const LearnDrive\Core\PT_QUIZ;

defined( 'ABSPATH' ) || exit;

const PT = 'ld_class';

const CAP = 'ld_manage_classes';

const ROLE = 'ld_instructor';

/**
 * Alphabet des codes (sans caractères ambigus : 0/O, 1/I/L).
 */
const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

/**
 * Nom de la table des inscriptions.
 *
 * @return string
 */
function table() {
	global $wpdb;
	return $wpdb->prefix . 'ld_class_members';
}

/**
 * Table et rôle (activation et mise à jour du plugin).
 */
function install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table = table();
	dbDelta(
		"CREATE TABLE {$table} (
			class_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			joined_at datetime NOT NULL,
			PRIMARY KEY  (class_id,user_id),
			KEY user_id (user_id)
		) {$wpdb->get_charset_collate()};"
	);

	if ( ! get_role( ROLE ) ) {
		add_role(
			ROLE,
			__( 'Moniteur', 'learn-drive-core' ),
			array(
				'read' => true,
				CAP    => true,
			)
		);
	}
}

/**
 * Les administrateurs gèrent toutes les classes.
 *
 * @param array $allcaps Capacités de l'utilisateur.
 * @return array
 */
function grant_admins( $allcaps ) {
	if ( ! empty( $allcaps['manage_options'] ) ) {
		$allcaps[ CAP ] = true;
	}
	return $allcaps;
}
add_filter( 'user_has_cap', __NAMESPACE__ . '\\grant_admins' );

/**
 * Type de contenu privé « Classe ».
 */
function register() {
	register_post_type(
		PT,
		array(
			'labels'              => array(
				'name'          => __( 'Classes', 'learn-drive-core' ),
				'singular_name' => __( 'Classe', 'learn-drive-core' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => false,
			'show_in_rest'        => false,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => array( 'title', 'author' ),
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\\register' );

/**
 * Nouveau code d'invitation unique, au format ABCD-EFGH.
 *
 * @return string
 */
function generate_code() {
	do {
		$code = '';
		for ( $i = 0; $i < 8; $i++ ) {
			$code .= CODE_ALPHABET[ random_int( 0, strlen( CODE_ALPHABET ) - 1 ) ];
		}
		$code = substr( $code, 0, 4 ) . '-' . substr( $code, 4 );
	} while ( find_by_code( $code ) );

	return $code;
}

/**
 * Normalise un code saisi (minuscules, espaces, tiret oublié).
 *
 * @param string $code Code saisi.
 * @return string
 */
function normalize_code( $code ) {
	$code = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $code ) );
	return 8 === strlen( $code ) ? substr( $code, 0, 4 ) . '-' . substr( $code, 4 ) : $code;
}

/**
 * Classe correspondant à un code d'invitation.
 *
 * @param string $code Code.
 * @return int ID de la classe ou 0.
 */
function find_by_code( $code ) {
	$code = normalize_code( $code );
	if ( '' === $code ) {
		return 0;
	}
	$ids = get_posts(
		array(
			'post_type'      => PT,
			'post_status'    => 'private',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => '_ld_class_code', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $code, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Crée une classe.
 *
 * @param string $name     Nom (ex. « Permis B — session de mars »).
 * @param int    $owner_id Moniteur.
 * @param int    $seats    Places (0 = illimité).
 * @return int|\WP_Error
 */
function create( $name, $owner_id, $seats = 0 ) {
	$name = trim( wp_strip_all_tags( (string) $name ) );
	if ( '' === $name ) {
		return new \WP_Error( 'ld_class_name', __( 'Donnez un nom à la classe.', 'learn-drive-core' ) );
	}

	$id = wp_insert_post(
		array(
			'post_type'   => PT,
			'post_status' => 'private',
			'post_title'  => $name,
			'post_author' => (int) $owner_id,
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		return $id;
	}

	update_post_meta( $id, '_ld_class_code', generate_code() );
	update_post_meta( $id, '_ld_class_seats', max( 0, (int) $seats ) );
	update_post_meta( $id, '_ld_class_open', 1 );
	update_post_meta( $id, '_ld_class_assignments', array() );

	return (int) $id;
}

/**
 * Données d'une classe.
 *
 * @param int $class_id Classe.
 * @return array|null
 */
function get( $class_id ) {
	$post = get_post( $class_id );
	if ( ! $post || PT !== $post->post_type ) {
		return null;
	}
	return array(
		'id'          => (int) $post->ID,
		'name'        => $post->post_title,
		'owner'       => (int) $post->post_author,
		'code'        => (string) get_post_meta( $post->ID, '_ld_class_code', true ),
		'seats'       => (int) get_post_meta( $post->ID, '_ld_class_seats', true ),
		'open'        => (bool) get_post_meta( $post->ID, '_ld_class_open', true ),
		'assignments' => assignments( $post->ID ),
	);
}

/**
 * Le moniteur peut-il gérer cette classe ? (propriétaire ou administrateur)
 *
 * @param int $class_id Classe.
 * @param int $user_id  Utilisateur.
 * @return bool
 */
function can_manage( $class_id, $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	$class   = get( $class_id );
	if ( ! $class || ! user_can( $user_id, CAP ) ) {
		return false;
	}
	return $class['owner'] === $user_id || user_can( $user_id, 'manage_options' );
}

/**
 * Classes visibles par un moniteur (les siennes ; toutes pour un administrateur).
 *
 * @param int $user_id Moniteur.
 * @return int[]
 */
function managed_by( $user_id ) {
	$args = array(
		'post_type'      => PT,
		'post_status'    => 'private',
		'posts_per_page' => 200, // phpcs:ignore WordPress.WP.PostsPerPage -- classes d'un moniteur, borné.
		'fields'         => 'ids',
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	);
	if ( ! user_can( $user_id, 'manage_options' ) ) {
		$args['author'] = (int) $user_id;
	}
	return array_map( 'intval', get_posts( $args ) );
}

/**
 * Élèves d'une classe.
 *
 * @param int $class_id Classe.
 * @return array<int,string> ID utilisateur => date d'inscription (UTC).
 */
function members( $class_id ) {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT user_id, joined_at FROM %i WHERE class_id = %d ORDER BY joined_at ASC', table(), $class_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
	$out  = array();
	foreach ( (array) $rows as $row ) {
		$out[ (int) $row->user_id ] = $row->joined_at;
	}
	return $out;
}

/**
 * Classes d'un élève.
 *
 * @param int $user_id Élève.
 * @return int[]
 */
function classes_of( $user_id ) {
	global $wpdb;
	$ids = $wpdb->get_col( $wpdb->prepare( 'SELECT class_id FROM %i WHERE user_id = %d ORDER BY joined_at ASC', table(), $user_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
	return array_values( array_filter( array_map( 'intval', (array) $ids ), static fn( $id ) => null !== get( $id ) ) );
}

/**
 * Inscrit un élève avec un code d'invitation.
 *
 * @param string $code    Code.
 * @param int    $user_id Élève.
 * @return int|\WP_Error ID de la classe.
 */
function join( $code, $user_id ) {
	global $wpdb;

	$class_id = find_by_code( $code );
	$class    = $class_id ? get( $class_id ) : null;
	if ( ! $class ) {
		return new \WP_Error( 'ld_class_code', __( 'Code de classe inconnu. Vérifiez-le auprès de votre auto-école.', 'learn-drive-core' ), array( 'status' => 404 ) );
	}
	if ( ! $class['open'] ) {
		return new \WP_Error( 'ld_class_closed', __( 'Cette classe n’accepte plus de nouveaux élèves.', 'learn-drive-core' ), array( 'status' => 403 ) );
	}

	$members = members( $class_id );
	if ( isset( $members[ (int) $user_id ] ) ) {
		return new \WP_Error( 'ld_class_member', __( 'Vous faites déjà partie de cette classe.', 'learn-drive-core' ), array( 'status' => 409 ) );
	}
	if ( $class['seats'] && count( $members ) >= $class['seats'] ) {
		return new \WP_Error( 'ld_class_full', __( 'Cette classe est complète.', 'learn-drive-core' ), array( 'status' => 403 ) );
	}

	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		table(),
		array(
			'class_id'  => $class_id,
			'user_id'   => (int) $user_id,
			'joined_at' => current_time( 'mysql', true ),
		),
		array( '%d', '%d', '%s' )
	);

	/**
	 * Un élève vient de rejoindre une classe.
	 *
	 * @param int $class_id Classe.
	 * @param int $user_id  Élève.
	 */
	do_action( 'learn_drive_class_joined', $class_id, (int) $user_id );

	return $class_id;
}

/**
 * Retire un élève d'une classe.
 *
 * @param int $class_id Classe.
 * @param int $user_id  Élève.
 * @return bool
 */
function leave( $class_id, $user_id ) {
	global $wpdb;
	return (bool) $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		table(),
		array(
			'class_id' => (int) $class_id,
			'user_id'  => (int) $user_id,
		),
		array( '%d', '%d' )
	);
}

/**
 * Devoirs d'une classe.
 *
 * @param int $class_id Classe.
 * @return array<int,array{id:string,quiz:int,due:string,created:int}>
 */
function assignments( $class_id ) {
	$items = get_post_meta( $class_id, '_ld_class_assignments', true );
	$items = is_array( $items ) ? $items : array();
	return array_values( array_filter( $items, static fn( $item ) => 'publish' === get_post_status( (int) $item['quiz'] ) ) );
}

/**
 * Assigne un quiz à une classe.
 *
 * @param int    $class_id Classe.
 * @param int    $quiz_id  Quiz.
 * @param string $due      Date limite (AAAA-MM-JJ) ou vide.
 * @return string|\WP_Error Identifiant du devoir.
 */
function assign( $class_id, $quiz_id, $due = '' ) {
	if ( PT_QUIZ !== get_post_type( $quiz_id ) || 'publish' !== get_post_status( $quiz_id ) ) {
		return new \WP_Error( 'ld_class_quiz', __( 'Choisissez un quiz publié.', 'learn-drive-core' ) );
	}
	$due = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $due ) ? $due : '';

	$items   = get_post_meta( $class_id, '_ld_class_assignments', true );
	$items   = is_array( $items ) ? $items : array();
	$id      = substr( md5( $class_id . '-' . $quiz_id . '-' . microtime() ), 0, 10 );
	$items[] = array(
		'id'      => $id,
		'quiz'    => (int) $quiz_id,
		'due'     => $due,
		'created' => time(),
	);
	update_post_meta( $class_id, '_ld_class_assignments', $items );

	return $id;
}

/**
 * Supprime un devoir.
 *
 * @param int    $class_id      Classe.
 * @param string $assignment_id Devoir.
 */
function unassign( $class_id, $assignment_id ) {
	$items = get_post_meta( $class_id, '_ld_class_assignments', true );
	$items = array_values( array_filter( is_array( $items ) ? $items : array(), static fn( $item ) => $item['id'] !== $assignment_id ) );
	update_post_meta( $class_id, '_ld_class_assignments', $items );
}

/**
 * État d'un devoir pour un élève : fait si le quiz a été terminé après l'assignation.
 *
 * @param array $assignment Devoir.
 * @param array $progress   Progression de l'élève (Progress\get()).
 * @return array{done:bool,best:int,passed:bool,late:bool}
 */
function assignment_status( array $assignment, array $progress ) {
	$result = $progress['quizzes'][ (string) $assignment['quiz'] ] ?? null;
	$done   = $result && (int) $result['date'] >= (int) $assignment['created'];
	$late   = ! $done && $assignment['due'] && wp_date( 'Y-m-d' ) > $assignment['due'];

	return array(
		'done'   => (bool) $done,
		'best'   => $done ? (int) $result['best'] : 0,
		'passed' => $done && ! empty( $result['passed'] ),
		'late'   => (bool) $late,
	);
}

/**
 * Statistiques d'un élève pour son moniteur.
 *
 * @param int $user_id Élève.
 * @return array
 */
function student_stats( $user_id ) {
	$data    = Progress\get( $user_id );
	$seen    = array_sum( array_column( $data['topics'], 'seen' ) );
	$correct = array_sum( array_column( $data['topics'], 'correct' ) );
	$ready   = Progress\readiness( $user_id );

	$weakest = '';
	$lowest  = 101;
	foreach ( $data['topics'] as $term_id => $topic ) {
		if ( $topic['seen'] >= 5 ) {
			$rate = 100 * $topic['correct'] / $topic['seen'];
			if ( $rate < $lowest ) {
				$lowest  = $rate;
				$term    = get_term( (int) $term_id );
				$weakest = $term && ! is_wp_error( $term ) ? $term->name : '';
			}
		}
	}

	return array(
		'progress' => $data,
		'seen'     => (int) $seen,
		'rate'     => $seen ? (int) round( 100 * $correct / $seen ) : 0,
		'exams'    => count( $data['exams'] ),
		'ready'    => $ready,
		'weakest'  => $weakest,
		'last'     => (string) $data['streak']['last'],
		'reviews'  => count( Progress\due_reviews( $user_id, 500 ) ),
	);
}

/**
 * Tableau de suivi d'une classe (écran du moniteur et export CSV).
 *
 * @param int $class_id Classe.
 * @return array{columns:string[],rows:array[]}
 */
function report( $class_id ) {
	$class   = get( $class_id );
	$columns = array(
		__( 'Élève', 'learn-drive-core' ),
		__( 'E-mail', 'learn-drive-core' ),
		__( 'Inscrit le', 'learn-drive-core' ),
		__( 'Dernière activité', 'learn-drive-core' ),
		__( 'Questions répondues', 'learn-drive-core' ),
		__( 'Bonnes réponses (%)', 'learn-drive-core' ),
		__( 'Examens blancs', 'learn-drive-core' ),
		__( 'Examens réussis, 5 derniers (%)', 'learn-drive-core' ),
		__( 'Prêt pour l’examen', 'learn-drive-core' ),
		__( 'Thème le plus faible', 'learn-drive-core' ),
		__( 'Erreurs à revoir', 'learn-drive-core' ),
	);
	foreach ( $class ? $class['assignments'] : array() as $assignment ) {
		$columns[] = get_the_title( $assignment['quiz'] );
	}

	$rows = array();
	foreach ( $class ? members( $class_id ) : array() as $user_id => $joined ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			continue;
		}
		$stats = student_stats( $user_id );
		$row   = array(
			'user_id' => $user_id,
			'cells'   => array(
				$user->display_name,
				$user->user_email,
				wp_date( 'Y-m-d', strtotime( $joined . ' UTC' ) ),
				$stats['last'],
				$stats['seen'],
				$stats['rate'],
				$stats['exams'],
				$stats['ready']['count'] ? $stats['ready']['percent'] : '',
				$stats['ready']['ready'] ? __( 'oui', 'learn-drive-core' ) : __( 'non', 'learn-drive-core' ),
				$stats['weakest'],
				$stats['reviews'],
			),
			'status'  => array(),
		);
		foreach ( $class['assignments'] as $assignment ) {
			$status          = assignment_status( $assignment, $stats['progress'] );
			$row['status'][] = $status;
			$row['cells'][]  = $status['done'] ? $status['best'] . ' %' : ( $status['late'] ? __( 'en retard', 'learn-drive-core' ) : __( 'à faire', 'learn-drive-core' ) );
		}
		$rows[] = $row;
	}

	return array(
		'columns' => $columns,
		'rows'    => $rows,
	);
}

/**
 * Export CSV (séparateur « ; » et BOM pour Excel).
 *
 * @param int $class_id Classe.
 * @return string
 */
function report_csv( $class_id ) {
	$report = report( $class_id );
	$handle = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	fwrite( $handle, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	fputcsv( $handle, $report['columns'], ';', '"', '' );
	foreach ( $report['rows'] as $row ) {
		// Neutralise les formules (injection CSV) dans les champs saisis par les utilisateurs.
		$cells = array_map( static fn( $cell ) => is_string( $cell ) && preg_match( '/^[=+\-@\t\r]/', $cell ) ? "'" . $cell : $cell, $row['cells'] );
		fputcsv( $handle, $cells, ';', '"', '' );
	}
	rewind( $handle );
	$csv = stream_get_contents( $handle );
	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	return $csv;
}

/**
 * Classes et devoirs d'un élève (tableau de bord et API).
 *
 * @param int $user_id Élève.
 * @return array[]
 */
function for_student( $user_id ) {
	$progress = Progress\get( $user_id );
	$out      = array();
	foreach ( classes_of( $user_id ) as $class_id ) {
		$class = get( $class_id );
		$owner = get_userdata( $class['owner'] );
		$items = array();
		foreach ( array_reverse( $class['assignments'] ) as $assignment ) {
			$items[] = array_merge(
				array(
					'quiz'  => $assignment['quiz'],
					'title' => get_the_title( $assignment['quiz'] ),
					'url'   => get_permalink( $assignment['quiz'] ),
					'due'   => $assignment['due'],
				),
				assignment_status( $assignment, $progress )
			);
		}
		$out[] = array(
			'id'          => $class_id,
			'name'        => $class['name'],
			'instructor'  => $owner ? $owner->display_name : '',
			'assignments' => $items,
		);
	}
	return $out;
}

/**
 * Nettoyage : élève supprimé, classe supprimée.
 *
 * @param int $user_id Utilisateur supprimé.
 */
function delete_user( $user_id ) {
	global $wpdb;
	$wpdb->delete( table(), array( 'user_id' => (int) $user_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}
add_action( 'deleted_user', __NAMESPACE__ . '\\delete_user' );

/**
 * Supprime les inscriptions d'une classe supprimée.
 *
 * @param int $post_id Contenu supprimé.
 */
function delete_class( $post_id ) {
	global $wpdb;
	if ( PT === get_post_type( $post_id ) ) {
		$wpdb->delete( table(), array( 'class_id' => (int) $post_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}
add_action( 'before_delete_post', __NAMESPACE__ . '\\delete_class' );

/**
 * Après connexion : les moniteurs arrivent sur leur espace, les apprenants
 * sur « Mon compte » plutôt que sur le profil de l'administration.
 *
 * @param string             $redirect_to URL de destination.
 * @param string             $requested   URL demandée par le formulaire.
 * @param \WP_User|\WP_Error $user        Utilisateur.
 * @return string
 */
function login_redirect( $redirect_to, $requested, $user ) {
	if ( ! $user instanceof \WP_User || ( $requested && admin_url() !== $requested ) || admin_url() !== $redirect_to ) {
		return $redirect_to;
	}
	if ( in_array( ROLE, (array) $user->roles, true ) ) {
		return admin_url( 'admin.php?page=learn-drive-classes' );
	}
	if ( ! user_can( $user, 'edit_posts' ) ) {
		return home_url( user_trailingslashit( trim( (string) \LearnDrive\Core\setting( 'account_url' ), '/' ) ) );
	}
	return $redirect_to;
}
add_filter( 'login_redirect', __NAMESPACE__ . '\\login_redirect', 10, 3 );

/**
 * Exportateur RGPD : classes rejointes.
 *
 * @param array $exporters Exportateurs.
 * @return array
 */
function register_exporter( $exporters ) {
	$exporters['learn-drive-classes'] = array(
		'exporter_friendly_name' => __( 'Classes d’auto-école', 'learn-drive-core' ),
		'callback'               => __NAMESPACE__ . '\\export_personal_data',
	);
	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', __NAMESPACE__ . '\\register_exporter' );

/**
 * Classes rejointes par un utilisateur.
 *
 * @param string $email Adresse e-mail.
 * @return array
 */
function export_personal_data( $email ) {
	$user  = get_user_by( 'email', $email );
	$items = array();
	foreach ( $user ? members_of_user( $user->ID ) : array() as $class_id => $joined ) {
		$class   = get( $class_id );
		$items[] = array(
			'group_id'    => 'learn-drive-classes',
			'group_label' => __( 'Classes d’auto-école', 'learn-drive-core' ),
			'item_id'     => 'class-' . $class_id,
			'data'        => array(
				array(
					'name'  => __( 'Classe', 'learn-drive-core' ),
					'value' => $class ? $class['name'] : '#' . $class_id,
				),
				array(
					'name'  => __( 'Inscrit le', 'learn-drive-core' ),
					'value' => $joined,
				),
			),
		);
	}
	return array(
		'data' => $items,
		'done' => true,
	);
}

/**
 * Inscriptions d'un utilisateur.
 *
 * @param int $user_id Utilisateur.
 * @return array<int,string> ID de classe => date d'inscription.
 */
function members_of_user( $user_id ) {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT class_id, joined_at FROM %i WHERE user_id = %d', table(), $user_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
	$out  = array();
	foreach ( (array) $rows as $row ) {
		$out[ (int) $row->class_id ] = $row->joined_at;
	}
	return $out;
}

/**
 * Effaceur RGPD : retire l'utilisateur de toutes ses classes.
 *
 * @param array $erasers Effaceurs.
 * @return array
 */
function register_eraser( $erasers ) {
	$erasers['learn-drive-classes'] = array(
		'eraser_friendly_name' => __( 'Classes d’auto-école', 'learn-drive-core' ),
		'callback'             => __NAMESPACE__ . '\\erase_personal_data',
	);
	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', __NAMESPACE__ . '\\register_eraser' );

/**
 * Retire l'utilisateur de ses classes.
 *
 * @param string $email Adresse e-mail.
 * @return array
 */
function erase_personal_data( $email ) {
	$user    = get_user_by( 'email', $email );
	$removed = false;
	if ( $user ) {
		$removed = (bool) members_of_user( $user->ID );
		delete_user( $user->ID );
	}
	return array(
		'items_removed'  => $removed,
		'items_retained' => false,
		'messages'       => array(),
		'done'           => true,
	);
}
