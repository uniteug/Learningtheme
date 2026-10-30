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
use LearnDrive\Core\Classes;
use LearnDrive\Core\Admin\Setup;

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

// Formats structurés : réponses en identifiants d'affichage (mélangés).
$order = array(
	'id'       => 999001,
	'format'   => 'order',
	'answers'  => array( array( 'text' => 'Un', 'correct' => false, 'match' => '' ), array( 'text' => 'Deux', 'correct' => false, 'match' => '' ), array( 'text' => 'Trois', 'correct' => false, 'match' => '' ) ),
	'hotspots' => array(),
	'numeric'  => array( 'value' => 0, 'tolerance' => 0, 'unit' => '' ),
);
$shuffled = Questions\shuffled_indexes( $order );
check( 'Ordre : affichage mélangé, jamais l’ordre juste', array( 0, 1, 2 ) !== $shuffled && count( $shuffled ) === 3 );
check( 'Ordre : la solution (identifiants d’affichage) est juste', Questions\grade( $order, Questions\solution( $order ) ) );
check( 'Ordre : l’ordre d’affichage initial est faux', ! Questions\grade( $order, array( 0, 1, 2 ) ) );
check( 'Ordre : réponse incomplète refusée', ! Questions\grade( $order, array( 0, 1 ) ) );

$match           = $order;
$match['format'] = 'match';
foreach ( $match['answers'] as $k => $a ) {
	$match['answers'][ $k ]['match'] = 'M' . $k;
}
check( 'Association : solution juste', Questions\grade( $match, Questions\solution( $match ) ) );
$wrong = Questions\solution( $match );
list( $wrong[0], $wrong[1] ) = array( $wrong[1], $wrong[0] );
check( 'Association : deux correspondances inversées = faux', ! Questions\grade( $match, $wrong ) );

$hot = array(
	'id'       => 999002,
	'format'   => 'hotspot',
	'answers'  => array(),
	'hotspots' => array( array( 'x' => 38, 'y' => 12, 'w' => 24, 'h' => 76 ) ),
	'numeric'  => array( 'value' => 0, 'tolerance' => 0, 'unit' => '' ),
);
check( 'Image cliquable : colonne centrale juste (cases 2, 7, 12, 17)', array( 2, 7, 12, 17 ) === Questions\hotspot_cells( $hot ) );
check( 'Image cliquable : case 7 juste, case 0 fausse', Questions\grade( $hot, 7 ) && ! Questions\grade( $hot, 0 ) );
check( 'Zone hors image ramenée dans l’image', 100.0 >= Questions\sanitize_zone( array( 'x' => 90, 'y' => 0, 'w' => 50, 'h' => 10 ) )['x'] + Questions\sanitize_zone( array( 'x' => 90, 'y' => 0, 'w' => 50, 'h' => 10 ) )['w'] );

/* ------------------------------------------------------------------ */
section( 'Règles d’examen par pays (aucun code par pays)' );

$fr_exam = get_page_by_path( 'examen-blanc-code-permis-b', OBJECT, 'ld_quiz' );
$it_exam = get_posts( array( 'post_type' => 'ld_quiz', 'meta_key' => '_ld_mode', 'meta_value' => 'exam', 'tax_query' => array( array( 'taxonomy' => 'ld_country', 'field' => 'slug', 'terms' => 'italie' ) ), 'posts_per_page' => 1 ) );
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
	check( 'Examen France : uniquement des QCM (pas d’ordre, d’association ni d’image cliquable)', ! array_diff( array_map( static fn( $id ) => get_post_meta( $id, '_ld_format', true ), $a ), array( 'single', 'multiple' ) ) );
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
check( 'Export JSON Lines', $count >= 125 && $count === count( file( $export ) ) );
@unlink( $export );

/* ------------------------------------------------------------------ */
section( 'Espace auto-école' );

$new_user = static function ( $role ) use ( &$cleanup ) {
	$id        = wp_insert_user( array( 'user_login' => 'ld_' . $role . '_' . wp_generate_password( 6, false ), 'user_pass' => wp_generate_password(), 'user_email' => 'ld-' . wp_generate_password( 8, false ) . '@example.com', 'display_name' => '=HYPERLINK("x")', 'role' => $role ) );
	$cleanup[] = static function () use ( $id ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $id );
	};
	return $id;
};
$coach   = $new_user( 'ld_instructor' );
$coach2  = $new_user( 'ld_instructor' );
$student = $new_user( 'subscriber' );
$other   = $new_user( 'subscriber' );

check( 'Rôle Moniteur : peut gérer des classes', user_can( $coach, Classes\CAP ) && ! user_can( $coach, 'edit_posts' ) );
check( 'Élève : ne peut pas gérer de classe', ! user_can( $student, Classes\CAP ) );

$class_id  = Classes\create( 'Classe test', $coach, 1 );
$cleanup[] = static function () use ( $class_id ) {
	wp_delete_post( $class_id, true );
};
$class = Classes\get( $class_id );
check( 'Classe créée avec un code ABCD-EFGH', (bool) preg_match( '/^[A-HJKMNP-Z2-9]{4}-[A-HJKMNP-Z2-9]{4}$/', $class['code'] ) );
check( 'Classe privée, hors sitemap et hors recherche', 'private' === get_post_status( $class_id ) && ! is_post_type_viewable( Classes\PT ) );
check( 'Seul le propriétaire (ou un administrateur) gère la classe', Classes\can_manage( $class_id, $coach ) && ! Classes\can_manage( $class_id, $coach2 ) && ! Classes\can_manage( $class_id, $student ) && Classes\can_manage( $class_id, 1 ) );
check( 'Code saisi en minuscules sans tiret accepté', $class_id === Classes\join( strtolower( str_replace( '-', ' ', $class['code'] ) ), $student ) );
check( 'Double inscription refusée', is_wp_error( Classes\join( $class['code'], $student ) ) );
check( 'Classe complète : inscription refusée', 'ld_class_full' === Classes\join( $class['code'], $other )->get_error_code() );
update_post_meta( $class_id, '_ld_class_seats', 0 );
update_post_meta( $class_id, '_ld_class_open', 0 );
check( 'Inscriptions fermées : refusée', 'ld_class_closed' === Classes\join( $class['code'], $other )->get_error_code() );
check( 'Code inconnu refusé', 'ld_class_code' === Classes\join( 'ZZZZ-ZZZZ', $other )->get_error_code() );

$quiz      = get_posts( array( 'post_type' => 'ld_quiz', 'posts_per_page' => 1, 'meta_key' => '_ld_mode', 'meta_value' => 'training' ) );
$quiz      = $quiz ? $quiz[0] : get_posts( array( 'post_type' => 'ld_quiz', 'posts_per_page' => 1 ) )[0];
$item      = Classes\assign( $class_id, $quiz->ID, wp_date( 'Y-m-d', time() + WEEK_IN_SECONDS ) );
check( 'Devoir assigné', is_string( $item ) && 1 === count( Classes\get( $class_id )['assignments'] ) );
check( 'Devoir refusé pour un contenu qui n’est pas un quiz', is_wp_error( Classes\assign( $class_id, $class_id ) ) );

$mine = Classes\for_student( $student );
check( 'L’élève voit sa classe et le devoir à faire', 1 === count( $mine ) && ! $mine[0]['assignments'][0]['done'] && ! $mine[0]['assignments'][0]['late'] );

$result = Quizzes\grade_quiz( $quiz->ID, array(), '', $student, false );
do_action( 'learn_drive_quiz_completed', $result, $student );
Progress\record_answer( $student, (int) array_key_first( $result['details'] ), true );
$mine = Classes\for_student( $student );
check( 'Quiz terminé → devoir fait', $mine[0]['assignments'][0]['done'] );

$report = Classes\report( $class_id );
check( 'Suivi : une ligne par élève, une colonne par devoir', 1 === count( $report['rows'] ) && count( $report['columns'] ) === 12 && 1 === $report['rows'][0]['cells'][4] );
$csv = Classes\report_csv( $class_id );
check( 'Export CSV : BOM, séparateur ;, formule neutralisée', str_starts_with( $csv, "\xEF\xBB\xBF" ) && str_contains( $csv, ';' ) && str_contains( $csv, "'=HYPERLINK" ) );

$export = Classes\export_personal_data( get_userdata( $student )->user_email );
check( 'Export RGPD : classe rejointe listée', 1 === count( $export['data'] ) );

wp_set_current_user( $coach2 );
$request  = new WP_REST_Request( 'DELETE', '/learn/v1/classes/' . $class_id . '/membership' );
$response = rest_do_request( $request );
check( 'API : quitter une classe dont on n’est pas membre → 404', 404 === $response->get_status() );
wp_set_current_user( $student );
$response = rest_do_request( new WP_REST_Request( 'GET', '/learn/v1/me/classes' ) );
check( 'API : /me/classes', 200 === $response->get_status() && 1 === count( $response->get_data() ) );
$response = rest_do_request( $request );
check( 'API : l’élève quitte la classe', 200 === $response->get_status() && ! Classes\classes_of( $student ) );
wp_set_current_user( 0 );
check( 'API : /me/classes refusé aux visiteurs', 401 === rest_do_request( new WP_REST_Request( 'GET', '/learn/v1/me/classes' ) )->get_status() );

update_post_meta( $class_id, '_ld_class_open', 1 );
Classes\join( $class['code'], $other );
wp_delete_post( $class_id, true );
check( 'Classe supprimée : inscriptions nettoyées', ! Classes\classes_of( $other ) );

/* ------------------------------------------------------------------ */
section( 'Assistant de démarrage' );

wp_set_current_user( 1 );
$settings_backup = get_option( 'learn_drive_core' );
$styles_id       = WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
$styles_backup   = get_post_field( 'post_content', $styles_id );
$cleanup[]       = static function () use ( $settings_backup, $styles_id, $styles_backup ) {
	update_option( 'learn_drive_core', $settings_backup );
	wp_update_post( wp_slash( array( 'ID' => $styles_id, 'post_content' => $styles_backup ) ) );
	$term = get_term_by( 'slug', 'luxembourg', 'ld_country' );
	if ( $term ) {
		wp_delete_term( $term->term_id, 'ld_country' );
	}
};

check( 'Variations du thème proposées (+ origine)', count( Setup\variations() ) >= 5 && isset( Setup\variations()['default'] ) );
$done = Setup\apply( array( 'sector' => 'school', 'country' => 'luxembourg', 'variation' => 'coach', 'access' => 'paid', 'demo' => false, 'pages' => true ) );
$opts = get_option( 'learn_drive_core' );
check( 'Secteur « soutien scolaire » : libellés et adresse des fiches', 'Niveau' === $opts['label_track'] && 'Chapitres' === $opts['label_topic_pl'] && 'fiches' === $opts['slug_sheet'] );
check( 'Pays absent créé et choisi', 'luxembourg' === $opts['default_country'] && term_exists( 'luxembourg', 'ld_country' ) );
check( 'Modèle d’accès appliqué', 'paid' === $opts['access_mode'] );
check( 'Variation « Coach » appliquée aux styles globaux', str_contains( get_post_field( 'post_content', $styles_id ), '#6D28D9' ) );
check( 'Page « Mon espace » présente', (bool) get_page_by_path( 'mon-compte' ) );
check( 'Récapitulatif rédigé', count( $done ) >= 5 && get_option( 'ld_setup_done' ) );
check( 'Réglages déjà en place conservés', $settings_backup['org_name'] === $opts['org_name'] );
Setup\apply( array( 'sector' => 'driving', 'country' => 'france', 'variation' => 'default', 'access' => 'mixed' ) );
$opts = get_option( 'learn_drive_core' );
check( 'Retour au permis : libellés et variation d’origine', 'Catégorie de permis' === $opts['label_track'] && 'panneaux' === $opts['slug_sheet'] && ! str_contains( get_post_field( 'post_content', $styles_id ), '#6D28D9' ) );
wp_set_current_user( 0 );

/* ------------------------------------------------------------------ */
section( 'Présentation et politique d’indexation' );

$opts_backup = get_option( 'learn_drive_core' );
$set_option  = static function ( array $changes ) use ( $opts_backup ) {
	update_option( 'learn_drive_core', array_merge( $opts_backup, $changes ) );
	do_action( 'update_option_learn_drive_core' );
};
$set_option( array( 'access_mode' => 'paid', 'presentation' => 'auto' ) );
check( 'Tout payant → vitrine commerciale', 'commercial' === apply_filters( 'learn_drive_presentation', 'editorial' ) );
$set_option( array( 'access_mode' => 'mixed', 'presentation' => 'auto' ) );
check( 'Freemium → portail (aucune mise en avant payante)', 'editorial' === apply_filters( 'learn_drive_presentation', 'editorial' ) );
$set_option( array( 'access_mode' => 'mixed', 'presentation' => 'commercial' ) );
check( 'Choix manuel du volet respecté', 'commercial' === apply_filters( 'learn_drive_presentation', 'editorial' ) );
$set_option( array( 'default_country' => 'italie' ) );
$links = apply_filters( 'learn_drive_links', array( 'exam' => '', 'learn' => '', 'quiz' => '', 'signs' => '', 'news' => '', 'guides' => '', 'glossary' => '' ) );
check( 'Accès rapides : examen blanc de la catégorie principale du pays', str_contains( $links['exam'], '/patente-b/examen-blanc/' ) );
update_option( 'learn_drive_core', $opts_backup );

$track_b = get_term_by( 'slug', 'patente-b', 'ld_track' );
$summary = LearnDrive\Core\Blocks\track_summary( $track_b );
check( 'Carte de programme : règles de l’examen (30 questions, 20 min, 3 erreurs)', in_array( '30 questions', $summary['facts'], true ) && in_array( '20 min', $summary['facts'], true ) && in_array( '3 erreurs max', $summary['facts'], true ) );

$short = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Brève trop courte', 'post_content' => '<!-- wp:paragraph --><p>Trois mots seulement.</p><!-- /wp:paragraph -->' ) );
$cleanup[] = static function () use ( $short ) {
	wp_delete_post( $short, true );
};
check( 'Article trop court : exclu de l’index et du sitemap', get_post_meta( $short, '_ld_noindex', true ) && get_post_meta( $short, '_ld_thin', true ) );
$long = get_page_by_path( 'convertir-permis-etranger-italie', OBJECT, 'ld_guide' );
check( 'Guide complet : indexé', $long && ! get_post_meta( $long->ID, '_ld_noindex', true ) );

$lonely_tag = wp_insert_term( 'Étiquette isolée test', 'post_tag' );
$cleanup[]  = static function () use ( $lonely_tag ) {
	wp_delete_term( $lonely_tag['term_id'], 'post_tag' );
};
check( 'Étiquette avec un seul article : non indexée', LearnDrive\Core\SEO\Index\is_thin_term( get_term( $lonely_tag['term_id'] ) ) );
check( 'Page pilier rédigée : indexée même avec peu de contenus', ! LearnDrive\Core\SEO\Index\is_thin_term( get_term_by( 'slug', 'patente-am', 'ld_track' ) ) );
$parent_cat = wp_insert_term( 'Rubrique parente test', 'category' );
$child_cat  = wp_insert_term( 'Sous-rubrique test', 'category', array( 'parent' => $parent_cat['term_id'] ) );
$child_ids  = array();
for ( $i = 0; $i < 3; $i++ ) {
	$child_ids[] = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Article rangé test ' . $i, 'post_category' => array( $child_cat['term_id'] ) ) );
}
$cleanup[] = static function () use ( $parent_cat, $child_cat, $child_ids ) {
	foreach ( $child_ids as $id ) {
		wp_delete_post( $id, true );
	}
	wp_delete_term( $child_cat['term_id'], 'category' );
	wp_delete_term( $parent_cat['term_id'], 'category' );
};
clean_term_cache( array( $parent_cat['term_id'], $child_cat['term_id'] ), 'category' );
check( 'Rubrique parente sans article direct : indexée grâce à ses sous-rubriques', ! LearnDrive\Core\SEO\Index\is_thin_term( get_term( $parent_cat['term_id'] ) ) );
$args = LearnDrive\Core\SEO\Index\sitemap_terms( array(), 'post_tag' );
check( 'Sitemap : étiquettes minces retirées', in_array( $lonely_tag['term_id'], $args['exclude'] ?? array(), true ) );

/* ------------------------------------------------------------------ */
section( 'Marchés (pays, langue, hreflang, mise en ligne)' );

use LearnDrive\Core\Markets;
use LearnDrive\Core\Permalinks;

$default_market = Markets\default_market();
$other_market   = 'france' === $default_market ? 'italie' : 'france';
$market_terms   = array(
	$default_market => get_term_by( 'slug', $default_market, 'ld_country' ),
	$other_market   => get_term_by( 'slug', $other_market, 'ld_country' ),
);
$track_b        = array( 'france' => get_term_by( 'slug', 'permis-b', 'ld_track' ), 'italie' => get_term_by( 'slug', 'patente-b', 'ld_track' ) );
$market_meta    = array();
foreach ( array_merge( array_values( $market_terms ), array_values( $track_b ) ) as $t ) {
	$market_meta[ $t->term_id ] = get_term_meta( $t->term_id );
}
$cleanup[] = static function () use ( $market_meta ) {
	foreach ( $market_meta as $id => $meta ) {
		foreach ( array( 'ld_locale', 'ld_active', 'ld_hub', 'ld_slugs', 'ld_equivalent' ) as $key ) {
			delete_term_meta( $id, $key );
			if ( isset( $meta[ $key ][0] ) ) {
				update_term_meta( $id, $key, maybe_unserialize( $meta[ $key ][0] ) );
			}
		}
	}
	delete_option( 'ld_track_slugs' );
	Markets\all( true );
	flush_rewrite_rules( false );
};

check( 'Marché principal toujours en ligne, pays sans réglage en ligne (compatibilité)', Markets\is_active( $default_market ) && Markets\is_active( $other_market ) && Markets\is_active( '' ) );

update_term_meta( $market_terms['italie']->term_id, 'ld_locale', 'it_IT' );
update_term_meta( $market_terms['italie']->term_id, 'ld_slugs', array( 'slug_question' => 'domande', 'slug_exam' => 'simulazione-esame' ) );
update_term_meta( $market_terms['france']->term_id, 'ld_locale', 'fr_FR' );
update_term_meta( $track_b['france']->term_id, 'ld_equivalent', 'b' );
update_term_meta( $track_b['italie']->term_id, 'ld_equivalent', 'b' );
Markets\all( true );
delete_option( 'ld_track_slugs' );

check( 'Segment d’URL propre au marché, segment du site sinon', 'domande' === Markets\segment( 'slug_question', 'italie' ) && LearnDrive\Core\setting( 'slug_question' ) === Markets\segment( 'slug_question', 'france' ) );
check( 'Catégorie rattachée à son marché', 'italie' === ( Permalinks\track_markets()['patente-b'] ?? '' ) && 'france' === ( Permalinks\track_markets()['permis-b'] ?? '' ) );

$it_question = get_posts( array( 'post_type' => 'ld_question', 'posts_per_page' => 1, 'tax_query' => array( array( 'taxonomy' => 'ld_track', 'field' => 'slug', 'terms' => 'patente-b' ) ) ) );
$fr_question = get_posts( array( 'post_type' => 'ld_question', 'posts_per_page' => 1, 'tax_query' => array( array( 'taxonomy' => 'ld_track', 'field' => 'slug', 'terms' => 'permis-b' ) ) ) );
check( 'Adresse d’une question italienne avec le segment italien', $it_question && str_contains( get_permalink( $it_question[0] ), '/patente-b/domande/' ) );
check( 'Adresse d’une question française inchangée', $fr_question && str_contains( get_permalink( $fr_question[0] ), '/permis-b/' . LearnDrive\Core\setting( 'slug_question' ) . '/' ) );
check( 'Marché d’une question : celui de sa catégorie', $it_question && 'italie' === Markets\of_post( $it_question[0] ) );

Permalinks\add_rules();
flush_rewrite_rules( false );
$market_rules = array_filter( array_keys( (array) get_option( 'rewrite_rules' ) ), static fn( $rule ) => str_contains( $rule, 'patente\\-b' ) && str_contains( $rule, '/domande/([^/]+)' ) );
check( 'Règles de réécriture par marché', (bool) $market_rules );

$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = new WP_Query( array( 'ld_track' => 'permis-b' ) );
$alternates = Markets\alternates();
check( 'hreflang entre catégories équivalentes (fr-FR, it-IT, x-default)', isset( $alternates['fr-FR'], $alternates['it-IT'], $alternates['x-default'] ) && str_contains( $alternates['it-IT'], '/patente-b/' ) );
check( 'Marché de la page : celui de la catégorie', 'france' === Markets\current() );

update_term_meta( $market_terms[ $other_market ]->term_id, 'ld_active', '0' );
Markets\all( true );
$alternates = Markets\alternates();
check( 'Marché en préparation : retiré des hreflang', ! isset( $alternates[ 'italie' === $other_market ? 'it-IT' : 'fr-FR' ] ) );
check( 'Marché en préparation : hors sitemap (contenus et catégories)', in_array( $other_market, ( Markets\sitemap_posts( array() )['tax_query'][0]['terms'] ?? array() ), true ) && in_array( $track_b[ $other_market ]->term_id, Markets\inactive_term_ids(), true ) );
$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = new WP_Query( array( 'ld_track' => 'italie' === $other_market ? 'patente-b' : 'permis-b' ) );
check( 'Marché en préparation : page jamais indexée', true === apply_filters( 'learn_drive_noindex', false ) );
$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = new WP_Query();

/* ------------------------------------------------------------------ */
foreach ( array_reverse( $cleanup ) as $fn ) {
	$fn();
}

printf( "\n%d réussis, %d échoués\n", $passed, count( $failed ) );
exit( $failed ? 1 : 0 );
