<?php
/**
 * Zoeken en filteren voor werkgevers.
 */

defined( 'ABSPATH' ) || exit;

/** De filters uit de URL, opgeschoond. */
function deedni_search_filters() {
	$get = wp_unslash( $_GET );
	$str = function ( $key ) use ( $get ) {
		return isset( $get[ $key ] ) && is_string( $get[ $key ] ) ? sanitize_text_field( $get[ $key ] ) : '';
	};
	$vaardigheden = isset( $get['vaardigheid'] ) ? array_filter( array_map( 'sanitize_title', (array) $get['vaardigheid'] ) ) : array();

	return array(
		'q'               => $str( 'q' ),
		'specialisme'     => sanitize_title( $str( 'specialisme' ) ),
		'vaardigheid'     => array_values( $vaardigheden ),
		'provincie'       => sanitize_title( $str( 'provincie' ) ),
		'werkvorm'        => array_key_exists( $str( 'werkvorm' ), deedni_choices( 'werkvorm' ) ) ? $str( 'werkvorm' ) : '',
		'beschikbaarheid' => array_key_exists( $str( 'beschikbaarheid' ), deedni_choices( 'beschikbaarheid' ) ) ? $str( 'beschikbaarheid' ) : '',
		'ervaring'        => absint( $str( 'ervaring' ) ),
		'salaris_max'     => absint( $str( 'salaris_max' ) ),
		'actief'          => '1' === $str( 'actief' ),
	);
}

/**
 * Bouwt de WP_Query voor de zoekpagina. Filter 'deedni_search_args' om er iets aan toe te voegen.
 */
function deedni_search_query( $filters, $paged = 1 ) {
	$meta = array(
		'relation' => 'AND',
		array( 'key' => 'profiel_zichtbaar', 'value' => '1' ),
		array( 'key' => 'functietitel', 'value' => '', 'compare' => '!=' ),
	);
	if ( $filters['werkvorm'] ) {
		$meta[] = array( 'key' => 'werkvorm', 'value' => $filters['werkvorm'] );
	}
	if ( $filters['beschikbaarheid'] ) {
		$meta[] = array( 'key' => 'beschikbaarheid', 'value' => $filters['beschikbaarheid'] );
	}
	if ( $filters['actief'] ) {
		$meta[] = array( 'key' => 'zoekstatus', 'value' => 'actief' );
	}
	if ( $filters['ervaring'] ) {
		$meta[] = array( 'key' => 'ervaring_jaren', 'value' => $filters['ervaring'], 'compare' => '>=', 'type' => 'NUMERIC' );
	}
	if ( $filters['salaris_max'] ) {
		// Kandidaten zonder salariswens tellen ook mee.
		$meta[] = array(
			'relation' => 'OR',
			array( 'key' => 'salaris_min', 'value' => $filters['salaris_max'], 'compare' => '<=', 'type' => 'NUMERIC' ),
			array( 'key' => 'salaris_min', 'value' => '' ),
			array( 'key' => 'salaris_min', 'compare' => 'NOT EXISTS' ),
		);
	}

	$tax = array( 'relation' => 'AND' );
	if ( $filters['specialisme'] ) {
		$tax[] = array( 'taxonomy' => 'specialisme', 'field' => 'slug', 'terms' => $filters['specialisme'] );
	}
	if ( $filters['provincie'] ) {
		$tax[] = array( 'taxonomy' => 'provincie', 'field' => 'slug', 'terms' => $filters['provincie'] );
	}
	if ( $filters['vaardigheid'] ) {
		$tax[] = array( 'taxonomy' => 'vaardigheid', 'field' => 'slug', 'terms' => $filters['vaardigheid'], 'operator' => 'AND' );
	}

	$args = array(
		'post_type'      => 'kandidaat',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'paged'          => max( 1, (int) $paged ),
		'orderby'        => 'modified',
		'order'          => 'DESC',
		'meta_query'     => $meta,
		'tax_query'      => $tax,
		'post__not_in'   => deedni_blocked_kandidaat_ids(),
	);
	if ( $filters['q'] ) {
		$args['s'] = $filters['q'];
	}

	return new WP_Query( apply_filters( 'deedni_search_args', $args, $filters ) );
}

/** Alle termen van een taxonomie die in gebruik zijn of standaard bestaan, voor de filterlijsten. */
function deedni_filter_terms( $taxonomy ) {
	$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'orderby' => 'name' ) );
	return is_wp_error( $terms ) ? array() : $terms;
}
