<?php
/**
 * Formulieren aanpassen in wp-admin (Formulieren & mail > Formulieren): per formulier de labels,
 * hulpteksten, voorbeeldteksten, verplicht/optioneel en de volgorde van velden wijzigen, velden verbergen
 * en eigen extra velden toevoegen.
 *
 * Waarom zo en niet met een formulierplugin (Gravity Forms, WPForms, Fluent Forms)?
 * De formulieren van deedni doen meer dan gegevens verzamelen: ze maken accounts, sturen aanbiedingen met
 * sloten tegen dubbel versturen, rekenen matches uit en hebben rate limits, nonces en anonimiteit ingebouwd.
 * Een formulierplugin zou al die logica opnieuw moeten krijgen (vaak betaald, en met minder controle over
 * beveiliging). Daarom laten we de formulieren zelf staan en leggen we er een instelbare laag overheen:
 *
 *  - De formulieren worden gewoon opgebouwd zoals altijd. Daarna past deze laag de HTML aan: labels en
 *    hulpteksten vervangen, velden verbergen of verplicht maken, de volgorde wijzigen en extra velden
 *    toevoegen. Zonder aanpassingen blijft de HTML precies hetzelfde.
 *  - Kernvelden (nodig voor accounts, matching of de controle in de code) kun je niet verbergen en niet
 *    optioneel maken. Zo kan het verwijderen van een veld de matching of validatie nooit breken.
 *  - Verplicht maken controleren we ook op de server, niet alleen in de browser.
 *  - Extra velden worden opgeslagen bij het aanbod, het profiel of het account en zijn te zien in wp-admin.
 *    Bij een aanbod en een "Ja, maar..." gaan ze mee in de mail (variabele {extra_velden}). Extra velden
 *    van een profiel zijn nooit zichtbaar voor werkgevers, zodat er niet per ongeluk iets herkenbaars
 *    op het anonieme profiel komt.
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * Welke formulieren en velden er zijn
 * ========================================================================= */

/**
 * Per formulier:
 *  'actie'    waarde van het verborgen veld deedni_action
 *  'context'  sleutel voor foutmeldingen (deedni_fout / deedni_flash)
 *  'opslag'   waar extra velden heen gaan: aanbod, reactie, gebruiker, kandidaat, bedrijf, mail (alleen in de mail)
 *             of '' (geen extra velden)
 *  'groepen'  stukken van het formulier; de volgorde wijzigt alleen binnen een groep
 *  'velden'   per veld: label en hint (de standaard, ter info), groep, kern, verplicht (standaard),
 *             naam (name-attribuut), container (field, fieldset of check), placeholder (kan een voorbeeldtekst),
 *             volgorde (mag verplaatst worden), alleen_als (alleen verplicht bij deze keuze)
 */
function deedni_formulieren_defs() {
	return apply_filters(
		'deedni_formulieren_defs',
		array(
			'registreren'   => array(
				'titel'     => 'Account aanmaken',
				'uitleg'    => 'Het formulier op de pagina Inloggen, voor kandidaten en werkgevers samen.',
				'pagina'    => 'inloggen',
				'actie'     => 'registreren',
				'context'   => 'registreren',
				'opslag'    => 'gebruiker',
				'rollen'    => array( 'kandidaat' => 'Alleen kandidaten', 'werkgever' => 'Alleen werkgevers' ),
				'rol_veld'  => 'rol',
				'flash'     => array( 'rol', 'naam', 'email' ),
				'groepen'   => array( 'account' => 'Velden' ),
				'velden'    => array(
					'naam'       => array( 'label' => 'Je voornaam', 'label_w' => 'Bedrijfsnaam', 'varianten' => true, 'kern' => true, 'verplicht' => true, 'uitleg_kern' => 'Nodig voor het account (voornaam of bedrijfsnaam).' ),
					'email'      => array( 'label' => 'E-mailadres', 'kern' => true, 'verplicht' => true, 'uitleg_kern' => 'Je inlognaam.' ),
					'wachtwoord' => array( 'label' => 'Wachtwoord', 'hint' => 'Minstens 8 tekens.', 'kern' => true, 'verplicht' => true, 'placeholder' => false, 'uitleg_kern' => 'Nodig om in te loggen.' ),
					'akkoord'    => array( 'label' => 'Akkoord met de privacyverklaring', 'kern' => true, 'verplicht' => true, 'container' => 'check', 'label_bewerkbaar' => false, 'hint_bewerkbaar' => false, 'placeholder' => false, 'volgorde' => false, 'uitleg_kern' => 'Wettelijk verplicht. De tekst bevat een link naar je privacyverklaring.' ),
				),
			),
			'profiel'       => array(
				'titel'     => 'Profiel van een kandidaat',
				'uitleg'    => 'De stappen van Mijn profiel. Verplicht betekent hier: nodig om het profiel te publiceren. Opslaan zonder kan altijd, zodat niemand werk kwijtraakt.',
				'pagina'    => 'profiel',
				'actie'     => 'profiel',
				'context'   => 'profiel',
				'opslag'    => 'kandidaat',
				'geen_html_required' => true,
				'groepen'   => array(
					'persoonlijk'  => 'Stap 1: Persoonlijk',
					'werkervaring' => 'Stap 2: Werkervaring',
					'zoek'         => 'Stap 4: Wat zoek je',
				),
				'velden'    => array(
					'voornaam'          => array( 'label' => 'Voornaam', 'groep' => 'persoonlijk', 'kern' => true, 'uitleg_kern' => 'Naam die een werkgever ziet na een ja, en in de mails.' ),
					'achternaam'        => array( 'label' => 'Achternaam', 'groep' => 'persoonlijk', 'kern' => true, 'uitleg_kern' => 'Naam die een werkgever ziet na een ja.' ),
					'functietitel'      => array( 'label' => 'Functietitel', 'hint' => 'Bijvoorbeeld Hovenier, Webdesigner of Senior accountant.', 'groep' => 'persoonlijk', 'kern' => true, 'verplicht' => true, 'uitleg_kern' => 'Nodig om te publiceren en om te zoeken.' ),
					'specialisme'       => array( 'label' => 'Vakgebied en functie', 'hint' => 'Staat je functie er niet bij? Kies je vakgebied (algemeen) en schrijf je functie bij Functietitel.', 'groep' => 'persoonlijk', 'kern' => true, 'verplicht' => true, 'placeholder' => false, 'uitleg_kern' => 'Basis van de matching.' ),
					'ervaring_jaren'    => array( 'label' => 'Jaren ervaring', 'groep' => 'persoonlijk', 'kern' => true, 'uitleg_kern' => 'Werkgevers filteren hierop.' ),
					'telefoon'          => array( 'label' => 'Telefoon', 'groep' => 'persoonlijk' ),
					'linkedin'          => array( 'label' => 'LinkedIn', 'groep' => 'persoonlijk' ),
					'opleiding'         => array( 'label' => 'Opleiding of certificaten', 'groep' => 'persoonlijk' ),
					'samenvatting'      => array( 'label' => 'Over mij', 'hint' => 'Twee à drie zinnen. Geen namen van werkgevers.', 'groep' => 'persoonlijk', 'kern' => true, 'uitleg_kern' => 'Telt mee voor hoe compleet het profiel is.' ),
					'huidige_werkgever' => array( 'label' => 'Huidige werkgever', 'hint' => 'Zet je huidige werkgever ook op de blokkeerlijst bij stap 4.', 'groep' => 'werkervaring' ),
					'salaris_min'       => array( 'label' => 'Salaris vanaf, per maand', 'hint' => 'Bedrijven zien dit afgerond naar beneden op honderdtallen.', 'groep' => 'zoek', 'kern' => true, 'uitleg_kern' => 'Telt mee in de matching.' ),
					'uren'              => array( 'label' => 'Uren per week', 'groep' => 'zoek' ),
					'provincie'         => array( 'label' => 'Provincie', 'groep' => 'zoek', 'kern' => true, 'placeholder' => false, 'uitleg_kern' => 'Telt mee in de matching.' ),
					'regio'             => array( 'label' => 'Regio in je eigen woorden', 'groep' => 'zoek', 'kern' => true, 'uitleg_kern' => 'Nodig om te publiceren (of provincie).' ),
					'beschikbaarheid'   => array( 'label' => 'Beschikbaar', 'groep' => 'zoek', 'kern' => true, 'placeholder' => false, 'uitleg_kern' => 'Werkgevers filteren hierop.' ),
					'zoekstatus'        => array( 'label' => 'Hoe actief zoek je?', 'groep' => 'zoek', 'kern' => true, 'placeholder' => false, 'uitleg_kern' => 'Werkgevers filteren hierop.' ),
					'werkplekken'       => array( 'label' => 'Werkplek', 'naam' => 'werkplekken[]', 'container' => 'fieldset', 'groep' => 'zoek', 'kern' => true, 'placeholder' => false, 'hint_bewerkbaar' => false, 'uitleg_kern' => 'Telt mee in de matching.' ),
				),
			),
			'bedrijf'       => array(
				'titel'     => 'Bedrijfsprofiel',
				'uitleg'    => 'Het profiel van een werkgever (Mijn profiel als werkgever).',
				'pagina'    => 'profiel',
				'actie'     => 'bedrijf',
				'context'   => 'bedrijf',
				'opslag'    => 'bedrijf',
				'groepen'   => array(
					'gegevens' => 'Bedrijfsgegevens',
					'zoek'     => 'Wie je zoekt',
				),
				'velden'    => array(
					'bedrijfsnaam'     => array( 'label' => 'Naam van je bedrijf', 'groep' => 'gegevens', 'kern' => true, 'verplicht' => true, 'uitleg_kern' => 'Kandidaten zien dit bij elk aanbod.' ),
					'soort'            => array( 'label' => 'Soort bedrijf', 'groep' => 'gegevens' ),
					'plaats'           => array( 'label' => 'Plaats', 'groep' => 'gegevens' ),
					'website'          => array( 'label' => 'Website', 'groep' => 'gegevens' ),
					'medewerkers'      => array( 'label' => 'Aantal medewerkers', 'groep' => 'gegevens' ),
					'kvk_nummer'       => array( 'label' => 'KvK-nummer', 'hint' => 'Hiermee controleren we je bedrijf.', 'groep' => 'gegevens' ),
					'contactpersoon'   => array( 'label' => 'Contactpersoon', 'groep' => 'gegevens' ),
					'telefoon'         => array( 'label' => 'Telefoon', 'groep' => 'gegevens' ),
					'omschrijving'     => array( 'label' => 'Over het bedrijf', 'hint' => 'Twee à drie zinnen: wat jullie doen en hoe het is om er te werken.', 'groep' => 'gegevens' ),
					'zoek_salaris_min' => array( 'label' => 'Salarisbudget van', 'groep' => 'zoek', 'kern' => true, 'uitleg_kern' => 'Telt mee in de matching.' ),
					'zoek_salaris_max' => array( 'label' => 'Salarisbudget tot', 'groep' => 'zoek', 'kern' => true, 'uitleg_kern' => 'Telt mee in de matching.' ),
					'zoek_uren'        => array( 'label' => 'Uren per week', 'groep' => 'zoek' ),
				),
			),
			'aanbod_sturen' => array(
				'titel'     => 'Aanbod sturen',
				'uitleg'    => 'De pop-up waarmee een werkgever een kandidaat een aanbod stuurt (of het aanbod aanpast).',
				'pagina'    => 'zoeken',
				'actie'     => 'aanbod_sturen',
				'context'   => 'aanbod',
				'opslag'    => 'aanbod',
				'groepen'   => array( 'aanbod' => 'Velden' ),
				'velden'    => array(
					'functie'     => array( 'label' => 'Functie', 'kern' => true, 'verplicht' => true, 'uitleg_kern' => 'Staat in elke mail en elk overzicht.' ),
					'salaris_min' => array( 'label' => 'Salaris van', 'kern' => true, 'verplicht' => true, 'uitleg_kern' => 'Een aanbod gaat nooit zonder salaris de deur uit.' ),
					'salaris_max' => array( 'label' => 'Salaris tot', 'kern' => true, 'verplicht' => true, 'uitleg_kern' => 'Een aanbod gaat nooit zonder salaris de deur uit.' ),
					'uren'        => array( 'label' => 'Uren per week' ),
					'contract'    => array( 'label' => 'Contract', 'placeholder' => false ),
					'standplaats' => array( 'label' => 'Standplaats' ),
					'werkplek'    => array( 'label' => 'Werkplek', 'container' => 'fieldset', 'placeholder' => false, 'hint_bewerkbaar' => false, 'kan_verplicht' => false ),
					'bericht'     => array( 'label' => 'Persoonlijk bericht', 'hint' => 'Schrijf waarom dit profiel past. Dat wordt vaker beantwoord dan een standaardtekst.', 'kern' => true, 'verplicht' => true, 'uitleg_kern' => 'Minstens 20 tekens, gecontroleerd door de site.' ),
				),
			),
			'aanbod_jamaar' => array(
				'titel'     => 'Reageren met "Ja, maar..."',
				'uitleg'    => 'De pop-up waarmee een kandidaat met een voorwaarde reageert op een aanbod.',
				'pagina'    => 'dashboard',
				'actie'     => 'aanbod_jamaar',
				'context'   => 'jamaar',
				'opslag'    => 'reactie',
				'groepen'   => array( 'reactie' => 'Velden' ),
				'velden'    => array(
					'voorwaarden' => array( 'label' => 'Waar gaat je voorwaarde over?', 'naam' => 'voorwaarden[]', 'container' => 'fieldset', 'kern' => true, 'verplicht' => true, 'placeholder' => false, 'hint_bewerkbaar' => false, 'uitleg_kern' => 'Het bedrijf moet weten waar de voorwaarde over gaat.' ),
					'tegen_min'   => array( 'label' => 'Salaris van', 'kern' => true, 'uitleg_kern' => 'Het bedrijf kan hiermee akkoord gaan.' ),
					'tegen_max'   => array( 'label' => 'Salaris tot', 'kern' => true, 'uitleg_kern' => 'Het bedrijf kan hiermee akkoord gaan.' ),
					'tegen_wens'  => array( 'label' => 'Extra wens' ),
					'bericht'     => array( 'label' => 'Bericht aan het bedrijf', 'hint' => 'Schrijf er duidelijk bij wat wel goed is. Dat helpt het bedrijf je aanbod aan te passen.', 'kern' => true, 'verplicht' => true, 'uitleg_kern' => 'Minstens 10 tekens, gecontroleerd door de site.' ),
				),
			),
			'contact'       => array(
				'titel'     => 'Contact',
				'uitleg'    => 'Het contactformulier (blok Contact). De kop, de keuzes bij "Ik ben", de knop en de toestemmingstekst pas je aan in het blok zelf.',
				'pagina'    => '',
				'actie'     => 'contact',
				'context'   => 'contact',
				'opslag'    => 'mail',
				'flash'     => array( 'rol', 'naam', 'email', 'onderwerp', 'bericht', 'akkoord' ),
				'groepen'   => array( 'velden' => 'Velden' ),
				'velden'    => array(
					'naam'      => array( 'label' => 'Naam', 'kern' => true, 'verplicht' => true, 'uitleg_kern' => 'Nodig om te kunnen antwoorden.' ),
					'email'     => array( 'label' => 'E-mailadres', 'kern' => true, 'verplicht' => true, 'uitleg_kern' => 'Nodig om te kunnen antwoorden.' ),
					'onderwerp' => array( 'label' => 'Onderwerp' ),
					'bericht'   => array( 'label' => 'Bericht', 'kern' => true, 'verplicht' => true, 'uitleg_kern' => 'Het bericht zelf.' ),
					'akkoord'   => array( 'label' => 'Toestemming', 'kern' => true, 'verplicht' => true, 'container' => 'check', 'label_bewerkbaar' => false, 'hint_bewerkbaar' => false, 'placeholder' => false, 'volgorde' => false, 'uitleg_kern' => 'Wettelijk verplicht. De tekst pas je aan in het blok Contact.' ),
				),
			),
			'vooraanmelding' => array(
				'titel'     => 'Vooraanmelding',
				'uitleg'    => 'Het korte aanmeldformulier zonder account (blok Vooraanmelding). De kop, keuzes, knop en toestemmingstekst pas je aan in het blok zelf.',
				'pagina'    => '',
				'actie'     => 'vooraanmelden',
				'context'   => 'vooraanmelding',
				'opslag'    => '',
				'flash'     => array( 'doelgroep', 'naam', 'email', 'bedrijf', 'functie', 'akkoord' ),
				'groepen'   => array( 'velden' => 'Velden' ),
				'velden'    => array(
					'naam'    => array( 'label' => 'Je naam', 'kern' => true, 'verplicht' => true, 'uitleg_kern' => 'Nodig om iemand uit te nodigen.' ),
					'email'   => array( 'label' => 'E-mailadres', 'kern' => true, 'verplicht' => true, 'uitleg_kern' => 'Nodig om iemand uit te nodigen.' ),
					'bedrijf' => array( 'label' => 'Bedrijf', 'alleen_als' => array( 'doelgroep', 'werkgever' ), 'uitleg' => 'Alleen voor werkgevers. Verbergen kan ook in het blok.' ),
					'functie' => array( 'label' => 'Je vak / Je functie', 'label_bewerkbaar' => false, 'hint_bewerkbaar' => false, 'uitleg' => 'Label en hulptekst wisselen mee met de keuze, en staan vast.' ),
				),
			),
		)
	);
}

/** Soorten extra velden. */
function deedni_formulier_veldtypen() {
	return array(
		'tekst'    => 'Tekst (één regel)',
		'tekstvak' => 'Tekstvak (meer regels)',
		'keuze'    => 'Keuzelijst',
		'vinkje'   => 'Vinkje (ja/nee)',
		'getal'    => 'Getal',
		'email'    => 'E-mailadres',
		'telefoon' => 'Telefoonnummer',
		'datum'    => 'Datum',
	);
}

/** Welk formulier hoort bij deze deedni_action? */
function deedni_formulier_bij_actie( $actie ) {
	foreach ( deedni_formulieren_defs() as $sleutel => $def ) {
		if ( $def['actie'] === $actie ) {
			return $sleutel;
		}
	}
	return '';
}

/* =========================================================================
 * Instellingen lezen
 * ========================================================================= */

function deedni_formulieren_opgeslagen() {
	$v = get_option( 'deedni_formulieren', array() );
	return is_array( $v ) ? $v : array();
}

/**
 * Hoe het formulier nu is ingesteld: de velden met standaard + jouw aanpassingen, en de extra velden.
 * Elk veld krijgt: label, label_w, hint, hint_w, placeholder, verplicht (bool), verborgen (bool), volgorde (int).
 */
function deedni_formulier_config( $form ) {
	$defs = deedni_formulieren_defs();
	if ( ! isset( $defs[ $form ] ) ) {
		return null;
	}
	$def    = $defs[ $form ];
	$eigen  = deedni_formulieren_opgeslagen();
	$eigen  = isset( $eigen[ $form ] ) && is_array( $eigen[ $form ] ) ? $eigen[ $form ] : array();
	$groep1 = array_keys( $def['groepen'] )[0];
	$velden = array();
	$i      = 0;
	foreach ( $def['velden'] as $sleutel => $v ) {
		$v = wp_parse_args(
			$v,
			array(
				'label'            => $sleutel,
				'label_w'          => '',
				'hint'             => '',
				'groep'            => $groep1,
				'kern'             => false,
				'verplicht'        => false,
				'naam'             => $sleutel,
				'container'        => 'field',
				'placeholder'      => true,
				'volgorde'         => true,
				'label_bewerkbaar' => true,
				'hint_bewerkbaar'  => true,
				'kan_verplicht'    => true,
				'varianten'        => false,
				'alleen_als'       => null,
				'uitleg_kern'      => '',
				'uitleg'           => '',
			)
		);
		$e = isset( $eigen['velden'][ $sleutel ] ) && is_array( $eigen['velden'][ $sleutel ] ) ? $eigen['velden'][ $sleutel ] : array();
		$velden[ $sleutel ] = array(
			'def'         => $v,
			'eigen_label' => isset( $e['label'] ) ? (string) $e['label'] : '',
			'eigen_label_w' => isset( $e['label_w'] ) ? (string) $e['label_w'] : '',
			'eigen_hint'  => isset( $e['hint'] ) ? (string) $e['hint'] : '',
			'eigen_hint_w' => isset( $e['hint_w'] ) ? (string) $e['hint_w'] : '',
			'placeholder' => isset( $e['placeholder'] ) ? (string) $e['placeholder'] : '',
			// Kernvelden: verplicht en zichtbaar staan vast.
			'verplicht'   => $v['kern'] ? $v['verplicht'] : ( $v['kan_verplicht'] && ! empty( $e['verplicht'] ) ),
			'verborgen'   => ! $v['kern'] && ! empty( $e['verborgen'] ),
			'volgorde'    => isset( $e['volgorde'] ) ? (int) $e['volgorde'] : ( $i + 1 ) * 10,
		);
		++$i;
	}
	$extra = array();
	if ( $def['opslag'] && ! empty( $eigen['extra'] ) && is_array( $eigen['extra'] ) ) {
		foreach ( $eigen['extra'] as $slug => $x ) {
			if ( ! is_array( $x ) || empty( $x['label'] ) || ! isset( deedni_formulier_veldtypen()[ isset( $x['type'] ) ? $x['type'] : '' ] ) ) {
				continue;
			}
			$extra[ $slug ] = wp_parse_args(
				$x,
				array(
					'type'        => 'tekst',
					'label'       => '',
					'hint'        => '',
					'placeholder' => '',
					'opties'      => array(),
					'verplicht'   => false,
					'groep'       => isset( $def['groepen'][ isset( $x['groep'] ) ? $x['groep'] : '' ] ) ? $x['groep'] : $groep1,
					'volgorde'    => 1000,
					'rol'         => '',
				)
			);
			if ( ! isset( $def['groepen'][ $extra[ $slug ]['groep'] ] ) ) {
				$extra[ $slug ]['groep'] = $groep1;
			}
		}
	}
	return array(
		'def'        => $def,
		'velden'     => $velden,
		'extra'      => $extra,
		'aangepast'  => ! empty( $eigen ),
	);
}

/* =========================================================================
 * Extra velden: waarden lezen, opslaan en tonen
 * ========================================================================= */

/** De extra velden uit het verstuurde formulier, opgeschoond. */
function deedni_formulier_extra_uit_post( $cfg ) {
	$post    = isset( $_POST['dn_extra'] ) && is_array( $_POST['dn_extra'] ) ? wp_unslash( $_POST['dn_extra'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
	$waarden = array();
	foreach ( $cfg['extra'] as $slug => $x ) {
		$v = isset( $post[ $slug ] ) && is_string( $post[ $slug ] ) ? $post[ $slug ] : '';
		switch ( $x['type'] ) {
			case 'tekstvak':
				$v = mb_substr( sanitize_textarea_field( $v ), 0, 2000 );
				break;
			case 'vinkje':
				$v = '' !== $v ? '1' : '';
				break;
			case 'email':
				$v = sanitize_email( $v );
				break;
			case 'keuze':
				$v = sanitize_text_field( $v );
				$v = in_array( $v, (array) $x['opties'], true ) ? $v : '';
				break;
			case 'datum':
				$v = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : '';
				break;
			case 'getal':
				$v = trim( sanitize_text_field( $v ) );
				$v = '' === $v ? '' : ( is_numeric( str_replace( array( '.', ',' ), array( '', '.' ), $v ) ) ? $v : '' );
				break;
			default:
				$v = mb_substr( sanitize_text_field( $v ), 0, 300 );
		}
		$waarden[ $slug ] = $v;
	}
	return $waarden;
}

/** Een leesbaar lijstje (veilige HTML) van ingevulde extra velden, voor mails en wp-admin. */
function deedni_formulier_extra_html( $form, $waarden ) {
	if ( ! is_array( $waarden ) || ! $waarden ) {
		return '';
	}
	$cfg = deedni_formulier_config( $form );
	$h   = '';
	foreach ( $waarden as $slug => $v ) {
		if ( '' === (string) $v ) {
			continue;
		}
		$label = isset( $cfg['extra'][ $slug ] ) ? $cfg['extra'][ $slug ]['label'] : $slug;
		$type  = isset( $cfg['extra'][ $slug ] ) ? $cfg['extra'][ $slug ]['type'] : 'tekst';
		if ( 'vinkje' === $type ) {
			$v = 'Ja';
		} elseif ( 'datum' === $type ) {
			$v = date_i18n( 'j F Y', strtotime( $v ) );
		}
		$label = rtrim( $label, ': ' );
		$h    .= '<li><strong>' . esc_html( $label ) . ( '?' === substr( $label, -1 ) ? '' : ':' ) . '</strong> ' . nl2br( esc_html( $v ) ) . '</li>';
	}
	return $h ? '<ul style="margin:0;padding-left:20px">' . $h . '</ul>' : '';
}

/** Opgeslagen extra velden van een profiel (kandidaat of bedrijf). */
function deedni_formulier_extra_van( $form ) {
	$cfg = deedni_formulier_config( $form );
	if ( ! $cfg || ! $cfg['extra'] ) {
		return array();
	}
	if ( 'kandidaat' === $cfg['def']['opslag'] || 'bedrijf' === $cfg['def']['opslag'] ) {
		$id = deedni_profile_id( get_current_user_id(), $cfg['def']['opslag'] );
		$v  = $id ? get_post_meta( $id, 'deedni_extra', true ) : array();
		return is_array( $v ) ? $v : array();
	}
	return array();
}

/* =========================================================================
 * Controle op de server (vóór de gewone verwerking van het formulier)
 * ========================================================================= */

add_action( 'template_redirect', 'deedni_formulieren_controle', 4 );

function deedni_formulieren_controle() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || empty( $_POST['deedni_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$form = deedni_formulier_bij_actie( sanitize_key( wp_unslash( $_POST['deedni_action'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$cfg  = $form ? deedni_formulier_config( $form ) : null;
	if ( ! $cfg || ! $cfg['aangepast'] ) {
		return;
	}
	if ( 'aanbod_sturen' === $form && ! empty( $_POST['alleen_bewaren'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return; // "Bewaar voor later" stuurt niets.
	}
	$def       = $cfg['def'];
	$rol       = ! empty( $def['rol_veld'] ) ? deedni_post( $def['rol_veld'] ) : '';
	$alleen_publiceren = 'profiel' === $form;
	$publiceert = $alleen_publiceren && '1' === deedni_post( 'publiceren' );
	$errors    = array();

	foreach ( $cfg['velden'] as $sleutel => $v ) {
		if ( $v['def']['kern'] || ! $v['verplicht'] || $v['verborgen'] ) {
			continue; // Kernvelden controleert de site zelf al.
		}
		if ( $alleen_publiceren && ! $publiceert ) {
			continue;
		}
		if ( $v['def']['alleen_als'] && deedni_post( $v['def']['alleen_als'][0] ) !== $v['def']['alleen_als'][1] ) {
			continue;
		}
		$naam = rtrim( $v['def']['naam'], '[]' );
		if ( ! isset( $_POST[ $naam ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			continue; // Het veld stond niet in het formulier (bijvoorbeeld uitgezet in het blok).
		}
		$waarde = wp_unslash( $_POST[ $naam ] ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		if ( is_array( $waarde ) ? ! array_filter( $waarde ) : '' === trim( (string) $waarde ) ) {
			$errors[ $sleutel ] = 'Vul ' . deedni_formulier_klein( deedni_formulier_label( $v, $rol ) ) . ' in.';
		}
	}

	$waarden = deedni_formulier_extra_uit_post( $cfg );
	$ruw     = isset( $_POST['dn_extra'] ) && is_array( $_POST['dn_extra'] ) ? wp_unslash( $_POST['dn_extra'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
	foreach ( $cfg['extra'] as $slug => $x ) {
		if ( $x['rol'] && $x['rol'] !== $rol ) {
			$waarden[ $slug ] = '';
			continue;
		}
		$gestuurd = isset( $ruw[ $slug ] ) && is_string( $ruw[ $slug ] ) && '' !== trim( $ruw[ $slug ] );
		if ( $gestuurd && '' === $waarden[ $slug ] ) {
			$errors[ 'dn_extra_' . $slug ] = 'Controleer ' . deedni_formulier_klein( $x['label'] ) . '.';
		} elseif ( $x['verplicht'] && '' === $waarden[ $slug ] && ( ! $alleen_publiceren || $publiceert ) ) {
			$errors[ 'dn_extra_' . $slug ] = 'vinkje' === $x['type'] ? 'Zet een vinkje bij ' . deedni_formulier_klein( $x['label'] ) . '.' : 'Vul ' . deedni_formulier_klein( $x['label'] ) . ' in.';
		}
	}

	if ( $errors && $alleen_publiceren ) {
		// Profiel: wel opslaan, niet publiceren. Zo raakt niemand ingevuld werk kwijt.
		unset( $_POST['publiceren'] ); // phpcs:ignore WordPress.Security.NonceVerification
		set_transient( 'deedni_formulier_melding_' . get_current_user_id(), 'Je profiel is opgeslagen, maar staat nog niet online. ' . implode( ' ', $errors ), 10 * MINUTE_IN_SECONDS );
		foreach ( array_keys( $errors ) as $k ) {
			if ( 0 === strpos( $k, 'dn_extra_' ) && '' !== $waarden[ substr( $k, 9 ) ] ) {
				unset( $errors[ $k ] ); // Fout ingevuld: niet opslaan.
				$waarden[ substr( $k, 9 ) ] = '';
			}
		}
		$errors = array();
	}

	if ( $errors ) {
		if ( ! deedni_is_ajax() ) {
			$errors = array( 'form' => 'Er ontbreekt nog iets. Kijk de velden met een rode tekst na.' ) + $errors;
		}
		$data = array();
		foreach ( isset( $def['flash'] ) ? $def['flash'] : array() as $k ) {
			$data[ $k ] = 'akkoord' === $k ? ! empty( $_POST['akkoord'] ) : deedni_post( $k, 'email' === $k ? 'email' : 'text' ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		$GLOBALS['deedni_formulier_post'][ $form ] = $waarden;
		deedni_fout( $def['context'], $errors, $data );
		remove_action( 'template_redirect', 'deedni_handle_forms', 5 );
		return;
	}

	$GLOBALS['deedni_formulier_extra'][ $form ] = $waarden;
	if ( ! $cfg['extra'] ) {
		return;
	}
	// Profiel en bedrijf: meteen opslaan (de gewone verwerking stuurt daarna door).
	if ( 'profiel' === $form && deedni_nonce_ok( 'deedni_profiel' ) && deedni_is_kandidaat() ) {
		deedni_formulier_extra_bewaar_post( deedni_ensure_profile( get_current_user_id(), 'kandidaat' ), 'deedni_extra', $waarden );
	} elseif ( 'bedrijf' === $form && deedni_nonce_ok( 'deedni_bedrijf' ) && ( deedni_is_werkgever() || deedni_is_beheerder() ) ) {
		deedni_formulier_extra_bewaar_post( deedni_ensure_profile( get_current_user_id(), 'bedrijf' ), 'deedni_extra', $waarden );
	}
}

/** Waarden samenvoegen met wat er al stond (velden die er niet meer zijn blijven bewaard tot je ze wist). */
function deedni_formulier_extra_bewaar_post( $post_id, $meta, $waarden ) {
	if ( ! $post_id ) {
		return;
	}
	$oud = get_post_meta( $post_id, $meta, true );
	update_post_meta( $post_id, $meta, array_merge( is_array( $oud ) ? $oud : array(), $waarden ) );
}

/** "Telefoon" wordt "telefoon" in een zin, maar "KvK-nummer" blijft staan. */
function deedni_formulier_klein( $tekst ) {
	$a = mb_substr( $tekst, 0, 1 );
	$b = mb_substr( $tekst, 1, 1 );
	return ( $b && mb_strtolower( $b ) === $b && mb_strtoupper( $b ) !== $b ) ? mb_strtolower( $a ) . mb_substr( $tekst, 1 ) : $tekst;
}

/** Het label zoals de bezoeker het zag. */
function deedni_formulier_label( $v, $rol = '' ) {
	if ( $v['def']['varianten'] && 'werkgever' === $rol ) {
		return '' !== $v['eigen_label_w'] ? $v['eigen_label_w'] : $v['def']['label_w'];
	}
	return '' !== $v['eigen_label'] ? $v['eigen_label'] : $v['def']['label'];
}

/* Extra velden opslaan bij het aanbod, de reactie en het account. */
add_action( 'deedni_aanbod_verstuurd', 'deedni_formulier_bewaar_aanbod' );
add_action( 'deedni_aanbod_aangepast', 'deedni_formulier_bewaar_aanbod' );
function deedni_formulier_bewaar_aanbod( $aanbod_id ) {
	if ( isset( $GLOBALS['deedni_formulier_extra']['aanbod_sturen'] ) && $GLOBALS['deedni_formulier_extra']['aanbod_sturen'] ) {
		update_post_meta( $aanbod_id, 'deedni_extra_aanbod', $GLOBALS['deedni_formulier_extra']['aanbod_sturen'] );
	}
}

add_action(
	'deedni_aanbod_tegenvoorstel',
	function ( $aanbod_id ) {
		if ( ! empty( $GLOBALS['deedni_formulier_extra']['aanbod_jamaar'] ) ) {
			update_post_meta( $aanbod_id, 'deedni_extra_reactie', $GLOBALS['deedni_formulier_extra']['aanbod_jamaar'] );
		}
	}
);

add_action(
	'user_register',
	function ( $user_id ) {
		if ( ! empty( $GLOBALS['deedni_formulier_extra']['registreren'] ) ) {
			update_user_meta( $user_id, 'deedni_extra_registratie', $GLOBALS['deedni_formulier_extra']['registreren'] );
		}
	}
);

/* =========================================================================
 * De HTML van de formulieren aanpassen
 * ========================================================================= */

/** Formulieren in deedni-blokken. */
add_filter(
	'render_block',
	function ( $html, $block ) {
		if ( empty( $block['blockName'] ) || 0 !== strpos( $block['blockName'], 'deedni/' ) || false === strpos( (string) $html, 'deedni_action' ) ) {
			return $html;
		}
		return deedni_formulieren_html( $html );
	},
	20,
	2
);

/** Pop-ups (aanbod sturen, ja maar) komen onderaan de pagina in wp_footer (prioriteit 5). */
add_action(
	'wp_footer',
	function () {
		if ( ! empty( $GLOBALS['deedni_popups'] ) ) {
			ob_start();
			$GLOBALS['deedni_formulieren_ob'] = true;
		}
	},
	4
);
add_action(
	'wp_footer',
	function () {
		if ( ! empty( $GLOBALS['deedni_formulieren_ob'] ) ) {
			$GLOBALS['deedni_formulieren_ob'] = false;
			echo deedni_formulieren_html( ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	},
	6
);

/** Alle deedni-formulieren in dit stuk HTML aanpassen (alleen formulieren met aanpassingen). */
function deedni_formulieren_html( $html ) {
	$opgeslagen = deedni_formulieren_opgeslagen();
	if ( ! $opgeslagen || ! class_exists( 'DOMDocument' ) ) {
		return $html;
	}
	return preg_replace_callback(
		'#<form\b[^>]*>.*?</form>#s',
		function ( $m ) use ( $opgeslagen ) {
			if ( ! preg_match( '/name="deedni_action"\s+value="([a-z_]+)"/', $m[0], $a ) ) {
				return $m[0];
			}
			$form = deedni_formulier_bij_actie( $a[1] );
			if ( ! $form || empty( $opgeslagen[ $form ] ) ) {
				return $m[0];
			}
			$nieuw = deedni_formulier_dom( $m[0], $form );
			return null === $nieuw ? $m[0] : $nieuw;
		},
		(string) $html
	);
}

/* ---------- Kleine DOM-hulpjes ---------- */

function deedni_dom_heeft_klasse( $el, $klasse ) {
	return $el instanceof DOMElement && in_array( $klasse, preg_split( '/\s+/', (string) $el->getAttribute( 'class' ) ), true );
}

function deedni_dom_dichtstbij( $el, $test ) {
	for ( $n = $el; $n instanceof DOMElement; $n = $n->parentNode ) {
		if ( $test( $n ) ) {
			return $n;
		}
	}
	return null;
}

/** Tekst van een label vervangen en de tags erin (zoals "Na jouw ja") laten staan. */
function deedni_dom_zet_label( $doc, $el, $tekst ) {
	foreach ( iterator_to_array( $el->childNodes ) as $c ) {
		if ( XML_TEXT_NODE === $c->nodeType ) {
			$el->removeChild( $c );
		}
	}
	$heeft_kinderen = (bool) $el->firstChild;
	$t              = $doc->createTextNode( $tekst . ( $heeft_kinderen ? ' ' : '' ) );
	$heeft_kinderen ? $el->insertBefore( $t, $el->firstChild ) : $el->appendChild( $t );
}

/** Een stukje HTML als DOM-knopen. */
function deedni_dom_fragment( $doc, $html ) {
	$tmp = new DOMDocument();
	$tmp->loadHTML( '<?xml encoding="UTF-8"?><html><body><div id="dn-tmp">' . $html . '</div></body></html>', LIBXML_HTML_NODEFDTD | LIBXML_NONET );
	$frag = $doc->createDocumentFragment();
	foreach ( iterator_to_array( $tmp->getElementById( 'dn-tmp' )->childNodes ) as $c ) {
		$frag->appendChild( $doc->importNode( $c, true ) );
	}
	return $frag;
}

/** Het element van een veld: div.field, de fieldset of het vinkje. */
function deedni_formulier_container( $control, $soort ) {
	if ( 'fieldset' === $soort ) {
		return deedni_dom_dichtstbij( $control, function ( $n ) { return 'fieldset' === $n->nodeName; } );
	}
	if ( 'check' === $soort ) {
		return deedni_dom_dichtstbij( $control, function ( $n ) { return 'label' === $n->nodeName; } );
	}
	return deedni_dom_dichtstbij( $control, function ( $n ) { return deedni_dom_heeft_klasse( $n, 'field' ); } );
}

/** De HTML van één extra veld. */
function deedni_formulier_extra_veld_html( $form, $slug, $x, $waarde, $fout, $html_required ) {
	$id   = 'dn-x-' . $form . '-' . $slug;
	$naam = 'dn_extra[' . $slug . ']';
	$req  = $x['verplicht'] && $html_required && ! $x['rol'] ? ' required' : '';
	$desc = array();
	if ( $x['hint'] ) {
		$desc[] = $id . '-hint';
	}
	if ( $fout ) {
		$desc[] = $id . '-fout';
	}
	$aria = ( $desc ? ' aria-describedby="' . esc_attr( implode( ' ', $desc ) ) . '"' : '' ) . ( $fout ? ' aria-invalid="true"' : '' ) . ( $x['verplicht'] ? ' aria-required="true"' : '' );
	$ph   = $x['placeholder'] ? ' placeholder="' . esc_attr( $x['placeholder'] ) . '"' : '';
	$h    = '<div class="field dn-extra" data-dn-extra="' . esc_attr( $slug ) . '"' . ( $x['rol'] ? ' data-dn-rol="' . esc_attr( $x['rol'] ) . '"' : '' ) . '>';
	$ster = $x['verplicht'] ? '' : '';
	if ( 'vinkje' === $x['type'] ) {
		$h .= '<label class="check"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $naam ) . '" value="1"' . checked( '' !== (string) $waarde, true, false ) . $req . $aria . '> ' . esc_html( $x['label'] ) . $ster . '</label>';
	} else {
		$h .= '<label for="' . esc_attr( $id ) . '">' . esc_html( $x['label'] ) . $ster . '</label>';
		if ( 'tekstvak' === $x['type'] ) {
			$h .= '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $naam ) . '"' . $ph . $req . $aria . '>' . esc_textarea( (string) $waarde ) . '</textarea>';
		} elseif ( 'keuze' === $x['type'] ) {
			$h .= '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $naam ) . '"' . $req . $aria . '><option value="">' . esc_html( $x['placeholder'] ? $x['placeholder'] : 'Maak een keuze' ) . '</option>';
			foreach ( (array) $x['opties'] as $o ) {
				$h .= '<option value="' . esc_attr( $o ) . '"' . selected( (string) $waarde, (string) $o, false ) . '>' . esc_html( $o ) . '</option>';
			}
			$h .= '</select>';
		} else {
			$typen = array( 'getal' => 'text', 'email' => 'email', 'telefoon' => 'tel', 'datum' => 'date', 'tekst' => 'text' );
			$extra = 'getal' === $x['type'] ? ' inputmode="decimal"' : ( 'telefoon' === $x['type'] ? ' autocomplete="tel"' : '' );
			$h    .= '<input type="' . esc_attr( $typen[ $x['type'] ] ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $naam ) . '" value="' . esc_attr( (string) $waarde ) . '"' . $ph . $extra . $req . $aria . '>';
		}
	}
	if ( $x['hint'] ) {
		$h .= '<span class="hint" id="' . esc_attr( $id ) . '-hint">' . esc_html( $x['hint'] ) . '</span>';
	}
	if ( $fout ) {
		$h .= '<span class="field-error" id="' . esc_attr( $id ) . '-fout">' . esc_html( $fout ) . '</span>';
	}
	return $h . '</div>';
}

/**
 * Eén formulier aanpassen. Geeft de nieuwe HTML, of null als er iets misgaat (dan blijft het origineel staan).
 */
function deedni_formulier_dom( $form_html, $form ) {
	$cfg = deedni_formulier_config( $form );
	if ( ! $cfg ) {
		return null;
	}
	$def   = $cfg['def'];
	$vorig = libxml_use_internal_errors( true );
	$doc   = new DOMDocument();
	$ok    = $doc->loadHTML( '<?xml encoding="UTF-8"?><html><body><div id="dn-root">' . $form_html . '</div></body></html>', LIBXML_HTML_NODEFDTD | LIBXML_NONET );
	libxml_clear_errors();
	libxml_use_internal_errors( $vorig );
	$root = $ok ? $doc->getElementById( 'dn-root' ) : null;
	if ( ! $root ) {
		return null;
	}
	$xp      = new DOMXPath( $doc );
	$formel  = $xp->query( './/form', $root )->item( 0 );
	$flash   = deedni_flash( $def['context'] );
	$fouten  = $flash && ! empty( $flash['errors'] ) ? $flash['errors'] : array();
	$html_required = empty( $def['geen_html_required'] );
	$rol_nu  = '';
	if ( ! empty( $def['rol_veld'] ) ) {
		$gekozen = $xp->query( './/input[@name="' . $def['rol_veld'] . '"][@checked]', $root )->item( 0 );
		$rol_nu  = $gekozen ? $gekozen->getAttribute( 'value' ) : '';
	}

	// 1. Velden zoeken.
	$gevonden = array();
	foreach ( $cfg['velden'] as $sleutel => $v ) {
		$control = $xp->query( './/*[@name="' . $v['def']['naam'] . '"]', $root )->item( 0 );
		if ( ! $control ) {
			continue;
		}
		$container = deedni_formulier_container( $control, $v['def']['container'] );
		if ( $container ) {
			$gevonden[ $sleutel ] = array( 'control' => $control, 'container' => $container );
		}
	}

	// 2. Per groep de "ouder" bepalen (waar de velden van die groep in staan).
	$groep_ouder = array();
	foreach ( array_keys( $def['groepen'] ) as $groep ) {
		$lijst = array();
		foreach ( $gevonden as $sleutel => $g ) {
			if ( $cfg['velden'][ $sleutel ]['def']['groep'] === $groep && $cfg['velden'][ $sleutel ]['def']['volgorde'] ) {
				$lijst[] = $g['container'];
			}
		}
		if ( ! $lijst ) {
			continue;
		}
		$ouder = $lijst[0]->parentNode;
		foreach ( $lijst as $c ) {
			while ( $ouder && ! deedni_dom_bevat( $ouder, $c ) ) {
				$ouder = $ouder->parentNode;
			}
		}
		$groep_ouder[ $groep ] = $ouder;
	}

	// 3. Labels, hulpteksten, voorbeeldteksten en verplicht.
	foreach ( $gevonden as $sleutel => $g ) {
		$v   = $cfg['velden'][ $sleutel ];
		$d   = $v['def'];
		$con = $g['container'];
		$ctl = $g['control'];
		$label = null;
		if ( 'fieldset' === $d['container'] ) {
			$label = $xp->query( './legend', $con )->item( 0 );
		} elseif ( 'check' === $d['container'] ) {
			$label = $con;
		} else {
			$id    = $ctl->getAttribute( 'id' );
			$label = $id ? $xp->query( './/label[@for="' . $id . '"]', $con )->item( 0 ) : null;
			$label = $label ? $label : $xp->query( './/label', $con )->item( 0 );
		}
		if ( $d['varianten'] && $formel ) {
			// Account aanmaken: het label en de hulptekst wisselen mee met kandidaat/werkgever (app.js).
			$hints = json_decode( (string) $formel->getAttribute( 'data-hints' ), true );
			if ( is_array( $hints ) ) {
				if ( '' !== $v['eigen_label'] ) {
					$hints['kandidaat'][0] = $v['eigen_label'];
				}
				if ( '' !== $v['eigen_label_w'] ) {
					$hints['werkgever'][0] = $v['eigen_label_w'];
				}
				if ( '' !== $v['eigen_hint'] ) {
					$hints['kandidaat'][1] = $v['eigen_hint'];
				}
				if ( '' !== $v['eigen_hint_w'] ) {
					$hints['werkgever'][1] = $v['eigen_hint_w'];
				}
				$formel->setAttribute( 'data-hints', wp_json_encode( $hints ) );
				$nu = 'werkgever' === $rol_nu ? 'werkgever' : 'kandidaat';
				if ( $label && isset( $hints[ $nu ][0] ) ) {
					deedni_dom_zet_label( $doc, $label, $hints[ $nu ][0] );
				}
				$hint = $xp->query( './/*[@data-naam-hint]', $con )->item( 0 );
				if ( $hint && isset( $hints[ $nu ][1] ) ) {
					$hint->textContent = $hints[ $nu ][1];
				}
			}
		} else {
			if ( $label && $d['label_bewerkbaar'] && '' !== $v['eigen_label'] ) {
				deedni_dom_zet_label( $doc, $label, $v['eigen_label'] );
			}
			if ( $d['hint_bewerkbaar'] && '' !== $v['eigen_hint'] ) {
				$hint = $xp->query( './/*[contains(concat(" ", normalize-space(@class), " "), " hint ")]', $con )->item( 0 );
				if ( ! $hint ) {
					$hint = $xp->query( './/p[contains(concat(" ", normalize-space(@class), " "), " fine ")]', $con )->item( 0 );
				}
				if ( $hint ) {
					$hint->textContent = $v['eigen_hint'];
				} else {
					$hid  = ( $ctl->getAttribute( 'id' ) ? $ctl->getAttribute( 'id' ) : 'dn-' . $sleutel ) . '-hint';
					$hint = $doc->createElement( 'span' );
					$hint->setAttribute( 'class', 'hint' );
					$hint->setAttribute( 'id', $hid );
					$hint->appendChild( $doc->createTextNode( $v['eigen_hint'] ) );
					$na = $ctl->parentNode === $con ? $ctl->nextSibling : null;
					$na ? $con->insertBefore( $hint, $na ) : $con->appendChild( $hint );
					$ctl->setAttribute( 'aria-describedby', trim( $ctl->getAttribute( 'aria-describedby' ) . ' ' . $hid ) );
				}
			}
		}
		if ( $d['placeholder'] && '' !== $v['placeholder'] && in_array( $ctl->nodeName, array( 'input', 'textarea' ), true ) ) {
			$ctl->setAttribute( 'placeholder', $v['placeholder'] );
		}
		if ( ! $d['kern'] && $v['verplicht'] && ! $v['verborgen'] ) {
			if ( $html_required && ! $d['alleen_als'] ) {
				$ctl->setAttribute( 'required', 'required' );
			}
			$ctl->setAttribute( 'aria-required', 'true' );
			if ( $label ) {
				// "(optioneel)" weghalen.
				foreach ( iterator_to_array( $xp->query( './/*[contains(@class,"va-opt") or contains(@class,"fine")]', $label ) ) as $opt ) {
					if ( false !== stripos( $opt->textContent, 'optioneel' ) ) {
						$opt->parentNode->removeChild( $opt );
					}
				}
			}
		}
		if ( isset( $fouten[ $sleutel ] ) && ! $xp->query( './/*[contains(@class,"field-error")]', $con )->length ) {
			$fout = $doc->createElement( 'span' );
			$fout->setAttribute( 'class', 'field-error' );
			$fout->appendChild( $doc->createTextNode( wp_strip_all_tags( $fouten[ $sleutel ] ) ) );
			$con->appendChild( $fout );
			$ctl->setAttribute( 'aria-invalid', 'true' );
		}
	}

	// 4. Extra velden toevoegen, achter het laatste veld van hun groep.
	$opgeslagen = deedni_formulier_extra_van( $form );
	$gepost     = isset( $GLOBALS['deedni_formulier_post'][ $form ] ) ? $GLOBALS['deedni_formulier_post'][ $form ] : null;
	$extra_els  = array();
	foreach ( $cfg['extra'] as $slug => $x ) {
		$ouder = isset( $groep_ouder[ $x['groep'] ] ) ? $groep_ouder[ $x['groep'] ] : null;
		if ( ! $ouder ) {
			continue;
		}
		$waarde = is_array( $gepost ) && isset( $gepost[ $slug ] ) ? $gepost[ $slug ] : ( isset( $opgeslagen[ $slug ] ) ? $opgeslagen[ $slug ] : '' );
		$fout   = isset( $fouten[ 'dn_extra_' . $slug ] ) ? $fouten[ 'dn_extra_' . $slug ] : '';
		$frag   = deedni_dom_fragment( $doc, deedni_formulier_extra_veld_html( $form, $slug, $x, $waarde, $fout, $html_required ) );
		$el     = $frag->firstChild;
		if ( $x['rol'] && $rol_nu && $x['rol'] !== $rol_nu ) {
			$el->setAttribute( 'hidden', 'hidden' );
		}
		// Na de laatste eenheid van de groep in deze ouder.
		$laatste = null;
		foreach ( $gevonden as $sleutel => $g ) {
			if ( $cfg['velden'][ $sleutel ]['def']['groep'] === $x['groep'] && $cfg['velden'][ $sleutel ]['def']['volgorde'] ) {
				$e = deedni_dom_eenheid( $g['container'], $ouder );
				if ( $e && ( ! $laatste || deedni_dom_voor( $laatste, $e ) ) ) {
					$laatste = $e;
				}
			}
		}
		foreach ( $extra_els as $vorige ) {
			if ( $vorige->parentNode === $ouder && ( ! $laatste || deedni_dom_voor( $laatste, $vorige ) ) ) {
				$laatste = $vorige;
			}
		}
		if ( $laatste && $laatste->nextSibling ) {
			$ouder->insertBefore( $frag, $laatste->nextSibling );
		} else {
			$ouder->appendChild( $frag );
		}
		$extra_els[ $slug ] = $el;
	}

	// 5. Verborgen velden weghalen.
	foreach ( $gevonden as $sleutel => $g ) {
		if ( $cfg['velden'][ $sleutel ]['verborgen'] && $g['container']->parentNode ) {
			$rij = $g['container']->parentNode;
			$rij->removeChild( $g['container'] );
			if ( deedni_dom_heeft_klasse( $rij, 'fields' ) && ! $xp->query( './*', $rij )->length && $rij->parentNode ) {
				$rij->parentNode->removeChild( $rij );
			}
			unset( $gevonden[ $sleutel ] );
		}
	}

	// 6. Volgorde per groep. Velden die naast elkaar staan (een rij) verhuizen samen.
	foreach ( $groep_ouder as $groep => $ouder ) {
		$eenheden = array(); // spl_object_id => [element, volgorde]
		$plekken  = array();
		$items    = array();
		foreach ( $gevonden as $sleutel => $g ) {
			if ( $cfg['velden'][ $sleutel ]['def']['groep'] === $groep && $cfg['velden'][ $sleutel ]['def']['volgorde'] ) {
				$items[] = array( $g['container'], $cfg['velden'][ $sleutel ]['volgorde'] );
			}
		}
		foreach ( $cfg['extra'] as $slug => $x ) {
			if ( $x['groep'] === $groep && isset( $extra_els[ $slug ] ) ) {
				$items[] = array( $extra_els[ $slug ], (int) $x['volgorde'] );
			}
		}
		foreach ( $items as $item ) {
			$e = deedni_dom_eenheid( $item[0], $ouder );
			if ( ! $e ) {
				continue;
			}
			$k = spl_object_id( $e );
			if ( ! isset( $eenheden[ $k ] ) ) {
				$eenheden[ $k ] = array( $e, $item[1] );
			} else {
				$eenheden[ $k ][1] = min( $eenheden[ $k ][1], $item[1] );
			}
		}
		if ( count( $eenheden ) < 2 ) {
			continue;
		}
		// Plekken in documentvolgorde.
		foreach ( iterator_to_array( $ouder->childNodes ) as $c ) {
			if ( isset( $eenheden[ spl_object_id( $c ) ] ) ) {
				$plekken[] = $eenheden[ spl_object_id( $c ) ];
			}
		}
		$gesorteerd = $plekken;
		$index      = array();
		foreach ( $gesorteerd as $i => $p ) {
			$index[ spl_object_id( $p[0] ) ] = $i;
		}
		usort(
			$gesorteerd,
			function ( $a, $b ) use ( $index ) {
				return $a[1] === $b[1] ? $index[ spl_object_id( $a[0] ) ] - $index[ spl_object_id( $b[0] ) ] : $a[1] - $b[1];
			}
		);
		if ( wp_list_pluck( $gesorteerd, 0 ) === wp_list_pluck( $plekken, 0 ) ) {
			continue;
		}
		$markers = array();
		foreach ( $plekken as $p ) {
			$m = $doc->createComment( 'dn' );
			$ouder->insertBefore( $m, $p[0] );
			$markers[] = $m;
		}
		foreach ( $plekken as $p ) {
			$ouder->removeChild( $p[0] );
		}
		foreach ( $markers as $i => $m ) {
			$ouder->replaceChild( $gesorteerd[ $i ][0], $m );
		}
	}

	// 7. Melding bij een profiel dat niet gepubliceerd kon worden.
	if ( 'profiel' === $form && $formel && is_user_logged_in() ) {
		$melding = get_transient( 'deedni_formulier_melding_' . get_current_user_id() );
		if ( $melding ) {
			delete_transient( 'deedni_formulier_melding_' . get_current_user_id() );
			$formel->insertBefore( deedni_dom_fragment( $doc, deedni_notice( esc_html( $melding ), 'error' ) ), $formel->firstChild );
		}
	}

	// 8. Extra velden die alleen bij één rol horen: meewisselen met de keuze.
	if ( ! empty( $def['rol_veld'] ) && $formel && $xp->query( './/*[@data-dn-rol]', $root )->length ) {
		$script = $doc->createElement( 'script' );
		$script->appendChild( $doc->createTextNode( '(function(f){if(!f)return;function s(){var c=f.querySelector(\'input[name="' . esc_js( $def['rol_veld'] ) . '"]:checked\'),v=c?c.value:"";f.querySelectorAll("[data-dn-rol]").forEach(function(e){e.hidden=e.getAttribute("data-dn-rol")!==v;});}f.addEventListener("change",s);s();})(document.currentScript&&document.currentScript.closest("form"));' ) );
		$formel->appendChild( $script );
	}

	$uit = '';
	foreach ( iterator_to_array( $root->childNodes ) as $c ) {
		$uit .= $doc->saveHTML( $c );
	}
	return $uit;
}

/** Zit $kind in $ouder (of is het $ouder zelf)? */
function deedni_dom_bevat( $ouder, $kind ) {
	for ( $n = $kind; $n; $n = $n->parentNode ) {
		if ( $n === $ouder ) {
			return true;
		}
	}
	return false;
}

/** Het kind van $ouder waar $el in zit (de "eenheid" die verplaatst wordt). */
function deedni_dom_eenheid( $el, $ouder ) {
	for ( $n = $el; $n && $n->parentNode; $n = $n->parentNode ) {
		if ( $n->parentNode === $ouder ) {
			return $n;
		}
	}
	return null;
}

/** Staat $a voor $b (zelfde ouder)? */
function deedni_dom_voor( $a, $b ) {
	for ( $n = $a->nextSibling; $n; $n = $n->nextSibling ) {
		if ( $n === $b ) {
			return true;
		}
	}
	return false;
}

/* =========================================================================
 * Extra velden zien in wp-admin
 * ========================================================================= */

add_action(
	'add_meta_boxes',
	function () {
		foreach ( array( 'kandidaat', 'bedrijf', 'aanbod' ) as $type ) {
			add_meta_box(
				'deedni-extra-velden',
				'Extra velden',
				function ( $post ) use ( $type ) {
					$blokken = array();
					if ( 'kandidaat' === $type ) {
						$blokken['Profiel'] = deedni_formulier_extra_html( 'profiel', get_post_meta( $post->ID, 'deedni_extra', true ) );
						$blokken['Account aanmaken'] = deedni_formulier_extra_html( 'registreren', get_user_meta( $post->post_author, 'deedni_extra_registratie', true ) );
					} elseif ( 'bedrijf' === $type ) {
						$blokken['Bedrijfsprofiel'] = deedni_formulier_extra_html( 'bedrijf', get_post_meta( $post->ID, 'deedni_extra', true ) );
						$blokken['Account aanmaken'] = deedni_formulier_extra_html( 'registreren', get_user_meta( $post->post_author, 'deedni_extra_registratie', true ) );
					} else {
						$blokken['Aanbod'] = deedni_formulier_extra_html( 'aanbod_sturen', get_post_meta( $post->ID, 'deedni_extra_aanbod', true ) );
						$blokken['Ja, maar...'] = deedni_formulier_extra_html( 'aanbod_jamaar', get_post_meta( $post->ID, 'deedni_extra_reactie', true ) );
					}
					$blokken = array_filter( $blokken );
					if ( ! $blokken ) {
						echo '<p class="description">Geen extra velden ingevuld. Extra velden voeg je toe bij <a href="' . esc_url( admin_url( 'admin.php?page=deedni-formulieren' ) ) . '">Formulieren &amp; mail &gt; Formulieren</a>.</p>';
						return;
					}
					foreach ( $blokken as $kop => $html ) {
						echo '<p><strong>' . esc_html( $kop ) . '</strong></p>' . wp_kses_post( $html );
					}
				},
				$type,
				'side'
			);
		}
	}
);

function deedni_formulier_extra_gebruiker( $user ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$html = deedni_formulier_extra_html( 'registreren', get_user_meta( $user->ID, 'deedni_extra_registratie', true ) );
	if ( $html ) {
		echo '<h2>Extra velden bij het aanmaken van het account</h2>' . wp_kses_post( $html );
	}
}
add_action( 'show_user_profile', 'deedni_formulier_extra_gebruiker' );
add_action( 'edit_user_profile', 'deedni_formulier_extra_gebruiker' );

/* =========================================================================
 * wp-admin: Formulieren & mail > Formulieren
 * ========================================================================= */

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'deedni-maillog', 'Formulieren', 'Formulieren', 'manage_options', 'deedni-formulieren', 'deedni_formulieren_pagina' );
	},
	12
);

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( false !== strpos( (string) $hook, 'deedni-formulieren' ) ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
		}
	}
);

add_action(
	'admin_post_deedni_formulier',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.', 403 );
		}
		$form = isset( $_POST['form'] ) ? sanitize_key( wp_unslash( $_POST['form'] ) ) : '';
		check_admin_referer( 'deedni_formulier_' . $form );
		$cfg = deedni_formulier_config( $form );
		if ( ! $cfg ) {
			wp_die( 'Onbekend formulier.' );
		}
		$alles = deedni_formulieren_opgeslagen();
		$terug = admin_url( 'admin.php?page=deedni-formulieren&form=' . $form );
		if ( ! empty( $_POST['herstel'] ) ) {
			unset( $alles[ $form ] );
			update_option( 'deedni_formulieren', $alles, false );
			wp_safe_redirect( add_query_arg( 'deedni_klaar', 'hersteld', $terug ) );
			exit;
		}
		$tekst = function ( $v, $max = 200 ) {
			return mb_substr( sanitize_text_field( (string) $v ), 0, $max );
		};
		$in     = isset( $_POST['velden'] ) && is_array( $_POST['velden'] ) ? wp_unslash( $_POST['velden'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$velden = array();
		foreach ( $cfg['velden'] as $sleutel => $v ) {
			$d = $v['def'];
			$e = isset( $in[ $sleutel ] ) && is_array( $in[ $sleutel ] ) ? $in[ $sleutel ] : array();
			$n = array();
			foreach ( array( 'label', 'label_w', 'hint', 'hint_w', 'placeholder' ) as $k ) {
				$waarde = isset( $e[ $k ] ) ? $tekst( $e[ $k ] ) : '';
				$std    = 'label' === $k ? $d['label'] : ( 'label_w' === $k ? $d['label_w'] : ( 'hint' === $k ? $d['hint'] : '' ) );
				if ( '' !== $waarde && $waarde !== $std ) {
					$n[ $k ] = $waarde;
				}
			}
			if ( ! $d['kern'] && $d['kan_verplicht'] && ! empty( $e['verplicht'] ) ) {
				$n['verplicht'] = 1;
			}
			if ( ! $d['kern'] && ! empty( $e['verborgen'] ) ) {
				$n['verborgen'] = 1;
			}
			if ( isset( $e['volgorde'] ) && (int) $e['volgorde'] !== $v['volgorde'] ) {
				$n['volgorde'] = (int) $e['volgorde'];
			} elseif ( isset( $e['volgorde'] ) && isset( $alles[ $form ]['velden'][ $sleutel ]['volgorde'] ) ) {
				$n['volgorde'] = (int) $e['volgorde'];
			}
			if ( $n ) {
				$velden[ $sleutel ] = $n;
			}
		}

		$extra  = array();
		$typen  = deedni_formulier_veldtypen();
		$in_x   = isset( $_POST['extra'] ) && is_array( $_POST['extra'] ) ? wp_unslash( $_POST['extra'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$opties = function ( $v ) use ( $tekst ) {
			return array_values( array_unique( array_filter( array_map( function ( $r ) use ( $tekst ) { return $tekst( $r, 100 ); }, preg_split( '/\R/', (string) $v ) ) ) ) );
		};
		$rollen = isset( $cfg['def']['rollen'] ) ? $cfg['def']['rollen'] : array();
		foreach ( $cfg['extra'] as $slug => $x ) {
			$e = isset( $in_x[ $slug ] ) && is_array( $in_x[ $slug ] ) ? $in_x[ $slug ] : array();
			if ( ! empty( $e['weg'] ) ) {
				continue;
			}
			$extra[ $slug ] = array(
				'type'        => $x['type'],
				'label'       => isset( $e['label'] ) && '' !== $tekst( $e['label'] ) ? $tekst( $e['label'] ) : $x['label'],
				'hint'        => isset( $e['hint'] ) ? $tekst( $e['hint'] ) : '',
				'placeholder' => isset( $e['placeholder'] ) ? $tekst( $e['placeholder'] ) : '',
				'opties'      => 'keuze' === $x['type'] ? $opties( isset( $e['opties'] ) ? $e['opties'] : '' ) : array(),
				'verplicht'   => ! empty( $e['verplicht'] ),
				'groep'       => $x['groep'],
				'volgorde'    => isset( $e['volgorde'] ) ? (int) $e['volgorde'] : (int) $x['volgorde'],
				'rol'         => isset( $e['rol'] ) && isset( $rollen[ $e['rol'] ] ) ? $e['rol'] : '',
			);
		}
		$nieuw = isset( $_POST['nieuw'] ) && is_array( $_POST['nieuw'] ) ? wp_unslash( $_POST['nieuw'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$fout  = '';
		if ( $cfg['def']['opslag'] && ! empty( $nieuw['label'] ) ) {
			$type  = isset( $nieuw['type'] ) && isset( $typen[ $nieuw['type'] ] ) ? $nieuw['type'] : 'tekst';
			$label = $tekst( $nieuw['label'] );
			$basis = substr( sanitize_key( str_replace( array( ' ', '-' ), '_', remove_accents( strtolower( $label ) ) ) ), 0, 30 );
			$basis = $basis ? $basis : 'veld';
			$slug  = $basis;
			for ( $i = 2; isset( $extra[ $slug ] ) || isset( $cfg['velden'][ $slug ] ); $i++ ) {
				$slug = $basis . '_' . $i;
			}
			$groep = isset( $nieuw['groep'] ) && isset( $cfg['def']['groepen'][ $nieuw['groep'] ] ) ? $nieuw['groep'] : array_keys( $cfg['def']['groepen'] )[0];
			$o     = 'keuze' === $type ? $opties( isset( $nieuw['opties'] ) ? $nieuw['opties'] : '' ) : array();
			if ( 'keuze' === $type && ! $o ) {
				$fout = 'opties';
			} else {
				$max = 0;
				foreach ( $cfg['velden'] as $v ) {
					$max = $v['def']['groep'] === $groep ? max( $max, $v['volgorde'] ) : $max;
				}
				foreach ( $extra as $x ) {
					$max = $x['groep'] === $groep ? max( $max, $x['volgorde'] ) : $max;
				}
				$extra[ $slug ] = array(
					'type'        => $type,
					'label'       => $label,
					'hint'        => isset( $nieuw['hint'] ) ? $tekst( $nieuw['hint'] ) : '',
					'placeholder' => '',
					'opties'      => $o,
					'verplicht'   => ! empty( $nieuw['verplicht'] ),
					'groep'       => $groep,
					'volgorde'    => $max + 10,
					'rol'         => isset( $nieuw['rol'] ) && isset( $rollen[ $nieuw['rol'] ] ) ? $nieuw['rol'] : '',
				);
			}
		}
		$alles[ $form ] = array_filter( array( 'velden' => $velden, 'extra' => $extra ) );
		if ( ! $alles[ $form ] ) {
			unset( $alles[ $form ] );
		}
		update_option( 'deedni_formulieren', $alles, false );
		wp_safe_redirect( add_query_arg( 'deedni_klaar', $fout ? 'opties' : 'opgeslagen', $terug ) );
		exit;
	}
);

function deedni_formulieren_pagina() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$defs = deedni_formulieren_defs();
	$form = isset( $_GET['form'] ) ? sanitize_key( wp_unslash( $_GET['form'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	echo '<div class="wrap deedni-beheer">';
	if ( $form && isset( $defs[ $form ] ) ) {
		deedni_formulier_bewerken( $form );
		echo '</div>';
		return;
	}
	echo '<h1>Formulieren</h1>';
	echo '<p class="deedni-intro">Pas per formulier de labels, hulpteksten en volgorde aan, maak velden verplicht of verberg ze, en voeg eigen velden toe. Velden met een slotje zijn kernvelden: die zijn nodig voor accounts of de matching en blijven altijd staan.</p>';
	echo '<table class="widefat striped deedni-tabel"><thead><tr><th>Formulier</th><th>Waar</th><th>Status</th></tr></thead><tbody>';
	foreach ( $defs as $sleutel => $def ) {
		$cfg = deedni_formulier_config( $sleutel );
		echo '<tr><td><a class="row-title" href="' . esc_url( admin_url( 'admin.php?page=deedni-formulieren&form=' . $sleutel ) ) . '">' . esc_html( $def['titel'] ) . '</a></td>';
		echo '<td>' . esc_html( $def['uitleg'] ) . '</td><td>';
		echo $cfg['aangepast'] ? '<span class="deedni-label deedni-label--eigen">Aangepast</span>' . ( $cfg['extra'] ? ' <span class="deedni-sub">' . count( $cfg['extra'] ) . ' extra ' . ( 1 === count( $cfg['extra'] ) ? 'veld' : 'velden' ) . '</span>' : '' ) : '<span class="deedni-label">Standaard</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</td></tr>';
	}
	echo '</tbody></table>';
	echo '</div>';
}

function deedni_formulier_bewerken( $form ) {
	$cfg   = deedni_formulier_config( $form );
	$def   = $cfg['def'];
	$klaar = isset( $_GET['deedni_klaar'] ) ? sanitize_key( wp_unslash( $_GET['deedni_klaar'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$rollen = isset( $def['rollen'] ) ? $def['rollen'] : array();
	echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=deedni-formulieren' ) ) . '">&larr; Alle formulieren</a></p>';
	echo '<h1>' . esc_html( $def['titel'] ) . '</h1><p class="deedni-intro">' . esc_html( $def['uitleg'] );
	if ( $def['pagina'] && function_exists( 'deedni_page_url' ) && deedni_page_url( $def['pagina'] ) ) {
		echo ' <a href="' . esc_url( deedni_page_url( $def['pagina'] ) ) . '" target="_blank" rel="noopener">Bekijk de pagina</a>.';
	}
	echo '</p>';
	$meldingen = array(
		'opgeslagen' => array( 'Opgeslagen. Het formulier op de site is meteen aangepast.', 'success' ),
		'hersteld'   => array( 'Het formulier staat weer zoals het standaard was. Extra velden zijn weg; al ingevulde antwoorden blijven bewaard.', 'success' ),
		'opties'     => array( 'Het nieuwe veld is niet toegevoegd: een keuzelijst heeft minstens één keuze nodig (één per regel). De rest is opgeslagen.', 'warning' ),
	);
	if ( isset( $meldingen[ $klaar ] ) ) {
		echo '<div class="notice notice-' . esc_attr( $meldingen[ $klaar ][1] ) . ' is-dismissible"><p>' . esc_html( $meldingen[ $klaar ][0] ) . '</p></div>';
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" id="deedni-form-beheer">';
	echo '<input type="hidden" name="action" value="deedni_formulier"><input type="hidden" name="form" value="' . esc_attr( $form ) . '">';
	wp_nonce_field( 'deedni_formulier_' . $form );
	echo '<p class="description">Laat een vak leeg voor de standaardtekst (die zie je lichtgrijs). Sleep een rij aan het handvat om de volgorde te wijzigen. Velden die op de site naast elkaar staan, verhuizen samen.</p>';

	foreach ( $def['groepen'] as $groep => $titel ) {
		$rijen = array();
		foreach ( $cfg['velden'] as $sleutel => $v ) {
			if ( $v['def']['groep'] === $groep ) {
				$rijen[] = array( 'veld', $sleutel, $v, $v['volgorde'] );
			}
		}
		foreach ( $cfg['extra'] as $slug => $x ) {
			if ( $x['groep'] === $groep ) {
				$rijen[] = array( 'extra', $slug, $x, (int) $x['volgorde'] );
			}
		}
		usort(
			$rijen,
			function ( $a, $b ) {
				return $a[3] - $b[3];
			}
		);
		if ( count( $def['groepen'] ) > 1 ) {
			echo '<h2>' . esc_html( $titel ) . '</h2>';
		}
		echo '<table class="widefat deedni-velden"><thead><tr><th style="width:24px"></th><th>Veld</th><th>Label</th><th>Hulptekst</th><th>Voorbeeldtekst in het vak</th><th>Verplicht</th><th>Tonen</th></tr></thead><tbody class="deedni-sorteer">';
		foreach ( $rijen as $rij ) {
			list( $soort, $sleutel, $v ) = $rij;
			$pre   = 'veld' === $soort ? 'velden[' . $sleutel . ']' : 'extra[' . $sleutel . ']';
			$vast  = 'veld' === $soort && ! $v['def']['volgorde'];
			echo '<tr' . ( $vast ? ' class="deedni-vast"' : '' ) . '><td class="deedni-handvat">' . ( $vast ? '' : '<span class="dashicons dashicons-move" title="Slepen om te verplaatsen"></span>' ) . '<input type="hidden" class="deedni-volgorde" name="' . esc_attr( $pre ) . '[volgorde]" value="' . (int) $rij[3] . '"></td>';
			if ( 'veld' === $soort ) {
				$d = $v['def'];
				echo '<td><strong>' . esc_html( $d['varianten'] ? $d['label'] . ' / ' . $d['label_w'] : $d['label'] ) . '</strong>';
				if ( $d['kern'] ) {
					echo '<div class="deedni-sub"><span class="dashicons dashicons-lock" style="font-size:14px;width:14px;height:14px"></span> Kernveld. ' . esc_html( $d['uitleg_kern'] ) . '</div>';
				} elseif ( $d['uitleg'] ) {
					echo '<div class="deedni-sub">' . esc_html( $d['uitleg'] ) . '</div>';
				}
				echo '</td><td>';
				if ( $d['varianten'] ) {
					echo '<input type="text" class="regular-text" name="' . esc_attr( $pre ) . '[label]" value="' . esc_attr( $v['eigen_label'] ) . '" placeholder="' . esc_attr( $d['label'] ) . '" aria-label="Label voor kandidaten"><div class="deedni-sub">Kandidaat</div>';
					echo '<input type="text" class="regular-text" name="' . esc_attr( $pre ) . '[label_w]" value="' . esc_attr( $v['eigen_label_w'] ) . '" placeholder="' . esc_attr( $d['label_w'] ) . '" aria-label="Label voor werkgevers"><div class="deedni-sub">Werkgever</div>';
				} elseif ( $d['label_bewerkbaar'] ) {
					echo '<input type="text" class="regular-text" name="' . esc_attr( $pre ) . '[label]" value="' . esc_attr( $v['eigen_label'] ) . '" placeholder="' . esc_attr( $d['label'] ) . '">';
				} else {
					echo '<span class="deedni-sub">Vast</span>';
				}
				echo '</td><td>';
				if ( $d['varianten'] ) {
					echo '<input type="text" class="regular-text" name="' . esc_attr( $pre ) . '[hint]" value="' . esc_attr( $v['eigen_hint'] ) . '" placeholder="Zoals in het blok Inloggen" aria-label="Hulptekst voor kandidaten"><div class="deedni-sub">Kandidaat</div>';
					echo '<input type="text" class="regular-text" name="' . esc_attr( $pre ) . '[hint_w]" value="' . esc_attr( $v['eigen_hint_w'] ) . '" placeholder="Zoals in het blok Inloggen" aria-label="Hulptekst voor werkgevers"><div class="deedni-sub">Werkgever</div>';
				} elseif ( $d['hint_bewerkbaar'] ) {
					echo '<input type="text" class="regular-text" name="' . esc_attr( $pre ) . '[hint]" value="' . esc_attr( $v['eigen_hint'] ) . '" placeholder="' . esc_attr( $d['hint'] ? $d['hint'] : 'Geen' ) . '">';
				} else {
					echo '<span class="deedni-sub">Vast</span>';
				}
				echo '</td><td>' . ( $d['placeholder'] ? '<input type="text" class="regular-text" name="' . esc_attr( $pre ) . '[placeholder]" value="' . esc_attr( $v['placeholder'] ) . '">' : '<span class="deedni-sub">n.v.t.</span>' ) . '</td>';
				if ( $d['kern'] ) {
					echo '<td>' . ( $d['verplicht'] ? 'Altijd' : 'Nee' ) . '</td><td>Altijd</td>';
				} else {
					echo '<td>' . ( $d['kan_verplicht'] ? '<label><input type="checkbox" name="' . esc_attr( $pre ) . '[verplicht]" value="1"' . checked( $v['verplicht'], true, false ) . '> Verplicht</label>' : '<span class="deedni-sub">n.v.t.</span>' ) . '</td>';
					echo '<td><label><input type="checkbox" name="' . esc_attr( $pre ) . '[verborgen]" value="1"' . checked( $v['verborgen'], true, false ) . '> Verbergen</label></td>';
				}
			} else {
				echo '<td><strong>' . esc_html( $v['label'] ) . '</strong><div class="deedni-sub">Eigen veld: ' . esc_html( mb_strtolower( deedni_formulier_veldtypen()[ $v['type'] ] ) ) . '</div>';
				if ( $rollen ) {
					echo '<select name="' . esc_attr( $pre ) . '[rol]" aria-label="Voor wie"><option value="">Iedereen</option>';
					foreach ( $rollen as $r => $rl ) {
						echo '<option value="' . esc_attr( $r ) . '"' . selected( $v['rol'], $r, false ) . '>' . esc_html( $rl ) . '</option>';
					}
					echo '</select>';
				}
				if ( 'keuze' === $v['type'] ) {
					echo '<div class="deedni-sub">Keuzes, één per regel:</div><textarea name="' . esc_attr( $pre ) . '[opties]" rows="3" class="regular-text">' . esc_textarea( implode( "\n", (array) $v['opties'] ) ) . '</textarea>';
				}
				echo '</td>';
				echo '<td><input type="text" class="regular-text" name="' . esc_attr( $pre ) . '[label]" value="' . esc_attr( $v['label'] ) . '" required></td>';
				echo '<td><input type="text" class="regular-text" name="' . esc_attr( $pre ) . '[hint]" value="' . esc_attr( $v['hint'] ) . '"></td>';
				echo '<td>' . ( 'vinkje' !== $v['type'] ? '<input type="text" class="regular-text" name="' . esc_attr( $pre ) . '[placeholder]" value="' . esc_attr( $v['placeholder'] ) . '">' : '<span class="deedni-sub">n.v.t.</span>' ) . '</td>';
				echo '<td><label><input type="checkbox" name="' . esc_attr( $pre ) . '[verplicht]" value="1"' . checked( $v['verplicht'], true, false ) . '> Verplicht</label></td>';
				echo '<td><label class="deedni-fout"><input type="checkbox" name="' . esc_attr( $pre ) . '[weg]" value="1"> Verwijderen</label></td>';
			}
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	if ( $def['opslag'] ) {
		echo '<h2>Veld toevoegen</h2>';
		echo '<p class="description">';
		$waar = array(
			'aanbod'    => 'De antwoorden staan bij het aanbod in wp-admin en gaan mee in de mail aan de kandidaat (variabele {extra_velden}).',
			'reactie'   => 'De antwoorden staan bij het aanbod in wp-admin en gaan mee in de mail aan de werkgever (variabele {extra_velden}).',
			'gebruiker' => 'De antwoorden staan bij het account (Gebruikers) en bij het profiel in wp-admin.',
			'kandidaat' => 'De antwoorden zie je alleen in wp-admin bij het profiel. Werkgevers zien ze niet, zodat het profiel anoniem blijft.',
			'bedrijf'   => 'De antwoorden staan bij het bedrijfsprofiel in wp-admin.',
			'mail'      => 'De antwoorden gaan mee in de mail naar jou (variabele {extra_velden}). Ze worden nergens bewaard.',
		);
		echo esc_html( $waar[ $def['opslag'] ] ) . '</p>';
		echo '<table class="form-table"><tr><th><label for="nw-label">Label</label></th><td><input type="text" id="nw-label" name="nieuw[label]" class="regular-text" placeholder="Bijvoorbeeld: Gewenste startdatum"></td></tr>';
		echo '<tr><th><label for="nw-type">Soort veld</label></th><td><select id="nw-type" name="nieuw[type]">';
		foreach ( deedni_formulier_veldtypen() as $k => $l ) {
			echo '<option value="' . esc_attr( $k ) . '">' . esc_html( $l ) . '</option>';
		}
		echo '</select></td></tr>';
		echo '<tr class="deedni-nw-opties"><th><label for="nw-opties">Keuzes</label></th><td><textarea id="nw-opties" name="nieuw[opties]" rows="3" class="regular-text" placeholder="Eén keuze per regel"></textarea><p class="description">Alleen voor een keuzelijst.</p></td></tr>';
		echo '<tr><th><label for="nw-hint">Hulptekst</label></th><td><input type="text" id="nw-hint" name="nieuw[hint]" class="regular-text"></td></tr>';
		if ( count( $def['groepen'] ) > 1 ) {
			echo '<tr><th><label for="nw-groep">Waar</label></th><td><select id="nw-groep" name="nieuw[groep]">';
			foreach ( $def['groepen'] as $k => $l ) {
				echo '<option value="' . esc_attr( $k ) . '">' . esc_html( $l ) . '</option>';
			}
			echo '</select></td></tr>';
		}
		if ( $rollen ) {
			echo '<tr><th><label for="nw-rol">Voor wie</label></th><td><select id="nw-rol" name="nieuw[rol]"><option value="">Iedereen</option>';
			foreach ( $rollen as $r => $rl ) {
				echo '<option value="' . esc_attr( $r ) . '">' . esc_html( $rl ) . '</option>';
			}
			echo '</select></td></tr>';
		}
		echo '<tr><th>Verplicht</th><td><label><input type="checkbox" name="nieuw[verplicht]" value="1"> Dit veld moet worden ingevuld</label></td></tr></table>';
	} else {
		echo '<p class="description">Bij dit formulier kun je geen eigen velden toevoegen.</p>';
	}

	echo '<p class="deedni-knoppen"><button type="submit" class="button button-primary">Opslaan</button>';
	if ( $cfg['aangepast'] ) {
		echo ' <button type="submit" name="herstel" value="1" class="button button-link-delete" formnovalidate onclick="return confirm(\'Alle aanpassingen aan dit formulier verdwijnen, ook je eigen velden. Al ingevulde antwoorden blijven bewaard. Doorgaan?\')">Standaard herstellen</button>';
	}
	echo '</p></form>';
	?>
	<style>
	.deedni-velden td{vertical-align:top}
	.deedni-velden .regular-text{width:100%;max-width:260px}
	.deedni-handvat{cursor:move;color:#8c8f94}
	.deedni-vast .deedni-handvat{cursor:default}
	.deedni-velden tr.ui-sortable-helper{background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.15)}
	</style>
	<script>
	jQuery(function ($) {
		$('.deedni-sorteer').sortable({
			items: 'tr:not(.deedni-vast)', handle: '.deedni-handvat', axis: 'y',
			update: function () { $(this).find('tr').each(function (i) { $(this).find('.deedni-volgorde').val((i + 1) * 10); }); }
		});
		var t = $('#nw-type'), o = $('.deedni-nw-opties');
		function s() { o.toggle(t.val() === 'keuze'); }
		t.on('change', s); s();
	});
	</script>
	<?php
}
