<?php
/**
 * Idempotency keys: elk formulier stuurt een eenmalige sleutel mee (_deedni_sleutel, gemaakt door deedni.js).
 * Komt dezelfde sleutel twee keer binnen (dubbelklik, opnieuw versturen na een haperende verbinding,
 * terugknop en nog eens), dan voeren we de actie niet opnieuw uit maar geven we hetzelfde antwoord terug.
 *
 * Dit vult de sloten uit aanbod.php aan: die voorkomen dat twee verschillende verzoeken tegelijk iets
 * dubbel doen; de sleutel zorgt ervoor dat hetzelfde verzoek ook het juiste antwoord krijgt.
 *
 * Zonder JavaScript is er geen sleutel en werkt alles zoals voorheen.
 */

defined( 'ABSPATH' ) || exit;

/** Formulieren waarbij een tweede keer versturen geen kwaad kan of juist moet werken. */
function deedni_sleutel_uitgezonderd() {
	return array( 'inloggen' );
}

/** Naam van de rij in wp_options voor deze sleutel, of '' als er geen geldige sleutel is. */
function deedni_sleutel_naam( $actie ) {
	$sleutel = isset( $_POST['_deedni_sleutel'] ) ? sanitize_text_field( wp_unslash( $_POST['_deedni_sleutel'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! preg_match( '/^[A-Za-z0-9-]{16,64}$/', $sleutel ) ) {
		return '';
	}
	// Per gebruiker (of per IP voor bezoekers): een sleutel van een ander levert nooit diens antwoord op.
	$wie = get_current_user_id() ? 'u' . get_current_user_id() : 'ip' . deedni_ip();
	return 'deedni_ik_' . substr( hash_hmac( 'sha256', $wie . '|' . $actie . '|' . $sleutel, wp_salt( 'nonce' ) ), 0, 40 );
}

function deedni_sleutel_lees( $naam ) {
	global $wpdb;
	$waarde = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $naam ) ); // phpcs:ignore WordPress.DB
	$data   = null === $waarde ? null : json_decode( $waarde, true );
	return is_array( $data ) ? $data : null;
}

/**
 * Begin van een verzoek met sleutel. Geeft true als dit verzoek de actie mag uitvoeren.
 * Anders is het antwoord van het eerste verzoek al teruggestuurd (en stopt PHP hier).
 */
function deedni_sleutel_start( $actie ) {
	global $wpdb;
	if ( in_array( $actie, deedni_sleutel_uitgezonderd(), true ) ) {
		return true;
	}
	$naam = deedni_sleutel_naam( $actie );
	if ( ! $naam ) {
		return true;
	}
	// INSERT IGNORE is één databasebewerking: van twee gelijktijdige verzoeken krijgt er precies één de rij.
	$nieuw = $wpdb->query( // phpcs:ignore WordPress.DB
		$wpdb->prepare(
			"INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'off')",
			$naam,
			wp_json_encode( array( 's' => 'bezig', 't' => time() ) )
		)
	);
	if ( 1 === (int) $nieuw ) {
		$GLOBALS['deedni_sleutel'] = $naam;
		// Gaat het verzoek mis of eindigt het met een fout, dan mag dezelfde sleutel het opnieuw proberen.
		register_shutdown_function( 'deedni_sleutel_opruimen_als_bezig', $naam );
		return true;
	}

	// Dezelfde sleutel kwam al eerder binnen. Is het eerste verzoek nog bezig, dan wachten we kort.
	$tot = microtime( true ) + 8;
	do {
		$data = deedni_sleutel_lees( $naam );
		if ( ! $data ) {
			return true; // Het eerste verzoek is mislukt en opgeruimd: dit verzoek mag het doen.
		}
		if ( 'klaar' === $data['s'] ) {
			$GLOBALS['deedni_sleutel_herhaling'] = true;
			deedni_klaar( $data['url'], $data['melding'], isset( $data['extra'] ) && is_array( $data['extra'] ) ? $data['extra'] : array() );
			exit;
		}
		usleep( 200000 );
	} while ( microtime( true ) < $tot );

	deedni_fout( $actie, array( 'form' => 'We zijn hier nog mee bezig. Wacht even en ververs dan de pagina.' ) );
	return false;
}

/** Na een gelukte actie: het antwoord bewaren, zodat een herhaling precies hetzelfde terugkrijgt. */
function deedni_sleutel_klaar( $url, $melding, $extra ) {
	global $wpdb;
	if ( empty( $GLOBALS['deedni_sleutel'] ) ) {
		return;
	}
	$wpdb->update( // phpcs:ignore WordPress.DB
		$wpdb->options,
		array( 'option_value' => wp_json_encode( array( 's' => 'klaar', 't' => time(), 'url' => $url, 'melding' => $melding, 'extra' => $extra ) ) ),
		array( 'option_name' => $GLOBALS['deedni_sleutel'] )
	);
	$GLOBALS['deedni_sleutel'] = '';
}

function deedni_sleutel_opruimen_als_bezig( $naam ) {
	$data = deedni_sleutel_lees( $naam );
	if ( $data && 'bezig' === $data['s'] ) {
		global $wpdb;
		$wpdb->delete( $wpdb->options, array( 'option_name' => $naam ) ); // phpcs:ignore WordPress.DB
	}
}

/** Sleutels ouder dan een dag weggooien (dagelijkse opruimtaak uit beveiliging.php). */
add_action(
	'deedni_opruimen',
	function () {
		global $wpdb;
		$rijen = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'deedni\\_ik\\_%' LIMIT 2000" ); // phpcs:ignore WordPress.DB
		foreach ( (array) $rijen as $rij ) {
			$data = json_decode( $rij->option_value, true );
			if ( ! is_array( $data ) || empty( $data['t'] ) || $data['t'] < time() - DAY_IN_SECONDS ) {
				$wpdb->delete( $wpdb->options, array( 'option_name' => $rij->option_name ) ); // phpcs:ignore WordPress.DB
			}
		}
	}
);
