<?php
/**
 * Contenuti e impostazioni di noigui.com (in italiano).
 *
 * @package NoiguiSetup
 */

namespace Noigui\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * Impostazioni di Learn & Drive per noigui.com.
 *
 * @return array
 */
function settings() {
	return array(
		'access_mode'     => 'free',
		'presentation'    => 'editorial',
		'default_country' => 'italia',
		'home_eyebrow'    => 'Patente B · Quiz ministeriali · Simulazione d’esame',
		'home_title'      => 'Quiz patente B con il listato ministeriale, spiegati bene',
		'home_lead'       => 'Le 7.020 affermazioni ufficiali del Ministero divise nei 25 argomenti, la simulazione d’esame come in Motorizzazione (30 domande vero/falso, 20 minuti, massimo 3 errori) e guide chiare per arrivare preparato.',
		'org_name'        => 'Noigui',
		'news_name'       => 'Noigui',
		'account_url'     => '/la-mia-area/',
		'app_android_url' => 'https://play.google.com/store/apps/details?id=com.noigui',
		'app_ios_url'     => 'https://apps.apple.com/it/app/noigui-patente-guida-insieme/id6779575620',
		'label_track'     => 'Categoria di patente',
		'label_track_pl'  => 'Categorie di patente',
		'label_topic'     => 'Argomento',
		'label_topic_pl'  => 'Argomenti',
		'label_sheet'     => 'Segnale',
		'label_sheet_pl'  => 'Segnali',
		'slug_lesson'     => 'lezioni',
		'slug_course'     => 'corsi',
		'slug_quiz'       => 'quiz',
		'slug_question'   => 'domande',
		'slug_exam'       => 'simulazione-esame',
		'slug_sheet'      => 'segnali',
		'slug_guide'      => 'pratiche',
		'slug_glossary'   => 'glossario',
		'slug_track'      => 'categoria',
		'slug_topic'      => 'argomento',
		'slug_family'     => 'famiglia',
		'slug_country'    => 'paese',
		'offline'         => 1,
		'index_policy'    => 1,
	);
}

/**
 * Paragrafo, titolo e FAQ in markup a blocchi.
 *
 * @param string $text Testo.
 * @return string
 */
function p( $text ) {
	return "<!-- wp:paragraph -->\n<p>" . $text . "</p>\n<!-- /wp:paragraph -->\n\n";
}

/**
 * Titolo H2.
 *
 * @param string $text Testo.
 * @return string
 */
function h2( $text ) {
	return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . $text . "</h2>\n<!-- /wp:heading -->\n\n";
}

/**
 * Elenco puntato.
 *
 * @param string[] $items Voci.
 * @return string
 */
function ul( array $items ) {
	$html = '';
	foreach ( $items as $item ) {
		$html .= "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->";
	}
	return "<!-- wp:list -->\n<ul class=\"wp-block-list\">" . $html . "</ul>\n<!-- /wp:list -->\n\n";
}

/**
 * Domanda frequente.
 *
 * @param string $question Domanda.
 * @param string $answer   Risposta.
 * @return string
 */
function faq( $question, $answer ) {
	return "<!-- wp:details {\"className\":\"is-style-faq\"} -->\n<details class=\"wp-block-details is-style-faq\"><summary>" . $question . "</summary><!-- wp:paragraph -->\n<p>" . $answer . "</p>\n<!-- /wp:paragraph --></details>\n<!-- /wp:details -->\n\n";
}

/**
 * Categorie di patente (pagine pilastro).
 *
 * @return array[]
 */
function tracks() {
	$b = p( 'La patente B permette di guidare autovetture e veicoli con massa massima autorizzata fino a 3,5 tonnellate. Il percorso si compone di due esami: la teoria, con le domande vero/falso del listato ministeriale, e la prova pratica di guida con un esaminatore della Motorizzazione civile.' )
		. h2( 'Requisiti per iniziare' )
		. ul(
			array(
				'Età minima: 18 anni (dai 17 anni è possibile la guida accompagnata, con condizioni precise).',
				'Certificato medico che attesti i requisiti psicofisici.',
				'Domanda alla Motorizzazione, direttamente o tramite un’autoscuola.',
			)
		)
		. h2( 'L’esame di teoria' )
		. p( 'L’esame si svolge al computer: 30 affermazioni a cui rispondere vero o falso in 20 minuti, con un massimo di 3 errori. Le domande vengono estratte dal listato ministeriale, diviso in 25 argomenti: ogni scheda d’esame contiene un numero fisso di domande per argomento, per esempio due sui segnali di pericolo e una sul sorpasso. È possibile sostenere la teoria anche in francese o in tedesco, scegliendo la lingua al momento della prenotazione.' )
		. p( 'La teoria va superata entro sei mesi dalla domanda, con un massimo di due tentativi. La nostra simulazione d’esame applica le stesse regole: stessa scheda, stesso tempo, stessa soglia.' )
		. h2( 'Foglio rosa e prova pratica' )
		. p( 'Superata la teoria si ottiene il foglio rosa, l’autorizzazione a esercitarsi alla guida, valido 12 mesi. Prima della prova pratica servono le guide obbligatorie in autoscuola previste dalle norme in vigore. La prova pratica comprende i controlli sul veicolo, almeno due manovre e la guida nel traffico per non meno di 25 minuti.' )
		. h2( 'Dopo la patente' )
		. p( 'Per i primi tre anni si è neopatentati: limiti di potenza del veicolo, 100 km/h in autostrada, 90 km/h sulle strade extraurbane principali e tasso alcolemico zero.' )
		. h2( 'Domande frequenti' )
		. faq( 'Quante domande ci sono all’esame di teoria della patente B?', '30 affermazioni vero/falso da completare in 20 minuti: l’esame è superato con al massimo 3 errori.' )
		. faq( 'Le domande della simulazione sono quelle ufficiali?', 'Sì: usiamo il listato ministeriale «Patente AB» pubblicato dal Ministero delle Infrastrutture e dei Trasporti, con le stesse quote per argomento della scheda d’esame.' )
		. faq( 'Quanto dura il foglio rosa?', '12 mesi dal rilascio, dopo il superamento della teoria.' );

	$a = p( 'Con la patente A si guidano i motocicli. Esistono tre livelli, con età minime diverse: A1 per le moto leggere, A2 per le moto di media potenza e A per tutte le moto. L’esame di teoria usa lo stesso listato ministeriale della patente B.' )
		. h2( 'A1, A2 e A: età e veicoli' )
		. ul(
			array(
				'<strong>A1</strong>, dai 16 anni: motocicli fino a 125 cm³ e 11 kW.',
				'<strong>A2</strong>, dai 18 anni: motocicli fino a 35 kW.',
				'<strong>A</strong>, dai 24 anni, oppure dai 20 anni con almeno due anni di patente A2.',
			)
		)
		. p( 'Con la patente B si possono guidare, solo in Italia, i motocicli fino a 125 cm³ e 11 kW.' )
		. h2( 'Esame di teoria e prova pratica' )
		. p( 'La teoria è identica a quella della patente B: 30 affermazioni vero/falso in 20 minuti, massimo 3 errori, estratte dal listato «Patente AB». La prova pratica si svolge in due fasi: manovre in area chiusa e guida nel traffico.' )
		. h2( 'Domande frequenti' )
		. faq( 'Il quiz della patente A è diverso da quello della B?', 'No: il listato ministeriale è lo stesso («Patente AB»), con la stessa scheda d’esame.' )
		. faq( 'Ho la patente B: posso guidare una moto 125?', 'Sì, ma solo in Italia, entro 125 cm³ e 11 kW.' );

	return array(
		array(
			'slug'        => 'patente-b',
			'name'        => 'Patente B',
			'description' => 'La patente per l’auto: esame di teoria con le domande vero/falso del listato ministeriale, foglio rosa e prova pratica.',
			'color'       => '#0A6E9E',
			'min_age'     => '18',
			'content'     => $b,
		),
		array(
			'slug'        => 'patente-a',
			'name'        => 'Patente A (moto)',
			'description' => 'A1, A2 e A: età minime, moto ammesse ed esame di teoria con lo stesso listato della patente B.',
			'color'       => '#6D28D9',
			'min_age'     => '16',
			'content'     => $a,
		),
	);
}

/**
 * Simulazione d'esame e quiz per argomento.
 *
 * @return array[]
 */
function quizzes() {
	$list = array(
		array(
			'slug'    => 'simulazione-esame-patente-b',
			'title'   => 'Simulazione d’esame patente B',
			'excerpt' => '30 affermazioni vero/falso estratte dal listato ministeriale con le quote ufficiali per argomento, 20 minuti, massimo 3 errori.',
			'content' => p( 'Questa simulazione riproduce l’esame di teoria della patente B in Motorizzazione: 30 affermazioni vero/falso estratte dal listato ministeriale con le stesse quote per argomento della scheda ufficiale, 20 minuti di tempo, promosso con al massimo 3 errori. Ogni simulazione è diversa dalla precedente.' )
				. h2( 'Domande frequenti' )
				. faq( 'Posso tornare alle domande precedenti?', 'Sì, come all’esame: puoi rivedere e cambiare le risposte fino alla consegna o allo scadere del tempo.' )
				. faq( 'Quando sono pronto per l’esame?', 'Quando superi con regolarità più simulazioni di fila con al massimo uno o due errori, su argomenti sempre diversi.' ),
			'mode'    => 'exam',
			'count'   => 30,
			'topic'   => '',
			'order'   => 0,
		),
	);
	foreach ( meta()['topics'] ?? array() as $index => $topic ) {
		$list[] = array(
			'slug'    => 'quiz-' . $topic['slug'],
			'title'   => 'Quiz ' . $topic['name'],
			/* La descrizione completa dell'argomento viene dal listato ministeriale. */
			'excerpt' => 'Le affermazioni ministeriali sull’argomento «' . $topic['name'] . '», con correzione immediata.',
			'content' => p( 'Allenati sull’argomento «' . esc_html( $topic['description'] ) . '» con le affermazioni vero/falso del listato ministeriale. All’esame questo argomento vale ' . ( 1 === (int) $topic['quota'] ? 'una domanda' : (int) $topic['quota'] . ' domande' ) . ' su 30.' ),
			'mode'    => 'thematic',
			'count'   => 20,
			'topic'   => $topic['slug'],
			'order'   => $index + 1,
		);
	}
	return $list;
}
