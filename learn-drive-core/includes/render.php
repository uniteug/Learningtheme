<?php
/**
 * Rendu HTML du moteur de quiz.
 *
 * Tout est rendu côté serveur : chaque question existe en HTML lisible par
 * les moteurs de recherche, avec de vrais boutons radio / cases à cocher. Le
 * module Interactivity API (assets/js/quiz.js) ajoute ensuite le mode
 * « une question par écran », le chronomètre et la correction.
 *
 * @package LearnDriveCore
 */

namespace LearnDrive\Core\Render;

use LearnDrive\Core\Questions;
use LearnDrive\Core\Quizzes;
use LearnDrive\Core\Access;
use function LearnDrive\Core\human_duration;
use function LearnDrive\Core\format_label;

defined( 'ABSPATH' ) || exit;

/**
 * Configuration partagée par tous les quiz de la page (une seule fois).
 */
function quiz_config() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;

	wp_interactivity_config(
		'learn-drive/quiz',
		array(
			'restUrl' => esc_url_raw( rest_url( 'learn/v1/' ) ),
			'nonce'   => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
			'i18n'    => array(
				'correct'    => __( 'Bonne réponse', 'learn-drive-core' ),
				'wrong'      => __( 'Mauvaise réponse', 'learn-drive-core' ),
				'answer'     => __( 'Réponse attendue :', 'learn-drive-core' ),
				'locked'     => __( 'La correction détaillée est réservée aux abonnés.', 'learn-drive-core' ),
				'unlock'     => __( 'Débloquer', 'learn-drive-core' ),
				'passed'     => __( 'Réussi ! Vous avez atteint le seuil de réussite.', 'learn-drive-core' ),
				'failed'     => __( 'Pas encore : continuez à réviser les thèmes ci-dessous.', 'learn-drive-core' ),
				'error'      => __( 'Connexion impossible. Vos réponses sont conservées : réessayez.', 'learn-drive-core' ),
				'reportSent' => __( 'Merci, votre signalement a été transmis.', 'learn-drive-core' ),
				'copied'     => __( 'Lien copié : envoyez-le pour lancer le défi !', 'learn-drive-core' ),
				'challenge'  => __( 'Score à battre :', 'learn-drive-core' ),
				'reviewLink' => __( 'Revoir la leçon', 'learn-drive-core' ),
				'timeUp'     => __( 'Temps écoulé', 'learn-drive-core' ),
				'errorsOf'   => __( 'erreur(s) sur', 'learn-drive-core' ),
				/* translators: %s: score, e.g. 32/40 */
				'shareText'  => __( 'J’ai obtenu %s. Ferez-vous mieux ?', 'learn-drive-core' ),
			),
		)
	);
}

/**
 * Image d'une question.
 *
 * @param array $question Question normalisée.
 * @param bool  $lcp      Image principale de la page (préchargée, sans lazy-loading).
 * @return string
 */
function media_html( array $question, $lcp = false ) {
	if ( ! $question['image_id'] ) {
		return '';
	}

	$attrs = array(
		'class'    => 'ld-q__img',
		'sizes'    => '240px',
		'decoding' => 'async',
	);
	if ( $lcp ) {
		$attrs['fetchpriority'] = 'high';
		$attrs['loading']       = false;
	} else {
		$attrs['loading'] = 'lazy';
	}

	$alt = get_post_meta( $question['image_id'], '_wp_attachment_image_alt', true );
	if ( '' === trim( (string) $alt ) ) {
		// Le texte alternatif est obligatoire : à défaut, l'énoncé décrit la situation.
		$attrs['alt'] = $question['title'];
	}

	$image = wp_get_attachment_image( $question['image_id'], 'learn-drive-800', false, $attrs );
	return $image ? '<figure class="ld-q__media">' . $image . '</figure>' : '';
}

/**
 * Propositions d'une question.
 *
 * @param array $question Question normalisée.
 * @param array $args     Options : name (préfixe des champs), interactive.
 * @return string
 */
function options_html( array $question, array $args ) {
	$name = $args['name'] . $question['id'];
	$html = '';

	if ( 'numeric' === $question['format'] ) {
		$id    = $name . '-value';
		$unit  = $question['numeric']['unit'];
		$html .= '<div class="ld-q__numeric">';
		$html .= sprintf( '<label class="ld-sr-only" for="%s">%s</label>', esc_attr( $id ), esc_html__( 'Votre réponse', 'learn-drive-core' ) );
		$html .= sprintf(
			'<input id="%1$s" type="number" step="any" inputmode="decimal" name="%2$s" data-wp-on--input="actions.select" data-wp-bind--disabled="state.isLocked" placeholder="%3$s">',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr__( 'Votre réponse', 'learn-drive-core' )
		);
		if ( $unit ) {
			$html .= '<span class="ld-q__unit">' . esc_html( $unit ) . '</span>';
		}
		$html .= '</div>';
		return $html;
	}

	if ( 'order' === $question['format'] ) {
		return order_html( $question );
	}

	if ( 'match' === $question['format'] ) {
		return match_html( $question, $name );
	}

	if ( 'hotspot' === $question['format'] ) {
		return hotspot_html( $question, $args );
	}

	if ( 'flashcard' === $question['format'] ) {
		return sprintf(
			'<div class="ld-q__flash"><button type="button" class="ld-btn" data-wp-on--click="actions.flipCard">%s</button></div>',
			esc_html__( 'Retourner la carte', 'learn-drive-core' )
		);
	}

	$type  = 'multiple' === $question['format'] ? 'checkbox' : 'radio';
	$html .= '<div class="ld-q__options' . ( 'tf' === $question['format'] ? ' is-tf' : '' ) . '">';

	foreach ( $question['answers'] as $index => $answer ) {
		$id      = $name . '-' . $index;
		$letter  = 'tf' === $question['format'] ? '' : '<span class="ld-opt__letter" aria-hidden="true">' . esc_html( Questions\letter( $index ) ) . '</span>';
		$context = wp_json_encode( array( 'opt' => $index ) );

		$html .= sprintf(
			'<label class="ld-opt" for="%1$s" data-wp-context=\'%2$s\' data-wp-class--is-chosen="state.isChosen" data-wp-class--is-solution="state.isSolution" data-wp-class--is-missed="state.isChosenWrong">'
			. '<input id="%1$s" type="%3$s" name="%4$s" value="%5$d" data-wp-on--change="actions.select" data-wp-bind--checked="state.isChosen" data-wp-bind--disabled="state.isLocked">'
			. '%6$s<span class="ld-opt__text">%7$s</span><span class="ld-opt__mark" aria-hidden="true"></span></label>',
			esc_attr( $id ),
			esc_attr( $context ),
			esc_attr( $type ),
			esc_attr( $name ),
			(int) $index,
			$letter,
			esc_html( $answer['text'] )
		);
	}

	$html .= '</div>';

	if ( 'multiple' === $question['format'] ) {
		$html = '<p class="ld-q__hint">' . esc_html__( 'Une ou plusieurs bonnes réponses.', 'learn-drive-core' ) . '</p>' . $html;
	}

	return $html;
}

/**
 * Une question dans un quiz.
 *
 * @param array $question Question normalisée.
 * @param int   $index    Position.
 * @param array $args     Options du quiz.
 * @return string
 */
function question_item( array $question, $index, array $args ) {
	$context = array(
		'qid'    => $question['id'],
		'i'      => $index,
		'format' => $question['format'],
	);
	if ( in_array( $question['format'], array( 'order', 'match' ), true ) ) {
		$context['size'] = count( $question['answers'] );
	}
	$context = wp_json_encode( $context );

	$stem = esc_html( $question['title'] );
	if ( $args['link_stems'] ) {
		$stem = sprintf( '<a class="ld-q__link" href="%s">%s</a>', esc_url( $question['url'] ), $stem );
	}

	$heading_tag = $args['heading_level'] ? 'h' . (int) $args['heading_level'] : 'p';

	$html  = sprintf( '<li class="ld-q" id="ld-q-%1$s-%2$d" data-wp-context=\'%3$s\' data-wp-class--is-current="state.isCurrent" data-wp-class--is-right="state.isRight" data-wp-class--is-wrong="state.isWrong">', esc_attr( $args['uid'] ), (int) $question['id'], esc_attr( $context ) );
	$html .= '<fieldset class="ld-q__fieldset">';
	$html .= sprintf(
		'<legend class="ld-q__legend"><span class="ld-q__num">%1$s</span><%2$s class="ld-q__stem">%3$s</%2$s></legend>',
		/* translators: %d: question number */
		esc_html( sprintf( __( 'Question %d', 'learn-drive-core' ), $index + 1 ) ),
		$heading_tag,
		$stem
	);
	$html .= '<div class="ld-q__body">';
	if ( 'hotspot' !== $question['format'] ) {
		$html .= media_html( $question, $args['lcp'] && 0 === $index );
	}
	$html .= options_html( $question, $args );
	$html .= '</div></fieldset>';

	if ( $args['feedback'] ) {
		$html .= '<div class="ld-q__actions">';
		if ( ! in_array( $question['format'], array( 'tf', 'single', 'flashcard', 'hotspot' ), true ) ) {
			$html .= sprintf( '<button type="button" class="ld-btn ld-btn--primary" data-wp-on--click="actions.check" data-wp-bind--disabled="!state.canCheck" data-wp-bind--hidden="state.isChecked">%s</button>', esc_html__( 'Valider', 'learn-drive-core' ) );
		}
		if ( 'flashcard' === $question['format'] ) {
			$html .= sprintf(
				'<span class="ld-flash-eval" data-wp-bind--hidden="!state.isFlipped"><button type="button" class="ld-btn" data-wp-on--click="actions.knew">%s</button> <button type="button" class="ld-btn" data-wp-on--click="actions.didNotKnow">%s</button></span>',
				esc_html__( 'Je savais', 'learn-drive-core' ),
				esc_html__( 'À revoir', 'learn-drive-core' )
			);
		}
		$html .= '</div>';
	}

	$html .= '<div class="ld-q__feedback" role="status" aria-live="polite" data-wp-watch="callbacks.renderFeedback"></div>';

	if ( $args['report'] ) {
		$html .= sprintf(
			'<details class="ld-q__report"><summary>%1$s</summary><label class="ld-sr-only" for="ld-report-%2$s-%3$d">%4$s</label><textarea id="ld-report-%2$s-%3$d" rows="3" maxlength="1000" placeholder="%4$s"></textarea><button type="button" class="ld-btn" data-wp-on--click="actions.report">%5$s</button></details>',
			esc_html__( 'Signaler une erreur', 'learn-drive-core' ),
			esc_attr( $args['uid'] ),
			(int) $question['id'],
			esc_attr__( 'Qu’est-ce qui ne va pas dans cette question ?', 'learn-drive-core' ),
			esc_html__( 'Envoyer', 'learn-drive-core' )
		);
	}

	$html .= '</li>';
	return $html;
}

/**
 * Identifiant unique d'un quiz dans la page.
 *
 * @return string
 */
function next_uid() {
	static $count = 0;
	++$count;
	return 'z' . $count;
}

/**
 * Rend un quiz complet.
 *
 * @param array $args {
 *     Options du quiz.
 *
 *     @type int    $quiz_id      Quiz (0 pour une série libre).
 *     @type int[]  $question_ids Questions (défaut : celles du quiz).
 *     @type string $layout       player (une question par écran) ou stack.
 *     @type string $mode         practice|thematic|exam.
 *     @type string $session      Jeton d'examen blanc.
 *     @type bool   $compact      Version intégrée (« Testez-vous »).
 *     @type string $heading      Titre affiché au-dessus.
 *     @type string $more_url     Lien vers le quiz complet.
 * }
 * @return string
 */
function quiz( array $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'quiz_id'       => 0,
			'question_ids'  => null,
			'layout'        => 'player',
			'mode'          => '',
			'session'       => '',
			'compact'       => false,
			'heading'       => '',
			'more_url'      => '',
			'more_label'    => '',
			'link_stems'    => true,
			'class'         => '',
			'heading_level' => 3,
			'lcp'           => false,
		)
	);

	$quiz_id = (int) $args['quiz_id'];
	$mode    = $args['mode'] ? $args['mode'] : ( $quiz_id ? Quizzes\mode( $quiz_id ) : 'practice' );
	$rules   = $quiz_id ? Quizzes\rules( $quiz_id ) : Quizzes\default_rules();
	$seed    = '';

	if ( $args['session'] && $quiz_id ) {
		$session = Quizzes\read_session( $args['session'], $quiz_id );
		$seed    = $session ? $session['s'] : '';
	}

	$ids = null === $args['question_ids'] ? Quizzes\question_ids( $quiz_id, $seed ? $seed : null ) : array_map( 'intval', $args['question_ids'] );
	if ( ! $ids ) {
		return '';
	}

	_prime_post_caches( $ids, true, true );
	update_object_term_cache( $ids, \LearnDrive\Core\PT_QUESTION );

	quiz_config();

	$uid       = next_uid();
	$is_exam   = 'exam' === $mode;
	$locked    = $quiz_id && ! Access\can_access( $quiz_id );
	$in_play   = 'stack' === $args['layout'];
	$per_q     = $is_exam ? (int) $rules['per_question'] : 0;
	$duration  = $is_exam ? (int) $rules['duration'] : 0;
	$exam_home = $is_exam && ! $seed;

	$context = array(
		'quizId'      => $quiz_id,
		'uid'         => $uid,
		'mode'        => $is_exam ? 'exam' : 'practice',
		'layout'      => $args['layout'],
		'ids'         => array_values( $ids ),
		'total'       => count( $ids ),
		'current'     => 0,
		'intro'       => ! $in_play,
		'playing'     => $in_play,
		'finished'    => false,
		'reviewing'   => false,
		'restored'    => false,
		'startedAt'   => 0,
		'busy'        => false,
		'answers'     => (object) array(),
		'fb'          => (object) array(),
		'flipped'     => (object) array(),
		'duration'    => $duration,
		'perQuestion' => $per_q,
		'timeLeft'    => $duration,
		'qTimeLeft'   => $per_q,
		'extraTime'   => false,
		'allowBack'   => (bool) $rules['allow_back'],
		'session'     => $seed ? $args['session'] : '',
		'summary'     => array(
			'correct' => 0,
			'total'   => 0,
			'percent' => 0,
			'passed'  => false,
			'topics'  => array(),
		),
		'challenge'   => '',
		'resume'      => false,
		'big'         => false,
		'dys'         => false,
		'message'     => '',
		'storageKey'  => 'ld-quiz-' . ( $quiz_id ? $quiz_id : $uid ),
		'shareUrl'    => $quiz_id ? get_permalink( $quiz_id ) : '',
	);

	$item_args = array(
		'uid'           => $uid,
		'name'          => 'ld-' . $uid . '-',
		'link_stems'    => $args['link_stems'] && ! $seed,
		'feedback'      => ! $is_exam,
		'report'        => ! $args['compact'],
		'heading_level' => $args['heading_level'],
		'lcp'           => $args['lcp'],
	);

	$classes = array( 'ld-quiz', 'is-' . $args['layout'], $is_exam ? 'is-exam' : 'is-practice' );
	if ( $args['compact'] ) {
		$classes[] = 'is-compact';
	}
	if ( $args['class'] ) {
		$classes[] = $args['class'];
	}

	$html = sprintf(
		'<div class="%1$s" data-wp-interactive="learn-drive/quiz" data-wp-context=\'%2$s\' data-wp-init="callbacks.init" data-wp-watch--persist="callbacks.persist" data-wp-class--is-playing="context.playing" data-wp-class--is-finished="context.finished" data-wp-class--is-reviewing="context.reviewing" data-wp-class--is-big="context.big" data-wp-class--is-dys="context.dys">',
		esc_attr( implode( ' ', $classes ) ),
		esc_attr( wp_json_encode( $context ) )
	);

	if ( $args['heading'] ) {
		$html .= '<h2 class="ld-quiz__heading">' . esc_html( $args['heading'] ) . '</h2>';
	}

	// Écran d'accueil : règles, bouton de départ, reprise.
	if ( ! $in_play ) {
		$facts = array(
			/* translators: %d: number of questions */
			sprintf( _n( '%d question', '%d questions', count( $ids ), 'learn-drive-core' ), count( $ids ) ),
		);
		if ( $duration ) {
			/* translators: %s: duration */
			$facts[] = sprintf( __( '%s au total', 'learn-drive-core' ), human_duration( $duration ) );
		} elseif ( $per_q ) {
			/* translators: %s: duration */
			$facts[] = sprintf( __( '%s par question', 'learn-drive-core' ), human_duration( $per_q ) );
		} else {
			$facts[] = __( 'Sans limite de temps', 'learn-drive-core' );
		}
		$facts[] = $is_exam ? Quizzes\pass_label( array_merge( $rules, array( 'count' => count( $ids ) ) ) ) : __( 'Correction après chaque question', 'learn-drive-core' );
		if ( $is_exam && ! $rules['allow_back'] ) {
			$facts[] = __( 'Retour arrière impossible', 'learn-drive-core' );
		}

		$html .= '<div class="ld-quiz__intro" data-wp-bind--hidden="!context.intro">';
		$html .= '<ul class="ld-quiz__facts">';
		foreach ( $facts as $fact ) {
			$html .= '<li>' . esc_html( $fact ) . '</li>';
		}
		$html .= '</ul>';

		$html .= '<p class="ld-quiz__challenge" hidden data-wp-bind--hidden="!context.challenge"><span data-wp-text="state.challengeText"></span></p>';

		if ( $locked ) {
			$html .= Access\paywall_html( $quiz_id, 'quiz' );
		} else {
			$html .= '<div class="ld-quiz__start">';
			if ( $exam_home ) {
				$html .= sprintf(
					'<a class="wp-element-button ld-btn--xl" href="%s" rel="nofollow">%s</a>',
					esc_url( add_query_arg( 'ld_session', 'new', get_permalink( $quiz_id ) ) ),
					esc_html__( 'Lancer l’examen blanc', 'learn-drive-core' )
				);
			} else {
				$html .= sprintf( '<button type="button" class="wp-element-button ld-btn--xl" data-wp-on--click="actions.start">%s</button>', esc_html( $is_exam ? __( 'Commencer l’examen', 'learn-drive-core' ) : __( 'Commencer le quiz', 'learn-drive-core' ) ) );
				$html .= sprintf( '<button type="button" class="ld-btn" hidden data-wp-bind--hidden="!context.resume" data-wp-on--click="actions.resume">%s</button>', esc_html__( 'Reprendre où j’en étais', 'learn-drive-core' ) );
			}
			if ( $is_exam && ( $duration || $per_q ) && ! $exam_home ) {
				$html .= sprintf(
					'<label class="ld-quiz__extra"><input type="checkbox" data-wp-on--change="actions.toggleExtraTime" data-wp-bind--checked="context.extraTime"> %s</label>',
					esc_html__( 'Temps majoré (+ 50 %, aménagement d’épreuve)', 'learn-drive-core' )
				);
			}
			$html .= '</div>';
		}
		$html .= '</div>';
	}

	// Barre de progression et outils pendant le quiz.
	if ( ! $in_play ) {
		$html .= '<div class="ld-quiz__bar" hidden data-wp-bind--hidden="!state.showBar">';
		$html .= sprintf( '<button type="button" class="ld-icon-btn ld-quiz__quit" data-wp-on--click="actions.quit" aria-label="%s">✕</button>', esc_attr__( 'Quitter le quiz', 'learn-drive-core' ) );
		$html .= '<div class="ld-quiz__progress"><span class="ld-quiz__count" data-wp-text="state.progressText"></span>';
		$html .= sprintf( '<div class="ld-quiz__track" role="progressbar" aria-label="%s" aria-valuemin="0" data-wp-bind--aria-valuemax="context.total" data-wp-bind--aria-valuenow="state.position"><span class="ld-quiz__fill" data-wp-style--width="state.progressWidth"></span></div></div>', esc_attr__( 'Progression', 'learn-drive-core' ) );
		if ( $duration || $per_q ) {
			$html .= sprintf( '<span class="ld-quiz__timer" data-wp-class--is-urgent="state.isUrgent" data-wp-bind--hidden="context.finished"><span class="ld-sr-only">%s</span><span data-wp-text="state.timerText" aria-hidden="true"></span></span>', esc_html__( 'Temps restant', 'learn-drive-core' ) );
		}
		$html .= sprintf( '<button type="button" class="ld-icon-btn" data-wp-on--click="actions.toggleBig" data-wp-bind--aria-pressed="context.big" title="%1$s"><span aria-hidden="true">A+</span><span class="ld-sr-only">%1$s</span></button>', esc_attr__( 'Agrandir le texte', 'learn-drive-core' ) );
		$html .= sprintf( '<button type="button" class="ld-icon-btn" data-wp-on--click="actions.toggleDys" data-wp-bind--aria-pressed="context.dys" title="%1$s"><span aria-hidden="true">Dys</span><span class="ld-sr-only">%1$s</span></button>', esc_attr__( 'Police adaptée à la dyslexie', 'learn-drive-core' ) );
		$html .= '</div>';
	}

	// Accueil d'un examen tiré d'une grande banque avec quotas officiels : composition de
	// la fiche plutôt qu'une liste de questions qui ne seront pas celles de l'épreuve.
	$blueprint = $exam_home && $quiz_id ? exam_blueprint( $quiz_id ) : '';
	if ( $blueprint ) {
		$html .= $blueprint;
	} else {
		// Questions.
		$html .= '<ol class="ld-quiz__questions">';
		foreach ( $ids as $index => $id ) {
			$question = Questions\get( $id );
			if ( $question ) {
				$html .= question_item( $question, $index, $item_args );
			}
		}
		$html .= '</ol>';
	}

	// Navigation.
	if ( ! $in_play ) {
		$html .= '<div class="ld-quiz__nav" hidden data-wp-bind--hidden="!state.showBar">';
		$html .= sprintf( '<button type="button" class="ld-btn" data-wp-on--click="actions.prev" data-wp-bind--hidden="!state.canPrev">%s</button>', esc_html__( '← Précédente', 'learn-drive-core' ) );
		$html .= sprintf( '<button type="button" class="ld-btn ld-btn--primary" data-wp-on--click="actions.next" data-wp-bind--hidden="!state.canNext">%s</button>', esc_html__( 'Suivante →', 'learn-drive-core' ) );
		$html .= sprintf( '<button type="button" class="ld-btn ld-btn--primary" data-wp-on--click="actions.finish" data-wp-bind--hidden="!state.canFinish" data-wp-bind--disabled="context.busy">%s</button>', esc_html( $is_exam ? __( 'Terminer et corriger', 'learn-drive-core' ) : __( 'Voir mon résultat', 'learn-drive-core' ) ) );
		$html .= sprintf( '<button type="button" class="ld-btn" data-wp-on--click="actions.closeReview" data-wp-bind--hidden="!context.reviewing">%s</button>', esc_html__( 'Retour au résultat', 'learn-drive-core' ) );
		$html .= '</div>';
	}

	$html .= '<p class="ld-quiz__message" role="alert" data-wp-text="context.message" data-wp-bind--hidden="!context.message" hidden></p>';

	// Résultat.
	if ( ! $in_play ) {
		$html .= '<section class="ld-quiz__result" tabindex="-1" hidden data-wp-bind--hidden="!state.showResult" aria-live="polite">';
		$html .= sprintf( '<h2 class="ld-quiz__result-title">%s</h2>', esc_html__( 'Votre résultat', 'learn-drive-core' ) );
		$html .= '<p class="ld-quiz__score"><span data-wp-text="state.scoreText"></span></p>';
		$html .= '<p class="ld-quiz__verdict" data-wp-class--is-passed="context.summary.passed" data-wp-text="state.verdict"></p>';
		$html .= '<p class="ld-quiz__threshold" data-wp-text="context.summary.threshold"></p>';
		$html .= sprintf( '<h3 class="ld-quiz__topics-title" data-wp-bind--hidden="!state.hasTopics">%s</h3>', esc_html__( 'Erreurs par thème', 'learn-drive-core' ) );
		$html .= '<ul class="ld-quiz__topics"><template data-wp-each--topic="context.summary.topics"><li><a data-wp-bind--href="context.topic.link" data-wp-text="context.topic.name"></a> <span class="ld-quiz__topic-count" data-wp-text="state.topicCount"></span></li></template></ul>';
		$html .= '<div class="ld-quiz__result-actions">';
		$html .= sprintf( '<button type="button" class="ld-btn ld-btn--primary" data-wp-on--click="actions.review">%s</button>', esc_html__( 'Revoir la correction', 'learn-drive-core' ) );
		if ( $is_exam && $quiz_id ) {
			$html .= sprintf( '<a class="ld-btn" href="%s" rel="nofollow">%s</a>', esc_url( add_query_arg( 'ld_session', 'new', get_permalink( $quiz_id ) ) ), esc_html__( 'Nouvel examen blanc', 'learn-drive-core' ) );
		} else {
			$html .= sprintf( '<button type="button" class="ld-btn" data-wp-on--click="actions.restart">%s</button>', esc_html__( 'Recommencer', 'learn-drive-core' ) );
		}
		if ( $quiz_id ) {
			$html .= sprintf( '<button type="button" class="ld-btn" data-wp-on--click="actions.share">%s</button>', esc_html__( 'Défier un ami', 'learn-drive-core' ) );
		}
		$html .= '</div>';
		$html .= '</section>';
	}

	if ( $args['more_url'] ) {
		$html .= sprintf( '<p class="ld-quiz__more"><a href="%s">%s</a></p>', esc_url( $args['more_url'] ), esc_html( $args['more_label'] ? $args['more_label'] : __( 'Faire le quiz complet', 'learn-drive-core' ) ) );
	}

	$html .= '</div>';

	return $html;
}

/**
 * Page d'une question : énoncé, média, propositions, puis réponse et
 * explication présentes dans le HTML dès le chargement (repliées derrière
 * « Voir la réponse »).
 *
 * @param int    $question_id Question.
 * @param string $layout      stack|media-left.
 * @return string
 */
function single_question( $question_id, $layout = 'stack' ) {
	$question = Questions\get( $question_id );
	if ( ! $question ) {
		return '';
	}

	$can_see = Access\can_access( $question_id );

	$quiz = quiz(
		array(
			'question_ids'  => array( $question_id ),
			'layout'        => 'stack',
			'mode'          => 'practice',
			'link_stems'    => false,
			'heading_level' => 0,
			'lcp'           => true,
			'class'         => 'is-single' . ( 'media-left' === $layout ? ' is-media-left' : '' ),
		)
	);

	$html = $quiz;

	$html .= '<details class="ld-answer"' . ( $can_see ? '' : ' open' ) . '>';
	$html .= '<summary class="ld-answer__toggle">' . esc_html__( 'Voir la réponse et l’explication', 'learn-drive-core' ) . '</summary>';

	if ( $can_see ) {
		$solution = Questions\solution_text( $question );
		if ( $solution ) {
			$html .= '<p class="ld-answer__solution"><strong>' . esc_html__( 'Bonne réponse :', 'learn-drive-core' ) . '</strong> ' . esc_html( $solution ) . '</p>';
		}
		$html .= '<div class="ld-answer__explanation">' . apply_filters( 'the_content', $question['content'] ) . '</div>'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- filtre du cœur.
	} else {
		$html .= Access\paywall_html( $question_id, 'question' );
	}

	$meta = array();
	if ( $question['source'] ) {
		$meta[] = '<li><strong>' . esc_html__( 'Source :', 'learn-drive-core' ) . '</strong> ' . esc_html( $question['source'] ) . '</li>';
	}
	if ( $question['official_id'] ) {
		$meta[] = '<li><strong>' . esc_html__( 'Référence officielle :', 'learn-drive-core' ) . '</strong> ' . esc_html( $question['official_id'] ) . '</li>';
	}
	if ( $question['verified'] ) {
		$meta[] = '<li><strong>' . esc_html__( 'Vérifiée le', 'learn-drive-core' ) . '</strong> <time datetime="' . esc_attr( $question['verified'] ) . '">' . esc_html( wp_date( get_option( 'date_format' ), strtotime( $question['verified'] ) ) ) . '</time></li>';
	}
	$rate = Questions\success_rate( $question_id );
	if ( null !== $rate ) {
		/* translators: %d: percentage */
		$meta[] = '<li>' . esc_html( sprintf( __( '%d %% des apprenants trouvent la bonne réponse', 'learn-drive-core' ), $rate ) ) . '</li>';
	}
	$meta[] = '<li>' . esc_html( format_label( $question['format'] ) ) . '</li>';

	$html .= '<ul class="ld-answer__meta">' . implode( '', $meta ) . '</ul>';
	$html .= '</details>';

	return '<div class="ld-question-page">' . $html . '</div>';
}

/**
 * « Remettre dans l'ordre » : les éléments sont affichés mélangés et déplacés
 * avec des boutons (clavier et lecteur d'écran), sans glisser-déposer.
 *
 * Les identifiants (opt) sont les positions d'affichage : l'ordre juste ne se
 * lit pas dans le code source.
 *
 * @param array $question Question normalisée.
 * @return string
 */
function order_html( array $question ) {
	$html  = '<p class="ld-q__hint">' . esc_html__( 'Remettez les éléments dans le bon ordre avec les flèches.', 'learn-drive-core' ) . '</p>';
	$html .= '<ol class="ld-order">';
	foreach ( Questions\shuffled_indexes( $question ) as $display => $original ) {
		$text  = $question['answers'][ $original ]['text'];
		$html .= sprintf(
			'<li class="ld-order__item" data-wp-context=\'%1$s\' data-wp-style--order="state.orderPos" data-wp-class--is-solution="state.isOrderRight" data-wp-class--is-missed="state.isOrderWrong">'
			. '<span class="ld-order__pos" data-wp-text="state.orderLabel">%2$d</span><span class="ld-order__text">%3$s</span>'
			. '<span class="ld-order__moves"><button type="button" class="ld-icon-btn" data-wp-on--click="actions.moveUp" data-wp-bind--disabled="state.isLocked" aria-label="%4$s"><span aria-hidden="true">↑</span></button>'
			. '<button type="button" class="ld-icon-btn" data-wp-on--click="actions.moveDown" data-wp-bind--disabled="state.isLocked" aria-label="%5$s"><span aria-hidden="true">↓</span></button></span></li>',
			esc_attr( wp_json_encode( array( 'opt' => $display ) ) ),
			$display + 1,
			esc_html( $text ),
			/* translators: %s: item text */
			esc_attr( sprintf( __( 'Monter : %s', 'learn-drive-core' ), $text ) ),
			/* translators: %s: item text */
			esc_attr( sprintf( __( 'Descendre : %s', 'learn-drive-core' ), $text ) )
		);
	}
	$html .= '</ol>';
	$html .= '<p class="ld-sr-only" aria-live="polite" data-ld-announce></p>';
	return $html;
}

/**
 * « Association » : chaque élément de gauche reçoit une liste déroulante des
 * correspondances (mélangées).
 *
 * @param array  $question Question normalisée.
 * @param string $name     Préfixe des champs.
 * @return string
 */
function match_html( array $question, $name ) {
	$shuffled = Questions\shuffled_indexes( $question );
	$options  = '<option value="">' . esc_html__( '— Choisir —', 'learn-drive-core' ) . '</option>';
	foreach ( $shuffled as $display => $original ) {
		$options .= sprintf( '<option value="%d">%s</option>', (int) $display, esc_html( $question['answers'][ $original ]['match'] ) );
	}

	$html = '<p class="ld-q__hint">' . esc_html__( 'Associez chaque élément à sa correspondance.', 'learn-drive-core' ) . '</p><ul class="ld-match">';
	foreach ( $question['answers'] as $index => $answer ) {
		$id    = $name . '-m' . $index;
		$html .= sprintf(
			'<li class="ld-match__row" data-wp-context=\'%1$s\' data-wp-class--is-solution="state.isMatchRight" data-wp-class--is-missed="state.isMatchWrong"><label class="ld-match__left" for="%2$s">%3$s</label><select id="%2$s" data-wp-on--change="actions.selectMatch" data-wp-bind--disabled="state.isLocked">%4$s</select><span class="ld-opt__mark" aria-hidden="true"></span></li>',
			esc_attr( wp_json_encode( array( 'opt' => $index ) ) ),
			esc_attr( $id ),
			esc_html( $answer['text'] ),
			$options
		);
	}
	return $html . '</ul>';
}

/**
 * « Image cliquable » : une grille de vrais boutons posée sur l'image.
 *
 * @param array $question Question normalisée.
 * @param array $args     Options du quiz.
 * @return string
 */
function hotspot_html( array $question, array $args ) {
	if ( ! $question['image_id'] ) {
		return '<p class="ld-q__hint">' . esc_html__( 'Image manquante pour cette question.', 'learn-drive-core' ) . '</p>';
	}

	$image = wp_get_attachment_image(
		$question['image_id'],
		'learn-drive-800',
		false,
		array(
			'class'    => 'ld-hotspot__img',
			'sizes'    => '(max-width: 860px) 100vw, 760px',
			'loading'  => $args['lcp'] ? false : 'lazy',
			'decoding' => 'async',
		)
	);

	$cells = '';
	for ( $row = 0; $row < Questions\HOTSPOT_ROWS; $row++ ) {
		for ( $col = 0; $col < Questions\HOTSPOT_COLS; $col++ ) {
			$cell   = $row * Questions\HOTSPOT_COLS + $col;
			$cells .= sprintf(
				'<button type="button" class="ld-hotspot__cell" data-wp-context=\'%1$s\' data-wp-on--click="actions.pickCell" data-wp-bind--aria-pressed="state.isChosen" data-wp-bind--disabled="state.isLocked" data-wp-class--is-chosen="state.isChosen" data-wp-class--is-solution="state.isSolution" data-wp-class--is-missed="state.isChosenWrong" aria-label="%2$s"></button>',
				esc_attr( wp_json_encode( array( 'opt' => $cell ) ) ),
				/* translators: 1: row, 2: column */
				esc_attr( sprintf( __( 'Zone ligne %1$d, colonne %2$d', 'learn-drive-core' ), $row + 1, $col + 1 ) )
			);
		}
	}

	return sprintf(
		'<p class="ld-q__hint">%1$s</p><div class="ld-hotspot">%2$s<div class="ld-hotspot__grid" role="group" aria-label="%3$s" style="grid-template-columns:repeat(%4$d,1fr);grid-template-rows:repeat(%5$d,1fr)">%6$s</div></div>',
		esc_html__( 'Touchez la zone de l’image qui répond à la question.', 'learn-drive-core' ),
		$image,
		esc_attr__( 'Zones de l’image', 'learn-drive-core' ),
		Questions\HOTSPOT_COLS,
		Questions\HOTSPOT_ROWS,
		$cells
	);
}

/**
 * Composition officielle d'une fiche d'examen (thèmes et nombre de questions),
 * quand les thèmes du pays portent un quota.
 *
 * @param int $quiz_id Examen.
 * @return string
 */
function exam_blueprint( $quiz_id ) {
	$country = wp_get_post_terms( $quiz_id, \LearnDrive\Core\TAX_COUNTRY, array( 'fields' => 'slugs' ) );
	$args    = array(
		'taxonomy'   => \LearnDrive\Core\TAX_TOPIC,
		'hide_empty' => true,
		'orderby'    => 'term_id',
		'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => 'ld_exam_quota',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'NUMERIC',
			),
		),
	);
	if ( $country && ! is_wp_error( $country ) ) {
		$args['meta_query'][] = array(
			'key'   => 'ld_country',
			'value' => $country[0],
		);
	}
	$topics = get_terms( $args );
	if ( ! $topics || is_wp_error( $topics ) ) {
		return '';
	}
	$rows  = '';
	$total = 0;
	foreach ( $topics as $topic ) {
		$quota  = (int) get_term_meta( $topic->term_id, 'ld_exam_quota', true );
		$total += $quota;
		$rows  .= sprintf( '<tr><td><a href="%s">%s</a></td><td>%s</td><td>%d</td></tr>', esc_url( get_term_link( $topic ) ), esc_html( $topic->name ), esc_html( number_format_i18n( $topic->count ) ), $quota );
	}
	return sprintf(
		'<section class="ld-exam-blueprint"><h2>%1$s</h2><p>%2$s</p><figure class="wp-block-table"><table><thead><tr><th>%3$s</th><th>%4$s</th><th>%5$s</th></tr></thead><tbody>%6$s</tbody></table></figure></section>',
		esc_html__( 'Composition de la fiche d’examen', 'learn-drive-core' ),
		/* translators: %d: number of questions */
		esc_html( sprintf( __( 'Chaque examen blanc tire au hasard %d questions selon la répartition officielle par thème : jamais deux fois la même fiche.', 'learn-drive-core' ), $total ) ),
		esc_html__( 'Thème', 'learn-drive-core' ),
		esc_html__( 'Questions dans la banque', 'learn-drive-core' ),
		esc_html__( 'Questions à l’examen', 'learn-drive-core' ),
		$rows
	);
}
