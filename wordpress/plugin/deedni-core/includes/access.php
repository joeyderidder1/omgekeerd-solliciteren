<?php
/**
 * Toegang: wie ziet welk profiel, wp-admin afschermen, inlog-doorverwijzingen en het cv downloaden.
 */

defined( 'ABSPATH' ) || exit;

/** Doorsturen naar de inlogpagina en daarna terug naar waar iemand was. */
function deedni_redirect_to_login() {
	$current = home_url( add_query_arg( array() ) );
	wp_safe_redirect( deedni_page_url( 'inloggen', array( 'redirect_to' => rawurlencode( $current ) ) ) );
	exit;
}

add_action( 'template_redirect', 'deedni_guard_pages', 1 );

function deedni_guard_pages() {
	// Persoonlijke pagina's: alleen ingelogd.
	if ( ( deedni_is_page( 'dashboard' ) || deedni_is_page( 'profiel' ) ) && ! is_user_logged_in() ) {
		deedni_redirect_to_login();
	}

	// Een kandidaatprofiel zien alleen: de kandidaat zelf, beheerders en goedgekeurde werkgevers
	// die niet op de blokkeerlijst staan. Is het profiel verborgen, dan ook geen werkgevers.
	if ( is_singular( 'kandidaat' ) ) {
		$id = get_queried_object_id();
		if ( ! is_user_logged_in() ) {
			deedni_redirect_to_login();
		}
		$own       = (int) get_post_field( 'post_author', $id ) === get_current_user_id();
		$contacted = deedni_has_contact_access( $id );
		$visible   = deedni_field( $id, 'profiel_zichtbaar' ) && ! deedni_beheer_verborgen( $id ) && 'publish' === get_post_status( $id ) && ! in_array( $id, deedni_blocked_kandidaat_ids(), true );
		$allowed   = $own || deedni_is_beheerder() || ( $contacted && ! deedni_beheer_verborgen( $id ) ) || ( deedni_can_search() && $visible );
		if ( ! $allowed ) {
			wp_safe_redirect( deedni_page_url( deedni_is_werkgever() ? 'zoeken' : 'dashboard', array( 'deedni_melding' => 'geen_toegang' ) ) );
			exit;
		}
		// Een werkgever die het profiel opent: telt als bekeken (voor "Wie keek mee").
		if ( ! $own && deedni_is_werkgever() ) {
			deedni_log_bekeken( $id, get_current_user_id() );
		}
	}

	// Auteurspagina's zouden namen van kandidaten en werkgevers kunnen tonen.
	if ( is_author() ) {
		$author = get_queried_object();
		if ( $author instanceof WP_User && ( deedni_is_kandidaat( $author ) || deedni_is_werkgever( $author ) ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}
	}
}

/** Kandidaten en werkgevers horen niet in wp-admin. */
add_action(
	'admin_init',
	function () {
		if ( wp_doing_ajax() || ! is_user_logged_in() ) {
			return;
		}
		if ( ( deedni_is_kandidaat() || deedni_is_werkgever() ) && ! deedni_is_beheerder() ) {
			wp_safe_redirect( deedni_page_url( 'dashboard' ) );
			exit;
		}
	}
);

add_filter(
	'show_admin_bar',
	function ( $show ) {
		return ( deedni_is_kandidaat() || deedni_is_werkgever() ) ? false : $show;
	}
);

/** Na inloggen naar het eigen dashboard, ook via wp-login.php. */
add_filter(
	'login_redirect',
	function ( $redirect_to, $requested, $user ) {
		if ( $user instanceof WP_User && ( deedni_is_kandidaat( $user ) || deedni_is_werkgever( $user ) ) ) {
			$admin = admin_url();
			if ( ! $requested || 0 === strpos( $requested, $admin ) ) {
				return home_url( '/' );
			}
		}
		return $redirect_to;
	},
	10,
	3
);

/** Verwijzingen naar wp-login.php laten landen op de eigen inlogpagina (wachtwoord vergeten blijft werken). */
add_filter(
	'login_url',
	function ( $url, $redirect ) {
		if ( ! deedni_page_id( 'inloggen' ) || is_admin() ) {
			return $url;
		}
		return $redirect ? deedni_page_url( 'inloggen', array( 'redirect_to' => rawurlencode( $redirect ) ) ) : deedni_page_url( 'inloggen' );
	},
	10,
	2
);

/**
 * Cv downloaden: ?deedni_cv={kandidaat-ID}&_wpnonce=...
 * Het bestand wordt doorgegeven na een toegangscontrole, zodat de link in de pagina nooit het echte pad toont.
 */
add_action(
	'template_redirect',
	function () {
		if ( empty( $_GET['deedni_cv'] ) ) {
			return;
		}
		$kandidaat_id = absint( $_GET['deedni_cv'] );
		if ( ! is_user_logged_in() ) {
			deedni_redirect_to_login();
		}
		check_admin_referer( 'deedni_cv_' . $kandidaat_id );
		if ( 'kandidaat' !== get_post_type( $kandidaat_id ) || ! deedni_has_contact_access( $kandidaat_id ) ) {
			wp_die( 'Je hebt geen toegang tot dit cv.', 'Geen toegang', array( 'response' => 403 ) );
		}
		$file = get_attached_file( (int) deedni_field( $kandidaat_id, 'cv' ) );
		if ( (int) get_post_field( 'post_author', (int) deedni_field( $kandidaat_id, 'cv' ) ) !== (int) get_post_field( 'post_author', $kandidaat_id ) && ! deedni_is_beheerder() ) {
			$file = '';
		}
		if ( ! $file || ! is_readable( $file ) ) {
			wp_die( 'Dit cv is niet gevonden.', 'Niet gevonden', array( 'response' => 404 ) );
		}
		$type = wp_check_filetype( $file );
		nocache_headers();
		header( 'Content-Type: ' . ( $type['type'] ? $type['type'] : 'application/octet-stream' ) );
		header( 'Content-Disposition: attachment; filename="cv-' . deedni_code( $kandidaat_id ) . '.' . $type['ext'] . '"' );
		header( 'Content-Length: ' . filesize( $file ) );
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	},
	0
);

function deedni_cv_url( $kandidaat_id ) {
	if ( ! deedni_field( $kandidaat_id, 'cv' ) ) {
		return '';
	}
	return wp_nonce_url( add_query_arg( 'deedni_cv', (int) $kandidaat_id, home_url( '/' ) ), 'deedni_cv_' . (int) $kandidaat_id );
}

/** Kandidaten en hun bijlagen niet in de mediabibliotheek van andere gebruikers laten zien. */
add_filter(
	'ajax_query_attachments_args',
	function ( $query ) {
		if ( ! deedni_is_beheerder() ) {
			$query['author'] = get_current_user_id();
		}
		return $query;
	}
);

/** Kandidaatprofielen nooit in de standaard zoekresultaten, feeds of sitemaps. */
add_filter(
	'wp_sitemaps_post_types',
	function ( $types ) {
		unset( $types['kandidaat'], $types['bedrijf'], $types['aanbod'] );
		return $types;
	}
);

add_action(
	'wp_head',
	function () {
		if ( is_singular( 'kandidaat' ) || deedni_is_page( 'zoeken' ) || deedni_is_page( 'dashboard' ) || deedni_is_page( 'profiel' ) ) {
			echo '<meta name="robots" content="noindex, nofollow">' . "\n";
		}
	},
	1
);
