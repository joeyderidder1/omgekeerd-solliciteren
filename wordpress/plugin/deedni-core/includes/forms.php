<?php
/**
 * Verwerkt de formulieren op de site: registreren, een aanbod sturen, reageren op een aanbod en
 * een aanname melden. Fouten blijven in deze request (deedni_flash), zodat ingevulde velden blijven staan.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'template_redirect', 'deedni_handle_forms', 10 );

function deedni_handle_forms() {
	if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['deedni_action'] ) ) {
		return;
	}
	$action = sanitize_key( $_POST['deedni_action'] );
	switch ( $action ) {
		case 'registreren':
			deedni_handle_register();
			break;
		case 'aanbod_sturen':
			deedni_handle_send_offer();
			break;
		case 'aanbod_reageren':
			deedni_handle_offer_response();
			break;
		case 'aanbod_aangenomen':
			deedni_handle_hired();
			break;
	}
}

/** Waarde uit $_POST, opgeschoond. */
function deedni_post( $key, $type = 'text' ) {
	if ( ! isset( $_POST[ $key ] ) ) {
		return '';
	}
	$value = wp_unslash( $_POST[ $key ] );
	switch ( $type ) {
		case 'email':
			return sanitize_email( $value );
		case 'textarea':
			return sanitize_textarea_field( $value );
		case 'int':
			return absint( preg_replace( '/[^\d]/', '', (string) $value ) );
		case 'raw':
			return (string) $value;
		default:
			return sanitize_text_field( $value );
	}
}

/* ---------- Registreren ---------- */

function deedni_handle_register() {
	if ( ! wp_verify_nonce( isset( $_POST['_deedni_nonce'] ) ? $_POST['_deedni_nonce'] : '', 'deedni_registreren' ) ) {
		deedni_flash( 'registreren', array( 'errors' => array( 'form' => 'Je sessie is verlopen. Probeer het opnieuw.' ) ) );
		return;
	}
	// Honeypot: dit veld is onzichtbaar voor mensen.
	if ( deedni_post( 'website_url' ) ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	$data = array(
		'rol'          => 'werkgever' === deedni_post( 'rol' ) ? 'werkgever' : 'kandidaat',
		'voornaam'     => deedni_post( 'voornaam' ),
		'bedrijfsnaam' => deedni_post( 'bedrijfsnaam' ),
		'email'        => deedni_post( 'email', 'email' ),
		'wachtwoord'   => deedni_post( 'wachtwoord', 'raw' ),
		'akkoord'      => ! empty( $_POST['akkoord'] ),
	);

	$errors = array();
	if ( '' === $data['voornaam'] ) {
		$errors['voornaam'] = 'Vul je voornaam in.';
	}
	if ( 'werkgever' === $data['rol'] && '' === $data['bedrijfsnaam'] ) {
		$errors['bedrijfsnaam'] = 'Vul de naam van je bedrijf in.';
	}
	if ( ! is_email( $data['email'] ) ) {
		$errors['email'] = 'Dit e-mailadres klopt niet. Bijvoorbeeld: naam@voorbeeld.nl';
	} elseif ( email_exists( $data['email'] ) ) {
		$errors['email'] = 'Er is al een account met dit e-mailadres. <a href="' . esc_url( deedni_page_url( 'inloggen' ) ) . '">Log in</a>.';
	}
	if ( strlen( $data['wachtwoord'] ) < 8 ) {
		$errors['wachtwoord'] = 'Kies een wachtwoord van minstens 8 tekens.';
	}
	if ( ! $data['akkoord'] ) {
		$errors['akkoord'] = 'Ga akkoord met de voorwaarden en de privacyverklaring.';
	}

	if ( $errors ) {
		unset( $data['wachtwoord'] );
		deedni_flash( 'registreren', array( 'errors' => $errors, 'data' => $data ) );
		return;
	}

	$login = sanitize_user( strstr( $data['email'], '@', true ), true );
	$login = $login ? $login : 'gebruiker';
	$base  = $login;
	for ( $i = 2; username_exists( $login ); $i++ ) {
		$login = $base . $i;
	}

	$user_id = wp_insert_user(
		array(
			'user_login'   => $login,
			'user_email'   => $data['email'],
			'user_pass'    => $data['wachtwoord'],
			'first_name'   => $data['voornaam'],
			'display_name' => 'werkgever' === $data['rol'] ? $data['bedrijfsnaam'] : $data['voornaam'],
			'role'         => 'deedni_' . $data['rol'],
		)
	);
	if ( is_wp_error( $user_id ) ) {
		unset( $data['wachtwoord'] );
		deedni_flash( 'registreren', array( 'errors' => array( 'form' => $user_id->get_error_message() ), 'data' => $data ) );
		return;
	}

	if ( 'werkgever' === $data['rol'] ) {
		$profile_id = wp_insert_post(
			array(
				'post_type'   => 'bedrijf',
				'post_status' => 'publish',
				'post_title'  => $data['bedrijfsnaam'],
				'post_author' => $user_id,
			)
		);
		update_user_meta( $user_id, 'deedni_profiel_id', $profile_id );
		deedni_set_field( $profile_id, 'bedrijfsnaam', $data['bedrijfsnaam'], 'b' );
		deedni_set_field( $profile_id, 'contactpersoon', $data['voornaam'], 'b' );
		if ( ! get_option( 'deedni_werkgever_goedkeuring', 1 ) ) {
			update_user_meta( $user_id, 'deedni_goedgekeurd', 1 );
		} else {
			deedni_mail_admin_new_werkgever( $user_id );
		}
	} else {
		$profile_id = wp_insert_post(
			array(
				'post_type'   => 'kandidaat',
				'post_status' => 'publish',
				'post_title'  => 'Nieuw profiel',
				'post_author' => $user_id,
			)
		);
		wp_update_post( array( 'ID' => $profile_id, 'post_title' => deedni_code( $profile_id ) ) );
		update_user_meta( $user_id, 'deedni_profiel_id', $profile_id );
		deedni_set_field( $profile_id, 'voornaam', $data['voornaam'], 'k' );
	}

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );
	deedni_mail_welcome( $user_id );

	wp_safe_redirect( deedni_page_url( 'profiel', array( 'deedni_melding' => 'welkom' ) ) );
	exit;
}

/** Een mislukte login op de eigen inlogpagina terugsturen naar die pagina, met melding. */
add_action(
	'wp_login_failed',
	function () {
		$referer = wp_get_referer();
		$page    = deedni_page_url( 'inloggen' );
		if ( deedni_page_id( 'inloggen' ) && $referer && 0 === strpos( $referer, strtok( $page, '?' ) ) ) {
			wp_safe_redirect( add_query_arg( 'deedni_melding', 'login_mislukt', $referer ) );
			exit;
		}
	}
);

/** Leeg ingevulde inlog ook terug naar de eigen pagina in plaats van naar wp-login.php. */
add_filter(
	'authenticate',
	function ( $user, $username, $password ) {
		if ( isset( $_POST['deedni_login'] ) && ( '' === $username || '' === $password ) ) {
			do_action( 'wp_login_failed', $username, new WP_Error( 'empty' ) );
		}
		return $user;
	},
	30,
	3
);

/* ---------- Aanbod sturen ---------- */

function deedni_handle_send_offer() {
	$kandidaat_id = absint( isset( $_POST['kandidaat_id'] ) ? $_POST['kandidaat_id'] : 0 );
	if ( ! wp_verify_nonce( isset( $_POST['_deedni_nonce'] ) ? $_POST['_deedni_nonce'] : '', 'deedni_aanbod_' . $kandidaat_id ) ) {
		deedni_flash( 'aanbod', array( 'errors' => array( 'form' => 'Je sessie is verlopen. Probeer het opnieuw.' ) ) );
		return;
	}
	$user_id    = get_current_user_id();
	$bedrijf_id = deedni_profile_id( $user_id );

	if ( ! deedni_can_search() || 'kandidaat' !== get_post_type( $kandidaat_id ) || in_array( $kandidaat_id, deedni_blocked_kandidaat_ids(), true ) || ! deedni_field( $kandidaat_id, 'profiel_zichtbaar' ) ) {
		deedni_flash( 'aanbod', array( 'errors' => array( 'form' => 'Je kunt deze kandidaat geen aanbod sturen.' ) ) );
		return;
	}
	if ( ! $bedrijf_id && ! deedni_is_beheerder() ) {
		deedni_flash( 'aanbod', array( 'errors' => array( 'form' => 'Maak eerst je bedrijfsprofiel af.' ) ) );
		return;
	}
	if ( deedni_offers_between( $kandidaat_id, $user_id, array( 'verstuurd' ) ) ) {
		deedni_flash( 'aanbod', array( 'errors' => array( 'form' => 'Je hebt deze kandidaat al een aanbod gestuurd. Wacht op een reactie.' ) ) );
		return;
	}

	$data = array(
		'functie'     => deedni_post( 'functie' ),
		'salaris_min' => deedni_post( 'salaris_min', 'int' ),
		'salaris_max' => deedni_post( 'salaris_max', 'int' ),
		'uren'        => array_key_exists( deedni_post( 'uren' ), deedni_choices( 'uren' ) ) ? deedni_post( 'uren' ) : '',
		'werkplek'    => array_key_exists( deedni_post( 'werkplek' ), deedni_choices( 'werkvorm' ) ) ? deedni_post( 'werkplek' ) : '',
		'standplaats' => deedni_post( 'standplaats' ),
		'bericht'     => deedni_post( 'bericht', 'textarea' ),
	);

	$errors = array();
	if ( '' === $data['functie'] ) {
		$errors['functie'] = 'Vul de functie in.';
	}
	if ( ! $data['salaris_min'] || ! $data['salaris_max'] ) {
		$errors['salaris'] = 'Vul een salaris van en tot in. Elk aanbod op deedni heeft een salaris.';
	} elseif ( $data['salaris_max'] < $data['salaris_min'] ) {
		$errors['salaris'] = 'Het salaris tot moet hoger zijn dan het salaris vanaf.';
	}
	if ( strlen( $data['bericht'] ) < 20 ) {
		$errors['bericht'] = 'Schrijf een persoonlijk bericht van een paar zinnen. Waarom past deze marketeer bij jullie?';
	}
	if ( $errors ) {
		deedni_flash( 'aanbod', array( 'errors' => $errors, 'data' => $data ) );
		return;
	}

	$aanbod_id = wp_insert_post(
		array(
			'post_type'   => 'aanbod',
			'post_status' => 'publish',
			'post_title'  => $data['functie'] . ' – ' . deedni_code( $kandidaat_id ),
			'post_author' => $user_id,
		)
	);
	foreach ( $data as $name => $value ) {
		deedni_set_field( $aanbod_id, $name, $value, 'a' );
	}
	deedni_set_field( $aanbod_id, 'kandidaat', $kandidaat_id, 'a' );
	deedni_set_field( $aanbod_id, 'bedrijf', $bedrijf_id, 'a' );
	deedni_set_field( $aanbod_id, 'status', 'verstuurd', 'a' );

	do_action( 'deedni_aanbod_verstuurd', $aanbod_id );
	deedni_mail_new_offer( $aanbod_id );

	wp_safe_redirect( add_query_arg( 'deedni_melding', 'aanbod_verstuurd', get_permalink( $kandidaat_id ) ) );
	exit;
}

/* ---------- Reageren op een aanbod ---------- */

function deedni_handle_offer_response() {
	$aanbod_id = absint( isset( $_POST['aanbod_id'] ) ? $_POST['aanbod_id'] : 0 );
	check_admin_referer( 'deedni_reageren_' . $aanbod_id, '_deedni_nonce' );

	$kandidaat_id = (int) deedni_field( $aanbod_id, 'kandidaat' );
	if ( 'aanbod' !== get_post_type( $aanbod_id ) || $kandidaat_id !== deedni_profile_id() || 'verstuurd' !== deedni_field( $aanbod_id, 'status' ) ) {
		wp_die( 'Je kunt op dit aanbod niet (meer) reageren.', 'Geen toegang', array( 'response' => 403 ) );
	}

	$status = 'ja' === deedni_post( 'keuze' ) ? 'geaccepteerd' : 'afgewezen';
	deedni_set_field( $aanbod_id, 'status', $status, 'a' );
	deedni_set_field( $aanbod_id, 'reactie_op', current_time( 'mysql' ), 'a' );

	do_action( 'deedni_aanbod_' . $status, $aanbod_id );
	deedni_mail_offer_response( $aanbod_id );

	wp_safe_redirect( deedni_page_url( 'dashboard', array( 'deedni_melding' => $status ) ) . '#aanbod-' . $aanbod_id );
	exit;
}

/* ---------- Aanname melden ---------- */

function deedni_handle_hired() {
	$aanbod_id = absint( isset( $_POST['aanbod_id'] ) ? $_POST['aanbod_id'] : 0 );
	check_admin_referer( 'deedni_aangenomen_' . $aanbod_id, '_deedni_nonce' );

	if ( 'aanbod' !== get_post_type( $aanbod_id ) || (int) get_post_field( 'post_author', $aanbod_id ) !== get_current_user_id() || 'geaccepteerd' !== deedni_field( $aanbod_id, 'status' ) ) {
		wp_die( 'Je kunt dit aanbod niet als aangenomen melden.', 'Geen toegang', array( 'response' => 403 ) );
	}

	deedni_set_field( $aanbod_id, 'status', 'aangenomen', 'a' );
	do_action( 'deedni_aanbod_aangenomen', $aanbod_id );
	deedni_mail_hired( $aanbod_id );

	wp_safe_redirect( deedni_page_url( 'dashboard', array( 'deedni_melding' => 'aangenomen' ) ) );
	exit;
}
