<?php
/**
 * Assistant de démarrage : secteur (libellés et URL), pays principal,
 * apparence (variation de style), modèle d'accès, contenu de démonstration,
 * pages essentielles et permaliens — en un seul écran.
 *
 * Proposé une fois après l'activation, puis accessible depuis le menu.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Admin\Setup;

use function LearnDrive\Core\settings;
use const LearnDrive\Core\OPTION;
use const LearnDrive\Core\TAX_COUNTRY;

defined( 'ABSPATH' ) || exit;

const PAGE = 'learn-drive-setup';

/**
 * Secteurs proposés et libellés associés.
 *
 * @return array<string,array>
 */
function sectors() {
	return array(
		'driving'       => array(
			'label'    => __( 'Permis de conduire', 'learn-drive-core' ),
			'hint'     => __( 'Code de la route, examens blancs officiels par pays, panneaux.', 'learn-drive-core' ),
			'settings' => array(
				'label_track'    => __( 'Catégorie de permis', 'learn-drive-core' ),
				'label_track_pl' => __( 'Catégories de permis', 'learn-drive-core' ),
				'label_topic'    => __( 'Thème officiel', 'learn-drive-core' ),
				'label_topic_pl' => __( 'Thèmes officiels', 'learn-drive-core' ),
				'label_sheet'    => __( 'Panneau', 'learn-drive-core' ),
				'label_sheet_pl' => __( 'Panneaux', 'learn-drive-core' ),
				'slug_sheet'     => 'panneaux',
			),
		),
		'school'        => array(
			'label'    => __( 'Soutien scolaire', 'learn-drive-core' ),
			'hint'     => __( 'Niveaux, chapitres, fiches de révision, exercices corrigés.', 'learn-drive-core' ),
			'settings' => array(
				'label_track'    => __( 'Niveau', 'learn-drive-core' ),
				'label_track_pl' => __( 'Niveaux', 'learn-drive-core' ),
				'label_topic'    => __( 'Chapitre', 'learn-drive-core' ),
				'label_topic_pl' => __( 'Chapitres', 'learn-drive-core' ),
				'label_sheet'    => __( 'Fiche de révision', 'learn-drive-core' ),
				'label_sheet_pl' => __( 'Fiches de révision', 'learn-drive-core' ),
				'slug_sheet'     => 'fiches',
			),
		),
		'languages'     => array(
			'label'    => __( 'Langues', 'learn-drive-core' ),
			'hint'     => __( 'Niveaux A1 à C2, compétences, fiches de vocabulaire.', 'learn-drive-core' ),
			'settings' => array(
				'label_track'    => __( 'Niveau', 'learn-drive-core' ),
				'label_track_pl' => __( 'Niveaux', 'learn-drive-core' ),
				'label_topic'    => __( 'Compétence', 'learn-drive-core' ),
				'label_topic_pl' => __( 'Compétences', 'learn-drive-core' ),
				'label_sheet'    => __( 'Fiche de vocabulaire', 'learn-drive-core' ),
				'label_sheet_pl' => __( 'Fiches de vocabulaire', 'learn-drive-core' ),
				'slug_sheet'     => 'vocabulaire',
			),
		),
		'certification' => array(
			'label'    => __( 'Certifications professionnelles', 'learn-drive-core' ),
			'hint'     => __( 'Certifications, domaines du référentiel, fiches mémo, examens blancs.', 'learn-drive-core' ),
			'settings' => array(
				'label_track'    => __( 'Certification', 'learn-drive-core' ),
				'label_track_pl' => __( 'Certifications', 'learn-drive-core' ),
				'label_topic'    => __( 'Domaine', 'learn-drive-core' ),
				'label_topic_pl' => __( 'Domaines', 'learn-drive-core' ),
				'label_sheet'    => __( 'Fiche mémo', 'learn-drive-core' ),
				'label_sheet_pl' => __( 'Fiches mémo', 'learn-drive-core' ),
				'slug_sheet'     => 'fiches',
			),
		),
	);
}

/**
 * Pays proposés (en plus de ceux déjà créés).
 *
 * @return array<string,string> slug => nom.
 */
function countries() {
	$list  = array(
		'france'        => __( 'France', 'learn-drive-core' ),
		'belgique'      => __( 'Belgique', 'learn-drive-core' ),
		'suisse'        => __( 'Suisse', 'learn-drive-core' ),
		'canada'        => __( 'Canada', 'learn-drive-core' ),
		'luxembourg'    => __( 'Luxembourg', 'learn-drive-core' ),
		'maroc'         => __( 'Maroc', 'learn-drive-core' ),
		'algerie'       => __( 'Algérie', 'learn-drive-core' ),
		'tunisie'       => __( 'Tunisie', 'learn-drive-core' ),
		'senegal'       => __( 'Sénégal', 'learn-drive-core' ),
		'cote-d-ivoire' => __( 'Côte d’Ivoire', 'learn-drive-core' ),
		'cameroun'      => __( 'Cameroun', 'learn-drive-core' ),
		'italia'        => __( 'Italie', 'learn-drive-core' ),
	);
	$terms = get_terms(
		array(
			'taxonomy'   => TAX_COUNTRY,
			'hide_empty' => false,
		)
	);
	foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
		$list[ $term->slug ] = $term->name;
	}
	return $list;
}

/**
 * Variations de style du thème actif (avec la variation d'origine en tête).
 *
 * @return array<string,array{title:string,settings:array,styles:array}>
 */
function variations() {
	$out = array(
		'default' => array(
			'title'    => __( 'D’origine', 'learn-drive-core' ),
			'settings' => array(),
			'styles'   => array(),
		),
	);
	foreach ( \WP_Theme_JSON_Resolver::get_style_variations() as $variation ) {
		if ( empty( $variation['title'] ) ) {
			continue;
		}
		$out[ sanitize_title( $variation['title'] ) ] = array(
			'title'    => $variation['title'],
			'settings' => $variation['settings'] ?? array(),
			'styles'   => $variation['styles'] ?? array(),
		);
	}
	return $out;
}

/**
 * Couleurs d'aperçu d'une variation (fond, texte, principale, accent).
 *
 * @param array $variation Variation.
 * @return string[]
 */
function swatches( array $variation ) {
	$palette = $variation['settings']['color']['palette']['theme'] ?? $variation['settings']['color']['palette'] ?? wp_get_global_settings( array( 'color', 'palette', 'theme' ), array( 'origin' => 'theme' ) );
	$colors  = array();
	foreach ( (array) $palette as $color ) {
		if ( isset( $color['slug'], $color['color'] ) ) {
			$colors[ $color['slug'] ] = $color['color'];
		}
	}
	return array_values( array_filter( array( $colors['base'] ?? '', $colors['contrast'] ?? '', $colors['primary'] ?? '', $colors['accent'] ?? '' ) ) );
}

/**
 * Applique une variation de style (styles globaux de l'utilisateur).
 *
 * @param string $key Variation.
 * @return bool
 */
function apply_variation( $key ) {
	$variations = variations();
	if ( ! isset( $variations[ $key ] ) || ! class_exists( 'WP_Theme_JSON_Resolver' ) ) {
		return false;
	}
	$post_id = \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
	if ( ! $post_id ) {
		return false;
	}
	$config = array(
		'version'                     => \WP_Theme_JSON::LATEST_SCHEMA,
		'isGlobalStylesUserThemeJSON' => true,
	);
	if ( $variations[ $key ]['settings'] ) {
		$config['settings'] = $variations[ $key ]['settings'];
	}
	if ( $variations[ $key ]['styles'] ) {
		$config['styles'] = $variations[ $key ]['styles'];
	}
	$result = wp_update_post(
		wp_slash(
			array(
				'ID'           => $post_id,
				'post_content' => wp_json_encode( $config ),
			)
		),
		true
	);
	\WP_Theme_JSON_Resolver::clean_cached_data();
	if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
		wp_clean_theme_json_cache();
	}
	return ! is_wp_error( $result );
}

/**
 * L'utilisateur a-t-il déjà personnalisé les styles globaux ?
 *
 * @return bool
 */
function has_custom_styles() {
	$post_id = \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
	$config  = $post_id ? json_decode( (string) get_post_field( 'post_content', $post_id ), true ) : null;
	return is_array( $config ) && ( ! empty( $config['settings'] ) || ! empty( $config['styles'] ) );
}

/**
 * Crée une page si son adresse est libre.
 *
 * @param string $slug    Adresse.
 * @param string $title   Titre.
 * @param string $content Contenu.
 * @return int
 */
function ensure_page( $slug, $title, $content ) {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		return (int) $page->ID;
	}
	return (int) wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $title,
			'post_content' => $content,
		)
	);
}

/**
 * Applique les choix de l'assistant.
 *
 * @param array $choices sector, country, variation, access, demo, pages.
 * @return string[] Ce qui a été fait (pour le récapitulatif).
 */
function apply( array $choices ) {
	$done    = array();
	$sectors = sectors();
	$sector  = isset( $sectors[ $choices['sector'] ?? '' ] ) ? $choices['sector'] : 'driving';

	// Contenu de démonstration d'abord : il pose ses propres réglages, que les choix ci-dessous remplacent.
	if ( ! empty( $choices['demo'] ) ) {
		$count  = \LearnDrive\Core\Demo\import();
		$done[] = sprintf(
			/* translators: 1: questions, 2: lessons, 3: news */
			__( 'Démonstration importée : %1$d questions, %2$d leçons, %3$d actualités.', 'learn-drive-core' ),
			$count['questions'] ?? 0,
			$count['lessons'] ?? 0,
			$count['news'] ?? 0
		);
	}

	$changes = $sectors[ $sector ]['settings'];
	/* translators: %s: sector */
	$done[] = sprintf( __( 'Secteur : %s (libellés et adresses adaptés).', 'learn-drive-core' ), $sectors[ $sector ]['label'] );

	$country = sanitize_title( (string) ( $choices['country'] ?? '' ) );
	$list    = countries();
	if ( $country && isset( $list[ $country ] ) ) {
		if ( ! term_exists( $country, TAX_COUNTRY ) ) {
			wp_insert_term( $list[ $country ], TAX_COUNTRY, array( 'slug' => $country ) );
		}
		$changes['default_country'] = $country;
		/* translators: %s: country */
		$done[] = sprintf( __( 'Pays principal : %s.', 'learn-drive-core' ), $list[ $country ] );
	} else {
		$changes['default_country'] = '';
		$done[]                     = __( 'Tous les pays affichés sur l’accueil.', 'learn-drive-core' );
	}

	$access = (string) ( $choices['access'] ?? 'mixed' );
	if ( in_array( $access, array( 'free', 'mixed', 'paid' ), true ) ) {
		$changes['access_mode'] = $access;
		$labels                 = array(
			'free'  => __( 'tout gratuit', 'learn-drive-core' ),
			'mixed' => __( 'gratuit et premium', 'learn-drive-core' ),
			'paid'  => __( 'tout payant', 'learn-drive-core' ),
		);
		/* translators: %s: access model */
		$done[] = sprintf( __( 'Modèle d’accès : %s.', 'learn-drive-core' ), $labels[ $access ] );
	}

	$current = get_option( OPTION, array() );
	update_option( OPTION, array_merge( is_array( $current ) ? $current : array(), $changes ) );
	delete_option( 'ld_track_slugs' );
	update_option( 'ld_flush_rewrite', 1 );

	$variation = sanitize_key( (string) ( $choices['variation'] ?? '' ) );
	if ( $variation && 'learn-drive' === get_template() && apply_variation( $variation ) ) {
		/* translators: %s: style variation */
		$done[] = sprintf( __( 'Apparence : %s.', 'learn-drive-core' ), variations()[ $variation ]['title'] );
	}

	if ( ! empty( $choices['pages'] ) ) {
		$account = trim( (string) settings()['account_url'], '/' );
		ensure_page( $account ? $account : 'mon-compte', __( 'Mon espace', 'learn-drive-core' ), '<!-- wp:learn-drive/dashboard /-->' );
		ensure_page( 'plan-du-site', __( 'Plan du site', 'learn-drive-core' ), "<!-- wp:page-list /-->\n\n<!-- wp:categories /-->" );
		$done[] = __( 'Pages « Mon espace » et « Plan du site » prêtes.', 'learn-drive-core' );

		if ( '' === (string) get_option( 'permalink_structure' ) ) {
			update_option( 'permalink_structure', '/actualites/%postname%/' );
			update_option( 'category_base', 'rubrique' );
			update_option( 'tag_base', 'mot-cle' );
			$done[] = __( 'Adresses lisibles activées (/actualites/titre/, /rubrique/nom/).', 'learn-drive-core' );
		}
		update_option( 'users_can_register', 1 );
	}

	update_option( 'ld_setup_done', time(), false );
	return $done;
}

/**
 * Menu.
 */
function menu() {
	add_submenu_page( 'learn-drive', __( 'Assistant de démarrage', 'learn-drive-core' ), __( 'Démarrage', 'learn-drive-core' ), 'manage_options', PAGE, __NAMESPACE__ . '\\render' );
}
add_action( 'admin_menu', __NAMESPACE__ . '\\menu', 20 );

/**
 * Ouvre l'assistant une seule fois, juste après la première activation.
 */
function maybe_redirect() {
	if ( ! get_option( 'ld_setup_redirect' ) || wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	delete_option( 'ld_setup_redirect' );
	if ( isset( $_GET['activate-multi'] ) || is_network_admin() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture seule.
		return;
	}
	wp_safe_redirect( admin_url( 'admin.php?page=' . PAGE ) );
	exit;
}
add_action( 'admin_init', __NAMESPACE__ . '\\maybe_redirect' );

/**
 * Rappel tant que l'assistant n'a pas été terminé (écrans du plugin seulement).
 */
function notice() {
	$screen = get_current_screen();
	if ( get_option( 'ld_setup_done' ) || ! current_user_can( 'manage_options' ) || ! $screen || ! str_contains( (string) $screen->id, 'learn-drive' ) || str_contains( (string) $screen->id, PAGE ) ) {
		return;
	}
	printf(
		'<div class="notice notice-info"><p>%s <a class="button button-primary" href="%s">%s</a></p></div>',
		esc_html__( 'Configurez votre site en deux minutes : secteur, pays, apparence et contenu de départ.', 'learn-drive-core' ),
		esc_url( admin_url( 'admin.php?page=' . PAGE ) ),
		esc_html__( 'Lancer l’assistant', 'learn-drive-core' )
	);
}
add_action( 'admin_notices', __NAMESPACE__ . '\\notice' );

/**
 * Enregistre les choix.
 */
function handle() {
	check_admin_referer( 'ld_setup' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Action non autorisée.', 'learn-drive-core' ), 403 );
	}
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- vérifié ci-dessus.
	$done = apply(
		array(
			'sector'    => sanitize_key( wp_unslash( $_POST['sector'] ?? '' ) ),
			'country'   => sanitize_title( wp_unslash( $_POST['country'] ?? '' ) ),
			'variation' => sanitize_key( wp_unslash( $_POST['variation'] ?? '' ) ),
			'access'    => sanitize_key( wp_unslash( $_POST['access'] ?? '' ) ),
			'demo'      => ! empty( $_POST['demo'] ),
			'pages'     => ! empty( $_POST['pages'] ),
		)
	);
	// phpcs:enable
	set_transient( 'ld_setup_report_' . get_current_user_id(), $done, 10 * MINUTE_IN_SECONDS );
	wp_safe_redirect( admin_url( 'admin.php?page=' . PAGE . '&done=1' ) );
	exit;
}
add_action( 'admin_post_ld_setup', __NAMESPACE__ . '\\handle' );

/**
 * Écran de l'assistant.
 */
function render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$report = get_transient( 'ld_setup_report_' . get_current_user_id() );
	if ( isset( $_GET['done'] ) && is_array( $report ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- affichage seul.
		delete_transient( 'ld_setup_report_' . get_current_user_id() );
		render_done( $report );
		return;
	}

	$current = settings();
	$sector  = 'driving';
	foreach ( sectors() as $key => $preset ) {
		if ( $preset['settings']['label_track'] === $current['label_track'] ) {
			$sector = $key;
			break;
		}
	}
	$has_content = (bool) wp_count_posts( \LearnDrive\Core\PT_QUESTION )->publish;
	$is_theme    = 'learn-drive' === get_template();
	$custom      = $is_theme && has_custom_styles();
	?>
	<div class="wrap ld-setup">
		<h1><?php esc_html_e( 'Assistant de démarrage', 'learn-drive-core' ); ?></h1>
		<p class="ld-setup__intro"><?php esc_html_e( 'Quelques choix pour adapter le thème et le plugin à votre site. Tout reste modifiable ensuite dans les réglages et l’éditeur du site.', 'learn-drive-core' ); ?></p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ld_setup">
			<?php wp_nonce_field( 'ld_setup' ); ?>

			<fieldset class="ld-setup__step">
				<legend><span class="ld-setup__num">1</span> <?php esc_html_e( 'Que proposez-vous ?', 'learn-drive-core' ); ?></legend>
				<div class="ld-setup__choices">
					<?php foreach ( sectors() as $key => $preset ) : ?>
						<label class="ld-setup__choice">
							<input type="radio" name="sector" value="<?php echo esc_attr( $key ); ?>" <?php checked( $sector, $key ); ?>>
							<strong><?php echo esc_html( $preset['label'] ); ?></strong>
							<span><?php echo esc_html( $preset['hint'] ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>

			<fieldset class="ld-setup__step">
				<legend><span class="ld-setup__num">2</span> <?php esc_html_e( 'Pays principal', 'learn-drive-core' ); ?></legend>
				<p><label for="ld-setup-country"><?php esc_html_e( 'L’accueil met en avant les catégories et thèmes de ce pays ; les règles d’examen (nombre de questions, seuil, chrono) se règlent par pays.', 'learn-drive-core' ); ?></label></p>
				<select id="ld-setup-country" name="country">
					<option value=""><?php esc_html_e( 'Tous les pays', 'learn-drive-core' ); ?></option>
					<?php foreach ( countries() as $slug => $name ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $current['default_country'] ? $current['default_country'] : 'france', $slug ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
			</fieldset>

			<?php if ( $is_theme ) : ?>
				<fieldset class="ld-setup__step">
					<legend><span class="ld-setup__num">3</span> <?php esc_html_e( 'Apparence', 'learn-drive-core' ); ?></legend>
					<div class="ld-setup__choices ld-setup__choices--styles">
						<?php if ( $custom ) : ?>
							<label class="ld-setup__choice">
								<input type="radio" name="variation" value="" checked>
								<strong><?php esc_html_e( 'Garder l’apparence actuelle', 'learn-drive-core' ); ?></strong>
								<span><?php esc_html_e( 'Vos réglages de l’éditeur du site sont conservés.', 'learn-drive-core' ); ?></span>
							</label>
						<?php endif; ?>
						<?php foreach ( variations() as $key => $variation ) : ?>
							<label class="ld-setup__choice">
								<input type="radio" name="variation" value="<?php echo esc_attr( $key ); ?>" <?php checked( ! $custom && 'default' === $key ); ?>>
								<span class="ld-setup__swatches" aria-hidden="true">
									<?php foreach ( swatches( $variation ) as $color ) : ?>
										<span style="background:<?php echo esc_attr( $color ); ?>"></span>
									<?php endforeach; ?>
								</span>
								<strong><?php echo esc_html( $variation['title'] ); ?></strong>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>
			<?php endif; ?>

			<fieldset class="ld-setup__step">
				<legend><span class="ld-setup__num"><?php echo $is_theme ? '4' : '3'; ?></span> <?php esc_html_e( 'Accès aux contenus', 'learn-drive-core' ); ?></legend>
				<div class="ld-setup__choices">
					<?php
					$access_choices = array(
						'free'  => array( __( 'Tout gratuit', 'learn-drive-core' ), __( 'Site financé par la publicité ou une auto-école.', 'learn-drive-core' ) ),
						'mixed' => array( __( 'Gratuit et premium', 'learn-drive-core' ), __( 'Découverte gratuite, examens et leçons avancées réservés.', 'learn-drive-core' ) ),
						'paid'  => array( __( 'Tout payant', 'learn-drive-core' ), __( 'Les actualités restent publiques pour le référencement.', 'learn-drive-core' ) ),
					);
					foreach ( $access_choices as $key => $choice ) :
						?>
						<label class="ld-setup__choice">
							<input type="radio" name="access" value="<?php echo esc_attr( $key ); ?>" <?php checked( $current['access_mode'], $key ); ?>>
							<strong><?php echo esc_html( $choice[0] ); ?></strong>
							<span><?php echo esc_html( $choice[1] ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>

			<fieldset class="ld-setup__step">
				<legend><span class="ld-setup__num"><?php echo $is_theme ? '5' : '4'; ?></span> <?php esc_html_e( 'Contenu de départ', 'learn-drive-core' ); ?></legend>
				<p><label><input type="checkbox" name="demo" value="1" <?php checked( ! $has_content ); ?>> <?php esc_html_e( 'Importer la démonstration (permis B : 125 questions, leçons, quiz, examens blancs France et Italie, panneaux, actualités)', 'learn-drive-core' ); ?></label></p>
				<p><label><input type="checkbox" name="pages" value="1" checked> <?php esc_html_e( 'Créer les pages « Mon espace » et « Plan du site », ouvrir les inscriptions et activer des adresses lisibles si ce n’est pas déjà fait', 'learn-drive-core' ); ?></label></p>
			</fieldset>

			<?php submit_button( __( 'Configurer mon site', 'learn-drive-core' ), 'primary hero' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Récapitulatif et suites possibles.
 *
 * @param string[] $report Ce qui a été fait.
 */
function render_done( array $report ) {
	?>
	<div class="wrap ld-setup">
		<h1><?php esc_html_e( 'Votre site est prêt', 'learn-drive-core' ); ?></h1>
		<ul class="ld-setup__report">
			<?php foreach ( $report as $line ) : ?>
				<li>✓ <?php echo esc_html( $line ); ?></li>
			<?php endforeach; ?>
		</ul>
		<h2><?php esc_html_e( 'Et maintenant', 'learn-drive-core' ); ?></h2>
		<p class="ld-setup__next">
			<a class="button button-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Voir le site', 'learn-drive-core' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'site-editor.php' ) ); ?>"><?php esc_html_e( 'Personnaliser l’apparence', 'learn-drive-core' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . \LearnDrive\Core\PT_QUESTION ) ); ?>"><?php esc_html_e( 'Écrire une question', 'learn-drive-core' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=learn-drive-tools' ) ); ?>"><?php esc_html_e( 'Importer des questions (CSV)', 'learn-drive-core' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=learn-drive-classes' ) ); ?>"><?php esc_html_e( 'Créer une classe', 'learn-drive-core' ); ?></a>
		</p>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . PAGE ) ); ?>"><?php esc_html_e( 'Relancer l’assistant', 'learn-drive-core' ); ?></a></p>
	</div>
	<?php
}
