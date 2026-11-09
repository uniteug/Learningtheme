<?php
/**
 * Adresses des articles par rubrique (réglage « post_sections ») :
 *
 *   /news/{article}/            article de la rubrique « News »
 *   /news/                      archive de la rubrique
 *   /news/{sous-rubrique}/      archive d'une sous-rubrique
 *
 * La rubrique est la catégorie de premier niveau de l'article (la principale
 * choisie dans Yoast, sinon la plus précise) ; son segment d'URL est sa méta
 * « ld_url_base » (court : news, guide…), sinon son slug. Les anciennes
 * adresses (/{article}/, /category/…) redirigent en 301 vers les nouvelles :
 * la structure de permaliens de WordPress reste inchangée pour les résoudre.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\PostSections;

use function LearnDrive\Core\setting;

defined( 'ABSPATH' ) || exit;

/**
 * Fonction active ?
 *
 * @return bool
 */
function enabled() {
	return (bool) setting( 'post_sections' );
}

/**
 * Rubriques de premier niveau et leur segment d'URL.
 *
 * @return array<int,string> ID de la catégorie => segment.
 */
function sections() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	$terms = get_terms(
		array(
			'taxonomy'   => 'category',
			'parent'     => 0,
			'hide_empty' => false,
			'lang'       => '', // Polylang : rubriques de toutes les langues.
		)
	);
	foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
		if ( (int) get_option( 'default_category' ) === (int) $term->term_id && ! $term->count ) {
			continue;
		}
		$base                          = sanitize_title( (string) get_term_meta( $term->term_id, 'ld_url_base', true ) );
		$cache[ (int) $term->term_id ] = '' !== $base ? $base : $term->slug;
	}
	return $cache;
}

/**
 * Rubrique (catégorie de premier niveau) d'un article.
 *
 * @param \WP_Post $post Article.
 * @return \WP_Term|null
 */
function section_of( $post ) {
	$category = \LearnDrive\Core\SEO\OG\primary_category( $post->ID );
	if ( ! $category ) {
		return null;
	}
	$ancestors = get_ancestors( $category->term_id, 'category', 'taxonomy' );
	$top       = $ancestors ? (int) end( $ancestors ) : (int) $category->term_id;
	return isset( sections()[ $top ] ) ? get_term( $top, 'category' ) : null;
}

/**
 * Règles de réécriture : /{rubrique}/, /{rubrique}/page/N/, /{rubrique}/{article ou sous-rubrique}/.
 */
function add_rules() {
	if ( ! enabled() ) {
		return;
	}
	foreach ( sections() as $term_id => $base ) {
		$term = get_term( $term_id, 'category' );
		if ( ! $term instanceof \WP_Term ) {
			continue;
		}
		$quoted = preg_quote( $base, '#' );
		add_rewrite_rule( "^{$quoted}/?$", 'index.php?category_name=' . $term->slug, 'top' );
		add_rewrite_rule( "^{$quoted}/page/([0-9]{1,})/?$", 'index.php?category_name=' . $term->slug . '&paged=$matches[1]', 'top' );
		add_rewrite_rule( "^{$quoted}/([^/]+)/page/([0-9]{1,})/?$", 'index.php?ld_section=' . $term_id . '&ld_section_child=$matches[1]&paged=$matches[2]', 'top' );
		add_rewrite_rule( "^{$quoted}/([^/]+)/?$", 'index.php?ld_section=' . $term_id . '&name=$matches[1]', 'top' );
	}
}
add_action( 'init', __NAMESPACE__ . '\\add_rules', 22 );

/**
 * Variables de requête.
 *
 * @param string[] $vars Variables.
 * @return string[]
 */
function query_vars( $vars ) {
	$vars[] = 'ld_section';
	$vars[] = 'ld_section_child';
	return $vars;
}
add_filter( 'query_vars', __NAMESPACE__ . '\\query_vars' );

/**
 * /{rubrique}/{x}/ : un article, sinon une sous-rubrique de cette rubrique.
 *
 * @param array $vars Requête.
 * @return array
 */
function resolve( $vars ) {
	if ( empty( $vars['ld_section'] ) ) {
		return $vars;
	}
	$section = (int) $vars['ld_section'];
	$slug    = (string) ( $vars['name'] ?? $vars['ld_section_child'] ?? '' );
	unset( $vars['ld_section'], $vars['ld_section_child'] );

	if ( isset( $vars['name'] ) && get_page_by_path( $slug, OBJECT, 'post' ) ) {
		return $vars;
	}
	$child = get_term_by( 'slug', $slug, 'category' );
	if ( $child && in_array( $section, array_map( 'intval', get_ancestors( $child->term_id, 'category', 'taxonomy' ) ), true ) ) {
		unset( $vars['name'] );
		$vars['category_name'] = $child->slug;
	}
	return $vars;
}
add_filter( 'request', __NAMESPACE__ . '\\resolve' );

/**
 * Adresse d'un article : /{rubrique}/{article}/.
 *
 * @param string   $link Lien.
 * @param \WP_Post $post Article.
 * @return string
 */
function post_link( $link, $post ) {
	if ( ! enabled() || 'post' !== $post->post_type || ! in_array( $post->post_status, array( 'publish', 'private', 'future' ), true ) || '' === $post->post_name ) {
		return $link;
	}
	$section = section_of( $post );
	if ( ! $section ) {
		return $link;
	}
	return home_url( user_trailingslashit( sections()[ $section->term_id ] . '/' . $post->post_name ) );
}
add_filter( 'post_link', __NAMESPACE__ . '\\post_link', 10, 2 );

/**
 * Adresse d'une rubrique (/news/) ou d'une sous-rubrique (/news/{sous-rubrique}/).
 *
 * @param string   $link     Lien.
 * @param \WP_Term $term     Terme.
 * @param string   $taxonomy Taxonomie.
 * @return string
 */
function term_link( $link, $term, $taxonomy ) {
	if ( ! enabled() || 'category' !== $taxonomy ) {
		return $link;
	}
	$sections = sections();
	if ( isset( $sections[ $term->term_id ] ) ) {
		return home_url( user_trailingslashit( $sections[ $term->term_id ] ) );
	}
	$ancestors = get_ancestors( $term->term_id, 'category', 'taxonomy' );
	$top       = $ancestors ? (int) end( $ancestors ) : 0;
	if ( $top && isset( $sections[ $top ] ) && 1 === count( $ancestors ) ) {
		return home_url( user_trailingslashit( $sections[ $top ] . '/' . $term->slug ) );
	}
	return $link;
}
add_filter( 'term_link', __NAMESPACE__ . '\\term_link', 10, 3 );

/**
 * Anciennes adresses (/{article}/, /category/…) : 301 vers la nouvelle.
 */
function redirect_old() {
	if ( ! enabled() || is_admin() || is_preview() || ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture seule.
		return;
	}
	if ( is_singular( 'post' ) ) {
		$target = get_permalink( get_queried_object_id() );
	} elseif ( is_category() && ! is_paged() ) {
		$target = get_term_link( get_queried_object() );
	} else {
		return;
	}
	$request  = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	$expected = is_string( $target ) ? wp_parse_url( $target, PHP_URL_PATH ) : '';
	if ( $request && $expected && untrailingslashit( $request ) !== untrailingslashit( $expected ) ) {
		wp_safe_redirect( $target, 301 );
		exit;
	}
}
add_action( 'template_redirect', __NAMESPACE__ . '\\redirect_old', 4 );

/**
 * Règles à régénérer quand une rubrique ou le réglage change.
 */
function flush_later() {
	update_option( 'ld_flush_rewrite', 1, true );
}
add_action( 'created_category', __NAMESPACE__ . '\\flush_later' );
add_action( 'edited_category', __NAMESPACE__ . '\\flush_later' );
add_action( 'delete_category', __NAMESPACE__ . '\\flush_later' );

/**
 * Champ « segment d'URL » d'une rubrique de premier niveau.
 *
 * @param \WP_Term $term Rubrique.
 */
function term_field( $term ) {
	if ( $term->parent ) {
		return;
	}
	wp_nonce_field( 'ld_url_base', 'ld_url_base_nonce' );
	?>
	<tr class="form-field">
		<th><label for="ld_url_base"><?php esc_html_e( 'Segment d’URL des articles', 'learn-drive-core' ); ?></label></th>
		<td>
			<input type="text" id="ld_url_base" name="ld_url_base" value="<?php echo esc_attr( get_term_meta( $term->term_id, 'ld_url_base', true ) ); ?>" placeholder="<?php echo esc_attr( $term->slug ); ?>">
			<p class="description"><?php esc_html_e( 'Court et parlant (ex. « news », « guide ») : les articles de la rubrique ont pour adresse /segment/article/. Vide : slug de la rubrique. Actif si « Adresses des articles par rubrique » est coché dans les réglages.', 'learn-drive-core' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'category_edit_form_fields', __NAMESPACE__ . '\\term_field' );

/**
 * Enregistre le segment d'URL.
 *
 * @param int $term_id Rubrique.
 */
function save_term_field( $term_id ) {
	if ( ! isset( $_POST['ld_url_base_nonce'], $_POST['ld_url_base'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ld_url_base_nonce'] ), 'ld_url_base' ) || ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	update_term_meta( $term_id, 'ld_url_base', sanitize_title( wp_unslash( $_POST['ld_url_base'] ) ) );
}
add_action( 'edited_category', __NAMESPACE__ . '\\save_term_field' );

/*
 * Liens internes : les contenus qui pointent encore vers l'ancienne adresse
 * d'un article (/article/) sont réécrits vers la nouvelle (/rubrique/article/),
 * par lots en arrière-plan, pour éviter une redirection à chaque lien.
 */

/**
 * Anciennes adresses → nouvelles (articles dont l'adresse a changé).
 *
 * @return array<string,string> Slug => nouvelle adresse.
 */
function moved_posts() {
	global $wpdb;
	$map = array();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- lecture unique de quelques centaines de lignes.
	$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'" );
	foreach ( $ids as $id ) {
		$post = get_post( (int) $id );
		$new  = get_permalink( $post );
		if ( $post && $new && untrailingslashit( (string) wp_parse_url( $new, PHP_URL_PATH ) ) !== '/' . $post->post_name ) {
			$map[ $post->post_name ] = $new;
		}
	}
	return $map;
}

/**
 * Réécrit les liens d'un lot de contenus.
 */
function migrate_links_batch() {
	if ( ! enabled() ) {
		return;
	}
	global $wpdb;
	$state = get_option( 'ld_links_migration', array( 'last' => 0 ) );
	$map   = moved_posts();
	if ( ! $map ) {
		update_option(
			'ld_links_migration',
			array(
				'last'    => 0,
				'done'    => LEARN_DRIVE_CORE_VERSION,
				'changed' => 0,
			),
			false
		);
		return;
	}
	$host = preg_quote( (string) wp_parse_url( home_url(), PHP_URL_HOST ), '#' );
	$slug = implode( '|', array_map( static fn( $s ) => preg_quote( $s, '#' ), array_keys( $map ) ) );
	$re   = '#(href=["\'])(?:https?://(?:www\.)?' . $host . '(?::\d+)?)?/(' . $slug . ')/?(?=["\'\#?])#';

	// phpcs:disable WordPress.DB.DirectDatabaseQuery -- réécriture en lot, sans révisions ni filtrage du HTML existant.
	$rows    = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID, post_content FROM {$wpdb->posts} WHERE ID > %d AND post_status IN ( 'publish', 'draft', 'future', 'private' ) AND post_type NOT IN ( 'revision', 'attachment', 'nav_menu_item' ) AND post_content LIKE %s ORDER BY ID ASC LIMIT 40",
			(int) $state['last'],
			'%href=%'
		)
	);
	$changed = (int) ( $state['changed'] ?? 0 );
	foreach ( $rows as $row ) {
		$content = preg_replace_callback( $re, static fn( $m ) => $m[1] . $map[ $m[2] ], $row->post_content );
		if ( null !== $content && $content !== $row->post_content ) {
			$wpdb->update( $wpdb->posts, array( 'post_content' => $content ), array( 'ID' => $row->ID ) );
			clean_post_cache( (int) $row->ID );
			++$changed;
		}
		$state['last'] = (int) $row->ID;
	}
	// phpcs:enable WordPress.DB.DirectDatabaseQuery

	if ( count( $rows ) === 40 ) {
		update_option(
			'ld_links_migration',
			array(
				'last'    => $state['last'],
				'changed' => $changed,
			),
			false
		);
		wp_schedule_single_event( time() + 20, 'learn_drive_links_migration' );
	} else {
		update_option(
			'ld_links_migration',
			array(
				'last'    => 0,
				'done'    => LEARN_DRIVE_CORE_VERSION,
				'changed' => $changed,
			),
			false
		);
	}
}
add_action( 'learn_drive_links_migration', __NAMESPACE__ . '\\migrate_links_batch' );

/**
 * Lance la réécriture une fois par version, quand les adresses par rubrique sont actives.
 */
function schedule_links_migration() {
	$state = get_option( 'ld_links_migration', array() );
	if ( enabled() && ( $state['done'] ?? '' ) !== LEARN_DRIVE_CORE_VERSION && ! wp_next_scheduled( 'learn_drive_links_migration' ) ) {
		wp_schedule_single_event( time() + 10, 'learn_drive_links_migration' );
	}
}
add_action( 'admin_init', __NAMESPACE__ . '\\schedule_links_migration' );
add_action( 'rest_api_init', __NAMESPACE__ . '\\schedule_links_migration' );
