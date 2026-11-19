<?php
/**
 * Blokken voor ingelogde gebruikers: welkom op de homepage, inloggen, overzicht, mijn profiel,
 * kandidaten zoeken en het kandidaatprofiel. Plus de pop-ups "Aanbod sturen" en "Ja, maar...".
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * Pop-ups
 * ========================================================================= */

function deedni_ok_dot() {
	return '<span class="ok-dot" aria-hidden="true">' . deedni_icon( 'check', 28 ) . '</span>';
}

/** Foutmeldingen in een formulier (gevuld door deedni.js of na een gewone POST). */
function deedni_fouten_html( $context ) {
	$flash = deedni_flash( $context );
	$h     = '<div class="dn-fouten" data-fouten role="alert"' . ( $flash ? '' : ' hidden' ) . '>';
	if ( $flash && ! empty( $flash['errors'] ) ) {
		$h .= '<ul>';
		foreach ( $flash['errors'] as $e ) {
			$h .= '<li>' . wp_kses_post( $e ) . '</li>';
		}
		$h .= '</ul>';
	}
	return $h . '</div>';
}

function deedni_popup_aanbod_sturen() {
	$werkplek = '';
	foreach ( deedni_choices( 'werkvorm' ) as $key => $label ) {
		$werkplek .= '<label class="chip chip--radio"><input type="radio" name="werkplek" value="' . esc_attr( $key ) . '"' . checked( 'hybride', $key, false ) . '>' . esc_html( $label ) . '</label>';
	}
	$contract = '';
	foreach ( deedni_choices( 'contract' ) as $key => $label ) {
		$contract .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</option>';
	}
	$b       = deedni_profile_id( get_current_user_id(), 'bedrijf' );
	$plaats  = $b ? (string) get_post_meta( $b, 'plaats', true ) : '';
	$h  = '<dialog class="modal" id="aanbod-sturen" aria-labelledby="as-kop">';
	$h .= '<form method="post" data-ajax novalidate>';
	$h .= '<input type="hidden" name="deedni_action" value="aanbod_sturen">' . deedni_nonce_field( 'deedni_aanbod' );
	$h .= '<input type="hidden" name="kandidaat_id" value="">';
	$h .= '<input type="hidden" name="aanbod_id" value="">';
	$h .= '<div class="modal-head"><div class="stack-s"><h2 id="as-kop"><span data-tekst="kop">Aanbod sturen aan</span> <span data-tekst="code"></span></h2><p class="fine" data-tekst="sub"></p></div><button type="button" class="modal-close" data-close aria-label="Sluiten">×</button></div>';
	$h .= '<div class="modal-body">' . deedni_fouten_html( 'aanbod' );
	$h .= '<div class="field"><label for="as-functie">Functie</label><input id="as-functie" name="functie" required></div>';
	$h .= '<fieldset><legend>Salaris per maand <span class="tag tag--ink who-sees">Verplicht</span></legend><div class="fields mt-s">';
	$h .= '<div class="field"><label for="as-van">Van</label><div class="euro"><input id="as-van" name="salaris_min" inputmode="numeric" required></div></div>';
	$h .= '<div class="field"><label for="as-tot">Tot</label><div class="euro"><input id="as-tot" name="salaris_max" inputmode="numeric" required></div></div>';
	$h .= '</div><p class="fine mt-xs">Bruto, bij 40 uur. Zonder salaris gaat een aanbod niet de deur uit.</p></fieldset>';
	$h .= '<div class="fields"><div class="field"><label for="as-uren">Uren per week</label><input id="as-uren" name="uren" value="36 tot 40"></div>';
	$h .= '<div class="field"><label for="as-contract">Contract</label><select id="as-contract" name="contract">' . $contract . '</select></div></div>';
	$h .= '<div class="fields"><div class="field"><label for="as-plaats">Standplaats</label><input id="as-plaats" name="standplaats" value="' . esc_attr( $plaats ) . '"></div></div>';
	$h .= '<fieldset class="stack-s"><legend>Werkplek</legend><div class="chips">' . $werkplek . '</div></fieldset>';
	$h .= '<div class="field"><label for="as-bericht">Persoonlijk bericht</label><textarea id="as-bericht" name="bericht" required aria-describedby="as-tip"></textarea><span class="hint" id="as-tip">Schrijf waarom dit profiel past. Dat wordt vaker beantwoord dan een standaardtekst.</span></div>';
	$h .= '<p class="note">De kandidaat ziet je bedrijfsnaam, het salaris en je bericht. Jij ziet de naam pas als de kandidaat ja zegt.</p></div>';
	$h .= '<div class="modal-foot"><button type="submit" class="link-btn" name="alleen_bewaren" value="1" data-bewaar-knop formnovalidate>Bewaar voor later</button><div class="push"><button type="button" class="btn btn--ghost" data-close>Annuleren</button><button type="submit" class="btn btn--primary">Verstuur aanbod</button></div></div>';
	$h .= '</form>';
	$h .= '<div class="modal-done" data-klaar hidden>' . deedni_ok_dot() . '<h2 tabindex="-1">Aanbod verstuurd</h2><p data-klaar-tekst>De kandidaat ziet je aanbod nu. Je vindt het terug onder Verstuurd op je overzicht. Zodra er een antwoord is, krijg je een mail.</p>';
	$h .= '<div class="row"><a class="btn btn--primary" href="' . esc_url( deedni_page_url( 'dashboard' ) ) . '">Naar overzicht</a><button type="button" class="btn btn--ghost" data-close>Sluiten</button></div></div>';
	return $h . '</dialog>';
}

function deedni_popup_ja_maar() {
	$chips = '';
	foreach ( deedni_choices( 'voorwaarden' ) as $key => $label ) {
		$chips .= '<label class="chip"><input type="checkbox" name="voorwaarden[]" value="' . esc_attr( $key ) . '"' . ( 'anders' === $key ? ' data-anders' : '' ) . '>' . esc_html( $label ) . '</label>';
	}
	$h  = '<dialog class="modal" id="ja-maar" aria-labelledby="jm-kop">';
	$h .= '<form method="post" data-ajax novalidate>';
	$h .= '<input type="hidden" name="deedni_action" value="aanbod_jamaar">' . deedni_nonce_field( 'deedni_jamaar' ) . '<input type="hidden" name="aanbod_id" value="">';
	$h .= '<div class="modal-head"><div class="stack-s"><h2 id="jm-kop">Ja, maar...</h2><p class="fine">Reageer met een voorwaarde op het aanbod van <span data-tekst="bedrijf"></span>.</p></div><button type="button" class="modal-close" data-close aria-label="Sluiten">×</button></div>';
	$h .= '<div class="modal-body">' . deedni_fouten_html( 'jamaar' );
	$h .= '<div class="summary"><span data-tekst="titel"></span><span class="salaris" data-tekst="salaris"></span></div>';
	$h .= '<fieldset class="stack-s"><legend>Waar gaat je voorwaarde over?</legend><div class="chips mt-s">' . $chips . '</div>';
	$h .= '<div class="field mt-m" data-anders-veld hidden><label for="jm-anders">Wat wil je anders?</label><input id="jm-anders" name="tegen_anders" placeholder="Bijvoorbeeld: een vierdaagse werkweek"></div></fieldset>';
	$h .= '<fieldset><legend>Salaris dat je wilt, per maand</legend><div class="fields mt-s">';
	$h .= '<div class="field"><label for="jm-van">Van</label><div class="euro"><input id="jm-van" name="tegen_min" inputmode="numeric"></div></div>';
	$h .= '<div class="field"><label for="jm-tot">Tot</label><div class="euro"><input id="jm-tot" name="tegen_max" inputmode="numeric"></div></div>';
	$h .= '</div><p class="fine mt-xs">Alleen invullen als het over salaris gaat.</p></fieldset>';
	$h .= '<div class="field"><label for="jm-wens">Extra wens</label><input id="jm-wens" name="tegen_wens" placeholder="Bijvoorbeeld: auto van de zaak"></div>';
	$h .= '<div class="field"><label for="jm-bericht">Bericht aan het bedrijf</label><textarea id="jm-bericht" name="bericht" required aria-describedby="jm-tip"></textarea><span class="hint" id="jm-tip">Schrijf er duidelijk bij wat wel goed is. Dat helpt het bedrijf je aanbod aan te passen.</span></div>';
	$h .= '<p class="note">Je naam blijft verborgen. Het bedrijf kan het aanbod aanpassen, akkoord gaan of het laten vallen. Pas als jij daarna ja zegt, deel je je naam.</p></div>';
	$h .= '<div class="modal-foot"><div class="push"><button type="button" class="btn btn--ghost" data-close>Annuleren</button><button type="submit" class="btn btn--primary">Verstuur reactie</button></div></div>';
	$h .= '</form>';
	$h .= '<div class="modal-done" data-klaar hidden>' . deedni_ok_dot() . '<h2 tabindex="-1">Reactie verstuurd</h2><p><span data-tekst="bedrijf"></span> ziet je voorwaarde, zonder je naam. Je hoort het als ze het aanbod aanpassen. Je vindt het terug onder Beantwoord.</p><button type="button" class="btn btn--primary" data-close>Sluiten</button></div>';
	return $h . '</dialog>';
}

/** Knop die de pop-up "Aanbod sturen" opent voor een kandidaat (of een bestaand aanbod aanpast). */
function deedni_aanbod_knop( $k, $tekst = 'Aanbod sturen', $class = 'btn btn--primary btn--small', $aanbod = null ) {
	if ( ! deedni_can_search() ) {
		return '';
	}
	deedni_popup_nodig( 'aanbod-sturen' );
	$vul = array(
		'kandidaat_id' => $k['id'],
		'aanbod_id'    => $aanbod ? $aanbod['id'] : '',
		'functie'      => $aanbod ? $aanbod['functie'] : $k['functietitel'],
		'salaris_min'  => $aanbod ? deedni_number( $aanbod['tegen']['min'] ? $aanbod['tegen']['min'] : $aanbod['salaris_min'] ) : ( $k['salaris_min'] ? deedni_number( deedni_salaris_afgerond( $k['salaris_min'] ) ) : '' ),
		'salaris_max'  => $aanbod ? deedni_number( $aanbod['tegen']['max'] ? $aanbod['tegen']['max'] : $aanbod['salaris_max'] ) : ( $k['salaris_min'] ? deedni_number( deedni_salaris_afgerond( $k['salaris_min'] ) + 600 ) : '' ),
	);
	if ( $aanbod ) {
		$vul['uren']        = $aanbod['uren'];
		$vul['contract']    = $aanbod['contract'];
		$vul['standplaats'] = $aanbod['standplaats'];
		$vul['bericht']     = $aanbod['bericht'];
		$vul['werkplek']    = $aanbod['werkplek'];
	}
	$sub   = implode( ' · ', array_filter( array( $k['functietitel'], $k['ervaring_jaren'] ? $k['ervaring_jaren'] . ' jaar' : '', deedni_regio_tekst( $k ) ) ) );
	$tekst_data = array(
		'kop'  => $aanbod ? 'Aanbod aanpassen voor' : 'Aanbod sturen aan',
		'code' => $k['code'],
		'sub'  => $sub,
	);
	return '<button type="button" class="' . esc_attr( $class ) . '" data-open="aanbod-sturen" data-vul="' . esc_attr( wp_json_encode( $vul ) ) . '" data-tekst-vul="' . esc_attr( wp_json_encode( $tekst_data ) ) . '">' . esc_html( $tekst ) . '</button>';
}

/** Bewaarknop (bladwijzer of tekst). */
function deedni_bewaar_knop( $kandidaat_id, $stijl = 'icoon' ) {
	if ( ! deedni_can_search() ) {
		return '';
	}
	$aan = in_array( (int) $kandidaat_id, deedni_bewaard(), true );
	$h   = '<form method="post" class="inline-form" data-ajax="toggle">';
	$h  .= '<input type="hidden" name="deedni_action" value="bewaren">' . deedni_nonce_field( 'deedni_bewaren' );
	$h  .= '<input type="hidden" name="kandidaat_id" value="' . (int) $kandidaat_id . '"><input type="hidden" name="keuze" value="' . ( $aan ? 'uit' : 'aan' ) . '">';
	if ( 'icoon' === $stijl ) {
		$h .= '<button type="submit" class="save" aria-pressed="' . ( $aan ? 'true' : 'false' ) . '" aria-label="Bewaar ' . esc_attr( deedni_code( $kandidaat_id ) ) . '"><svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3.5h12v17l-6-4-6 4z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></button>';
	} else {
		$h .= '<button type="submit" class="btn btn--ghost" aria-pressed="' . ( $aan ? 'true' : 'false' ) . '" data-aan="Bewaard" data-uit="Bewaren">' . ( $aan ? 'Bewaard' : 'Bewaren' ) . '</button>';
	}
	return $h . '</form>';
}

/* =========================================================================
 * Welkom op de homepage (ingelogd)
 * ========================================================================= */

deedni_blok(
	'welkom',
	array(
		'titel'        => 'Welkom voor ingelogde gebruikers',
		'beschrijving' => 'Bovenaan de homepage: marketeers zien hun nieuwste aanbod, werkgevers de beste matches als kaarten. Bezoekers zien dit blok niet.',
		'icoon'        => 'admin-users',
		'stijl'        => 'deedni-home',
		'script'       => 'deedni-app',
		'leeg'         => 'bezoekers zien dit blok niet; kies rechts een rol voor het voorbeeld',
		'velden'       => array(
			'k_kop'       => array( 'type' => 'text', 'label' => 'Kop (met nieuwe aanbiedingen)', 'groep' => 'Marketeer', 'standaard' => 'Hoi {naam}, je hebt *{aantal} nieuwe aanbiedingen*.', 'help' => '{naam} en {aantal} worden ingevuld. Tussen *sterretjes* = accentkleur.' ),
			'k_kop_1'     => array( 'type' => 'text', 'label' => 'Kop (met één nieuw aanbod)', 'groep' => 'Marketeer', 'standaard' => 'Hoi {naam}, je hebt *1 nieuw aanbod*.' ),
			'k_kop_leeg'  => array( 'type' => 'text', 'label' => 'Kop (zonder nieuwe aanbiedingen)', 'groep' => 'Marketeer', 'standaard' => 'Hoi {naam}, *welkom terug*.' ),
			'k_leeg'      => array( 'type' => 'textarea', 'label' => 'Kaart als er geen nieuw aanbod is', 'groep' => 'Marketeer', 'standaard' => 'Nog geen nieuw aanbod. Een compleet profiel met resultaten wordt vaker gevonden.' ),
			'w_kop'       => array( 'type' => 'text', 'label' => 'Kop', 'groep' => 'Werkgever', 'standaard' => '*{aantal} marketeers* passen bij {bedrijf}.' ),
			'w_tekst'     => array( 'type' => 'textarea', 'label' => 'Tekst', 'groep' => 'Werkgever', 'standaard' => 'Op basis van je bedrijfsprofiel: {voorkeuren}. Liever zelf zoeken?' ),
			'w_wacht'     => array( 'type' => 'textarea', 'label' => 'Tekst als het account nog niet is goedgekeurd', 'groep' => 'Werkgever', 'standaard' => 'We controleren je account, meestal binnen één werkdag. Vul intussen je bedrijfsprofiel aan, dan staan je matches meteen klaar.' ),
		),
		'render'       => 'deedni_render_welkom',
	)
);

function deedni_render_welkom( $a ) {
	$rol = deedni_rol();
	if ( 'kandidaat' === $rol ) {
		return deedni_render_welkom_kandidaat( $a );
	}
	if ( 'werkgever' === $rol ) {
		return deedni_render_welkom_werkgever( $a );
	}
	return '';
}

function deedni_render_welkom_kandidaat( $a ) {
	$kid   = deedni_profile_id( 0, 'kandidaat' );
	$k     = $kid ? deedni_kandidaat( $kid ) : null;
	$naam  = $k && $k['voornaam'] ? $k['voornaam'] : wp_get_current_user()->display_name;
	$nieuw = $kid ? deedni_offers( array( 'kandidaat' => $kid, 'status' => 'verstuurd' ) ) : array();
	$n     = count( $nieuw );
	$kop   = 0 === $n ? $a['k_kop_leeg'] : ( 1 === $n ? $a['k_kop_1'] : $a['k_kop'] );
	$kop   = str_replace( array( '{naam}', '{aantal}' ), array( $naam, (string) $n ), $kop );
	$c     = $k ? deedni_compleet( $k ) : array( 'procent' => 0 );

	$h  = '<section class="welkom" aria-labelledby="h-welkom"><div class="wrap welkom-grid"><div>';
	$h .= '<h1 id="h-welkom">' . str_replace( 'class="soft"', 'class="em"', deedni_tekst( $kop ) ) . '</h1>';
	$h .= '<dl class="stats"><div><dt>keer bekeken deze week</dt><dd>' . (int) ( $kid ? deedni_aantal_bekeken( $kid ) : 0 ) . '</dd></div><div><dt>keer bewaard</dt><dd>' . (int) ( $kid ? deedni_aantal_bewaard_door( $kid ) : 0 ) . '</dd></div><div><dt>profiel compleet</dt><dd>' . (int) $c['procent'] . '%</dd></div></dl></div>';
	if ( $nieuw ) {
		$o  = $nieuw[0];
		$h .= '<article class="wcard" aria-label="Nieuwste aanbod"><div class="between fine"><strong class="ink">' . esc_html( get_the_title( $o['bedrijf_id'] ) ) . '</strong><span>' . esc_html( deedni_geleden( $o['gewijzigd'] ) ) . '</span></div>';
		$h .= '<div class="t">' . esc_html( $o['functie'] ) . '</div>';
		$h .= '<p><span class="salaris salaris--l">' . esc_html( deedni_range( $o['salaris_min'], $o['salaris_max'] ) ) . '</span> per maand</p>';
		$h .= '<p class="fine">Reageer binnen ' . (int) get_option( 'deedni_reactietermijn', 5 ) . ' dagen. Je naam blijft verborgen tot jij ja zegt.</p>';
		$h .= '<div class="wacts"><a class="btn btn--primary" href="' . esc_url( deedni_page_url( 'dashboard' ) . '#aanbod-' . $o['id'] ) . '">Bekijk aanbod</a>';
		if ( $n > 1 ) {
			$h .= '<a class="btn btn--ghost" href="' . esc_url( deedni_page_url( 'dashboard' ) . '#aanbiedingen' ) . '">Alle aanbiedingen (' . $n . ')</a>';
		}
		$h .= '</div></article>';
	} else {
		$h .= '<article class="wcard" aria-label="Je profiel"><div class="t">Je profiel is ' . (int) $c['procent'] . '% compleet</div><p class="fine">' . esc_html( $a['k_leeg'] ) . '</p>';
		$h .= '<div class="wacts"><a class="btn btn--primary" href="' . esc_url( deedni_page_url( 'profiel' ) ) . '">Profiel aanvullen</a><a class="btn btn--ghost" href="' . esc_url( deedni_page_url( 'dashboard' ) ) . '">Naar je aanbiedingen</a></div></article>';
	}
	return $h . '</div></section>';
}

/** Een kandidaat als gegevens voor de matchkaart op de homepage (JSON). */
function deedni_match_kaart_data( $k ) {
	$kpis = array();
	foreach ( $k['projecten'] as $p ) {
		foreach ( $p['resultaten'] as $r ) {
			if ( $r['cijfer'] && count( $kpis ) < 2 ) {
				$kpis[] = array( $r['cijfer'], wp_trim_words( $r['tekst'], 3, '' ) );
			}
		}
	}
	if ( $k['projecten'] ) {
		$kpis[] = array( (string) count( $k['projecten'] ), 1 === count( $k['projecten'] ) ? 'case' : 'cases' );
	}
	if ( $k['ervaring_jaren'] ) {
		$kpis[] = array( $k['ervaring_jaren'] . ' jr', 'ervaring' );
	}
	$meta = array_filter(
		array(
			$k['salaris_min'] ? 'vanaf ' . deedni_euro( deedni_salaris_afgerond( $k['salaris_min'] ) ) : '',
			$k['uren'] ? $k['uren'] . ' uur' : '',
			deedni_label( 'beschikbaarheid', $k['beschikbaarheid'] ),
			deedni_regio_tekst( $k ),
		)
	);
	$sub = implode( ' · ', array_filter( array( $k['functietitel'], $k['ervaring_jaren'] ? $k['ervaring_jaren'] . ' jaar' : '', deedni_regio_tekst( $k ) ) ) );
	return array(
		'id'    => $k['id'],
		'code'  => $k['code'],
		'p'     => (int) $k['match']['procent'],
		't'     => $k['functietitel'],
		'meta'  => implode( ' · ', $meta ),
		'k'     => array_slice( $kpis, 0, 3 ),
		'w'     => array_slice( wp_list_pluck( array_filter( $k['match']['redenen'], function ( $r ) {
			return $r[1];
		} ), 0 ), 0, 3 ),
		'url'   => $k['url'],
		'vul'   => array(
			'kandidaat_id' => $k['id'],
			'aanbod_id'    => '',
			'functie'      => $k['functietitel'],
			'salaris_min'  => $k['salaris_min'] ? deedni_number( deedni_salaris_afgerond( $k['salaris_min'] ) ) : '',
			'salaris_max'  => $k['salaris_min'] ? deedni_number( deedni_salaris_afgerond( $k['salaris_min'] ) + 600 ) : '',
		),
		'tekst' => array( 'kop' => 'Aanbod sturen aan', 'code' => $k['code'], 'sub' => $sub ),
	);
}

function deedni_bedrijf_voorkeuren_tekst( $b ) {
	$delen = array_merge(
		deedni_term_lijst( $b['zoek_specialisme'], 'specialisme' ),
		array_slice( deedni_term_lijst( $b['zoek_tools'], 'vaardigheid' ), 0, 2 ),
		deedni_term_lijst( $b['zoek_provincie'], 'provincie' )
	);
	if ( $b['zoek_salaris_max'] ) {
		$delen[] = deedni_euro( $b['zoek_salaris_min'] ) . ' tot ' . deedni_euro( $b['zoek_salaris_max'] );
	}
	return $delen ? implode( ', ', $delen ) : '';
}

function deedni_render_welkom_werkgever( $a ) {
	$bid  = deedni_profile_id( 0, 'bedrijf' );
	$b    = deedni_bedrijf( $bid );
	$naam = $bid ? $b['naam'] : wp_get_current_user()->display_name;
	$h    = '<section class="welkom" aria-labelledby="h-welkom"><div class="wrap welkom-grid">';

	if ( ! deedni_can_search() ) {
		$h .= '<div><h1 id="h-welkom">Welkom, <em>' . esc_html( $naam ) . '</em>.</h1><p class="lead welkom-lead">' . esc_html( $a['w_wacht'] ) . '</p></div>';
		$h .= '<article class="wcard"><div class="t">Je account wacht op goedkeuring</div><p class="fine">Je krijgt een mail zodra je kunt zoeken.</p><div class="wacts"><a class="btn btn--primary" href="' . esc_url( deedni_page_url( 'profiel' ) ) . '">Bedrijfsprofiel aanvullen</a></div></article>';
		return $h . '</div></section>';
	}

	$best        = deedni_beste_matches( 12 );
	$voorkeuren  = deedni_bedrijf_voorkeuren_tekst( $b );
	$kop         = str_replace( array( '{aantal}', '{bedrijf}' ), array( (string) $best['totaal'], $naam ), $a['w_kop'] );
	if ( 1 === (int) $best['totaal'] ) {
		$kop = str_replace( array( '1 marketeers', ' passen ' ), array( '1 marketeer', ' past ' ), $kop );
	}
	$tekst       = $voorkeuren ? str_replace( '{voorkeuren}', $voorkeuren, $a['w_tekst'] ) : 'Vul bij je bedrijfsprofiel in wie je zoekt, dan berekenen we het matchpercentage. Liever zelf zoeken?';
	$h          .= '<div><h1 id="h-welkom">' . str_replace( 'class="soft"', 'class="em"', deedni_tekst( $kop ) ) . '</h1>';
	$h          .= '<p class="lead welkom-lead">' . esc_html( $tekst ) . ' <a href="' . esc_url( deedni_page_url( 'zoeken' ) ) . '">Naar alle filters</a>.</p></div>';

	if ( ! $best['items'] ) {
		$h .= '<article class="wcard"><div class="t">Nog geen nieuwe matches</div><p class="fine">Er staan op dit moment geen profielen online die je nog niet hebt bekeken of bewaard.</p><div class="wacts"><a class="btn btn--primary" href="' . esc_url( deedni_page_url( 'zoeken' ) ) . '">Kandidaten zoeken</a></div></article>';
		return $h . '</div></section>';
	}
	deedni_popup_nodig( 'aanbod-sturen' );
	$data = array_map( 'deedni_match_kaart_data', $best['items'] );
	$m    = $data[0];

	$h .= '<div data-matches="' . esc_attr( wp_json_encode( $data ) ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'deedni_bewaren' ) ) . '">';
	$h .= '<div class="between welkom-teller"><span class="fine">Beste matches</span><span class="fine" aria-live="polite"><span data-m="nr">1</span> / ' . count( $data ) . '</span></div>';
	$h .= '<article class="wcard is-in" data-m-kaart aria-live="polite">';
	$h .= '<div class="between"><strong data-m="code">' . esc_html( $m['code'] ) . '</strong>';
	if ( ! empty( $best['items'][0]['match']['ingevuld'] ) ) {
		$h .= '<span class="match"><span class="ring" data-m="ring" style="--p:' . (int) $m['p'] . '"><span data-m="pct">' . (int) $m['p'] . '%</span></span>match</span>';
	}
	$h .= '</div><div class="t" data-m="titel">' . esc_html( $m['t'] ) . '</div><p class="fine" data-m="meta">' . esc_html( $m['meta'] ) . '</p>';
	$h .= '<div class="kpis" data-m="kpis"></div><div data-m="why-blok"><strong class="why-kop">Waarom deze match</strong><ul class="why" data-m="why"></ul></div>';
	$h .= '<div class="wacts"><button type="button" class="btn btn--ghost" data-m-actie="niet_nu">Niet nu</button><button type="button" class="btn btn--ghost" data-m-actie="aan">Bewaren</button><a class="btn btn--ghost" href="' . esc_url( $m['url'] ) . '" data-m="url">Bekijk profiel</a><button type="button" class="btn btn--primary" data-open="aanbod-sturen" data-m="aanbod">Stuur aanbod</button></div>';
	$h .= '</article><p class="fine welkom-melding" data-m="melding" role="status">Bewaard en Niet nu vind je terug op je overzicht.</p></div>';
	return $h . '</div></section>';
}

/* =========================================================================
 * Inloggen en account maken
 * ========================================================================= */

deedni_blok(
	'inloggen',
	array(
		'titel'        => 'Inloggen en account maken',
		'beschrijving' => 'Links inloggen, rechts een account maken, als marketeer of werkgever.',
		'icoon'        => 'lock',
		'stijl'        => 'deedni-app',
		'script'       => 'deedni-app',
		'velden'       => array(
			'kop_login'    => array( 'type' => 'text', 'label' => 'Kop inloggen', 'standaard' => 'Inloggen' ),
			'kop_account'  => array( 'type' => 'text', 'label' => 'Kop account maken', 'standaard' => 'Account maken' ),
			'hint_k'       => array( 'type' => 'text', 'label' => 'Uitleg bij marketeer', 'standaard' => 'Bedrijven zien je naam pas als jij ja zegt.' ),
			'hint_w'       => array( 'type' => 'text', 'label' => 'Uitleg bij werkgever', 'standaard' => 'Gebruik je zakelijke e-mailadres. We controleren elk bedrijf.' ),
			'onder'        => array( 'type' => 'text', 'label' => 'Tekst onder het formulier', 'standaard' => 'Gratis voor marketeers. Bedrijven betalen alleen bij een aanname.' ),
			'label_k'      => array( 'type' => 'text', 'label' => 'Naam rol 1', 'standaard' => 'Marketeer' ),
			'label_w'      => array( 'type' => 'text', 'label' => 'Naam rol 2', 'standaard' => 'Werkgever' ),
		),
		'render'       => 'deedni_render_inloggen',
	)
);

function deedni_render_inloggen( $a ) {
	if ( is_user_logged_in() && ! deedni_in_editor() ) {
		$u = wp_get_current_user();
		return '<div class="wrap page"><div class="card stack auth-ingelogd"><h1>Je bent ingelogd</h1><p class="lead">Ingelogd als ' . esc_html( $u->display_name ) . '.</p><div class="row"><a class="btn btn--primary" href="' . esc_url( deedni_page_url( 'dashboard' ) ) . '">Naar je overzicht</a><a class="btn btn--ghost" href="' . esc_url( wp_logout_url() ) . '">Uitloggen</a></div></div></div>';
	}
	$reg      = deedni_flash( 'registreren' );
	$login    = deedni_flash( 'inloggen' );
	$data     = $reg && isset( $reg['data'] ) ? $reg['data'] : array( 'rol' => isset( $_GET['rol'] ) && 'werkgever' === $_GET['rol'] ? 'werkgever' : 'kandidaat', 'naam' => '', 'email' => '' ); // phpcs:ignore WordPress.Security.NonceVerification
	$err      = $reg && isset( $reg['errors'] ) ? $reg['errors'] : array();
	$redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$wg       = 'werkgever' === $data['rol'];
	$fout     = function ( $key ) use ( $err ) {
		return isset( $err[ $key ] ) ? '<span class="field-error" id="fout-' . esc_attr( $key ) . '">' . wp_kses_post( $err[ $key ] ) . '</span>' : '';
	};
	$focus_reg = $reg ? ' data-focus' : '';

	$h  = '<div class="wrap page deedni-inloggen">' . deedni_melding_html() . '<div class="auth">';
	// Inloggen.
	$h .= '<form class="card" id="login" method="post" novalidate>';
	$h .= '<input type="hidden" name="deedni_action" value="inloggen">' . deedni_nonce_field( 'deedni_inloggen' );
	if ( $redirect ) {
		$h .= '<input type="hidden" name="redirect_to" value="' . esc_attr( $redirect ) . '">';
	}
	$h .= '<h1>' . esc_html( $a['kop_login'] ) . '</h1>';
	if ( $login && ! empty( $login['errors']['form'] ) ) {
		$h .= deedni_notice( esc_html( $login['errors']['form'] ), 'error' );
	}
	$h .= '<div class="field"><label for="l-mail">E-mailadres</label><input id="l-mail" name="email" type="email" autocomplete="email" required value="' . esc_attr( $login && isset( $login['data']['email'] ) ? $login['data']['email'] : '' ) . '"></div>';
	$h .= '<div class="field"><label for="l-ww">Wachtwoord</label><input id="l-ww" name="wachtwoord" type="password" autocomplete="current-password" required></div>';
	$h .= '<label class="check"><input type="checkbox" name="onthouden" value="1">Ingelogd blijven</label>';
	$h .= '<div class="row"><button class="btn btn--primary" type="submit">Inloggen</button><a href="' . esc_url( wp_lostpassword_url() ) . '">Wachtwoord vergeten?</a></div>';
	$h .= '</form>';

	// Account maken.
	$h .= '<form class="card" id="aanmelden" method="post" novalidate' . $focus_reg . ' data-hints="' . esc_attr( wp_json_encode( array( 'kandidaat' => array( 'Je voornaam', $a['hint_k'] ), 'werkgever' => array( 'Bedrijfsnaam', $a['hint_w'] ) ) ) ) . '">';
	$h .= '<input type="hidden" name="deedni_action" value="registreren">' . deedni_nonce_field( 'deedni_registreren' );
	$h .= '<h2>' . esc_html( $a['kop_account'] ) . '</h2>';
	if ( isset( $err['form'] ) ) {
		$h .= deedni_notice( esc_html( $err['form'] ), 'error' );
	}
	$h .= '<fieldset class="stack-s"><legend>Ik ben</legend><div class="rolkeuze">';
	$h .= '<label><input type="radio" name="rol" value="kandidaat"' . checked( ! $wg, true, false ) . '>' . esc_html( $a['label_k'] ) . '</label>';
	$h .= '<label><input type="radio" name="rol" value="werkgever"' . checked( $wg, true, false ) . '>' . esc_html( $a['label_w'] ) . '</label></div></fieldset>';
	$h .= '<div class="field"><label for="a-naam" data-naam-label>' . ( $wg ? 'Bedrijfsnaam' : 'Je voornaam' ) . '</label><input id="a-naam" name="naam" autocomplete="' . ( $wg ? 'organization' : 'given-name' ) . '" required value="' . esc_attr( $data['naam'] ) . '"' . ( isset( $err['naam'] ) ? ' aria-invalid="true" aria-describedby="fout-naam"' : '' ) . '><span class="hint" data-naam-hint>' . esc_html( $wg ? $a['hint_w'] : $a['hint_k'] ) . '</span>' . $fout( 'naam' ) . '</div>';
	$h .= '<div class="field"><label for="a-mail">E-mailadres</label><input id="a-mail" name="email" type="email" autocomplete="email" required value="' . esc_attr( $data['email'] ) . '"' . ( isset( $err['email'] ) ? ' aria-invalid="true" aria-describedby="fout-email"' : '' ) . '>' . $fout( 'email' ) . '</div>';
	$h .= '<div class="field"><label for="a-ww">Wachtwoord</label><input id="a-ww" name="wachtwoord" type="password" autocomplete="new-password" minlength="8" required' . ( isset( $err['wachtwoord'] ) ? ' aria-invalid="true" aria-describedby="fout-wachtwoord"' : '' ) . '><span class="hint">Minstens 8 tekens.</span>' . $fout( 'wachtwoord' ) . '</div>';
	$privacy = get_privacy_policy_url();
	$h .= '<label class="check"><input type="checkbox" name="akkoord" value="1" required>' . ( $privacy ? 'Ik ga akkoord met de <a href="' . esc_url( $privacy ) . '" target="_blank" rel="noopener">privacyverklaring</a>.' : 'Ik ga akkoord met de privacyverklaring.' ) . '</label>' . $fout( 'akkoord' );
	$h .= '<p class="hp" aria-hidden="true"><label>Website <input type="text" name="website_url" tabindex="-1" autocomplete="off"></label></p>';
	$h .= '<div><button class="btn btn--primary" type="submit">' . esc_html( $a['kop_account'] ) . '</button></div>';
	if ( $a['onder'] ) {
		$h .= '<p class="fine">' . esc_html( $a['onder'] ) . '</p>';
	}
	return $h . '</form></div></div>';
}

/* =========================================================================
 * Overzicht (marketeer: aanbiedingen, werkgever: verstuurd, bewaard, bedrijfsprofiel)
 * ========================================================================= */

deedni_blok(
	'overzicht',
	array(
		'titel'        => 'Overzicht (aanbiedingen)',
		'beschrijving' => 'Marketeers zien hun aanbiedingen met Ja, Ja maar en Nee. Werkgevers zien hun verstuurde aanbiedingen, tegenvoorstellen, bewaarde kandidaten en bedrijfsprofiel.',
		'icoon'        => 'list-view',
		'stijl'        => 'deedni-app',
		'script'       => 'deedni-app',
		'velden'       => array(
			'k_kop'       => array( 'type' => 'text', 'label' => 'Kop', 'groep' => 'Marketeer', 'standaard' => 'Hoi {naam}' ),
			'k_tekst'     => array( 'type' => 'text', 'label' => 'Tekst', 'groep' => 'Marketeer', 'standaard' => 'Je hebt **{aantal} nieuwe aanbiedingen**. Bedrijven zien je naam pas als jij ja zegt.' ),
			'k_tekst_0'   => array( 'type' => 'text', 'label' => 'Tekst zonder nieuwe aanbiedingen', 'groep' => 'Marketeer', 'standaard' => 'Je hebt geen nieuwe aanbiedingen. Bedrijven zien je naam pas als jij ja zegt.' ),
			'k_uitleg'    => array( 'type' => 'textarea', 'label' => 'Uitleg onder elk aanbod', 'groep' => 'Marketeer', 'standaard' => 'Met “Ja, maar...” reageer je met een voorwaarde, bijvoorbeeld een hoger salaris. Je naam blijft verborgen tot je ja zegt.' ),
			'wie_keek'    => array( 'type' => 'toggle', 'label' => 'Toon "Wie keek mee"', 'groep' => 'Marketeer', 'standaard' => true, 'help' => 'Laat zien wat voor bedrijven het profiel bekeken, zonder hun naam.' ),
			'w_kop'       => array( 'type' => 'text', 'label' => 'Kop', 'groep' => 'Werkgever', 'standaard' => 'Hoi {bedrijf}' ),
			'w_tekst'     => array( 'type' => 'text', 'label' => 'Tekst', 'groep' => 'Werkgever', 'standaard' => 'Zoek anonieme profielen en stuur een aanbod met salaris. Je betaalt pas bij een aanname.' ),
			'w_knop'      => array( 'type' => 'text', 'label' => 'Knop', 'groep' => 'Werkgever', 'standaard' => 'Kandidaten zoeken' ),
		),
		'render'       => 'deedni_render_overzicht',
	)
);

function deedni_render_overzicht( $a ) {
	$rol = deedni_rol();
	if ( 'bezoeker' === $rol ) {
		return '<div class="wrap page"><div class="card stack"><h1>Log in om je overzicht te zien</h1><p class="lead">Hier staan je aanbiedingen als je bent ingelogd.</p><div>' . deedni_cta( 'Inloggen', deedni_page_url( 'inloggen' ), 'arrow' ) . '</div></div></div>';
	}
	return 'kandidaat' === $rol ? deedni_render_overzicht_kandidaat( $a ) : deedni_render_overzicht_werkgever( $a );
}

function deedni_aanbod_kaart_kandidaat( $o, $a ) {
	$b      = deedni_bedrijf( $o['bedrijf_id'] );
	$nieuw  = 'verstuurd' === $o['status'];
	$titel  = 'aanbod-' . $o['id'] . '-titel';
	$h      = '<article class="card offer' . ( $nieuw ? ' is-new' : '' ) . '" id="aanbod-' . $o['id'] . '" aria-labelledby="' . $titel . '" data-ajax-doel>';
	$h     .= '<div class="offer-top"><div class="offer-co">' . deedni_logo_html( $b ) . esc_html( $b['naam'] );
	if ( $nieuw ) {
		$h .= ' <span class="tag tag--accent">' . ( $o['ronde'] > 1 ? ( $o['akkoord'] ? 'Akkoord met je voorwaarde' : 'Aangepast aanbod' ) : 'Nieuw' ) . '</span>';
	}
	$h .= '</div><span class="fine">' . esc_html( deedni_geleden( $o['gewijzigd'] ) ) . '</span></div>';
	$h .= '<h3 id="' . $titel . '">' . esc_html( $o['functie'] ) . '</h3>';
	if ( deedni_aanbod_meta( $o ) ) {
		$h .= '<p class="fine">' . esc_html( deedni_aanbod_meta( $o ) ) . '</p>';
	}
	$h .= '<p><span class="salaris salaris--l">' . esc_html( deedni_range( $o['salaris_min'], $o['salaris_max'] ) ) . '</span> per maand</p>';
	if ( $o['bericht'] ) {
		$h .= '<blockquote class="quote">' . nl2br( esc_html( $o['bericht'] ) ) . '</blockquote>';
	}
	if ( $b['soort'] || $b['plaats'] || $b['website'] ) {
		$h .= '<p class="fine">' . esc_html( implode( ' · ', array_filter( array( $b['soort'], $b['plaats'], $b['medewerkers'] ? $b['medewerkers'] . ' medewerkers' : '' ) ) ) ) . ( $b['website'] ? ' · <a href="' . esc_url( $b['website'] ) . '" target="_blank" rel="noopener">website</a>' : '' ) . '</p>';
	}
	$k = deedni_kandidaat( $o['kandidaat_id'] );
	if ( 'geaccepteerd' === $o['status'] || 'aangenomen' === $o['status'] ) {
		$h .= '<div class="sees"><strong>' . esc_html( $b['naam'] ) . ' ziet nu:</strong> <span class="shown">' . esc_html( deedni_naam( $k ) ) . '</span>, e-mail, telefoon en cv</div>';
	} else {
		$h .= '<div class="sees"><strong>' . esc_html( $b['naam'] ) . ' ziet nu:</strong> Naam ' . deedni_redact() . '</div>';
	}
	if ( $nieuw ) {
		deedni_popup_nodig( 'ja-maar' );
		$nonce = deedni_nonce_field( 'deedni_reageren_' . $o['id'] );
		$h    .= '<div class="answer">';
		$h    .= '<form method="post" data-ajax="vervang"><input type="hidden" name="deedni_action" value="aanbod_reageren"><input type="hidden" name="aanbod_id" value="' . $o['id'] . '"><input type="hidden" name="keuze" value="ja">' . $nonce . '<button type="submit" class="btn btn--primary">Ja, deel mijn naam</button></form>';
		$h    .= '<button type="button" class="btn btn--ghost" data-open="ja-maar" data-vul="' . esc_attr( wp_json_encode( array( 'aanbod_id' => $o['id'] ) ) ) . '" data-tekst-vul="' . esc_attr( wp_json_encode( array( 'bedrijf' => $b['naam'], 'titel' => $o['functie'] . ( $o['standplaats'] ? ' · ' . $o['standplaats'] : '' ), 'salaris' => deedni_range( $o['salaris_min'], $o['salaris_max'] ) ) ) ) . '">Ja, maar...</button>';
		$h    .= '<form method="post" data-ajax="vervang"><input type="hidden" name="deedni_action" value="aanbod_reageren"><input type="hidden" name="aanbod_id" value="' . $o['id'] . '"><input type="hidden" name="keuze" value="nee">' . $nonce . '<button type="submit" class="btn btn--ghost">Nee, bedankt</button></form>';
		$h    .= '</div>';
		$h    .= '<p class="fine">' . esc_html( $a['k_uitleg'] ) . ' <span class="deadline">Reageer binnen ' . (int) get_option( 'deedni_reactietermijn', 5 ) . ' dagen.</span></p>';
	} else {
		$teksten = array(
			'tegenvoorstel' => array( 'maar', 'Je voorwaarde is verstuurd. ' . $b['naam'] . ' past het aanbod aan, gaat akkoord of laat het vallen. Je naam blijft verborgen.' ),
			'geaccepteerd'  => array( 'ok', 'Je naam en contactgegevens zijn gedeeld met ' . $b['naam'] . '. Ze nemen binnen een paar dagen contact op.' ),
			'aangenomen'    => array( 'ok', 'Gefeliciteerd! ' . $b['naam'] . ' heeft gemeld dat je bent aangenomen.' ),
			'afgewezen'     => array( 'nee', 'Afgewezen. ' . $b['naam'] . ' kreeg een nette afwijzing en jij bleef anoniem.' ),
			'gestopt'       => array( 'nee', $b['naam'] . ' is gestopt met dit aanbod. Je bleef anoniem.' ),
		);
		if ( isset( $teksten[ $o['status'] ] ) ) {
			$h .= '<p class="offer-state ' . $teksten[ $o['status'] ][0] . '">' . esc_html( $teksten[ $o['status'] ][1] ) . '</p>';
		}
	}
	return $h . '</article>';
}

function deedni_render_overzicht_kandidaat( $a ) {
	$kid = deedni_profile_id( 0, 'kandidaat' );
	if ( ! $kid && ! deedni_in_editor() ) {
		$kid = deedni_ensure_profile( get_current_user_id(), 'kandidaat' );
	}
	$k        = $kid ? deedni_kandidaat( $kid ) : deedni_kandidaat( 0 );
	$alle     = $kid ? deedni_offers( array( 'kandidaat' => $kid ) ) : array();
	$nieuw    = array_values( array_filter( $alle, function ( $o ) {
		return 'verstuurd' === $o['status'];
	} ) );
	$klaar    = array_values( array_filter( $alle, function ( $o ) {
		return 'verstuurd' !== $o['status'];
	} ) );
	$naam     = $k['voornaam'] ? $k['voornaam'] : wp_get_current_user()->display_name;
	$c        = deedni_compleet( $k );

	$h  = '<div class="wrap page deedni-overzicht deedni-overzicht-k">' . deedni_melding_html();
	$h .= '<div class="hello"><h1>' . esc_html( str_replace( '{naam}', $naam, $a['k_kop'] ) ) . '</h1>';
	$h .= '<p class="lead">' . deedni_tekst( str_replace( array( '{aantal}', '1 nieuwe aanbiedingen' ), array( (string) count( $nieuw ), '1 nieuw aanbod' ), $nieuw ? $a['k_tekst'] : $a['k_tekst_0'] ) ) . '</p>';
	if ( ! $k['zichtbaar'] ) {
		$h .= deedni_notice( 'Je profiel staat nog niet online. <a href="' . esc_url( deedni_page_url( 'profiel' ) . '#controleren' ) . '">Controleer en publiceer je profiel</a>, dan kunnen bedrijven je vinden.', 'info' );
	}
	$h .= '</div><div class="split">';

	$h .= '<section id="aanbiedingen" class="stack" aria-labelledby="aanbod-kop" data-tabs><div class="between"><h2 id="aanbod-kop">Aanbiedingen</h2>';
	$h .= '<div class="seg" role="tablist" aria-label="Aanbiedingen filteren"><button type="button" role="tab" id="tab-nieuw" aria-selected="true" aria-controls="paneel-nieuw">Nieuw (' . count( $nieuw ) . ')</button><button type="button" role="tab" id="tab-klaar" aria-selected="false" aria-controls="paneel-klaar" tabindex="-1">Beantwoord (' . count( $klaar ) . ')</button></div></div>';
	$h .= '<div id="paneel-nieuw" role="tabpanel" aria-labelledby="tab-nieuw" class="stack">';
	if ( $nieuw ) {
		foreach ( $nieuw as $o ) {
			$h .= deedni_aanbod_kaart_kandidaat( $o, $a );
		}
	} else {
		$h .= '<div class="card empty"><h3>Nog geen nieuw aanbod</h3><p class="fine">Zodra een bedrijf je een aanbod stuurt, staat het hier en krijg je een mail.</p></div>';
	}
	$h .= '</div><div id="paneel-klaar" role="tabpanel" aria-labelledby="tab-klaar" class="stack" hidden>';
	if ( $klaar ) {
		$h .= '<ul class="card card--flush done-list">';
		foreach ( $klaar as $o ) {
			$tags = array(
				'tegenvoorstel' => '<span class="tag tag--accent">Tegenvoorstel gestuurd</span>',
				'geaccepteerd'  => '<span class="tag tag--ok">Ja, naam gedeeld</span>',
				'aangenomen'    => '<span class="tag tag--ok">Aangenomen</span>',
				'afgewezen'     => '<span class="tag">Afgewezen</span>',
				'gestopt'       => '<span class="tag">Gestopt door bedrijf</span>',
			);
			$h .= '<li id="aanbod-' . $o['id'] . '"><span><strong>' . esc_html( get_the_title( $o['bedrijf_id'] ) ) . '</strong><br><span class="fine">' . esc_html( $o['functie'] . ( $o['standplaats'] ? ', ' . $o['standplaats'] : '' ) . ' · ' . deedni_range( $o['salaris_min'], $o['salaris_max'] ) ) . '</span></span>' . ( isset( $tags[ $o['status'] ] ) ? $tags[ $o['status'] ] : '' ) . '</li>';
		}
		$h .= '</ul>';
	} else {
		$h .= '<div class="card empty"><p class="fine">Hier komen de aanbiedingen waarop je hebt gereageerd.</p></div>';
	}
	$h .= '</div></section>';

	// Zijkolom.
	$h .= '<aside class="side" aria-label="Je profiel in cijfers"><dl class="stats"><div><dt>keer bekeken deze week</dt><dd>' . (int) deedni_aantal_bekeken( $kid ) . '</dd></div><div><dt>keer bewaard door bedrijven</dt><dd>' . (int) deedni_aantal_bewaard_door( $kid ) . '</dd></div><div><dt>van je profiel ingevuld</dt><dd>' . (int) $c['procent'] . '%</dd></div></dl>';
	$h .= '<section class="card stack" aria-labelledby="profiel-kop"><div class="between"><h3 id="profiel-kop">Je profiel</h3><span class="fine">' . (int) $c['procent'] . '% compleet</span></div>';
	$h .= '<div class="bar" role="img" aria-label="' . (int) $c['procent'] . ' procent compleet"><i style="width:' . (int) $c['procent'] . '%"></i></div>';
	if ( $c['todo'] ) {
		$h .= '<ul class="todo">';
		foreach ( array_slice( $c['todo'], 0, 3 ) as $t ) {
			$h .= '<li><span>' . esc_html( $t[0] ) . '</span><a href="' . esc_url( deedni_page_url( 'profiel' ) . '#' . $t[1] ) . '">Invullen</a></li>';
		}
		$h .= '</ul>';
	}
	$h .= '<a class="btn btn--ghost btn--small" href="' . esc_url( deedni_page_url( 'profiel' ) ) . '">Profiel bekijken</a></section>';
	if ( $a['wie_keek'] && get_option( 'deedni_wie_keek_mee', 1 ) ) {
		$wie = deedni_wie_keek_mee( $kid );
		$h  .= '<section class="card stack" aria-labelledby="keek-kop"><h3 id="keek-kop">Wie keek mee</h3>';
		if ( $wie ) {
			$h .= '<ul class="watch">';
			foreach ( $wie as $w ) {
				$h .= '<li><span>' . esc_html( $w['tekst'] ) . '</span><span>' . esc_html( $w['wanneer'] ) . '</span></li>';
			}
			$h .= '</ul>';
		} else {
			$h .= '<p class="fine">Nog niemand. Zodra een bedrijf je profiel opent, zie je hier wat voor bedrijf het is.</p>';
		}
		$h .= '<p class="fine">Je ziet nooit de naam van het bedrijf, tenzij het je een aanbod stuurt.</p></section>';
	}
	return $h . '</aside></div></div>';
}

function deedni_render_overzicht_werkgever( $a ) {
	$uid  = get_current_user_id();
	$bid  = deedni_profile_id( $uid, 'bedrijf' );
	$b    = deedni_bedrijf( $bid );
	$naam = $bid ? $b['naam'] : wp_get_current_user()->display_name;
	$alle = deedni_offers( array( 'werkgever' => $uid ) );
	$per  = array( 'verstuurd' => array(), 'tegenvoorstel' => array(), 'geaccepteerd' => array(), 'afgewezen' => array(), 'gestopt' => array(), 'aangenomen' => array() );
	foreach ( $alle as $o ) {
		$per[ isset( $per[ $o['status'] ] ) ? $o['status'] : 'verstuurd' ][] = $o;
	}
	$bewaard = array_values( array_filter( deedni_bewaard(), function ( $id ) {
		return 'kandidaat' === get_post_type( $id );
	} ) );
	$open    = array_values( array_filter( $alle, function ( $o ) {
		return ! in_array( $o['status'], array( 'afgewezen', 'gestopt' ), true );
	} ) );
	$weg     = array_values( array_filter( $alle, function ( $o ) {
		return in_array( $o['status'], array( 'afgewezen', 'gestopt' ), true );
	} ) );

	$h  = '<div class="wrap page deedni-overzicht deedni-overzicht-w">' . deedni_melding_html();
	$h .= '<div class="hello"><h1>' . esc_html( str_replace( '{bedrijf}', $naam, $a['w_kop'] ) ) . '</h1><p class="lead">' . deedni_tekst( $a['w_tekst'] ) . '</p>';
	if ( ! deedni_can_search() ) {
		$h .= deedni_notice( 'Je account wacht op goedkeuring. Je krijgt een mail zodra je kunt zoeken. Vul intussen je <a href="' . esc_url( deedni_page_url( 'profiel' ) ) . '">bedrijfsprofiel</a> aan.', 'info' );
	} else {
		$h .= deedni_cta( $a['w_knop'], deedni_page_url( 'zoeken' ), 'search' );
	}
	$h .= '</div>';

	$h .= '<dl class="stats counter"><div><dt>aanbiedingen verstuurd</dt><dd>' . count( $alle ) . '</dd></div><div><dt>wacht op antwoord</dt><dd>' . count( $per['verstuurd'] ) . '</dd></div><div><dt>tegenvoorstel</dt><dd>' . count( $per['tegenvoorstel'] ) . '</dd></div><div><dt>keer ja gezegd</dt><dd>' . ( count( $per['geaccepteerd'] ) + count( $per['aangenomen'] ) ) . '</dd></div><div><dt>kandidaten bewaard</dt><dd>' . count( $bewaard ) . '</dd></div></dl>';

	// Verstuurd.
	$h .= '<section class="stack" id="verstuurd" aria-labelledby="h-verstuurd"><div class="between"><h2 id="h-verstuurd">Verstuurd <span class="count">(' . count( $open ) . ')</span></h2></div>';
	if ( $open ) {
		$h .= '<div class="rows"><div class="r r--head" aria-hidden="true"><span>Kandidaat</span><span>Aanbod</span><span>Verstuurd</span><span>Status</span></div>';
		foreach ( $open as $o ) {
			$h .= deedni_aanbod_rij_werkgever( $o );
		}
		$h .= '</div>';
	} else {
		$h .= '<div class="card empty"><p class="fine">Je hebt nog geen aanbod verstuurd. <a href="' . esc_url( deedni_page_url( 'zoeken' ) ) . '">Zoek kandidaten</a> en stuur een aanbod met salaris.</p></div>';
	}
	$h .= '</section>';

	// Bewaard.
	$h .= '<section class="stack" id="bewaard" aria-labelledby="h-bewaard"><div class="between"><h2 id="h-bewaard">Bewaard <span class="count">(' . count( $bewaard ) . ')</span></h2><span class="fine">Kandidaten waar je later op terugkomt</span></div>';
	if ( $bewaard ) {
		$h .= '<ul class="rows saved">';
		foreach ( $bewaard as $id ) {
			$k  = deedni_kandidaat( $id );
			$h .= '<li><div><a href="' . esc_url( $k['url'] ) . '" class="strong-link">' . esc_html( $k['code'] ) . '</a><div class="sub fine">' . esc_html( implode( ', ', array_filter( array( $k['functietitel'], $k['ervaring_jaren'] ? $k['ervaring_jaren'] . ' jaar' : '', deedni_regio_tekst( $k ) ) ) ) ) . '</div>';
			if ( $k['salaris_min'] ) {
				$h .= '<div class="mt-xs"><span class="salaris">vanaf ' . esc_html( deedni_euro( deedni_salaris_afgerond( $k['salaris_min'] ) ) ) . '</span></div>';
			}
			$h .= '</div><div class="row"><a class="btn btn--ghost btn--small" href="' . esc_url( $k['url'] ) . '">Bekijk profiel</a>' . ( $k['zichtbaar'] ? deedni_aanbod_knop( $k, 'Stuur aanbod' ) : '<span class="fine">Niet meer online</span>' ) . '</div></li>';
		}
		$h .= '</ul>';
	} else {
		$h .= '<div class="card empty"><p class="fine">Bewaar een kandidaat met het bladwijzertje, dan staat die hier.</p></div>';
	}
	$h .= '</section>';

	// Afgewezen en gestopt.
	if ( $weg ) {
		$h .= '<section class="stack" aria-labelledby="h-afgewezen"><div class="between"><h2 id="h-afgewezen">Afgewezen en gestopt <span class="count">(' . count( $weg ) . ')</span></h2><span class="fine">Een afwijzing is netjes en anoniem</span></div><div class="rows"><div class="r r--head" aria-hidden="true"><span>Kandidaat</span><span>Aanbod</span><span>Verstuurd</span><span>Status</span></div>';
		foreach ( $weg as $o ) {
			$h .= deedni_aanbod_rij_werkgever( $o );
		}
		$h .= '</div></section>';
	}

	// Bedrijfsprofiel.
	$h .= '<section class="stack" id="bedrijfsprofiel" aria-labelledby="h-bedrijf"><div class="between"><h2 id="h-bedrijf">Bedrijfsprofiel</h2><span class="fine">Kandidaten zien dit bij elk aanbod</span></div><div class="bedrijf">';
	$h .= '<div class="card stack"><div class="row">' . deedni_logo_html( $b, 'logo-blok' ) . '<div><strong class="strong-l">' . esc_html( $naam ) . '</strong><div class="fine">' . esc_html( $b['soort'] ) . '</div></div></div>';
	$h .= '<dl class="kv"><dt>Plaats</dt><dd>' . esc_html( $b['plaats'] ? $b['plaats'] : '–' ) . '</dd><dt>Website</dt><dd>' . esc_html( $b['website'] ? preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( $b['website'] ) ) : '–' ) . '</dd><dt>Medewerkers</dt><dd>' . esc_html( $b['medewerkers'] ? $b['medewerkers'] : '–' ) . '</dd><dt>Account</dt><dd>' . ( $b['goedgekeurd'] || deedni_can_search() ? '<span class="tag tag--ok">Goedgekeurd</span>' : '<span class="tag">Wacht op goedkeuring</span>' ) . '</dd></dl>';
	$h .= '<div><a class="btn btn--ghost btn--small" href="' . esc_url( deedni_page_url( 'profiel' ) ) . '">Wijzig bedrijfsgegevens</a></div></div>';
	$h .= '<div class="card stack"><h3>Wie je zoekt</h3><p class="fine">Hierop berekenen we je matches.</p><dl class="kv">';
	$h .= '<dt>Specialisme</dt><dd>' . esc_html( implode( ', ', deedni_term_lijst( $b['zoek_specialisme'], 'specialisme' ) ) ?: '–' ) . '</dd>';
	$h .= '<dt>Tools</dt><dd>' . esc_html( implode( ', ', deedni_term_lijst( $b['zoek_tools'], 'vaardigheid' ) ) ?: '–' ) . '</dd>';
	$h .= '<dt>Salarisbudget</dt><dd>' . ( $b['zoek_salaris_max'] ? '<span class="salaris">' . esc_html( deedni_range( $b['zoek_salaris_min'], $b['zoek_salaris_max'] ) ) . '</span>' : '–' ) . '</dd>';
	$h .= '<dt>Regio</dt><dd>' . esc_html( implode( ', ', array_filter( array( implode( ', ', deedni_term_lijst( $b['zoek_provincie'], 'provincie' ) ), strtolower( deedni_werkplek_tekst( $b['zoek_werkplek'] ) ) ) ) ) ?: '–' ) . '</dd>';
	$h .= '<dt>Uren</dt><dd>' . esc_html( $b['zoek_uren'] ? $b['zoek_uren'] : '–' ) . '</dd></dl>';
	$h .= '<div><a class="btn btn--ghost btn--small" href="' . esc_url( deedni_page_url( 'profiel' ) . '#wie-je-zoekt' ) . '">Wijzig voorkeuren</a></div></div>';
	return $h . '</div></section></div>';
}

function deedni_aanbod_rij_werkgever( $o ) {
	$k   = deedni_kandidaat( $o['kandidaat_id'] );
	$h   = '<div class="r" id="aanbod-' . $o['id'] . '"><div><a href="' . esc_url( $k['url'] ) . '" class="strong-link">' . esc_html( $k['code'] ) . '</a><div class="sub">' . esc_html( $k['functietitel'] ) . '</div>';
	if ( in_array( $o['status'], array( 'geaccepteerd', 'aangenomen' ), true ) ) {
		$contact = array_filter( array( $k['email'], $k['telefoon'] ) );
		$h      .= '<div class="contact"><strong>' . esc_html( deedni_naam( $k ) ) . '</strong><div class="sub">' . esc_html( implode( ' · ', $contact ) ) . '</div>';
		if ( deedni_cv_url( $k['id'] ) ) {
			$h .= '<a href="' . esc_url( deedni_cv_url( $k['id'] ) ) . '">Download cv</a>';
		}
		$h .= '</div>';
	}
	if ( in_array( $o['status'], array( 'afgewezen', 'gestopt' ), true ) ) {
		$h .= '<div class="sub mt-xs">De kandidaat bleef anoniem.</div>';
	}
	$h .= '</div><div><span class="lbl">Aanbod</span>' . esc_html( deedni_range( $o['salaris_min'], $o['salaris_max'] ) ) . ' per maand<div class="sub">' . esc_html( $o['functie'] ) . '</div></div>';
	$h .= '<div class="sub"><span class="lbl">Verstuurd</span>' . esc_html( deedni_geleden( $o['datum'] ) ) . '</div>';
	$h .= '<div class="stack-s status-cel">' . deedni_status_tag( $o['status'], $o['akkoord'] );
	if ( 'geaccepteerd' === $o['status'] ) {
		$h .= '<form method="post" data-ajax="vervang" data-ajax-zelf><input type="hidden" name="deedni_action" value="aanbod_aangenomen"><input type="hidden" name="aanbod_id" value="' . $o['id'] . '">' . deedni_nonce_field( 'deedni_aangenomen_' . $o['id'] ) . '<button type="submit" class="link-btn">Kandidaat aangenomen melden</button></form>';
	}
	$h .= '</div></div>';

	if ( 'tegenvoorstel' === $o['status'] ) {
		$t     = $o['tegen'];
		$wens  = array();
		foreach ( $t['voorwaarden'] as $v ) {
			if ( 'salaris' !== $v && 'anders' !== $v ) {
				$wens[] = deedni_label( 'voorwaarden', $v );
			}
		}
		if ( $t['anders'] ) {
			$wens[] = $t['anders'];
		}
		if ( $t['wens'] ) {
			$wens[] = $t['wens'];
		}
		$nonce = deedni_nonce_field( 'deedni_tegen_' . $o['id'] );
		$h    .= '<div class="reply" data-ajax-doel><div class="inner"><div class="row"><strong>Reactie van ' . esc_html( $k['code'] ) . '</strong><span class="tag tag--accent">Ja, maar</span></div>';
		if ( $t['bericht'] ) {
			$h .= '<blockquote>' . nl2br( esc_html( $t['bericht'] ) ) . '</blockquote>';
		}
		$h .= '<div class="row">';
		if ( $t['min'] ) {
			$h .= '<span class="fine">Gevraagd salaris</span><span class="salaris">' . esc_html( deedni_range( $t['min'], $t['max'] ) ) . '</span>';
		}
		foreach ( array_unique( $wens ) as $w ) {
			$h .= '<span class="tool">' . esc_html( $w ) . '</span>';
		}
		$h .= '</div><div class="row">' . deedni_aanbod_knop( $k, 'Pas aanbod aan', 'btn btn--primary btn--small', $o );
		$h .= '<form method="post" data-ajax="vervang"><input type="hidden" name="deedni_action" value="tegen_reactie"><input type="hidden" name="aanbod_id" value="' . $o['id'] . '"><input type="hidden" name="keuze" value="akkoord">' . $nonce . '<button type="submit" class="btn btn--ghost btn--small">Ga akkoord</button></form>';
		$h .= '<form method="post" data-ajax="vervang"><input type="hidden" name="deedni_action" value="tegen_reactie"><input type="hidden" name="aanbod_id" value="' . $o['id'] . '"><input type="hidden" name="keuze" value="vallen">' . $nonce . '<button type="submit" class="btn btn--ghost btn--small">Laat vallen</button></form>';
		$h .= '</div><p class="fine">De naam van de kandidaat blijft verborgen tot er ja is gezegd.</p></div></div>';
	}
	return $h;
}

/* =========================================================================
 * Mijn profiel (marketeer: vijf stappen, werkgever: bedrijfsprofiel)
 * ========================================================================= */

deedni_blok(
	'mijn-profiel',
	array(
		'titel'        => 'Mijn profiel',
		'beschrijving' => 'Marketeers maken hun profiel in vijf stappen met een live voorbeeld. Werkgevers vullen hier hun bedrijfsprofiel en "Wie je zoekt" in.',
		'icoon'        => 'id',
		'stijl'        => 'deedni-app',
		'script'       => 'deedni-app',
		'velden'       => array(
			'stap1_kop'  => array( 'type' => 'text', 'label' => 'Stap 1: kop', 'groep' => 'Marketeer', 'standaard' => 'Wie ben je?' ),
			'stap1_lead' => array( 'type' => 'textarea', 'label' => 'Stap 1: tekst', 'groep' => 'Marketeer', 'standaard' => 'Bij elk veld staat wie het ziet. Na jouw ja blijft verborgen tot jij ja zegt op een aanbod.' ),
			'stap2_kop'  => array( 'type' => 'text', 'label' => 'Stap 2: kop', 'groep' => 'Marketeer', 'standaard' => 'Waar heb je gewerkt?' ),
			'stap2_lead' => array( 'type' => 'textarea', 'label' => 'Stap 2: tekst', 'groep' => 'Marketeer', 'standaard' => 'De nieuwste bovenaan. Namen van werkgevers blijven verborgen tot jij ja zegt.' ),
			'stap3_kop'  => array( 'type' => 'text', 'label' => 'Stap 3: kop', 'groep' => 'Marketeer', 'standaard' => 'Projecten en cases' ),
			'stap3_lead' => array( 'type' => 'textarea', 'label' => 'Stap 3: tekst', 'groep' => 'Marketeer', 'standaard' => 'Dit is wat je opvalt. Beschrijf per project wat je deed en wat het opleverde, in cijfers. Projectnamen en klanten blijven verborgen.' ),
			'stap4_kop'  => array( 'type' => 'text', 'label' => 'Stap 4: kop', 'groep' => 'Marketeer', 'standaard' => 'Wat zoek je?' ),
			'stap4_lead' => array( 'type' => 'textarea', 'label' => 'Stap 4: tekst', 'groep' => 'Marketeer', 'standaard' => 'Hierop worden je matches berekend. Bedrijven zien een afgerond salaris, nooit je exacte wens.' ),
			'stap5_kop'  => array( 'type' => 'text', 'label' => 'Stap 5: kop', 'groep' => 'Marketeer', 'standaard' => 'Controleren en publiceren' ),
			'stap5_lead' => array( 'type' => 'textarea', 'label' => 'Stap 5: tekst', 'groep' => 'Marketeer', 'standaard' => 'Rechts zie je precies wat bedrijven zien. Klopt alles? Dan zet je je profiel online.' ),
			'w_kop'      => array( 'type' => 'text', 'label' => 'Kop', 'groep' => 'Werkgever', 'standaard' => 'Bedrijfsprofiel' ),
			'w_lead'     => array( 'type' => 'textarea', 'label' => 'Tekst', 'groep' => 'Werkgever', 'standaard' => 'Kandidaten zien je bedrijfsgegevens bij elk aanbod. Met "Wie je zoekt" berekenen we het matchpercentage.' ),
		),
		'render'       => 'deedni_render_mijn_profiel',
	)
);

function deedni_render_mijn_profiel( $a ) {
	$rol = deedni_rol();
	if ( 'bezoeker' === $rol ) {
		return '<div class="wrap page"><div class="card stack"><h1>Log in om je profiel te zien</h1><div>' . deedni_cta( 'Inloggen', deedni_page_url( 'inloggen' ), 'arrow' ) . '</div></div></div>';
	}
	return 'kandidaat' === $rol ? deedni_template( 'mijn-profiel', array( 'a' => $a ) ) : deedni_template( 'bedrijfsprofiel', array( 'a' => $a ) );
}

/* =========================================================================
 * Kandidaten zoeken
 * ========================================================================= */

deedni_blok(
	'zoeken',
	array(
		'titel'        => 'Kandidaten zoeken',
		'beschrijving' => 'Zoeken en filteren in anonieme profielen, gesorteerd op matchpercentage, met de pop-up Aanbod sturen.',
		'icoon'        => 'search',
		'stijl'        => 'deedni-app',
		'script'       => 'deedni-app',
		'velden'       => array(
			'kop'       => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Kandidaten zoeken' ),
			'lead'      => array( 'type' => 'textarea', 'label' => 'Tekst', 'standaard' => 'Anonieme profielen van online marketeers. Je ziet ervaring, tools, cases en salarisindicatie. De naam komt pas als de kandidaat ja zegt op jouw aanbod.' ),
			'gesloten'  => array( 'type' => 'textarea', 'label' => 'Tekst voor wie (nog) niet mag zoeken', 'standaard' => 'Zoeken in profielen kan alleen met een goedgekeurd werkgeversaccount. Maak gratis een account, dan controleren we je bedrijf.' ),
		),
		'render'       => function ( $a ) {
			return deedni_template( 'zoeken', array( 'a' => $a ) );
		},
	)
);

/** Een kaart in de zoekresultaten. */
function deedni_kandidaat_kaart( $k ) {
	$t  = 't-' . $k['id'];
	$h  = '<article class="card cand" aria-labelledby="' . $t . '"><div class="stack-s cand-main">';
	$h .= '<div class="row"><strong>' . esc_html( $k['code'] ) . '</strong>' . deedni_match_tag( $k['match'] ) . '<span class="tag ' . ( 'actief' === $k['zoekstatus'] ? 'tag--ok' : 'tag--accent' ) . '">' . esc_html( deedni_label( 'zoekstatus', $k['zoekstatus'] ) ) . '</span></div>';
	$h .= '<h2 id="' . $t . '"><a href="' . esc_url( $k['url'] ) . '">' . esc_html( $k['functietitel'] ) . '</a></h2>';
	$meta = array_filter( array( $k['ervaring_jaren'] ? $k['ervaring_jaren'] . ' jaar ervaring' : '', deedni_regio_tekst( $k ), deedni_werkplek_tekst( $k['werkplekken'] ), deedni_label( 'beschikbaarheid', $k['beschikbaarheid'] ) ) );
	$h .= '<div class="meta"><span>' . implode( '</span><span>', array_map( 'esc_html', $meta ) ) . '</span></div>';
	$h .= deedni_tools_html( array_slice( $k['tools'], 0, 6 ) );
	$h .= '<div class="hidden-row">' . ( $k['projecten'] ? '<span>' . count( $k['projecten'] ) . ' ' . ( 1 === count( $k['projecten'] ) ? 'case' : 'cases' ) . ' in portfolio</span>' : '' ) . '<span>Naam ' . deedni_redact( 's' ) . '</span><span>Werkgevers ' . deedni_redact() . '</span></div>';
	$h .= '</div><div class="right"><div>' . ( $k['salaris_min'] ? '<div class="fine">Salarisindicatie</div><span class="salaris salaris--l">vanaf ' . esc_html( deedni_euro( deedni_salaris_afgerond( $k['salaris_min'] ) ) ) . '</span>' : '' ) . '</div>';
	$h .= '<div class="acts">' . deedni_bewaar_knop( $k['id'] ) . '<a class="btn btn--ghost btn--small" href="' . esc_url( $k['url'] ) . '">Bekijk profiel</a>' . deedni_aanbod_knop( $k ) . '</div></div>';
	return $h . '</article>';
}

/* =========================================================================
 * Kandidaatprofiel
 * ========================================================================= */

deedni_blok(
	'kandidaat',
	array(
		'titel'        => 'Kandidaatprofiel',
		'beschrijving' => 'Het profiel van één kandidaat: publiek, of met naam en contact na een ja. Staat in het sjabloon "Kandidaat".',
		'icoon'        => 'businessperson',
		'stijl'        => 'deedni-app',
		'script'       => 'deedni-app',
		'multiple'     => false,
		'leeg'         => 'dit blok toont het profiel van de kandidaat die je opent',
		'velden'       => array(
			'terug'      => array( 'type' => 'text', 'label' => 'Terug-link', 'standaard' => '← Terug naar resultaten' ),
			'uitleg_pub' => array( 'type' => 'textarea', 'label' => 'Uitleg bij het publieke profiel', 'standaard' => 'Publieke weergave. Dit zien bedrijven. De zwarte balken zijn namen die verborgen blijven tot de kandidaat ja zegt.' ),
			'uitleg_ja'  => array( 'type' => 'textarea', 'label' => 'Uitleg na een ja', 'standaard' => 'De kandidaat heeft jouw aanbod geaccepteerd. Wat eerst verborgen was, is nu zichtbaar voor jouw bedrijf, in blauw.' ),
		),
		'render'       => function ( $a ) {
			$id = get_the_ID();
			if ( 'kandidaat' !== get_post_type( $id ) ) {
				if ( ! deedni_in_editor() ) {
					return '';
				}
				$ids = deedni_zichtbare_ids();
				$id  = $ids ? $ids[0] : 0;
				if ( ! $id ) {
					return '';
				}
			}
			return deedni_template( 'kandidaat', array( 'a' => $a, 'k' => deedni_kandidaat( $id ) ) );
		},
	)
);
