<?php
/**
 * Twee etalageblokken: een boog met anonieme kandidaatkaarten die langzaam doordraait,
 * en een sectie met quotes en foto's. Alle teksten, kaarten en foto's pas je aan in de editor.
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Kandidaten in een boog ---------- */

deedni_blok(
	'kandidaten-boog',
	array(
		'titel'        => 'Kandidaten in een boog',
		'beschrijving' => 'Anonieme kandidaatkaarten in een boog die langzaam doordraait, met een kop en knop in het midden. Past op de homepage en op een pagina om te ontdekken.',
		'icoon'        => 'groups',
		'stijl'        => 'deedni-home',
		'script'       => 'deedni-home',
		'achtergrond'  => 'standaard',
		'velden'       => array(
			'label'   => array( 'type' => 'text', 'label' => 'Label boven de kop', 'standaard' => 'Ontdek wie er klaarstaat' ),
			'kop'     => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Talent dat op jouw *aanbod wacht.*', 'help' => 'Woorden tussen *sterretjes* worden grijs.' ),
			'lead'    => array( 'type' => 'textarea', 'label' => 'Tekst', 'standaard' => 'Elke kaart is een echte vakmens met ervaring, tools en een salarisindicatie. Naam en foto blijven verborgen tot de marketeer ja zegt.' ),
			'knop'    => array( 'type' => 'text', 'label' => 'Knop', 'standaard' => 'Bekijk alle profielen' ),
			'knop_link' => array( 'type' => 'link', 'label' => 'Link van de knop', 'standaard' => 'pagina:zoeken' ),
			'draaien' => array( 'type' => 'toggle', 'label' => 'Kaarten draaien vanzelf door', 'standaard' => true ),
			'kaarten' => array(
				'type'      => 'lijst',
				'label'     => 'Kaarten',
				'velden'    => array(
					'code'     => array( 'type' => 'text', 'label' => 'Code' ),
					'functie'  => array( 'type' => 'text', 'label' => 'Functie' ),
					'ervaring' => array( 'type' => 'text', 'label' => 'Ervaring' ),
					'regio'    => array( 'type' => 'text', 'label' => 'Regio' ),
					'salaris'  => array( 'type' => 'text', 'label' => 'Salaris' ),
					'kleur'    => array(
						'type'    => 'keuze',
						'label'   => 'Kleur',
						'opties'  => array(
							'blauw'  => 'Blauw',
							'geel'   => 'Geel',
							'groen'  => 'Groen',
							'nacht'  => 'Donker',
							'paars'  => 'Lichtblauw',
						),
						'nieuw'   => 'blauw',
					),
				),
				'standaard' => array(
					array( 'code' => 'K-2041', 'functie' => 'Performance marketeer', 'ervaring' => '7 jaar', 'regio' => 'Utrecht', 'salaris' => 'vanaf €4.000', 'kleur' => 'blauw' ),
					array( 'code' => 'K-2057', 'functie' => 'SEO-specialist', 'ervaring' => '5 jaar', 'regio' => 'Rotterdam', 'salaris' => 'vanaf €3.600', 'kleur' => 'geel' ),
					array( 'code' => 'K-2063', 'functie' => 'Contentmarketeer', 'ervaring' => '4 jaar', 'regio' => 'Gelderland', 'salaris' => 'vanaf €3.300', 'kleur' => 'groen' ),
					array( 'code' => 'K-2078', 'functie' => 'Marketing-automation', 'ervaring' => '8 jaar', 'regio' => 'Amsterdam', 'salaris' => 'vanaf €5.000', 'kleur' => 'nacht' ),
					array( 'code' => 'K-2082', 'functie' => 'Data-analist marketing', 'ervaring' => '6 jaar', 'regio' => 'Remote', 'salaris' => 'vanaf €4.400', 'kleur' => 'paars' ),
					array( 'code' => 'K-2090', 'functie' => 'Social advertising', 'ervaring' => '3 jaar', 'regio' => 'Brabant', 'salaris' => 'vanaf €3.100', 'kleur' => 'blauw' ),
					array( 'code' => 'K-2104', 'functie' => 'CRO-specialist', 'ervaring' => '6 jaar', 'regio' => 'Den Haag', 'salaris' => 'vanaf €4.200', 'kleur' => 'geel' ),
					array( 'code' => 'K-2115', 'functie' => 'E-mailmarketeer', 'ervaring' => '4 jaar', 'regio' => 'Groningen', 'salaris' => 'vanaf €3.400', 'kleur' => 'groen' ),
					array( 'code' => 'K-2121', 'functie' => 'SEA-specialist', 'ervaring' => '9 jaar', 'regio' => 'Utrecht', 'salaris' => 'vanaf €5.200', 'kleur' => 'nacht' ),
					array( 'code' => 'K-2136', 'functie' => 'Growth marketeer', 'ervaring' => '5 jaar', 'regio' => 'Eindhoven', 'salaris' => 'vanaf €4.100', 'kleur' => 'paars' ),
				),
			),
			'voetnoot' => array( 'type' => 'text', 'label' => 'Kleine tekst onderaan', 'standaard' => 'Voorbeeldkaarten met fictieve gegevens.' ),
		),
		'render'       => 'deedni_render_boog',
	)
);

function deedni_render_boog( $a ) {
	$kaarten = array_values(
		array_filter(
			(array) $a['kaarten'],
			function ( $k ) {
				return ! empty( $k['functie'] );
			}
		)
	);
	$id = deedni_uid( 'h-boog' );
	$n  = count( $kaarten );
	$h  = '<section class="sec boog' . deedni_achtergrond_klasse( $a ) . '"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '" data-boog' . ( $a['draaien'] ? ' data-boog-auto' : '' ) . '>';
	$h .= '<div class="boog-ring" aria-hidden="true"></div>';
	if ( $n ) {
		$h .= '<ul class="boog-baan" aria-label="Voorbeelden van anonieme profielen">';
		$midden = (int) floor( ( $n - 1 ) / 2 );
		foreach ( $kaarten as $i => $k ) {
			$k     = wp_parse_args( $k, array( 'code' => '', 'functie' => '', 'ervaring' => '', 'regio' => '', 'salaris' => '', 'kleur' => 'blauw' ) );
			$o     = $i - $midden;
			$kleur = in_array( $k['kleur'], array( 'blauw', 'geel', 'groen', 'nacht', 'paars' ), true ) ? $k['kleur'] : 'blauw';
			$meta  = implode( ' · ', array_filter( array( $k['ervaring'], $k['regio'] ) ) );
			$h    .= '<li class="boog-kaart kleur-' . $kleur . ( abs( $o ) > 4 ? ' is-weg' : '' ) . '" style="--o:' . $o . '">';
			$h    .= '<div class="bk-beeld"><span class="bk-code">' . esc_html( $k['code'] ) . '</span><span class="bk-anon">Anoniem</span>';
			$h    .= '<svg class="bk-silhouet" viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="46" r="22"/><path d="M18 120c4-26 21-40 42-40s38 14 42 40z"/></svg></div>';
			$h    .= '<div class="bk-tekst"><strong>' . esc_html( $k['functie'] ) . '</strong>';
			if ( $meta ) {
				$h .= '<span class="bk-meta">' . esc_html( $meta ) . '</span>';
			}
			if ( $k['salaris'] ) {
				$h .= '<span class="salaris">' . esc_html( $k['salaris'] ) . '</span>';
			}
			$h .= '<span class="bk-naam">' . deedni_redact( 's', 'naam verborgen' ) . '</span></div></li>';
		}
		$h .= '</ul>';
	}
	$h .= '<div class="wrap boog-midden">';
	$h .= deedni_eyebrow( $a['label'] );
	$h .= '<h2 id="' . esc_attr( $id ) . '" class="big-h">' . deedni_tekst( $a['kop'] ) . '</h2>';
	if ( $a['lead'] ) {
		$h .= '<p class="lead">' . deedni_tekst( $a['lead'] ) . '</p>';
	}
	$h .= '<div class="boog-acties">' . deedni_cta( $a['knop'], deedni_link( $a['knop_link'] ), 'search' );
	if ( $n > 1 ) {
		$h .= '<div class="rond-knoppen" data-boog-knoppen hidden>';
		$h .= '<button type="button" class="rond" data-boog-stap="-1" aria-label="Vorige kaart"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 6l-6 6 6 6"/></svg></button>';
		if ( $a['draaien'] ) {
			$h .= '<button type="button" class="rond" data-boog-pauze aria-pressed="false" aria-label="Pauzeer het draaien"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg></button>';
		}
		$h .= '<button type="button" class="rond" data-boog-stap="1" aria-label="Volgende kaart">' . deedni_icon( 'arrow', 20 ) . '</button></div>';
	}
	$h .= '</div>';
	if ( $a['voetnoot'] ) {
		$h .= '<p class="fine boog-voet">' . esc_html( $a['voetnoot'] ) . '</p>';
	}
	return $h . '</div></section>';
}

/* ---------- Quotes met foto's ---------- */

deedni_blok(
	'quotes',
	array(
		'titel'        => 'Quotes met foto\'s',
		'beschrijving' => 'Ervaringen van marketeers en werkgevers, met een stapel foto\'s die meebeweegt. Wissel met de pijltjes.',
		'icoon'        => 'format-quote',
		'stijl'        => 'deedni-home',
		'script'       => 'deedni-home',
		'achtergrond'  => 'geel',
		'velden'       => array(
			'label'  => array( 'type' => 'text', 'label' => 'Label boven de kop', 'standaard' => 'Ervaringen' ),
			'kop'    => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Zo voelt het *als zij jou vinden.*', 'help' => 'Woorden tussen *sterretjes* worden zachter.' ),
			'quotes' => array(
				'type'      => 'lijst',
				'label'     => 'Quotes',
				'velden'    => array(
					'tekst' => array( 'type' => 'textarea', 'label' => 'Quote' ),
					'naam'  => array( 'type' => 'text', 'label' => 'Naam' ),
					'rol'   => array( 'type' => 'text', 'label' => 'Functie of bedrijf' ),
					'foto'  => array( 'type' => 'image', 'label' => 'Foto' ),
				),
				'standaard' => array(
					array( 'tekst' => 'Binnen een week had ik twee aanbiedingen, allebei met salaris erbij. Ik hoefde geen enkele brief te schrijven.', 'naam' => 'Lotte', 'rol' => 'SEA-specialist, Utrecht', 'foto' => deedni_img( 'werken-laptop.jpg', 'Iemand werkt aan een laptop' ) ),
					array( 'tekst' => 'We zochten maanden via vacatures. Hier zagen we meteen wat iemand kan, en na één aanbod zat de nieuwe collega aan tafel.', 'naam' => 'Mark', 'rol' => 'Oprichter van een online bureau', 'foto' => deedni_img( 'overleg.jpg', 'Twee collega\'s in overleg' ) ),
					array( 'tekst' => 'Mijn huidige werkgever kan mijn profiel niet zien. Daardoor durfde ik eindelijk rond te kijken.', 'naam' => 'Yara', 'rol' => 'Contentmarketeer, Arnhem', 'foto' => deedni_img( 'telefoon-laptop.jpg', 'Een telefoon en laptop op een bureau' ) ),
					array( 'tekst' => 'Ik koos op resultaten en tools, niet op een cv-foto. Dat voelt eerlijker voor iedereen.', 'naam' => 'Daan', 'rol' => 'Marketingmanager in de e-commerce', 'foto' => deedni_img( 'marketing-analyse.jpg', 'Iemand wijst naar marketingcijfers' ) ),
				),
			),
			'voetnoot' => array( 'type' => 'text', 'label' => 'Kleine tekst onderaan', 'standaard' => 'Voorbeeldquotes met fictieve namen.' ),
		),
		'render'       => 'deedni_render_quotes',
	)
);

function deedni_render_quotes( $a ) {
	$quotes = array_values(
		array_filter(
			(array) $a['quotes'],
			function ( $q ) {
				return ! empty( $q['tekst'] );
			}
		)
	);
	if ( ! $quotes ) {
		return '';
	}
	$id = deedni_uid( 'h-quotes' );
	$h  = '<section class="sec quotes' . deedni_achtergrond_klasse( $a ) . '"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '" data-quotes><div class="wrap quotes-grid">';

	$h .= '<div class="q-fotos" aria-hidden="true">';
	foreach ( $quotes as $i => $q ) {
		if ( ! empty( $q['foto']['url'] ) ) {
			$h .= '<figure class="q-foto" data-q-foto="' . $i . '" style="--i:' . $i . '">' . deedni_beeld( $q['foto'], array( 'loading' => 'lazy', 'alt' => '' ) ) . '</figure>';
		}
	}
	$h .= '</div>';

	$h .= '<div class="q-tekst">';
	$h .= deedni_eyebrow( $a['label'] );
	$h .= '<h2 id="' . esc_attr( $id ) . '" class="q-kop">' . deedni_tekst( $a['kop'] ) . '</h2>';
	$h .= '<div class="q-lijst" aria-live="polite">';
	foreach ( $quotes as $i => $q ) {
		$q    = wp_parse_args( $q, array( 'tekst' => '', 'naam' => '', 'rol' => '' ) );
		$init = function_exists( 'mb_substr' ) ? mb_strtoupper( mb_substr( trim( $q['naam'] ), 0, 1 ) ) : strtoupper( substr( trim( $q['naam'] ), 0, 1 ) );
		$h   .= '<figure class="q-item' . ( 0 === $i ? ' is-aan' : '' ) . '" data-q-item="' . $i . '">';
		$h   .= '<blockquote><p>' . esc_html( $q['tekst'] ) . '</p></blockquote>';
		$h   .= '<figcaption><span class="q-av" aria-hidden="true">' . esc_html( $init ) . '</span><span><strong>' . esc_html( $q['naam'] ) . '</strong>' . esc_html( $q['rol'] ) . '</span></figcaption></figure>';
	}
	$h .= '</div>';
	if ( count( $quotes ) > 1 ) {
		$h .= '<div class="q-nav" data-q-nav hidden><button type="button" class="rond" data-q-stap="-1" aria-label="Vorige quote"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 6l-6 6 6 6"/></svg></button>';
		$h .= '<span class="q-teller" data-q-teller>1 / ' . count( $quotes ) . '</span>';
		$h .= '<button type="button" class="rond" data-q-stap="1" aria-label="Volgende quote">' . deedni_icon( 'arrow', 20 ) . '</button></div>';
	}
	if ( $a['voetnoot'] ) {
		$h .= '<p class="fine q-voet">' . esc_html( $a['voetnoot'] ) . '</p>';
	}
	return $h . '</div></div></section>';
}
