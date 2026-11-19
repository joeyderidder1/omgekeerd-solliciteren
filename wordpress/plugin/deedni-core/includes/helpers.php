<?php
/**
 * Kleine hulpfuncties die door de rest van de plugin en door het thema worden gebruikt.
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Pagina's ---------- */

function deedni_page_id( $key ) {
	$pages = get_option( 'deedni_pages', array() );
	return isset( $pages[ $key ] ) ? (int) $pages[ $key ] : 0;
}

function deedni_page_url( $key, $args = array() ) {
	$id  = deedni_page_id( $key );
	$url = $id ? get_permalink( $id ) : home_url( '/' );
	return $args ? add_query_arg( $args, $url ) : $url;
}

function deedni_is_page( $key ) {
	$id = deedni_page_id( $key );
	return $id && is_page( $id );
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
function deedni_profile_id( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( ! $user_id ) {
		return 0;
	}
	$id   = (int) get_user_meta( $user_id, 'deedni_profiel_id', true );
	$type = deedni_is_werkgever( $user_id ) ? 'bedrijf' : 'kandidaat';
	if ( $id && get_post_type( $id ) === $type && (int) get_post_field( 'post_author', $id ) === $user_id ) {
		return $id;
	}
	return 0;
}

/** Anonieme code die bedrijven te zien krijgen, bijvoorbeeld K-2041. */
function deedni_code( $kandidaat_id ) {
	return 'K-' . ( 1000 + (int) $kandidaat_id );
}

function deedni_field( $post_id, $name ) {
	return get_post_meta( $post_id, $name, true );
}

/**
 * Slaat een veld op via ACF (met veldsleutel, zodat het in de beheeromgeving goed toont) of als gewone meta.
 * $group is 'k' (kandidaat), 'b' (bedrijf) of 'a' (aanbod).
 */
function deedni_set_field( $post_id, $name, $value, $group ) {
	if ( function_exists( 'update_field' ) ) {
		update_field( 'field_dn_' . $group . '_' . $name, $value, $post_id );
	} else {
		update_post_meta( $post_id, $name, $value );
	}
}

function deedni_term_names( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );
	return ( $terms && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'name' ) : array();
}

/** Keuzelijsten die in velden, filters en weergave terugkomen. */
function deedni_choices( $list ) {
	$choices = array(
		'werkvorm'        => array(
			'op_locatie' => 'Op locatie',
			'hybride'    => 'Hybride',
			'remote'     => 'Remote',
		),
		'uren'            => array(
			'16-24' => '16–24 uur',
			'24-32' => '24–32 uur',
			'32-40' => '32–40 uur',
		),
		'beschikbaarheid' => array(
			'direct'      => 'Direct',
			'1_maand'     => 'Binnen 1 maand',
			'2_3_maanden' => 'Binnen 2 à 3 maanden',
			'in_overleg'  => 'In overleg',
		),
		'zoekstatus'      => array(
			'actief' => 'Actief op zoek',
			'open'   => 'Staat open voor een aanbod',
		),
		'aanbod_status'   => array(
			'verstuurd'    => 'Wacht op reactie',
			'geaccepteerd' => 'Geaccepteerd',
			'afgewezen'    => 'Afgewezen',
			'aangenomen'   => 'Aangenomen',
		),
	);
	return isset( $choices[ $list ] ) ? $choices[ $list ] : array();
}

function deedni_label( $list, $value ) {
	$choices = deedni_choices( $list );
	return isset( $choices[ $value ] ) ? $choices[ $value ] : '';
}

/** Bedragen altijd in Nederlandse notatie: 4.200 */
function deedni_number( $amount ) {
	return number_format( (float) $amount, 0, ',', '.' );
}

function deedni_euro( $amount ) {
	return '€' . deedni_number( $amount );
}

/** Een profiel is compleet als de velden zijn ingevuld die bedrijven nodig hebben om te kiezen. */
function deedni_profile_complete( $kandidaat_id ) {
	return deedni_field( $kandidaat_id, 'functietitel' )
		&& deedni_term_names( $kandidaat_id, 'specialisme' )
		&& deedni_term_names( $kandidaat_id, 'provincie' );
}

/* ---------- Aanbiedingen en toegang ---------- */

/** Aanbiedingen van een werkgever aan een kandidaat, eventueel alleen met bepaalde statussen. */
function deedni_offers_between( $kandidaat_id, $werkgever_id, $statuses = array() ) {
	$meta = array(
		array( 'key' => 'kandidaat', 'value' => (int) $kandidaat_id ),
	);
	if ( $statuses ) {
		$meta[] = array( 'key' => 'status', 'value' => (array) $statuses, 'compare' => 'IN' );
	}
	return get_posts(
		array(
			'post_type'      => 'aanbod',
			'post_status'    => 'publish',
			'author'         => (int) $werkgever_id,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => $meta,
		)
	);
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

function deedni_email_domain( $email ) {
	$at = strrpos( (string) $email, '@' );
	return false === $at ? '' : strtolower( substr( $email, $at + 1 ) );
}

/** Kandidaten die het e-maildomein van deze werkgever op hun blokkeerlijst hebben gezet. */
function deedni_blocked_kandidaat_ids( $user_id = 0 ) {
	$user = deedni_user( $user_id ? $user_id : null );
	if ( ! $user || ! $user->exists() || deedni_is_beheerder( $user ) ) {
		return array();
	}
	$domain = deedni_email_domain( $user->user_email );
	if ( ! $domain ) {
		return array();
	}
	return get_posts(
		array(
			'post_type'      => 'kandidaat',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array( array( 'key' => '_dn_blocked_domain', 'value' => $domain ) ),
		)
	);
}

/* ---------- Weergave ---------- */

/**
 * Laadt een template uit de plugin. Een thema kan het overschrijven met een bestand in /deedni/ in het thema.
 */
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

function deedni_check_icon() {
	return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"></path></svg>';
}
