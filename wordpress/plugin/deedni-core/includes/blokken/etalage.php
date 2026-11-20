<?php
/**
 * Twee etalageblokken: een boog met anonieme kandidaatkaarten die meedraait als je scrolt,
 * en een sectie met quotes en foto's. Alle teksten, kaarten en foto's pas je aan in de editor.
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Kandidaten in een boog ---------- */

deedni_blok(
	'kandidaten-boog',
	array(
		'titel'        => 'Kandidaten in een boog',
		'beschrijving' => 'Anonieme kandidaatkaarten in een boog die meedraait als je scrolt, met een kop en knop in het midden. De kaarten hebben dezelfde stijl als de voorbeeldkaart in de hero.',
		'icoon'        => 'groups',
		'stijl'        => 'deedni-home',
		'script'       => 'deedni-home',
		'achtergrond'  => 'standaard',
		'velden'       => array(
			'label'   => array( 'type' => 'text', 'label' => 'Label boven de kop', 'standaard' => 'Ontdek wie er klaarstaat' ),
			'kop'     => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Talent dat op jouw *aanbod wacht.*', 'help' => 'Woorden tussen *sterretjes* worden grijs.' ),
			'lead'    => array( 'type' => 'textarea', 'label' => 'Tekst', 'standaard' => 'Elke kaart is een echte vakmens met ervaring, tools en een salarisindicatie. De naam blijft verborgen tot de marketeer ja zegt.' ),
			'knop'    => array( 'type' => 'text', 'label' => 'Knop', 'standaard' => 'Bekijk alle profielen' ),
			'knop_link' => array( 'type' => 'link', 'label' => 'Link van de knop', 'standaard' => 'pagina:zoeken' ),
			'draaien' => array( 'type' => 'toggle', 'label' => 'Kaarten draaien mee als je scrolt', 'standaard' => true, 'help' => 'Uit: de kaarten staan stil. Bij "minder beweging" in de instellingen van de bezoeker staan ze altijd stil.' ),
			'kaarten' => array(
				'type'      => 'lijst',
				'label'     => 'Kaarten',
				'velden'    => array(
					'code'     => array( 'type' => 'text', 'label' => 'Code' ),
					'functie'  => array( 'type' => 'text', 'label' => 'Functie' ),
					'ervaring' => array( 'type' => 'text', 'label' => 'Ervaring' ),
					'regio'    => array( 'type' => 'text', 'label' => 'Regio' ),
					'salaris'  => array( 'type' => 'text', 'label' => 'Salaris' ),
					'tools'    => array( 'type' => 'text', 'label' => 'Tools (met komma\'s, maximaal drie)' ),
				),
				'standaard' => array(
					array( 'code' => 'K-2041', 'functie' => 'Performance marketeer', 'ervaring' => '7 jaar', 'regio' => 'Utrecht', 'salaris' => 'vanaf €4.000', 'tools' => 'Google Ads, Meta Ads, GA4' ),
					array( 'code' => 'K-2057', 'functie' => 'SEO-specialist', 'ervaring' => '5 jaar', 'regio' => 'Rotterdam', 'salaris' => 'vanaf €3.600', 'tools' => 'Semrush, Ahrefs' ),
					array( 'code' => 'K-2063', 'functie' => 'Contentmarketeer', 'ervaring' => '4 jaar', 'regio' => 'Gelderland', 'salaris' => 'vanaf €3.300', 'tools' => 'WordPress, Semrush' ),
					array( 'code' => 'K-2078', 'functie' => 'Marketing-automation', 'ervaring' => '8 jaar', 'regio' => 'Amsterdam', 'salaris' => 'vanaf €5.000', 'tools' => 'HubSpot, Salesforce' ),
					array( 'code' => 'K-2082', 'functie' => 'Data-analist marketing', 'ervaring' => '6 jaar', 'regio' => 'Remote', 'salaris' => 'vanaf €4.400', 'tools' => 'GA4, SQL, Looker Studio' ),
					array( 'code' => 'K-2090', 'functie' => 'Social advertising', 'ervaring' => '3 jaar', 'regio' => 'Brabant', 'salaris' => 'vanaf €3.100', 'tools' => 'Meta Ads, TikTok Ads' ),
					array( 'code' => 'K-2104', 'functie' => 'CRO-specialist', 'ervaring' => '6 jaar', 'regio' => 'Den Haag', 'salaris' => 'vanaf €4.200', 'tools' => 'Hotjar, Figma' ),
					array( 'code' => 'K-2115', 'functie' => 'E-mailmarketeer', 'ervaring' => '4 jaar', 'regio' => 'Groningen', 'salaris' => 'vanaf €3.400', 'tools' => 'Klaviyo, ActiveCampaign' ),
					array( 'code' => 'K-2121', 'functie' => 'SEA-specialist', 'ervaring' => '9 jaar', 'regio' => 'Utrecht', 'salaris' => 'vanaf €5.200', 'tools' => 'Google Ads, Merchant Center' ),
					array( 'code' => 'K-2136', 'functie' => 'Growth marketeer', 'ervaring' => '5 jaar', 'regio' => 'Eindhoven', 'salaris' => 'vanaf €4.100', 'tools' => 'GA4, HubSpot' ),
				),
			),
			'voetnoot' => array( 'type' => 'text', 'label' => 'Kleine tekst onderaan', 'standaard' => 'Voorbeeldkaarten met fictieve gegevens.' ),
		),
		'render'       => 'deedni_render_boog',
	)
);

/** Eén kaart in de boog: dezelfde opbouw als de voorbeeldkaart in de hero, zonder beeld. */
function deedni_boog_kaart( $k, $o ) {
	$k     = wp_parse_args( $k, array( 'code' => '', 'functie' => '', 'ervaring' => '', 'regio' => '', 'salaris' => '', 'tools' => '' ) );
	$tools = array_slice( array_filter( array_map( 'trim', explode( ',', (string) $k['tools'] ) ) ), 0, 3 );
	$h     = '<li class="boog-kaart' . ( abs( $o ) > 4 ? ' is-weg' : '' ) . '" style="--o:' . $o . '">';
	$h    .= '<div class="between fine"><strong class="ink">' . esc_html( $k['code'] ) . '</strong><span>Anoniem</span></div>';
	$h    .= '<div class="t">' . esc_html( $k['functie'] ) . '</div>';
	if ( $k['salaris'] ) {
		$h .= '<p class="bk-salaris"><span class="salaris">' . esc_html( $k['salaris'] ) . '</span> <span class="fine">p/m</span></p>';
	}
	$h .= '<dl class="kv">';
	if ( $k['ervaring'] ) {
		$h .= '<dt>Ervaring</dt><dd>' . esc_html( $k['ervaring'] ) . '</dd>';
	}
	if ( $k['regio'] ) {
		$h .= '<dt>Regio</dt><dd>' . esc_html( $k['regio'] ) . '</dd>';
	}
	$h .= '<dt>Naam</dt><dd>' . deedni_redact( 's', 'naam verborgen' ) . '</dd></dl>';
	$h .= deedni_tools_html( $tools );
	return $h . '</li>';
}

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
	$h  = '<section class="sec boog' . deedni_achtergrond_klasse( $a ) . '"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . $id . '" data-boog' . ( $a['draaien'] ? ' data-boog-scroll' : '' ) . '>';
	$h .= '<div class="boog-ring" aria-hidden="true"></div>';
	if ( $n ) {
		$h .= '<ul class="boog-baan" aria-label="Voorbeelden van anonieme profielen">';
		$midden = (int) floor( ( $n - 1 ) / 2 );
		foreach ( $kaarten as $i => $k ) {
			$h .= deedni_boog_kaart( $k, $i - $midden );
		}
		$h .= '</ul>';
	}
	$h .= '<div class="wrap boog-midden">';
	$h .= deedni_eyebrow( $a['label'] );
	$h .= '<h2 id="' . esc_attr( $id ) . '" class="big-h">' . deedni_tekst( $a['kop'] ) . '</h2>';
	if ( $a['lead'] ) {
		$h .= '<p class="lead">' . deedni_tekst( $a['lead'] ) . '</p>';
	}
	$h .= '<div class="boog-acties">' . deedni_cta( $a['knop'], deedni_link( $a['knop_link'] ), 'search' ) . '</div>';
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
