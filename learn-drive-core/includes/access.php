<?php
/**
 * Moteur d'accès gratuit / payant.
 *
 * Le propriétaire du site décide de ce qui est gratuit : mode global (tout
 * gratuit, tout payant, mixte), puis exceptions par catégorie, cours, leçon,
 * quiz ou question, chaque niveau héritant de son parent. Le moteur ne connaît
 * pas le paiement : il lit une table d'accès « utilisateur / offre / début /
 * fin / origine », alimentée par WooCommerce, un module de paiement, un code
 * d'accès ou l'administrateur.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Access;

use function LearnDrive\Core\setting;
use function LearnDrive\Core\setting_url;
use const LearnDrive\Core\PT_COURSE;
use const LearnDrive\Core\PT_LESSON;
use const LearnDrive\Core\PT_QUIZ;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\TAX_TRACK;

defined( 'ABSPATH' ) || exit;

const RULES = array( 'inherit', 'free', 'registered', 'premium' );

/**
 * Nom de la table des accès.
 *
 * @return string
 */
function table() {
	global $wpdb;
	return $wpdb->prefix . 'ld_access';
}

/**
 * Nom de la table des codes d'accès.
 *
 * @return string
 */
function codes_table() {
	global $wpdb;
	return $wpdb->prefix . 'ld_codes';
}

/**
 * Crée ou met à jour les tables.
 */
function install_tables() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset = $wpdb->get_charset_collate();
	$access  = table();
	$codes   = codes_table();

	dbDelta(
		"CREATE TABLE {$access} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			offer varchar(100) NOT NULL,
			starts_at datetime NOT NULL,
			ends_at datetime NULL,
			source varchar(100) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_offer (user_id,offer),
			KEY source (source)
		) {$charset};"
	);

	dbDelta(
		"CREATE TABLE {$codes} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			code varchar(40) NOT NULL,
			offer varchar(100) NOT NULL,
			days int(11) NOT NULL DEFAULT 0,
			max_uses int(11) NOT NULL DEFAULT 1,
			uses int(11) NOT NULL DEFAULT 0,
			group_name varchar(100) NOT NULL DEFAULT '',
			expires_at datetime NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY code (code)
		) {$charset};"
	);

	update_option( 'ld_db_version', \LearnDrive\Core\VERSION, false );
}

/**
 * Met à jour les tables après une mise à jour du plugin.
 */
function maybe_upgrade() {
	if ( get_option( 'ld_db_version' ) !== \LearnDrive\Core\VERSION ) {
		install_tables();
		\LearnDrive\Core\Classes\install();
		\LearnDrive\Core\SEO\Index\schedule_recompute();
	}
}
add_action( 'admin_init', __NAMESPACE__ . '\\maybe_upgrade' );

/**
 * Règle d'accès d'un contenu, après héritage.
 *
 * @param int $post_id Contenu.
 * @return string free|registered|premium
 */
function rule( $post_id ) {
	static $cache = array();
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$rule = resolve_rule( (int) $post_id, 0 );

	/**
	 * Filtre la règle d'accès d'un contenu.
	 *
	 * @param string $rule    free|registered|premium.
	 * @param int    $post_id Contenu.
	 */
	$cache[ $post_id ] = apply_filters( 'learn_drive_access_rule', $rule, $post_id );
	return $cache[ $post_id ];
}

/**
 * Résolution récursive : contenu > parent (cours) > catégorie > réglage global.
 *
 * @param int $post_id Contenu.
 * @param int $depth   Profondeur (garde-fou).
 * @return string
 */
function resolve_rule( $post_id, $depth ) {
	$own = (string) get_post_meta( $post_id, '_ld_access', true );
	if ( in_array( $own, array( 'free', 'registered', 'premium' ), true ) ) {
		return $own;
	}

	$type = get_post_type( $post_id );

	if ( PT_LESSON === $type && $depth < 3 ) {
		$course = (int) get_post_meta( $post_id, '_ld_course', true );
		if ( $course ) {
			return resolve_rule( $course, $depth + 1 );
		}
	}

	if ( in_array( $type, array( PT_COURSE, PT_LESSON, PT_QUIZ, PT_QUESTION ), true ) ) {
		$terms = get_the_terms( $post_id, TAX_TRACK );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_rule = (string) get_term_meta( $term->term_id, 'ld_access', true );
				if ( in_array( $term_rule, array( 'free', 'registered', 'premium' ), true ) ) {
					return $term_rule;
				}
			}
		}
	}

	return site_default( $post_id );
}

/**
 * Règle par défaut selon le mode global.
 *
 * @param int $post_id Contenu.
 * @return string
 */
function site_default( $post_id ) {
	$type = get_post_type( $post_id );

	// Actualités, infos, fiches et lexique restent publics, quel que soit le mode.
	if ( ! in_array( $type, array( PT_COURSE, PT_LESSON, PT_QUIZ, PT_QUESTION ), true ) ) {
		return 'free';
	}

	switch ( setting( 'access_mode' ) ) {
		case 'free':
			return 'free';
		case 'paid':
			return 'premium';
		default:
			if ( PT_QUIZ === $type && 'exam' === get_post_meta( $post_id, '_ld_mode', true ) ) {
				return in_array( setting( 'mixed_exam' ), array( 'free', 'registered', 'premium' ), true ) ? setting( 'mixed_exam' ) : 'free';
			}
			return in_array( setting( 'mixed_default' ), array( 'free', 'registered', 'premium' ), true ) ? setting( 'mixed_default' ) : 'free';
	}
}

/**
 * Indique si un utilisateur peut accéder à un contenu.
 *
 * @param int      $post_id Contenu.
 * @param int|null $user_id Utilisateur (courant par défaut).
 * @return bool
 */
function can_access( $post_id, $user_id = null ) {
	$user_id = null === $user_id ? get_current_user_id() : (int) $user_id;
	$rule    = rule( $post_id );

	if ( 'free' === $rule ) {
		$allowed = true;
	} elseif ( $user_id && user_can( $user_id, 'edit_others_posts' ) ) {
		$allowed = true;
	} elseif ( 'registered' === $rule ) {
		$allowed = $user_id > 0;
	} else {
		$allowed = $user_id > 0 && has_entitlement( $user_id, $post_id );
	}

	/**
	 * Filtre le droit d'accès d'un utilisateur à un contenu.
	 *
	 * @param bool $allowed Accès.
	 * @param int  $post_id Contenu.
	 * @param int  $user_id Utilisateur.
	 */
	return (bool) apply_filters( 'learn_drive_user_can_access', $allowed, $post_id, $user_id );
}

/**
 * Offres qui débloquent un contenu.
 *
 * @param int $post_id Contenu.
 * @return string[]
 */
function offers_for( $post_id ) {
	$offers = array( 'all', 'post:' . (int) $post_id );
	$terms  = get_the_terms( $post_id, TAX_TRACK );
	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$offers[] = 'track:' . $term->slug;
		}
	}
	$course = (int) get_post_meta( $post_id, '_ld_course', true );
	if ( $course ) {
		$offers[] = 'post:' . $course;
	}
	return $offers;
}

/**
 * Offres actives d'un utilisateur.
 *
 * @param int $user_id Utilisateur.
 * @return array[] Lignes { offer, ends_at, source }.
 */
function active_offers( $user_id ) {
	global $wpdb;

	$cache_key = 'ld_offers_' . $user_id;
	$rows      = wp_cache_get( $cache_key, 'learn-drive' );

	if ( false === $rows ) {
		$now  = current_time( 'mysql', true );
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT offer, ends_at, source FROM %i WHERE user_id = %d AND starts_at <= %s AND ( ends_at IS NULL OR ends_at > %s )', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
				table(),
				$user_id,
				$now,
				$now
			),
			ARRAY_A
		);
		wp_cache_set( $cache_key, $rows, 'learn-drive', 5 * MINUTE_IN_SECONDS );
	}

	return is_array( $rows ) ? $rows : array();
}

/**
 * L'utilisateur a-t-il une offre active couvrant le contenu ?
 *
 * @param int $user_id Utilisateur.
 * @param int $post_id Contenu (0 = n'importe quelle offre).
 * @return bool
 */
function has_entitlement( $user_id, $post_id = 0 ) {
	$offers = wp_list_pluck( active_offers( $user_id ), 'offer' );
	if ( ! $post_id ) {
		return (bool) $offers;
	}
	return (bool) array_intersect( $offers, offers_for( $post_id ) );
}

/**
 * L'utilisateur est-il premium (au moins une offre active) ?
 *
 * @param int|null $user_id Utilisateur.
 * @return bool
 */
function is_premium( $user_id = null ) {
	$user_id = null === $user_id ? get_current_user_id() : (int) $user_id;
	return $user_id > 0 && has_entitlement( $user_id );
}

/**
 * Ouvre un accès.
 *
 * @param int    $user_id Utilisateur.
 * @param string $offer   Offre (all, track:slug, post:ID).
 * @param int    $days    Durée en jours (0 = illimité).
 * @param string $source  Origine (wc:123, code:ABCD, admin, app…).
 * @return int ID de la ligne.
 */
function grant( $user_id, $offer, $days = 0, $source = 'admin' ) {
	global $wpdb;

	$now = time();
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		table(),
		array(
			'user_id'    => (int) $user_id,
			'offer'      => sanitize_text_field( $offer ),
			'starts_at'  => gmdate( 'Y-m-d H:i:s', $now ),
			'ends_at'    => $days > 0 ? gmdate( 'Y-m-d H:i:s', $now + $days * DAY_IN_SECONDS ) : null,
			'source'     => sanitize_text_field( $source ),
			'created_at' => gmdate( 'Y-m-d H:i:s', $now ),
		)
	);
	wp_cache_delete( 'ld_offers_' . (int) $user_id, 'learn-drive' );

	/**
	 * Un accès vient d'être ouvert.
	 *
	 * @param int    $user_id Utilisateur.
	 * @param string $offer   Offre.
	 * @param int    $days    Durée.
	 * @param string $source  Origine.
	 */
	do_action( 'learn_drive_access_granted', $user_id, $offer, $days, $source );

	return (int) $wpdb->insert_id;
}

/**
 * Ferme les accès d'une origine (remboursement, annulation).
 *
 * @param string $source Origine.
 */
function revoke_source( $source ) {
	global $wpdb;

	$users = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT user_id FROM %i WHERE source = %s', table(), $source ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
	$wpdb->update( table(), array( 'ends_at' => current_time( 'mysql', true ) ), array( 'source' => $source ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	foreach ( $users as $user_id ) {
		wp_cache_delete( 'ld_offers_' . (int) $user_id, 'learn-drive' );
	}
}

/**
 * Génère des codes d'accès (vente en agence, auto-école).
 *
 * @param int    $count    Nombre.
 * @param string $offer    Offre.
 * @param int    $days     Durée en jours.
 * @param int    $max_uses Utilisations par code.
 * @param string $group    Groupe (classe, auto-école).
 * @return string[]
 */
function generate_codes( $count, $offer, $days, $max_uses = 1, $group = '' ) {
	global $wpdb;

	$codes = array();
	$count = min( 500, (int) $count );
	for ( $i = 0; $i < $count; $i++ ) {
		$code = strtoupper( implode( '-', str_split( wp_generate_password( 12, false, false ), 4 ) ) );
		$ok   = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			codes_table(),
			array(
				'code'       => $code,
				'offer'      => sanitize_text_field( $offer ),
				'days'       => (int) $days,
				'max_uses'   => max( 1, (int) $max_uses ),
				'group_name' => sanitize_text_field( $group ),
				'created_at' => current_time( 'mysql', true ),
			)
		);
		if ( $ok ) {
			$codes[] = $code;
		}
	}
	return $codes;
}

/**
 * Utilise un code d'accès.
 *
 * @param string $code    Code saisi.
 * @param int    $user_id Utilisateur.
 * @return true|\WP_Error
 */
function redeem_code( $code, $user_id ) {
	global $wpdb;

	$code = strtoupper( preg_replace( '/[^A-Za-z0-9-]/', '', (string) $code ) );
	$row  = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE code = %s', codes_table(), $code ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder

	if ( ! $row ) {
		return new \WP_Error( 'ld_code_invalid', __( 'Ce code n’existe pas.', 'learn-drive-core' ), array( 'status' => 404 ) );
	}
	if ( (int) $row['uses'] >= (int) $row['max_uses'] ) {
		return new \WP_Error( 'ld_code_used', __( 'Ce code a déjà été utilisé.', 'learn-drive-core' ), array( 'status' => 409 ) );
	}
	if ( $row['expires_at'] && strtotime( $row['expires_at'] . ' UTC' ) < time() ) {
		return new \WP_Error( 'ld_code_expired', __( 'Ce code a expiré.', 'learn-drive-core' ), array( 'status' => 410 ) );
	}

	$updated = $wpdb->query( $wpdb->prepare( 'UPDATE %i SET uses = uses + 1 WHERE id = %d AND uses < max_uses', codes_table(), $row['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
	if ( ! $updated ) {
		return new \WP_Error( 'ld_code_used', __( 'Ce code a déjà été utilisé.', 'learn-drive-core' ), array( 'status' => 409 ) );
	}

	grant( $user_id, $row['offer'], (int) $row['days'], 'code:' . $code );

	if ( $row['group_name'] ) {
		update_user_meta( $user_id, 'ld_group', sanitize_text_field( $row['group_name'] ) );
	}

	return true;
}

/**
 * URL de l'offre qui débloque un contenu, avec retour automatique ensuite.
 *
 * @param int $post_id Contenu verrouillé.
 * @return string
 */
function unlock_url( $post_id ) {
	return add_query_arg( 'redirect_to', rawurlencode( get_permalink( $post_id ) ), setting_url( 'premium_url' ) );
}

/**
 * Encadré affiché à la place d'un contenu réservé.
 *
 * La classe « ld-paywall » est déclarée dans les données structurées
 * (isAccessibleForFree + hasPart.cssSelector), pour ne pas être confondue
 * avec du cloaking.
 *
 * @param int    $post_id Contenu.
 * @param string $context lesson|question|quiz.
 * @return string
 */
function paywall_html( $post_id, $context = 'lesson' ) {
	$rule = rule( $post_id );

	if ( 'registered' === $rule ) {
		$title = __( 'Contenu gratuit réservé aux inscrits', 'learn-drive-core' );
		$text  = __( 'Créez votre compte gratuit pour lire la suite et suivre votre progression.', 'learn-drive-core' );
		$cta   = __( 'Créer mon compte gratuit', 'learn-drive-core' );
		$url   = wp_registration_url();
		if ( ! get_option( 'users_can_register' ) ) {
			$url = wp_login_url( get_permalink( $post_id ) );
			$cta = __( 'Se connecter', 'learn-drive-core' );
		}
	} else {
		$titles = array(
			'lesson'   => __( 'La suite de cette leçon est réservée aux abonnés', 'learn-drive-core' ),
			'question' => __( 'Correction détaillée réservée aux abonnés', 'learn-drive-core' ),
			'quiz'     => __( 'Ce quiz fait partie de l’accès premium', 'learn-drive-core' ),
		);
		$title  = $titles[ $context ] ?? $titles['lesson'];
		$text   = __( 'Débloquez toutes les explications, les examens blancs illimités et la révision de vos erreurs.', 'learn-drive-core' );
		$cta    = __( 'Débloquer l’accès', 'learn-drive-core' );
		$url    = unlock_url( $post_id );
	}

	$login = is_user_logged_in() ? '' : sprintf(
		'<p class="ld-paywall__login">%s <a href="%s" rel="nofollow">%s</a></p>',
		esc_html__( 'Déjà abonné ?', 'learn-drive-core' ),
		esc_url( wp_login_url( get_permalink( $post_id ) ) ),
		esc_html__( 'Se connecter', 'learn-drive-core' )
	);

	return sprintf(
		'<div class="ld-paywall" role="region" aria-label="%1$s"><p class="ld-paywall__title"><span class="ld-badge ld-badge--premium">%2$s</span> %1$s</p><p>%3$s</p><p><a class="wp-element-button ld-paywall__cta" href="%4$s" rel="nofollow">%5$s</a></p>%6$s</div>',
		esc_attr( $title ),
		esc_html( 'registered' === $rule ? __( 'Gratuit', 'learn-drive-core' ) : __( 'Premium', 'learn-drive-core' ) ),
		esc_html( $text ),
		esc_url( $url ),
		esc_html( $cta ),
		$login
	);
}

/**
 * Coupe le contenu réservé : le début reste lisible (jusqu'au bloc « Lire la
 * suite » ou aux deux premiers blocs), la suite est remplacée par l'encadré.
 *
 * @param string $content Contenu rendu.
 * @return string
 */
function filter_content( $content ) {
	$post_id = get_the_ID();

	if ( is_admin() || ! is_singular() || get_queried_object_id() !== $post_id ) {
		return $content;
	}

	$type = get_post_type( $post_id );

	// Les questions et quiz gèrent leur propre verrouillage dans leurs blocs.
	if ( ! in_array( $type, array( PT_LESSON, PT_COURSE ), true ) || can_access( $post_id ) ) {
		return $content;
	}

	$raw = get_post_field( 'post_content', $post_id );
	if ( str_contains( $raw, '<!--more-->' ) ) {
		$parts  = get_extended( $raw );
		$teaser = apply_filters( 'learn_drive_teaser_content', do_blocks( $parts['main'] ) );
	} else {
		$blocks = array_filter(
			parse_blocks( $raw ),
			static function ( $block ) {
				return ! empty( $block['blockName'] );
			}
		);
		$teaser = '';
		foreach ( array_slice( $blocks, 0, 2 ) as $block ) {
			$teaser .= render_block( $block );
		}
	}

	return '<div class="ld-teaser">' . $teaser . '</div>' . paywall_html( $post_id, 'lesson' );
}
add_filter( 'the_content', __NAMESPACE__ . '\\filter_content', 999 );
