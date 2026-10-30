<?php
/**
 * Écrans d'administration : réglages (accès, libellés, URL, SEO, médias,
 * API) et outils (import / export, contenu de démonstration).
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Admin\Settings;

use LearnDrive\Core\SEO\AI;
use function LearnDrive\Core\settings;
use function LearnDrive\Core\defaults;
use const LearnDrive\Core\OPTION;

defined( 'ABSPATH' ) || exit;

const SLUG = 'learn-drive';

/**
 * Menus.
 */
function menu() {
	add_menu_page( __( 'Learn & Drive', 'learn-drive-core' ), __( 'Learn & Drive', 'learn-drive-core' ), 'manage_options', SLUG, __NAMESPACE__ . '\\render', 'dashicons-welcome-learn-more', 20 );
	add_submenu_page( SLUG, __( 'Réglages', 'learn-drive-core' ), __( 'Réglages', 'learn-drive-core' ), 'manage_options', SLUG, __NAMESPACE__ . '\\render' );
	add_submenu_page( SLUG, __( 'Outils', 'learn-drive-core' ), __( 'Import, export, démo', 'learn-drive-core' ), 'manage_options', SLUG . '-tools', __NAMESPACE__ . '\\render_tools' );
}
add_action( 'admin_menu', __NAMESPACE__ . '\\menu' );

/**
 * Déclare l'option.
 */
function register() {
	register_setting(
		'learn_drive_settings',
		OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', __NAMESPACE__ . '\\register' );

/**
 * Nettoie les réglages envoyés (fusion avec les valeurs existantes : chaque
 * onglet n'envoie qu'une partie des champs).
 *
 * @param mixed $input Valeurs.
 * @return array
 */
function sanitize( $input ) {
	$current  = get_option( OPTION, array() );
	$current  = is_array( $current ) ? $current : array();
	$input    = is_array( $input ) ? $input : array();
	$defaults = defaults();
	$clean    = $current;

	// Écriture par le code (démo, assistant) : tableau complet déjà nettoyé.
	if ( ! isset( $input['_fields'] ) ) {
		return $input;
	}

	$checkboxes = array( 'seo_titles', 'legacy_schema', 'indexnow', 'llms_txt', 'glossary_links', 'og_images', 'auto_featured', 'post_sections', 'log_404', 'trim_image_sizes', 'rename_uploads', 'video_facade', 'disable_emojis', 'local_avatars', 'offline', 'index_policy' );
	$tab_fields = isset( $input['_fields'] ) ? array_map( 'sanitize_key', explode( ',', (string) $input['_fields'] ) ) : array();

	foreach ( $tab_fields as $key ) {
		if ( ! array_key_exists( $key, $defaults ) ) {
			continue;
		}
		$value = $input[ $key ] ?? null;

		if ( in_array( $key, $checkboxes, true ) ) {
			$clean[ $key ] = empty( $value ) ? 0 : 1;
		} elseif ( 'ai_block' === $key ) {
			$clean[ $key ] = array_values( array_intersect( (array) $value, array_keys( AI\bots() ) ) );
		} elseif ( in_array( $key, array( 'org_same_as', 'api_keys', 'home_lead' ), true ) ) {
			$clean[ $key ] = sanitize_textarea_field( (string) $value );
		} elseif ( in_array( $key, array( 'image_quality', 'thin_words', 'glossary_max', 'rate_limit', 'org_logo', 'index_min_words', 'index_min_items' ), true ) ) {
			$clean[ $key ] = absint( $value );
		} elseif ( str_starts_with( $key, 'slug_' ) ) {
			$slug          = sanitize_title( (string) $value );
			$clean[ $key ] = '' !== $slug ? $slug : $defaults[ $key ];
		} elseif ( in_array( $key, array( 'webhook_url', 'cdn_url', 'app_ios_url', 'app_android_url' ), true ) ) {
			$clean[ $key ] = esc_url_raw( (string) $value );
		} else {
			$clean[ $key ] = sanitize_text_field( (string) $value );
		}
	}

	return $clean;
}

/**
 * Onglets et champs.
 *
 * @return array
 */
function tabs() {
	$access_choices = array(
		'free'       => __( 'Gratuit', 'learn-drive-core' ),
		'registered' => __( 'Gratuit avec inscription', 'learn-drive-core' ),
		'premium'    => __( 'Premium', 'learn-drive-core' ),
	);

	return array(
		'access' => array(
			'label'  => __( 'Accès et libellés', 'learn-drive-core' ),
			'fields' => array(
				'access_mode'     => array(
					'select',
					__( 'Mode global', 'learn-drive-core' ),
					array(
						'free'  => __( 'Tout gratuit', 'learn-drive-core' ),
						'paid'  => __( 'Tout payant (actualités et infos restent publiques)', 'learn-drive-core' ),
						'mixed' => __( 'Mixte (freemium)', 'learn-drive-core' ),
					),
				),
				'presentation'    => array(
					'select',
					__( 'Présentation du site', 'learn-drive-core' ),
					array(
						'auto'       => __( 'Automatique (vitrine si tout payant, portail sinon)', 'learn-drive-core' ),
						'commercial' => __( 'Vitrine commerciale : offres, essai gratuit, témoignages, tarifs', 'learn-drive-core' ),
						'editorial'  => __( 'Portail d’information et d’apprentissage : aucune mise en avant payante', 'learn-drive-core' ),
					),
				),
				'home_eyebrow'    => array( 'text', __( 'Accueil : surtitre du bandeau', 'learn-drive-core' ), __( 'Vide = « Cours · Quiz · Examens blancs ».', 'learn-drive-core' ) ),
				'home_title'      => array( 'text', __( 'Accueil : titre principal (H1)', 'learn-drive-core' ), __( 'Vide = slogan du site. Une phrase claire avec vos mots-clés.', 'learn-drive-core' ) ),
				'home_lead'       => array( 'textarea', __( 'Accueil : texte d’introduction', 'learn-drive-core' ) ),
				'mixed_default'   => array( 'select', __( 'Mode mixte : règle par défaut des contenus', 'learn-drive-core' ), $access_choices ),
				'mixed_exam'      => array( 'select', __( 'Mode mixte : examens blancs', 'learn-drive-core' ), $access_choices ),
				'default_country' => array( 'text', __( 'Pays principal (slug)', 'learn-drive-core' ), __( 'Sur l’accueil, n’affiche que les catégories et thèmes de ce pays. Vide = tous.', 'learn-drive-core' ) ),
				'premium_url'     => array( 'text', __( 'Page de l’offre premium', 'learn-drive-core' ) ),
				'app_ios_url'     => array( 'text', __( 'Application : adresse sur l’App Store', 'learn-drive-core' ), 'https://apps.apple.com/…' ),
				'app_android_url' => array( 'text', __( 'Application : adresse sur Google Play', 'learn-drive-core' ), 'https://play.google.com/store/apps/details?id=…' ),
				'account_url'     => array( 'text', __( 'Page « Mon espace »', 'learn-drive-core' ) ),
				'label_track'     => array( 'text', __( 'Libellé « Catégorie » (singulier)', 'learn-drive-core' ), __( 'Ex. : Catégorie de permis, Classe, Niveau scolaire.', 'learn-drive-core' ) ),
				'label_track_pl'  => array( 'text', __( 'Libellé « Catégorie » (pluriel)', 'learn-drive-core' ) ),
				'label_topic'     => array( 'text', __( 'Libellé « Thème » (singulier)', 'learn-drive-core' ) ),
				'label_topic_pl'  => array( 'text', __( 'Libellé « Thème » (pluriel)', 'learn-drive-core' ) ),
				'label_sheet'     => array( 'text', __( 'Libellé « Fiche » (singulier)', 'learn-drive-core' ), __( 'Ex. : Panneau, Formule, Notion.', 'learn-drive-core' ) ),
				'label_sheet_pl'  => array( 'text', __( 'Libellé « Fiche » (pluriel)', 'learn-drive-core' ) ),
			),
		),
		'urls'   => array(
			'label'  => __( 'URL', 'learn-drive-core' ),
			'fields' => array(
				'slug_lesson'   => array( 'text', __( 'Leçons', 'learn-drive-core' ), '/permis-b/cours/…' ),
				'slug_course'   => array( 'text', __( 'Cours', 'learn-drive-core' ), '/permis-b/formation/…' ),
				'slug_quiz'     => array( 'text', __( 'Quiz', 'learn-drive-core' ), '/permis-b/quiz/…' ),
				'slug_exam'     => array( 'text', __( 'Examen blanc', 'learn-drive-core' ), '/permis-b/examen-blanc/' ),
				'slug_question' => array( 'text', __( 'Questions', 'learn-drive-core' ), '/permis-b/questions/…' ),
				'slug_sheet'    => array( 'text', __( 'Fiches', 'learn-drive-core' ), '/panneaux/…' ),
				'slug_guide'    => array( 'text', __( 'Infos & démarches', 'learn-drive-core' ), '/infos/…' ),
				'slug_glossary' => array( 'text', __( 'Lexique', 'learn-drive-core' ), '/lexique/…' ),
				'slug_track'    => array( 'text', __( 'Base des catégories (si silo désactivé)', 'learn-drive-core' ) ),
				'slug_topic'    => array( 'text', __( 'Base des thèmes', 'learn-drive-core' ) ),
				'slug_country'  => array( 'text', __( 'Base des pays', 'learn-drive-core' ) ),
			),
		),
		'seo'    => array(
			'label'  => __( 'SEO', 'learn-drive-core' ),
			'fields' => array(
				'seo_titles'      => array( 'checkbox', __( 'Gérer les titres et descriptions', 'learn-drive-core' ), __( 'Désactivé automatiquement si Yoast, Rank Math, SEOPress, AIOSEO ou The SEO Framework est actif.', 'learn-drive-core' ) ),
				'title_sep'       => array( 'text', __( 'Séparateur', 'learn-drive-core' ) ),
				'tpl_question'    => array( 'text', __( 'Modèle de titre — question', 'learn-drive-core' ), '{title} {track} {topic} {country} {site}' ),
				'tpl_quiz'        => array( 'text', __( 'Modèle de titre — quiz', 'learn-drive-core' ) ),
				'tpl_lesson'      => array( 'text', __( 'Modèle de titre — leçon et cours', 'learn-drive-core' ) ),
				'tpl_sheet'       => array( 'text', __( 'Modèle de titre — fiche', 'learn-drive-core' ) ),
				'tpl_guide'       => array( 'text', __( 'Modèle de titre — info & démarche', 'learn-drive-core' ) ),
				'tpl_post'        => array( 'text', __( 'Modèle de titre — actualité', 'learn-drive-core' ) ),
				'tpl_glossary'    => array( 'text', __( 'Modèle de titre — lexique', 'learn-drive-core' ) ),
				'tpl_track'       => array( 'text', __( 'Modèle de titre — page pilier', 'learn-drive-core' ) ),
				'org_type'        => array(
					'select',
					__( 'Type d’organisation', 'learn-drive-core' ),
					array(
						'EducationalOrganization' => 'EducationalOrganization',
						'DrivingSchool'           => 'DrivingSchool',
						'Organization'            => 'Organization',
						'NewsMediaOrganization'   => 'NewsMediaOrganization',
					),
				),
				'org_name'        => array( 'text', __( 'Nom de l’organisation', 'learn-drive-core' ) ),
				'org_logo'        => array( 'number', __( 'Logo (ID du média, sinon logo du site)', 'learn-drive-core' ) ),
				'org_same_as'     => array( 'textarea', __( 'Profils officiels (sameAs)', 'learn-drive-core' ), __( 'Une URL par ligne.', 'learn-drive-core' ) ),
				'news_name'       => array( 'text', __( 'Nom de publication Google News', 'learn-drive-core' ) ),
				'thin_words'      => array( 'number', __( 'Mots minimum d’une explication indexable', 'learn-drive-core' ) ),
				'index_policy'    => array( 'checkbox', __( 'Ne pas indexer les pages minces ou inutiles (contenus courts, rubriques presque vides, auteurs sans biographie, archives par date, fichiers joints, flux)', 'learn-drive-core' ) ),
				'index_min_words' => array( 'number', __( 'Mots minimum d’un article, guide ou leçon indexable (pages : 60 %, fiches : 30 %, lexique : 20 %)', 'learn-drive-core' ) ),
				'index_min_items' => array( 'number', __( 'Contenus minimum d’une rubrique, d’un thème ou d’un auteur indexable (sauf page pilier rédigée)', 'learn-drive-core' ) ),
				'glossary_links'  => array( 'checkbox', __( 'Liens automatiques du lexique (première occurrence)', 'learn-drive-core' ) ),
				'glossary_max'    => array( 'number', __( 'Liens du lexique par page (maximum)', 'learn-drive-core' ) ),
				'og_images'       => array( 'checkbox', __( 'Générer les images de partage des quiz et questions', 'learn-drive-core' ) ),
				'auto_featured'   => array( 'checkbox', __( 'Donner une image à la une (titre et rubrique) aux articles qui n’en ont pas', 'learn-drive-core' ) ),
				'post_sections'   => array( 'checkbox', __( 'Adresses des articles par rubrique (/news/article/), anciennes adresses redirigées', 'learn-drive-core' ) ),
				'indexnow'        => array( 'checkbox', __( 'Notifier IndexNow (Bing, Yandex) à chaque publication', 'learn-drive-core' ) ),
				'llms_txt'        => array( 'checkbox', __( 'Publier /llms.txt (pages piliers pour les assistants IA)', 'learn-drive-core' ) ),
				'ai_block'        => array( 'bots', __( 'Bloquer ces robots d’IA dans robots.txt', 'learn-drive-core' ) ),
				'log_404'         => array( 'checkbox', __( 'Journaliser les erreurs 404', 'learn-drive-core' ) ),
			),
		),
		'media'  => array(
			'label'  => __( 'Performance et médias', 'learn-drive-core' ),
			'fields' => array(
				'image_format'     => array(
					'select',
					__( 'Format des images générées', 'learn-drive-core' ),
					array(
						'avif'     => 'AVIF (repli WebP)',
						'webp'     => 'WebP',
						'original' => __( 'Format d’origine', 'learn-drive-core' ),
					),
				),
				'image_quality'    => array( 'number', __( 'Qualité (40 à 95)', 'learn-drive-core' ) ),
				'trim_image_sizes' => array( 'checkbox', __( 'Ne générer que les tailles utiles', 'learn-drive-core' ) ),
				'rename_uploads'   => array( 'checkbox', __( 'Renommer les fichiers envoyés (minuscules, sans accents, titre du contenu)', 'learn-drive-core' ) ),
				'video_facade'     => array( 'checkbox', __( 'Façade vidéo (YouTube / Vimeo chargés au clic)', 'learn-drive-core' ) ),
				'disable_emojis'   => array( 'checkbox', __( 'Supprimer le script des émojis', 'learn-drive-core' ) ),
				'local_avatars'    => array( 'checkbox', __( 'Avatars locaux (aucune requête vers Gravatar)', 'learn-drive-core' ) ),
				'offline'          => array( 'checkbox', __( 'Révision hors ligne (application installable, pages consultées gardées sur l’appareil)', 'learn-drive-core' ) ),
				'cdn_url'          => array( 'text', __( 'URL du CDN d’images', 'learn-drive-core' ), 'https://cdn.example.com' ),
			),
		),
		'api'    => array(
			'label'  => __( 'API et intégrations', 'learn-drive-core' ),
			'fields' => array(
				'api_keys'       => array( 'textarea', __( 'Clés d’API (application, sites clients)', 'learn-drive-core' ), __( 'Une clé par ligne, 20 caractères minimum. En-tête HTTP : X-Learn-Key.', 'learn-drive-core' ) ),
				'rate_limit'     => array( 'number', __( 'Requêtes par minute et par visiteur', 'learn-drive-core' ) ),
				'webhook_url'    => array( 'text', __( 'URL du webhook (CRM, e-mailing)', 'learn-drive-core' ) ),
				'webhook_secret' => array( 'text', __( 'Secret de signature (HMAC SHA-256)', 'learn-drive-core' ) ),
			),
		),
	);
}

/**
 * Page de réglages.
 */
function render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tabs    = tabs();
	$current = isset( $_GET['tab'] ) && isset( $tabs[ sanitize_key( $_GET['tab'] ) ] ) ? sanitize_key( $_GET['tab'] ) : 'access'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$values  = settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Learn & Drive — Réglages', 'learn-drive-core' ); ?></h1>
		<?php if ( \LearnDrive\Core\seo_plugin_active() ) : ?>
			<div class="notice notice-info"><p><?php esc_html_e( 'Un plugin SEO est actif : Learn & Drive lui laisse les balises meta et fusionne ses données structurées dans son graphe.', 'learn-drive-core' ); ?></p></div>
		<?php endif; ?>
		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $key => $tab ) : ?>
				<a class="nav-tab <?php echo $key === $current ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', $key, admin_url( 'admin.php?page=' . SLUG ) ) ); ?>"><?php echo esc_html( $tab['label'] ); ?></a>
			<?php endforeach; ?>
		</nav>
		<form method="post" action="options.php">
			<?php settings_fields( 'learn_drive_settings' ); ?>
			<input type="hidden" name="<?php echo esc_attr( OPTION ); ?>[_fields]" value="<?php echo esc_attr( implode( ',', array_keys( $tabs[ $current ]['fields'] ) ) ); ?>">
			<table class="form-table" role="presentation">
				<?php foreach ( $tabs[ $current ]['fields'] as $key => $field ) : ?>
					<?php
					$type  = $field[0];
					$label = $field[1];
					$extra = $field[2] ?? '';
					$name  = OPTION . '[' . $key . ']';
					$value = $values[ $key ] ?? '';
					?>
					<tr>
						<th scope="row"><label for="ld-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td>
							<?php if ( 'checkbox' === $type ) : ?>
								<input type="checkbox" id="ld-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( (bool) $value ); ?>>
								<?php if ( $extra ) : ?>
									<p class="description"><?php echo esc_html( $extra ); ?></p>
								<?php endif; ?>
							<?php elseif ( 'select' === $type ) : ?>
								<select id="ld-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>">
									<?php foreach ( $extra as $option => $option_label ) : ?>
										<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $value, $option ); ?>><?php echo esc_html( $option_label ); ?></option>
									<?php endforeach; ?>
								</select>
							<?php elseif ( 'textarea' === $type ) : ?>
								<textarea class="large-text" rows="4" id="ld-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>"><?php echo esc_textarea( (string) $value ); ?></textarea>
								<p class="description"><?php echo esc_html( $extra ); ?></p>
							<?php elseif ( 'bots' === $type ) : ?>
								<fieldset id="ld-<?php echo esc_attr( $key ); ?>">
									<?php foreach ( AI\bots() as $bot => $bot_label ) : ?>
										<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $bot ); ?>" <?php checked( in_array( $bot, (array) $value, true ) ); ?>> <code><?php echo esc_html( $bot ); ?></code> — <?php echo esc_html( $bot_label ); ?></label><br>
									<?php endforeach; ?>
								</fieldset>
							<?php else : ?>
								<input type="<?php echo 'number' === $type ? 'number' : 'text'; ?>" class="regular-text" id="ld-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>">
								<?php if ( $extra ) : ?>
									<p class="description"><?php echo esc_html( $extra ); ?></p>
								<?php endif; ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Page des outils : import, export, démonstration.
 */
function render_tools() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$report = get_transient( 'ld_tools_report_' . get_current_user_id() );
	delete_transient( 'ld_tools_report_' . get_current_user_id() );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Learn & Drive — Import, export, démo', 'learn-drive-core' ); ?></h1>
		<?php if ( $report ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( $report ); ?></p></div>
		<?php endif; ?>

		<div class="ld-cards">
			<div class="card">
				<h2><?php esc_html_e( 'Importer des questions', 'learn-drive-core' ); ?></h2>
				<p><?php esc_html_e( 'CSV (séparateur virgule ou point-virgule), JSON ou JSON Lines. Les questions existantes (même slug ou identifiant officiel) sont mises à jour.', 'learn-drive-core' ); ?></p>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'ld_import' ); ?>
					<input type="hidden" name="action" value="ld_import">
					<input type="file" name="ld_file" accept=".csv,.json,.jsonl,.ndjson" required>
					<p><label><input type="checkbox" name="dry_run" value="1"> <?php esc_html_e( 'Simulation (aucune écriture)', 'learn-drive-core' ); ?></label></p>
					<?php submit_button( __( 'Importer', 'learn-drive-core' ), 'primary', 'submit', false ); ?>
				</form>
				<p class="description"><?php esc_html_e( 'Pour plusieurs milliers de questions avec images, préférez WP-CLI : wp learn import fichier.csv', 'learn-drive-core' ); ?></p>
			</div>
			<div class="card">
				<h2><?php esc_html_e( 'Exporter les questions', 'learn-drive-core' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'ld_export' ); ?>
					<input type="hidden" name="action" value="ld_export">
					<select name="format"><option value="csv">CSV</option><option value="jsonl">JSON Lines</option></select>
					<?php submit_button( __( 'Télécharger', 'learn-drive-core' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
			<div class="card">
				<h2><?php esc_html_e( 'Contenu de démonstration', 'learn-drive-core' ); ?></h2>
				<p><?php esc_html_e( '2 pays, 5 catégories, 120 questions, quiz, examens blancs, leçons, panneaux, actualités et guides. Définit aussi la page d’accueil et la page des actualités.', 'learn-drive-core' ); ?></p>
				<?php if ( get_option( 'ld_demo_imported' ) ) : ?>
					<p><strong><?php esc_html_e( 'Déjà importé : relancer met les contenus à jour sans doublon.', 'learn-drive-core' ); ?></strong></p>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'ld_demo' ); ?>
					<input type="hidden" name="action" value="ld_demo">
					<?php submit_button( __( 'Importer la démo', 'learn-drive-core' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Traitement de l'import.
 */
function handle_import() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Action non autorisée.', 'learn-drive-core' ) );
	}
	check_admin_referer( 'ld_import' );

	if ( empty( $_FILES['ld_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['ld_file']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		wp_die( esc_html__( 'Aucun fichier reçu.', 'learn-drive-core' ) );
	}

	$name      = sanitize_file_name( wp_unslash( $_FILES['ld_file']['name'] ?? 'import.csv' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
	$extension = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
	if ( ! in_array( $extension, array( 'csv', 'json', 'jsonl', 'ndjson' ), true ) ) {
		wp_die( esc_html__( 'Format non pris en charge.', 'learn-drive-core' ) );
	}

	$tmp = wp_tempnam( 'ld-import.' . $extension );
	$tmp = preg_replace( '/\.tmp$/', '', $tmp ) . '.' . $extension;
	move_uploaded_file( $_FILES['ld_file']['tmp_name'], $tmp ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	$report = \LearnDrive\Core\Import\import_file( $tmp, array( 'dry_run' => ! empty( $_POST['dry_run'] ) ) );
	wp_delete_file( $tmp );

	/* translators: 1: created, 2: updated, 3: errors */
	$message = sprintf( __( 'Import terminé : %1$d créées, %2$d mises à jour, %3$d erreurs.', 'learn-drive-core' ), $report['created'], $report['updated'], count( $report['errors'] ) );
	if ( $report['errors'] ) {
		$message .= ' ' . implode( ' / ', array_slice( $report['errors'], 0, 5 ) );
	}
	set_transient( 'ld_tools_report_' . get_current_user_id(), $message, MINUTE_IN_SECONDS );
	wp_safe_redirect( admin_url( 'admin.php?page=' . SLUG . '-tools' ) );
	exit;
}
add_action( 'admin_post_ld_import', __NAMESPACE__ . '\\handle_import' );

/**
 * Traitement de l'export.
 */
function handle_export() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Action non autorisée.', 'learn-drive-core' ) );
	}
	check_admin_referer( 'ld_export' );
	$format = isset( $_POST['format'] ) && 'jsonl' === $_POST['format'] ? 'jsonl' : 'csv';
	$file   = wp_tempnam( 'ld-export' );
	\LearnDrive\Core\Import\export_file( $file, $format );

	nocache_headers();
	header( 'Content-Type: ' . ( 'csv' === $format ? 'text/csv' : 'application/x-ndjson' ) . '; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename=questions-' . gmdate( 'Y-m-d' ) . '.' . $format );
	readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
	wp_delete_file( $file );
	exit;
}
add_action( 'admin_post_ld_export', __NAMESPACE__ . '\\handle_export' );

/**
 * Import de la démonstration.
 */
function handle_demo() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Action non autorisée.', 'learn-drive-core' ) );
	}
	check_admin_referer( 'ld_demo' );
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 0 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
	}
	$count = \LearnDrive\Core\Demo\import();
	/* translators: %d: number of questions */
	set_transient( 'ld_tools_report_' . get_current_user_id(), sprintf( __( 'Démo importée (%d questions).', 'learn-drive-core' ), $count['questions'] ?? 0 ), MINUTE_IN_SECONDS );
	wp_safe_redirect( admin_url( 'admin.php?page=' . SLUG . '-tools' ) );
	exit;
}
add_action( 'admin_post_ld_demo', __NAMESPACE__ . '\\handle_demo' );
