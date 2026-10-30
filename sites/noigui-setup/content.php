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
 * Elenco di link «Approfondisci» verso le guide già pubblicate su Noigui.
 *
 * @param array<string,string> $links Titolo => indirizzo relativo.
 * @return string
 */
function read_more( array $links ) {
	$items = array();
	foreach ( $links as $title => $path ) {
		$items[] = '<a href="' . esc_url( home_url( $path ) ) . '">' . $title . '</a>';
	}
	return h2( 'Approfondisci' ) . ul( $items );
}

/**
 * Nota sulle fonti in fondo alle pagine pilastro.
 *
 * @return string
 */
function sources_note() {
	return p( '<em>Regole aggiornate a ottobre 2026 secondo il Codice della strada (artt. 115-126) e le circolari del Ministero delle Infrastrutture e dei Trasporti. Le norme cambiano: prima di iscriverti verifica sul <a href="https://www.ilportaledellautomobilista.it/" target="_blank" rel="noopener">Portale dell’Automobilista</a> o presso la tua autoscuola.</em>' );
}

/**
 * Regole comuni di teoria e foglio rosa.
 *
 * @return string
 */
function common_rules() {
	return p( 'La teoria va superata entro sei mesi dalla domanda, con al massimo due tentativi. Superata la teoria si ottiene il foglio rosa, valido 12 mesi, con cui ci si esercita alla guida e si possono sostenere fino a tre prove pratiche.' );
}

/**
 * Categorie di patente (pagine pilastro), nell'ordine del menu.
 *
 * @return array[]
 */
function tracks() {
	$validity_ab = h2( 'Validità e rinnovo' ) . p( 'La patente vale 10 anni; dopo i 50 anni va rinnovata ogni 5 anni, dopo i 70 ogni 3 e dopo gli 80 ogni 2, con la visita medica.' );

	$am = p( 'La patente AM è la prima patente che si può prendere in Italia: a 14 anni permette di guidare ciclomotori e quadricicli leggeri, i veicoli che non superano i 45 km/h. Ha un esame di teoria con un proprio listato ministeriale e una prova pratica obbligatoria.' )
		. h2( 'Cosa si può guidare' )
		. ul(
			array(
				'Ciclomotori a due e tre ruote con velocità massima di 45 km/h: fino a 50 cm³ se a motore termico, fino a 4 kW se elettrici.',
				'Quadricicli leggeri (minicar) con velocità massima di 45 km/h.',
				'Il passeggero è consentito solo se il veicolo è omologato per due persone e il conducente ha almeno 16 anni.',
				'I ciclomotori non possono circolare in autostrada né sulle strade extraurbane principali.',
			)
		)
		. h2( 'Requisiti' )
		. ul(
			array(
				'Età minima: 14 anni.',
				'Per i minorenni la domanda è firmata da un genitore o da chi ne fa le veci.',
				'Certificato medico che attesti i requisiti psicofisici.',
			)
		)
		. h2( 'L’esame di teoria' )
		. p( 'Il quiz della AM è composto da 30 affermazioni vero/falso con un massimo di 3 errori, estratte da un listato ministeriale dedicato, diverso da quello delle patenti A e B. Chi ha la AM non è esonerato dalla teoria della patente B: sono due esami distinti.' )
		. common_rules()
		. h2( 'La prova pratica' )
		. p( 'Dal 2013 la prova pratica è obbligatoria anche per la AM. Si svolge in due fasi: esercizi di equilibrio in area chiusa (slalom tra i coni, otto) e guida nel traffico. Mettere un piede a terra o abbattere un cono è penalizzato.' )
		. $validity_ab
		. read_more(
			array(
				'Patente AM e ciclomotori 50: come funziona il patentino' => '/patente-am-ciclomotori-patentino/',
				'Patente A1, A2 e A: differenze, età e cosa si può guidare' => '/patente-a1-a2-a-differenze/',
			)
		)
		. h2( 'Domande frequenti' )
		. faq( 'A che età si prende la patente AM?', 'A 14 anni compiuti. Per i minorenni la domanda è firmata da un genitore.' )
		. faq( 'Il quiz della AM è lo stesso della patente B?', 'No: la AM ha un proprio listato ministeriale. Il formato è vero/falso, con un massimo di 3 errori su 30 affermazioni.' )
		. faq( 'Con la AM posso portare un passeggero?', 'Solo se il ciclomotore è omologato per due persone e tu hai almeno 16 anni.' )
		. sources_note();

	$a = p( 'Le patenti per le moto sono tre, con età e potenze crescenti: A1, A2 e A. L’esame di teoria è lo stesso della patente B (listato «Patente AB»), la prova pratica si svolge sulla moto della categoria richiesta.' )
		. h2( 'A1, A2 e A: età e moto ammesse' )
		. ul(
			array(
				'<strong>A1</strong>, dai 16 anni: motocicli fino a 125 cm³, 11 kW e 0,1 kW/kg; tricicli fino a 15 kW.',
				'<strong>A2</strong>, dai 18 anni: motocicli fino a 35 kW e 0,2 kW/kg, non derivati da un modello di oltre 70 kW.',
				'<strong>A</strong>, dai 24 anni con accesso diretto, oppure dai 20 anni con la A2 posseduta da almeno due anni: tutte le moto, senza limiti di potenza.',
			)
		)
		. p( 'Con la patente B si possono guidare, solo in Italia, i motocicli fino a 125 cm³ e 11 kW.' )
		. h2( 'Accesso graduale' )
		. p( 'Chi ha la A1 conseguita con esame da almeno due anni può passare alla A2 a 18 anni, e chi ha la A2 da almeno due anni può passare alla A a 20 anni, frequentando un corso di 7 ore in autoscuola invece dell’esame pratico (DM 9 giugno 2023). Il passaggio diretto dalla A1 alla A non è previsto.' )
		. h2( 'L’esame di teoria' )
		. p( 'Il quiz è identico a quello della patente B: 30 affermazioni vero/falso in 20 minuti, con un massimo di 3 errori. Chi ha già la patente B, B1 o BE, o una patente per moto, è esonerato dalla teoria per la A2 e la A. Per allenarti usa la <a href="' . esc_url( home_url( '/patente-b/simulazione-esame/' ) ) . '">simulazione d’esame</a> e i quiz per argomento: il listato è lo stesso.' )
		. common_rules()
		. h2( 'La prova pratica' )
		. p( 'Si svolge in tre fasi: verifiche preliminari sulla moto, esercizi in area chiusa (slalom, otto, frenata) e guida nel traffico per almeno 25 minuti.' )
		. $validity_ab
		. read_more(
			array(
				'Patente A1, A2 e A: differenze, età e cosa si può guidare' => '/patente-a1-a2-a-differenze/',
				'Patente A2: cosa posso guidare? Potenza, limiti e moto ammesse' => '/patente-a2-cosa-posso-guidare/',
				'Esame patente A2: come si svolge, dai coni alla guida nel traffico' => '/esame-patente-a2-come-si-svolge/',
				'Patente A2 senza esame: quando è possibile e come funziona il corso' => '/patente-a2-senza-esame/',
				'Patente A2, costo reale: quanto si spende voce per voce' => '/patente-a2-costo/',
				'Si può guidare una moto o scooter 125 con la patente B?' => '/guidare-125-con-patente-b/',
			)
		)
		. h2( 'Domande frequenti' )
		. faq( 'Il quiz della patente A è diverso da quello della B?', 'No: il listato ministeriale è lo stesso («Patente AB»), con lo stesso formato d’esame.' )
		. faq( 'Ho la patente B: posso guidare una moto 125?', 'Sì, ma solo in Italia, entro 125 cm³ e 11 kW.' )
		. faq( 'A che età si prende la patente A?', 'A 24 anni con accesso diretto, oppure a 20 anni se hai la A2 da almeno due anni.' )
		. sources_note();

	$b = p( 'La patente B permette di guidare autovetture e veicoli con massa massima autorizzata fino a 3,5 tonnellate. Il percorso si compone di due esami: la teoria, con le domande vero/falso del listato ministeriale, e la prova pratica di guida con un esaminatore della Motorizzazione civile.' )
		. h2( 'Requisiti per iniziare' )
		. ul(
			array(
				'Età minima: 18 anni.',
				'Certificato medico che attesti i requisiti psicofisici.',
				'Domanda alla Motorizzazione, direttamente o tramite un’autoscuola.',
			)
		)
		. h2( 'L’esame di teoria' )
		. p( 'L’esame si svolge al computer: 30 affermazioni a cui rispondere vero o falso in 20 minuti, con un massimo di 3 errori. Le domande vengono estratte dal listato ministeriale, diviso in 25 argomenti: ogni scheda d’esame contiene un numero fisso di domande per argomento, per esempio due sui segnali di pericolo e una sul sorpasso.' )
		. common_rules()
		. p( 'La nostra simulazione d’esame applica le stesse regole: stessa scheda, stesso tempo, stessa soglia.' )
		. h2( 'Foglio rosa e prova pratica' )
		. p( 'Prima della prova pratica servono le guide obbligatorie in autoscuola previste dalle norme in vigore. La prova pratica comprende i controlli sul veicolo, le manovre e la guida nel traffico per non meno di 25 minuti.' )
		. h2( 'Dopo la patente' )
		. p( 'Per i primi tre anni si è neopatentati: limiti di potenza del veicolo, 100 km/h in autostrada, 90 km/h sulle strade extraurbane principali e tasso alcolemico zero.' )
		. $validity_ab
		. h2( 'Domande frequenti' )
		. faq( 'Quante domande ci sono all’esame di teoria della patente B?', '30 affermazioni vero/falso da completare in 20 minuti: l’esame è superato con al massimo 3 errori.' )
		. faq( 'Le domande della simulazione sono quelle ufficiali?', 'Sì: usiamo il listato ministeriale «Patente AB» pubblicato dal Ministero delle Infrastrutture e dei Trasporti, con le stesse quote per argomento della scheda d’esame.' )
		. faq( 'Quanto dura il foglio rosa?', '12 mesi, durante i quali si possono sostenere fino a tre prove pratiche.' )
		. sources_note();

	$be = p( 'La patente BE serve per trainare con un’auto un rimorchio pesante, oltre i limiti della patente B. In alternativa, per complessi più leggeri, basta il codice 96 annotato sulla patente B. Nessuna delle due richiede un esame di teoria.' )
		. h2( 'Cosa si può guidare' )
		. ul(
			array(
				'<strong>Patente B</strong>: auto con rimorchio fino a 750 kg, oppure rimorchio più pesante se il complesso non supera 3.500 kg.',
				'<strong>Codice 96</strong>: auto con rimorchio oltre 750 kg e complesso oltre 3.500 kg fino a 4.250 kg. È un codice europeo, valido in tutta l’Unione.',
				'<strong>Patente BE</strong>: auto di categoria B con rimorchio fino a 3.500 kg di massa massima autorizzata.',
			)
		)
		. h2( 'Requisiti ed esame' )
		. ul(
			array(
				'Età minima: 18 anni.',
				'Serve la patente B, che si può conseguire anche contestualmente.',
				'Nessun esame di teoria: solo la prova pratica con il complesso, compresi aggancio e sgancio del rimorchio.',
			)
		)
		. $validity_ab
		. read_more(
			array(
				'Patente B96 e patente BE: quando servono per rimorchio e caravan' => '/patente-b96-be-rimorchio/',
			)
		)
		. h2( 'Domande frequenti' )
		. faq( 'Per la patente BE c’è il quiz?', 'No: la BE si consegue con la sola prova pratica, con auto e rimorchio.' )
		. faq( 'Che differenza c’è tra codice 96 e patente BE?', 'Il codice 96 copre complessi fino a 4.250 kg; la BE permette rimorchi fino a 3.500 kg, quindi complessi più pesanti.' )
		. sources_note();

	$c = p( 'Le patenti C1 e C servono per guidare autocarri oltre 3,5 tonnellate; la CE per autotreni e autoarticolati. Si conseguono partendo dalla patente B e, per lavorare come autista, serve anche la CQC merci.' )
		. h2( 'C1, C e CE: cosa si può guidare' )
		. ul(
			array(
				'<strong>C1</strong>, dai 18 anni: autocarri oltre 3.500 kg e fino a 7.500 kg, con non più di 8 passeggeri oltre al conducente; rimorchio fino a 750 kg.',
				'<strong>C</strong>, dai 21 anni (18 con la CQC merci conseguita con il corso ordinario): autocarri oltre 3.500 kg senza limite di massa; rimorchio fino a 750 kg.',
				'<strong>CE</strong>: autotreni e autoarticolati, cioè motrice di categoria C con rimorchio o semirimorchio oltre 750 kg. Serve la patente C.',
			)
		)
		. h2( 'L’esame di teoria' )
		. p( 'L’esame si svolge al computer con affermazioni vero/falso tratte dal listato ministeriale delle patenti superiori:' )
		. ul(
			array(
				'Patente C: 40 quesiti in 40 minuti, massimo 4 errori.',
				'Patente C1: 20 quesiti in 20 minuti, massimo 2 errori.',
				'Da C1 a C: 20 quesiti in 20 minuti, massimo 2 errori.',
				'C1 con codice 97 (uso non professionale): 10 quesiti in 20 minuti, massimo 1 errore.',
			)
		)
		. common_rules()
		. h2( 'La prova pratica' )
		. p( 'Si svolge in tre fasi: verifiche preliminari sul veicolo, manovre in area riservata e guida nel traffico per almeno 45 minuti. Per la CE la prova si svolge con autotreno o autoarticolato e comprende aggancio e sgancio.' )
		. h2( 'Validità e rinnovo' )
		. p( 'Le patenti C1, C e CE vanno rinnovate ogni 5 anni fino a 65 anni, poi ogni 2. Per guidare autotreni e autoarticolati oltre 20 tonnellate il limite è 65 anni, elevabile a 68 con un attestato medico annuale.' )
		. h2( 'La CQC merci' )
		. p( 'Per trasportare merci per lavoro serve la <a href="' . esc_url( home_url( '/cqc/' ) ) . '">Carta di qualificazione del conducente (CQC) merci</a>, che si aggiunge alla patente con un corso e un esame dedicati.' )
		. read_more(
			array(
				'Patente C: cosa si può guidare, età minima e requisiti' => '/patente-c-cosa-si-guida-requisiti/',
				'Esame di teoria della patente C: cosa studiare, materia per materia' => '/esame-teoria-patente-c-cosa-studiare/',
				'Esame patente C: come si svolge, dai quiz alla prova sul camion' => '/esame-patente-c-come-si-svolge/',
				'È difficile prendere la patente C? Cosa è più duro della B' => '/patente-c-e-difficile/',
				'Quanto costa la patente C: voci di spesa, preventivi e aiuti' => '/patente-c-costo/',
			)
		)
		. h2( 'Domande frequenti' )
		. faq( 'Quante domande ci sono al quiz della patente C?', '40 quesiti vero/falso in 40 minuti, con un massimo di 4 errori. Per la sola C1 sono 20 quesiti in 20 minuti, con 2 errori.' )
		. faq( 'A che età si prende la patente C?', 'A 21 anni, oppure a 18 con la CQC merci conseguita con il corso ordinario. La C1 si prende a 18 anni.' )
		. faq( 'Serve la patente B per la patente C?', 'Sì: la patente B è il requisito di partenza per C1 e C.' )
		. sources_note();

	$d = p( 'Le patenti D1 e D servono per guidare minibus e autobus, la DE per gli autobus con rimorchio pesante. Si conseguono partendo dalla patente B (la C non è richiesta) e, per trasportare persone per lavoro, serve anche la CQC persone.' )
		. h2( 'D1, D e DE: cosa si può guidare' )
		. ul(
			array(
				'<strong>D1</strong>, dai 21 anni (18 con la CQC persone conseguita con il corso ordinario): veicoli fino a 16 passeggeri oltre al conducente e lunghi non più di 8 metri; rimorchio fino a 750 kg.',
				'<strong>D</strong>, dai 24 anni: autobus per più di 8 passeggeri, senza limite di posti; rimorchio fino a 750 kg. Con la CQC persone le età minime si abbassano, fino a 18 o 20 anni a seconda del corso e del tipo di servizio.',
				'<strong>DE</strong>: autobus di categoria D con rimorchio oltre 750 kg. Serve la patente D.',
			)
		)
		. h2( 'L’esame di teoria' )
		. p( 'Affermazioni vero/falso tratte dal listato ministeriale delle patenti superiori:' )
		. ul(
			array(
				'Patente D: 40 quesiti in 40 minuti, massimo 4 errori.',
				'Patente D1: 20 quesiti in 20 minuti, massimo 2 errori.',
				'Da D1 a D: 20 quesiti in 20 minuti, massimo 2 errori. Avere la patente C non dà riduzioni.',
			)
		)
		. common_rules()
		. h2( 'La prova pratica' )
		. p( 'Tre fasi: verifiche preliminari (comprese uscite di emergenza, estintori e cronotachigrafo), manovre e guida nel traffico per almeno 45 minuti.' )
		. h2( 'Validità e rinnovo' )
		. p( 'Le patenti D1, D e DE vanno rinnovate ogni 5 anni, ogni 3 dopo i 70 anni. Per guidare veicoli che trasportano persone il limite è 60 anni, elevabile a 68 con un attestato annuale della commissione medica locale.' )
		. h2( 'La CQC persone' )
		. p( 'Per il trasporto professionale di persone serve la <a href="' . esc_url( home_url( '/cqc/' ) ) . '">CQC persone</a>, annotata sulla patente con il codice 95.' )
		. read_more(
			array(
				'Patente D: età minima, requisiti e cosa si può guidare' => '/patente-d-eta-requisiti/',
				'Quiz patente D: come funzionano, materie e come prepararsi' => '/quiz-patente-d-come-funzionano/',
				'Esame patente D: teoria e prova pratica sull’autobus, fase per fase' => '/esame-patente-d-teoria-pratica/',
				'Patente D, costo voce per voce: quanto si spende e chi la finanzia' => '/patente-d-costo/',
				'Patente D1: cosa si guida, a chi serve e quando ci vuole la CQC' => '/patente-d1-cosa-si-guida/',
			)
		)
		. h2( 'Domande frequenti' )
		. faq( 'Per la patente D serve la patente C?', 'No: basta la patente B. Chi ha la C non ha riduzioni all’esame della D.' )
		. faq( 'Quante domande ci sono al quiz della patente D?', '40 quesiti vero/falso in 40 minuti, con un massimo di 4 errori. Per la D1 sono 20 quesiti in 20 minuti, con 2 errori.' )
		. sources_note();

	$cqc = p( 'La Carta di qualificazione del conducente (CQC) non è una patente: è la qualificazione professionale obbligatoria per chi trasporta merci (CQC merci, con le patenti C1, C1E, C, CE) o persone (CQC persone, con le patenti D1, D1E, D, DE) per lavoro. Sulla patente è annotata con il codice 95.' )
		. h2( 'Il corso di qualificazione iniziale' )
		. p( 'Per sostenere l’esame bisogna frequentare un corso in autoscuola o in un centro autorizzato: ordinario di 280 ore oppure accelerato di 140 ore. La CQC conseguita con il corso ordinario permette di guidare camion e autobus a un’età più bassa (vedi le pagine delle <a href="' . esc_url( home_url( '/patente-c/' ) ) . '">patenti C</a> e <a href="' . esc_url( home_url( '/patente-d/' ) ) . '">D</a>).' )
		. h2( 'L’esame' )
		. ul(
			array(
				'Esame completo: 70 quesiti (40 sulla parte comune e 30 sulla parte specialistica merci o persone) in 90 minuti, con un massimo di 7 errori.',
				'Estensione da merci a persone o viceversa: solo la parte specialistica, 30 quesiti in 40 minuti, con un massimo di 3 errori.',
			)
		)
		. h2( 'Validità e rinnovo' )
		. p( 'La CQC vale 5 anni. Si rinnova frequentando un corso di formazione periodica di 35 ore, senza esame finale.' )
		. read_more(
			array(
				'Patente C: cosa si può guidare, età minima e requisiti' => '/patente-c-cosa-si-guida-requisiti/',
				'Patente D: età minima, requisiti e cosa si può guidare' => '/patente-d-eta-requisiti/',
				'Patente D1: cosa si guida, a chi serve e quando ci vuole la CQC' => '/patente-d1-cosa-si-guida/',
			)
		)
		. h2( 'Domande frequenti' )
		. faq( 'Quante domande ci sono all’esame CQC?', '70 quesiti in 90 minuti, con un massimo di 7 errori: 40 sulla parte comune e 30 sulla parte specialistica.' )
		. faq( 'Ogni quanto si rinnova la CQC?', 'Ogni 5 anni, con un corso di formazione periodica di 35 ore, senza esame.' )
		. sources_note();

	$kb = p( 'Il Certificato di abilitazione professionale (CAP) serve per guidare taxi e veicoli a noleggio con conducente (NCC): il KA con le moto, il KB con le auto. Si consegue con un esame di teoria presso la Motorizzazione.' )
		. h2( 'KA e KB' )
		. ul(
			array(
				'<strong>KA</strong>: si affianca alle patenti A1, A2 e A, per i servizi di taxi e noleggio con conducente in moto.',
				'<strong>KB</strong>: si affianca alle patenti B1 e B, per taxi e noleggio con conducente in auto.',
			)
		)
		. h2( 'Requisiti ed esame' )
		. ul(
			array(
				'Età minima: 21 anni.',
				'Patente della categoria corrispondente e residenza in Italia.',
				'Esame di teoria: 20 domande a scelta multipla (tre risposte, una sola corretta) in 30 minuti, con un massimo di 2 errori. Non c’è prova pratica.',
			)
		)
		. p( 'Per lavorare come tassista o autista NCC serve anche l’iscrizione al ruolo dei conducenti, con un esame distinto presso la Camera di commercio o la Regione.' )
		. h2( 'Domande frequenti' )
		. faq( 'Come si svolge l’esame per il KB?', '20 domande a scelta multipla in 30 minuti, con un massimo di 2 errori. Non c’è prova pratica.' )
		. faq( 'A che età si può prendere il KB?', 'A 21 anni, con la patente B.' )
		. sources_note();

	return array(
		array(
			'slug'        => 'patente-am',
			'name'        => 'Patente AM',
			'short'       => 'Ciclomotori e minicar, dai 14 anni',
			'description' => 'La patente AM per ciclomotori e quadricicli leggeri: si prende a 14 anni, con un quiz dedicato e una prova pratica.',
			'color'       => '#0F766E',
			'min_age'     => '14',
			'order'       => 10,
			'equivalent'  => 'am',
			'content'     => $am,
		),
		array(
			'slug'        => 'patente-a',
			'name'        => 'Patente A, A1 e A2',
			'short'       => 'Moto: A1 a 16, A2 a 18, A a 24 anni',
			'description' => 'A1, A2 e A: età minime, moto ammesse, accesso graduale ed esame di teoria con lo stesso listato della patente B.',
			'color'       => '#6D28D9',
			'min_age'     => '16',
			'order'       => 20,
			'equivalent'  => 'a',
			'content'     => $a,
		),
		array(
			'slug'        => 'patente-b',
			'name'        => 'Patente B',
			'short'       => 'Auto fino a 3,5 t, dai 18 anni',
			'description' => 'La patente per l’auto: esame di teoria con le domande vero/falso del listato ministeriale, foglio rosa e prova pratica.',
			'color'       => '#0A6E9E',
			'min_age'     => '18',
			'order'       => 30,
			'equivalent'  => 'b',
			'content'     => $b,
		),
		array(
			'slug'        => 'patente-be',
			'name'        => 'Patente BE e codice 96',
			'short'       => 'Auto con rimorchio pesante',
			'description' => 'Patente BE e codice 96: quando servono per rimorchi e caravan, senza esame di teoria.',
			'color'       => '#0369A1',
			'min_age'     => '18',
			'order'       => 40,
			'equivalent'  => 'be',
			'content'     => $be,
		),
		array(
			'slug'        => 'patente-c',
			'name'        => 'Patente C, C1 e CE',
			'short'       => 'Camion, autotreni e autoarticolati',
			'description' => 'Patenti C1, C e CE per autocarri e autotreni: età, quiz delle patenti superiori, prova pratica e CQC merci.',
			'color'       => '#B45309',
			'min_age'     => '18',
			'order'       => 50,
			'equivalent'  => 'c',
			'content'     => $c,
		),
		array(
			'slug'        => 'patente-d',
			'name'        => 'Patente D, D1 e DE',
			'short'       => 'Minibus e autobus',
			'description' => 'Patenti D1, D e DE per minibus e autobus: età, quiz delle patenti superiori, prova pratica e CQC persone.',
			'color'       => '#B91C1C',
			'min_age'     => '21',
			'order'       => 60,
			'equivalent'  => 'd',
			'content'     => $d,
		),
		array(
			'slug'        => 'cqc',
			'name'        => 'CQC merci e persone',
			'short'       => 'Qualificazione per autisti professionali',
			'description' => 'La Carta di qualificazione del conducente per chi trasporta merci o persone per lavoro: corso, esame e rinnovo.',
			'color'       => '#334155',
			'min_age'     => '',
			'order'       => 70,
			'equivalent'  => 'cqc',
			'content'     => $cqc,
		),
		array(
			'slug'        => 'cap-ka-kb',
			'name'        => 'CAP KA e KB (taxi e NCC)',
			'short'       => 'Taxi e noleggio con conducente',
			'description' => 'Il certificato KA o KB per guidare taxi e veicoli a noleggio con conducente: requisiti ed esame.',
			'color'       => '#A16207',
			'min_age'     => '21',
			'order'       => 80,
			'equivalent'  => 'ka-kb',
			'content'     => $kb,
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

/**
 * Descrizione della pagina «Patenti in Italia» (/patenti/).
 *
 * @return string
 */
function hub_description() {
	return 'Tutte le categorie di patente in Italia: cosa si può guidare, a che età, come si svolgono gli esami e quali quiz preparare.';
}

/**
 * Testo della pagina «Patenti in Italia».
 *
 * @return string
 */
function hub_content() {
	return h2( 'Come scegliere la patente giusta' )
		. p( 'In Italia le patenti sono divise per tipo di veicolo: ciclomotori e motocicli (AM, A1, A2, A), autovetture (B e BE), autocarri (C1, C, CE) e autobus (D1, D, DE). Ogni categoria ha un’età minima, un esame di teoria e una prova pratica; per guidare per lavoro camion e autobus serve anche la Carta di qualificazione del conducente (CQC).' )
		. p( 'Le categorie superiori si conseguono partendo dalla patente B. Per ogni patente trovi requisiti, svolgimento degli esami e le guide pratiche della redazione, con i quiz ministeriali dove disponibili.' );
}
