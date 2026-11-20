<?php
/**
 * Matchpercentage: hoe goed past een kandidaat bij "Wie je zoekt" in het bedrijfsprofiel?
 *
 * Punten (samen 100):
 *   specialisme 30, tools 25, salaris binnen budget 20, regio 15, werkplek 10.
 * Onderdelen die het bedrijf niet heeft ingevuld tellen niet mee; het percentage is dan over de rest.
 * Filter 'deedni_match' om de berekening aan te passen.
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array( 'procent' => int, 'redenen' => array( array( tekst, past ) ) )
 */
function deedni_match( $k, $b ) {
	$score   = 0;
	$max     = 0;
	$redenen = array();

	// Specialisme.
	if ( $b['zoek_specialisme'] ) {
		$max += 30;
		if ( array_intersect( $b['zoek_specialisme'], $k['specialisme_ids'] ) ) {
			$score    += 30;
			$redenen[] = array( implode( ', ', array_intersect( $k['specialisme'], deedni_term_lijst( $b['zoek_specialisme'], 'specialisme' ) ) ) . ', het specialisme dat je zoekt', true );
		} elseif ( $k['specialisme'] ) {
			$redenen[] = array( 'Ander specialisme: ' . implode( ', ', $k['specialisme'] ), false );
		}
	}

	// Tools.
	if ( $b['zoek_tools'] ) {
		$max  += 25;
		$common = array_intersect( $b['zoek_tools'], $k['tool_ids'] );
		$score += (int) round( 25 * count( $common ) / count( $b['zoek_tools'] ) );
		if ( $common ) {
			$names     = deedni_term_lijst( $common, 'vaardigheid' );
			$redenen[] = array( deedni_opsomming( $names ) . ', net als jullie', true );
		} else {
			$redenen[] = array( 'Werkt (nog) niet met jullie tools', false );
		}
	}

	// Salaris.
	if ( $b['zoek_salaris_max'] ) {
		$max += 20;
		if ( ! $k['salaris_min'] || $k['salaris_min'] <= $b['zoek_salaris_max'] ) {
			$score    += 20;
			$redenen[] = array( $b['zoek_salaris_min'] && $k['salaris_min'] && $k['salaris_min'] < $b['zoek_salaris_min'] ? 'Onder je salarisbudget' : 'Salarisindicatie past binnen je budget', true );
		} else {
			$te_hoog = $k['salaris_min'] - $b['zoek_salaris_max'];
			$score  += $te_hoog <= 300 ? 10 : 0;
			$redenen[] = array( 'Salariswens ligt boven je budget', false );
		}
	}

	// Regio.
	if ( $b['zoek_provincie'] ) {
		$max += 15;
		if ( array_intersect( $b['zoek_provincie'], $k['provincie_ids'] ) ) {
			$score    += 15;
			$redenen[] = array( 'Woont in jullie regio (' . implode( ', ', array_intersect( $k['provincie'], deedni_term_lijst( $b['zoek_provincie'], 'provincie' ) ) ) . ')', true );
		} elseif ( in_array( 'remote', $k['werkplekken'], true ) ) {
			$score    += 8;
			$redenen[] = array( 'Andere regio, maar wil remote werken', true );
		} else {
			$redenen[] = array( 'Andere regio', false );
		}
	}

	// Werkplek.
	if ( $b['zoek_werkplek'] ) {
		$max += 10;
		if ( ! $k['werkplekken'] || array_intersect( $b['zoek_werkplek'], $k['werkplekken'] ) ) {
			$score    += 10;
			$redenen[] = array( deedni_werkplek_tekst( $k['werkplekken'] ? array_intersect( $k['werkplekken'], $b['zoek_werkplek'] ) : $b['zoek_werkplek'] ) . ', zoals jullie werken', true );
		} else {
			$redenen[] = array( 'Wil ' . strtolower( deedni_werkplek_tekst( $k['werkplekken'] ) ) . ', jullie zoeken ' . strtolower( deedni_werkplek_tekst( $b['zoek_werkplek'] ) ), false );
		}
	}

	$procent = $max ? (int) round( $score / $max * 100 ) : 0;

	// Volgorde: wat past eerst.
	usort(
		$redenen,
		function ( $a, $c ) {
			return (int) $c[1] - (int) $a[1];
		}
	);

	return apply_filters( 'deedni_match', array( 'procent' => $procent, 'redenen' => $redenen, 'ingevuld' => $max > 0 ), $k, $b );
}

/** "Google Ads, Meta Ads en GA4" */
function deedni_opsomming( $items ) {
	$items = array_values( array_filter( (array) $items ) );
	if ( count( $items ) < 2 ) {
		return implode( '', $items );
	}
	$last = array_pop( $items );
	return implode( ', ', $items ) . ' en ' . $last;
}

/** Match voor de ingelogde werkgever, met cache per request. */
function deedni_match_huidig( $kandidaat_id ) {
	static $cache = array();
	if ( isset( $cache[ $kandidaat_id ] ) ) {
		return $cache[ $kandidaat_id ];
	}
	$bedrijf_id = deedni_profile_id( get_current_user_id(), 'bedrijf' );
	if ( ! $bedrijf_id ) {
		$cache[ $kandidaat_id ] = array( 'procent' => 0, 'redenen' => array(), 'ingevuld' => false );
		return $cache[ $kandidaat_id ];
	}
	$cache[ $kandidaat_id ] = deedni_match( deedni_kandidaat( $kandidaat_id ), deedni_bedrijf( $bedrijf_id ) );
	return $cache[ $kandidaat_id ];
}

/** HTML-label "88% match". */
function deedni_match_tag( $match, $suffix = 'match' ) {
	if ( empty( $match['ingevuld'] ) ) {
		return '';
	}
	return '<span class="match-tag" title="Hoe goed dit profiel past bij je bedrijfsprofiel">' . (int) $match['procent'] . '% ' . esc_html( $suffix ) . '</span>';
}
