<?php
/**
 * Bloc « Tableau de bord apprenant ».
 *
 * Page de compte : jamais indexée, jamais mise en cache (contenu personnel).
 *
 * @package LearnDriveCore
 */

use LearnDrive\Core\Progress;
use LearnDrive\Core\Access;
use LearnDrive\Core\Quizzes;
use LearnDrive\Core\Classes;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'DONOTCACHEPAGE' ) ) {
	define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- constante standard des plugins de cache.
}

$learn_drive_user = wp_get_current_user();

if ( ! $learn_drive_user->exists() ) {
	$learn_drive_html  = '<h2>' . esc_html__( 'Suivez votre progression', 'learn-drive-core' ) . '</h2>';
	$learn_drive_html .= '<p>' . esc_html__( 'Sans compte, vos quiz en cours sont gardés sur cet appareil. Avec un compte gratuit, retrouvez votre progression partout, vos erreurs à revoir et vos statistiques par thème.', 'learn-drive-core' ) . '</p>';
	$learn_drive_html .= '<div class="wp-block-buttons">';
	$learn_drive_html .= sprintf( '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%s" rel="nofollow">%s</a></div>', esc_url( wp_login_url( get_permalink() ) ), esc_html__( 'Se connecter', 'learn-drive-core' ) );
	if ( get_option( 'users_can_register' ) ) {
		$learn_drive_html .= sprintf( '<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="%s" rel="nofollow">%s</a></div>', esc_url( wp_registration_url() ), esc_html__( 'Créer un compte gratuit', 'learn-drive-core' ) );
	}
	$learn_drive_html .= '</div>';
	printf( '<section %s>%s</section>', get_block_wrapper_attributes( array( 'class' => 'ld-dashboard is-guest' ) ), $learn_drive_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$learn_drive_data      = Progress\get( $learn_drive_user->ID );
$learn_drive_ready     = Progress\readiness( $learn_drive_user->ID );
$learn_drive_due       = Progress\due_reviews( $learn_drive_user->ID, 10 );
$learn_drive_offers    = Access\active_offers( $learn_drive_user->ID );
$learn_drive_goal_date = (string) get_user_meta( $learn_drive_user->ID, 'ld_goal_date', true );
$learn_drive_classes   = Classes\for_student( $learn_drive_user->ID );

wp_interactivity_config(
	'learn-drive/account',
	array(
		'restUrl' => esc_url_raw( rest_url( 'learn/v1/' ) ),
		'nonce'   => wp_create_nonce( 'wp_rest' ),
		'i18n'    => array(
			'confirmDelete' => __( 'Supprimer définitivement votre progression ? Cette action est irréversible.', 'learn-drive-core' ),
			'deleted'       => __( 'Votre progression a été supprimée.', 'learn-drive-core' ),
			'error'         => __( 'Une erreur est survenue, réessayez.', 'learn-drive-core' ),
			'saved'         => __( 'Objectif enregistré.', 'learn-drive-core' ),
			'confirmLeave'  => __( 'Quitter cette classe ? Votre moniteur ne verra plus votre progression.', 'learn-drive-core' ),
		),
	)
);

ob_start();
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'ld-dashboard' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-wp-interactive="learn-drive/account" data-wp-context='{"message":"","busy":false,"ready":false}' data-wp-init="callbacks.ready" data-wp-class--is-ready="context.ready">
	<header class="ld-dashboard__head">
		<?php /* translators: %s: user display name */ ?>
		<h2><?php echo esc_html( sprintf( __( 'Bonjour %s', 'learn-drive-core' ), $learn_drive_user->display_name ) ); ?></h2>
		<p>
			<?php if ( $learn_drive_offers ) : ?>
				<span class="ld-badge ld-badge--premium"><?php esc_html_e( 'Premium', 'learn-drive-core' ); ?></span>
				<?php
				$learn_drive_end = max( array_map( static fn( $row ) => $row['ends_at'] ? strtotime( $row['ends_at'] . ' UTC' ) : PHP_INT_MAX, $learn_drive_offers ) );
				if ( PHP_INT_MAX !== $learn_drive_end ) {
					/* translators: %s: date */
					echo esc_html( sprintf( __( 'jusqu’au %s', 'learn-drive-core' ), wp_date( get_option( 'date_format' ), $learn_drive_end ) ) );
				}
				?>
			<?php else : ?>
				<span class="ld-badge"><?php esc_html_e( 'Accès gratuit', 'learn-drive-core' ); ?></span>
				<a href="<?php echo esc_url( \LearnDrive\Core\setting_url( 'premium_url' ) ); ?>"><?php esc_html_e( 'Découvrir le premium', 'learn-drive-core' ); ?></a>
			<?php endif; ?>
		</p>
	</header>

	<ul class="ld-dashboard__kpis">
		<li class="is-style-card">
			<span class="ld-kpi__value"><?php echo esc_html( $learn_drive_ready['count'] ? $learn_drive_ready['percent'] . ' %' : '—' ); ?></span>
			<span class="ld-kpi__label"><?php esc_html_e( 'examens blancs réussis (5 derniers)', 'learn-drive-core' ); ?></span>
			<?php if ( $learn_drive_ready['ready'] ) : ?>
				<span class="ld-badge ld-badge--updated"><?php esc_html_e( 'Prêt pour l’examen', 'learn-drive-core' ); ?></span>
			<?php endif; ?>
		</li>
		<li class="is-style-card">
			<span class="ld-kpi__value"><?php echo esc_html( number_format_i18n( (int) $learn_drive_data['streak']['count'] ) ); ?></span>
			<span class="ld-kpi__label"><?php esc_html_e( 'jour(s) d’affilée', 'learn-drive-core' ); ?></span>
		</li>
		<li class="is-style-card">
			<span class="ld-kpi__value"><?php echo esc_html( number_format_i18n( count( $learn_drive_due ) ) ); ?></span>
			<span class="ld-kpi__label"><?php esc_html_e( 'erreur(s) à revoir aujourd’hui', 'learn-drive-core' ); ?></span>
		</li>
		<li class="is-style-card">
			<span class="ld-kpi__value"><?php echo esc_html( number_format_i18n( count( $learn_drive_data['exams'] ) ) ); ?></span>
			<span class="ld-kpi__label"><?php esc_html_e( 'examen(s) blanc(s) passé(s)', 'learn-drive-core' ); ?></span>
		</li>
	</ul>

	<?php if ( $learn_drive_due ) : ?>
		<section class="ld-dashboard__section">
			<h3><?php esc_html_e( 'À revoir aujourd’hui', 'learn-drive-core' ); ?></h3>
			<ul class="ld-dashboard__list">
				<?php foreach ( $learn_drive_due as $learn_drive_qid ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $learn_drive_qid ) ); ?>"><?php echo esc_html( get_the_title( $learn_drive_qid ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<section class="ld-dashboard__section ld-dashboard__classes" aria-labelledby="ld-classes-title">
		<h3 id="ld-classes-title"><?php esc_html_e( 'Mon auto-école', 'learn-drive-core' ); ?></h3>
		<?php foreach ( $learn_drive_classes as $learn_drive_class ) : ?>
			<article class="ld-class is-style-card" data-wp-context='<?php echo esc_attr( wp_json_encode( array( 'classId' => $learn_drive_class['id'] ) ) ); ?>'>
				<header class="ld-class__head">
					<h4><?php echo esc_html( $learn_drive_class['name'] ); ?></h4>
					<?php if ( $learn_drive_class['instructor'] ) : ?>
						<?php /* translators: %s: instructor name */ ?>
						<p><?php echo esc_html( sprintf( __( 'Moniteur : %s', 'learn-drive-core' ), $learn_drive_class['instructor'] ) ); ?></p>
					<?php endif; ?>
				</header>
				<?php if ( $learn_drive_class['assignments'] ) : ?>
					<ul class="ld-class__todo">
						<?php foreach ( $learn_drive_class['assignments'] as $learn_drive_item ) : ?>
							<?php $learn_drive_state = $learn_drive_item['done'] ? 'done' : ( $learn_drive_item['late'] ? 'late' : 'todo' ); ?>
							<li class="is-<?php echo esc_attr( $learn_drive_state ); ?>">
								<a href="<?php echo esc_url( $learn_drive_item['url'] ); ?>"><?php echo esc_html( $learn_drive_item['title'] ); ?></a>
								<span class="ld-class__state">
									<?php
									if ( $learn_drive_item['done'] ) {
										/* translators: %d: best score in percent */
										echo esc_html( '✓ ' . sprintf( __( 'Fait — meilleur score %d %%', 'learn-drive-core' ), $learn_drive_item['best'] ) );
									} elseif ( $learn_drive_item['late'] ) {
										echo esc_html( '! ' . __( 'En retard', 'learn-drive-core' ) );
									} elseif ( $learn_drive_item['due'] ) {
										/* translators: %s: date */
										echo esc_html( sprintf( __( 'À faire avant le %s', 'learn-drive-core' ), wp_date( get_option( 'date_format' ), strtotime( $learn_drive_item['due'] ) ) ) );
									} else {
										esc_html_e( 'À faire', 'learn-drive-core' );
									}
									?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p><?php esc_html_e( 'Aucun devoir pour le moment.', 'learn-drive-core' ); ?></p>
				<?php endif; ?>
				<p><button type="button" class="ld-btn ld-btn--link" data-wp-on--click="actions.leaveClass"><?php esc_html_e( 'Quitter la classe', 'learn-drive-core' ); ?></button></p>
			</article>
		<?php endforeach; ?>
		<form class="ld-inline-form" data-wp-on--submit="actions.joinClass">
			<label for="ld-class-code"><?php esc_html_e( 'Code donné par votre auto-école', 'learn-drive-core' ); ?></label>
			<input id="ld-class-code" name="code" type="text" required maxlength="20" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="ABCD-EFGH">
			<button type="submit" class="ld-btn" data-wp-bind--disabled="context.busy"><?php esc_html_e( 'Rejoindre la classe', 'learn-drive-core' ); ?></button>
		</form>
		<p class="ld-dashboard__hint"><?php esc_html_e( 'En rejoignant une classe, votre moniteur voit votre progression (thèmes, examens blancs, devoirs). Vous pouvez la quitter à tout moment.', 'learn-drive-core' ); ?></p>
	</section>

	<?php echo do_blocks( '<!-- wp:learn-drive/topic-progress /-->' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<?php if ( $learn_drive_data['exams'] ) : ?>
		<section class="ld-dashboard__section">
			<h3><?php esc_html_e( 'Derniers examens blancs', 'learn-drive-core' ); ?></h3>
			<figure class="wp-block-table"><table>
				<thead><tr><th><?php esc_html_e( 'Date', 'learn-drive-core' ); ?></th><th><?php esc_html_e( 'Examen', 'learn-drive-core' ); ?></th><th><?php esc_html_e( 'Score', 'learn-drive-core' ); ?></th><th><?php esc_html_e( 'Résultat', 'learn-drive-core' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( array_reverse( array_slice( $learn_drive_data['exams'], -10 ) ) as $learn_drive_exam ) : ?>
					<tr>
						<td><?php echo esc_html( wp_date( get_option( 'date_format' ), $learn_drive_exam['date'] ) ); ?></td>
						<td><a href="<?php echo esc_url( get_permalink( $learn_drive_exam['quiz'] ) ); ?>"><?php echo esc_html( get_the_title( $learn_drive_exam['quiz'] ) ); ?></a></td>
						<td><?php echo esc_html( $learn_drive_exam['correct'] . ' / ' . $learn_drive_exam['total'] ); ?></td>
						<td><?php echo $learn_drive_exam['passed'] ? '✓ ' . esc_html__( 'Réussi', 'learn-drive-core' ) : '✗ ' . esc_html__( 'Échoué', 'learn-drive-core' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></figure>
		</section>
	<?php endif; ?>

	<section class="ld-dashboard__section">
		<h3><?php esc_html_e( 'Mon objectif', 'learn-drive-core' ); ?></h3>
		<form class="ld-inline-form" data-wp-on--submit="actions.saveGoal">
			<label for="ld-goal-date"><?php esc_html_e( 'Date de mon examen', 'learn-drive-core' ); ?></label>
			<input id="ld-goal-date" name="date" type="date" value="<?php echo esc_attr( $learn_drive_goal_date ); ?>" min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>">
			<button type="submit" class="ld-btn"><?php esc_html_e( 'Enregistrer', 'learn-drive-core' ); ?></button>
		</form>
		<?php
		if ( $learn_drive_goal_date ) {
			$learn_drive_days = (int) ceil( ( strtotime( $learn_drive_goal_date ) - time() ) / DAY_IN_SECONDS );
			if ( $learn_drive_days > 0 ) {
				/* translators: %d: number of days */
				echo '<p class="ld-dashboard__plan">' . esc_html( sprintf( _n( 'J-%d : faites un examen blanc par jour et revoyez vos erreurs du jour.', 'J-%d : faites un examen blanc par jour et revoyez vos erreurs du jour.', $learn_drive_days, 'learn-drive-core' ), $learn_drive_days ) ) . '</p>';
			}
		}
		?>
	</section>

	<section class="ld-dashboard__section">
		<h3><?php esc_html_e( 'J’ai un code d’accès', 'learn-drive-core' ); ?></h3>
		<?php echo do_blocks( '<!-- wp:learn-drive/access-code /-->' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</section>

	<section class="ld-dashboard__section ld-dashboard__privacy">
		<h3><?php esc_html_e( 'Mes données', 'learn-drive-core' ); ?></h3>
		<p><?php esc_html_e( 'Vos statistiques d’apprentissage sont pseudonymisées. Vous pouvez les télécharger ou les supprimer à tout moment.', 'learn-drive-core' ); ?></p>
		<p>
			<button type="button" class="ld-btn" data-wp-on--click="actions.exportData"><?php esc_html_e( 'Télécharger mes données', 'learn-drive-core' ); ?></button>
			<button type="button" class="ld-btn ld-btn--danger" data-wp-on--click="actions.deleteData"><?php esc_html_e( 'Supprimer ma progression', 'learn-drive-core' ); ?></button>
		</p>
	</section>

	<p class="ld-dashboard__message" role="status" data-wp-text="context.message"></p>
</section>
<?php
echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
