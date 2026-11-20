<?php
/**
 * Kleine hulpfuncties die door de rest van de plugin en door het thema worden gebruikt.
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Pagina's ---------- */

/** De pagina's die de plugin gebruikt. Sleutel => array( titel, adres, blok ). */
function deedni_pagina_lijst() {
	return array(
		'inloggen'    => array( 'Inloggen', 'inloggen', 'deedni/inloggen' ),
		'registreren' => array( 'Account maken', 'aanmelden', 'deedni/inloggen' ),
		'dashboard'   => array( 'Overzicht', 'overzicht', 'deedni/overzicht' ),
		'profiel'     => array( 'Mijn profiel', 'mijn-profiel', 'deedni/mijn-profiel' ),
		'zoeken'      => array( 'Kandidaten zoeken', 'kandidaten', 'deedni/zoeken' ),
	);
}

function deedni_page_id( $key ) {
	$pages = get_option( 'deedni_pages', array() );
	return isset( $pages[ $key ] ) ? (int) $pages[ $key ] : 0;
}

function deedni_page_url( $key, $args = array() ) {
	$id  = deedni_page_id( $key );
	$url = $id && get_post_status( $id ) ? get_permalink( $id ) : home_url( '/' );
	if ( 'registreren' === $key && ! $id ) {
		$url = deedni_page_url( 'inloggen' ) . '#aanmelden';
	}
	return $args ? add_query_arg( $args, $url ) : $url;
}

function deedni_is_page( $key ) {
	$id = deedni_page_id( $key );
	return $id && is_page( $id );
}

/**
 * Een link uit een blokveld omzetten naar een echt adres.
 * "pagina:inloggen" wordt het adres van die pagina, "#vragen" blijft een anker, de rest blijft zoals het is.
 */
function deedni_link( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	if ( 0 === strpos( $value, 'pagina:' ) ) {
		$parts = explode( '#', substr( $value, 7 ), 2 );
		$key   = $parts[0];
		$url   = 'home' === $key ? home_url( '/' ) : deedni_page_url( $key );
		return isset( $parts[1] ) ? strtok( $url, '#' ) . '#' . $parts[1] : $url;
	}
	if ( 0 === strpos( $value, '#' ) ) {
		return is_front_page() ? $value : home_url( '/' ) . $value;
	}
	return $value;
}

/* ---------- Rollen ---------- */

function deedni_user( $user = null ) {
	if ( null === $user ) {
		return wp_get_current_user();
	}
	return $user instanceof WP_User ? $user : get_userdata( (int) $user );
}

function deedni_is_kandidaat( $user = null ) {
	$user = deedni_user( $user );
	return $user && in_array( 'deedni_kandidaat', (array) $user->roles, true );
}

function deedni_is_werkgever( $user = null ) {
	$user = deedni_user( $user );
	return $user && in_array( 'deedni_werkgever', (array) $user->roles, true );
}

/** Beheerders en redacteuren mogen alles inzien. */
function deedni_is_beheerder( $user = null ) {
	$user = deedni_user( $user );
	return $user && $user->exists() && user_can( $user, 'edit_others_posts' );
}

/**
 * De rol waarvoor we de site tonen: 'bezoeker', 'kandidaat' of 'werkgever'.
 * Een beheerder kan met ?bekijk_als=kandidaat of ?bekijk_als=werkgever een rol bekijken.
 */
function deedni_rol() {
	$override = function_exists( 'deedni_rol_override' ) ? deedni_rol_override() : '';
	if ( $override ) {
		return $override;
	}
	if ( ! is_user_logged_in() ) {
		return 'bezoeker';
	}
	if ( deedni_is_beheerder() && isset( $_GET['bekijk_als'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$als = sanitize_key( wp_unslash( $_GET['bekijk_als'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( in_array( $als, array( 'bezoeker', 'kandidaat', 'werkgever' ), true ) ) {
			return $als;
		}
	}
	if ( deedni_is_kandidaat() ) {
		return 'kandidaat';
	}
	if ( deedni_is_werkgever() || deedni_is_beheerder() ) {
		return 'werkgever';
	}
	return 'bezoeker';
}

/** Een werkgever kan pas zoeken nadat de beheerder het account heeft goedgekeurd (als die instelling aan staat). */
function deedni_werkgever_goedgekeurd( $user = null ) {
	$user = deedni_user( $user );
	if ( ! $user || ! $user->exists() ) {
		return false;
	}
	if ( deedni_is_beheerder( $user ) ) {
		return true;
	}
	if ( ! deedni_is_werkgever( $user ) ) {
		return false;
	}
	if ( ! get_option( 'deedni_werkgever_goedkeuring', 1 ) ) {
		return true;
	}
	return (bool) get_user_meta( $user->ID, 'deedni_goedgekeurd', true );
}

/** Mag deze gebruiker kandidaten zoeken en aanbiedingen sturen? */
function deedni_can_search( $user = null ) {
	return deedni_is_beheerder( $user ) || deedni_werkgever_goedgekeurd( $user );
}

/* ---------- Profielen ---------- */

/** Het kandidaat- of bedrijfsprofiel (post-ID) dat bij een gebruiker hoort. */
function deedni_profile_id( $user_id = 0, $type = '' ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( ! $user_id ) {
		return 0;
	}
	if ( ! $type ) {
		$type = deedni_is_werkgever( $user_id ) || ( deedni_is_beheerder( $user_id ) && ! deedni_is_kandidaat( $user_id ) ) ? 'bedrijf' : 'kandidaat';
	}
	$id = (int) get_user_meta( $user_id, 'deedni_profiel_id', true );
	if ( $id && get_post_type( $id ) === $type && (int) get_post_field( 'post_author', $id ) === $user_id ) {
		return $id;
	}
	// Terugvallen op het eerste profiel van deze gebruiker.
	$found = get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => array( 'publish', 'private', 'draft' ),
			'author'         => $user_id,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	return $found ? (int) $found[0] : 0;
}

/** Maakt het profiel aan als het nog niet bestaat. */
function deedni_ensure_profile( $user_id, $type ) {
	$id = deedni_profile_id( $user_id, $type );
	if ( $id ) {
		return $id;
	}
	$user = get_userdata( $user_id );
	$id   = wp_insert_post(
		array(
			'post_type'   => $type,
			'post_status' => 'publish',
			'post_title'  => 'bedrijf' === $type ? ( $user ? $user->display_name : 'Bedrijf' ) : 'Nieuw profiel',
			'post_author' => $user_id,
		)
	);
	if ( 'kandidaat' === $type ) {
		wp_update_post( array( 'ID' => $id, 'post_title' => deedni_code( $id ), 'post_name' => strtolower( deedni_code( $id ) ) ) );
	}
	update_user_meta( $user_id, 'deedni_profiel_id', $id );
	return $id;
}

/** Anonieme code die bedrijven te zien krijgen, bijvoorbeeld K-2041. */
function deedni_code( $kandidaat_id ) {
	return 'K-' . ( 1000 + (int) $kandidaat_id );
}

function deedni_field( $post_id, $name ) {
	return get_post_meta( $post_id, $name, true );
}

function deedni_set_field( $post_id, $name, $value ) {
	if ( is_array( $value ) && ! $value ) {
		delete_post_meta( $post_id, $name );
		return;
	}
	update_post_meta( $post_id, $name, $value );
}

/** Een veld dat een lijst is (werkervaring, projecten): altijd een array. */
function deedni_list( $post_id, $name ) {
	$value = get_post_meta( $post_id, $name, true );
	return is_array( $value ) ? array_values( $value ) : array();
}

function deedni_term_names( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	return ( $terms && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'name' ) : array();
}

function deedni_term_ids( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	return ( $terms && ! is_wp_error( $terms ) ) ? array_map( 'intval', wp_list_pluck( $terms, 'term_id' ) ) : array();
}

/** Keuzelijsten die in formulieren, filters en weergave terugkomen. */
function deedni_choices( $list ) {
	$choices = array(
		'werkvorm'        => array(
			'op_locatie' => 'Op kantoor',
			'hybride'    => 'Hybride',
			'remote'     => 'Remote',
		),
		'beschikbaarheid' => array(
			'direct'      => 'Direct',
			'1_maand'     => 'Binnen 1 maand',
			'2_3_maanden' => 'Binnen 3 maanden',
			'in_overleg'  => 'Alleen voor een heel goed aanbod',
		),
		'zoekstatus'      => array(
			'actief' => 'Actief op zoek',
			'open'   => 'Open voor aanbod',
		),
		'contract'        => array(
			'vast'      => 'Vast, na proeftijd',
			'jaar'      => 'Jaarcontract',
			'freelance' => 'Freelance',
		),
		'voorwaarden'     => array(
			'salaris'    => 'Salaris te laag',
			'auto'       => 'Auto van de zaak',
			'vakantie'   => 'Meer vakantiedagen',
			'thuiswerken' => 'Thuiswerken',
			'opleiding'  => 'Opleidingsbudget',
			'uren'       => 'Andere uren',
			'pensioen'   => 'Pensioen of bonus',
			'anders'     => 'Iets anders',
		),
		'aanbod_status'   => array(
			'verstuurd'     => 'Wacht op antwoord',
			'tegenvoorstel' => 'Tegenvoorstel',
			'geaccepteerd'  => 'Ja, naam zichtbaar',
			'afgewezen'     => 'Afgewezen',
			'gestopt'       => 'Gestopt',
			'aangenomen'    => 'Aangenomen',
		),
	);
	return isset( $choices[ $list ] ) ? $choices[ $list ] : array();
}

function deedni_label( $list, $value ) {
	$choices = deedni_choices( $list );
	return isset( $choices[ $value ] ) ? $choices[ $value ] : '';
}

/** Oude urenwaarden ("32-40") netjes tonen, nieuwe zijn vrije tekst ("32 tot 40"). */
function deedni_uren( $value ) {
	$value = trim( (string) $value );
	if ( preg_match( '/^(\d+)-(\d+)$/', $value, $m ) ) {
		return $m[1] . ' tot ' . $m[2];
	}
	return $value;
}

/** Bedragen altijd in Nederlandse notatie: 4.200 */
function deedni_number( $amount ) {
	return number_format( (float) $amount, 0, ',', '.' );
}

function deedni_euro( $amount ) {
	return '€' . deedni_number( $amount );
}

/** €4.200–4.800 */
function deedni_range( $min, $max ) {
	$min = (int) $min;
	$max = (int) $max;
	if ( $min && $max && $max !== $min ) {
		return deedni_euro( $min ) . '–' . deedni_number( $max );
	}
	if ( $min ) {
		return deedni_euro( $min );
	}
	return $max ? deedni_euro( $max ) : '';
}

/** Getal uit invoer zoals "4.200" of "€ 4200". */
function deedni_parse_int( $value ) {
	return absint( preg_replace( '/[^\d]/', '', (string) $value ) );
}

/** Afgerond salaris dat bedrijven zien (op 100 euro naar beneden). */
function deedni_salaris_afgerond( $amount ) {
	return (int) ( floor( (int) $amount / 100 ) * 100 );
}

/** "2 dagen geleden" */
function deedni_geleden( $time ) {
	$time = is_numeric( $time ) ? (int) $time : strtotime( (string) $time );
	if ( ! $time ) {
		return '';
	}
	$diff = time() - $time;
	if ( $diff < 3600 ) {
		return 'net binnen';
	}
	if ( $diff < DAY_IN_SECONDS ) {
		return 'vandaag';
	}
	$days = (int) floor( $diff / DAY_IN_SECONDS );
	if ( 1 === $days ) {
		return 'gisteren';
	}
	if ( $days < 30 ) {
		return $days . ' dagen geleden';
	}
	return date_i18n( 'j F Y', $time );
}

/** Initialen voor het rondje in het menu: "Studio Noord" wordt "SN". */
function deedni_initialen( $name ) {
	$words = preg_split( '/\s+/', trim( (string) $name ) );
	$out   = '';
	foreach ( $words as $w ) {
		if ( '' !== $w && preg_match( '/^\p{L}/u', $w ) ) {
			$out .= mb_strtoupper( mb_substr( $w, 0, 1 ) );
		}
		if ( mb_strlen( $out ) >= 2 ) {
			break;
		}
	}
	return $out ? $out : '?';
}

/* ---------- Weergave ---------- */

/** Tekst met *accent* en **vet**: veilig omzetten naar HTML. */
function deedni_tekst( $text ) {
	$html = esc_html( (string) $text );
	$html = preg_replace( '/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $html );
	$html = preg_replace( '/\*(.+?)\*/u', '<span class="soft">$1</span>', $html );
	return $html;
}

/** Tekst met alinea's (lege regel = nieuwe alinea). */
function deedni_alineas( $text, $class = '' ) {
	$parts = preg_split( '/\n\s*\n/', trim( (string) $text ) );
	$out   = '';
	foreach ( $parts as $p ) {
		if ( '' !== trim( $p ) ) {
			$out .= '<p' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . '>' . nl2br( deedni_tekst( trim( $p ) ) ) . '</p>';
		}
	}
	return $out;
}

/** Laadt een template uit de plugin. Een thema kan het overschrijven met een bestand in /deedni/ in het thema. */
function deedni_template( $name, $vars = array() ) {
	$file = locate_template( 'deedni/' . $name . '.php' );
	if ( ! $file ) {
		$file = DEEDNI_DIR . 'templates/' . $name . '.php';
	}
	ob_start();
	extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
	include $file;
	return ob_get_clean();
}

function deedni_notice( $text, $type = 'info' ) {
	return '<div class="dn-notice dn-notice--' . esc_attr( $type ) . '" role="' . ( 'error' === $type ? 'alert' : 'status' ) . '">' . wp_kses_post( $text ) . '</div>';
}

/** Meldingen die een formulier in deze request heeft opgeleverd. */
function deedni_flash( $key = null, $value = null ) {
	static $flash = array();
	if ( null !== $value ) {
		$flash[ $key ] = $value;
	}
	if ( null === $key ) {
		return $flash;
	}
	return isset( $flash[ $key ] ) ? $flash[ $key ] : null;
}

function deedni_icon( $name, $size = 22 ) {
	$paths = array(
		'check'  => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
		'user'   => '<circle cx="12" cy="8.5" r="3.6"/><path d="M5 20c.8-3.6 3.6-5.6 7-5.6s6.2 2 7 5.6"/>',
		'search' => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>',
		'plus'   => '<path d="M12 5v14M5 12h14"/>',
		'arrow'  => '<path d="M10 6l6 6-6 6"/>',
		'mail'   => '<rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="M4 7l8 6 8-6"/>',
		'chev'   => '<path d="M6 9l6 6 6-6"/>',
	);
	$path = isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['arrow'];
	return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

/** De gooey CTA-knop uit het ontwerp. */
function deedni_cta( $text, $url, $icon = 'user', $variant = 'accent', $extra = '' ) {
	if ( '' === trim( (string) $text ) ) {
		return '';
	}
	$class = 'cta-pill' . ( $variant ? ' cta-pill--' . $variant : '' );
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '"' . $extra . '><span class="cta-ico" aria-hidden="true">' . deedni_icon( $icon, 26 ) . '</span><span class="cta-txt">' . esc_html( $text ) . '</span></a>';
}

/** Klein pill-label boven een kop (zoals 'Over deedni'). */
function deedni_eyebrow( $tekst, $extra = '' ) {
	if ( '' === trim( (string) $tekst ) ) {
		return '';
	}
	return '<p class="eyebrow' . ( $extra ? ' ' . esc_attr( $extra ) : '' ) . '"><span class="eyebrow-dot" aria-hidden="true"></span>' . esc_html( $tekst ) . '</p>';
}

/** Losse labels met een vinkje uit een tekst met punten ertussen: 'Gratis · anoniem'. */
function deedni_chips_html( $tekst ) {
	$h = '';
	foreach ( preg_split( '/\s*[·|]\s*/u', (string) $tekst ) as $deel ) {
		if ( '' !== trim( $deel ) ) {
			$h .= '<li>' . deedni_icon( 'check', 16 ) . '<span>' . esc_html( trim( $deel ) ) . '</span></li>';
		}
	}
	return $h;
}

function deedni_redact( $size = '', $label = 'verborgen' ) {
	return '<span class="redact' . ( $size ? ' redact--' . esc_attr( $size ) : '' ) . '" role="img" aria-label="' . esc_attr( $label ) . '"></span>';
}

/** Lijst met tools als labels. */
function deedni_tools_html( $tools ) {
	if ( is_string( $tools ) ) {
		$tools = array_filter( array_map( 'trim', explode( ',', $tools ) ) );
	}
	if ( ! $tools ) {
		return '';
	}
	$out = '<div class="tools">';
	foreach ( $tools as $t ) {
		$out .= '<span class="tool">' . esc_html( $t ) . '</span>';
	}
	return $out . '</div>';
}
