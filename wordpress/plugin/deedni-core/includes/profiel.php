<?php
/**
 * Het profiel van een marketeer en het bedrijfsprofiel: uitlezen, opslaan, compleetheid en blokkeerlijst.
 *
 * Velden van een kandidaat (post meta):
 *   openbaar   functietitel, ervaring_jaren, samenvatting (over mij), regio, uren, salaris_min,
 *              beschikbaarheid, zoekstatus, werkplekken (lijst), opleiding, werkervaring (lijst), projecten (lijst)
 *   na een ja  voornaam, achternaam, telefoon, linkedin, huidige_werkgever, cv (bijlage-ID)
 *   taxonomie  specialisme, vaardigheid (tools), provincie
 *   overig     profiel_zichtbaar, blokkeerlijst (één per regel), bevestigd_op
 */

defined( 'ABSPATH' ) || exit;

/** Alles van een kandidaat in één array, met nette standaardwaarden. */
function deedni_kandidaat( $id ) {
	$id = (int) $id;
	$f  = function ( $k ) use ( $id ) {
		return (string) get_post_meta( $id, $k, true );
	};

	$werkplekken = get_post_meta( $id, 'werkplekken', true );
	if ( ! is_array( $werkplekken ) || ! $werkplekken ) {
		$oud         = $f( 'werkvorm' ); // versie 1 had één keuze
		$werkplekken = $oud ? array( $oud ) : array();
	}

	$owner = (int) get_post_field( 'post_author', $id );
	$user  = $owner ? get_userdata( $owner ) : null;

	return array(
		'id'                => $id,
		'code'              => deedni_code( $id ),
		'url'               => get_permalink( $id ),
		'user_id'           => $owner,
		'email'             => $user ? $user->user_email : '',
		'functietitel'      => $f( 'functietitel' ),
		'ervaring_jaren'    => (int) $f( 'ervaring_jaren' ),
		'samenvatting'      => $f( 'samenvatting' ),
		'regio'             => $f( 'regio' ),
		'uren'              => deedni_uren( $f( 'uren' ) ),
		'salaris_min'       => (int) $f( 'salaris_min' ),
		'beschikbaarheid'   => $f( 'beschikbaarheid' ),
		'zoekstatus'        => $f( 'zoekstatus' ) ? $f( 'zoekstatus' ) : 'open',
		'werkplekken'       => array_values( array_intersect( $werkplekken, array_keys( deedni_choices( 'werkvorm' ) ) ) ),
		'opleiding'         => $f( 'opleiding' ) ? $f( 'opleiding' ) : $f( 'certificaten' ),
		'werkervaring'      => deedni_list( $id, 'werkervaring' ),
		'projecten'         => deedni_list( $id, 'projecten' ),
		'voornaam'          => $f( 'voornaam' ),
		'achternaam'        => $f( 'achternaam' ),
		'telefoon'          => $f( 'telefoon' ),
		'linkedin'          => $f( 'linkedin' ),
		'huidige_werkgever' => $f( 'huidige_werkgever' ),
		'cv'                => (int) $f( 'cv' ),
		'blokkeerlijst'     => $f( 'blokkeerlijst' ),
		'zichtbaar'         => (bool) $f( 'profiel_zichtbaar' ),
		'specialisme'       => deedni_term_names( $id, 'specialisme' ),
		'specialisme_ids'   => deedni_term_ids( $id, 'specialisme' ),
		'tools'             => deedni_term_names( $id, 'vaardigheid' ),
		'tool_ids'          => deedni_term_ids( $id, 'vaardigheid' ),
		'provincie'         => deedni_term_names( $id, 'provincie' ),
		'provincie_ids'     => deedni_term_ids( $id, 'provincie' ),
		'bevestigd_op'      => (int) $f( 'bevestigd_op' ),
		'gewijzigd'         => get_post_modified_time( 'U', true, $id ),
	);
}

function deedni_naam( $k ) {
	return trim( $k['voornaam'] . ' ' . $k['achternaam'] );
}

/** Werkplekken als leesbare tekst: "Hybride of remote". */
function deedni_werkplek_tekst( $keys ) {
	$labels = array();
	foreach ( (array) $keys as $key ) {
		$label = deedni_label( 'werkvorm', $key );
		if ( $label ) {
			$labels[] = $label;
		}
	}
	if ( ! $labels ) {
		return '';
	}
	$text = implode( ' of ', $labels );
	return mb_strtoupper( mb_substr( $text, 0, 1 ) ) . mb_strtolower( mb_substr( $text, 1 ) );
}

/** Regio zoals bedrijven hem zien: eigen tekst of provincie. */
function deedni_regio_tekst( $k ) {
	if ( $k['regio'] ) {
		return $k['regio'];
	}
	return $k['provincie'] ? implode( ', ', $k['provincie'] ) : '';
}

/**
 * Hoe compleet is het profiel, in procenten, en wat ontbreekt er nog?
 * Geeft array( 'procent' => 85, 'todo' => array( array( tekst, stap ) ) ).
 */
function deedni_compleet( $k ) {
	$checks = array(
		array( (bool) $k['functietitel'], 'Vul je functietitel in', 'persoonlijk' ),
		array( (bool) $k['specialisme'], 'Kies je specialisme', 'persoonlijk' ),
		array( strlen( $k['samenvatting'] ) > 30, 'Schrijf twee of drie zinnen over jezelf', 'persoonlijk' ),
		array( (bool) $k['voornaam'], 'Vul je naam in (pas zichtbaar na jouw ja)', 'persoonlijk' ),
		array( (bool) $k['werkervaring'], 'Voeg je werkervaring toe', 'werkervaring' ),
		array( (bool) $k['projecten'], 'Voeg een project met resultaat toe', 'projecten' ),
		array( (bool) $k['tools'], 'Kies de tools waarmee je werkt', 'projecten' ),
		array( (bool) $k['salaris_min'], 'Vul je salariswens in', 'wat-zoek-je' ),
		array( (bool) ( $k['provincie'] || $k['regio'] ), 'Kies je regio', 'wat-zoek-je' ),
		array( (bool) $k['werkplekken'], 'Kies waar je wilt werken', 'wat-zoek-je' ),
		array( '' !== trim( $k['blokkeerlijst'] ), 'Zet je huidige werkgever op de blokkeerlijst', 'blokkeerlijst' ),
	);
	$done = 0;
	$todo = array();
	foreach ( $checks as $c ) {
		if ( $c[0] ) {
			++$done;
		} else {
			$todo[] = array( $c[1], $c[2] );
		}
	}
	return array(
		'procent' => (int) round( $done / count( $checks ) * 100 ),
		'todo'    => $todo,
	);
}

/** Een profiel kan online als bedrijven er genoeg aan hebben om te kiezen. */
function deedni_profile_complete( $kandidaat_id ) {
	$k = deedni_kandidaat( $kandidaat_id );
	return $k['functietitel'] && $k['specialisme'] && ( $k['provincie'] || $k['regio'] );
}

/* ---------- Blokkeerlijst ---------- */

/** "Studio Noord Digital", "studionoord.nl" of "iemand@studionoord.nl" naar zoeksleutels. */
function deedni_blok_sleutels( $lijst ) {
	$domains = array();
	$names   = array();
	foreach ( preg_split( '/[\r\n,]+/', (string) $lijst ) as $item ) {
		$item = trim( $item );
		if ( '' === $item ) {
			continue;
		}
		if ( false !== strpos( $item, '@' ) ) {
			$domains[] = deedni_email_domain( $item );
		} elseif ( preg_match( '#^(https?://)?(www\.)?([a-z0-9-]+(\.[a-z0-9-]+)+)#i', $item, $m ) ) {
			$domains[] = strtolower( $m[3] );
		} else {
			$names[] = deedni_naam_sleutel( $item );
		}
	}
	return array(
		'domains' => array_values( array_unique( array_filter( $domains ) ) ),
		'names'   => array_values( array_unique( array_filter( $names ) ) ),
	);
}

/** Bedrijfsnaam vergelijkbaar maken: kleine letters, zonder "bv" en leestekens. */
function deedni_naam_sleutel( $name ) {
	$name = strtolower( remove_accents( (string) $name ) );
	$name = preg_replace( '/\b(b\.?v\.?|n\.?v\.?|vof|holding)\b/', '', $name );
	return preg_replace( '/[^a-z0-9]/', '', $name );
}

function deedni_email_domain( $email ) {
	$at = strrpos( (string) $email, '@' );
	return false === $at ? '' : strtolower( substr( $email, $at + 1 ) );
}

/** Slaat de blokkeerlijst op en houdt de zoeksleutels bij voor snel uitsluiten. */
function deedni_save_blokkeerlijst( $kandidaat_id, $lijst ) {
	update_post_meta( $kandidaat_id, 'blokkeerlijst', $lijst );
	delete_post_meta( $kandidaat_id, '_dn_blocked_domain' );
	delete_post_meta( $kandidaat_id, '_dn_blocked_name' );
	$keys = deedni_blok_sleutels( $lijst );
	foreach ( $keys['domains'] as $d ) {
		add_post_meta( $kandidaat_id, '_dn_blocked_domain', $d );
	}
	foreach ( $keys['names'] as $n ) {
		add_post_meta( $kandidaat_id, '_dn_blocked_name', $n );
	}
}

/** Kandidaten die deze werkgever (e-maildomein, website of bedrijfsnaam) hebben geblokkeerd. */
function deedni_blocked_kandidaat_ids( $user_id = 0 ) {
	static $cache = array();
	$user = deedni_user( $user_id ? $user_id : null );
	if ( ! $user || ! $user->exists() || deedni_is_beheerder( $user ) ) {
		return array();
	}
	if ( isset( $cache[ $user->ID ] ) ) {
		return $cache[ $user->ID ];
	}
	$domains = array( deedni_email_domain( $user->user_email ) );
	$names   = array();
	$bedrijf = deedni_profile_id( $user->ID, 'bedrijf' );
	if ( $bedrijf ) {
		$names[] = deedni_naam_sleutel( get_the_title( $bedrijf ) );
		$site    = (string) get_post_meta( $bedrijf, 'website', true );
		if ( $site && preg_match( '#^(https?://)?(www\.)?([a-z0-9-]+(\.[a-z0-9-]+)+)#i', $site, $m ) ) {
			$domains[] = strtolower( $m[3] );
		}
	}
	$meta = array( 'relation' => 'OR' );
	foreach ( array_filter( $domains ) as $d ) {
		$meta[] = array( 'key' => '_dn_blocked_domain', 'value' => $d );
	}
	foreach ( array_filter( $names ) as $n ) {
		$meta[] = array( 'key' => '_dn_blocked_name', 'value' => $n );
	}
	if ( count( $meta ) < 2 ) {
		$cache[ $user->ID ] = array();
		return array();
	}
	$cache[ $user->ID ] = array_map(
		'intval',
		get_posts(
			array(
				'post_type'      => 'kandidaat',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => $meta,
			)
		)
	);
	return $cache[ $user->ID ];
}

/* ---------- Bedrijf ---------- */

function deedni_bedrijf( $id ) {
	$id = (int) $id;
	$f  = function ( $k ) use ( $id ) {
		return get_post_meta( $id, $k, true );
	};
	$ids = function ( $k ) use ( $f ) {
		$v = $f( $k );
		return is_array( $v ) ? array_map( 'intval', $v ) : ( $v ? array( (int) $v ) : array() );
	};
	$owner = (int) get_post_field( 'post_author', $id );
	return array(
		'id'                => $id,
		'user_id'           => $owner,
		'naam'              => $id ? get_the_title( $id ) : '',
		'soort'             => (string) $f( 'soort' ),
		'plaats'            => (string) $f( 'plaats' ),
		'website'           => (string) $f( 'website' ),
		'medewerkers'       => (string) $f( 'medewerkers' ),
		'kvk_nummer'        => (string) $f( 'kvk_nummer' ),
		'omschrijving'      => (string) $f( 'omschrijving' ),
		'logo'              => (int) $f( 'logo' ),
		'contactpersoon'    => (string) $f( 'contactpersoon' ),
		'telefoon'          => (string) $f( 'telefoon' ),
		'zoek_specialisme'  => $ids( 'zoek_specialisme' ),
		'zoek_tools'        => $ids( 'zoek_tools' ),
		'zoek_provincie'    => $ids( 'zoek_provincie' ),
		'zoek_werkplek'     => is_array( $f( 'zoek_werkplek' ) ) ? $f( 'zoek_werkplek' ) : array(),
		'zoek_salaris_min'  => (int) $f( 'zoek_salaris_min' ),
		'zoek_salaris_max'  => (int) $f( 'zoek_salaris_max' ),
		'zoek_uren'         => (string) $f( 'zoek_uren' ),
		'goedgekeurd'       => $owner ? deedni_werkgever_goedgekeurd( $owner ) : false,
	);
}

/** Namen van termen bij een lijst ID's. */
function deedni_term_lijst( $ids, $taxonomy ) {
	$names = array();
	foreach ( (array) $ids as $id ) {
		$t = get_term( (int) $id, $taxonomy );
		if ( $t && ! is_wp_error( $t ) ) {
			$names[] = $t->name;
		}
	}
	return $names;
}

/** Een logo of initialen in een blokje. */
function deedni_logo_html( $bedrijf, $class = 'co-logo' ) {
	if ( $bedrijf['logo'] ) {
		$img = wp_get_attachment_image( $bedrijf['logo'], 'thumbnail', false, array( 'alt' => '', 'class' => $class . ' is-img' ) );
		if ( $img ) {
			return $img;
		}
	}
	return '<span class="' . esc_attr( $class ) . '" aria-hidden="true">' . esc_html( deedni_initialen( $bedrijf['naam'] ) ) . '</span>';
}
