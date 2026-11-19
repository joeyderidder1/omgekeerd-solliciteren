<?php
/**
 * Zoeken en filteren voor werkgevers, gesorteerd op matchpercentage.
 */

defined( 'ABSPATH' ) || exit;

/** De filters uit de URL, opgeschoond. */
function deedni_search_filters() {
	$get  = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification
	$str  = function ( $key ) use ( $get ) {
		return isset( $get[ $key ] ) && is_string( $get[ $key ] ) ? sanitize_text_field( $get[ $key ] ) : '';
	};
	$list = function ( $key ) use ( $get ) {
		return isset( $get[ $key ] ) ? array_values( array_filter( array_map( 'sanitize_title', (array) $get[ $key ] ) ) ) : array();
	};
	$erv   = $str( 'erv' );
	$besch = $str( 'besch' );
	return array(
		'q'      => $str( 'q' ),
		'spec'   => $list( 'spec' ),
		'tool'   => $list( 'tool' ),
		'regio'  => $list( 'regio' ),
		'plek'   => array_values( array_intersect( $list( 'plek' ), array( 'op_locatie', 'hybride', 'remote' ) ) ),
		'erv'    => in_array( $erv, array( '2', '5' ), true ) ? $erv : '0',
		'besch'  => in_array( $besch, array( '1', '3' ), true ) ? $besch : '0',
		'actief' => '1' === $str( 'actief' ),
		'pagina' => max( 1, absint( $str( 'pagina' ) ) ),
	);
}

/** Alle profielen die werkgevers mogen vinden (zonder filters). */
function deedni_zichtbare_ids() {
	static $ids = null;
	if ( null !== $ids ) {
		return $ids;
	}
	$meta = array(
		'relation' => 'AND',
		array( 'key' => 'profiel_zichtbaar', 'value' => '1' ),
		array( 'key' => 'functietitel', 'value' => '', 'compare' => '!=' ),
	);
	$dagen = (int) get_option( 'deedni_actueel_dagen', 0 );
	if ( $dagen > 0 ) {
		$meta[] = array( 'key' => 'bevestigd_op', 'value' => time() - $dagen * DAY_IN_SECONDS, 'compare' => '>=', 'type' => 'NUMERIC' );
	}
	$ids = array_map(
		'intval',
		get_posts(
			array(
				'post_type'      => 'kandidaat',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => $meta,
				'post__not_in'   => deedni_blocked_kandidaat_ids(),
				'no_found_rows'  => true,
			)
		)
	);
	return $ids;
}

/** Voldoet dit profiel aan de filters? */
function deedni_past_filter( $k, $f ) {
	if ( $f['spec'] && ! array_intersect( $f['spec'], deedni_term_slugs( $k['id'], 'specialisme' ) ) ) {
		return false;
	}
	if ( $f['tool'] && ! array_intersect( $f['tool'], deedni_term_slugs( $k['id'], 'vaardigheid' ) ) ) {
		return false;
	}
	if ( $f['regio'] ) {
		$prov   = deedni_term_slugs( $k['id'], 'provincie' );
		$remote = in_array( 'remote', $f['regio'], true ) && in_array( 'remote', $k['werkplekken'], true );
		if ( ! $remote && ! array_intersect( $f['regio'], $prov ) ) {
			return false;
		}
	}
	if ( $f['plek'] && ! array_intersect( $f['plek'], $k['werkplekken'] ) ) {
		return false;
	}
	if ( '5' === $f['erv'] && $k['ervaring_jaren'] < 5 ) {
		return false;
	}
	if ( '2' === $f['erv'] && ( $k['ervaring_jaren'] < 2 || $k['ervaring_jaren'] >= 5 ) ) {
		return false;
	}
	if ( '1' === $f['besch'] && ! in_array( $k['beschikbaarheid'], array( 'direct', '1_maand' ), true ) ) {
		return false;
	}
	if ( '3' === $f['besch'] && ! in_array( $k['beschikbaarheid'], array( 'direct', '1_maand', '2_3_maanden' ), true ) ) {
		return false;
	}
	if ( $f['actief'] && 'actief' !== $k['zoekstatus'] ) {
		return false;
	}
	if ( $f['q'] ) {
		$hooi = mb_strtolower( implode( ' ', array_merge( array( $k['functietitel'], $k['samenvatting'], $k['regio'], $k['code'] ), $k['tools'], $k['specialisme'], $k['provincie'], wp_list_pluck( $k['werkervaring'], 'functie' ), wp_list_pluck( $k['werkervaring'], 'soort' ), wp_list_pluck( $k['projecten'], 'titel' ), wp_list_pluck( $k['projecten'], 'branche' ) ) ) );
		foreach ( preg_split( '/\s+/', mb_strtolower( $f['q'] ) ) as $woord ) {
			if ( '' !== $woord && false === mb_strpos( $hooi, $woord ) ) {
				return false;
			}
		}
	}
	return true;
}

function deedni_term_slugs( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	return ( $terms && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'slug' ) : array();
}

/**
 * Zoekt en sorteert. Geeft array( 'items' => kandidaten met 'match', 'totaal', 'alle', 'paginas', 'telling' ).
 */
function deedni_zoek( $f, $per_pagina = 10 ) {
	$alle  = deedni_zichtbare_ids();
	$items = array();
	$tel   = array( 'spec' => array(), 'tool' => array(), 'regio' => array(), 'plek' => array(), 'erv' => array( '0' => 0, '2' => 0, '5' => 0 ), 'besch' => array( '0' => 0, '1' => 0, '3' => 0 ) );
	foreach ( $alle as $id ) {
		$k = deedni_kandidaat( $id );
		// Aantallen bij de filters: over alle zichtbare profielen.
		foreach ( deedni_term_slugs( $id, 'specialisme' ) as $s ) {
			$tel['spec'][ $s ] = ( isset( $tel['spec'][ $s ] ) ? $tel['spec'][ $s ] : 0 ) + 1;
		}
		foreach ( deedni_term_slugs( $id, 'vaardigheid' ) as $s ) {
			$tel['tool'][ $s ] = ( isset( $tel['tool'][ $s ] ) ? $tel['tool'][ $s ] : 0 ) + 1;
		}
		foreach ( deedni_term_slugs( $id, 'provincie' ) as $s ) {
			$tel['regio'][ $s ] = ( isset( $tel['regio'][ $s ] ) ? $tel['regio'][ $s ] : 0 ) + 1;
		}
		foreach ( $k['werkplekken'] as $s ) {
			$tel['plek'][ $s ] = ( isset( $tel['plek'][ $s ] ) ? $tel['plek'][ $s ] : 0 ) + 1;
		}
		if ( in_array( 'remote', $k['werkplekken'], true ) ) {
			$tel['regio']['remote'] = ( isset( $tel['regio']['remote'] ) ? $tel['regio']['remote'] : 0 ) + 1;
		}
		++$tel['erv']['0'];
		if ( $k['ervaring_jaren'] >= 5 ) {
			++$tel['erv']['5'];
		} elseif ( $k['ervaring_jaren'] >= 2 ) {
			++$tel['erv']['2'];
		}
		++$tel['besch']['0'];
		if ( in_array( $k['beschikbaarheid'], array( 'direct', '1_maand' ), true ) ) {
			++$tel['besch']['1'];
		}
		if ( in_array( $k['beschikbaarheid'], array( 'direct', '1_maand', '2_3_maanden' ), true ) ) {
			++$tel['besch']['3'];
		}
		if ( deedni_past_filter( $k, $f ) ) {
			$k['match'] = deedni_match_huidig( $id );
			$items[]    = $k;
		}
	}
	usort(
		$items,
		function ( $a, $b ) {
			if ( $a['match']['procent'] !== $b['match']['procent'] ) {
				return $b['match']['procent'] - $a['match']['procent'];
			}
			return $b['gewijzigd'] - $a['gewijzigd'];
		}
	);
	$totaal  = count( $items );
	$paginas = max( 1, (int) ceil( $totaal / $per_pagina ) );
	$pagina  = min( $f['pagina'], $paginas );
	return array(
		'items'   => array_slice( $items, ( $pagina - 1 ) * $per_pagina, $per_pagina ),
		'totaal'  => $totaal,
		'alle'    => count( $alle ),
		'pagina'  => $pagina,
		'paginas' => $paginas,
		'telling' => $tel,
	);
}

/** Alle termen van een taxonomie, voor de filterlijsten. */
function deedni_filter_terms( $taxonomy ) {
	$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'orderby' => 'name' ) );
	return is_wp_error( $terms ) ? array() : $terms;
}

/** Beste matches voor de homepage van een werkgever (zonder bewaarde en "niet nu"). */
function deedni_beste_matches( $aantal = 12 ) {
	$weg   = array_merge( deedni_bewaard(), deedni_niet_nu() );
	$items = array();
	foreach ( deedni_zichtbare_ids() as $id ) {
		if ( in_array( $id, $weg, true ) || deedni_offers_between( $id, get_current_user_id() ) ) {
			continue;
		}
		$k          = deedni_kandidaat( $id );
		$k['match'] = deedni_match_huidig( $id );
		$items[]    = $k;
	}
	usort(
		$items,
		function ( $a, $b ) {
			return $b['match']['procent'] - $a['match']['procent'];
		}
	);
	return array(
		'items'  => array_slice( $items, 0, $aantal ),
		'totaal' => count( array_filter( $items, function ( $k ) {
			return $k['match']['procent'] >= 60 || empty( $k['match']['ingevuld'] );
		} ) ),
	);
}
