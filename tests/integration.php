<?php
/**
 * Tests d'intégration Learn & Drive (à lancer sur une installation de test
 * où le thème et le plugin sont actifs et la démo importée).
 *
 * Usage : php tests/integration.php /chemin/vers/wordpress
 *
 * @package LearnDriveCore
 */

// phpcs:disable

if ( empty( $argv[1] ) ) {
	fwrite( STDERR, "Usage : php tests/integration.php /chemin/vers/wordpress\n" );
	exit( 2 );
}

$_SERVER['HTTP_HOST'] = parse_url( getenv( 'LD_HOME' ) ?: 'http://127.0.0.1:8080', PHP_URL_HOST ) . ':' . ( parse_url( getenv( 'LD_HOME' ) ?: 'http://127.0.0.1:8080', PHP_URL_PORT ) ?: 80 );
require rtrim( $argv[1], '/' ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';

use LearnDrive\Core\Questions;
use LearnDrive\Core\Quizzes;
use LearnDrive\Core\Access;
use LearnDrive\Core\Progress;
use LearnDrive\Core\Import;
use LearnDrive\Core\SEO;

$passed = 0;
$failed = array();

function check( $label, $condition ) {
	global $passed, $failed;
	if ( $condition ) {
		++$passed;
		echo "  ✓ {$label}\n";
	} else {
		$failed[] = $label;
		echo "  ✗ {$label}\n";
	}
}

function section( $title ) {
	echo "\n{$title}\n";
}

$cleanup = array();

/* ------------------------------------------------------------------ */
section( 'Correction des formats de questions' );

$q = array(
	'format'  => 'multiple',
	'answers' => array(
		array( 'text' => 'A', 'correct' => true ),
		array( 'text' => 'B', 'correct' => false ),
		array( 'text' => 'C', 'correct' => true ),
	),
	'numeric' => array( 'value' => 0, 'tolerance' => 0, 'unit' => '' ),
);
check( 'Choix multiple : toutes les bonnes cases', Questions\grade( $q, array( 2, 0 ) ) );
check( 'Choix multiple : une bonne case manquante = faux', ! Questions\grade( $q, array( 0 ) ) );
check( 'Choix multiple : une case de trop = faux', ! Questions\grade( $q, array( 0, 1, 2 ) ) );

$q['format']  = 'single';
$q['answers'] = array( array( 'text' => 'A', 'correct' => false ), array( 'text' => 'B', 'correct' => true ) );
check( 'Choix unique : bonne réponse', Questions\grade( $q, 1 ) );
check( 'Choix unique : mauvaise réponse', ! Questions\grade( $q, 0 ) );
check( 'Choix unique : réponse invalide', ! Questions\grade( $q, 'abc' ) );

$q['format'] = 'tf';
check( 'Vrai / faux', Questions\grade( $q, 1 ) && ! Questions\grade( $q, 0 ) );

$q['format']  = 'numeric';
$q['numeric'] = array( 'value' => 25, 'tolerance' => 2, 'unit' => 'm' );
check( 'Numérique dans la tolérance', Questions\grade( $q, 26.5 ) );
check( 'Numérique hors tolérance', ! Questions\grade( $q, 28 ) );
check( 'Texte de solution numérique', '25 m' === Questions\solution_text( $q ) );

/* ------------------------------------------------------------------ */
section( 'Règles d’examen par pays (aucun code par pays)' );

$fr_exam = get_page_by_path( 'examen-blanc-code-permis-b', OBJECT, 'ld_quiz' );
$it_exam = get_posts( array( 'post_type' => 'ld_quiz', 'meta_key' => '_ld_mode', 'meta_value' => 'exam', 'tax_query' => array( array( 'taxonomy' => 'ld_country', 'field' => 'slug', 'terms' => 'italia' ) ), 'posts_per_page' => 1 ) );
$it_exam = $it_exam ? $it_exam[0] : null;

check( 'Examen France trouvé', (bool) $fr_exam );
check( 'Examen Italie trouvé', (bool) $it_exam );

if ( $fr_exam && $it_exam ) {
	$fr = Quizzes\rules( $fr_exam->ID );
	$it = Quizzes\rules( $it_exam->ID );
	check( 'France : 40 questions, 20 s/question, 35 bonnes réponses, retour interdit', 40 === $fr['count'] && 20 === $fr['per_question'] && 'min_correct' === $fr['pass_type'] && 35 === $fr['pass_value'] && false === $fr['allow_back'] );
	check( 'Italie : 30 questions, 20 min, 3 erreurs max, retour autorisé', 30 === $it['count'] && 1200 === $it['duration'] && 'max_errors' === $it['pass_type'] && 3 === $it['pass_value'] && true === $it['allow_back'] );
	check( 'Format Italie vrai/faux', 'tf' === $it['format'] );

	// Tirage d'examen : reproductible avec la graine, différent selon la graine, réparti par thème.
	$a = Quizzes\question_ids( $fr_exam->ID, 'seed-a' );
	$b = Quizzes\question_ids( $fr_exam->ID, 'seed-a' );
	$c = Quizzes\question_ids( $fr_exam->ID, 'seed-b' );
	check( 'Tirage : 40 questions', 40 === count( $a ) );
	check( 'Tirage reproductible pour une même session', $a === $b );
	check( 'Tirage différent pour une autre session', $a !== $c );
	check( 'Tirage sans doublon', count( array_unique( $a ) ) === count( $a ) );
	$topics = array();
	foreach ( $a as $id ) {
		$t = LearnDrive\Core\primary_term( $id, 'ld_topic' );
		$topics[ $t ? $t->slug : '' ] = true;
	}
	check( 'Tirage réparti sur les 9 thèmes officiels', count( $topics ) >= 9 );

	// Jeton de session signé.
	$token = Quizzes\create_session( $fr_exam->ID );
	check( 'Session valide', is_array( Quizzes\read_session( $token, $fr_exam->ID ) ) );
	check( 'Session refusée pour un autre quiz', null === Quizzes\read_session( $token, $it_exam->ID ) );
	check( 'Session falsifiée refusée', null === Quizzes\read_session( substr( $token, 0, -1 ) . ( '0' === substr( $token, -1 ) ? '1' : '0' ), $fr_exam->ID ) );

	// Correction complète : seuil 35/40.
	$session = Quizzes\read_session( $token, $fr_exam->ID );
	$ids     = Quizzes\question_ids( $fr_exam->ID, $session['s'] );
	$answers = array();
	foreach ( $ids as $index => $id ) {
		$question = Questions\get( $id );
		$solution = Questions\solution( $question );
		$answers[ $id ] = $index < 35 ? ( 'multiple' === $question['format'] ? $solution : ( is_array( $solution ) ? $solution[0] : $solution ) ) : -1;
	}
	$result = Quizzes\grade_quiz( $fr_exam->ID, $answers, $session['s'], 1, false );
	check( '35/40 = réussi (France)', 35 === $result['correct'] && $result['passed'] );
	$answers[ $ids[0] ] = -1;
	$result = Quizzes\grade_quiz( $fr_exam->ID, $answers, $session['s'], 1, false );
	check( '34/40 = échoué (France)', 34 === $result['correct'] && ! $result['passed'] );
	check( 'Erreurs par thème avec lien de révision', ! empty( $result['topics'] ) && '' !== $result['topics'][0]['link'] );

	$it_ids     = Quizzes\question_ids( $it_exam->ID, 'x' );
	$it_answers = array();
	foreach ( $it_ids as $index => $id ) {
		$solution = Questions\solution( Questions\get( $id ) );
		$it_answers[ $id ] = $index < 4 ? 1 - $solution[0] : $solution[0];
	}
	$it_result = Quizzes\grade_quiz( $it_exam->ID, $it_answers, 'x', 1, false );
	check( 'Italie : 4 erreurs = échoué', 4 === $it_result['errors'] && ! $it_result['passed'] );
}

/* ------------------------------------------------------------------ */
section( 'Accès gratuit / premium' );

$premium_q = get_posts( array( 'post_type' => 'ld_question', 'meta_key' => '_ld_access', 'meta_value' => 'premium', 'posts_per_page' => 1, 'fields' => 'ids' ) );
$free_q    = get_posts( array( 'post_type' => 'ld_question', 'meta_key' => '_ld_access', 'meta_value' => 'free', 'posts_per_page' => 1, 'fields' => 'ids' ) );
$user_id   = wp_insert_user( array( 'user_login' => 'ld_test_' . wp_generate_password( 6, false ), 'user_pass' => wp_generate_password(), 'user_email' => 'ld-test-' . wp_generate_password( 6, false ) . '@example.com', 'role' => 'subscriber' ) );
$cleanup[] = static function () use ( $user_id ) {
	require_once ABSPATH . 'wp-admin/includes/user.php';
	wp_delete_user( $user_id );
};

check( 'Question gratuite accessible au visiteur', Access\can_access( $free_q[0], 0 ) );
check( 'Question premium verrouillée pour un visiteur', ! Access\can_access( $premium_q[0], 0 ) );
check( 'Question premium verrouillée pour un inscrit sans offre', ! Access\can_access( $premium_q[0], $user_id ) );

$codes = Access\generate_codes( 1, 'all', 30, 1, 'Auto-école test' );
check( 'Code d’accès généré', 1 === count( $codes ) );
check( 'Code d’accès utilisé', true === Access\redeem_code( strtolower( $codes[0] ), $user_id ) );
check( 'Code d’accès à usage unique', is_wp_error( Access\redeem_code( $codes[0], $user_id ) ) );
check( 'Question premium débloquée après le code', Access\can_access( $premium_q[0], $user_id ) );
Access\revoke_source( 'code:' . $codes[0] );
check( 'Accès retiré après révocation', ! Access\can_access( $premium_q[0], $user_id ) );

// Héritage : un quiz « hériter » prend la règle de sa catégorie.
$track_c = get_term_by( 'slug', 'permis-c', 'ld_track' );
$temp    = wp_insert_post( array( 'post_type' => 'ld_quiz', 'post_status' => 'publish', 'post_title' => 'Quiz héritage test' ) );
wp_set_object_terms( $temp, array( $track_c->term_id ), 'ld_track' );
update_term_meta( $track_c->term_id, 'ld_access', 'premium' );
check( 'Catégorie premium → quiz hérité premium', 'premium' === Access\resolve_rule( $temp, 0 ) );
update_post_meta( $temp, '_ld_access', 'free' );
check( 'Exception posée à la main sur le quiz', 'free' === Access\resolve_rule( $temp, 0 ) );
delete_term_meta( $track_c->term_id, 'ld_access' );
wp_delete_post( $temp, true );

// Leçon premium : début lisible, suite verrouillée.
$lesson = get_page_by_path( 'les-limitations-de-vitesse', OBJECT, 'ld_lesson' );
check( 'Leçon premium détectée', 'premium' === Access\rule( $lesson->ID ) );

// Actualités toujours publiques, même en mode « tout payant ».
$settings                = get_option( 'learn_drive_core', array() );
$backup                  = $settings;
$settings['access_mode'] = 'paid';
update_option( 'learn_drive_core', $settings );
$news = get_posts( array( 'post_type' => 'post', 'posts_per_page' => 1, 'fields' => 'ids' ) );
check( 'Mode tout payant : actualité publique', 'free' === Access\resolve_rule( $news[0], 0 ) );
check( 'Mode tout payant : quiz premium', 'premium' === Access\resolve_rule( $fr_exam->ID, 0 ) || 'free' === get_post_meta( $fr_exam->ID, '_ld_access', true ) );
update_option( 'learn_drive_core', $backup );

/* ------------------------------------------------------------------ */
section( 'Progression et révision espacée' );

$qid  = $free_q[0];
Progress\record_answer( $user_id, $qid, false );
$data = Progress\get( $user_id );
check( 'Erreur programmée en révision à J+1', isset( $data['review'][ (string) $qid ] ) && $data['review'][ (string) $qid ]['due'] > time() );
check( 'Série de jours démarrée', 1 === $data['streak']['count'] );
$data['review'][ (string) $qid ]['due'] = time() - 1;
Progress\save( $user_id, $data );
check( 'Question due aujourd’hui', in_array( $qid, Progress\due_reviews( $user_id ), true ) );
Progress\record_answer( $user_id, $qid, true );
$data = Progress\get( $user_id );
check( 'Bonne réponse en révision : prochaine étape J+3', 1 === $data['review'][ (string) $qid ]['step'] );

/* ------------------------------------------------------------------ */
section( 'SEO : contenu mince, titres, modèles' );

$thin_id = wp_insert_post( array( 'post_type' => 'ld_question', 'post_status' => 'publish', 'post_title' => 'Question de test trop courte ?', 'post_content' => '<!-- wp:paragraph --><p>Réponse courte.</p><!-- /wp:paragraph -->' ) );
$cleanup[] = static function () use ( $thin_id ) {
	wp_delete_post( $thin_id, true );
};
update_post_meta( $thin_id, '_ld_answers', array( array( 'text' => 'Oui', 'correct' => true ), array( 'text' => 'Non', 'correct' => false ) ) );
Questions\update_seo_flags( $thin_id );
check( 'Explication < 40 mots : noindex automatique', (bool) get_post_meta( $thin_id, '_ld_noindex', true ) );

$empty_id = wp_insert_post( array( 'post_type' => 'ld_question', 'post_status' => 'publish', 'post_title' => 'Question sans explication ?', 'post_content' => '' ) );
$cleanup[] = static function () use ( $empty_id ) {
	wp_delete_post( $empty_id, true );
};
check( 'Question sans explication : publication refusée (brouillon)', 'draft' === get_post_status( $empty_id ) );

check( 'Modèle de titre sans répétition', ! str_contains( SEO\post_title( $fr_exam->ID ), 'Permis B – Permis B' ) );
check( 'Séparateurs orphelins nettoyés', 'Titre | Site' === SEO\fill_template( '{title} – {track} | {site}', array( '{title}' => 'Titre', '{track}' => '', '{site}' => 'Site' ) ) );
check( 'Description tronquée à ~158 caractères', mb_strlen( SEO\truncate( str_repeat( 'mot ', 80 ) ) ) <= 158 );

$question_url = get_permalink( $free_q[0] );
check( 'URL en silo /{catégorie}/questions/{slug}/', (bool) preg_match( '#/[a-z0-9-]+/questions/[a-z0-9-]+/$#', $question_url ) );
check( 'URL d’examen unique /{catégorie}/examen-blanc/', str_ends_with( get_permalink( $fr_exam->ID ), '/permis-b/examen-blanc/' ) );

/* ------------------------------------------------------------------ */
section( 'Import / export CSV' );

$csv = wp_tempnam( 'ld-test' ) . '.csv';
file_put_contents(
	$csv,
	"title;slug;format;answers;explanation;tracks;topic;country;difficulty;access;source\n" .
	"Question importée de test ?;question-importee-test;single;Bonne*|Mauvaise|Autre;Ceci est une explication suffisamment longue pour être indexée : elle reprend la règle, explique pourquoi la bonne réponse est correcte, et donne un exemple concret que l’apprenant pourra retenir facilement avant son examen du code de la route.;permis-b;conducteur;france;2;free;Test\n" .
	"Ligne invalide;;single;Seule;Explication;permis-b;;;1;free;\n"
);
$report = Import\import_file( $csv );
$imported = get_page_by_path( 'question-importee-test', OBJECT, 'ld_question' );
$cleanup[] = static function () use ( $imported, $csv ) {
	if ( $imported ) {
		wp_delete_post( $imported->ID, true );
	}
	@unlink( $csv );
};
check( 'Import CSV (séparateur ;) : 1 créée, 1 erreur', 1 === $report['created'] && 1 === count( $report['errors'] ) );
check( 'Question importée correcte', $imported && Questions\grade( Questions\get( $imported ), 0 ) && ! get_post_meta( $imported->ID, '_ld_noindex', true ) );
$report2 = Import\import_file( $csv );
check( 'Réimport : mise à jour sans doublon', 1 === $report2['updated'] && 0 === $report2['created'] );

$export = wp_tempnam( 'ld-export' );
$count  = Import\export_file( $export, 'jsonl' );
check( 'Export JSON Lines', $count >= 120 && $count === count( file( $export ) ) );
@unlink( $export );

/* ------------------------------------------------------------------ */
foreach ( array_reverse( $cleanup ) as $fn ) {
	$fn();
}

printf( "\n%d réussis, %d échoués\n", $passed, count( $failed ) );
exit( $failed ? 1 : 0 );
