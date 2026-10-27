<?php
/**
 * Écran « Auto-école » : classes du moniteur, code d'invitation, devoirs,
 * suivi des élèves et export CSV.
 *
 * Chaque action passe par admin-post.php (nonce + capacité + propriété de la
 * classe), puis redirige vers l'écran (pas de double envoi au rechargement).
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Admin\Classes;

use LearnDrive\Core\Classes;
use const LearnDrive\Core\PT_QUIZ;

defined( 'ABSPATH' ) || exit;

const PAGE = 'learn-drive-classes';

/**
 * Menu : visible des moniteurs (qui n'ont pas accès au reste du plugin).
 */
function menu() {
	add_menu_page( __( 'Auto-école', 'learn-drive-core' ), __( 'Auto-école', 'learn-drive-core' ), Classes\CAP, PAGE, __NAMESPACE__ . '\\render', 'dashicons-groups', 21 );
}
add_action( 'admin_menu', __NAMESPACE__ . '\\menu' );

/**
 * URL de l'écran.
 *
 * @param array $args Paramètres.
 * @return string
 */
function page_url( array $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => PAGE ), $args ), admin_url( 'admin.php' ) );
}

/**
 * URL d'une action protégée.
 *
 * @param string $action   Action.
 * @param int    $class_id Classe.
 * @param array  $args     Paramètres supplémentaires.
 * @return string
 */
function action_url( $action, $class_id, array $args = array() ) {
	return wp_nonce_url(
		add_query_arg(
			array_merge(
				array(
					'action' => 'ld_class_' . $action,
					'class'  => (int) $class_id,
				),
				$args
			),
			admin_url( 'admin-post.php' )
		),
		'ld_class_' . $action . '_' . (int) $class_id
	);
}

/**
 * Vérifie nonce, capacité et propriété ; renvoie la classe.
 *
 * @param string $action Action.
 * @return int
 */
function guard( $action ) {
	$class_id = isset( $_REQUEST['class'] ) ? absint( $_REQUEST['class'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- vérifié juste après.
	check_admin_referer( 'ld_class_' . $action . '_' . $class_id );

	if ( ! current_user_can( Classes\CAP ) || ( $class_id && ! Classes\can_manage( $class_id ) ) ) {
		wp_die( esc_html__( 'Vous ne pouvez pas gérer cette classe.', 'learn-drive-core' ), 403 );
	}
	return $class_id;
}

/**
 * Retour à l'écran avec un message.
 *
 * @param int    $class_id Classe (0 = liste).
 * @param string $notice   Code du message.
 */
function back( $class_id, $notice ) {
	wp_safe_redirect(
		page_url(
			array_filter(
				array(
					'class'  => $class_id,
					'notice' => $notice,
				)
			)
		)
	);
	exit;
}

/**
 * Crée une classe.
 */
function handle_create() {
	guard( 'create' );
	$id = Classes\create( sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ), get_current_user_id(), absint( $_POST['seats'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- guard().
	back( is_wp_error( $id ) ? 0 : $id, is_wp_error( $id ) ? 'error' : 'created' );
}
add_action( 'admin_post_ld_class_create', __NAMESPACE__ . '\\handle_create' );

/**
 * Assigne un quiz.
 */
function handle_assign() {
	$class_id = guard( 'assign' );
	$result   = Classes\assign( $class_id, absint( $_POST['quiz'] ?? 0 ), sanitize_text_field( wp_unslash( $_POST['due'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- guard().
	back( $class_id, is_wp_error( $result ) ? 'error' : 'assigned' );
}
add_action( 'admin_post_ld_class_assign', __NAMESPACE__ . '\\handle_assign' );

/**
 * Retire un devoir.
 */
function handle_unassign() {
	$class_id = guard( 'unassign' );
	Classes\unassign( $class_id, sanitize_key( wp_unslash( $_GET['item'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- guard().
	back( $class_id, 'unassigned' );
}
add_action( 'admin_post_ld_class_unassign', __NAMESPACE__ . '\\handle_unassign' );

/**
 * Retire un élève.
 */
function handle_remove() {
	$class_id = guard( 'remove' );
	Classes\leave( $class_id, absint( $_GET['user'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- guard().
	back( $class_id, 'removed' );
}
add_action( 'admin_post_ld_class_remove', __NAMESPACE__ . '\\handle_remove' );

/**
 * Ouvre / ferme les inscriptions.
 */
function handle_toggle() {
	$class_id = guard( 'toggle' );
	update_post_meta( $class_id, '_ld_class_open', Classes\get( $class_id )['open'] ? 0 : 1 );
	back( $class_id, 'saved' );
}
add_action( 'admin_post_ld_class_toggle', __NAMESPACE__ . '\\handle_toggle' );

/**
 * Nouveau code d'invitation (l'ancien cesse de fonctionner).
 */
function handle_code() {
	$class_id = guard( 'code' );
	update_post_meta( $class_id, '_ld_class_code', Classes\generate_code() );
	back( $class_id, 'code' );
}
add_action( 'admin_post_ld_class_code', __NAMESPACE__ . '\\handle_code' );

/**
 * Supprime la classe (les élèves gardent leur progression).
 */
function handle_delete() {
	$class_id = guard( 'delete' );
	wp_delete_post( $class_id, true );
	back( 0, 'deleted' );
}
add_action( 'admin_post_ld_class_delete', __NAMESPACE__ . '\\handle_delete' );

/**
 * Télécharge le suivi de la classe en CSV.
 */
function handle_export() {
	$class_id = guard( 'export' );
	$class    = Classes\get( $class_id );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( 'classe-' . sanitize_title( $class['name'] ) . '-' . wp_date( 'Y-m-d' ) . '.csv' ) . '"' );
	echo Classes\report_csv( $class_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fichier CSV.
	exit;
}
add_action( 'admin_post_ld_class_export', __NAMESPACE__ . '\\handle_export' );

/**
 * Message après une action.
 */
function notice() {
	$messages = array(
		'created'    => __( 'Classe créée. Donnez le code d’invitation à vos élèves.', 'learn-drive-core' ),
		'assigned'   => __( 'Devoir assigné.', 'learn-drive-core' ),
		'unassigned' => __( 'Devoir retiré.', 'learn-drive-core' ),
		'removed'    => __( 'Élève retiré de la classe.', 'learn-drive-core' ),
		'saved'      => __( 'Modification enregistrée.', 'learn-drive-core' ),
		'code'       => __( 'Nouveau code créé : l’ancien ne fonctionne plus.', 'learn-drive-core' ),
		'deleted'    => __( 'Classe supprimée. Les élèves conservent leur progression.', 'learn-drive-core' ),
		'error'      => __( 'Action impossible : vérifiez les informations saisies.', 'learn-drive-core' ),
	);
	$key      = sanitize_key( wp_unslash( $_GET['notice'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- affichage seul.
	if ( isset( $messages[ $key ] ) ) {
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', 'error' === $key ? 'error' : 'success', esc_html( $messages[ $key ] ) );
	}
}

/**
 * Affiche l'écran.
 */
function render() {
	if ( ! current_user_can( Classes\CAP ) ) {
		return;
	}
	$class_id = absint( $_GET['class'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- navigation.

	echo '<div class="wrap ld-classes">';
	if ( $class_id && Classes\can_manage( $class_id ) ) {
		render_class( $class_id );
	} else {
		render_list();
	}
	echo '</div>';
}

/**
 * Liste des classes et création.
 */
function render_list() {
	$ids = Classes\managed_by( get_current_user_id() );
	?>
	<h1><?php esc_html_e( 'Auto-école', 'learn-drive-core' ); ?></h1>
	<?php notice(); ?>
	<p><?php esc_html_e( 'Créez une classe, donnez son code à vos élèves : ils la rejoignent depuis leur espace « Mon compte ». Vous suivez ensuite leur progression et leur assignez des quiz.', 'learn-drive-core' ); ?></p>

	<table class="widefat striped">
		<thead><tr>
			<th><?php esc_html_e( 'Classe', 'learn-drive-core' ); ?></th>
			<th><?php esc_html_e( 'Code d’invitation', 'learn-drive-core' ); ?></th>
			<th><?php esc_html_e( 'Élèves', 'learn-drive-core' ); ?></th>
			<th><?php esc_html_e( 'Devoirs', 'learn-drive-core' ); ?></th>
			<th><?php esc_html_e( 'Inscriptions', 'learn-drive-core' ); ?></th>
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<th><?php esc_html_e( 'Moniteur', 'learn-drive-core' ); ?></th>
			<?php endif; ?>
		</tr></thead>
		<tbody>
		<?php foreach ( $ids as $id ) : ?>
			<?php
			$class = Classes\get( $id );
			$count = count( Classes\members( $id ) );
			$owner = get_userdata( $class['owner'] );
			?>
			<tr>
				<td><strong><a href="<?php echo esc_url( page_url( array( 'class' => $id ) ) ); ?>"><?php echo esc_html( $class['name'] ); ?></a></strong></td>
				<td><code class="ld-class-code"><?php echo esc_html( $class['code'] ); ?></code></td>
				<td><?php echo esc_html( $class['seats'] ? $count . ' / ' . $class['seats'] : (string) $count ); ?></td>
				<td><?php echo esc_html( (string) count( $class['assignments'] ) ); ?></td>
				<td><?php echo $class['open'] ? esc_html__( 'ouvertes', 'learn-drive-core' ) : esc_html__( 'fermées', 'learn-drive-core' ); ?></td>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<td><?php echo esc_html( $owner ? $owner->display_name : '—' ); ?></td>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
		<?php if ( ! $ids ) : ?>
			<tr><td colspan="6"><?php esc_html_e( 'Aucune classe pour le moment.', 'learn-drive-core' ); ?></td></tr>
		<?php endif; ?>
		</tbody>
	</table>

	<form class="card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<h2><?php esc_html_e( 'Nouvelle classe', 'learn-drive-core' ); ?></h2>
		<input type="hidden" name="action" value="ld_class_create">
		<?php wp_nonce_field( 'ld_class_create_0' ); ?>
		<p><label for="ld-class-name"><?php esc_html_e( 'Nom', 'learn-drive-core' ); ?></label><br>
			<input id="ld-class-name" type="text" name="name" class="regular-text" required maxlength="120" placeholder="<?php esc_attr_e( 'Permis B — session de mars', 'learn-drive-core' ); ?>"></p>
		<p><label for="ld-class-seats"><?php esc_html_e( 'Nombre de places (0 = illimité)', 'learn-drive-core' ); ?></label><br>
			<input id="ld-class-seats" type="number" name="seats" min="0" max="1000" value="0"></p>
		<?php submit_button( __( 'Créer la classe', 'learn-drive-core' ), 'primary', 'submit', false ); ?>
	</form>
	<?php
}

/**
 * Détail d'une classe : code, devoirs, élèves.
 *
 * @param int $class_id Classe.
 */
function render_class( $class_id ) {
	$class   = Classes\get( $class_id );
	$report  = Classes\report( $class_id );
	$account = home_url( user_trailingslashit( trim( (string) \LearnDrive\Core\setting( 'account_url' ), '/' ) ) );
	$quizzes = get_posts(
		array(
			'post_type'      => PT_QUIZ,
			'post_status'    => 'publish',
			'posts_per_page' => 200, // phpcs:ignore WordPress.WP.PostsPerPage -- liste des quiz, bornée.
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
	?>
	<p><a href="<?php echo esc_url( page_url() ); ?>">← <?php esc_html_e( 'Toutes les classes', 'learn-drive-core' ); ?></a></p>
	<h1><?php echo esc_html( $class['name'] ); ?></h1>
	<?php notice(); ?>

	<div class="ld-cards">
		<div class="card">
			<h2><?php esc_html_e( 'Code d’invitation', 'learn-drive-core' ); ?></h2>
			<p class="ld-class-code ld-class-code--big"><code><?php echo esc_html( $class['code'] ); ?></code></p>
			<p>
				<?php
				/* translators: %s: account page URL */
				echo wp_kses( sprintf( __( 'Les élèves créent un compte gratuit puis saisissent ce code dans <a href="%s">Mon compte</a>.', 'learn-drive-core' ), esc_url( $account ) ), array( 'a' => array( 'href' => array() ) ) );
				?>
			</p>
			<p>
				<?php echo $class['open'] ? esc_html__( 'Inscriptions ouvertes.', 'learn-drive-core' ) : esc_html__( 'Inscriptions fermées.', 'learn-drive-core' ); ?>
				<a class="button" href="<?php echo esc_url( action_url( 'toggle', $class_id ) ); ?>"><?php echo $class['open'] ? esc_html__( 'Fermer', 'learn-drive-core' ) : esc_html__( 'Ouvrir', 'learn-drive-core' ); ?></a>
				<a class="button" href="<?php echo esc_url( action_url( 'code', $class_id ) ); ?>"><?php esc_html_e( 'Nouveau code', 'learn-drive-core' ); ?></a>
			</p>
		</div>

		<form class="card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<h2><?php esc_html_e( 'Assigner un quiz', 'learn-drive-core' ); ?></h2>
			<input type="hidden" name="action" value="ld_class_assign">
			<input type="hidden" name="class" value="<?php echo esc_attr( (string) $class_id ); ?>">
			<?php wp_nonce_field( 'ld_class_assign_' . $class_id ); ?>
			<p><label for="ld-class-quiz"><?php esc_html_e( 'Quiz ou examen blanc', 'learn-drive-core' ); ?></label><br>
				<select id="ld-class-quiz" name="quiz" required>
					<?php foreach ( $quizzes as $quiz ) : ?>
						<option value="<?php echo esc_attr( (string) $quiz->ID ); ?>"><?php echo esc_html( $quiz->post_title ); ?></option>
					<?php endforeach; ?>
				</select></p>
			<p><label for="ld-class-due"><?php esc_html_e( 'À faire avant le (facultatif)', 'learn-drive-core' ); ?></label><br>
				<input id="ld-class-due" type="date" name="due" min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></p>
			<?php submit_button( __( 'Assigner', 'learn-drive-core' ), 'primary', 'submit', false ); ?>
		</form>
	</div>

	<?php if ( $class['assignments'] ) : ?>
		<h2><?php esc_html_e( 'Devoirs', 'learn-drive-core' ); ?></h2>
		<ul class="ld-class-assignments">
			<?php foreach ( $class['assignments'] as $position => $assignment ) : ?>
				<?php $done = count( array_filter( $report['rows'], static fn( $row ) => $row['status'][ $position ]['done'] ) ); ?>
				<li>
					<a href="<?php echo esc_url( get_permalink( $assignment['quiz'] ) ); ?>"><?php echo esc_html( get_the_title( $assignment['quiz'] ) ); ?></a>
					<?php if ( $assignment['due'] ) : ?>
						<?php /* translators: %s: date */ ?>
						— <?php echo esc_html( sprintf( __( 'avant le %s', 'learn-drive-core' ), wp_date( get_option( 'date_format' ), strtotime( $assignment['due'] ) ) ) ); ?>
					<?php endif; ?>
					<?php /* translators: 1: students done, 2: students total */ ?>
					— <?php echo esc_html( sprintf( __( '%1$d / %2$d élève(s) l’ont fait', 'learn-drive-core' ), $done, count( $report['rows'] ) ) ); ?>
					<a class="ld-link-danger" href="<?php echo esc_url( action_url( 'unassign', $class_id, array( 'item' => $assignment['id'] ) ) ); ?>"><?php esc_html_e( 'Retirer', 'learn-drive-core' ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<h2>
		<?php
		/* translators: %d: number of students */
		echo esc_html( sprintf( _n( '%d élève', '%d élèves', count( $report['rows'] ), 'learn-drive-core' ), count( $report['rows'] ) ) );
		?>
		<?php if ( $report['rows'] ) : ?>
			<a class="page-title-action" href="<?php echo esc_url( action_url( 'export', $class_id ) ); ?>"><?php esc_html_e( 'Exporter en CSV', 'learn-drive-core' ); ?></a>
		<?php endif; ?>
	</h2>

	<div class="ld-table-scroll">
		<table class="widefat striped ld-class-report">
			<thead><tr>
				<?php foreach ( $report['columns'] as $index => $column ) : ?>
					<?php if ( 1 !== $index && 2 !== $index ) : ?>
						<th scope="col"><?php echo esc_html( $column ); ?></th>
					<?php endif; ?>
				<?php endforeach; ?>
				<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'learn-drive-core' ); ?></span></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $report['rows'] as $row ) : ?>
				<tr>
					<?php foreach ( $row['cells'] as $index => $cell ) : ?>
						<?php
						if ( 1 === $index || 2 === $index ) {
							continue;
						}
						$status     = $index >= 11 ? $row['status'][ $index - 11 ] : null;
						$class_name = $status ? ( $status['done'] ? ( $status['passed'] ? 'is-passed' : 'is-done' ) : ( $status['late'] ? 'is-late' : 'is-todo' ) ) : '';
						?>
						<td class="<?php echo esc_attr( $class_name ); ?>">
							<?php if ( 0 === $index ) : ?>
								<strong><?php echo esc_html( (string) $cell ); ?></strong><br><small><?php echo esc_html( (string) $row['cells'][1] ); ?></small>
							<?php else : ?>
								<?php echo esc_html( '' === $cell ? '—' : (string) $cell ); ?>
							<?php endif; ?>
						</td>
					<?php endforeach; ?>
					<td><a class="ld-link-danger" href="<?php echo esc_url( action_url( 'remove', $class_id, array( 'user' => $row['user_id'] ) ) ); ?>" onclick="return confirm(<?php echo esc_attr( wp_json_encode( __( 'Retirer cet élève de la classe ? Sa progression est conservée.', 'learn-drive-core' ) ) ); ?>)"><?php esc_html_e( 'Retirer', 'learn-drive-core' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
			<?php if ( ! $report['rows'] ) : ?>
				<tr><td colspan="<?php echo esc_attr( (string) count( $report['columns'] ) ); ?>"><?php esc_html_e( 'Aucun élève inscrit : partagez le code d’invitation.', 'learn-drive-core' ); ?></td></tr>
			<?php endif; ?>
			</tbody>
		</table>
	</div>

	<p class="ld-class-delete"><a class="ld-link-danger" href="<?php echo esc_url( action_url( 'delete', $class_id ) ); ?>" onclick="return confirm(<?php echo esc_attr( wp_json_encode( __( 'Supprimer cette classe ? Les élèves conservent leur progression.', 'learn-drive-core' ) ) ); ?>)"><?php esc_html_e( 'Supprimer la classe', 'learn-drive-core' ); ?></a></p>
	<?php
}
