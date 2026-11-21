<?php
/**
 * Mailteksten: elke mail die deedni verstuurt, met een standaardtekst die je in wp-admin kunt aanpassen
 * (Formulieren & mail > Mailteksten). Met variabelen zoals {naam} en {bedrijf}, een voorbeeldweergave,
 * een testmail naar jezelf en een knop om terug te gaan naar de standaardtekst.
 *
 * Opslag: één optie 'deedni_mailteksten' met alleen de teksten die je hebt aangepast. De standaardteksten
 * staan hier in de code, zodat een update van de plugin ze kan verbeteren zonder jouw aanpassingen te raken.
 *
 * Veiligheid:
 *  - Alleen beheerders (manage_options) kunnen teksten zien en wijzigen. Elk formulier heeft een nonce.
 *  - Je typt gewone tekst. HTML wordt niet uitgevoerd maar als tekst getoond; **vet** en witregels werken wel.
 *  - Waarden van variabelen (namen, bedrijfsnamen) worden altijd ge-escaped.
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * Welke mails er zijn
 * ========================================================================= */

/**
 * Alle variabelen, met uitleg en een voorbeeldwaarde voor de voorbeeldweergave en de testmail.
 * 'html' => true: de waarde is al veilige HTML (bijvoorbeeld een lijstje), niet nog eens escapen.
 */
function deedni_mail_variabelen() {
	return array(
		'naam'           => array( 'Voornaam van de ontvanger (of de naam van het account)', 'Sanne' ),
		'bedrijf'        => array( 'Naam van het bedrijf', 'Groenwerk Hoveniers' ),
		'functie'        => array( 'Functie uit het aanbod', 'Hovenier' ),
		'salaris'        => array( 'Salaris uit het aanbod', '€ 3.200 tot € 3.800' ),
		'code'           => array( 'Anonieme code van de kandidaat', 'K-1042' ),
		'kandidaat_naam' => array( 'Volledige naam van de kandidaat (alleen na ja)', 'Sanne de Vries' ),
		'email'          => array( 'E-mailadres van het account', 'sanne@voorbeeld.nl' ),
		'link'           => array( 'De link achter de knop (ook los in de tekst te gebruiken)', 'https://voorbeeld.nl/overzicht/' ),
		'wijzigingen'    => array( 'Wat deedni aan het profiel heeft aangepast', 'functietitel, samenvatting' ),
		'zichtbaarheid'  => array( 'Zin over een verborgen profiel, met de reden (leeg als het profiel zichtbaar is)', 'Je profiel is op dit moment niet zichtbaar voor bedrijven. We missen nog je regio.' ),
		'doelgroep'      => array( 'Werkzoekende of werkgever', 'werkzoekende' ),
		'rol'            => array( 'Wat iemand koos bij "Ik ben" in het contactformulier (leeg als er geen keuze is)', 'Werkgever' ),
		'onderwerp'      => array( 'Onderwerp uit het contactformulier', 'Vraag over de kosten' ),
		'bericht'        => array( 'Het bericht uit het contactformulier', 'Hoi, wat kost het als we twee mensen aannemen?' ),
		'extra_velden'   => array( 'Extra velden die je zelf aan het formulier hebt toegevoegd (leeg als er geen zijn)', '<ul style="margin:0;padding-left:20px"><li><strong>Startdatum:</strong> 1 maart</li></ul>', true ),
		'site'           => array( 'Naam van de website', get_bloginfo( 'name' ) ),
		'site_url'       => array( 'Adres van de website', home_url( '/' ) ),
	);
}

/**
 * Elke mail: voor wie, standaard onderwerp, tekst en knop, en welke variabelen erin kunnen.
 *  'knop_url'  => true: de mail heeft een knop met {link} als adres (het adres zelf is niet aan te passen).
 *  'gevoelig'  => true: de link is een geheime sleutel (bevestigen, wachtwoord). Die bewaart de e-maillog nooit
 *                 leesbaar, en opnieuw versturen maakt een nieuwe link.
 *  'altijd'    => true: kan niet uit, want zonder deze mail kan iemand niet inloggen.
 */
function deedni_mail_typen() {
	$kandidaat_slot = 'Het bedrijf ziet je naam pas als jij ja zegt.';
	return apply_filters(
		'deedni_mail_typen',
		array(
			/* ---------- Account ---------- */
			'bevestigen'              => array(
				'titel'      => 'E-mailadres bevestigen',
				'groep'      => 'Account',
				'aan'        => 'Kandidaat of werkgever',
				'wanneer'    => 'Direct na het aanmaken van een account, en als iemand inlogt zonder te hebben bevestigd.',
				'onderwerp'  => 'Bevestig je e-mailadres',
				'tekst'      => "Hoi {naam},\n\nKlik op de knop om je e-mailadres te bevestigen. Daarna kun je inloggen. De link werkt 48 uur.\n\nHeb jij geen account aangemaakt bij {site}? Dan kun je deze mail negeren.",
				'knop'       => 'E-mailadres bevestigen',
				'knop_url'   => true,
				'variabelen' => array( 'naam', 'email', 'link' ),
				'gevoelig'   => true,
				'altijd'     => true,
			),
			'bestaand_account'        => array(
				'titel'      => 'Er is al een account',
				'groep'      => 'Account',
				'aan'        => 'Kandidaat of werkgever',
				'wanneer'    => 'Als iemand een account probeert te maken met een adres dat al bestaat. Zo verraadt de site niet welke adressen bekend zijn.',
				'onderwerp'  => 'Je hebt al een account',
				'tekst'      => "Iemand, waarschijnlijk jij, probeerde een nieuw account te maken met dit e-mailadres. Er bestaat al een account met dit adres.\n\nLog in met je wachtwoord. Weet je het wachtwoord niet meer, of heb je zelf nooit een account gemaakt? Kies dan via de knop een nieuw wachtwoord. Daarmee is het account van jou.\n\nWas jij dit niet en wil je niets doen? Dan kun je deze mail negeren.",
				'knop'       => 'Nieuw wachtwoord kiezen',
				'knop_url'   => true,
				'variabelen' => array( 'email', 'link' ),
				'altijd'     => true,
			),
			'wachtwoord'              => array(
				'titel'      => 'Wachtwoord vergeten',
				'groep'      => 'Account',
				'aan'        => 'Kandidaat of werkgever',
				'wanneer'    => 'Als iemand op "Wachtwoord vergeten" klikt (of jij in wp-admin een resetlink stuurt).',
				'onderwerp'  => 'Kies een nieuw wachtwoord',
				'tekst'      => "Hoi {naam},\n\nJe hebt gevraagd om een nieuw wachtwoord voor je account bij {site}. Klik op de knop en kies een nieuw wachtwoord. De link werkt één keer.\n\nHeb jij dit niet gevraagd? Dan kun je deze mail negeren. Je wachtwoord blijft dan hetzelfde.",
				'knop'       => 'Nieuw wachtwoord kiezen',
				'knop_url'   => true,
				'variabelen' => array( 'naam', 'email', 'link' ),
				'gevoelig'   => true,
				'altijd'     => true,
			),
			'welkom_kandidaat'        => array(
				'titel'      => 'Welkom (kandidaat)',
				'groep'      => 'Account',
				'aan'        => 'Kandidaat',
				'wanneer'    => 'Na het bevestigen van het e-mailadres.',
				'onderwerp'  => 'Welkom bij {site}',
				'tekst'      => "Hoi {naam},\n\nMaak je profiel compleet. Zodra het online staat, kunnen bedrijven je een aanbod sturen. Ze zien je naam pas als jij ja zegt.",
				'knop'       => 'Naar je profiel',
				'knop_url'   => true,
				'variabelen' => array( 'naam', 'email', 'link', 'extra_velden' ),
			),
			'welkom_werkgever'        => array(
				'titel'      => 'Welkom (werkgever, direct goedgekeurd)',
				'groep'      => 'Account',
				'aan'        => 'Werkgever',
				'wanneer'    => 'Na het bevestigen van het e-mailadres, als goedkeuring van werkgevers uit staat.',
				'onderwerp'  => 'Welkom bij {site}',
				'tekst'      => "Hoi {naam},\n\nJe account is klaar. Vul je bedrijfsprofiel aan, dan berekenen we welke kandidaten het best bij jullie passen.",
				'knop'       => 'Naar je profiel',
				'knop_url'   => true,
				'variabelen' => array( 'naam', 'bedrijf', 'email', 'link', 'extra_velden' ),
			),
			'welkom_werkgever_wacht'  => array(
				'titel'      => 'Welkom (werkgever, wacht op goedkeuring)',
				'groep'      => 'Account',
				'aan'        => 'Werkgever',
				'wanneer'    => 'Na het bevestigen van het e-mailadres, als jij werkgevers eerst goedkeurt.',
				'onderwerp'  => 'Welkom bij {site}',
				'tekst'      => "Hoi {naam},\n\nWe controleren je account, meestal binnen één werkdag. Je krijgt een mail zodra je kunt zoeken. Vul intussen je bedrijfsprofiel aan.",
				'knop'       => 'Naar je profiel',
				'knop_url'   => true,
				'variabelen' => array( 'naam', 'bedrijf', 'email', 'link', 'extra_velden' ),
			),
			'werkgever_goedgekeurd'   => array(
				'titel'      => 'Werkgever goedgekeurd',
				'groep'      => 'Account',
				'aan'        => 'Werkgever',
				'wanneer'    => 'Als jij een werkgever goedkeurt bij Gebruikers.',
				'onderwerp'  => 'Je kunt nu kandidaten zoeken',
				'tekst'      => 'Goed nieuws: je account is goedgekeurd. Je kunt nu anonieme profielen bekijken en een aanbod sturen.',
				'knop'       => 'Kandidaten zoeken',
				'knop_url'   => true,
				'variabelen' => array( 'naam', 'bedrijf', 'link' ),
			),
			'profiel_aangepast'       => array(
				'titel'      => 'Profiel aangepast door deedni',
				'groep'      => 'Account',
				'aan'        => 'Kandidaat',
				'wanneer'    => 'Als jij in wp-admin een profiel aanpast en "Kandidaat mailen" aanzet.',
				'onderwerp'  => 'Je profiel is aangepast',
				'tekst'      => "Hoi {naam},\n\nWe hebben je profiel {code} op {site} aangepast: {wijzigingen}.\n\n{zichtbaarheid}\n\nKijk je profiel even na. Klopt er iets niet, antwoord dan op deze mail.",
				'knop'       => 'Bekijk je profiel',
				'knop_url'   => true,
				'variabelen' => array( 'naam', 'code', 'wijzigingen', 'zichtbaarheid', 'link' ),
			),

			/* ---------- Aanbiedingen naar kandidaten ---------- */
			'aanbod_nieuw'            => array(
				'titel'      => 'Nieuw aanbod',
				'groep'      => 'Aanbiedingen',
				'aan'        => 'Kandidaat',
				'wanneer'    => 'Als een bedrijf een aanbod stuurt.',
				'onderwerp'  => 'Nieuw aanbod van {bedrijf}',
				'tekst'      => "Hoi {naam},\n\n{bedrijf} wil je graag spreken voor de functie **{functie}**, met een salaris van {salaris} bruto per maand.\n\n{extra_velden}\n\n" . $kandidaat_slot,
				'knop'       => 'Aanbod bekijken',
				'knop_url'   => true,
				'variabelen' => array( 'naam', 'bedrijf', 'functie', 'salaris', 'code', 'link', 'extra_velden' ),
			),
			'aanbod_aangepast'        => array(
				'titel'      => 'Aanbod aangepast',
				'groep'      => 'Aanbiedingen',
				'aan'        => 'Kandidaat',
				'wanneer'    => 'Als een bedrijf het aanbod aanpast na een "Ja, maar...".',
				'onderwerp'  => 'Aangepast aanbod van {bedrijf}',
				'tekst'      => "Hoi {naam},\n\n{bedrijf} heeft het aanbod aangepast na je reactie. Het nieuwe salaris is {salaris} per maand.\n\n{extra_velden}\n\n" . $kandidaat_slot,
				'knop'       => 'Aanbod bekijken',
				'knop_url'   => true,
				'variabelen' => array( 'naam', 'bedrijf', 'functie', 'salaris', 'code', 'link', 'extra_velden' ),
			),
			'aanbod_akkoord'          => array(
				'titel'      => 'Bedrijf gaat akkoord met je voorwaarde',
				'groep'      => 'Aanbiedingen',
				'aan'        => 'Kandidaat',
				'wanneer'    => 'Als een bedrijf akkoord gaat met een "Ja, maar...".',
				'onderwerp'  => 'Aangepast aanbod van {bedrijf}',
				'tekst'      => "Hoi {naam},\n\n{bedrijf} gaat akkoord met je voorwaarde. Het aanbod is nu {salaris} per maand. Zeg je ja?\n\n" . $kandidaat_slot,
				'knop'       => 'Aanbod bekijken',
				'knop_url'   => true,
				'variabelen' => array( 'naam', 'bedrijf', 'functie', 'salaris', 'code', 'link' ),
			),
			'aanbod_gestopt'          => array(
				'titel'      => 'Aanbod gestopt',
				'groep'      => 'Aanbiedingen',
				'aan'        => 'Kandidaat',
				'wanneer'    => 'Als een bedrijf een "Ja, maar..." laat vallen.',
				'onderwerp'  => 'Aanbod gestopt',
				'tekst'      => "Hoi {naam},\n\n{bedrijf} gaat niet verder met het aanbod voor {functie}. Je bent anoniem gebleven.\n\n" . $kandidaat_slot,
				'knop'       => 'Aanbod bekijken',
				'knop_url'   => true,
				'variabelen' => array( 'naam', 'bedrijf', 'functie', 'salaris', 'code', 'link' ),
			),

			/* ---------- Reacties naar werkgevers ---------- */
			'reactie_ja'              => array(
				'titel'      => 'Kandidaat zegt ja',
				'groep'      => 'Reacties',
				'aan'        => 'Werkgever',
				'wanneer'    => 'Als een kandidaat ja zegt op een aanbod.',
				'onderwerp'  => '{code} zegt ja op je aanbod',
				'tekst'      => "Goed nieuws: {kandidaat_naam} ({code}) heeft ja gezegd op je aanbod voor {functie}.\n\nNaam, cv en contactgegevens staan nu in je overzicht. Neem snel contact op.",
				'knop'       => 'Contactgegevens bekijken',
				'knop_url'   => true,
				'variabelen' => array( 'naam', 'bedrijf', 'functie', 'salaris', 'code', 'kandidaat_naam', 'link' ),
			),
			'reactie_jamaar'          => array(
				'titel'      => 'Kandidaat reageert met "Ja, maar..."',
				'groep'      => 'Reacties',
				'aan'        => 'Werkgever',
				'wanneer'    => 'Als een kandidaat reageert met een voorwaarde.',
				'onderwerp'  => '{code} reageert met "Ja, maar..."',
				'tekst'      => "{code} heeft interesse in je aanbod voor {functie}, met een voorwaarde.\n\n{extra_velden}\n\nBekijk de reactie in je overzicht. Je kunt het aanbod aanpassen, akkoord gaan of het laten vallen.",
				'knop'       => 'Reactie bekijken',
				'knop_url'   => true,
				'variabelen' => array( 'naam', 'bedrijf', 'functie', 'salaris', 'code', 'link', 'extra_velden' ),
			),
			'reactie_nee'             => array(
				'titel'      => 'Kandidaat zegt nee',
				'groep'      => 'Reacties',
				'aan'        => 'Werkgever',
				'wanneer'    => 'Als een kandidaat een aanbod afwijst.',
				'onderwerp'  => '{code} heeft je aanbod afgewezen',
				'tekst'      => '{code} heeft je aanbod voor {functie} vriendelijk afgewezen en blijft anoniem. Er staan nog meer kandidaten voor je klaar.',
				'knop'       => 'Verder zoeken',
				'knop_url'   => true,
				'variabelen' => array( 'naam', 'bedrijf', 'functie', 'salaris', 'code', 'link' ),
			),

			/* ---------- Naar jou (de beheerder) ---------- */
			'beheer_nieuwe_werkgever' => array(
				'titel'      => 'Nieuwe werkgever om goed te keuren',
				'groep'      => 'Naar jou',
				'aan'        => 'Beheerder (het e-mailadres bij Instellingen > Algemeen)',
				'wanneer'    => 'Als een werkgever zijn e-mailadres heeft bevestigd en wacht op goedkeuring.',
				'onderwerp'  => 'Nieuwe werkgever: {bedrijf}',
				'tekst'      => "{bedrijf} ({email}) heeft zich aangemeld als werkgever.\n\n{extra_velden}\n\nKeur het account goed bij Gebruikers, dan kan deze werkgever kandidaten zoeken.",
				'knop'       => 'Account bekijken',
				'knop_url'   => true,
				'variabelen' => array( 'bedrijf', 'email', 'link', 'extra_velden' ),
			),
			'beheer_aanname'          => array(
				'titel'      => 'Aanname gemeld',
				'groep'      => 'Naar jou',
				'aan'        => 'Beheerder',
				'wanneer'    => 'Als een werkgever meldt dat een kandidaat is aangenomen.',
				'onderwerp'  => 'Aanname gemeld: {bedrijf}',
				'tekst'      => "{bedrijf} meldt dat {code} is aangenomen als {functie} ({salaris} per maand).\n\nTijd om te factureren.",
				'knop'       => 'Aanbod bekijken',
				'knop_url'   => true,
				'variabelen' => array( 'bedrijf', 'functie', 'salaris', 'code', 'link' ),
			),
			'beheer_aanmelding'       => array(
				'titel'      => 'Nieuwe vooraanmelding',
				'groep'      => 'Naar jou',
				'aan'        => 'Beheerder',
				'wanneer'    => 'Bij een nieuwe vooraanmelding, als je dat aanzet bij Aanmeldingen.',
				'onderwerp'  => 'Nieuwe aanmelding: {naam}',
				'tekst'      => '{naam} heeft zich aangemeld als {doelgroep}.',
				'knop'       => 'Alle aanmeldingen',
				'knop_url'   => true,
				'variabelen' => array( 'naam', 'email', 'doelgroep', 'link' ),
			),
			'beheer_contact'          => array(
				'titel'      => 'Bericht via het contactformulier',
				'groep'      => 'Naar jou',
				'aan'        => 'Beheerder (of het adres bij het blok Contact)',
				'wanneer'    => 'Als iemand het contactformulier invult. Antwoorden kan direct op de mail.',
				'onderwerp'  => 'Contactformulier: {onderwerp}',
				'tekst'      => "**{naam}** ({email}) stuurde een bericht via het contactformulier.\n\nIk ben: {rol}\n\n{bericht}\n\n{extra_velden}\n\nAntwoord gewoon op deze mail.",
				'knop'       => '',
				'knop_url'   => false,
				'variabelen' => array( 'naam', 'email', 'rol', 'onderwerp', 'bericht', 'extra_velden' ),
			),
		)
	);
}

/** Variabelen die in elke mail werken. */
function deedni_mail_vaste_variabelen() {
	return array(
		'site'     => get_bloginfo( 'name' ),
		'site_url' => home_url( '/' ),
	);
}

/* =========================================================================
 * Opgeslagen teksten
 * ========================================================================= */

function deedni_mailteksten_opgeslagen() {
	$v = get_option( 'deedni_mailteksten', array() );
	return is_array( $v ) ? $v : array();
}

/** De tekst die nu geldt: jouw aanpassing, anders de standaard. */
function deedni_mailtekst( $type ) {
	$typen = deedni_mail_typen();
	if ( ! isset( $typen[ $type ] ) ) {
		return null;
	}
	$def  = $typen[ $type ];
	$eigen = deedni_mailteksten_opgeslagen();
	$eigen = isset( $eigen[ $type ] ) && is_array( $eigen[ $type ] ) ? $eigen[ $type ] : array();
	return array(
		'onderwerp' => isset( $eigen['onderwerp'] ) && '' !== $eigen['onderwerp'] ? (string) $eigen['onderwerp'] : $def['onderwerp'],
		'tekst'     => isset( $eigen['tekst'] ) && '' !== trim( $eigen['tekst'] ) ? (string) $eigen['tekst'] : $def['tekst'],
		'knop'      => isset( $eigen['knop'] ) && '' !== $eigen['knop'] ? (string) $eigen['knop'] : $def['knop'],
		'aan'       => ! empty( $def['altijd'] ) || ! isset( $eigen['aan'] ) || $eigen['aan'],
		'aangepast' => (bool) $eigen,
	);
}

/* =========================================================================
 * Opbouwen en versturen
 * ========================================================================= */

/** Onderwerp of knoptekst: platte tekst met variabelen. */
function deedni_mail_vul_plat( $tekst, $vars ) {
	$tekst = preg_replace_callback(
		'/\{([a-z_]+)\}/',
		function ( $m ) use ( $vars ) {
			if ( ! array_key_exists( $m[1], $vars ) ) {
				return $m[0];
			}
			return wp_strip_all_tags( (string) $vars[ $m[1] ] );
		},
		(string) $tekst
	);
	return trim( preg_replace( '/\s+/', ' ', $tekst ) );
}

/**
 * De tekst van de mail naar alinea's met veilige HTML. Witregel = nieuwe alinea, enter = nieuwe regel,
 * **tekst** = vet. Jouw tekst wordt ge-escaped, net als de waarden van variabelen.
 */
function deedni_mail_alineas( $tekst, $vars ) {
	$defs    = deedni_mail_variabelen();
	$alineas = array();
	foreach ( preg_split( '/\R\s*\R/', trim( (string) $tekst ) ) as $blok ) {
		$blok = trim( $blok );
		if ( '' === $blok ) {
			continue;
		}
		// Een alinea waarvan alle variabelen leeg zijn (bijvoorbeeld "Ik ben: {rol}" zonder keuze) laten we weg.
		if ( preg_match_all( '/\{([a-z_]+)\}/', $blok, $gebruikt ) ) {
			$bekend = array_intersect( $gebruikt[1], array_keys( $vars ) );
			if ( $bekend && count( $bekend ) === count( $gebruikt[1] ) && ! array_filter(
				$bekend,
				function ( $n ) use ( $vars ) {
					return '' !== trim( wp_strip_all_tags( (string) $vars[ $n ] ) ) || false !== strpos( (string) $vars[ $n ], '<ul' );
				}
			) ) {
				continue;
			}
		}
		$h = esc_html( $blok );
		$h = preg_replace( '/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $h );
		$h = preg_replace_callback(
			'/\{([a-z_]+)\}/',
			function ( $m ) use ( $vars, $defs ) {
				if ( ! array_key_exists( $m[1], $vars ) ) {
					return $m[0];
				}
				$waarde = (string) $vars[ $m[1] ];
				if ( 'link' === $m[1] || 'site_url' === $m[1] ) {
					return '' === $waarde ? '' : '<a href="' . esc_url( $waarde ) . '" style="color:#2842E0">' . esc_html( $waarde ) . '</a>';
				}
				if ( ! empty( $defs[ $m[1] ][2] ) ) {
					return wp_kses_post( $waarde );
				}
				return esc_html( $waarde );
			},
			$h
		);
		$h = nl2br( $h, false );
		// Een alinea met alleen een lege variabele (bijvoorbeeld geen extra velden) laten we weg.
		if ( '' === trim( wp_strip_all_tags( $h ) ) && false === strpos( $h, '<ul' ) ) {
			continue;
		}
		$alineas[] = $h;
	}
	return $alineas;
}

/**
 * Een mail versturen met de tekst uit Mailteksten.
 *
 * @param string       $type  Sleutel uit deedni_mail_typen().
 * @param string|array $to    Ontvanger.
 * @param array        $vars  Waarden voor de variabelen.
 * @param array        $ctx   Voor de e-maillog: 'gebruiker' (user ID) en 'object' (aanbod-, profiel- of aanmeldings-ID).
 * @return bool
 */
function deedni_mail_sjabloon( $type, $to, $vars = array(), $ctx = array() ) {
	$typen = deedni_mail_typen();
	$tekst = isset( $ctx['tekst'] ) && is_array( $ctx['tekst'] ) ? $ctx['tekst'] : deedni_mailtekst( $type );
	unset( $ctx['tekst'] );
	if ( ! $tekst || ! $to ) {
		return false;
	}
	$def  = $typen[ $type ];
	$vars = array_merge( deedni_mail_vaste_variabelen(), $vars );
	$ctx  = wp_parse_args( $ctx, array( 'gebruiker' => 0, 'object' => 0, 'bron' => 'site' ) );
	$ctx['type']     = $type;
	$ctx['gevoelig'] = ! empty( $def['gevoelig'] );

	$onderwerp = deedni_mail_vul_plat( $tekst['onderwerp'], $vars );
	if ( 'test' === $ctx['bron'] ) {
		$onderwerp = '[Test] ' . $onderwerp;
	}
	if ( ! $tekst['aan'] ) {
		// Uitgezet bij Mailteksten: niet versturen, wel in de log, zodat je ziet waarom iemand niets kreeg.
		do_action( 'deedni_mail_overgeslagen', $to, $onderwerp, $ctx );
		return false;
	}
	$GLOBALS['deedni_mail_context'] = $ctx;
	$ok = deedni_mail(
		$to,
		$onderwerp,
		deedni_mail_alineas( $tekst['tekst'], $vars ),
		$def['knop_url'] ? deedni_mail_vul_plat( $tekst['knop'], $vars ) : '',
		$def['knop_url'] && ! empty( $vars['link'] ) ? $vars['link'] : ''
	);
	unset( $GLOBALS['deedni_mail_context'] );
	return $ok;
}

/** De voorbeeldwaarden van alle variabelen. */
function deedni_mail_voorbeeld_vars() {
	$vars = array();
	foreach ( deedni_mail_variabelen() as $naam => $v ) {
		$vars[ $naam ] = $v[1];
	}
	return $vars;
}

/** HTML van een mail met voorbeeldwaarden, voor de voorbeeldweergave in wp-admin. */
function deedni_mail_voorbeeld_html( $type, $tekst ) {
	$def  = deedni_mail_typen()[ $type ];
	$vars = deedni_mail_voorbeeld_vars();
	return deedni_mail_html(
		deedni_mail_alineas( $tekst['tekst'], $vars ),
		$def['knop_url'] ? deedni_mail_vul_plat( $tekst['knop'], $vars ) : '',
		$def['knop_url'] ? $vars['link'] : ''
	);
}

/* =========================================================================
 * Wachtwoord vergeten: de mail van WordPress zelf in deedni-stijl en met jouw tekst
 * ========================================================================= */

add_filter(
	'retrieve_password_notification_email',
	function ( $mail, $key, $user_login, $user ) {
		if ( ! $user instanceof WP_User ) {
			return $mail;
		}
		$tekst = deedni_mailtekst( 'wachtwoord' );
		$link  = network_site_url( 'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user_login ), 'login' );
		$vars  = array_merge(
			deedni_mail_vaste_variabelen(),
			array(
				'naam'  => $user->first_name ? $user->first_name : $user->display_name,
				'email' => $user->user_email,
				'link'  => $link,
			)
		);
		$mail['subject'] = deedni_mail_vul_plat( $tekst['onderwerp'], $vars );
		$mail['message'] = deedni_mail_html( deedni_mail_alineas( $tekst['tekst'], $vars ), deedni_mail_vul_plat( $tekst['knop'], $vars ), $link );
		$mail['headers'] = array( 'Content-Type: text/html; charset=UTF-8' );
		// Context voor de e-maillog: deze mail heeft een geheime link.
		$GLOBALS['deedni_mail_context'] = array(
			'type'      => 'wachtwoord',
			'gebruiker' => $user->ID,
			'object'    => 0,
			'gevoelig'  => true,
			'eenmalig'  => true,
		);
		return $mail;
	},
	10,
	4
);

/* =========================================================================
 * wp-admin: Formulieren & mail > Mailteksten
 * ========================================================================= */

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'deedni-maillog', 'Mailteksten', 'Mailteksten', 'manage_options', 'deedni-mailteksten', 'deedni_mailteksten_pagina' );
	},
	11
);

/** Opslaan, testmail of herstellen. */
add_action(
	'admin_post_deedni_mailtekst',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.', 403 );
		}
		$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		check_admin_referer( 'deedni_mailtekst_' . $type );
		$typen = deedni_mail_typen();
		if ( ! isset( $typen[ $type ] ) ) {
			wp_die( 'Onbekende mail.' );
		}
		$def   = $typen[ $type ];
		$doe   = isset( $_POST['doe'] ) ? sanitize_key( wp_unslash( $_POST['doe'] ) ) : 'opslaan';
		$alles = deedni_mailteksten_opgeslagen();
		$terug = admin_url( 'admin.php?page=deedni-mailteksten&type=' . $type );

		if ( 'herstel' === $doe ) {
			unset( $alles[ $type ] );
			update_option( 'deedni_mailteksten', $alles, false );
			wp_safe_redirect( add_query_arg( 'deedni_klaar', 'hersteld', $terug ) );
			exit;
		}

		$nieuw = array(
			'onderwerp' => isset( $_POST['onderwerp'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['onderwerp'] ) ), 0, 200 ) : '',
			'tekst'     => isset( $_POST['tekst'] ) ? mb_substr( sanitize_textarea_field( wp_unslash( $_POST['tekst'] ) ), 0, 5000 ) : '',
			'knop'      => isset( $_POST['knop'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['knop'] ) ), 0, 60 ) : '',
			'aan'       => ! empty( $def['altijd'] ) || ! empty( $_POST['aan'] ) ? 1 : 0,
		);
		// Gelijk aan de standaard? Dan niets bewaren, zodat een verbeterde standaardtekst later doorkomt.
		foreach ( array( 'onderwerp', 'tekst', 'knop' ) as $k ) {
			if ( str_replace( "\r", '', trim( $nieuw[ $k ] ) ) === str_replace( "\r", '', trim( $def[ $k ] ) ) ) {
				$nieuw[ $k ] = '';
			}
		}
		$leeg = '' === $nieuw['onderwerp'] && '' === $nieuw['tekst'] && '' === $nieuw['knop'] && $nieuw['aan'];

		if ( 'test' === $doe ) {
			// Testmail met de tekst zoals hij nu in het formulier staat (ook als je nog niet hebt opgeslagen).
			$tekst = array( 'aan' => true );
			foreach ( array( 'onderwerp', 'tekst', 'knop' ) as $k ) {
				$tekst[ $k ] = '' !== $nieuw[ $k ] ? $nieuw[ $k ] : $def[ $k ];
			}
			$ik = wp_get_current_user();
			$ok = deedni_mail_sjabloon( $type, $ik->user_email, deedni_mail_voorbeeld_vars(), array( 'gebruiker' => $ik->ID, 'bron' => 'test', 'tekst' => $tekst ) );
			set_transient( 'deedni_mailtekst_concept_' . get_current_user_id(), array( 'type' => $type ) + $nieuw, 5 * MINUTE_IN_SECONDS );
			wp_safe_redirect( add_query_arg( array( 'deedni_klaar' => $ok ? 'test' : 'test_mislukt', 'concept' => 1 ), $terug ) );
			exit;
		}

		if ( $leeg ) {
			unset( $alles[ $type ] );
		} else {
			$alles[ $type ] = $nieuw;
		}
		update_option( 'deedni_mailteksten', $alles, false );
		wp_safe_redirect( add_query_arg( 'deedni_klaar', 'opgeslagen', $terug ) );
		exit;
	}
);

/** Voorbeeldweergave tijdens het typen (admin-ajax). */
add_action(
	'wp_ajax_deedni_mail_voorbeeld',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		check_ajax_referer( 'deedni_mail_voorbeeld' );
		if ( ! isset( deedni_mail_typen()[ $type ] ) ) {
			wp_send_json_error();
		}
		$tekst = array(
			'onderwerp' => isset( $_POST['onderwerp'] ) ? sanitize_text_field( wp_unslash( $_POST['onderwerp'] ) ) : '',
			'tekst'     => isset( $_POST['tekst'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tekst'] ) ) : '',
			'knop'      => isset( $_POST['knop'] ) ? sanitize_text_field( wp_unslash( $_POST['knop'] ) ) : '',
		);
		$def = deedni_mail_typen()[ $type ];
		foreach ( array( 'onderwerp', 'tekst', 'knop' ) as $k ) {
			if ( '' === trim( $tekst[ $k ] ) ) {
				$tekst[ $k ] = $def[ $k ];
			}
		}
		wp_send_json_success(
			array(
				'onderwerp' => deedni_mail_vul_plat( $tekst['onderwerp'], array_merge( deedni_mail_vaste_variabelen(), deedni_mail_voorbeeld_vars() ) ),
				'html'      => deedni_mail_voorbeeld_html( $type, $tekst ),
			)
		);
	}
);

function deedni_mailteksten_pagina() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$typen = deedni_mail_typen();
	$type  = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	echo '<div class="wrap deedni-beheer">';
	if ( $type && isset( $typen[ $type ] ) ) {
		deedni_mailtekst_bewerken( $type, $typen[ $type ] );
		echo '</div>';
		return;
	}

	echo '<h1>Mailteksten</h1>';
	echo '<p class="deedni-intro">Dit zijn alle mails die de site verstuurt. Klik op een mail om de tekst te bekijken of aan te passen. Je ziet meteen een voorbeeld en kunt een testmail naar jezelf sturen. Wil je zien of een mail echt is verstuurd? Kijk dan bij <a href="' . esc_url( admin_url( 'admin.php?page=deedni-maillog' ) ) . '">E-maillog</a>.</p>';
	$groepen = array();
	foreach ( $typen as $sleutel => $def ) {
		$groepen[ $def['groep'] ][ $sleutel ] = $def;
	}
	foreach ( $groepen as $groep => $lijst ) {
		echo '<h2>' . esc_html( $groep ) . '</h2>';
		echo '<table class="widefat striped deedni-tabel"><thead><tr><th>Mail</th><th>Naar</th><th>Onderwerp</th><th>Status</th></tr></thead><tbody>';
		foreach ( $lijst as $sleutel => $def ) {
			$t   = deedni_mailtekst( $sleutel );
			$url = admin_url( 'admin.php?page=deedni-mailteksten&type=' . $sleutel );
			echo '<tr><td><a class="row-title" href="' . esc_url( $url ) . '">' . esc_html( $def['titel'] ) . '</a><div class="deedni-sub">' . esc_html( $def['wanneer'] ) . '</div></td>';
			echo '<td>' . esc_html( $def['aan'] ) . '</td>';
			echo '<td>' . esc_html( $t['onderwerp'] ) . '</td><td>';
			if ( ! $t['aan'] ) {
				echo '<span class="deedni-label deedni-label--uit">Uit</span>';
			} elseif ( $t['aangepast'] ) {
				echo '<span class="deedni-label deedni-label--eigen">Aangepast</span>';
			} else {
				echo '<span class="deedni-label">Standaard</span>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';
}

function deedni_mailtekst_bewerken( $type, $def ) {
	$t       = deedni_mailtekst( $type );
	$concept = ! empty( $_GET['concept'] ) ? get_transient( 'deedni_mailtekst_concept_' . get_current_user_id() ) : false; // phpcs:ignore WordPress.Security.NonceVerification
	if ( is_array( $concept ) && isset( $concept['type'] ) && $concept['type'] === $type ) {
		foreach ( array( 'onderwerp', 'tekst', 'knop' ) as $k ) {
			if ( '' !== $concept[ $k ] ) {
				$t[ $k ] = $concept[ $k ];
			}
		}
		$t['aan'] = $t['aan'] && $concept['aan'];
	}
	$klaar    = isset( $_GET['deedni_klaar'] ) ? sanitize_key( wp_unslash( $_GET['deedni_klaar'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$meldingen = array(
		'opgeslagen'   => array( 'Opgeslagen. Vanaf nu gaat deze mail met de nieuwe tekst de deur uit.', 'success' ),
		'hersteld'     => array( 'De standaardtekst staat er weer.', 'success' ),
		'test'         => array( 'Testmail verstuurd naar ' . wp_get_current_user()->user_email . ', met voorbeeldwaarden. Let op: je wijzigingen zijn nog niet opgeslagen.', 'success' ),
		'test_mislukt' => array( 'De testmail kon niet worden verstuurd. Kijk bij E-maillog wat de foutmelding is.', 'error' ),
	);
	$vars    = deedni_mail_variabelen();
	$vb      = array_merge( deedni_mail_vaste_variabelen(), deedni_mail_voorbeeld_vars() );
	$lijst_vars = array_merge( $def['variabelen'], array( 'site', 'site_url' ) );

	echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=deedni-mailteksten' ) ) . '">&larr; Alle mailteksten</a></p>';
	echo '<h1>' . esc_html( $def['titel'] ) . '</h1>';
	echo '<p class="deedni-intro"><strong>Naar:</strong> ' . esc_html( $def['aan'] ) . '. <strong>Wanneer:</strong> ' . esc_html( $def['wanneer'] ) . '</p>';
	if ( isset( $meldingen[ $klaar ] ) ) {
		echo '<div class="notice notice-' . esc_attr( $meldingen[ $klaar ][1] ) . ' is-dismissible"><p>' . esc_html( $meldingen[ $klaar ][0] ) . '</p></div>';
	}
	if ( ! empty( $def['gevoelig'] ) ) {
		echo '<div class="notice notice-info inline"><p>De knop in deze mail bevat een geheime link. Die zie je nooit in de e-maillog, en opnieuw versturen maakt altijd een nieuwe link.</p></div>';
	}

	echo '<div class="deedni-mail-grid">';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="deedni-mail-form" id="deedni-mail-form">';
	echo '<input type="hidden" name="action" value="deedni_mailtekst"><input type="hidden" name="type" value="' . esc_attr( $type ) . '">';
	wp_nonce_field( 'deedni_mailtekst_' . $type );
	echo '<p><label for="dm-onderwerp"><strong>Onderwerp</strong></label><br><input type="text" class="large-text" id="dm-onderwerp" name="onderwerp" maxlength="200" value="' . esc_attr( $t['onderwerp'] ) . '"></p>';
	echo '<p><label for="dm-tekst"><strong>Tekst</strong></label><br><textarea class="large-text" rows="12" id="dm-tekst" name="tekst" maxlength="5000">' . esc_textarea( $t['tekst'] ) . '</textarea>';
	echo '<span class="description">Een witregel begint een nieuwe alinea. Zet een woord tussen **twee sterretjes** voor vet. Een variabele tussen accolades, zoals {naam}, wordt in de mail vervangen.</span></p>';
	if ( $def['knop_url'] ) {
		echo '<p><label for="dm-knop"><strong>Tekst op de knop</strong></label><br><input type="text" class="regular-text" id="dm-knop" name="knop" maxlength="60" value="' . esc_attr( $t['knop'] ) . '"><br><span class="description">De knop gaat naar {link}. Dat adres maakt de site zelf.</span></p>';
	}
	if ( empty( $def['altijd'] ) ) {
		echo '<p><label><input type="checkbox" name="aan" value="1"' . checked( $t['aan'], true, false ) . '> Deze mail versturen</label><br><span class="description">Zet je hem uit, dan gaat hij niet meer de deur uit. In de e-maillog zie je dan "Uitgezet".</span></p>';
	} else {
		echo '<p class="description">Deze mail kan niet uit: zonder deze mail kan iemand niet inloggen.</p>';
	}
	echo '<p class="deedni-knoppen">';
	echo '<button type="submit" name="doe" value="opslaan" class="button button-primary">Opslaan</button> ';
	echo '<button type="submit" name="doe" value="test" class="button">Testmail naar mij</button> ';
	if ( $t['aangepast'] ) {
		echo '<button type="submit" name="doe" value="herstel" class="button button-link-delete" onclick="return confirm(\'Je eigen tekst voor deze mail verdwijnt en de standaardtekst komt terug. Doorgaan?\')">Standaardtekst herstellen</button>';
	}
	echo '</p></form>';

	echo '<div class="deedni-mail-zij">';
	echo '<h2>Variabelen</h2><p class="description">Klik om in te voegen op de plek van je cursor.</p><table class="widefat deedni-vars"><tbody>';
	foreach ( $lijst_vars as $v ) {
		if ( ! isset( $vars[ $v ] ) ) {
			continue;
		}
		echo '<tr><td><button type="button" class="button-link" data-var="' . esc_attr( $v ) . '"><code>{' . esc_html( $v ) . '}</code></button></td><td>' . esc_html( $vars[ $v ][0] ) . '<br><span class="deedni-sub">Bijvoorbeeld: ' . esc_html( wp_strip_all_tags( $vb[ $v ] ) ) . '</span></td></tr>';
	}
	echo '</tbody></table>';
	echo '<h2>Voorbeeld</h2><p class="deedni-sub">Met voorbeeldwaarden. Onderwerp: <strong id="dm-vb-onderwerp">' . esc_html( deedni_mail_vul_plat( $t['onderwerp'], $vb ) ) . '</strong></p>';
	echo '<iframe id="dm-voorbeeld" class="deedni-voorbeeld" sandbox="" title="Voorbeeld van de mail" srcdoc="' . esc_attr( deedni_mail_voorbeeld_html( $type, $t ) ) . '"></iframe>';
	echo '</div></div>';
	?>
	<script>
	(function () {
		var f = document.getElementById('deedni-mail-form'), laatste = null, timer = null;
		if (!f) { return; }
		document.querySelectorAll('[data-var]').forEach(function (b) {
			b.addEventListener('click', function () {
				var veld = laatste || document.getElementById('dm-tekst'), t = '{' + b.getAttribute('data-var') + '}';
				var s = veld.selectionStart || 0, e = veld.selectionEnd || 0;
				veld.value = veld.value.slice(0, s) + t + veld.value.slice(e);
				veld.focus(); veld.selectionStart = veld.selectionEnd = s + t.length;
				bijwerken();
			});
		});
		['dm-onderwerp', 'dm-tekst', 'dm-knop'].forEach(function (id) {
			var el = document.getElementById(id);
			if (!el) { return; }
			el.addEventListener('focus', function () { laatste = el; });
			el.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(bijwerken, 300); });
		});
		function bijwerken() {
			var d = new FormData();
			d.append('action', 'deedni_mail_voorbeeld');
			d.append('_ajax_nonce', <?php echo wp_json_encode( wp_create_nonce( 'deedni_mail_voorbeeld' ) ); ?>);
			d.append('type', <?php echo wp_json_encode( $type ); ?>);
			['onderwerp', 'tekst', 'knop'].forEach(function (k) { var el = f.elements[k]; d.append(k, el ? el.value : ''); });
			fetch(ajaxurl, { method: 'POST', body: d, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
				if (!r || !r.success) { return; }
				document.getElementById('dm-voorbeeld').srcdoc = r.data.html;
				document.getElementById('dm-vb-onderwerp').textContent = r.data.onderwerp;
			});
		}
	})();
	</script>
	<?php
}

/* =========================================================================
 * Gedeelde opmaak voor de pagina's onder Formulieren & mail
 * ========================================================================= */

add_action(
	'admin_head',
	function () {
		$scherm = get_current_screen();
		if ( ! $scherm || false === strpos( (string) $scherm->id, 'deedni-m' ) && false === strpos( (string) $scherm->id, 'deedni-formulieren' ) ) {
			return;
		}
		?>
		<style>
		.deedni-beheer .deedni-intro{max-width:780px;font-size:14px}
		.deedni-beheer .deedni-sub{color:#646970;font-size:12px;margin-top:2px}
		.deedni-beheer .deedni-tabel td{vertical-align:top}
		.deedni-label{display:inline-block;padding:2px 8px;border-radius:10px;background:#f0f0f1;font-size:12px}
		.deedni-label--eigen{background:#e7ecff;color:#1d35b8}
		.deedni-label--uit{background:#fcf0f1;color:#8a2424}
		.deedni-label--ok{background:#edfaef;color:#1a6b2c}
		.deedni-label--fout{background:#fcf0f1;color:#8a2424}
		.deedni-label--wacht{background:#fcf9e8;color:#6d5a00}
		.deedni-mail-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:32px;align-items:start;max-width:1400px}
		@media (max-width:1100px){.deedni-mail-grid{grid-template-columns:1fr}}
		.deedni-voorbeeld{width:100%;min-height:460px;border:1px solid #dcdcde;background:#fff;border-radius:4px}
		.deedni-vars td{padding:6px 8px}
		.deedni-knoppen .button{margin-right:6px}
		.deedni-fout{color:#8a2424}
		.deedni-inline{display:inline}
		.deedni-filters{margin:12px 0}
		</style>
		<?php
	}
);
