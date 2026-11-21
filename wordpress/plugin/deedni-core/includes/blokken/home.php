<?php
/**
 * De secties van de homepage. Elk blok volgt het ontwerp in homepage/index.html.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Grote bewegende kop. Elke regel in $tekst is een regel in beeld. [foto1], [foto2] of [foto] zet een foto tussen de woorden.
 */
function deedni_mega( $tekst, $fotos, $tag, $id, $modus = '' ) {
	$regels = array_values( array_filter( array_map( 'trim', preg_split( '/\r?\n/', (string) $tekst ) ), 'strlen' ) );
	$schoon = trim( preg_replace( '/\s+/', ' ', preg_replace( '/\[foto\d?\]/', '', implode( ' ', $regels ) ) ) );
	$h      = '<' . $tag . ' id="' . esc_attr( $id ) . '" class="mega" data-mega>';
	$h     .= '<span class="visually-hidden">' . esc_html( $schoon ) . '</span>';
	foreach ( $regels as $i => $regel ) {
		$h .= '<span class="line" aria-hidden="true" data-drift="' . ( 0 === $i % 2 ? '-1' : '1' ) . '"' . ( $modus ? ' data-mode="' . esc_attr( $modus ) . '"' : '' ) . '>';
		foreach ( preg_split( '/\s+/', $regel ) as $woord ) {
			if ( preg_match( '/^\[foto(\d?)\]$/', $woord, $m ) ) {
				$nr  = $m[1] ? (int) $m[1] : 1;
				$img = isset( $fotos[ $nr ] ) ? $fotos[ $nr ] : null;
				if ( $img && ! empty( $img['url'] ) ) {
					$h .= '<span class="ph' . ( 2 === $nr ? ' ph--2' : '' ) . '">' . deedni_beeld( $img, array( 'alt' => '', 'loading' => 'eager' ) ) . '</span>';
				}
				continue;
			}
			$h .= '<span class="w">' . esc_html( $woord ) . '</span>';
		}
		$h .= '</span>';
	}
	return $h . '</' . $tag . '>';
}

/** Heeft de huidige pagina een welkomstblok voor ingelogde gebruikers? Dan is de grote kop geen h1. */
function deedni_pagina_heeft_welkom() {
	if ( 'bezoeker' === deedni_rol() ) {
		return false;
	}
	$post = get_post();
	return $post && has_block( 'deedni/welkom', $post );
}

/* ---------- Hero ---------- */

deedni_blok(
	'hero',
	array(
		'titel'        => 'Homepage: hero',
		'beschrijving' => 'De grote bewegende kop met foto\'s, de keuze werk of personeel en de voorbeeldkaart.',
		'icoon'        => 'cover-image',
		'stijl'        => 'deedni-home',
		'script'       => 'deedni-home',
		'velden'       => array(
			'label'             => array( 'type' => 'text', 'label' => 'Label boven de kop (leeg = geen)', 'groep' => 'Kop', 'standaard' => 'Omgekeerd solliciteren, voor elk vak' ),
			'kop'               => array( 'type' => 'textarea', 'label' => 'Grote kop', 'groep' => 'Kop', 'standaard' => "Bedrijven\nkomen [foto1] naar\njou. [foto2]", 'help' => 'Elke regel is een regel in beeld. Zet [foto1] of [foto2] tussen de woorden voor een foto.' ),
			'foto1'             => array( 'type' => 'image', 'label' => 'Foto 1', 'groep' => 'Kop', 'standaard' => deedni_img( 'telefoon-laptop.jpg' ) ),
			'foto2'             => array( 'type' => 'image', 'label' => 'Foto 2', 'groep' => 'Kop', 'standaard' => deedni_img( 'werken-laptop.jpg' ) ),
			'lead'              => array( 'type' => 'textarea', 'label' => 'Tekst onder de kop', 'groep' => 'Kop', 'standaard' => 'Hovenier, accountant of webdesigner: maak een anoniem profiel met je ervaring en wat je kunt. Bedrijven sturen je een aanbod met salaris. Pas als jij ja zegt, zien ze wie je bent.' ),
			'keuze_werk'        => array( 'type' => 'text', 'label' => 'Keuzeknop 1', 'groep' => 'Keuze werk of personeel', 'standaard' => 'Ik zoek werk' ),
			'keuze_personeel'   => array( 'type' => 'text', 'label' => 'Keuzeknop 2', 'groep' => 'Keuze werk of personeel', 'standaard' => 'Ik zoek personeel' ),
			'werk_cta'          => array( 'type' => 'text', 'label' => 'Knop (ik zoek werk)', 'groep' => 'Knoppen: ik zoek werk', 'standaard' => 'Maak gratis je profiel' ),
			'werk_cta_link'     => array( 'type' => 'link', 'label' => 'Link van de knop', 'groep' => 'Knoppen: ik zoek werk', 'standaard' => 'pagina:registreren' ),
			'werk_link2'        => array( 'type' => 'text', 'label' => 'Tweede link', 'groep' => 'Knoppen: ik zoek werk', 'standaard' => 'Zo werkt het' ),
			'werk_link2_url'    => array( 'type' => 'link', 'label' => 'Waar gaat de tweede link heen?', 'groep' => 'Knoppen: ik zoek werk', 'standaard' => '#zo-werkt-het' ),
			'werk_notitie'      => array( 'type' => 'text', 'label' => 'Kleine tekst eronder', 'groep' => 'Knoppen: ik zoek werk', 'standaard' => 'Gratis · anoniem · salaris altijd vooraf', 'help' => 'Scheid met een punt (·) voor losse labels.' ),
			'pers_cta'          => array( 'type' => 'text', 'label' => 'Knop (ik zoek personeel)', 'groep' => 'Knoppen: ik zoek personeel', 'standaard' => 'Zoek kandidaten' ),
			'pers_cta_link'     => array( 'type' => 'link', 'label' => 'Link van de knop', 'groep' => 'Knoppen: ik zoek personeel', 'standaard' => 'pagina:zoeken' ),
			'pers_link2'        => array( 'type' => 'text', 'label' => 'Tweede link', 'groep' => 'Knoppen: ik zoek personeel', 'standaard' => 'Bekijk de prijzen' ),
			'pers_link2_url'    => array( 'type' => 'link', 'label' => 'Waar gaat de tweede link heen?', 'groep' => 'Knoppen: ik zoek personeel', 'standaard' => '#prijzen' ),
			'pers_notitie'      => array( 'type' => 'text', 'label' => 'Kleine tekst eronder', 'groep' => 'Knoppen: ik zoek personeel', 'standaard' => 'Zoeken is gratis · je betaalt pas bij een aanname' ),
			'k_cta'             => array( 'type' => 'text', 'label' => 'Knop voor een ingelogde kandidaat', 'groep' => 'Ingelogd', 'standaard' => 'Bekijk je aanbiedingen' ),
			'k_notitie'         => array( 'type' => 'text', 'label' => 'Tekst eronder', 'groep' => 'Ingelogd', 'standaard' => 'Je hebt {aantal} nieuwe aanbiedingen', 'help' => '{aantal} wordt het echte aantal.' ),
			'sticker'           => array( 'type' => 'text', 'label' => 'Sticker op de kaart', 'groep' => 'Voorbeeldkaart', 'standaard' => 'Voorbeeld' ),
			'demo_bedrijf'      => array( 'type' => 'text', 'label' => 'Bedrijf', 'groep' => 'Voorbeeldkaart', 'standaard' => 'Studio Noord' ),
			'demo_functie'      => array( 'type' => 'text', 'label' => 'Functie', 'groep' => 'Voorbeeldkaart', 'standaard' => 'Webdesigner' ),
			'demo_meta'         => array( 'type' => 'text', 'label' => 'Details', 'groep' => 'Voorbeeldkaart', 'standaard' => 'Webbureau in Utrecht · hybride · 32 tot 40 uur' ),
			'demo_salaris'      => array( 'type' => 'text', 'label' => 'Salaris', 'groep' => 'Voorbeeldkaart', 'standaard' => '€3.600–4.200' ),
			'demo_quote'        => array( 'type' => 'text', 'label' => 'Bericht', 'groep' => 'Voorbeeldkaart', 'standaard' => 'De websites die je bouwde passen precies bij onze klanten.' ),
			'demo_naam'         => array( 'type' => 'text', 'label' => 'Naam die verschijnt bij Ja', 'groep' => 'Voorbeeldkaart', 'standaard' => 'Sanne de Vries' ),
			'demo_ja'           => array( 'type' => 'text', 'label' => 'Uitkomst bij Ja', 'groep' => 'Voorbeeldkaart', 'standaard' => 'Je naam is gedeeld. Studio Noord neemt contact met je op.' ),
			'demo_maar'         => array( 'type' => 'text', 'label' => 'Uitkomst bij Ja, maar', 'groep' => 'Voorbeeldkaart', 'standaard' => 'Je vraagt om meer salaris. Studio Noord past het aanbod aan, je blijft anoniem.' ),
			'demo_nee'          => array( 'type' => 'text', 'label' => 'Uitkomst bij Nee', 'groep' => 'Voorbeeldkaart', 'standaard' => 'Afgewezen. Studio Noord ziet nooit wie je bent.' ),
			'p_code'            => array( 'type' => 'text', 'label' => 'Code', 'groep' => 'Voorbeeldprofiel (ik zoek personeel)', 'standaard' => 'K-2041' ),
			'p_functie'         => array( 'type' => 'text', 'label' => 'Functie', 'groep' => 'Voorbeeldprofiel (ik zoek personeel)', 'standaard' => 'Allround hovenier' ),
			'p_salaris'         => array( 'type' => 'text', 'label' => 'Salaris', 'groep' => 'Voorbeeldprofiel (ik zoek personeel)', 'standaard' => 'vanaf €2.900' ),
			'p_ervaring'        => array( 'type' => 'text', 'label' => 'Ervaring', 'groep' => 'Voorbeeldprofiel (ik zoek personeel)', 'standaard' => '7 jaar' ),
			'p_regio'           => array( 'type' => 'text', 'label' => 'Regio', 'groep' => 'Voorbeeldprofiel (ik zoek personeel)', 'standaard' => 'Utrecht' ),
			'p_resultaat'       => array( 'type' => 'text', 'label' => 'Uitgelicht (kort, optioneel)', 'groep' => 'Voorbeeldprofiel (ik zoek personeel)', 'standaard' => '40+ tuinen' ),
			'p_resultaat_tekst' => array( 'type' => 'text', 'label' => 'Uitgelicht (tekst, optioneel)', 'groep' => 'Voorbeeldprofiel (ik zoek personeel)', 'standaard' => 'aangelegd en onderhouden, van stadstuin tot bedrijfsterrein' ),
			'p_tools'           => array( 'type' => 'text', 'label' => 'Vaardigheden (met komma\'s)', 'groep' => 'Voorbeeldprofiel (ik zoek personeel)', 'standaard' => 'Bestrating, Snoeien, Rijbewijs BE' ),
			'sticky'            => array( 'type' => 'text', 'label' => 'Vaste knop onderin op mobiel (leeg = geen)', 'groep' => 'Mobiel', 'standaard' => 'Maak gratis je profiel' ),
		),
		'render'       => 'deedni_render_hero',
	)
);

function deedni_render_hero( $a ) {
	$rol   = deedni_rol();
	$tag   = deedni_pagina_heeft_welkom() ? 'h2' : 'h1';
	$id    = deedni_uid( 'h-hero' );
	$modes = array(
		'werk'      => array( 'cta' => $a['werk_cta'], 'href' => deedni_link( $a['werk_cta_link'] ), 'cta2' => $a['werk_link2'], 'href2' => deedni_link( $a['werk_link2_url'] ), 'note' => $a['werk_notitie'], 'ico' => 'user' ),
		'personeel' => array( 'cta' => $a['pers_cta'], 'href' => deedni_link( $a['pers_cta_link'] ), 'cta2' => $a['pers_link2'], 'href2' => deedni_link( $a['pers_link2_url'] ), 'note' => $a['pers_notitie'], 'ico' => 'search' ),
	);
	$start = 'werkgever' === $rol ? 'personeel' : 'werk';
	$m     = $modes[ $start ];
	if ( 'kandidaat' === $rol ) {
		$m['cta']  = $a['k_cta'];
		$m['href'] = deedni_page_url( 'dashboard' );
		$m['note'] = str_replace( '{aantal}', (string) deedni_aantal_nieuw(), $a['k_notitie'] );
	}

	$h  = '<section class="hero" aria-labelledby="' . esc_attr( $id ) . '"><div class="hero-gloed" aria-hidden="true"></div><div class="wrap">';
	$h .= deedni_eyebrow( $a['label'] );
	$h .= deedni_mega( $a['kop'], array( 1 => $a['foto1'], 2 => $a['foto2'] ), $tag, $id, 'uit' );
	$h .= '<div class="hero-sub"><div class="hero-copy">';
	$h .= '<p class="lead">' . deedni_tekst( $a['lead'] ) . '</p>';
	if ( 'bezoeker' === $rol ) {
		$h .= '<div class="seg" role="group" aria-label="Wat zoek je?" data-hero-modes="' . esc_attr( wp_json_encode( $modes ) ) . '">';
		$h .= '<button type="button" aria-pressed="true" data-wie="werk">' . esc_html( $a['keuze_werk'] ) . '</button>';
		$h .= '<button type="button" aria-pressed="false" data-wie="personeel">' . esc_html( $a['keuze_personeel'] ) . '</button></div>';
	}
	$h .= '<div class="cta-row" id="hero-cta">';
	$h .= '<a class="cta-pill cta-pill--accent" href="' . esc_url( $m['href'] ) . '" data-cta><span class="cta-ico" aria-hidden="true" data-cta-ico>' . deedni_icon( $m['ico'], 26 ) . '</span><span class="cta-txt" data-cta-txt>' . esc_html( $m['cta'] ) . '</span></a>';
	if ( 'kandidaat' !== $rol && $m['cta2'] ) {
		$h .= '<a class="plain" href="' . esc_url( $m['href2'] ) . '" data-cta-2>' . esc_html( $m['cta2'] ) . '</a>';
	}
	$h .= '</div>';
	$h .= '<ul class="hero-chips" data-cta-note' . ( $m['note'] ? '' : ' hidden' ) . '>' . deedni_chips_html( $m['note'] ) . '</ul>';
	$h .= '</div>';

	// Voorbeeldkaarten.
	$h .= '<div class="hero-card">';
	if ( $a['sticker'] ) {
		$h .= '<span class="sticker" aria-hidden="true">' . esc_html( $a['sticker'] ) . '</span>';
	}
	$h .= '<div class="offer-demo" data-voor="werk"' . ( 'personeel' === $start ? ' hidden' : '' ) . ' aria-label="Voorbeeld van een aanbod" data-demo-teksten="' . esc_attr( wp_json_encode( array( 'ja' => $a['demo_ja'], 'maar' => $a['demo_maar'], 'nee' => $a['demo_nee'], 'naam' => $a['demo_naam'] ) ) ) . '">';
	$h .= '<div class="between fine"><strong class="ink">Nieuw aanbod van ' . esc_html( $a['demo_bedrijf'] ) . '</strong><span>net binnen</span></div>';
	$h .= '<div class="t">' . esc_html( $a['demo_functie'] ) . '</div>';
	$h .= '<p class="fine">' . esc_html( $a['demo_meta'] ) . '</p>';
	$h .= '<p><span class="salaris">' . esc_html( $a['demo_salaris'] ) . '</span> per maand</p>';
	if ( $a['demo_quote'] ) {
		$h .= '<p class="fine">“' . esc_html( deedni_krul( $a['demo_quote'] ) ) . '”</p>';
	}
	$h .= '<div class="sees">' . esc_html( $a['demo_bedrijf'] ) . ' ziet nu: <span data-demo-naam>' . deedni_redact( '', 'je naam, verborgen' ) . '</span></div>';
	$h .= '<div class="acts" data-demo-acts><button type="button" class="btn btn--primary" data-demo="ja">Ja</button><button type="button" class="btn btn--ghost" data-demo="maar">Ja, maar...</button><button type="button" class="btn btn--ghost" data-demo="nee">Nee</button></div>';
	$h .= '<p class="uitkomst" role="status" data-demo-uit hidden></p></div>';

	$h .= '<div class="offer-demo" data-voor="personeel"' . ( 'werk' === $start ? ' hidden' : '' ) . ' aria-label="Voorbeeld van een anoniem profiel">';
	$h .= '<div class="between fine"><strong class="ink">' . esc_html( $a['p_code'] ) . '</strong><span>Voorbeeld</span></div>';
	$h .= '<div class="t">' . esc_html( $a['p_functie'] ) . '</div>';
	$h .= '<p><span class="salaris">' . esc_html( $a['p_salaris'] ) . '</span> per maand</p>';
	$h .= '<dl class="kv"><dt>Ervaring</dt><dd>' . esc_html( $a['p_ervaring'] ) . '</dd><dt>Regio</dt><dd>' . esc_html( $a['p_regio'] ) . '</dd><dt>Naam</dt><dd>' . deedni_redact() . '</dd><dt>Werkgevers</dt><dd>' . deedni_redact( 'l' ) . '</dd></dl>';
	if ( $a['p_resultaat'] || $a['p_resultaat_tekst'] ) {
		$h .= '<div class="sees"><strong>' . esc_html( $a['p_resultaat'] ) . '</strong> ' . esc_html( $a['p_resultaat_tekst'] ) . '</div>';
	}
	$h .= deedni_tools_html( $a['p_tools'] );
	$h .= '</div></div></div></div></section>';

	if ( 'bezoeker' === $rol && $a['sticky'] ) {
		$h .= '<div class="sticky-cta" id="sticky-cta">' . str_replace( 'cta-pill--accent', 'cta-pill--accent cta-pill--small', deedni_cta( $a['sticky'], deedni_link( $a['werk_cta_link'] ), 'user' ) ) . '</div>';
	}
	return $h;
}

/* ---------- Grote foto ---------- */

deedni_blok(
	'grote-foto',
	array(
		'titel'        => 'Homepage: grote foto',
		'beschrijving' => 'Een foto die openvouwt tot schermvullend terwijl je scrolt, met twee regels tekst eroverheen.',
		'icoon'        => 'format-image',
		'stijl'        => 'deedni-home',
		'script'       => 'deedni-home',
		'velden'       => array(
			'foto'   => array( 'type' => 'image', 'label' => 'Foto', 'standaard' => deedni_img( 'marketing-analyse.jpg', 'Iemand wijst met een pen naar cijfers op een tablet' ), 'help' => 'Vul bij de afbeelding een alternatieve tekst in (wat staat erop).' ),
			'regel1' => array( 'type' => 'text', 'label' => 'Regel 1', 'standaard' => 'Jij kiest.' ),
			'regel2' => array( 'type' => 'text', 'label' => 'Regel 2 (in de markeerkleur)', 'standaard' => 'Zij bieden.' ),
			'onder'  => array( 'type' => 'textarea', 'label' => 'Tekst onderin', 'standaard' => 'Geen sollicitatiebrieven meer. Je laat zien wat je kunt, in welk vak dan ook, en bedrijven doen een aanbod met salaris.' ),
		),
		'render'       => function ( $a ) {
			$h  = '<section class="bigphoto" aria-label="' . esc_attr( trim( $a['regel1'] . ' ' . $a['regel2'] ) ) . '" data-bigphoto><div class="stick">';
			$h .= '<div class="frame">' . deedni_beeld( $a['foto'], array( 'loading' => 'lazy' ) ) . '</div>';
			$h .= '<p class="over" aria-hidden="true"><span>' . esc_html( $a['regel1'] ) . '</span><span>' . esc_html( $a['regel2'] ) . '</span></p>';
			if ( $a['onder'] ) {
				$h .= '<p class="cap">' . esc_html( $a['onder'] ) . '</p>';
			}
			return $h . '</div></section>';
		},
	)
);

/* ---------- Lopende band ---------- */

deedni_blok(
	'band',
	array(
		'titel'        => 'Homepage: lopende band',
		'beschrijving' => 'Grote woorden die van rechts naar links lopen, met een pauzeknop.',
		'icoon'        => 'leftright',
		'stijl'        => 'deedni-home',
		'script'       => 'deedni-home',
		'velden'       => array(
			'items' => array(
				'type'      => 'lijst',
				'label'     => 'Woorden',
				'velden'    => array( 'tekst' => array( 'type' => 'text', 'label' => 'Tekst' ) ),
				'standaard' => array_map(
					function ( $t ) {
						return array( 'tekst' => $t );
					},
					array( 'Hovenier', 'Webdesigner', 'Accountant', 'Elektricien', 'Verpleegkundige', 'Marketeer', 'Chauffeur', 'Kok', 'Leerkracht' )
				),
			),
			'label' => array( 'type' => 'text', 'label' => 'Naam voor schermlezers', 'standaard' => 'Beroepen' ),
		),
		'render'       => function ( $a ) {
			$li = '';
			foreach ( $a['items'] as $i ) {
				if ( ! empty( $i['tekst'] ) ) {
					$li .= '<li>' . esc_html( $i['tekst'] ) . '</li>';
				}
			}
			if ( ! $li ) {
				return '';
			}
			return '<section class="band-run" aria-label="' . esc_attr( $a['label'] ) . '"><div class="run"><ul>' . $li . '</ul><ul aria-hidden="true">' . $li . '</ul></div><button type="button" class="pause" aria-pressed="false" data-band-pauze>Pauzeer</button></section>';
		},
	)
);

/* ---------- Dit zien bedrijven ---------- */

deedni_blok(
	'profielen',
	array(
		'titel'        => 'Homepage: voorbeeldprofielen',
		'beschrijving' => 'Drie voorbeeldkaarten: dit zien bedrijven van jou.',
		'icoon'        => 'id-alt',
		'stijl'        => 'deedni-home',
		'achtergrond'  => 'zacht',
		'velden'       => array(
			'kop'    => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Dit zien bedrijven *van jou.*', 'help' => 'Woorden tussen *sterretjes* worden grijs.' ),
			'lead'   => array( 'type' => 'textarea', 'label' => 'Tekst', 'standaard' => 'Ervaring, vaardigheden en wat je hebt gedaan. De zwarte balken blijven dicht tot jij ja zegt.' ),
			'kaarten' => array(
				'type'      => 'lijst',
				'label'     => 'Kaarten',
				'velden'    => array(
					'code'     => array( 'type' => 'text', 'label' => 'Code' ),
					'functie'  => array( 'type' => 'text', 'label' => 'Functie' ),
					'salaris'  => array( 'type' => 'text', 'label' => 'Salaris' ),
					'ervaring' => array( 'type' => 'text', 'label' => 'Ervaring' ),
					'regio'    => array( 'type' => 'text', 'label' => 'Regio' ),
					'cijfer'   => array( 'type' => 'text', 'label' => 'Uitgelicht (kort, optioneel)' ),
					'resultaat' => array( 'type' => 'text', 'label' => 'Uitgelicht (tekst, optioneel)' ),
					'tools'    => array( 'type' => 'text', 'label' => 'Vaardigheden (met komma\'s)' ),
				),
				'standaard' => array(
					array( 'code' => 'K-1010', 'functie' => 'Allround hovenier', 'salaris' => 'vanaf €2.900', 'ervaring' => '7 jaar', 'regio' => 'Utrecht', 'cijfer' => '', 'resultaat' => '', 'tools' => 'Bestrating, Snoeien, Rijbewijs BE' ),
					array( 'code' => 'K-1024', 'functie' => 'Webdesigner', 'salaris' => 'vanaf €3.400', 'ervaring' => '4 jaar', 'regio' => 'Gelderland', 'cijfer' => '30+', 'resultaat' => 'Websites gebouwd voor lokale ondernemers', 'tools' => 'Figma, WordPress, HTML en CSS' ),
					array( 'code' => 'K-1031', 'functie' => 'Accountant', 'salaris' => 'vanaf €4.600', 'ervaring' => '9 jaar', 'regio' => 'Noord-Holland', 'cijfer' => '', 'resultaat' => '', 'tools' => 'Exact Online, Jaarrekeningen, RA of AA' ),
				),
			),
			'voetnoot' => array( 'type' => 'text', 'label' => 'Kleine tekst onderaan', 'standaard' => 'Naam, werkgevers, projectnamen en links staan er niet in. Het salaris is afgerond. Voorbeelden met fictieve gegevens.' ),
		),
		'render'       => function ( $a ) {
			$id = deedni_uid( 'h-zien' );
			$h  = '<section class="sec' . deedni_achtergrond_klasse( $a ) . '"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '"><div class="wrap">';
			$h .= deedni_sec_head( $id, $a['kop'], $a['lead'] );
			$h .= '<div class="profiles">';
			foreach ( $a['kaarten'] as $k ) {
				$k  = wp_parse_args( $k, deedni_leeg_item( array_fill_keys( array( 'code', 'functie', 'salaris', 'ervaring', 'regio', 'cijfer', 'resultaat', 'tools' ), array( 'type' => 'text' ) ) ) );
				$h .= '<article class="pcard reveal" aria-label="' . esc_attr( 'Voorbeeldprofiel ' . $k['code'] ) . '">';
				$h .= '<div class="between fine"><strong class="ink">' . esc_html( $k['code'] ) . '</strong><span>Voorbeeld</span></div>';
				$h .= '<div class="t">' . esc_html( $k['functie'] ) . '</div>';
				$h .= '<p><span class="salaris">' . esc_html( $k['salaris'] ) . '</span> <span class="fine">per maand</span></p>';
				$h .= '<dl class="kv"><dt>Ervaring</dt><dd>' . esc_html( $k['ervaring'] ) . '</dd><dt>Regio</dt><dd>' . esc_html( $k['regio'] ) . '</dd><dt>Naam</dt><dd>' . deedni_redact() . '</dd><dt>Werkgevers</dt><dd>' . deedni_redact( 'l' ) . '</dd></dl>';
				if ( $k['cijfer'] || $k['resultaat'] ) {
					$h .= '<div class="res"><strong>' . esc_html( $k['cijfer'] ) . '</strong>' . esc_html( $k['resultaat'] ) . '</div>';
				}
				$h .= deedni_tools_html( $k['tools'] ) . '</article>';
			}
			$h .= '</div>';
			if ( $a['voetnoot'] ) {
				$h .= '<p class="fine sec-foot">' . esc_html( $a['voetnoot'] ) . '</p>';
			}
			return $h . '</div></section>';
		},
	)
);

/* ---------- Zo werkt het ---------- */

function deedni_standaard_stappen( $wie ) {
	$s = array(
		'k' => array( array( 'Je maakt een profiel', 'Je vak, vaardigheden, ervaring en wat je zoekt. Naam en foto zijn niet nodig.' ), array( 'Bedrijven zoeken', 'Ze filteren op vak, vaardigheden en regio en zien alleen je anonieme profiel.' ), array( 'Er komt een aanbod binnen', 'Met functie, salaris en een persoonlijk bericht.' ), array( 'Jij zegt ja, nee of ja, maar', 'Ja, maar met een voorwaarde, zoals een hoger salaris? Dan past het bedrijf het aanbod aan. Bij nee blijft je naam verborgen.' ), array( 'Jullie maken kennis', 'Na jouw ja krijgt het bedrijf je naam, cv en contactgegevens.' ) ),
		'w' => array( array( 'Je zoekt een nieuwe collega', 'Filter op vak, vaardigheden, regio en beschikbaarheid.' ), array( 'Je opent een profiel', 'Je ziet ervaring, projecten en salarisindicatie. De naam blijft verborgen.' ), array( 'Je stuurt een aanbod', 'Met functie, salaris en een bericht. Zonder salaris gaat het niet de deur uit.' ), array( 'De kandidaat beslist', 'Ja, nee, of ja met een voorwaarde. Bij nee betaal je niets en blijft de kandidaat anoniem.' ), array( 'Jullie maken kennis', 'Na een ja zie je naam, cv en contactgegevens.' ) ),
	);
	return array_map(
		function ( $x ) {
			return array( 'titel' => $x[0], 'tekst' => $x[1], 'beeld' => array() );
		},
		$s[ $wie ]
	);
}

/** De vaste telefoonschermen uit het ontwerp, per stap. */
function deedni_telefoon_scherm( $key ) {
	$r = deedni_redact( 's' );
	$s = array(
		'k0' => '<div class="ttl">Jouw profiel</div><div class="box"><div class="kvs"><span>Functie</span><b>Allround hovenier</b><span>Vaardigheden</span><b>Bestrating, Snoeien</b><span>Salaris</span><b>€2.900–3.400</b><span>Naam en foto</span><b>Niet nodig</b></div></div><span class="pbtn">Profiel opslaan</span>',
		'k1' => '<div class="ttl">Bedrijven zoeken</div><div class="fine">Hovenier · Bestrating · Utrecht</div><div class="box">Tuinontwerper<span class="fine">5 jaar, Amsterdam</span></div><div class="box me"><b>Allround hovenier</b><span class="fine">7 jaar, Utrecht en omgeving</span><span class="redact" aria-hidden="true"></span></div><div class="box">Boomverzorger<span class="fine">4 jaar, Rotterdam</span></div><div class="fine">De tweede kaart is jouw profiel. Zonder naam, zonder foto.</div>',
		'k2' => '<div class="ttl">Nieuw aanbod</div><div class="box"><b>Groenwerk Utrecht</b><span>Allround hovenier</span><span class="fine">Utrecht, op locatie, 38 uur</span><span><span class="salaris">€2.900–3.400</span></span><span class="fine">“Je projecten passen precies bij ons werk.”</span></div><span class="pbtn">Bekijk het aanbod</span>',
		'k3' => '<div class="ttl">Jouw antwoord</div><div class="box"><b>Allround hovenier</b><span class="salaris" style="align-self:flex-start">€2.900–3.400</span><span class="fine">Groenwerk Utrecht ziet nu: ' . $r . '</span></div><span class="pbtn">Ja, deel mijn naam</span><span class="pbtn g">Ja, maar...</span><span class="pbtn g">Nee, bedankt</span>',
		'k4' => '<div class="ttl">Groenwerk Utrecht</div><span class="chat">Hoi Sanne, fijn dat je reageert. Kunnen we donderdag bellen?</span><span class="chat me">Donderdag past goed.</span><span class="chat">Top, we bellen je om 10 uur.</span>',
		'w0' => '<div class="ttl">Kandidaten zoeken</div><div class="fine">Hovenier · Bestrating · Utrecht</div><div class="box"><b>K-2041</b><span class="fine">Allround hovenier, 7 jaar</span><span class="salaris" style="align-self:flex-start">€2.900–3.400</span></div><div class="box"><b>K-1187</b><span class="fine">Hovenier, 5 jaar</span></div><div class="box"><b>K-3320</b><span class="fine">Medewerker groenvoorziening, 6 jaar</span></div>',
		'w1' => '<div class="ttl">K-2041</div><div class="box"><div class="kvs"><span>Ervaring</span><b>7 jaar</b><span>Vaardigheden</span><b>Bestrating, Rijbewijs BE</b><span>Naam</span>' . $r . '</div></div><div class="box"><b>Stadstuin opnieuw aangelegd</b><span class="fine">Ontwerp, bestrating en beplanting</span></div><span class="pbtn">Stuur een aanbod</span>',
		'w2' => '<div class="ttl">Aanbod sturen</div><div class="box"><span class="fine">Functie</span><b>Allround hovenier</b><span class="fine">Salaris per maand</span><b>€2.900 tot €3.400</b><span class="fine">Bericht</span><span>Je projecten passen goed bij ons werk.</span></div><span class="pbtn">Verstuur aanbod</span>',
		'w3' => '<div class="ttl">Aanbod aan K-2041</div><div class="box"><b>Allround hovenier</b><span class="salaris" style="align-self:flex-start">€2.900–3.400</span></div><div class="box"><span>✓ Verstuurd</span><span>✓ Gelezen</span><b class="ok-txt">✓ K-2041 zegt ja</b></div>',
		'w4' => '<div class="ttl">Sanne de Vries</div><div class="box"><div class="kvs"><span>Contact</span><b>sanne@voorbeeld.nl</b><span>Cv</span><b>cv-sanne.pdf</b></div></div><span class="pbtn">Plan een gesprek</span>',
	);
	return isset( $s[ $key ] ) ? $s[ $key ] : '<div class="ttl">deedni</div>';
}

$deedni_stap_velden = array(
	'titel' => array( 'type' => 'text', 'label' => 'Stap' ),
	'tekst' => array( 'type' => 'textarea', 'label' => 'Uitleg' ),
	'beeld' => array( 'type' => 'image', 'label' => 'Eigen schermafbeelding in de telefoon (optioneel)', 'help' => 'Leeg = het ingebouwde voorbeeldscherm.' ),
);

deedni_blok(
	'zo-werkt-het',
	array(
		'titel'        => 'Homepage: zo werkt het',
		'beschrijving' => 'Donker blok met vijf stappen die automatisch doorlopen, en een telefoon die de stap laat zien.',
		'icoon'        => 'smartphone',
		'stijl'        => 'deedni-home',
		'script'       => 'deedni-home',
		'anker'        => 'zo-werkt-het',
		'velden'       => array(
			'kop'          => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Zo werkt het, *stap voor stap.*' ),
			'label_k'      => array( 'type' => 'text', 'label' => 'Knop 1', 'standaard' => 'Ik zoek werk' ),
			'label_w'      => array( 'type' => 'text', 'label' => 'Knop 2', 'standaard' => 'Ik zoek personeel' ),
			'stappen_k'    => array( 'type' => 'lijst', 'label' => 'Stappen voor kandidaten', 'velden' => $deedni_stap_velden, 'standaard' => deedni_standaard_stappen( 'k' ), 'groep' => 'Stappen voor kandidaten' ),
			'stappen_w'    => array( 'type' => 'lijst', 'label' => 'Stappen voor werkgevers', 'velden' => $deedni_stap_velden, 'standaard' => deedni_standaard_stappen( 'w' ), 'groep' => 'Stappen voor werkgevers' ),
			'seconden'     => array( 'type' => 'number', 'label' => 'Seconden per stap', 'standaard' => 5 ),
			'voetnoot'     => array( 'type' => 'text', 'label' => 'Kleine tekst onderaan', 'standaard' => 'Voorbeeld met fictieve gegevens.' ),
		),
		'render'       => 'deedni_render_zo_werkt_het',
	)
);
unset( $deedni_stap_velden );

function deedni_render_zo_werkt_het( $a ) {
	$id    = deedni_uid( 'h-how' );
	$start = 'werkgever' === deedni_rol() ? 'w' : 'k';
	$data  = array();
	foreach ( array( 'k', 'w' ) as $wie ) {
		foreach ( array_values( $a[ 'stappen_' . $wie ] ) as $i => $s ) {
			$data[ $wie ][] = array( isset( $s['titel'] ) ? $s['titel'] : '', isset( $s['tekst'] ) ? $s['tekst'] : '' );
		}
	}
	$h  = '<section class="how"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '" data-how-stappen="' . esc_attr( wp_json_encode( $data ) ) . '" data-how-duur="' . (int) max( 2, $a['seconden'] ) * 1000 . '" data-how-start="' . $start . '"><div class="wrap">';
	$h .= '<div class="sec-head"><h2 id="' . $id . '" class="big-h">' . deedni_tekst( $a['kop'] ) . '</h2><div class="row">';
	$h .= '<div class="seg" role="group" aria-label="Bekijk de stappen als"><button type="button" aria-pressed="' . ( 'k' === $start ? 'true' : 'false' ) . '" data-how="k">' . esc_html( $a['label_k'] ) . '</button><button type="button" aria-pressed="' . ( 'w' === $start ? 'true' : 'false' ) . '" data-how="w">' . esc_html( $a['label_w'] ) . '</button></div>';
	$h .= '<button type="button" class="link-btn how-pauze" data-how-pauze aria-pressed="false">Pauzeer de animatie</button></div></div>';
	$h .= '<div class="how-grid"><ol class="steps-list" data-how-lijst>';
	// Zonder JavaScript: de stappen als gewone lijst.
	foreach ( $data[ $start ] as $i => $s ) {
		$h .= '<li><button type="button"' . ( 0 === $i ? ' aria-current="step"' : '' ) . '><span class="n">' . ( $i + 1 ) . '</span><strong>' . esc_html( $s[0] ) . '</strong><span class="d">' . esc_html( $s[1] ) . '</span></button></li>';
	}
	$h .= '</ol><div class="phone" role="img" aria-label="Telefoon die de huidige stap laat zien" data-how-phone><span class="notch" aria-hidden="true"></span>';
	foreach ( array( 'k', 'w' ) as $wie ) {
		foreach ( array_values( $a[ 'stappen_' . $wie ] ) as $i => $s ) {
			$key    = $wie . $i;
			$hidden = ( $start . '0' === $key ) ? '' : ' hidden';
			if ( ! empty( $s['beeld']['url'] ) ) {
				$h .= '<div class="scr scr--beeld" data-scr="' . $key . '"' . $hidden . '>' . deedni_beeld( $s['beeld'], array( 'loading' => 'lazy' ) ) . '</div>';
			} else {
				$h .= '<div class="scr" data-scr="' . $key . '"' . $hidden . '>' . deedni_telefoon_scherm( $key ) . '</div>';
			}
		}
	}
	$h .= '</div></div>';
	if ( $a['voetnoot'] ) {
		$h .= '<p class="fine sec-foot">' . esc_html( $a['voetnoot'] ) . '</p>';
	}
	return $h . '</div></section>';
}

/* ---------- Tijdlijn ---------- */

deedni_blok(
	'tijdlijn',
	array(
		'titel'        => 'Homepage: tijdlijn',
		'beschrijving' => 'Van profiel tot kennismaking: een lijn die meeloopt als je scrolt.',
		'icoon'        => 'calendar-alt',
		'stijl'        => 'deedni-home',
		'script'       => 'deedni-home',
		'achtergrond'  => 'standaard',
		'velden'       => array(
			'kop'      => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Van profiel *tot kennismaking.*' ),
			'lead'     => array( 'type' => 'textarea', 'label' => 'Tekst', 'standaard' => 'Zo kan een eerste week eruitzien. Jij bepaalt het tempo.' ),
			'items'    => array(
				'type'      => 'lijst',
				'label'     => 'Momenten',
				'velden'    => array(
					'dag'   => array( 'type' => 'text', 'label' => 'Dag' ),
					'titel' => array( 'type' => 'text', 'label' => 'Titel' ),
					'tekst' => array( 'type' => 'text', 'label' => 'Tekst' ),
				),
				'standaard' => array(
					array( 'dag' => 'Dag 1', 'titel' => 'Je maakt een anoniem profiel', 'tekst' => 'Ongeveer tien minuten. Zonder naam of foto.' ),
					array( 'dag' => 'Dag 3', 'titel' => 'Bedrijven zoeken', 'tekst' => 'Ze zien alleen je ervaring, vaardigheden en projecten.' ),
					array( 'dag' => 'Dag 5', 'titel' => 'Er komt een aanbod', 'tekst' => 'Met functie, salaris en een persoonlijk bericht.' ),
					array( 'dag' => 'Dag 6', 'titel' => 'Jij reageert', 'tekst' => 'Ja, ja maar met een voorwaarde, of nee.' ),
					array( 'dag' => 'Dag 9', 'titel' => 'Jullie maken kennis', 'tekst' => 'Na jouw ja delen we je naam, cv en contact.' ),
				),
			),
			'voetnoot' => array( 'type' => 'text', 'label' => 'Kleine tekst onderaan', 'standaard' => 'Voorbeeld van een mogelijk verloop.' ),
		),
		'render'       => function ( $a ) {
			$id = deedni_uid( 'h-tl' );
			$h  = '<section class="sec' . deedni_achtergrond_klasse( $a ) . '"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '"><div class="wrap">';
			$h .= deedni_sec_head( $id, $a['kop'], $a['lead'] );
			$h .= '<ol class="tl" data-tl>';
			foreach ( $a['items'] as $i ) {
				$i  = wp_parse_args( $i, array( 'dag' => '', 'titel' => '', 'tekst' => '' ) );
				$h .= '<li><span class="day">' . esc_html( $i['dag'] ) . '</span><div><strong>' . esc_html( $i['titel'] ) . '</strong>' . esc_html( $i['tekst'] ) . '</div></li>';
			}
			$h .= '</ol>';
			if ( $a['voetnoot'] ) {
				$h .= '<p class="fine sec-foot">' . esc_html( $a['voetnoot'] ) . '</p>';
			}
			return $h . '</div></section>';
		},
	)
);

/* ---------- Snel zoeken ---------- */

deedni_blok(
	'snel-zoeken',
	array(
		'titel'        => 'Homepage: snel kandidaten zoeken',
		'beschrijving' => 'Voor werkgevers: kies vak, ervaring en regio en zie meteen hoeveel profielen passen.',
		'icoon'        => 'search',
		'stijl'        => 'deedni-home',
		'script'       => 'deedni-home',
		'achtergrond'  => 'accent',
		'velden'       => array(
			'kop'       => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Snel kandidaten *zoeken.*' ),
			'lead'      => array( 'type' => 'textarea', 'label' => 'Tekst', 'standaard' => 'Voor werkgevers. Kies een vakgebied, ervaring en regio en zie meteen hoeveel anonieme profielen passen.' ),
			'aantallen' => array(
				'type'      => 'keuze',
				'label'     => 'Welke aantallen tonen?',
				'opties'    => array(
					'voorbeeld' => 'Voorbeeldaantallen (zolang er nog weinig profielen zijn)',
					'echt'      => 'Echte aantallen uit de database',
				),
				'standaard' => 'voorbeeld',
				'help'      => 'Echte aantallen gebruiken de specialismen en provincies van de profielen die online staan.',
			),
			'resultaat' => array( 'type' => 'text', 'label' => 'Tekst bij het getal', 'standaard' => 'anonieme profielen passen bij je filter' ),
			'uitleg'    => array( 'type' => 'text', 'label' => 'Tekst eronder', 'standaard' => 'Je ziet ervaring, projecten en salarisindicatie. Namen blijven verborgen.' ),
			'knop'      => array( 'type' => 'text', 'label' => 'Knop', 'standaard' => 'Bekijk de profielen' ),
			'knop_link' => array( 'type' => 'link', 'label' => 'Link van de knop', 'standaard' => 'pagina:zoeken' ),
			'voetnoot'  => array( 'type' => 'text', 'label' => 'Kleine tekst onderaan', 'standaard' => 'Voorbeeldaantallen. Werkgevers loggen in om profielen te openen.' ),
			'v_spec'    => array(
				'type'      => 'lijst',
				'label'     => 'Voorbeeld: vakgebieden',
				'groep'     => 'Voorbeeldaantallen',
				'velden'    => array( 'label' => array( 'type' => 'text', 'label' => 'Vakgebied' ), 'aantal' => array( 'type' => 'number', 'label' => 'Aantal profielen' ) ),
				'standaard' => array( array( 'label' => 'Bouw en techniek', 'aantal' => 64 ), array( 'label' => 'ICT en webdesign', 'aantal' => 48 ), array( 'label' => 'Financiën en administratie', 'aantal' => 35 ), array( 'label' => 'Groen en buitenruimte', 'aantal' => 22 ) ),
			),
		),
		'render'       => 'deedni_render_snel_zoeken',
	)
);

function deedni_render_snel_zoeken( $a ) {
	$id   = deedni_uid( 'h-quick' );
	$echt = 'echt' === $a['aantallen'];
	$link = deedni_link( $a['knop_link'] );

	$spec  = array();
	$regio = array();
	$data  = array();
	if ( $echt ) {
		foreach ( deedni_zichtbare_ids() as $kid ) {
			$k      = deedni_kandidaat( $kid );
			$data[] = array( deedni_vakgebied_slugs( $k['specialisme_ids'] ), $k['ervaring_jaren'], deedni_term_slugs( $kid, 'provincie' ), in_array( 'remote', $k['werkplekken'], true ) ? 1 : 0 );
		}
		$telling = array();
		foreach ( $data as $d ) {
			foreach ( $d[0] as $s ) {
				$telling[ $s ] = ( isset( $telling[ $s ] ) ? $telling[ $s ] : 0 ) + 1;
			}
		}
		arsort( $telling );
		foreach ( array_slice( array_keys( $telling ), 0, 5 ) as $slug ) {
			$t = get_term_by( 'slug', $slug, 'specialisme' );
			if ( $t ) {
				$spec[] = array( 'label' => $t->name, 'v' => $slug );
			}
		}
		if ( ! $spec ) {
			foreach ( array_slice( deedni_vakgebieden_boom(), 0, 4 ) as $rij ) {
				$spec[] = array( 'label' => $rij[0]->name, 'v' => $rij[0]->slug );
			}
		}
		$regio = array( array( 'label' => 'Heel Nederland', 'v' => '' ), array( 'label' => 'Utrecht', 'v' => 'utrecht' ), array( 'label' => 'Noord-Holland', 'v' => 'noord-holland' ), array( 'label' => 'Remote', 'v' => 'remote' ) );
	} else {
		foreach ( $a['v_spec'] as $s ) {
			$spec[] = array( 'label' => isset( $s['label'] ) ? $s['label'] : '', 'v' => (string) ( isset( $s['aantal'] ) ? (int) $s['aantal'] : 0 ) );
		}
		$regio = array( array( 'label' => 'Heel Nederland', 'v' => '1' ), array( 'label' => 'Utrecht', 'v' => '.3' ), array( 'label' => 'Noord-Holland', 'v' => '.42' ), array( 'label' => 'Remote', 'v' => '.55' ) );
	}
	$erv = $echt ? array( array( 'Alle niveaus', '0' ), array( 'Vanaf 3 jaar', '3' ), array( 'Vanaf 5 jaar', '5' ) ) : array( array( 'Alle niveaus', '1' ), array( 'Vanaf 3 jaar', '.7' ), array( 'Vanaf 5 jaar', '.45' ) );

	$chips = function ( $items, $q ) {
		$h = '<div class="chips" data-een data-q="' . $q . '">';
		foreach ( array_values( $items ) as $i => $c ) {
			$h .= '<button type="button" class="chip" aria-pressed="' . ( 0 === $i ? 'true' : 'false' ) . '" data-v="' . esc_attr( $c['v'] ) . '">' . esc_html( $c['label'] ) . '</button>';
		}
		return $h . '</div>';
	};
	$erv_items = array_map(
		function ( $e ) {
			return array( 'label' => $e[0], 'v' => $e[1] );
		},
		$erv
	);

	$h  = '<section class="sec' . deedni_achtergrond_klasse( $a ) . '"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '"><div class="wrap">';
	$h .= deedni_sec_head( $id, $a['kop'], $a['lead'] );
	$h .= '<div class="quick" data-quick="' . ( $echt ? 'echt' : 'voorbeeld' ) . '"' . ( $echt ? ' data-quick-data="' . esc_attr( wp_json_encode( $data ) ) . '" data-quick-link="' . esc_url( $link ) . '"' : '' ) . '>';
	$h .= '<div class="card stack light-island">';
	$h .= '<fieldset class="stack-s"><legend>Vakgebied</legend>' . $chips( $spec, 'spec' ) . '</fieldset>';
	$h .= '<fieldset class="stack-s"><legend>Ervaring</legend>' . $chips( $erv_items, 'exp' ) . '</fieldset>';
	$h .= '<fieldset class="stack-s"><legend>Regio</legend>' . $chips( $regio, 'reg' ) . '</fieldset>';
	$h .= '</div><div class="result">';
	$start = $echt ? count( $data ) : ( $spec ? (int) $spec[0]['v'] : 0 );
	$h .= '<p aria-live="polite"><span class="num" data-q-num>' . (int) $start . '</span><br><strong class="num-label">' . esc_html( $a['resultaat'] ) . '</strong></p>';
	$h .= '<p>' . esc_html( $a['uitleg'] ) . '</p>';
	$h .= '<a class="cta-pill cta-pill--light" href="' . esc_url( $link ) . '" data-q-link><span class="cta-ico" aria-hidden="true">' . deedni_icon( 'search', 26 ) . '</span><span class="cta-txt">' . esc_html( $a['knop'] ) . '</span></a>';
	if ( $a['voetnoot'] && ! $echt ) {
		$h .= '<p class="fine quiet">' . esc_html( $a['voetnoot'] ) . '</p>';
	}
	return $h . '</div></div></div></section>';
}

/* ---------- Cijfers ---------- */

deedni_blok(
	'cijfers',
	array(
		'titel'        => 'Homepage: cijfers',
		'beschrijving' => 'Vier grote getallen die optellen als ze in beeld komen.',
		'icoon'        => 'chart-bar',
		'stijl'        => 'deedni-home',
		'script'       => 'deedni-home',
		'velden'       => array(
			'kop'   => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'De arbeidsmarkt in cijfers' ),
			'bron'  => array( 'type' => 'text', 'label' => 'Bron', 'standaard' => 'Bron: CBS, derde kwartaal 2026' ),
			'items' => array(
				'type'      => 'lijst',
				'label'     => 'Cijfers',
				'velden'    => array(
					'waarde' => array( 'type' => 'text', 'label' => 'Getal', 'help' => 'Een heel getal (zoals 375.000) telt op. Met een komma of % blijft het staan.' ),
					'label'  => array( 'type' => 'text', 'label' => 'Wat is het' ),
					'tekst'  => array( 'type' => 'text', 'label' => 'Uitleg' ),
				),
				'standaard' => array(
					array( 'waarde' => '375.000', 'label' => 'openstaande vacatures', 'tekst' => 'In Nederland, eind derde kwartaal 2026.' ),
					array( 'waarde' => '92', 'label' => 'vacatures per 100 werklozen', 'tekst' => 'Er zijn bijna evenveel vacatures als werklozen.' ),
					array( 'waarde' => '4,0%', 'label' => 'werkloosheid', 'tekst' => 'In het derde kwartaal van 2026.' ),
				),
			),
		),
		'render'       => function ( $a ) {
			$id = deedni_uid( 'h-cbs' );
			$h  = '<section class="sec"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '"><div class="wrap">';
			$h .= '<div class="between reveal cbs-head"><h2 id="' . $id . '">' . deedni_tekst( $a['kop'] ) . '</h2><span class="fine">' . esc_html( $a['bron'] ) . '</span></div>';
			$h .= '<dl class="cbs reveal">';
			foreach ( $a['items'] as $i ) {
				$i     = wp_parse_args( $i, array( 'waarde' => '', 'label' => '', 'tekst' => '' ) );
				$getal = preg_match( '/^[\d.]+$/', $i['waarde'] ) ? (int) str_replace( '.', '', $i['waarde'] ) : 0;
				$h    .= '<div><dd class="v"' . ( $getal ? ' data-tel="' . $getal . '"' : '' ) . '>' . esc_html( $i['waarde'] ) . '</dd><dt class="t">' . esc_html( $i['label'] ) . '</dt><p class="fine">' . esc_html( $i['tekst'] ) . '</p></div>';
			}
			return $h . '</dl></div></section>';
		},
	)
);

/* ---------- Anders dan een vacaturesite ---------- */

$deedni_punt = array(
	'titel' => array( 'type' => 'text', 'label' => 'Titel' ),
	'tekst' => array( 'type' => 'text', 'label' => 'Tekst' ),
);

deedni_blok(
	'verschil',
	array(
		'titel'        => 'Homepage: anders dan een vacaturesite',
		'beschrijving' => 'Twee kaarten met voordelen: voor kandidaten (licht) en voor werkgevers (donker).',
		'icoon'        => 'columns',
		'stijl'        => 'deedni-home',
		'velden'       => array(
			'kop'    => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Anders dan een *vacaturesite.*' ),
			'lead'   => array( 'type' => 'textarea', 'label' => 'Tekst', 'standaard' => 'Je kiest zelf wie jou ziet, en een aanbod komt nooit zonder salaris.' ),
			'titel1' => array( 'type' => 'text', 'label' => 'Titel kaart 1', 'standaard' => 'Voor werkzoekenden', 'groep' => 'Kaart 1' ),
			'punten1' => array(
				'type'      => 'lijst',
				'label'     => 'Punten kaart 1',
				'groep'     => 'Kaart 1',
				'velden'    => $deedni_punt,
				'standaard' => array(
					array( 'titel' => 'Je werkgever kijkt niet mee', 'tekst' => 'Blokkeer bedrijven die je profiel nooit mogen zien.' ),
					array( 'titel' => 'Altijd met salaris', 'tekst' => 'Een aanbod zonder salarisrange kan niet worden verstuurd.' ),
					array( 'titel' => 'Laat zien wat je kunt', 'tekst' => 'Je werk en ervaring zeggen meer dan een motivatiebrief.' ),
					array( 'titel' => 'Alleen actuele profielen', 'tekst' => 'Niet bevestigd? Dan verdwijnt je profiel vanzelf uit de zoekresultaten.' ),
				),
			),
			'titel2' => array( 'type' => 'text', 'label' => 'Titel kaart 2', 'standaard' => 'Voor werkgevers', 'groep' => 'Kaart 2' ),
			'punten2' => array(
				'type'      => 'lijst',
				'label'     => 'Punten kaart 2',
				'groep'     => 'Kaart 2',
				'velden'    => $deedni_punt,
				'standaard' => array(
					array( 'titel' => 'Kies zelf je nieuwe collega', 'tekst' => 'Betaal per contact, of alleen als je iemand aanneemt.' ),
					array( 'titel' => 'Zoek op wat telt', 'tekst' => 'Je kiest op ervaring, vaardigheden en vakmanschap. Niet op leeftijd of geslacht.' ),
					array( 'titel' => 'Bereik mensen met een baan', 'tekst' => 'Ook vakmensen die niet actief solliciteren staan open voor een goed aanbod.' ),
				),
			),
		),
		'render'       => function ( $a ) {
			$id   = deedni_uid( 'h-diff' );
			$kaart = function ( $titel, $punten ) {
				$h = '<div class="card reveal"><h3>' . esc_html( $titel ) . '</h3><ul>';
				foreach ( $punten as $p ) {
					$p  = wp_parse_args( $p, array( 'titel' => '', 'tekst' => '' ) );
					$h .= '<li><strong>' . esc_html( $p['titel'] ) . '</strong><span>' . esc_html( $p['tekst'] ) . '</span></li>';
				}
				return $h . '</ul></div>';
			};
			$h  = '<section class="sec sec--tight"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '"><div class="wrap">';
			$h .= deedni_sec_head( $id, $a['kop'], $a['lead'] );
			$h .= '<div class="diff">' . $kaart( $a['titel1'], $a['punten1'] ) . $kaart( $a['titel2'], $a['punten2'] ) . '</div>';
			return $h . '</div></section>';
		},
	)
);
unset( $deedni_punt );

/* ---------- Privacy ---------- */

deedni_blok(
	'privacy',
	array(
		'titel'        => 'Homepage: privacy',
		'beschrijving' => 'Foto en een groot blok in de accentkleur, met een profiel waarvan je de balken open en dicht kunt doen.',
		'icoon'        => 'lock',
		'stijl'        => 'deedni-home',
		'script'       => 'deedni-home',
		'anker'        => 'privacy',
		'velden'       => array(
			'foto'          => array( 'type' => 'image', 'label' => 'Foto', 'standaard' => deedni_img( 'overleg.jpg', 'Collega\'s overleggen aan een tafel met laptops en notitieboeken' ) ),
			'kop'           => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Je naam blijft van jou tot je ja zegt.' ),
			'tekst'         => array( 'type' => 'textarea', 'label' => 'Tekst', 'standaard' => 'Zet je huidige werkgever op je blokkeerlijst. Dan ziet dat bedrijf je profiel nooit. We vragen niet naar leeftijd, geslacht, afkomst of gezondheid. Bedrijven kunnen daar ook niet op filteren.' ),
			'knop1'         => array( 'type' => 'text', 'label' => 'Knop 1', 'standaard' => 'Werkgever', 'groep' => 'Wisselknop' ),
			'knop2'         => array( 'type' => 'text', 'label' => 'Knop 2', 'standaard' => 'Werknemer', 'groep' => 'Wisselknop' ),
			'uitleg1'       => array( 'type' => 'text', 'label' => 'Uitleg bij knop 1', 'standaard' => 'Zo ziet een bedrijf het profiel. De balken blijven dicht tot de werknemer ja zegt.', 'groep' => 'Wisselknop' ),
			'uitleg2'       => array( 'type' => 'text', 'label' => 'Uitleg bij knop 2', 'standaard' => 'Zo ziet de werknemer het eigen profiel: alles zichtbaar, zonder balken.', 'groep' => 'Wisselknop' ),
			'rijen'         => array(
				'type'      => 'lijst',
				'label'     => 'Regels in het profiel',
				'groep'     => 'Voorbeeldprofiel',
				'velden'    => array(
					'label'   => array( 'type' => 'text', 'label' => 'Label' ),
					'waarde'  => array( 'type' => 'text', 'label' => 'Waarde' ),
					'geheim'  => array( 'type' => 'toggle', 'label' => 'Verborgen tot ja (zwarte balk)' ),
					'salaris' => array( 'type' => 'toggle', 'label' => 'Tonen als salaris (gele markering)' ),
				),
				'standaard' => array(
					array( 'label' => 'Functie', 'waarde' => 'Allround hovenier', 'geheim' => false, 'salaris' => false ),
					array( 'label' => 'Ervaring', 'waarde' => '7 jaar, particulieren en bedrijven', 'geheim' => false, 'salaris' => false ),
					array( 'label' => 'Salarisindicatie', 'waarde' => '€2.900–3.400', 'geheim' => false, 'salaris' => true ),
					array( 'label' => 'Naam', 'waarde' => 'Sanne de Vries', 'geheim' => true, 'salaris' => false ),
					array( 'label' => 'Contact', 'waarde' => 'sanne@voorbeeld.nl', 'geheim' => true, 'salaris' => false ),
					array( 'label' => 'Huidige werkgever', 'waarde' => 'Groenwerk Utrecht', 'geheim' => true, 'salaris' => false ),
				),
			),
		),
		'render'       => function ( $a ) {
			$id = deedni_uid( 'h-priv' );
			$h  = '<section class="sec sec--tight"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '"><div class="wrap priv" data-priv-blok data-uitleg="' . esc_attr( wp_json_encode( array( $a['uitleg1'], $a['uitleg2'] ) ) ) . '">';
			$h .= '<figure class="priv-photo reveal">' . deedni_beeld( $a['foto'], array( 'loading' => 'lazy' ) ) . '</figure>';
			$h .= '<div class="priv-block reveal"><h2 id="' . $id . '" class="big-h">' . deedni_tekst( $a['kop'] ) . '</h2>';
			$h .= '<p>' . deedni_tekst( $a['tekst'] ) . '</p>';
			$h .= '<div class="seg" role="group" aria-label="Bekijk het profiel als"><button type="button" aria-pressed="true" data-priv="g">' . esc_html( $a['knop1'] ) . '</button><button type="button" aria-pressed="false" data-priv="w">' . esc_html( $a['knop2'] ) . '</button></div>';
			$h .= '<p role="status" data-priv-uitleg class="priv-uitleg">' . esc_html( $a['uitleg1'] ) . '</p><dl class="redcard">';
			foreach ( $a['rijen'] as $r ) {
				$r  = wp_parse_args( $r, array( 'label' => '', 'waarde' => '', 'geheim' => false, 'salaris' => false ) );
				$h .= '<dt>' . esc_html( $r['label'] ) . '</dt>';
				if ( $r['geheim'] ) {
					$h .= '<dd data-geheim="' . esc_attr( $r['waarde'] ) . '">' . deedni_redact( mb_strlen( $r['waarde'] ) > 16 ? 'l' : '' ) . '</dd>';
				} elseif ( $r['salaris'] ) {
					$h .= '<dd><span class="salaris">' . esc_html( $r['waarde'] ) . '</span></dd>';
				} else {
					$h .= '<dd>' . esc_html( $r['waarde'] ) . '</dd>';
				}
			}
			return $h . '</dl></div></div></section>';
		},
	)
);

/* ---------- Prijzen ---------- */

deedni_blok(
	'prijzen',
	array(
		'titel'        => 'Homepage: prijzen',
		'beschrijving' => 'Prijzen als grote rijen, met een rij ter vergelijking.',
		'icoon'        => 'money-alt',
		'stijl'        => 'deedni-home',
		'achtergrond'  => 'donker',
		'anker'        => 'prijzen',
		'velden'       => array(
			'kop'   => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Betaal pas *bij resultaat.*' ),
			'lead'  => array( 'type' => 'textarea', 'label' => 'Tekst', 'standaard' => 'Zoeken en aanbiedingen sturen kost niets. Je betaalt bij een aanname, of per kandidaat die ja zegt. Voor werkzoekenden is deedni altijd gratis.' ),
			'items' => array(
				'type'      => 'lijst',
				'label'     => 'Prijzen',
				'velden'    => array(
					'naam'   => array( 'type' => 'text', 'label' => 'Naam' ),
					'sub'    => array( 'type' => 'text', 'label' => 'Wanneer' ),
					'bedrag' => array( 'type' => 'text', 'label' => 'Bedrag' ),
					'tekst'  => array( 'type' => 'text', 'label' => 'Uitleg' ),
					'ref'    => array( 'type' => 'toggle', 'label' => 'Ter vergelijking (grijs, open cijfer)' ),
				),
				'standaard' => array(
					array( 'naam' => 'Per aanname', 'sub' => 'Je neemt iemand aan', 'bedrag' => '10%', 'tekst' => 'van het jaarsalaris. Geen kosten vooraf, geen bijkomende kosten.', 'ref' => false ),
					array( 'naam' => 'Per contact', 'sub' => 'De kandidaat zegt ja', 'bedrag' => '€195', 'tekst' => 'per kandidaat die ja zegt. Je betaalt niets bij nee, zonder aannamecontract.', 'ref' => false ),
					array( 'naam' => 'Bureau', 'sub' => 'Ter vergelijking', 'bedrag' => '20–25%', 'tekst' => 'van het jaarsalaris, betaling meestal bij start, weinig inzicht in de kandidaat.', 'ref' => true ),
				),
			),
		),
		'render'       => function ( $a ) {
			$id = deedni_uid( 'h-prijs' );
			$h  = '<section class="sec' . deedni_achtergrond_klasse( $a ) . '"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '"><div class="wrap">';
			$h .= deedni_sec_head( $id, $a['kop'], $a['lead'] );
			$h .= '<ul class="prices reveal">';
			foreach ( $a['items'] as $p ) {
				$p  = wp_parse_args( $p, array( 'naam' => '', 'sub' => '', 'bedrag' => '', 'tekst' => '', 'ref' => false ) );
				$h .= '<li' . ( $p['ref'] ? ' class="ref"' : '' ) . '><div><div class="name">' . esc_html( $p['naam'] ) . '</div><span class="fine">' . esc_html( $p['sub'] ) . '</span></div><div class="amt">' . esc_html( $p['bedrag'] ) . '</div><div>' . esc_html( $p['tekst'] ) . '</div></li>';
			}
			return $h . '</ul></div></section>';
		},
	)
);

/* ---------- Vragen ---------- */

deedni_blok(
	'vragen',
	array(
		'titel'        => 'Homepage: veelgestelde vragen',
		'beschrijving' => 'Vragen die openklappen.',
		'icoon'        => 'editor-help',
		'stijl'        => 'deedni-home',
		'achtergrond'  => 'zacht',
		'anker'        => 'vragen',
		'velden'       => array(
			'kop'   => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Veelgestelde *vragen.*' ),
			'items' => array(
				'type'      => 'lijst',
				'label'     => 'Vragen',
				'velden'    => array(
					'vraag'    => array( 'type' => 'text', 'label' => 'Vraag' ),
					'antwoord' => array( 'type' => 'textarea', 'label' => 'Antwoord' ),
				),
				'standaard' => array(
					array( 'vraag' => 'Wanneer opent deedni?', 'antwoord' => 'We starten met een testfase voor alle vakgebieden. Een datum hebben we nog niet. Als je een account maakt, krijg je één e-mail zodra het zover is.' ),
					array( 'vraag' => 'Kan mijn huidige werkgever mijn profiel zien?', 'antwoord' => 'Niet als je het bedrijf op je blokkeerlijst zet. Dan komt je profiel nooit in hun zoekresultaten.' ),
					array( 'vraag' => 'Wanneer ziet een bedrijf mijn naam?', 'antwoord' => 'Pas als jij een aanbod van dat bedrijf accepteert. Andere bedrijven blijven alleen je anonieme profiel zien.' ),
					array( 'vraag' => 'Wat kost het voor mij als werkzoekende?', 'antwoord' => 'Niets. Profiel, cv, projecten en aanbiedingen zijn gratis.' ),
					array( 'vraag' => 'Wat als een kandidaat nee zegt?', 'antwoord' => 'Dan krijgt het bedrijf een nette afwijzing en blijft de kandidaat anoniem. Met Per contact betaal je dan niets.' ),
					array( 'vraag' => 'Waarom kan ik niet filteren op leeftijd of geslacht?', 'antwoord' => 'We vragen er niet naar. Je kiest op ervaring, vaardigheden en vakmanschap.' ),
				),
			),
			'schema' => array( 'type' => 'toggle', 'label' => 'Vragen ook als FAQ-gegevens voor Google', 'standaard' => false, 'help' => 'Zet dit aan op één pagina per site-onderwerp (bijvoorbeeld een landingspagina), niet op elke pagina met dezelfde vragen.' ),
		),
		'render'       => function ( $a ) {
			$id = deedni_uid( 'h-faq' );
			$h  = '<section class="sec' . deedni_achtergrond_klasse( $a ) . '"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '"><div class="wrap">';
			$h .= deedni_sec_head( $id, $a['kop'] );
			$h .= '<div class="faq">';
			foreach ( $a['items'] as $v ) {
				$v  = wp_parse_args( $v, array( 'vraag' => '', 'antwoord' => '' ) );
				$h .= '<details><summary>' . esc_html( $v['vraag'] ) . '</summary>' . deedni_alineas( $v['antwoord'] ) . '</details>';
			}
			$h .= '</div>';
			if ( ! empty( $a['schema'] ) ) {
				$vragen = array();
				foreach ( $a['items'] as $v ) {
					if ( ! empty( $v['vraag'] ) && ! empty( $v['antwoord'] ) ) {
						$vragen[] = array( '@type' => 'Question', 'name' => $v['vraag'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $v['antwoord'] ) );
					}
				}
				if ( $vragen ) {
					$h .= '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $vragen ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . '</script>';
				}
			}
			return $h . '</div></section>';
		},
	)
);

/* ---------- Slot ---------- */

deedni_blok(
	'slot',
	array(
		'titel'        => 'Homepage: slot met grote kop',
		'beschrijving' => 'Afsluitende grote kop met knoppen. Ingelogde kandidaten en werkgevers krijgen hun eigen knop.',
		'icoon'        => 'megaphone',
		'stijl'        => 'deedni-home',
		'script'       => 'deedni-home',
		'velden'       => array(
			'kop'          => array( 'type' => 'textarea', 'label' => 'Grote kop', 'standaard' => "Laat [foto] je\nvinden.", 'help' => 'Elke regel is een regel in beeld. [foto] zet de foto tussen de woorden.' ),
			'foto'         => array( 'type' => 'image', 'label' => 'Foto', 'standaard' => deedni_img( 'werken-laptop.jpg' ) ),
			'lead'         => array( 'type' => 'text', 'label' => 'Tekst', 'standaard' => 'Gratis voor werkzoekenden. Bedrijven betalen alleen bij een aanname.' ),
			'knop1'        => array( 'type' => 'text', 'label' => 'Knop 1 (bezoeker)', 'standaard' => 'Account maken', 'groep' => 'Knoppen' ),
			'knop1_link'   => array( 'type' => 'link', 'label' => 'Link knop 1', 'standaard' => 'pagina:registreren', 'groep' => 'Knoppen' ),
			'knop2'        => array( 'type' => 'text', 'label' => 'Knop 2 (bezoeker)', 'standaard' => 'Inloggen', 'groep' => 'Knoppen' ),
			'knop2_link'   => array( 'type' => 'link', 'label' => 'Link knop 2', 'standaard' => 'pagina:inloggen', 'groep' => 'Knoppen' ),
			'knop_k'       => array( 'type' => 'text', 'label' => 'Knop voor kandidaten', 'standaard' => 'Maak je profiel compleet', 'groep' => 'Knoppen' ),
			'knop_w'       => array( 'type' => 'text', 'label' => 'Knop voor werkgevers', 'standaard' => 'Zoek kandidaten', 'groep' => 'Knoppen' ),
		),
		'render'       => function ( $a ) {
			$id  = deedni_uid( 'h-fin' );
			$rol = deedni_rol();
			$h   = '<section class="finale"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '"><div class="wrap">';
			$h  .= deedni_mega( $a['kop'], array( 1 => $a['foto'] ), 'h2', $id );
			$h  .= '<p class="lead">' . deedni_tekst( $a['lead'] ) . '</p><div class="cta-row">';
			if ( 'kandidaat' === $rol ) {
				$h .= deedni_cta( $a['knop_k'], deedni_page_url( 'profiel' ), 'user' );
			} elseif ( 'werkgever' === $rol ) {
				$h .= deedni_cta( $a['knop_w'], deedni_page_url( 'zoeken' ), 'search' );
			} else {
				$h .= deedni_cta( $a['knop1'], deedni_link( $a['knop1_link'] ), 'plus' );
				$h .= deedni_cta( $a['knop2'], deedni_link( $a['knop2_link'] ), 'arrow', '' );
			}
			return $h . '</div></div></section>';
		},
	)
);
