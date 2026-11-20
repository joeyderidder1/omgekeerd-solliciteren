<?php
/**
 * Aanbiedingen: uitlezen, statussen en wie wat mag zien.
 *
 * Een aanbod (post type 'aanbod') heeft als auteur de werkgever en deze velden:
 *   kandidaat, bedrijf, status, functie, salaris_min, salaris_max, uren, contract, werkplek, standplaats, bericht
 *   tegenvoorstel van de kandidaat: tegen_voorwaarden (lijst), tegen_min, tegen_max, tegen_wens, tegen_bericht
 *   ronde (1 = eerste aanbod), akkoord (1 = werkgever ging akkoord met het tegenvoorstel), historie (lijst), reactie_op
 *
 * Statussen: verstuurd -> geaccepteerd | afgewezen | tegenvoorstel
 *            tegenvoorstel -> verstuurd (aangepast of akkoord) | gestopt
 *            geaccepteerd -> aangenomen
 */

defined( 'ABSPATH' ) || exit;

function deedni_aanbod( $id ) {
	$id = (int) $id;
	$f  = function ( $k ) use ( $id ) {
		return get_post_meta( $id, $k, true );
	};
	$voorwaarden = $f( 'tegen_voorwaarden' );
	return array(
		'id'            => $id,
		'werkgever_id'  => (int) get_post_field( 'post_author', $id ),
		'kandidaat_id'  => (int) $f( 'kandidaat' ),
		'bedrijf_id'    => (int) $f( 'bedrijf' ),
		'status'        => $f( 'status' ) ? (string) $f( 'status' ) : 'verstuurd',
		'functie'       => (string) $f( 'functie' ),
		'salaris_min'   => (int) $f( 'salaris_min' ),
		'salaris_max'   => (int) $f( 'salaris_max' ),
		'uren'          => deedni_uren( $f( 'uren' ) ),
		'contract'      => (string) $f( 'contract' ),
		'werkplek'      => (string) $f( 'werkplek' ),
		'standplaats'   => (string) $f( 'standplaats' ),
		'bericht'       => (string) $f( 'bericht' ),
		'tegen'         => array(
			'voorwaarden' => is_array( $voorwaarden ) ? $voorwaarden : array(),
			'min'         => (int) $f( 'tegen_min' ),
			'max'         => (int) $f( 'tegen_max' ),
			'wens'        => (string) $f( 'tegen_wens' ),
			'anders'      => (string) $f( 'tegen_anders' ),
			'bericht'     => (string) $f( 'tegen_bericht' ),
		),
		'ronde'         => max( 1, (int) $f( 'ronde' ) ),
		'akkoord'       => (bool) $f( 'akkoord' ),
		'datum'         => get_post_time( 'U', true, $id ),
		'gewijzigd'     => get_post_modified_time( 'U', true, $id ),
		'reactie_op'    => (string) $f( 'reactie_op' ),
	);
}

/** "32 tot 40 uur, hybride, Utrecht" */
function deedni_aanbod_meta( $a ) {
	$parts = array();
	if ( $a['uren'] ) {
		$parts[] = $a['uren'] . ' uur';
	}
	if ( $a['werkplek'] ) {
		$parts[] = strtolower( deedni_label( 'werkvorm', $a['werkplek'] ) );
	}
	if ( $a['standplaats'] ) {
		$parts[] = $a['standplaats'];
	}
	if ( $a['contract'] ) {
		$parts[] = strtolower( deedni_label( 'contract', $a['contract'] ) );
	}
	$text = implode( ', ', $parts );
	return $text ? mb_strtoupper( mb_substr( $text, 0, 1 ) ) . mb_substr( $text, 1 ) : '';
}

/** Aanbiedingen van een werkgever aan een kandidaat, eventueel alleen met bepaalde statussen. */
function deedni_offers_between( $kandidaat_id, $werkgever_id, $statuses = array() ) {
	$meta = array(
		array( 'key' => 'kandidaat', 'value' => (int) $kandidaat_id ),
	);
	if ( $statuses ) {
		$meta[] = array( 'key' => 'status', 'value' => (array) $statuses, 'compare' => 'IN' );
	}
	return array_map(
		'intval',
		get_posts(
			array(
				'post_type'      => 'aanbod',
				'post_status'    => 'publish',
				'author'         => (int) $werkgever_id,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => $meta,
			)
		)
	);
}

/** Alle aanbiedingen voor een kandidaat of van een werkgever. */
function deedni_offers( $args ) {
	$q = array(
		'post_type'      => 'aanbod',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'orderby'        => 'modified',
		'order'          => 'DESC',
		'meta_query'     => array(),
	);
	if ( ! empty( $args['kandidaat'] ) ) {
		$q['meta_query'][] = array( 'key' => 'kandidaat', 'value' => (int) $args['kandidaat'] );
	}
	if ( ! empty( $args['werkgever'] ) ) {
		$q['author'] = (int) $args['werkgever'];
	}
	if ( ! empty( $args['status'] ) ) {
		$q['meta_query'][] = array( 'key' => 'status', 'value' => (array) $args['status'], 'compare' => 'IN' );
	}
	return array_map( 'deedni_aanbod', get_posts( $q ) );
}

/** Aantal nieuwe aanbiedingen (wacht op jouw antwoord) voor de ingelogde marketeer. */
function deedni_aantal_nieuw( $user_id = 0 ) {
	$k = deedni_profile_id( $user_id ? $user_id : get_current_user_id(), 'kandidaat' );
	return $k ? count( deedni_offers( array( 'kandidaat' => $k, 'status' => 'verstuurd' ) ) ) : 0;
}

/** Aantal tegenvoorstellen dat op de werkgever wacht. */
function deedni_aantal_tegenvoorstellen( $user_id = 0 ) {
	return count( deedni_offers( array( 'werkgever' => $user_id ? $user_id : get_current_user_id(), 'status' => 'tegenvoorstel' ) ) );
}

/** Ziet deze gebruiker de privégegevens (naam, contact, cv) van de kandidaat? */
function deedni_has_contact_access( $kandidaat_id, $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( ! $user_id ) {
		return false;
	}
	if ( deedni_is_beheerder( $user_id ) || (int) get_post_field( 'post_author', $kandidaat_id ) === $user_id ) {
		return true;
	}
	return (bool) deedni_offers_between( $kandidaat_id, $user_id, array( 'geaccepteerd', 'aangenomen' ) );
}

/** Een regel toevoegen aan de geschiedenis van een aanbod. */
function deedni_aanbod_log( $aanbod_id, $tekst ) {
	$log   = deedni_list( $aanbod_id, 'historie' );
	$log[] = array( 't' => time(), 'tekst' => $tekst );
	update_post_meta( $aanbod_id, 'historie', $log );
}

function deedni_status_tag( $status, $akkoord = false ) {
	$class = array(
		'verstuurd'     => '',
		'tegenvoorstel' => 'tag--accent',
		'geaccepteerd'  => 'tag--ok',
		'aangenomen'    => 'tag--ok',
		'afgewezen'     => '',
		'gestopt'       => '',
	);
	$label = deedni_label( 'aanbod_status', $status );
	if ( 'verstuurd' === $status && $akkoord ) {
		$label = 'Akkoord, wacht op ja';
	}
	return '<span class="tag ' . esc_attr( isset( $class[ $status ] ) ? $class[ $status ] : '' ) . '">' . esc_html( $label ) . '</span>';
}

/* ---------- Bewaard en Niet nu (werkgever) ---------- */

function deedni_bewaard( $user_id = 0 ) {
	$v = get_user_meta( $user_id ? $user_id : get_current_user_id(), 'deedni_bewaard', true );
	return is_array( $v ) ? array_map( 'intval', $v ) : array();
}

function deedni_niet_nu( $user_id = 0 ) {
	$v = get_user_meta( $user_id ? $user_id : get_current_user_id(), 'deedni_niet_nu', true );
	return is_array( $v ) ? array_map( 'intval', $v ) : array();
}

/** Hoeveel bedrijven hebben deze kandidaat bewaard? */
function deedni_aantal_bewaard_door( $kandidaat_id ) {
	return (int) get_post_meta( $kandidaat_id, '_dn_bewaard_aantal', true );
}

function deedni_zet_bewaard( $kandidaat_id, $aan, $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();
	$lijst   = deedni_bewaard( $user_id );
	$was     = in_array( (int) $kandidaat_id, $lijst, true );
	if ( $aan && ! $was ) {
		$lijst[] = (int) $kandidaat_id;
		update_post_meta( $kandidaat_id, '_dn_bewaard_aantal', deedni_aantal_bewaard_door( $kandidaat_id ) + 1 );
	} elseif ( ! $aan && $was ) {
		$lijst = array_values( array_diff( $lijst, array( (int) $kandidaat_id ) ) );
		update_post_meta( $kandidaat_id, '_dn_bewaard_aantal', max( 0, deedni_aantal_bewaard_door( $kandidaat_id ) - 1 ) );
	}
	update_user_meta( $user_id, 'deedni_bewaard', $lijst );
	if ( $aan ) {
		update_user_meta( $user_id, 'deedni_niet_nu', array_values( array_diff( deedni_niet_nu( $user_id ), array( (int) $kandidaat_id ) ) ) );
	}
}

/* ---------- Gelijktijdige verzoeken (race conditions) ---------- */

/**
 * Status alleen wijzigen als die nog $van is, in één databasebewerking. Twee verzoeken tegelijk
 * (dubbelklik, twee tabbladen, ja en nee tegelijk) kunnen zo nooit allebei slagen.
 * Geeft true als dit verzoek de wijziging deed.
 */
function deedni_aanbod_wissel( $aanbod_id, $van, $naar ) {
	global $wpdb;
	$n = $wpdb->query( // phpcs:ignore WordPress.DB
		$wpdb->prepare(
			"UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE post_id = %d AND meta_key = 'status' AND meta_value = %s",
			$naar,
			(int) $aanbod_id,
			$van
		)
	);
	wp_cache_delete( (int) $aanbod_id, 'post_meta' );
	return 1 === (int) $n;
}

/**
 * Een kort slot (MySQL GET_LOCK) rond "controleren en dan aanmaken". Wacht maximaal 5 seconden.
 * Geeft de naam van het slot terug, of false als het niet lukte. Het slot gaat vanzelf open aan
 * het eind van het verzoek.
 */
function deedni_slot( $naam ) {
	global $wpdb;
	$slot = 'deedni_' . md5( $wpdb->prefix . $naam );
	$ok   = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', $slot ) ); // phpcs:ignore WordPress.DB
	// 1 = slot gekregen, 0 = een ander verzoek houdt het slot vast. Iets anders (NULL, of een database
	// zonder GET_LOCK zoals SQLite) betekent: zonder slot verder, zoals voorheen.
	return '0' === (string) $ok ? false : $slot;
}

function deedni_slot_vrij( $slot ) {
	global $wpdb;
	if ( $slot ) {
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $slot ) ); // phpcs:ignore WordPress.DB
	}
}
