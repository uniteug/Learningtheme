<?php
/**
 * Plugin Name:       Noigui — configurazione del sito
 * Description:       Configura Learn & Drive per noigui.com: patente italiana, banca ufficiale dei quiz ministeriali (listato «Patente AB», 7.020 affermazioni), argomenti con le quote d'esame, simulazione d'esame, menu e collegamenti all'app. Le spiegazioni già scritte negli articoli «Quiz Patente B» vengono collegate alle affermazioni corrispondenti.
 * Version:           1.1.0
 * Requires at least: 6.9
 * Requires PHP:      8.1
 * Requires Plugins:  learn-drive-core
 * Author:            Noigui
 * License:           GPL-2.0-or-later
 * Text Domain:       noigui-setup
 *
 * @package NoiguiSetup
 */

namespace Noigui\Setup;

use LearnDrive\Core\Import;
use const LearnDrive\Core\OPTION;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\PT_QUIZ;
use const LearnDrive\Core\TAX_COUNTRY;
use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\TAX_TOPIC;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.1.0';
const DIR     = __DIR__ . '/';

/**
 * Attivazione : la configurazione parte al caricamento successivo, quando
 * Learn & Drive Core è sicuramente attivo.
 */
function activate() {
	update_option( 'noigui_setup_pending', 1, false );
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate' );

/**
 * Esegue la configurazione una sola volta (o dopo un aggiornamento dei dati).
 */
function maybe_setup() {
	if ( ! defined( 'LEARN_DRIVE_CORE_VERSION' ) ) {
		return;
	}
	if ( get_option( 'noigui_setup_pending' ) || get_option( 'noigui_setup_version' ) !== VERSION ) {
		delete_option( 'noigui_setup_pending' );
		setup();
		update_option( 'noigui_setup_version', VERSION, false );
	}
}
add_action( 'init', __NAMESPACE__ . '\\maybe_setup', 40 );

/**
 * Metadati del listato (argomenti e quote).
 *
 * @return array
 */
function meta() {
	$meta = json_decode( (string) file_get_contents( DIR . 'data/meta.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- file locale del plugin.
	return is_array( $meta ) ? $meta : array();
}

/**
 * Crea o aggiorna un termine.
 *
 * @param string $taxonomy    Tassonomia.
 * @param string $slug        Slug.
 * @param string $name        Nome.
 * @param string $description Descrizione.
 * @return int
 */
function term( $taxonomy, $slug, $name, $description = '' ) {
	$found = get_term_by( 'slug', $slug, $taxonomy );
	if ( $found ) {
		wp_update_term(
			$found->term_id,
			$taxonomy,
			array(
				'name'        => $name,
				'description' => $description,
			)
		);
		return (int) $found->term_id;
	}
	$made = wp_insert_term(
		$name,
		$taxonomy,
		array(
			'slug'        => $slug,
			'description' => $description,
		)
	);
	return is_wp_error( $made ) ? 0 : (int) $made['term_id'];
}

/**
 * Crea o aggiorna un contenuto per slug.
 *
 * @param string $type    Tipo.
 * @param array  $postarr Dati.
 * @return int
 */
function upsert( $type, array $postarr ) {
	$found = get_page_by_path( $postarr['post_name'], OBJECT, $type );
	$data  = array_merge(
		array(
			'post_type'   => $type,
			'post_status' => 'publish',
		),
		$postarr
	);
	if ( $found ) {
		$data['ID'] = $found->ID;
	} else {
		$data['post_author'] = Import\default_author();
	}
	$id = wp_insert_post( wp_slash( $data ), true );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

/**
 * Configurazione completa.
 */
function setup() {
	require_once DIR . 'content.php';

	// Impostazioni di Learn & Drive.
	$current = get_option( OPTION, array() );
	update_option(
		OPTION,
		array_merge(
			is_array( $current ) ? $current : array(),
			settings()
		)
	);

	// Paese e regole dell'esame di teoria (30 affermazioni vero/falso, 20 minuti, massimo 3 errori).
	$italia = term( TAX_COUNTRY, 'italia', 'Italia' );
	update_term_meta(
		$italia,
		'ld_rules',
		array(
			'format'       => 'tf',
			'count'        => 30,
			'duration'     => 1200,
			'per_question' => 0,
			'pass_type'    => 'max_errors',
			'pass_value'   => 3,
			'allow_back'   => true,
		)
	);
	update_term_meta( $italia, 'ld_flag', '#009246,#FFFFFF,#CE2B37' );

	// Mercato: lingua, home di tutte le patenti (/patenti/) e testo della pagina.
	update_term_meta( $italia, 'ld_locale', 'it_IT' );
	update_term_meta( $italia, 'ld_hub', 'patenti' );
	update_term_meta( $italia, 'ld_active', '1' );
	wp_update_term(
		$italia,
		TAX_COUNTRY,
		array(
			'name'        => 'Patenti in Italia',
			'description' => hub_description(),
		)
	);
	update_term_meta( $italia, 'ld_content', hub_content() );

	// Categorie di patente (pagine pilastro).
	foreach ( tracks() as $track ) {
		$id = term( TAX_TRACK, $track['slug'], $track['name'], $track['description'] );
		update_term_meta( $id, 'ld_country', 'italia' );
		update_term_meta( $id, 'ld_color', $track['color'] );
		update_term_meta( $id, 'ld_min_age', $track['min_age'] );
		update_term_meta( $id, 'ld_content', $track['content'] );
		update_term_meta( $id, 'ld_order', (int) ( $track['order'] ?? 0 ) );
		update_term_meta( $id, 'ld_short', (string) ( $track['short'] ?? '' ) );
		update_term_meta( $id, 'ld_equivalent', (string) ( $track['equivalent'] ?? '' ) );
	}

	// I 25 argomenti ministeriali, con la quota di ciascuno nella scheda d'esame.
	foreach ( meta()['topics'] ?? array() as $topic ) {
		$quota       = 1 === (int) $topic['quota'] ? '1 domanda' : (int) $topic['quota'] . ' domande';
		$summary     = sprintf( '%s affermazioni del listato ministeriale, %s su 30 all’esame.', number_format_i18n( (int) ( $topic['count'] ?? 0 ) ), $quota );
		$description = $topic['description'] !== $topic['name'] ? $topic['description'] . '. ' . $summary : $summary;
		$id          = term( TAX_TOPIC, $topic['slug'], $topic['name'], $description );
		update_term_meta( $id, 'ld_country', 'italia' );
		update_term_meta( $id, 'ld_exam_quota', (int) $topic['quota'] );
	}

	// Simulazione d'esame e quiz per argomento.
	foreach ( quizzes() as $quiz ) {
		$id = upsert(
			PT_QUIZ,
			array(
				'post_name'    => $quiz['slug'],
				'post_title'   => $quiz['title'],
				'post_excerpt' => $quiz['excerpt'],
				'post_content' => $quiz['content'],
				'menu_order'   => $quiz['order'],
			)
		);
		if ( ! $id ) {
			continue;
		}
		update_post_meta( $id, '_ld_mode', $quiz['mode'] );
		update_post_meta( $id, '_ld_access', 'free' );
		if ( 'exam' !== $quiz['mode'] ) {
			update_post_meta( $id, '_ld_rules', array( 'count' => (int) $quiz['count'] ) );
		}
		wp_set_object_terms( $id, array( 'patente-b' ), TAX_TRACK );
		wp_set_object_terms( $id, array( 'italia' ), TAX_COUNTRY );
		wp_set_object_terms( $id, $quiz['topic'] ? array( $quiz['topic'] ) : array(), TAX_TOPIC );
	}

	// Area personale.
	$account = upsert(
		'page',
		array(
			'post_name'    => 'la-mia-area',
			'post_title'   => 'La mia area',
			'post_content' => '<!-- wp:learn-drive/dashboard /-->',
		)
	);
	update_post_meta( $account, '_ld_seo_noindex', 1 );

	// Contenuti creati senza utente connesso (cron, prima versione) : autore = amministratore.
	assign_author();

	// Colori del marchio Noigui (blu del logo), contrasto AA sul bianco.
	brand_styles();

	// Importazione della banca ufficiale in background, a lotti (una sola volta).
	$state = get_option( 'ld_import_queue' );
	if ( ! is_array( $state ) || ! str_ends_with( (string) ( $state['file'] ?? '' ), 'patente-ab.jsonl' ) ) {
		Import\queue( DIR . 'data/patente-ab.jsonl', 150 );
	}

	// Le nuove basi d'indirizzo valgono dalla richiesta successiva : regole rigenerate allora.
	delete_option( 'ld_track_slugs' );
	add_action(
		'shutdown',
		static function () {
			update_option( 'ld_flush_rewrite', 1 );
		}
	);
}

/**
 * Assegna all'amministratore domande, quiz e figure importati senza autore.
 */
function assign_author() {
	global $wpdb;
	$author = Import\default_author();
	if ( ! $author ) {
		return;
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- aggiornamento in blocco una tantum.
	$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_author = %d WHERE post_author = 0 AND post_type IN ( %s, %s, 'attachment' )", $author, PT_QUESTION, PT_QUIZ ) );
	if ( $updated ) {
		wp_cache_flush();
	}
}

/**
 * Colori del marchio nei «Stili globali» del tema (modificabili dall'editor del sito).
 */
function brand_styles() {
	if ( 'learn-drive' !== get_template() || ! class_exists( 'WP_Theme_JSON_Resolver' ) ) {
		return;
	}
	$post_id = \WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
	$config  = json_decode( (string) get_post_field( 'post_content', $post_id ), true );
	if ( ! $post_id || ( is_array( $config ) && ( ! empty( $config['settings'] ) || ! empty( $config['styles'] ) ) ) ) {
		return; // Già personalizzati : non si tocca nulla.
	}
	$palette = array();
	foreach ( wp_get_global_settings( array( 'color', 'palette', 'theme' ), array( 'origin' => 'theme' ) ) as $color ) {
		if ( 'primary' === $color['slug'] ) {
			$color['color'] = '#0A6E9E';
		}
		$palette[] = $color;
	}
	wp_update_post(
		wp_slash(
			array(
				'ID'           => $post_id,
				'post_content' => wp_json_encode(
					array(
						'version'                     => \WP_Theme_JSON::LATEST_SCHEMA,
						'isGlobalStylesUserThemeJSON' => true,
						'settings'                    => array( 'color' => array( 'palette' => array( 'theme' => $palette ) ) ),
					)
				),
			)
		)
	);
}

/**
 * Normalizza un'affermazione per il confronto (spazi, apostrofi, punteggiatura finale).
 *
 * @param string $text Testo.
 * @return string
 */
function normalize( $text ) {
	$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' );
	$text = str_replace( array( '’', '‘', '`' ), "'", $text );
	$text = preg_replace( '/^\s*\d+\.\s*/u', '', $text );
	$text = preg_replace( '/\s+/u', ' ', $text );
	return mb_strtolower( trim( $text, " .\t\n" ) );
}

/**
 * Collega le spiegazioni degli articoli «Quiz Patente B» alle affermazioni ufficiali.
 *
 * Gli articoli contengono blocchi .nqz-q con testo, risposta (V/F) e spiegazione:
 * se l'affermazione esiste nel listato con la stessa risposta, la spiegazione
 * diventa il contenuto della pagina della domanda, con un rimando all'articolo.
 *
 * @return int Affermazioni arricchite.
 */
function link_explanations() {
	global $wpdb;

	$index = array();
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'", PT_QUESTION ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	foreach ( $rows as $row ) {
		$index[ normalize( $row->post_title ) ][] = (int) $row->ID;
	}

	$linked = 0;
	$posts  = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- operazione una tantum.
			's'              => 'nqz-q',
		)
	);
	foreach ( $posts as $post ) {
		if ( ! preg_match_all( '#<div class="nqz-q" data-r="([VF])"><p class="nqz-s">(.*?)</p>.*?data-x="([^"]*)"#su', $post->post_content, $matches, PREG_SET_ORDER ) ) {
			continue;
		}
		foreach ( $matches as $m ) {
			$key = normalize( $m[2] );
			foreach ( $index[ $key ] ?? array() as $question_id ) {
				$answers = get_post_meta( $question_id, '_ld_answers', true );
				$true    = is_array( $answers ) && ! empty( $answers[0]['correct'] );
				if ( ( 'V' === $m[1] ) !== $true || '' !== trim( get_post_field( 'post_content', $question_id ) ) ) {
					continue;
				}
				$explanation = html_entity_decode( $m[3], ENT_QUOTES, 'UTF-8' );
				$content     = "<!-- wp:paragraph -->\n<p>" . ( $true ? '<strong>Vero.</strong> ' : '<strong>Falso.</strong> ' ) . esc_html( $explanation ) . "</p>\n<!-- /wp:paragraph -->\n\n"
					. "<!-- wp:paragraph -->\n<p>Approfondisci l'argomento nella guida «<a href=\"" . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . "</a>», con altre affermazioni del listato spiegate una per una.</p>\n<!-- /wp:paragraph -->";
				wp_update_post(
					wp_slash(
						array(
							'ID'           => $question_id,
							'post_content' => $content,
						)
					)
				);
				update_post_meta( $question_id, '_ld_lesson_post', $post->ID );
				\LearnDrive\Core\Questions\update_seo_flags( $question_id );
				++$linked;
			}
		}
	}
	update_option( 'noigui_explanations_linked', $linked, false );
	return $linked;
}
add_action( 'learn_drive_import_done', __NAMESPACE__ . '\\link_explanations' );

/**
 * Collegamenti del tema (menu, piè di pagina, app).
 *
 * @param array $links Collegamenti.
 * @return array
 */
function links( $links ) {
	$news   = get_term_by( 'slug', 'news-patente', 'category' );
	$guides = get_term_by( 'slug', 'guide-patente', 'category' );
	return array_merge(
		$links,
		array(
			'news'        => $news ? get_term_link( $news ) : $links['news'],
			'guides'      => $guides ? get_term_link( $guides ) : $links['guides'],
			'about'       => home_url( '/about/' ),
			'editorial'   => home_url( '/about/' ),
			'contact'     => home_url( '/contact/' ),
			'legal'       => home_url( '/term-condition/' ),
			'sitemap'     => '',
			'app_android' => 'https://play.google.com/store/apps/details?id=com.noigui',
			'app_ios'     => 'https://apps.apple.com/it/app/noigui-patente-guida-insieme/id6779575620',
		)
	);
}
add_filter( 'learn_drive_links', __NAMESPACE__ . '\\links', 20 );

/**
 * Menu principale.
 *
 * @return array
 */
function nav_items() {
	$links   = function_exists( 'learn_drive_links' ) ? learn_drive_links() : array();
	$track   = get_term_by( 'slug', 'patente-b', TAX_TRACK );
	$quizzes = get_term_by( 'slug', 'quiz-patente-b', 'category' );
	$patenti = function_exists( 'LearnDrive\\Core\\Theme\\track_menu' ) ? \LearnDrive\Core\Theme\track_menu( 'italia' ) : array();
	$hub     = get_term_by( 'slug', 'italia', TAX_COUNTRY );
	if ( $hub ) {
		$patenti[] = array( 'Tutte le patenti', get_term_link( $hub ), 'Confronta categorie, età ed esami' );
	}
	return array(
		$patenti ? array( 'Patenti', $hub ? get_term_link( $hub ) : '', $patenti ) : array( 'Patente B', $track ? get_term_link( $track ) : '' ),
		array( 'Simulazione d’esame', $links['exam'] ?? '' ),
		array( 'Quiz commentati', $quizzes ? get_term_link( $quizzes ) : '' ),
		array( 'Guide', $links['guides'] ?? '' ),
		array( 'News', $links['news'] ?? '' ),
	);
}
add_filter( 'learn_drive_nav_items', __NAMESPACE__ . '\\nav_items', 20 );

/**
 * Accessi rapidi del banner della home.
 *
 * @return array
 */
function hero_chips() {
	$links   = function_exists( 'learn_drive_links' ) ? learn_drive_links() : array();
	$quizzes = get_term_by( 'slug', 'quiz-patente-b', 'category' );
	return array(
		array( 'Simulazione d’esame', $links['exam'] ?? '' ),
		array( 'Quiz per argomento', $links['quiz'] ?? '' ),
		array( 'Quiz commentati', $quizzes ? get_term_link( $quizzes ) : '' ),
		array( 'Guide', $links['guides'] ?? '' ),
	);
}
add_filter( 'learn_drive_hero_chips', __NAMESPACE__ . '\\hero_chips', 20 );

/**
 * Riquadri già presenti negli articoli («Leggi anche») allineati al tema.
 */
function article_styles() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	wp_register_style( 'noigui-articles', false, array(), VERSION );
	wp_enqueue_style( 'noigui-articles' );
	wp_add_inline_style(
		'noigui-articles',
		'.noigui-leggi-anche{margin:2rem 0;padding:1.25rem 1.5rem;border:1px solid var(--wp--preset--color--border,#dfe5ee);border-left:4px solid var(--wp--preset--color--primary,#0a6e9e);border-radius:12px;background:var(--wp--preset--color--surface,#f5f8fc)}'
		. '.noigui-leggi-anche h3{margin:0 0 .5rem;font-size:1.05rem}.noigui-leggi-anche ul{margin:0;padding-left:1.1rem}.noigui-leggi-anche li{margin:.3rem 0}'
		. '.nqz-q{border-color:var(--wp--preset--color--border,#dcdcec)!important}.nqz-b:focus-visible{outline:3px solid var(--wp--preset--color--primary,#0a6e9e);outline-offset:2px}'
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\article_styles' );

/**
 * Piè di pagina.
 *
 * @return array
 */
function footer_columns() {
	$links   = function_exists( 'learn_drive_links' ) ? learn_drive_links() : array();
	$track   = get_term_by( 'slug', 'patente-b', TAX_TRACK );
	$quizzes = get_term_by( 'slug', 'quiz-patente-b', 'category' );
	$cats    = static function ( $slug ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		return $term ? get_term_link( $term ) : '';
	};
	return array(
		'Impara'    => array(
			array( 'Patente B', $track ? get_term_link( $track ) : '' ),
			array( 'Simulazione d’esame', $links['exam'] ?? '' ),
			array( 'Quiz per argomento', $links['quiz'] ?? '' ),
			array( 'Quiz commentati', $quizzes ? get_term_link( $quizzes ) : '' ),
		),
		'Informati' => array(
			array( 'News', $links['news'] ?? '' ),
			array( 'Guide', $links['guides'] ?? '' ),
			array( 'Neopatentati', $cats( 'guide-neopatentati' ) ),
			array( 'Categorie di patente', $cats( 'guide-categorie-patente' ) ),
		),
		'Noigui'    => array(
			array( 'Chi siamo', home_url( '/about/' ) ),
			array( 'Contatti', home_url( '/contact/' ) ),
			array( 'App Android', $links['app_android'] ?? '' ),
			array( 'App iOS', $links['app_ios'] ?? '' ),
			array( 'Termini e condizioni', home_url( '/term-condition/' ) ),
			array( 'Privacy', get_privacy_policy_url() ),
			array( 'Eliminazione account', home_url( '/delete-account-request/' ) ),
		),
	);
}
add_filter( 'learn_drive_footer_columns', __NAMESPACE__ . '\\footer_columns', 20 );

/**
 * Accueil : le news sono gli articoli della rubrica «News Patente» (non i quiz),
 * le guide sono gli articoli della rubrica «Guide».
 *
 * @param array  $args Argomenti.
 * @param string $key  news|guides.
 * @return array
 */
function query_args( $args, $key ) {
	$parent = get_term_by( 'slug', 'news' === $key ? 'news-patente' : 'guide-patente', 'category' );
	if ( ! $parent ) {
		return $args;
	}
	$ids = array_merge( array( (int) $parent->term_id ), array_map( 'intval', get_term_children( $parent->term_id, 'category' ) ) );
	return array(
		'postType' => 'post',
		'taxQuery' => array( 'category' => $ids ),
	);
}
add_filter( 'learn_drive_query_args', __NAMESPACE__ . '\\query_args', 20, 2 );

/**
 * Pagine tecniche dell'app (conferma account, eliminazione account): mai indicizzate,
 * mai riscritte.
 *
 * @param bool $noindex Decisione.
 * @return bool
 */
function app_pages_noindex( $noindex ) {
	return $noindex || is_page( array( 'auth-confirm', 'delete-account-request' ) );
}
add_filter( 'learn_drive_noindex', __NAMESPACE__ . '\\app_pages_noindex' );

/**
 * Pagina di stato dell'importazione (Strumenti › Noigui).
 */
function admin_menu() {
	add_management_page( 'Noigui', 'Noigui', 'manage_options', 'noigui-setup', __NAMESPACE__ . '\\admin_page' );
}
add_action( 'admin_menu', __NAMESPACE__ . '\\admin_menu' );

/**
 * Stato dell'importazione e azioni.
 */
function admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( isset( $_POST['noigui_action'] ) && check_admin_referer( 'noigui' ) ) {
		$action = sanitize_key( wp_unslash( $_POST['noigui_action'] ) );
		if ( 'batch' === $action ) {
			Import\run_batch();
		} elseif ( 'link' === $action ) {
			link_explanations();
		} elseif ( 'setup' === $action ) {
			setup();
		}
	}
	$state = get_option( 'ld_import_queue', array() );
	$total = 7020;
	$done  = (int) ( $state['offset'] ?? 0 );
	?>
	<div class="wrap">
		<h1>Noigui — configurazione</h1>
		<p>Banca ufficiale: listato ministeriale «Patente AB» (<?php echo esc_html( number_format_i18n( $total ) ); ?> affermazioni, 25 argomenti). Testi del Ministero delle Infrastrutture e dei Trasporti, licenza CC BY 3.0 IT.</p>
		<p><strong>Importazione:</strong>
			<?php
			echo esc_html(
				! empty( $state['done'] )
					? sprintf( 'completata (%d create, %d aggiornate)', (int) $state['created'], (int) $state['updated'] )
					: sprintf( 'in corso: %d / %d', $done, $total )
			);
			?>
		</p>
		<p><strong>Spiegazioni collegate dagli articoli:</strong> <?php echo esc_html( (string) (int) get_option( 'noigui_explanations_linked', 0 ) ); ?></p>
		<?php if ( ! empty( $state['errors'] ) ) : ?>
			<details><summary>Errori (<?php echo esc_html( (string) count( $state['errors'] ) ); ?>)</summary><pre><?php echo esc_html( implode( "\n", $state['errors'] ) ); ?></pre></details>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'noigui' ); ?>
			<button class="button button-primary" name="noigui_action" value="batch">Importa il lotto successivo</button>
			<button class="button" name="noigui_action" value="link">Ricollega le spiegazioni</button>
			<button class="button" name="noigui_action" value="setup">Riapplica la configurazione</button>
		</form>
	</div>
	<?php
}

/**
 * Manifesto web : l'app nativa Android è proposta al posto dell'installazione del sito.
 *
 * @param array $manifest Manifesto.
 * @return array
 */
function manifest( $manifest ) {
	$manifest['related_applications']        = array(
		array(
			'platform' => 'play',
			'url'      => 'https://play.google.com/store/apps/details?id=com.noigui',
			'id'       => 'com.noigui',
		),
		array(
			'platform' => 'itunes',
			'url'      => 'https://apps.apple.com/it/app/noigui-patente-guida-insieme/id6779575620',
		),
	);
	$manifest['prefer_related_applications'] = true;
	return $manifest;
}
add_filter( 'learn_drive_manifest', __NAMESPACE__ . '\\manifest' );
