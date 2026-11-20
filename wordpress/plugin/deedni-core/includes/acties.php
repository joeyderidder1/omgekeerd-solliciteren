<?php
/**
 * Verwerkt alle formulieren op de site. Elk formulier werkt zonder JavaScript (gewone POST en daarna
 * terug naar de pagina met een melding). Met JavaScript stuurt deedni.js het formulier op de achtergrond
 * en krijgt het een JSON-antwoord, zodat pop-ups open kunnen blijven.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'template_redirect', 'deedni_handle_forms', 5 );

function deedni_handle_forms() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || empty( $_POST['deedni_action'] ) ) {
		return;
	}
	$action   = sanitize_key( wp_unslash( $_POST['deedni_action'] ) );
	$handlers = array(
		'registreren'     => 'deedni_handle_register',
		'inloggen'        => 'deedni_handle_login',
		'profiel'         => 'deedni_handle_profiel',
		'bedrijf'         => 'deedni_handle_bedrijf',
		'aanbod_sturen'   => 'deedni_handle_send_offer',
		'aanbod_reageren' => 'deedni_handle_offer_response',
		'aanbod_jamaar'   => 'deedni_handle_ja_maar',
		'tegen_reactie'   => 'deedni_handle_tegen_reactie',
		'aanbod_aangenomen' => 'deedni_handle_hired',
		'bewaren'         => 'deedni_handle_bewaren',
	);
	if ( isset( $handlers[ $action ] ) ) {
		call_user_func( $handlers[ $action ] );
	}
}

function deedni_is_ajax() {
	return ! empty( $_SERVER['HTTP_X_DEEDNI_AJAX'] );
}

/** Waarde uit $_POST, opgeschoond. */
function deedni_post( $key, $type = 'text' ) {
	if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return in_array( $type, array( 'array', 'keys', 'ids' ), true ) ? array() : '';
	}
	$value = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification
	switch ( $type ) {
		case 'email':
			return sanitize_email( $value );
		case 'textarea':
			return sanitize_textarea_field( $value );
		case 'int':
			return deedni_parse_int( $value );
		case 'url':
			return esc_url_raw( trim( (string) $value ) );
		case 'raw':
			return (string) $value;
		case 'array':
			return is_array( $value ) ? $value : array();
		case 'keys':
			return is_array( $value ) ? array_values( array_unique( array_map( 'sanitize_key', $value ) ) ) : array();
		case 'ids':
			return is_array( $value ) ? array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) ) : array();
		default:
			return is_string( $value ) ? sanitize_text_field( $value ) : '';
	}
}

function deedni_nonce_ok( $action ) {
	return wp_verify_nonce( isset( $_POST['_deedni_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_deedni_nonce'] ) ) : '', $action );
}

function deedni_nonce_field( $action ) {
	return wp_nonce_field( $action, '_deedni_nonce', true, false );
}

/** Meldingen na een actie. Sleutel in de URL (?deedni_melding=...), tekst hier. */
function deedni_melding_tekst( $key ) {
	$teksten = array(
		'welkom'           => array( 'Welkom! Je account is klaar. Vul je profiel aan, dan kunnen bedrijven je vinden.', 'success' ),
		'welkom_werkgever' => array( 'Welkom! Vul je bedrijfsprofiel aan. Dan berekenen we welke marketeers het best bij jullie passen.', 'success' ),
		'opgeslagen'       => array( 'Opgeslagen.', 'success' ),
		'gepubliceerd'     => array( 'Je profiel staat online. Bedrijven kunnen je nu vinden, zonder je naam.', 'success' ),
		'verborgen'        => array( 'Je profiel is offline. Bedrijven vinden je niet meer tot je het weer publiceert.', 'info' ),
		'aanbod_verstuurd' => array( 'Je aanbod is verstuurd. Je vindt het terug onder Verstuurd op je overzicht.', 'success' ),
		'aanbod_aangepast' => array( 'Je aangepaste aanbod is verstuurd. De kandidaat beslist nu.', 'success' ),
		'geaccepteerd'     => array( 'Je naam en contactgegevens zijn gedeeld. Het bedrijf neemt binnen een paar dagen contact op.', 'success' ),
		'afgewezen'        => array( 'Afgewezen. Het bedrijf krijgt een nette afwijzing en jij blijft anoniem.', 'info' ),
		'tegenvoorstel'    => array( 'Je voorwaarde is verstuurd. Het bedrijf past het aanbod aan, gaat akkoord of laat het vallen. Je naam blijft verborgen.', 'success' ),
		'akkoord'          => array( 'Je gaat akkoord met de voorwaarde. De kandidaat krijgt een melding en beslist nu.', 'success' ),
		'gestopt'          => array( 'Je laat dit aanbod vallen. De kandidaat blijft anoniem en jij betaalt niets.', 'info' ),
		'aangenomen'       => array( 'Gemeld. Je ontvangt de factuur per e-mail.', 'success' ),
		'bewaard'          => array( 'Bewaard. Je vindt deze kandidaat terug op je overzicht.', 'success' ),
		'niet_bewaard'     => array( 'Niet meer bewaard.', 'info' ),
		'niet_nu'          => array( 'Staat onder Niet nu. Je vindt deze kandidaat terug op je overzicht.', 'info' ),
		'geen_toegang'     => array( 'Je hebt geen toegang tot dat profiel.', 'error' ),
		'login_mislukt'    => array( 'Dat e-mailadres of wachtwoord klopt niet.', 'error' ),
		'uitgelogd'        => array( 'Je bent uitgelogd.', 'info' ),
	);
	return isset( $teksten[ $key ] ) ? $teksten[ $key ] : null;
}

/** Melding uit de URL als HTML. */
function deedni_melding_html() {
	if ( empty( $_GET['deedni_melding'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return '';
	}
	$m = deedni_melding_tekst( sanitize_key( wp_unslash( $_GET['deedni_melding'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
	return $m ? deedni_notice( $m[0], $m[1] ) : '';
}

/** Klaar: met JavaScript een JSON-antwoord, anders terug naar de pagina met een melding. */
function deedni_klaar( $url, $melding, $extra = array() ) {
	$m = deedni_melding_tekst( $melding );
	if ( deedni_is_ajax() ) {
		wp_send_json( array_merge( array( 'ok' => true, 'melding' => $m ? $m[0] : '', 'code' => $melding ), $extra ) );
	}
	$hash = '';
	if ( false !== strpos( $url, '#' ) ) {
		list( $url, $hash ) = explode( '#', $url, 2 );
		$hash               = '#' . $hash;
	}
	wp_safe_redirect( add_query_arg( 'deedni_melding', $melding, $url ) . $hash );
	exit;
}

/** Fout: met JavaScript een JSON-antwoord, anders blijven de gegevens staan in dit verzoek. */
function deedni_fout( $context, $errors, $data = array() ) {
	if ( deedni_is_ajax() ) {
		wp_send_json( array( 'ok' => false, 'errors' => $errors ) );
	}
	deedni_flash( $context, array( 'errors' => $errors, 'data' => $data ) );
}

function deedni_terug_url( $fallback ) {
	$ref = isset( $_POST['_wp_http_referer'] ) ? esc_url_raw( wp_unslash( $_POST['_wp_http_referer'] ) ) : '';
	$ref = $ref ? remove_query_arg( array( 'deedni_melding' ), home_url( $ref ) ) : '';
	return $ref ? wp_validate_redirect( $ref, $fallback ) : $fallback;
}

/* ---------- Account maken en inloggen ---------- */

function deedni_handle_register() {
	if ( ! deedni_nonce_ok( 'deedni_registreren' ) ) {
		deedni_fout( 'registreren', array( 'form' => 'Je sessie is verlopen. Probeer het opnieuw.' ) );
		return;
	}
	// Honeypot: dit veld is onzichtbaar voor mensen.
	if ( deedni_post( 'website_url' ) ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	$data = array(
		'rol'        => 'werkgever' === deedni_post( 'rol' ) ? 'werkgever' : 'kandidaat',
		'naam'       => deedni_post( 'naam' ),
		'email'      => deedni_post( 'email', 'email' ),
		'wachtwoord' => deedni_post( 'wachtwoord', 'raw' ),
		'akkoord'    => ! empty( $_POST['akkoord'] ),
	);

	$errors = array();
	if ( '' === $data['naam'] ) {
		$errors['naam'] = 'werkgever' === $data['rol'] ? 'Vul de naam van je bedrijf in.' : 'Vul je voornaam in.';
	}
	if ( ! is_email( $data['email'] ) ) {
		$errors['email'] = 'Dit e-mailadres klopt niet. Bijvoorbeeld: naam@voorbeeld.nl';
	} elseif ( email_exists( $data['email'] ) ) {
		$errors['email'] = 'Er is al een account met dit e-mailadres. Log links in.';
	}
	if ( strlen( $data['wachtwoord'] ) < 8 ) {
		$errors['wachtwoord'] = 'Kies een wachtwoord van minstens 8 tekens.';
	}
	if ( ! $data['akkoord'] ) {
		$errors['akkoord'] = 'Ga akkoord met de privacyverklaring.';
	}
	unset( $data['wachtwoord'] );
	if ( $errors ) {
		deedni_fout( 'registreren', $errors, $data );
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
			'user_pass'    => deedni_post( 'wachtwoord', 'raw' ),
			'first_name'   => 'werkgever' === $data['rol'] ? '' : $data['naam'],
			'display_name' => $data['naam'],
			'role'         => 'deedni_' . $data['rol'],
		)
	);
	if ( is_wp_error( $user_id ) ) {
		deedni_fout( 'registreren', array( 'form' => $user_id->get_error_message() ), $data );
		return;
	}

	if ( 'werkgever' === $data['rol'] ) {
		$profile_id = deedni_ensure_profile( $user_id, 'bedrijf' );
		wp_update_post( array( 'ID' => $profile_id, 'post_title' => $data['naam'] ) );
		update_post_meta( $profile_id, 'bedrijfsnaam', $data['naam'] );
		if ( ! get_option( 'deedni_werkgever_goedkeuring', 1 ) ) {
			update_user_meta( $user_id, 'deedni_goedgekeurd', 1 );
		} else {
			deedni_mail_admin_new_werkgever( $user_id );
		}
	} else {
		$profile_id = deedni_ensure_profile( $user_id, 'kandidaat' );
		update_post_meta( $profile_id, 'voornaam', $data['naam'] );
	}

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );
	deedni_mail_welcome( $user_id );

	$url = deedni_page_url( 'profiel' );
	deedni_klaar( $url, 'werkgever' === $data['rol'] ? 'welkom_werkgever' : 'welkom', array( 'url' => add_query_arg( 'deedni_melding', 'welkom', $url ) ) );
}

function deedni_handle_login() {
	if ( ! deedni_nonce_ok( 'deedni_inloggen' ) ) {
		deedni_fout( 'inloggen', array( 'form' => 'Je sessie is verlopen. Probeer het opnieuw.' ) );
		return;
	}
	$email = deedni_post( 'email' );
	$user  = wp_signon(
		array(
			'user_login'    => $email,
			'user_password' => deedni_post( 'wachtwoord', 'raw' ),
			'remember'      => ! empty( $_POST['onthouden'] ),
		),
		is_ssl()
	);
	if ( is_wp_error( $user ) ) {
		deedni_fout( 'inloggen', array( 'form' => 'Dat e-mailadres of wachtwoord klopt niet. Probeer het opnieuw of vraag een nieuw wachtwoord aan.' ), array( 'email' => $email ) );
		return;
	}
	$to = isset( $_POST['redirect_to'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ), '' ) : '';
	if ( ! $to ) {
		$to = ( deedni_is_kandidaat( $user ) || deedni_is_werkgever( $user ) ) ? home_url( '/' ) : admin_url();
	}
	wp_safe_redirect( $to );
	exit;
}

/** Een bestand (cv of logo) uploaden als bijlage van deze gebruiker. */
function deedni_upload( $field, $types, $max_mb, $parent ) {
	if ( empty( $_FILES[ $field ]['name'] ) ) {
		return 0;
	}
	if ( ! empty( $_FILES[ $field ]['size'] ) && $_FILES[ $field ]['size'] > $max_mb * MB_IN_BYTES ) {
		return new WP_Error( 'te_groot', 'Dit bestand is te groot. Het mag maximaal ' . $max_mb . ' MB zijn.' );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$id = media_handle_upload( $field, $parent, array(), array( 'test_form' => false, 'mimes' => $types ) );
	if ( is_wp_error( $id ) ) {
		return new WP_Error( 'upload', 'Uploaden lukte niet. Kies een ander bestand.' );
	}
	return (int) $id;
}

/* ---------- Profiel van een marketeer ---------- */

function deedni_handle_profiel() {
	if ( ! deedni_nonce_ok( 'deedni_profiel' ) || ! deedni_is_kandidaat() ) {
		deedni_fout( 'profiel', array( 'form' => 'Je sessie is verlopen. Probeer het opnieuw.' ) );
		return;
	}
	$id = deedni_ensure_profile( get_current_user_id(), 'kandidaat' );

	// Stap 1: persoonlijk.
	foreach ( array( 'functietitel', 'voornaam', 'achternaam', 'telefoon', 'huidige_werkgever', 'regio', 'uren', 'opleiding' ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			deedni_set_field( $id, $key, deedni_post( $key ) );
		}
	}
	if ( isset( $_POST['linkedin'] ) ) {
		deedni_set_field( $id, 'linkedin', deedni_post( 'linkedin', 'url' ) );
	}
	if ( isset( $_POST['samenvatting'] ) ) {
		deedni_set_field( $id, 'samenvatting', deedni_post( 'samenvatting', 'textarea' ) );
	}
	if ( isset( $_POST['ervaring_jaren'] ) ) {
		deedni_set_field( $id, 'ervaring_jaren', min( 50, deedni_post( 'ervaring_jaren', 'int' ) ) );
	}
	if ( isset( $_POST['salaris_min'] ) ) {
		deedni_set_field( $id, 'salaris_min', deedni_post( 'salaris_min', 'int' ) );
	}
	if ( isset( $_POST['beschikbaarheid'] ) ) {
		$v = deedni_post( 'beschikbaarheid' );
		deedni_set_field( $id, 'beschikbaarheid', array_key_exists( $v, deedni_choices( 'beschikbaarheid' ) ) ? $v : '' );
	}
	if ( isset( $_POST['zoekstatus'] ) ) {
		$v = deedni_post( 'zoekstatus' );
		deedni_set_field( $id, 'zoekstatus', array_key_exists( $v, deedni_choices( 'zoekstatus' ) ) ? $v : 'open' );
	}
	if ( isset( $_POST['werkplekken_aanwezig'] ) ) {
		deedni_set_field( $id, 'werkplekken', array_values( array_intersect( deedni_post( 'werkplekken', 'keys' ), array_keys( deedni_choices( 'werkvorm' ) ) ) ) );
		delete_post_meta( $id, 'werkvorm' );
	}
	if ( isset( $_POST['specialisme'] ) ) {
		wp_set_object_terms( $id, deedni_post( 'specialisme', 'int' ) ? array( deedni_post( 'specialisme', 'int' ) ) : array(), 'specialisme' );
	}
	if ( isset( $_POST['provincie'] ) ) {
		wp_set_object_terms( $id, deedni_post( 'provincie', 'int' ) ? array( deedni_post( 'provincie', 'int' ) ) : array(), 'provincie' );
	}
	if ( isset( $_POST['tools_aanwezig'] ) ) {
		$tools = deedni_post( 'tools', 'ids' );
		foreach ( array_filter( array_map( 'trim', explode( ',', deedni_post( 'tools_nieuw' ) ) ) ) as $nieuw ) {
			$t = term_exists( $nieuw, 'vaardigheid' );
			if ( ! $t ) {
				$t = wp_insert_term( mb_substr( $nieuw, 0, 40 ), 'vaardigheid' );
			}
			if ( ! is_wp_error( $t ) && $t ) {
				$tools[] = (int) ( is_array( $t ) ? $t['term_id'] : $t );
			}
		}
		wp_set_object_terms( $id, array_values( array_unique( $tools ) ), 'vaardigheid' );
	}

	// Stap 2: werkervaring.
	if ( isset( $_POST['werkervaring_aanwezig'] ) ) {
		$rows = array();
		foreach ( deedni_post( 'werkervaring', 'array' ) as $row ) {
			$row = array_map( 'sanitize_textarea_field', array_intersect_key( (array) $row, array_flip( array( 'functie', 'werkgever', 'soort', 'periode', 'tekst' ) ) ) );
			if ( ! empty( $row['functie'] ) ) {
				$rows[] = $row + array( 'functie' => '', 'werkgever' => '', 'soort' => '', 'periode' => '', 'tekst' => '' );
			}
		}
		deedni_set_field( $id, 'werkervaring', $rows );
	}

	// Stap 3: projecten met resultaten.
	if ( isset( $_POST['projecten_aanwezig'] ) ) {
		$rows = array();
		foreach ( deedni_post( 'projecten', 'array' ) as $row ) {
			$row = (array) $row;
			$p   = array();
			foreach ( array( 'titel', 'klant', 'rol', 'branche', 'tools' ) as $k ) {
				$p[ $k ] = isset( $row[ $k ] ) ? sanitize_text_field( $row[ $k ] ) : '';
			}
			$p['aanpak'] = isset( $row['aanpak'] ) ? sanitize_textarea_field( $row['aanpak'] ) : '';
			$p['link']   = isset( $row['link'] ) ? esc_url_raw( trim( $row['link'] ) ) : '';
			$p['resultaten'] = array();
			foreach ( isset( $row['resultaten'] ) ? (array) $row['resultaten'] : array() as $r ) {
				$r = array(
					'cijfer' => isset( $r['cijfer'] ) ? sanitize_text_field( $r['cijfer'] ) : '',
					'tekst'  => isset( $r['tekst'] ) ? sanitize_text_field( $r['tekst'] ) : '',
				);
				if ( $r['cijfer'] || $r['tekst'] ) {
					$p['resultaten'][] = $r;
				}
			}
			if ( $p['titel'] ) {
				$rows[] = $p;
			}
		}
		deedni_set_field( $id, 'projecten', $rows );
	}

	// Blokkeerlijst.
	if ( isset( $_POST['blokkeerlijst'] ) ) {
		deedni_save_blokkeerlijst( $id, deedni_post( 'blokkeerlijst', 'textarea' ) );
	}

	// Cv.
	if ( ! empty( $_POST['cv_verwijderen'] ) ) {
		delete_post_meta( $id, 'cv' );
	}
	$cv = deedni_upload(
		'cv',
		array(
			'pdf'  => 'application/pdf',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		),
		5,
		$id
	);
	if ( is_wp_error( $cv ) ) {
		deedni_fout( 'profiel', array( 'cv' => $cv->get_error_message() ) );
		return;
	}
	if ( $cv ) {
		update_post_meta( $id, 'cv', $cv );
	}

	update_post_meta( $id, 'bevestigd_op', time() );
	wp_update_post( array( 'ID' => $id ) ); // wijzigingsdatum bijwerken

	$melding = 'opgeslagen';
	if ( isset( $_POST['publiceren'] ) ) {
		$aan = '1' === deedni_post( 'publiceren' );
		if ( $aan && ! deedni_profile_complete( $id ) ) {
			deedni_fout( 'profiel', array( 'form' => 'Vul eerst je functietitel, specialisme en regio in. Dan kunnen bedrijven je vinden.' ) );
			return;
		}
		update_post_meta( $id, 'profiel_zichtbaar', $aan ? 1 : 0 );
		$melding = $aan ? 'gepubliceerd' : 'verborgen';
	}

	$naar = sanitize_key( deedni_post( 'naar' ) );
	$url  = deedni_page_url( 'profiel' ) . ( $naar ? '#' . $naar : '' );
	deedni_klaar( $url, $melding );
}

/* ---------- Bedrijfsprofiel ---------- */

function deedni_handle_bedrijf() {
	if ( ! deedni_nonce_ok( 'deedni_bedrijf' ) || ! ( deedni_is_werkgever() || deedni_is_beheerder() ) ) {
		deedni_fout( 'bedrijf', array( 'form' => 'Je sessie is verlopen. Probeer het opnieuw.' ) );
		return;
	}
	$id   = deedni_ensure_profile( get_current_user_id(), 'bedrijf' );
	$naam = deedni_post( 'bedrijfsnaam' );
	if ( '' === $naam ) {
		deedni_fout( 'bedrijf', array( 'bedrijfsnaam' => 'Vul de naam van je bedrijf in.' ) );
		return;
	}
	wp_update_post( array( 'ID' => $id, 'post_title' => $naam ) );
	update_post_meta( $id, 'bedrijfsnaam', $naam );
	foreach ( array( 'soort', 'plaats', 'medewerkers', 'kvk_nummer', 'contactpersoon', 'telefoon', 'zoek_uren' ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			deedni_set_field( $id, $key, deedni_post( $key ) );
		}
	}
	if ( isset( $_POST['website'] ) ) {
		deedni_set_field( $id, 'website', deedni_post( 'website', 'url' ) );
	}
	if ( isset( $_POST['omschrijving'] ) ) {
		deedni_set_field( $id, 'omschrijving', deedni_post( 'omschrijving', 'textarea' ) );
	}
	if ( isset( $_POST['zoek_aanwezig'] ) ) {
		deedni_set_field( $id, 'zoek_specialisme', deedni_post( 'zoek_specialisme', 'ids' ) );
		deedni_set_field( $id, 'zoek_tools', deedni_post( 'zoek_tools', 'ids' ) );
		deedni_set_field( $id, 'zoek_provincie', deedni_post( 'zoek_provincie', 'ids' ) );
		deedni_set_field( $id, 'zoek_werkplek', array_values( array_intersect( deedni_post( 'zoek_werkplek', 'keys' ), array_keys( deedni_choices( 'werkvorm' ) ) ) ) );
		deedni_set_field( $id, 'zoek_salaris_min', deedni_post( 'zoek_salaris_min', 'int' ) );
		deedni_set_field( $id, 'zoek_salaris_max', deedni_post( 'zoek_salaris_max', 'int' ) );
	}
	if ( ! empty( $_POST['logo_verwijderen'] ) ) {
		delete_post_meta( $id, 'logo' );
	}
	$logo = deedni_upload(
		'logo',
		array(
			'jpg|jpeg' => 'image/jpeg',
			'png'      => 'image/png',
			'webp'     => 'image/webp',
		),
		2,
		$id
	);
	if ( is_wp_error( $logo ) ) {
		deedni_fout( 'bedrijf', array( 'logo' => $logo->get_error_message() ) );
		return;
	}
	if ( $logo ) {
		update_post_meta( $id, 'logo', $logo );
	}
	deedni_klaar( deedni_page_url( 'profiel' ), 'opgeslagen' );
}

/* ---------- Aanbod sturen of aanpassen (werkgever) ---------- */

function deedni_handle_send_offer() {
	$kandidaat_id = deedni_post( 'kandidaat_id', 'int' );
	$aanbod_id    = deedni_post( 'aanbod_id', 'int' );
	if ( ! deedni_nonce_ok( 'deedni_aanbod' ) ) {
		deedni_fout( 'aanbod', array( 'form' => 'Je sessie is verlopen. Probeer het opnieuw.' ) );
		return;
	}
	$user_id    = get_current_user_id();
	$bedrijf_id = deedni_profile_id( $user_id, 'bedrijf' );

	// Alleen bewaren ("Bewaar voor later").
	if ( '1' === deedni_post( 'alleen_bewaren' ) && deedni_can_search() ) {
		deedni_zet_bewaard( $kandidaat_id, true );
		deedni_klaar( deedni_terug_url( deedni_page_url( 'dashboard' ) ), 'bewaard' );
	}

	if ( ! deedni_can_search() || 'kandidaat' !== get_post_type( $kandidaat_id ) || in_array( $kandidaat_id, deedni_blocked_kandidaat_ids(), true ) ) {
		deedni_fout( 'aanbod', array( 'form' => 'Je kunt deze kandidaat geen aanbod sturen.' ) );
		return;
	}
	if ( ! $aanbod_id && ! deedni_field( $kandidaat_id, 'profiel_zichtbaar' ) ) {
		deedni_fout( 'aanbod', array( 'form' => 'Dit profiel staat niet meer online.' ) );
		return;
	}
	if ( ! $bedrijf_id ) {
		deedni_fout( 'aanbod', array( 'form' => 'Maak eerst je bedrijfsprofiel af.' ) );
		return;
	}
	if ( $aanbod_id ) {
		$oud = deedni_aanbod( $aanbod_id );
		if ( 'aanbod' !== get_post_type( $aanbod_id ) || $oud['werkgever_id'] !== $user_id || 'tegenvoorstel' !== $oud['status'] ) {
			deedni_fout( 'aanbod', array( 'form' => 'Dit aanbod kun je niet meer aanpassen.' ) );
			return;
		}
	} elseif ( deedni_offers_between( $kandidaat_id, $user_id, array( 'verstuurd', 'tegenvoorstel' ) ) ) {
		deedni_fout( 'aanbod', array( 'form' => 'Je hebt deze kandidaat al een aanbod gestuurd. Wacht op een reactie.' ) );
		return;
	}

	$data = array(
		'functie'     => deedni_post( 'functie' ),
		'salaris_min' => deedni_post( 'salaris_min', 'int' ),
		'salaris_max' => deedni_post( 'salaris_max', 'int' ),
		'uren'        => deedni_post( 'uren' ),
		'contract'    => array_key_exists( deedni_post( 'contract' ), deedni_choices( 'contract' ) ) ? deedni_post( 'contract' ) : '',
		'werkplek'    => array_key_exists( deedni_post( 'werkplek' ), deedni_choices( 'werkvorm' ) ) ? deedni_post( 'werkplek' ) : '',
		'standplaats' => deedni_post( 'standplaats' ),
		'bericht'     => deedni_post( 'bericht', 'textarea' ),
	);

	$errors = array();
	if ( '' === $data['functie'] ) {
		$errors['functie'] = 'Vul de functie in.';
	}
	if ( ! $data['salaris_min'] || ! $data['salaris_max'] ) {
		$errors['salaris'] = 'Vul een salaris van en tot in. Zonder salaris gaat een aanbod niet de deur uit.';
	} elseif ( $data['salaris_max'] < $data['salaris_min'] ) {
		$errors['salaris'] = 'Het salaris tot moet hoger zijn dan het salaris vanaf.';
	} elseif ( $data['salaris_min'] < 500 || $data['salaris_max'] > 50000 ) {
		$errors['salaris'] = 'Vul het bruto salaris per maand in, bijvoorbeeld 4.200.';
	}
	if ( mb_strlen( $data['bericht'] ) < 20 ) {
		$errors['bericht'] = 'Schrijf een persoonlijk bericht van een paar zinnen. Waarom past dit profiel bij jullie?';
	}
	if ( $errors ) {
		deedni_fout( 'aanbod', $errors, $data );
		return;
	}

	if ( $aanbod_id ) {
		foreach ( $data as $name => $value ) {
			deedni_set_field( $aanbod_id, $name, $value );
		}
		update_post_meta( $aanbod_id, 'status', 'verstuurd' );
		update_post_meta( $aanbod_id, 'ronde', $oud['ronde'] + 1 );
		update_post_meta( $aanbod_id, 'akkoord', 0 );
		wp_update_post( array( 'ID' => $aanbod_id ) );
		deedni_aanbod_log( $aanbod_id, 'Aanbod aangepast: ' . deedni_range( $data['salaris_min'], $data['salaris_max'] ) );
		deedni_mail_offer_updated( $aanbod_id );
		do_action( 'deedni_aanbod_aangepast', $aanbod_id );
		deedni_klaar( deedni_terug_url( deedni_page_url( 'dashboard' ) ), 'aanbod_aangepast', array( 'aanbod_id' => $aanbod_id ) );
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
		deedni_set_field( $aanbod_id, $name, $value );
	}
	update_post_meta( $aanbod_id, 'kandidaat', $kandidaat_id );
	update_post_meta( $aanbod_id, 'bedrijf', $bedrijf_id );
	update_post_meta( $aanbod_id, 'status', 'verstuurd' );
	update_post_meta( $aanbod_id, 'ronde', 1 );
	deedni_aanbod_log( $aanbod_id, 'Aanbod verstuurd: ' . deedni_range( $data['salaris_min'], $data['salaris_max'] ) );

	do_action( 'deedni_aanbod_verstuurd', $aanbod_id );
	deedni_mail_new_offer( $aanbod_id );

	deedni_klaar( deedni_terug_url( deedni_page_url( 'dashboard' ) ), 'aanbod_verstuurd', array( 'aanbod_id' => $aanbod_id ) );
}

/* ---------- Reageren op een aanbod (marketeer) ---------- */

/** Mag de ingelogde marketeer op dit aanbod reageren? */
function deedni_eigen_open_aanbod( $aanbod_id ) {
	$a = deedni_aanbod( $aanbod_id );
	return 'aanbod' === get_post_type( $aanbod_id ) && $a['kandidaat_id'] && $a['kandidaat_id'] === deedni_profile_id( 0, 'kandidaat' ) && 'verstuurd' === $a['status'];
}

function deedni_handle_offer_response() {
	$aanbod_id = deedni_post( 'aanbod_id', 'int' );
	if ( ! deedni_nonce_ok( 'deedni_reageren_' . $aanbod_id ) || ! deedni_eigen_open_aanbod( $aanbod_id ) ) {
		deedni_fout( 'reageren', array( 'form' => 'Je kunt op dit aanbod niet (meer) reageren.' ) );
		return;
	}
	$status = 'ja' === deedni_post( 'keuze' ) ? 'geaccepteerd' : 'afgewezen';
	update_post_meta( $aanbod_id, 'status', $status );
	update_post_meta( $aanbod_id, 'reactie_op', current_time( 'mysql' ) );
	wp_update_post( array( 'ID' => $aanbod_id ) );
	deedni_aanbod_log( $aanbod_id, 'geaccepteerd' === $status ? 'Kandidaat zegt ja' : 'Kandidaat zegt nee' );

	do_action( 'deedni_aanbod_' . $status, $aanbod_id );
	deedni_mail_offer_response( $aanbod_id );

	deedni_klaar( deedni_page_url( 'dashboard' ) . '#aanbod-' . $aanbod_id, $status, array( 'status' => $status ) );
}

function deedni_handle_ja_maar() {
	$aanbod_id = deedni_post( 'aanbod_id', 'int' );
	if ( ! deedni_nonce_ok( 'deedni_jamaar' ) || ! deedni_eigen_open_aanbod( $aanbod_id ) ) {
		deedni_fout( 'jamaar', array( 'form' => 'Je kunt op dit aanbod niet (meer) reageren.' ) );
		return;
	}
	$voorwaarden = array_values( array_intersect( deedni_post( 'voorwaarden', 'keys' ), array_keys( deedni_choices( 'voorwaarden' ) ) ) );
	$bericht     = deedni_post( 'bericht', 'textarea' );
	$min         = deedni_post( 'tegen_min', 'int' );
	$max         = deedni_post( 'tegen_max', 'int' );
	$errors      = array();
	if ( ! $voorwaarden ) {
		$errors['voorwaarden'] = 'Kies waar je voorwaarde over gaat.';
	}
	if ( mb_strlen( $bericht ) < 10 ) {
		$errors['bericht'] = 'Schrijf kort wat je anders wilt, en wat wel goed is.';
	}
	if ( $min && $max && $max < $min ) {
		$errors['tegen_salaris'] = 'Het salaris tot moet hoger zijn dan het salaris vanaf.';
	}
	if ( $errors ) {
		deedni_fout( 'jamaar', $errors );
		return;
	}
	update_post_meta( $aanbod_id, 'tegen_voorwaarden', $voorwaarden );
	update_post_meta( $aanbod_id, 'tegen_min', $min );
	update_post_meta( $aanbod_id, 'tegen_max', $max );
	update_post_meta( $aanbod_id, 'tegen_wens', deedni_post( 'tegen_wens' ) );
	update_post_meta( $aanbod_id, 'tegen_anders', deedni_post( 'tegen_anders' ) );
	update_post_meta( $aanbod_id, 'tegen_bericht', $bericht );
	update_post_meta( $aanbod_id, 'status', 'tegenvoorstel' );
	update_post_meta( $aanbod_id, 'reactie_op', current_time( 'mysql' ) );
	wp_update_post( array( 'ID' => $aanbod_id ) );
	deedni_aanbod_log( $aanbod_id, 'Kandidaat reageert met Ja, maar' );

	do_action( 'deedni_aanbod_tegenvoorstel', $aanbod_id );
	deedni_mail_offer_response( $aanbod_id );

	deedni_klaar( deedni_page_url( 'dashboard' ) . '#aanbod-' . $aanbod_id, 'tegenvoorstel', array( 'status' => 'tegenvoorstel' ) );
}

/* ---------- Werkgever reageert op een tegenvoorstel ---------- */

function deedni_handle_tegen_reactie() {
	$aanbod_id = deedni_post( 'aanbod_id', 'int' );
	$a         = deedni_aanbod( $aanbod_id );
	if ( ! deedni_nonce_ok( 'deedni_tegen_' . $aanbod_id ) || 'aanbod' !== get_post_type( $aanbod_id ) || $a['werkgever_id'] !== get_current_user_id() || 'tegenvoorstel' !== $a['status'] ) {
		deedni_fout( 'tegen', array( 'form' => 'Je kunt op dit tegenvoorstel niet (meer) reageren.' ) );
		return;
	}
	if ( 'akkoord' === deedni_post( 'keuze' ) ) {
		if ( $a['tegen']['min'] ) {
			update_post_meta( $aanbod_id, 'salaris_min', $a['tegen']['min'] );
			update_post_meta( $aanbod_id, 'salaris_max', max( $a['tegen']['min'], $a['tegen']['max'] ) );
		}
		update_post_meta( $aanbod_id, 'status', 'verstuurd' );
		update_post_meta( $aanbod_id, 'akkoord', 1 );
		update_post_meta( $aanbod_id, 'ronde', $a['ronde'] + 1 );
		wp_update_post( array( 'ID' => $aanbod_id ) );
		deedni_aanbod_log( $aanbod_id, 'Werkgever gaat akkoord met het tegenvoorstel' );
		deedni_mail_offer_updated( $aanbod_id );
		deedni_klaar( deedni_page_url( 'dashboard' ) . '#aanbod-' . $aanbod_id, 'akkoord', array( 'status' => 'verstuurd' ) );
	}
	update_post_meta( $aanbod_id, 'status', 'gestopt' );
	wp_update_post( array( 'ID' => $aanbod_id ) );
	deedni_aanbod_log( $aanbod_id, 'Werkgever laat het aanbod vallen' );
	deedni_mail_offer_stopped( $aanbod_id );
	deedni_klaar( deedni_page_url( 'dashboard' ) . '#aanbod-' . $aanbod_id, 'gestopt', array( 'status' => 'gestopt' ) );
}

/* ---------- Aanname melden ---------- */

function deedni_handle_hired() {
	$aanbod_id = deedni_post( 'aanbod_id', 'int' );
	$a         = deedni_aanbod( $aanbod_id );
	if ( ! deedni_nonce_ok( 'deedni_aangenomen_' . $aanbod_id ) || 'aanbod' !== get_post_type( $aanbod_id ) || $a['werkgever_id'] !== get_current_user_id() || 'geaccepteerd' !== $a['status'] ) {
		deedni_fout( 'aangenomen', array( 'form' => 'Je kunt dit aanbod niet als aangenomen melden.' ) );
		return;
	}
	update_post_meta( $aanbod_id, 'status', 'aangenomen' );
	wp_update_post( array( 'ID' => $aanbod_id ) );
	deedni_aanbod_log( $aanbod_id, 'Werkgever meldt een aanname' );
	do_action( 'deedni_aanbod_aangenomen', $aanbod_id );
	deedni_mail_hired( $aanbod_id );
	deedni_klaar( deedni_page_url( 'dashboard' ), 'aangenomen', array( 'status' => 'aangenomen' ) );
}

/* ---------- Bewaren en Niet nu ---------- */

function deedni_handle_bewaren() {
	$kandidaat_id = deedni_post( 'kandidaat_id', 'int' );
	if ( ! deedni_nonce_ok( 'deedni_bewaren' ) || ! deedni_can_search() || 'kandidaat' !== get_post_type( $kandidaat_id ) ) {
		deedni_fout( 'bewaren', array( 'form' => 'Dat lukte niet. Probeer het opnieuw.' ) );
		return;
	}
	$keuze = deedni_post( 'keuze' );
	if ( 'niet_nu' === $keuze ) {
		$lijst = deedni_niet_nu();
		if ( ! in_array( $kandidaat_id, $lijst, true ) ) {
			$lijst[] = $kandidaat_id;
		}
		update_user_meta( get_current_user_id(), 'deedni_niet_nu', $lijst );
		deedni_zet_bewaard( $kandidaat_id, false );
		deedni_klaar( deedni_terug_url( home_url( '/' ) ), 'niet_nu', array( 'bewaard' => false ) );
	}
	$aan = 'uit' !== $keuze;
	deedni_zet_bewaard( $kandidaat_id, $aan );
	deedni_klaar( deedni_terug_url( home_url( '/' ) ), $aan ? 'bewaard' : 'niet_bewaard', array( 'bewaard' => $aan ) );
}

/** Na uitloggen terug naar de homepage. */
add_filter(
	'logout_redirect',
	function ( $redirect_to, $requested ) {
		return $requested ? $redirect_to : add_query_arg( 'deedni_melding', 'uitgelogd', home_url( '/' ) );
	},
	10,
	2
);
