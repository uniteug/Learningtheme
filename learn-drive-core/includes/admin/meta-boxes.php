<?php
/**
 * Méta-boxes : propositions des questions, règles des quiz, accès, SEO,
 * rattachements (cours, fiche, leçon) et champs des taxonomies.
 *
 * Les méta-boxes PHP fonctionnent dans l'éditeur de blocs sans étape de build.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Admin\Meta_Boxes;

use const LearnDrive\Core\PT_COURSE;
use const LearnDrive\Core\PT_LESSON;
use const LearnDrive\Core\PT_QUESTION;
use const LearnDrive\Core\PT_QUIZ;
use const LearnDrive\Core\PT_SHEET;
use const LearnDrive\Core\PT_GUIDE;
use const LearnDrive\Core\PT_GLOSSARY;
use const LearnDrive\Core\TAX_TRACK;
use const LearnDrive\Core\TAX_COUNTRY;
use LearnDrive\Core\Questions;
use LearnDrive\Core\Quizzes;
use function LearnDrive\Core\content_types;
use function LearnDrive\Core\format_label;

defined( 'ABSPATH' ) || exit;

/**
 * Déclare les méta-boxes.
 */
function register() {
	add_meta_box( 'ld-question', __( 'Propositions et correction', 'learn-drive-core' ), __NAMESPACE__ . '\\question_box', PT_QUESTION, 'normal', 'high' );
	add_meta_box( 'ld-quiz', __( 'Réglages du quiz', 'learn-drive-core' ), __NAMESPACE__ . '\\quiz_box', PT_QUIZ, 'normal', 'high' );
	add_meta_box( 'ld-links', __( 'Rattachements', 'learn-drive-core' ), __NAMESPACE__ . '\\links_box', array( PT_LESSON, PT_SHEET, PT_GLOSSARY, PT_GUIDE, 'post' ), 'side' );
	add_meta_box( 'ld-access', __( 'Accès', 'learn-drive-core' ), __NAMESPACE__ . '\\access_box', array( PT_COURSE, PT_LESSON, PT_QUESTION, PT_QUIZ ), 'side' );
	add_meta_box( 'ld-seo', __( 'Référencement (Learn & Drive)', 'learn-drive-core' ), __NAMESPACE__ . '\\seo_box', array_merge( content_types(), array( 'page' ) ), 'normal', 'low' );
}
add_action( 'add_meta_boxes', __NAMESPACE__ . '\\register' );

/**
 * Ressources de l'administration.
 *
 * @param string $hook Écran.
 */
function assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'term.php', 'edit-tags.php' ), true ) && ! str_contains( $hook, 'learn-drive' ) ) {
		return;
	}
	wp_enqueue_style( 'learn-drive-core-admin', LEARN_DRIVE_CORE_URL . 'assets/css/admin.css', array(), \LearnDrive\Core\VERSION );
	wp_enqueue_script( 'learn-drive-core-admin', LEARN_DRIVE_CORE_URL . 'assets/js/admin.js', array(), \LearnDrive\Core\VERSION, true );
	wp_localize_script(
		'learn-drive-core-admin',
		'learnDriveAdmin',
		array(
			'answer'  => __( 'Proposition', 'learn-drive-core' ),
			'correct' => __( 'Bonne réponse', 'learn-drive-core' ),
			'remove'  => __( 'Retirer', 'learn-drive-core' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\assets' );

/**
 * Méta-box des questions.
 *
 * @param \WP_Post $post Question.
 */
function question_box( $post ) {
	wp_nonce_field( 'ld_save_meta', 'ld_meta_nonce' );
	$q = Questions\get( $post );
	if ( ! get_post_meta( $post->ID, '_ld_answers', true ) && 'auto-draft' === $post->post_status ) {
		$q['answers'] = array_fill( 0, 3, array( 'text' => '', 'correct' => false ) );
	}
	$words = (int) get_post_meta( $post->ID, '_ld_words', true );
	?>
	<p class="ld-help"><?php esc_html_e( 'L’énoncé est le titre. L’explication (obligatoire, 40 mots minimum pour être indexée) se rédige dans le contenu : réponse dans la première phrase, règle citée.', 'learn-drive-core' ); ?></p>
	<?php if ( $post->post_content && $words < (int) \LearnDrive\Core\setting( 'thin_words' ) ) : ?>
		<p class="ld-warning">
			<?php
			/* translators: %d: number of words */
			echo esc_html( sprintf( __( 'Explication trop courte (%d mots) : la question ne sera pas indexée par les moteurs de recherche.', 'learn-drive-core' ), $words ) );
			?>
		</p>
	<?php endif; ?>
	<p>
		<label for="ld_format"><strong><?php esc_html_e( 'Format', 'learn-drive-core' ); ?></strong></label><br>
		<select id="ld_format" name="ld_format" data-ld-format>
			<?php foreach ( Questions\FORMATS as $format ) : ?>
				<option value="<?php echo esc_attr( $format ); ?>" <?php selected( $q['format'], $format ); ?>><?php echo esc_html( format_label( $format ) ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>

	<div class="ld-answers" data-ld-answers>
		<p><strong><?php esc_html_e( 'Propositions', 'learn-drive-core' ); ?></strong> <span class="description"><?php esc_html_e( '(2 à 6, cochez la ou les bonnes réponses)', 'learn-drive-core' ); ?></span></p>
		<ol class="ld-answers__list" data-ld-list>
			<?php foreach ( $q['answers'] as $index => $answer ) : ?>
				<li class="ld-answer-row">
					<input type="text" class="widefat" name="ld_answers[<?php echo (int) $index; ?>][text]" value="<?php echo esc_attr( $answer['text'] ); ?>" aria-label="<?php esc_attr_e( 'Proposition', 'learn-drive-core' ); ?>">
					<label><input type="checkbox" name="ld_answers[<?php echo (int) $index; ?>][correct]" value="1" <?php checked( $answer['correct'] ); ?>> <?php esc_html_e( 'Bonne réponse', 'learn-drive-core' ); ?></label>
					<button type="button" class="button-link-delete" data-ld-remove><?php esc_html_e( 'Retirer', 'learn-drive-core' ); ?></button>
				</li>
			<?php endforeach; ?>
		</ol>
		<p><button type="button" class="button" data-ld-add><?php esc_html_e( 'Ajouter une proposition', 'learn-drive-core' ); ?></button></p>
	</div>

	<div class="ld-numeric" data-ld-numeric>
		<p><strong><?php esc_html_e( 'Réponse numérique', 'learn-drive-core' ); ?></strong></p>
		<label><?php esc_html_e( 'Valeur', 'learn-drive-core' ); ?> <input type="number" step="any" name="ld_numeric[value]" value="<?php echo esc_attr( $q['numeric']['value'] ); ?>"></label>
		<label><?php esc_html_e( 'Tolérance ±', 'learn-drive-core' ); ?> <input type="number" step="any" min="0" name="ld_numeric[tolerance]" value="<?php echo esc_attr( $q['numeric']['tolerance'] ); ?>"></label>
		<label><?php esc_html_e( 'Unité', 'learn-drive-core' ); ?> <input type="text" class="small-text" name="ld_numeric[unit]" value="<?php echo esc_attr( $q['numeric']['unit'] ); ?>"></label>
	</div>

	<table class="form-table" role="presentation">
		<tr>
			<th><label for="ld_source"><?php esc_html_e( 'Source réglementaire', 'learn-drive-core' ); ?></label></th>
			<td><input type="text" class="widefat" id="ld_source" name="ld_source" value="<?php echo esc_attr( $q['source'] ); ?>" placeholder="<?php esc_attr_e( 'Code de la route, article R415-5', 'learn-drive-core' ); ?>"></td>
		</tr>
		<tr>
			<th><label for="ld_official_id"><?php esc_html_e( 'Identifiant officiel', 'learn-drive-core' ); ?></label></th>
			<td><input type="text" id="ld_official_id" name="ld_official_id" value="<?php echo esc_attr( $q['official_id'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="ld_verified"><?php esc_html_e( 'Dernière vérification réglementaire', 'learn-drive-core' ); ?></label></th>
			<td><input type="date" id="ld_verified" name="ld_verified" value="<?php echo esc_attr( $q['verified'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="ld_difficulty"><?php esc_html_e( 'Difficulté', 'learn-drive-core' ); ?></label></th>
			<td>
				<select id="ld_difficulty" name="ld_difficulty">
					<option value="1" <?php selected( $q['difficulty'], 1 ); ?>><?php esc_html_e( 'Facile', 'learn-drive-core' ); ?></option>
					<option value="2" <?php selected( $q['difficulty'], 2 ); ?>><?php esc_html_e( 'Moyenne', 'learn-drive-core' ); ?></option>
					<option value="3" <?php selected( $q['difficulty'], 3 ); ?>><?php esc_html_e( 'Difficile', 'learn-drive-core' ); ?></option>
				</select>
				<label><input type="checkbox" name="ld_eliminatory" value="1" <?php checked( $q['eliminatory'] ); ?>> <?php esc_html_e( 'Question éliminatoire', 'learn-drive-core' ); ?></label>
			</td>
		</tr>
		<tr>
			<th><label for="ld_sheet"><?php esc_html_e( 'Fiche liée', 'learn-drive-core' ); ?></label></th>
			<td><?php post_select( 'ld_sheet', PT_SHEET, $q['sheet'] ); ?></td>
		</tr>
		<tr>
			<th><label for="ld_lesson"><?php esc_html_e( 'Leçon de révision', 'learn-drive-core' ); ?></label></th>
			<td><?php post_select( 'ld_lesson', PT_LESSON, $q['lesson'] ); ?><p class="description"><?php esc_html_e( 'Par défaut : la première leçon du même thème.', 'learn-drive-core' ); ?></p></td>
		</tr>
	</table>
	<?php
	$reports = get_post_meta( $post->ID, '_ld_reports', true );
	if ( is_array( $reports ) && $reports ) {
		echo '<h4>' . esc_html__( 'Signalements des apprenants', 'learn-drive-core' ) . '</h4><ul class="ld-reports">';
		foreach ( array_reverse( $reports ) as $report ) {
			printf( '<li><time>%s</time> — %s</li>', esc_html( wp_date( 'd/m/Y H:i', $report['date'] ) ), esc_html( $report['message'] ) );
		}
		echo '</ul>';
	}
	$stats = get_post_meta( $post->ID, '_ld_stats', true );
	if ( is_array( $stats ) && $stats['attempts'] ) {
		/* translators: 1: attempts, 2: success rate */
		echo '<p class="description">' . esc_html( sprintf( __( '%1$d réponses, %2$d %% de réussite.', 'learn-drive-core' ), $stats['attempts'], round( 100 * $stats['correct'] / $stats['attempts'] ) ) ) . '</p>';
	}
}

/**
 * Liste déroulante de contenus.
 *
 * @param string $name     Champ.
 * @param string $type     Type.
 * @param int    $selected Sélection.
 */
function post_select( $name, $type, $selected ) {
	$posts = get_posts(
		array(
			'post_type'      => $type,
			'posts_per_page' => 500,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => array( 'publish', 'draft' ),
		)
	);
	printf( '<select id="%1$s" name="%1$s" class="widefat"><option value="0">—</option>', esc_attr( $name ) );
	foreach ( $posts as $post ) {
		printf( '<option value="%d" %s>%s</option>', (int) $post->ID, selected( (int) $selected, $post->ID, false ), esc_html( $post->post_title ) );
	}
	echo '</select>';
}

/**
 * Méta-box des quiz.
 *
 * @param \WP_Post $post Quiz.
 */
function quiz_box( $post ) {
	wp_nonce_field( 'ld_save_meta', 'ld_meta_nonce' );
	$mode   = Quizzes\mode( $post->ID );
	$own    = get_post_meta( $post->ID, '_ld_rules', true );
	$own    = is_array( $own ) ? $own : array();
	$rules  = Quizzes\rules( $post->ID );
	$manual = get_post_meta( $post->ID, '_ld_question_ids', true );
	$pool   = Quizzes\pool( $post->ID );
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="ld_mode"><?php esc_html_e( 'Mode', 'learn-drive-core' ); ?></label></th>
			<td>
				<select id="ld_mode" name="ld_mode">
					<option value="thematic" <?php selected( $mode, 'thematic' ); ?>><?php esc_html_e( 'Quiz thématique (correction immédiate)', 'learn-drive-core' ); ?></option>
					<option value="practice" <?php selected( $mode, 'practice' ); ?>><?php esc_html_e( 'Entraînement libre', 'learn-drive-core' ); ?></option>
					<option value="exam" <?php selected( $mode, 'exam' ); ?>><?php esc_html_e( 'Examen blanc (conditions réelles, tirage aléatoire)', 'learn-drive-core' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Questions', 'learn-drive-core' ); ?></th>
			<td>
				<p class="description">
					<?php
					/* translators: %d: number of questions */
					echo esc_html( sprintf( __( 'Sans liste manuelle, le quiz pioche dans les %d questions publiées qui partagent ses pays, catégories et thèmes.', 'learn-drive-core' ), count( $pool ) ) );
					?>
				</p>
				<label for="ld_question_ids"><?php esc_html_e( 'Liste manuelle (ID séparés par des virgules, optionnel)', 'learn-drive-core' ); ?></label>
				<input type="text" class="widefat" id="ld_question_ids" name="ld_question_ids" value="<?php echo esc_attr( is_array( $manual ) ? implode( ',', $manual ) : '' ); ?>">
			</td>
		</tr>
	</table>

	<h4><?php esc_html_e( 'Règles', 'learn-drive-core' ); ?></h4>
	<p class="description"><?php esc_html_e( 'Laissez vide pour hériter des règles du pays et de la catégorie (valeur effective entre crochets).', 'learn-drive-core' ); ?></p>
	<?php rules_fields( 'ld_rules', $own, $rules ); ?>
	<?php
}

/**
 * Champs de règles d'examen (quiz, pays, catégorie).
 *
 * @param string $name      Préfixe des champs.
 * @param array  $own       Valeurs propres.
 * @param array  $effective Valeurs effectives (indication).
 */
function rules_fields( $name, array $own, array $effective = array() ) {
	$hint = static function ( $key ) use ( $effective ) {
		if ( ! array_key_exists( $key, $effective ) ) {
			return '';
		}
		$value = is_bool( $effective[ $key ] ) ? ( $effective[ $key ] ? __( 'oui', 'learn-drive-core' ) : __( 'non', 'learn-drive-core' ) ) : $effective[ $key ];
		return ' <span class="description">[' . esc_html( (string) $value ) . ']</span>';
	};
	$bool = static function ( $key ) use ( $own ) {
		return array_key_exists( $key, $own ) ? ( $own[ $key ] ? '1' : '0' ) : '';
	};
	?>
	<table class="form-table ld-rules" role="presentation">
		<tr>
			<th><?php esc_html_e( 'Format', 'learn-drive-core' ); ?></th>
			<td>
				<select name="<?php echo esc_attr( $name ); ?>[format]">
					<option value=""><?php esc_html_e( '— hériter —', 'learn-drive-core' ); ?></option>
					<?php foreach ( Questions\FORMATS as $format ) : ?>
						<option value="<?php echo esc_attr( $format ); ?>" <?php selected( $own['format'] ?? '', $format ); ?>><?php echo esc_html( format_label( $format ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Nombre de questions', 'learn-drive-core' ); ?></th>
			<td><input type="number" min="0" name="<?php echo esc_attr( $name ); ?>[count]" value="<?php echo esc_attr( $own['count'] ?? '' ); ?>"><?php echo $hint( 'count' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Durée totale (secondes)', 'learn-drive-core' ); ?></th>
			<td><input type="number" min="0" name="<?php echo esc_attr( $name ); ?>[duration]" value="<?php echo esc_attr( $own['duration'] ?? '' ); ?>"><?php echo $hint( 'duration' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Temps par question (secondes)', 'learn-drive-core' ); ?></th>
			<td><input type="number" min="0" name="<?php echo esc_attr( $name ); ?>[per_question]" value="<?php echo esc_attr( $own['per_question'] ?? '' ); ?>"><?php echo $hint( 'per_question' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Seuil de réussite', 'learn-drive-core' ); ?></th>
			<td>
				<select name="<?php echo esc_attr( $name ); ?>[pass_type]">
					<option value=""><?php esc_html_e( '— hériter —', 'learn-drive-core' ); ?></option>
					<option value="percent" <?php selected( $own['pass_type'] ?? '', 'percent' ); ?>><?php esc_html_e( 'Pourcentage minimum', 'learn-drive-core' ); ?></option>
					<option value="min_correct" <?php selected( $own['pass_type'] ?? '', 'min_correct' ); ?>><?php esc_html_e( 'Bonnes réponses minimum', 'learn-drive-core' ); ?></option>
					<option value="max_errors" <?php selected( $own['pass_type'] ?? '', 'max_errors' ); ?>><?php esc_html_e( 'Erreurs maximum', 'learn-drive-core' ); ?></option>
				</select>
				<input type="number" min="0" name="<?php echo esc_attr( $name ); ?>[pass_value]" value="<?php echo esc_attr( $own['pass_value'] ?? '' ); ?>"><?php echo $hint( 'pass_value' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Points négatifs par erreur', 'learn-drive-core' ); ?></th>
			<td><input type="number" min="0" max="5" step="0.25" name="<?php echo esc_attr( $name ); ?>[negative]" value="<?php echo esc_attr( $own['negative'] ?? '' ); ?>"><?php echo $hint( 'negative' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Retour arrière', 'learn-drive-core' ); ?></th>
			<td>
				<select name="<?php echo esc_attr( $name ); ?>[allow_back]">
					<option value=""><?php esc_html_e( '— hériter —', 'learn-drive-core' ); ?></option>
					<option value="1" <?php selected( $bool( 'allow_back' ), '1' ); ?>><?php esc_html_e( 'Autorisé', 'learn-drive-core' ); ?></option>
					<option value="0" <?php selected( $bool( 'allow_back' ), '0' ); ?>><?php esc_html_e( 'Interdit', 'learn-drive-core' ); ?></option>
				</select><?php echo $hint( 'allow_back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Rattachements : cours d'une leçon, code d'une fiche, synonymes, historique.
 *
 * @param \WP_Post $post Contenu.
 */
function links_box( $post ) {
	wp_nonce_field( 'ld_save_meta', 'ld_meta_nonce' );
	if ( PT_LESSON === $post->post_type ) {
		echo '<p><label for="ld_course"><strong>' . esc_html__( 'Cours', 'learn-drive-core' ) . '</strong></label>';
		post_select( 'ld_course', PT_COURSE, (int) get_post_meta( $post->ID, '_ld_course', true ) );
		echo '</p><p class="description">' . esc_html__( 'L’ordre des leçons suit l’attribut « Ordre ».', 'learn-drive-core' ) . '</p>';
	}
	if ( PT_SHEET === $post->post_type ) {
		printf( '<p><label for="ld_code"><strong>%s</strong></label><input type="text" class="widefat" id="ld_code" name="ld_code" value="%s"></p>', esc_html__( 'Code officiel', 'learn-drive-core' ), esc_attr( get_post_meta( $post->ID, '_ld_code', true ) ) );
	}
	if ( PT_GLOSSARY === $post->post_type ) {
		printf( '<p><label for="ld_synonyms"><strong>%s</strong></label><input type="text" class="widefat" id="ld_synonyms" name="ld_synonyms" value="%s"><span class="description">%s</span></p>', esc_html__( 'Synonymes', 'learn-drive-core' ), esc_attr( get_post_meta( $post->ID, '_ld_synonyms', true ) ), esc_html__( 'Séparés par des virgules, liés automatiquement dans les textes.', 'learn-drive-core' ) );
	}
	if ( in_array( $post->post_type, array( PT_GUIDE, 'post' ), true ) ) {
		echo '<p><label for="ld_change_note"><strong>' . esc_html__( 'Ce qui a changé (historique visible)', 'learn-drive-core' ) . '</strong></label>';
		echo '<textarea class="widefat" rows="2" id="ld_change_note" name="ld_change_note" placeholder="' . esc_attr__( 'Ex. : nouveau tarif de l’examen', 'learn-drive-core' ) . '"></textarea></p>';
		echo '<p class="description">' . esc_html__( 'Renseignez ce champ lors d’une mise à jour importante : la date et la note s’ajoutent à l’historique.', 'learn-drive-core' ) . '</p>';
		if ( 'post' === $post->post_type ) {
			echo '<p><label for="ld_revise"><strong>' . esc_html__( 'Leçon « À réviser »', 'learn-drive-core' ) . '</strong></label>';
			post_select( 'ld_revise', PT_LESSON, (int) get_post_meta( $post->ID, '_ld_revise', true ) );
			echo '</p>';
		}
	}
}

/**
 * Méta-box d'accès.
 *
 * @param \WP_Post $post Contenu.
 */
function access_box( $post ) {
	wp_nonce_field( 'ld_save_meta', 'ld_meta_nonce' );
	$value     = (string) get_post_meta( $post->ID, '_ld_access', true );
	$effective = \LearnDrive\Core\Access\rule( $post->ID );
	$labels    = array(
		'inherit'    => __( 'Hériter (catégorie, cours, réglage global)', 'learn-drive-core' ),
		'free'       => __( 'Gratuit, ouvert à tous', 'learn-drive-core' ),
		'registered' => __( 'Gratuit avec inscription', 'learn-drive-core' ),
		'premium'    => __( 'Premium (offre payante)', 'learn-drive-core' ),
	);
	echo '<select name="ld_access" class="widefat">';
	foreach ( $labels as $key => $label ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $value ? $value : 'inherit', $key, false ), esc_html( $label ) );
	}
	echo '</select>';
	/* translators: %s: effective access rule */
	echo '<p class="description">' . esc_html( sprintf( __( 'Règle effective : %s', 'learn-drive-core' ), $labels[ $effective ] ?? $effective ) ) . '</p>';
	if ( PT_LESSON === $post->post_type ) {
		echo '<p class="description">' . esc_html__( 'Astuce : insérez un bloc « Lire la suite » pour garder le début de la leçon gratuit.', 'learn-drive-core' ) . '</p>';
	}
}

/**
 * Méta-box SEO.
 *
 * @param \WP_Post $post Contenu.
 */
function seo_box( $post ) {
	wp_nonce_field( 'ld_save_meta', 'ld_meta_nonce' );
	if ( \LearnDrive\Core\seo_plugin_active() ) {
		echo '<p class="description">' . esc_html__( 'Un plugin SEO est actif : titres et descriptions se règlent dans ce plugin. Les données structurées Learn & Drive y sont fusionnées automatiquement.', 'learn-drive-core' ) . '</p>';
		return;
	}
	$title = (string) get_post_meta( $post->ID, '_ld_seo_title', true );
	$desc  = (string) get_post_meta( $post->ID, '_ld_seo_desc', true );
	$og    = (string) get_post_meta( $post->ID, '_ld_og_title', true );
	?>
	<p>
		<label for="ld_seo_title"><strong><?php esc_html_e( 'Titre SEO', 'learn-drive-core' ); ?></strong></label>
		<input type="text" class="widefat" id="ld_seo_title" name="ld_seo_title" maxlength="120" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php echo esc_attr( \LearnDrive\Core\SEO\post_title( $post->ID ) ); ?>">
		<span class="description"><?php esc_html_e( 'Variables : {title} {track} {topic} {country} {site}. Visez 50 à 60 caractères.', 'learn-drive-core' ); ?></span>
	</p>
	<p>
		<label for="ld_og_title"><strong><?php esc_html_e( 'Titre de partage (Open Graph)', 'learn-drive-core' ); ?></strong></label>
		<input type="text" class="widefat" id="ld_og_title" name="ld_og_title" value="<?php echo esc_attr( $og ); ?>">
	</p>
	<p>
		<label for="ld_seo_desc"><strong><?php esc_html_e( 'Meta description', 'learn-drive-core' ); ?></strong></label>
		<textarea class="widefat" rows="2" id="ld_seo_desc" name="ld_seo_desc" maxlength="320"><?php echo esc_textarea( $desc ); ?></textarea>
	</p>
	<p>
		<label><input type="checkbox" name="ld_seo_noindex" value="1" <?php checked( (bool) get_post_meta( $post->ID, '_ld_seo_noindex', true ) ); ?>> <?php esc_html_e( 'Exclure des moteurs de recherche (noindex)', 'learn-drive-core' ); ?></label>
	</p>
	<?php if ( PT_QUESTION === $post->post_type ) : ?>
		<p>
			<label for="ld_canonical"><strong><?php esc_html_e( 'Quasi-doublon : question canonique (ID)', 'learn-drive-core' ); ?></strong></label>
			<input type="number" min="0" id="ld_canonical" name="ld_canonical" value="<?php echo esc_attr( (string) get_post_meta( $post->ID, '_ld_canonical', true ) ); ?>">
		</p>
	<?php endif; ?>
	<?php
}

/**
 * Enregistre les méta-boxes.
 *
 * @param int      $post_id Contenu.
 * @param \WP_Post $post    Contenu.
 */
function save( $post_id, $post ) {
	if ( ! isset( $_POST['ld_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ld_meta_nonce'] ), 'ld_save_meta' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$text = static function ( $key ) {
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- vérifié ci-dessus.
	};

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- vérifié ci-dessus.
	if ( PT_QUESTION === $post->post_type && isset( $_POST['ld_format'] ) ) {
		$format = in_array( $text( 'ld_format' ), Questions\FORMATS, true ) ? $text( 'ld_format' ) : 'multiple';
		update_post_meta( $post_id, '_ld_format', $format );

		$answers = array();
		foreach ( (array) wp_unslash( $_POST['ld_answers'] ?? array() ) as $answer ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé ci-dessous.
			$value = sanitize_text_field( $answer['text'] ?? '' );
			if ( '' !== $value ) {
				$answers[] = array(
					'text'    => $value,
					'correct' => ! empty( $answer['correct'] ),
				);
			}
		}
		if ( 'tf' === $format && count( $answers ) !== 2 ) {
			$answers = array(
				array( 'text' => __( 'Vrai', 'learn-drive-core' ), 'correct' => ! empty( $answers[0]['correct'] ) || ! $answers ),
				array( 'text' => __( 'Faux', 'learn-drive-core' ), 'correct' => ! empty( $answers[1]['correct'] ) ),
			);
		}
		update_post_meta( $post_id, '_ld_answers', array_slice( $answers, 0, 6 ) );

		$numeric = (array) wp_unslash( $_POST['ld_numeric'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé ci-dessous.
		update_post_meta(
			$post_id,
			'_ld_numeric',
			array(
				'value'     => (float) ( $numeric['value'] ?? 0 ),
				'tolerance' => abs( (float) ( $numeric['tolerance'] ?? 0 ) ),
				'unit'      => sanitize_text_field( $numeric['unit'] ?? '' ),
			)
		);

		update_post_meta( $post_id, '_ld_source', $text( 'ld_source' ) );
		update_post_meta( $post_id, '_ld_official_id', $text( 'ld_official_id' ) );
		update_post_meta( $post_id, '_ld_verified', preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $text( 'ld_verified' ) ) ? $text( 'ld_verified' ) : '' );
		update_post_meta( $post_id, '_ld_difficulty', max( 1, min( 3, (int) $text( 'ld_difficulty' ) ) ) );
		update_post_meta( $post_id, '_ld_eliminatory', isset( $_POST['ld_eliminatory'] ) ? 1 : 0 );
		update_post_meta( $post_id, '_ld_sheet', absint( $text( 'ld_sheet' ) ) );
		update_post_meta( $post_id, '_ld_lesson', absint( $text( 'ld_lesson' ) ) );
	}

	if ( PT_QUIZ === $post->post_type && isset( $_POST['ld_mode'] ) ) {
		update_post_meta( $post_id, '_ld_mode', in_array( $text( 'ld_mode' ), Quizzes\MODES, true ) ? $text( 'ld_mode' ) : 'thematic' );
		$ids = array_values( array_filter( array_map( 'absint', explode( ',', (string) $text( 'ld_question_ids' ) ) ) ) );
		update_post_meta( $post_id, '_ld_question_ids', $ids );
		update_post_meta( $post_id, '_ld_rules', Quizzes\sanitize_rules( (array) wp_unslash( $_POST['ld_rules'] ?? array() ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé par sanitize_rules().
		wp_cache_delete( 'ld_exam_' . $post_id, 'learn-drive' );
	}

	if ( isset( $_POST['ld_access'] ) ) {
		update_post_meta( $post_id, '_ld_access', in_array( $text( 'ld_access' ), \LearnDrive\Core\Access\RULES, true ) ? $text( 'ld_access' ) : 'inherit' );
	}

	if ( isset( $_POST['ld_course'] ) ) {
		update_post_meta( $post_id, '_ld_course', absint( $text( 'ld_course' ) ) );
	}
	if ( isset( $_POST['ld_code'] ) ) {
		update_post_meta( $post_id, '_ld_code', $text( 'ld_code' ) );
	}
	if ( isset( $_POST['ld_synonyms'] ) ) {
		update_post_meta( $post_id, '_ld_synonyms', $text( 'ld_synonyms' ) );
	}
	if ( isset( $_POST['ld_revise'] ) ) {
		update_post_meta( $post_id, '_ld_revise', absint( $text( 'ld_revise' ) ) );
	}
	$note = isset( $_POST['ld_change_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ld_change_note'] ) ) : '';
	if ( '' !== $note ) {
		$log   = get_post_meta( $post_id, '_ld_changelog', true );
		$log   = is_array( $log ) ? $log : array();
		$log[] = array(
			'date' => wp_date( 'Y-m-d' ),
			'note' => $note,
		);
		update_post_meta( $post_id, '_ld_changelog', $log );
		update_post_meta( $post_id, '_ld_reviewed', wp_date( 'Y-m-d' ) );
	}

	if ( isset( $_POST['ld_seo_title'] ) ) {
		foreach ( array( 'ld_seo_title', 'ld_og_title' ) as $key ) {
			update_post_meta( $post_id, '_' . $key, $text( $key ) );
		}
		update_post_meta( $post_id, '_ld_seo_desc', sanitize_textarea_field( wp_unslash( $_POST['ld_seo_desc'] ?? '' ) ) );
		if ( isset( $_POST['ld_seo_noindex'] ) ) {
			update_post_meta( $post_id, '_ld_seo_noindex', 1 );
		} else {
			delete_post_meta( $post_id, '_ld_seo_noindex' );
		}
		if ( isset( $_POST['ld_canonical'] ) ) {
			$canonical = absint( $text( 'ld_canonical' ) );
			if ( $canonical && $canonical !== $post_id ) {
				update_post_meta( $post_id, '_ld_canonical', $canonical );
			} else {
				delete_post_meta( $post_id, '_ld_canonical' );
			}
		}
	}
	// phpcs:enable

	if ( PT_QUESTION === $post->post_type ) {
		Questions\update_seo_flags( $post_id );
	}
}
add_action( 'save_post', __NAMESPACE__ . '\\save', 10, 2 );

/* -------------------------------------------------------------------------
 * Champs des taxonomies : couleur, accès, règles d'examen, page pilier.
 * ---------------------------------------------------------------------- */

/**
 * Champs d'édition d'un terme « Catégorie » ou « Pays ».
 *
 * @param \WP_Term $term Terme.
 */
function term_fields( $term ) {
	$taxonomy = $term->taxonomy;
	wp_nonce_field( 'ld_term_meta', 'ld_term_nonce' );
	$rules = get_term_meta( $term->term_id, 'ld_rules', true );
	$rules = is_array( $rules ) ? $rules : array();

	if ( TAX_TRACK === $taxonomy ) {
		?>
		<tr class="form-field">
			<th><label for="ld_color"><?php esc_html_e( 'Couleur', 'learn-drive-core' ); ?></label></th>
			<td><input type="color" id="ld_color" name="ld_color" value="<?php echo esc_attr( get_term_meta( $term->term_id, 'ld_color', true ) ?: '#1F4FD8' ); ?>"></td>
		</tr>
		<tr class="form-field">
			<th><label for="ld_term_access"><?php esc_html_e( 'Accès par défaut', 'learn-drive-core' ); ?></label></th>
			<td>
				<select id="ld_term_access" name="ld_term_access">
					<?php
					$current = (string) get_term_meta( $term->term_id, 'ld_access', true );
					foreach ( array(
						''           => __( 'Hériter du réglage global', 'learn-drive-core' ),
						'free'       => __( 'Gratuit', 'learn-drive-core' ),
						'registered' => __( 'Gratuit avec inscription', 'learn-drive-core' ),
						'premium'    => __( 'Premium', 'learn-drive-core' ),
					) as $key => $label ) {
						printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $current, $key, false ), esc_html( $label ) );
					}
					?>
				</select>
			</td>
		</tr>
		<tr class="form-field">
			<th><label for="ld_min_age"><?php esc_html_e( 'Âge minimum', 'learn-drive-core' ); ?></label></th>
			<td><input type="text" id="ld_min_age" name="ld_min_age" value="<?php echo esc_attr( get_term_meta( $term->term_id, 'ld_min_age', true ) ); ?>"></td>
		</tr>
		<tr class="form-field">
			<th><label for="ld_content"><?php esc_html_e( 'Contenu de la page pilier', 'learn-drive-core' ); ?></label></th>
			<td>
				<?php
				wp_editor(
					(string) get_term_meta( $term->term_id, 'ld_content', true ),
					'ld_content',
					array(
						'textarea_rows' => 12,
						'media_buttons' => true,
					)
				);
				?>
				<p class="description"><?php esc_html_e( 'Conditions d’inscription, déroulé de l’examen, FAQ éditoriale…', 'learn-drive-core' ); ?></p>
			</td>
		</tr>
		<?php
	}
	?>
	<tr class="form-field">
		<th><?php esc_html_e( 'Règles de l’examen', 'learn-drive-core' ); ?></th>
		<td><?php rules_fields( 'ld_term_rules', $rules ); ?></td>
	</tr>
	<?php
}
add_action( TAX_TRACK . '_edit_form_fields', __NAMESPACE__ . '\\term_fields' );
add_action( TAX_COUNTRY . '_edit_form_fields', __NAMESPACE__ . '\\term_fields' );

/**
 * Enregistre les champs d'un terme.
 *
 * @param int $term_id Terme.
 */
function save_term( $term_id ) {
	if ( ! isset( $_POST['ld_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['ld_term_nonce'] ), 'ld_term_meta' ) || ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	if ( isset( $_POST['ld_color'] ) ) {
		update_term_meta( $term_id, 'ld_color', sanitize_hex_color( wp_unslash( $_POST['ld_color'] ) ) );
	}
	if ( isset( $_POST['ld_term_access'] ) ) {
		update_term_meta( $term_id, 'ld_access', sanitize_key( wp_unslash( $_POST['ld_term_access'] ) ) );
	}
	if ( isset( $_POST['ld_min_age'] ) ) {
		update_term_meta( $term_id, 'ld_min_age', sanitize_text_field( wp_unslash( $_POST['ld_min_age'] ) ) );
	}
	if ( isset( $_POST['ld_content'] ) ) {
		update_term_meta( $term_id, 'ld_content', wp_kses_post( wp_unslash( $_POST['ld_content'] ) ) );
	}
	if ( isset( $_POST['ld_term_rules'] ) ) {
		update_term_meta( $term_id, 'ld_rules', Quizzes\sanitize_rules( (array) wp_unslash( $_POST['ld_term_rules'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé par sanitize_rules().
	}
}
add_action( 'edited_' . TAX_TRACK, __NAMESPACE__ . '\\save_term' );
add_action( 'edited_' . TAX_COUNTRY, __NAMESPACE__ . '\\save_term' );

/**
 * Colonnes utiles dans la liste des questions.
 *
 * @param array $columns Colonnes.
 * @return array
 */
function question_columns( $columns ) {
	$columns['ld_format']  = __( 'Format', 'learn-drive-core' );
	$columns['ld_seo']     = __( 'Indexable', 'learn-drive-core' );
	$columns['ld_reports'] = __( 'Signalements', 'learn-drive-core' );
	return $columns;
}
add_filter( 'manage_' . PT_QUESTION . '_posts_columns', __NAMESPACE__ . '\\question_columns' );

/**
 * Contenu des colonnes.
 *
 * @param string $column  Colonne.
 * @param int    $post_id Question.
 */
function question_column( $column, $post_id ) {
	if ( 'ld_format' === $column ) {
		echo esc_html( format_label( (string) get_post_meta( $post_id, '_ld_format', true ) ) );
	} elseif ( 'ld_seo' === $column ) {
		echo get_post_meta( $post_id, '_ld_noindex', true ) ? '<span aria-hidden="true">✗</span> ' . esc_html__( 'Non', 'learn-drive-core' ) : '<span aria-hidden="true">✓</span> ' . esc_html__( 'Oui', 'learn-drive-core' );
	} elseif ( 'ld_reports' === $column ) {
		echo esc_html( (string) (int) get_post_meta( $post_id, '_ld_report_count', true ) );
	}
}
add_action( 'manage_' . PT_QUESTION . '_posts_custom_column', __NAMESPACE__ . '\\question_column', 10, 2 );
