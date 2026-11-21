<?php
/**
 * E-maillog: elke mail die de site verstuurt (van deedni en van WordPress zelf), met ontvanger, onderwerp,
 * tijd, soort mail en status. Mislukt een mail, dan zie je de foutmelding. Met één klik verstuur je een
 * mail opnieuw. Te vinden in wp-admin onder Formulieren & mail > E-maillog, alleen voor beheerders.
 *
 * Privacy:
 *  - Geheime links (bevestigen, wachtwoord, alles met key=, sleutel=, token= enzovoort) worden nooit leesbaar
 *    bewaard. Zo'n mail opnieuw versturen maakt een nieuwe link.
 *  - Na de bewaartermijn (standaard 30 dagen, instelbaar) wordt een regel automatisch verwijderd.
 *  - De inhoud bewaren kan uit; dan bewaren we alleen ontvanger, onderwerp, tijd en status.
 *  - Wordt een account verwijderd, dan verdwijnen ook de mails aan dat account uit de log. De log doet mee
 *    met Extra > Persoonlijke gegevens wissen/exporteren van WordPress.
 *
 * "Verzonden" betekent: afgegeven aan de mailserver. Of de mail in de inbox kwam, ziet WordPress niet. Kijk
 * daarvoor in de log van je SMTP-plugin of mailprovider (bijvoorbeeld WP Mail SMTP).
 */

defined( 'ABSPATH' ) || exit;

define( 'DEEDNI_MAILLOG_DB', '1' );

function deedni_maillog_tabel() {
	global $wpdb;
	return $wpdb->prefix . 'deedni_maillog';
}

function deedni_maillog_installeer() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$tabel = deedni_maillog_tabel();
	dbDelta(
		"CREATE TABLE {$tabel} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			tijd datetime NOT NULL,
			type varchar(40) NOT NULL default '',
			ontvanger varchar(255) NOT NULL default '',
			onderwerp varchar(255) NOT NULL default '',
			status varchar(20) NOT NULL default 'bezig',
			fout text NOT NULL,
			inhoud longtext NOT NULL,
			headers text NOT NULL,
			gebruiker bigint(20) unsigned NOT NULL default 0,
			object bigint(20) unsigned NOT NULL default 0,
			gevoelig tinyint(1) NOT NULL default 0,
			gemaskeerd tinyint(1) NOT NULL default 0,
			bron varchar(20) NOT NULL default 'site',
			opnieuw_van bigint(20) unsigned NOT NULL default 0,
			door bigint(20) unsigned NOT NULL default 0,
			PRIMARY KEY  (id),
			KEY tijd (tijd),
			KEY status (status),
			KEY ontvanger (ontvanger(100))
		) {$wpdb->get_charset_collate()};"
	);
	update_option( 'deedni_maillog_db', DEEDNI_MAILLOG_DB );
}

/** Na het installeren of bijwerken van de plugin de tabel klaarzetten (ook als de zip is vervangen). */
add_action(
	'init',
	function () {
		if ( DEEDNI_MAILLOG_DB !== get_option( 'deedni_maillog_db' ) ) {
			deedni_maillog_installeer();
		}
	},
	5
);

function deedni_maillog_dagen() {
	return max( 1, min( 365, (int) get_option( 'deedni_maillog_dagen', 30 ) ) );
}

function deedni_maillog_inhoud_bewaren() {
	return (bool) get_option( 'deedni_maillog_inhoud', 1 );
}

function deedni_maillog_statussen() {
	return array(
		'verzonden' => array( 'Verzonden', 'ok' ),
		'mislukt'   => array( 'Mislukt', 'fout' ),
		'uit'       => array( 'Uitgezet', 'uit' ),
		'onbekend'  => array( 'Onbekend', 'wacht' ),
		'bezig'     => array( 'Bezig', 'wacht' ),
	);
}

/** Leesbare naam van de soort mail. */
function deedni_maillog_type_naam( $type ) {
	$typen = deedni_mail_typen();
	if ( isset( $typen[ $type ] ) ) {
		return $typen[ $type ]['titel'];
	}
	return 'overig' === $type || '' === $type ? 'Overig (WordPress of andere plugin)' : $type;
}

/* =========================================================================
 * Geheime links onleesbaar maken
 * ========================================================================= */

/**
 * Vervangt de waarde van geheime parameters in links (key=, sleutel=, token=, ...) door [verborgen].
 * Geeft de tekst terug en of er iets is vervangen.
 */
function deedni_maillog_maskeer( $tekst ) {
	$namen = apply_filters( 'deedni_maillog_geheime_parameters', array( 'key', 'sleutel', 'token', 'code', 'hash', 'nonce', '_wpnonce', 'password', 'pass', 'pwd', 'secret', 'auth', 'signature', 'rp_key' ) );
	$n     = 0;
	$tekst = preg_replace(
		'/([?&;](?:amp;)?(?:' . implode( '|', array_map( 'preg_quote', $namen ) ) . ')=)[^&"\'\s<>#]+/i',
		'$1[verborgen]',
		(string) $tekst,
		-1,
		$n
	);
	return array( $tekst, $n > 0 );
}

/* =========================================================================
 * Vastleggen: elke wp_mail
 * ========================================================================= */

function deedni_maillog_tabel_ok() {
	return DEEDNI_MAILLOG_DB === get_option( 'deedni_maillog_db' );
}

/** Vlak voor het versturen (laatste filter): een regel met status "bezig". */
add_filter(
	'wp_mail',
	function ( $args ) {
		if ( ! deedni_maillog_tabel_ok() || ! is_array( $args ) ) {
			return $args;
		}
		global $wpdb;
		$ctx   = isset( $GLOBALS['deedni_mail_context'] ) && is_array( $GLOBALS['deedni_mail_context'] ) ? $GLOBALS['deedni_mail_context'] : array();
		$extra = isset( $GLOBALS['deedni_maillog_extra'] ) && is_array( $GLOBALS['deedni_maillog_extra'] ) ? $GLOBALS['deedni_maillog_extra'] : array();
		if ( ! empty( $ctx['eenmalig'] ) ) {
			unset( $GLOBALS['deedni_mail_context'] ); // Alleen voor deze ene mail (de resetmail van WordPress).
		}
		$ctx   = array_merge( array( 'type' => 'overig', 'gebruiker' => 0, 'object' => 0, 'gevoelig' => false, 'bron' => 'site' ), $ctx, $extra );

		$to = isset( $args['to'] ) ? $args['to'] : '';
		$to = is_array( $to ) ? implode( ', ', $to ) : (string) $to;
		list( $onderwerp, $m1 ) = deedni_maillog_maskeer( isset( $args['subject'] ) ? $args['subject'] : '' );
		list( $inhoud, $m2 )    = deedni_maillog_maskeer( isset( $args['message'] ) ? $args['message'] : '' );
		$headers = isset( $args['headers'] ) ? $args['headers'] : array();
		$headers = is_array( $headers ) ? $headers : explode( "\n", str_replace( "\r\n", "\n", (string) $headers ) );
		$headers = array_values( array_filter( array_map( 'trim', $headers ) ) );

		$gebruiker = (int) $ctx['gebruiker'];
		if ( ! $gebruiker && is_email( $to ) ) {
			$u         = get_user_by( 'email', $to );
			$gebruiker = $u ? $u->ID : 0;
		}

		$wpdb->insert( // phpcs:ignore WordPress.DB
			deedni_maillog_tabel(),
			array(
				'tijd'        => current_time( 'mysql', true ),
				'type'        => mb_substr( sanitize_key( $ctx['type'] ), 0, 40 ),
				'ontvanger'   => mb_substr( $to, 0, 255 ),
				'onderwerp'   => mb_substr( wp_strip_all_tags( $onderwerp ), 0, 255 ),
				'status'      => 'bezig',
				'fout'        => '',
				'inhoud'      => deedni_maillog_inhoud_bewaren() ? $inhoud : '',
				'headers'     => wp_json_encode( $headers ),
				'gebruiker'   => $gebruiker,
				'object'      => (int) $ctx['object'],
				'gevoelig'    => $ctx['gevoelig'] ? 1 : 0,
				'gemaskeerd'  => ( $m1 || $m2 ) ? 1 : 0,
				'bron'        => mb_substr( sanitize_key( $ctx['bron'] ), 0, 20 ),
				'opnieuw_van' => isset( $ctx['opnieuw_van'] ) ? (int) $ctx['opnieuw_van'] : 0,
				'door'        => isset( $ctx['door'] ) ? (int) $ctx['door'] : 0,
			)
		);
		$GLOBALS['deedni_maillog_id'] = (int) $wpdb->insert_id;
		if ( $GLOBALS['deedni_maillog_id'] ) {
			$GLOBALS['deedni_maillog_open'][] = $GLOBALS['deedni_maillog_id'];
		}
		return $args;
	},
	PHP_INT_MAX
);

function deedni_maillog_zet( $id, $status, $fout = '' ) {
	global $wpdb;
	if ( ! $id ) {
		return;
	}
	$wpdb->update( deedni_maillog_tabel(), array( 'status' => $status, 'fout' => mb_substr( (string) $fout, 0, 2000 ) ), array( 'id' => (int) $id ) ); // phpcs:ignore WordPress.DB
	if ( ! empty( $GLOBALS['deedni_maillog_open'] ) ) {
		$GLOBALS['deedni_maillog_open'] = array_diff( $GLOBALS['deedni_maillog_open'], array( (int) $id ) );
	}
}

add_action(
	'wp_mail_succeeded',
	function () {
		if ( ! empty( $GLOBALS['deedni_maillog_id'] ) ) {
			deedni_maillog_zet( $GLOBALS['deedni_maillog_id'], 'verzonden' );
			$GLOBALS['deedni_maillog_id'] = 0;
		}
	}
);

add_action(
	'wp_mail_failed',
	function ( $fout ) {
		if ( empty( $GLOBALS['deedni_maillog_id'] ) ) {
			return;
		}
		$tekst = $fout instanceof WP_Error ? $fout->get_error_message() : 'Onbekende fout';
		list( $tekst ) = deedni_maillog_maskeer( $tekst );
		deedni_maillog_zet( $GLOBALS['deedni_maillog_id'], 'mislukt', $tekst ? $tekst : 'Onbekende fout' );
		$GLOBALS['deedni_maillog_id'] = 0;
	}
);

/** deedni_mail weet of wp_mail gelukt is. Hield een andere plugin de mail tegen, dan staat dat hier. */
add_action(
	'deedni_mail_resultaat',
	function ( $ok ) {
		if ( ! empty( $GLOBALS['deedni_maillog_id'] ) ) {
			deedni_maillog_zet( $GLOBALS['deedni_maillog_id'], $ok ? 'verzonden' : 'mislukt', $ok ? '' : 'wp_mail gaf geen succes terug. Mogelijk hield een andere plugin de mail tegen.' );
			$GLOBALS['deedni_maillog_id'] = 0;
		}
	}
);

/** Een mail die bij Mailteksten uit staat: wel in de log, zodat je ziet waarom iemand niets kreeg. */
add_action(
	'deedni_mail_overgeslagen',
	function ( $to, $onderwerp, $ctx ) {
		if ( ! deedni_maillog_tabel_ok() ) {
			return;
		}
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB
			deedni_maillog_tabel(),
			array(
				'tijd'      => current_time( 'mysql', true ),
				'type'      => sanitize_key( $ctx['type'] ),
				'ontvanger' => mb_substr( is_array( $to ) ? implode( ', ', $to ) : (string) $to, 0, 255 ),
				'onderwerp' => mb_substr( $onderwerp, 0, 255 ),
				'status'    => 'uit',
				'fout'      => 'Niet verstuurd: deze mail staat uit bij Formulieren & mail > Mailteksten.',
				'inhoud'    => '',
				'headers'   => '[]',
				'gebruiker' => (int) $ctx['gebruiker'],
				'object'    => (int) $ctx['object'],
				'gevoelig'  => empty( $ctx['gevoelig'] ) ? 0 : 1,
				'bron'      => sanitize_key( $ctx['bron'] ),
			)
		);
	},
	10,
	3
);

/** Bleef een mail op "bezig" staan (bijvoorbeeld omdat een plugin wp_mail vervangt), dan "onbekend". */
add_action(
	'shutdown',
	function () {
		if ( empty( $GLOBALS['deedni_maillog_open'] ) ) {
			return;
		}
		foreach ( $GLOBALS['deedni_maillog_open'] as $id ) {
			deedni_maillog_zet( $id, 'onbekend', 'WordPress kreeg geen bevestiging of de mail is verstuurd. Een andere plugin heeft het versturen overgenomen.' );
		}
	}
);

/* =========================================================================
 * Opruimen en privacy
 * ========================================================================= */

function deedni_maillog_opruimen() {
	global $wpdb;
	if ( ! deedni_maillog_tabel_ok() ) {
		return;
	}
	$grens = gmdate( 'Y-m-d H:i:s', time() - deedni_maillog_dagen() * DAY_IN_SECONDS );
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . deedni_maillog_tabel() . ' WHERE tijd < %s', $grens ) ); // phpcs:ignore WordPress.DB
}
add_action( 'deedni_opruimen', 'deedni_maillog_opruimen' );

/** Account verwijderd: de mails aan dat account ook. */
add_action(
	'deleted_user',
	function ( $user_id, $reassign = null, $user = null ) {
		global $wpdb;
		if ( ! deedni_maillog_tabel_ok() ) {
			return;
		}
		$wpdb->delete( deedni_maillog_tabel(), array( 'gebruiker' => (int) $user_id ) ); // phpcs:ignore WordPress.DB
		if ( $user instanceof WP_User ) {
			$wpdb->delete( deedni_maillog_tabel(), array( 'ontvanger' => $user->user_email ) ); // phpcs:ignore WordPress.DB
		}
	},
	10,
	3
);

/** Extra > Persoonlijke gegevens exporteren en wissen. */
add_filter(
	'wp_privacy_personal_data_exporters',
	function ( $lijst ) {
		$lijst['deedni-maillog'] = array(
			'exporter_friendly_name' => 'deedni e-maillog',
			'callback'               => function ( $email ) {
				global $wpdb;
				$rijen = deedni_maillog_tabel_ok() ? $wpdb->get_results( $wpdb->prepare( 'SELECT id, tijd, onderwerp, status FROM ' . deedni_maillog_tabel() . ' WHERE ontvanger = %s ORDER BY id', $email ) ) : array(); // phpcs:ignore WordPress.DB
				$data  = array();
				foreach ( (array) $rijen as $r ) {
					$data[] = array(
						'group_id'    => 'deedni-maillog',
						'group_label' => 'Verstuurde e-mails',
						'item_id'     => 'mail-' . $r->id,
						'data'        => array(
							array( 'name' => 'Verstuurd', 'value' => get_date_from_gmt( $r->tijd ) ),
							array( 'name' => 'Onderwerp', 'value' => $r->onderwerp ),
							array( 'name' => 'Status', 'value' => $r->status ),
						),
					);
				}
				return array( 'data' => $data, 'done' => true );
			},
		);
		return $lijst;
	}
);

add_filter(
	'wp_privacy_personal_data_erasers',
	function ( $lijst ) {
		$lijst['deedni-maillog'] = array(
			'eraser_friendly_name' => 'deedni e-maillog',
			'callback'             => function ( $email ) {
				global $wpdb;
				$n = deedni_maillog_tabel_ok() ? (int) $wpdb->delete( deedni_maillog_tabel(), array( 'ontvanger' => $email ) ) : 0; // phpcs:ignore WordPress.DB
				return array( 'items_removed' => $n > 0, 'items_retained' => false, 'messages' => array(), 'done' => true );
			},
		);
		return $lijst;
	}
);

/* =========================================================================
 * Opnieuw versturen
 * ========================================================================= */

function deedni_maillog_rij( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . deedni_maillog_tabel() . ' WHERE id = %d', (int) $id ), ARRAY_A ); // phpcs:ignore WordPress.DB
}

/** Kan deze mail opnieuw? Geeft '' als het kan, anders de reden. */
function deedni_maillog_kan_opnieuw( $rij ) {
	if ( in_array( $rij['type'], array( 'bevestigen', 'wachtwoord' ), true ) ) {
		return $rij['gebruiker'] && get_userdata( $rij['gebruiker'] ) ? '' : 'Het account bestaat niet meer.';
	}
	if ( 'uit' === $rij['status'] ) {
		return 'Deze mail staat uit bij Mailteksten. Zet hem daar aan; deze ene mail kan niet alsnog.';
	}
	if ( $rij['gemaskeerd'] || $rij['gevoelig'] ) {
		return 'Deze mail had een geheime link die we niet bewaren. Laat de ontvanger zelf een nieuwe link aanvragen.';
	}
	if ( '' === $rij['inhoud'] ) {
		return 'De inhoud is niet bewaard (zie de instelling onderaan de E-maillog).';
	}
	return '';
}

add_action(
	'admin_post_deedni_maillog_opnieuw',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.', 403 );
		}
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		check_admin_referer( 'deedni_maillog_opnieuw_' . $id );
		$rij   = deedni_maillog_rij( $id );
		$terug = isset( $_POST['terug'] ) ? esc_url_raw( wp_unslash( $_POST['terug'] ) ) : '';
		$terug = wp_validate_redirect( $terug, admin_url( 'admin.php?page=deedni-maillog' ) );
		$terug = remove_query_arg( array( 'deedni_klaar', 'deedni_reden' ), $terug );
		if ( ! $rij ) {
			wp_safe_redirect( add_query_arg( 'deedni_klaar', 'weg', $terug ) );
			exit;
		}
		$reden = deedni_maillog_kan_opnieuw( $rij );
		if ( $reden ) {
			wp_safe_redirect( add_query_arg( array( 'deedni_klaar' => 'kan_niet', 'deedni_reden' => rawurlencode( $reden ) ), $terug ) );
			exit;
		}

		$GLOBALS['deedni_maillog_extra'] = array( 'bron' => 'opnieuw', 'opnieuw_van' => $id, 'door' => get_current_user_id() );
		$ok   = false;
		$user = $rij['gebruiker'] ? get_userdata( $rij['gebruiker'] ) : null;
		if ( 'bevestigen' === $rij['type'] ) {
			if ( ! deedni_onbevestigd( $user->ID ) ) {
				wp_safe_redirect( add_query_arg( 'deedni_klaar', 'al_bevestigd', $terug ) );
				exit;
			}
			// Jij vraagt erom: de limiet van 3 mails per uur geldt dan niet.
			deedni_teller_wissen( 'mail', $user->user_email );
			$ok = (bool) deedni_stuur_bevestiging( $user->ID );
		} elseif ( 'wachtwoord' === $rij['type'] ) {
			$ok = true === retrieve_password( $user->user_login );
		} else {
			$headers = json_decode( (string) $rij['headers'], true );
			$GLOBALS['deedni_mail_context'] = array(
				'type'      => $rij['type'],
				'gebruiker' => (int) $rij['gebruiker'],
				'object'    => (int) $rij['object'],
			);
			$ok = wp_mail( $rij['ontvanger'], $rij['onderwerp'], $rij['inhoud'], is_array( $headers ) ? $headers : array() );
			unset( $GLOBALS['deedni_mail_context'] );
		}
		unset( $GLOBALS['deedni_maillog_extra'] );
		wp_safe_redirect( add_query_arg( 'deedni_klaar', $ok ? 'opnieuw' : 'opnieuw_mislukt', $terug ) );
		exit;
	}
);

/* =========================================================================
 * Instellingen
 * ========================================================================= */

add_action(
	'admin_post_deedni_maillog_instellingen',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.', 403 );
		}
		check_admin_referer( 'deedni_maillog_instellingen' );
		$terug = admin_url( 'admin.php?page=deedni-maillog' );
		if ( ! empty( $_POST['legen'] ) ) {
			global $wpdb;
			$wpdb->query( 'DELETE FROM ' . deedni_maillog_tabel() ); // phpcs:ignore WordPress.DB
			wp_safe_redirect( add_query_arg( 'deedni_klaar', 'geleegd', $terug ) );
			exit;
		}
		update_option( 'deedni_maillog_dagen', max( 1, min( 365, isset( $_POST['dagen'] ) ? absint( $_POST['dagen'] ) : 30 ) ), false );
		update_option( 'deedni_maillog_inhoud', empty( $_POST['inhoud'] ) ? 0 : 1, false );
		if ( empty( $_POST['inhoud'] ) ) {
			global $wpdb;
			$wpdb->query( 'UPDATE ' . deedni_maillog_tabel() . " SET inhoud = ''" ); // phpcs:ignore WordPress.DB
		}
		deedni_maillog_opruimen();
		wp_safe_redirect( add_query_arg( 'deedni_klaar', 'instellingen', $terug ) );
		exit;
	}
);

/* =========================================================================
 * wp-admin: Formulieren & mail > E-maillog
 * ========================================================================= */

add_action(
	'admin_menu',
	function () {
		add_menu_page( 'E-maillog', 'Formulieren & mail', 'manage_options', 'deedni-maillog', 'deedni_maillog_pagina', 'dashicons-email-alt', 59 );
		add_submenu_page( 'deedni-maillog', 'E-maillog', 'E-maillog', 'manage_options', 'deedni-maillog', 'deedni_maillog_pagina' );
	},
	9
);

/** Is er een SMTP-plugin? Zonder komt mail van de webserver zelf en belandt die vaak in spam. */
function deedni_smtp_plugin_actief() {
	return defined( 'WPMS_PLUGIN_VER' ) || class_exists( 'WPMailSMTP\\Core' ) || defined( 'FLUENTMAIL' ) || class_exists( 'Postman' ) || defined( 'EASY_WP_SMTP_PLUGIN_VERSION' ) || has_action( 'phpmailer_init' );
}

function deedni_maillog_status_html( $status ) {
	$s = deedni_maillog_statussen();
	$s = isset( $s[ $status ] ) ? $s[ $status ] : array( $status, '' );
	return '<span class="deedni-label deedni-label--' . esc_attr( $s[1] ) . '">' . esc_html( $s[0] ) . '</span>';
}

function deedni_maillog_meldingen() {
	$klaar = isset( $_GET['deedni_klaar'] ) ? sanitize_key( wp_unslash( $_GET['deedni_klaar'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$teksten = array(
		'opnieuw'         => array( 'Opnieuw verstuurd. De nieuwe regel staat bovenaan.', 'success' ),
		'opnieuw_mislukt' => array( 'Opnieuw versturen is mislukt. Bekijk de bovenste regel voor de foutmelding.', 'error' ),
		'al_bevestigd'    => array( 'Dit account heeft het e-mailadres al bevestigd. Er is niets verstuurd. De gebruiker kan gewoon inloggen.', 'info' ),
		'weg'             => array( 'Deze mail staat niet meer in de log.', 'warning' ),
		'instellingen'    => array( 'Instellingen opgeslagen.', 'success' ),
		'geleegd'         => array( 'De e-maillog is leeg.', 'success' ),
	);
	if ( 'kan_niet' === $klaar ) {
		$reden = isset( $_GET['deedni_reden'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['deedni_reden'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-warning is-dismissible"><p>Niet opnieuw verstuurd. ' . esc_html( $reden ) . '</p></div>';
	} elseif ( isset( $teksten[ $klaar ] ) ) {
		echo '<div class="notice notice-' . esc_attr( $teksten[ $klaar ][1] ) . ' is-dismissible"><p>' . esc_html( $teksten[ $klaar ][0] ) . '</p></div>';
	}
}

function deedni_maillog_opnieuw_knop( $rij, $klein = true ) {
	$reden = deedni_maillog_kan_opnieuw( $rij );
	if ( $reden ) {
		return '<span class="deedni-sub" title="' . esc_attr( $reden ) . '">Opnieuw kan niet</span>';
	}
	$label = in_array( $rij['type'], array( 'bevestigen', 'wachtwoord' ), true ) ? 'Nieuwe link sturen' : 'Opnieuw versturen';
	$terug = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$h     = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="deedni-inline">';
	$h    .= '<input type="hidden" name="action" value="deedni_maillog_opnieuw"><input type="hidden" name="id" value="' . (int) $rij['id'] . '"><input type="hidden" name="terug" value="' . esc_attr( $terug ) . '">';
	$h    .= wp_nonce_field( 'deedni_maillog_opnieuw_' . $rij['id'], '_wpnonce', false, false );
	$h    .= '<button type="submit" class="button' . ( $klein ? ' button-small' : ' button-primary' ) . '">' . esc_html( $label ) . '</button></form>';
	return $h;
}

function deedni_maillog_pagina() {
	global $wpdb;
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! deedni_maillog_tabel_ok() ) {
		deedni_maillog_installeer();
	}
	$tabel = deedni_maillog_tabel();
	echo '<div class="wrap deedni-beheer">';

	$bekijk = isset( $_GET['bekijk'] ) ? absint( $_GET['bekijk'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $bekijk ) {
		deedni_maillog_detail( $bekijk );
		echo '</div>';
		return;
	}

	echo '<h1>E-maillog</h1>';
	deedni_maillog_meldingen();
	echo '<p class="deedni-intro">Elke mail die de site verstuurt, staat hier ' . (int) deedni_maillog_dagen() . ' dagen. <strong>Verzonden</strong> betekent dat de mail is afgegeven aan de mailserver. Heeft iemand hem toch niet gekregen, vraag dan om in de spam te kijken en verstuur hem opnieuw. <strong>Mislukt</strong> toont de foutmelding van de mailserver. De teksten pas je aan bij <a href="' . esc_url( admin_url( 'admin.php?page=deedni-mailteksten' ) ) . '">Mailteksten</a>.</p>';
	if ( ! deedni_smtp_plugin_actief() ) {
		echo '<div class="notice notice-warning inline"><p><strong>Tip:</strong> er is geen SMTP-plugin actief. Mail gaat nu via de webserver en komt vaak in de spam of helemaal niet aan. Installeer <strong>WP Mail SMTP</strong> (gratis) en koppel het aan je mailadres. Kijk daarna hier of de mails op Verzonden komen.</p></div>';
	}

	// Filters.
	$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$type   = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$zoek   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$pagina = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification
	$per    = 50;
	$waar   = array( '1=1' );
	$args   = array();
	if ( isset( deedni_maillog_statussen()[ $status ] ) ) {
		$waar[] = 'status = %s';
		$args[] = $status;
	}
	if ( '' !== $type ) {
		$waar[] = 'type = %s';
		$args[] = $type;
	}
	if ( '' !== $zoek ) {
		$like   = '%' . $wpdb->esc_like( $zoek ) . '%';
		$waar[] = '(ontvanger LIKE %s OR onderwerp LIKE %s)';
		array_push( $args, $like, $like );
	}
	$where  = implode( ' AND ', $waar );
	$totaal = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) FROM {$tabel} WHERE {$where}", $args ) : "SELECT COUNT(*) FROM {$tabel} WHERE {$where}" ); // phpcs:ignore WordPress.DB
	$rijen  = $wpdb->get_results( $wpdb->prepare( "SELECT id, tijd, type, ontvanger, onderwerp, status, fout, gebruiker, object, gevoelig, gemaskeerd, bron, inhoud, opnieuw_van FROM {$tabel} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d", array_merge( $args, array( $per, ( $pagina - 1 ) * $per ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB

	// Tellers voor de afgelopen 7 dagen.
	$week  = gmdate( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS );
	$tel   = array();
	foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT status, COUNT(*) AS n FROM {$tabel} WHERE tijd >= %s GROUP BY status", $week ), ARRAY_A ) as $r ) { // phpcs:ignore WordPress.DB
		$tel[ $r['status'] ] = (int) $r['n'];
	}
	echo '<p><strong>Afgelopen 7 dagen:</strong> ' . (int) ( isset( $tel['verzonden'] ) ? $tel['verzonden'] : 0 ) . ' verzonden, ';
	$mislukt = isset( $tel['mislukt'] ) ? $tel['mislukt'] : 0;
	echo $mislukt ? '<a href="' . esc_url( admin_url( 'admin.php?page=deedni-maillog&status=mislukt' ) ) . '" class="deedni-fout"><strong>' . (int) $mislukt . ' mislukt</strong></a>' : '0 mislukt'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '.</p>';

	echo '<form method="get" class="deedni-filters"><input type="hidden" name="page" value="deedni-maillog">';
	echo '<select name="status"><option value="">Alle statussen</option>';
	foreach ( deedni_maillog_statussen() as $k => $s ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $status, $k, false ) . '>' . esc_html( $s[0] ) . '</option>';
	}
	echo '</select> <select name="type"><option value="">Alle soorten mail</option>';
	foreach ( deedni_mail_typen() as $k => $def ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $type, $k, false ) . '>' . esc_html( $def['titel'] ) . '</option>';
	}
	echo '<option value="overig"' . selected( $type, 'overig', false ) . '>Overig (WordPress of andere plugin)</option></select> ';
	echo '<input type="search" name="s" value="' . esc_attr( $zoek ) . '" placeholder="Zoek op e-mailadres of onderwerp"> <button class="button">Filteren</button>';
	if ( $status || $type || $zoek ) {
		echo ' <a href="' . esc_url( admin_url( 'admin.php?page=deedni-maillog' ) ) . '">Wis filters</a>';
	}
	echo '</form>';

	echo '<table class="widefat striped deedni-tabel"><thead><tr><th>Tijd</th><th>Ontvanger</th><th>Onderwerp</th><th>Soort</th><th>Status</th><th></th></tr></thead><tbody>';
	if ( ! $rijen ) {
		echo '<tr><td colspan="6">Nog geen mails' . ( $status || $type || $zoek ? ' met deze filters' : '' ) . '.</td></tr>';
	}
	foreach ( (array) $rijen as $r ) {
		$detail = admin_url( 'admin.php?page=deedni-maillog&bekijk=' . (int) $r['id'] );
		echo '<tr>';
		echo '<td>' . esc_html( get_date_from_gmt( $r['tijd'], 'j M Y H:i' ) ) . '</td>';
		echo '<td>' . esc_html( $r['ontvanger'] ) . ( $r['gebruiker'] && get_userdata( $r['gebruiker'] ) ? '<div class="deedni-sub"><a href="' . esc_url( get_edit_user_link( $r['gebruiker'] ) ) . '">Account</a></div>' : '' ) . '</td>';
		echo '<td><a href="' . esc_url( $detail ) . '">' . esc_html( $r['onderwerp'] ) . '</a>';
		if ( 'test' === $r['bron'] ) {
			echo ' <span class="deedni-label">Test</span>';
		} elseif ( 'opnieuw' === $r['bron'] ) {
			echo ' <span class="deedni-label">Opnieuw</span>';
		}
		echo '</td>';
		echo '<td>' . esc_html( deedni_maillog_type_naam( $r['type'] ) ) . '</td>';
		echo '<td>' . deedni_maillog_status_html( $r['status'] ) . ( $r['fout'] && 'uit' !== $r['status'] ? '<div class="deedni-sub deedni-fout">' . esc_html( $r['fout'] ) . '</div>' : '' ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td><a href="' . esc_url( $detail ) . '" class="button button-small">Bekijken</a> ' . deedni_maillog_opnieuw_knop( $r ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</tr>';
	}
	echo '</tbody></table>';

	$paginas = (int) ceil( $totaal / $per );
	if ( $paginas > 1 ) {
		echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput
			array(
				'base'    => add_query_arg( 'paged', '%#%' ),
				'format'  => '',
				'current' => $pagina,
				'total'   => $paginas,
			)
		) . '</div></div>';
	}

	// Instellingen.
	echo '<h2 style="margin-top:32px">Instellingen</h2>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="deedni_maillog_instellingen">';
	wp_nonce_field( 'deedni_maillog_instellingen' );
	echo '<table class="form-table"><tr><th><label for="ml-dagen">Bewaartermijn</label></th><td><input type="number" id="ml-dagen" name="dagen" min="1" max="365" value="' . (int) deedni_maillog_dagen() . '" class="small-text"> dagen<p class="description">Oudere regels worden elke dag automatisch verwijderd. Bewaar niet langer dan nodig; 30 dagen is meestal genoeg om een gemiste mail terug te vinden.</p></td></tr>';
	echo '<tr><th>Inhoud bewaren</th><td><label><input type="checkbox" name="inhoud" value="1"' . checked( deedni_maillog_inhoud_bewaren(), true, false ) . '> De tekst van de mail bewaren, zodat je hem kunt bekijken en opnieuw versturen</label><p class="description">Zet je dit uit, dan bewaren we alleen ontvanger, onderwerp, tijd en status, en wordt de bewaarde tekst nu gewist. Geheime links worden hoe dan ook nooit leesbaar bewaard.</p></td></tr></table>';
	echo '<p><button class="button button-primary">Opslaan</button> <button class="button button-link-delete" name="legen" value="1" onclick="return confirm(\'Alle regels uit de e-maillog verwijderen?\')">Hele log nu legen</button></p></form>';
	echo '</div>';
}

function deedni_maillog_detail( $id ) {
	$r = deedni_maillog_rij( $id );
	echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=deedni-maillog' ) ) . '">&larr; E-maillog</a></p>';
	deedni_maillog_meldingen();
	if ( ! $r ) {
		echo '<h1>Mail niet gevonden</h1><p>Deze mail staat niet (meer) in de log. Misschien is de bewaartermijn voorbij.</p>';
		return;
	}
	echo '<h1>' . esc_html( $r['onderwerp'] ) . '</h1>';
	echo '<table class="form-table deedni-detail"><tbody>';
	echo '<tr><th>Status</th><td>' . deedni_maillog_status_html( $r['status'] ) . ( $r['fout'] ? '<p class="deedni-fout">' . esc_html( $r['fout'] ) . '</p>' : '' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<tr><th>Ontvanger</th><td>' . esc_html( $r['ontvanger'] ) . ( $r['gebruiker'] && get_userdata( $r['gebruiker'] ) ? ' &middot; <a href="' . esc_url( get_edit_user_link( $r['gebruiker'] ) ) . '">account bekijken</a>' : '' ) . '</td></tr>';
	echo '<tr><th>Verstuurd</th><td>' . esc_html( get_date_from_gmt( $r['tijd'], 'l j F Y, H:i:s' ) ) . '</td></tr>';
	echo '<tr><th>Soort mail</th><td>' . esc_html( deedni_maillog_type_naam( $r['type'] ) );
	if ( isset( deedni_mail_typen()[ $r['type'] ] ) ) {
		echo ' &middot; <a href="' . esc_url( admin_url( 'admin.php?page=deedni-mailteksten&type=' . $r['type'] ) ) . '">tekst aanpassen</a>';
	}
	echo '</td></tr>';
	if ( $r['object'] && get_post( $r['object'] ) ) {
		echo '<tr><th>Hoort bij</th><td><a href="' . esc_url( get_edit_post_link( $r['object'] ) ) . '">' . esc_html( get_the_title( $r['object'] ) ) . '</a></td></tr>';
	}
	if ( 'opnieuw' === $r['bron'] && $r['opnieuw_van'] ) {
		$door = $r['door'] ? get_userdata( $r['door'] ) : null;
		echo '<tr><th>Opnieuw verstuurd</th><td>Van <a href="' . esc_url( admin_url( 'admin.php?page=deedni-maillog&bekijk=' . (int) $r['opnieuw_van'] ) ) . '">mail #' . (int) $r['opnieuw_van'] . '</a>' . ( $door ? ', door ' . esc_html( $door->display_name ) : '' ) . '</td></tr>';
	} elseif ( 'test' === $r['bron'] ) {
		echo '<tr><th>Test</th><td>Testmail vanuit Mailteksten, met voorbeeldwaarden.</td></tr>';
	}
	echo '<tr><th>Opnieuw</th><td>' . deedni_maillog_opnieuw_knop( $r, false ); // phpcs:ignore WordPress.Security.EscapeOutput
	if ( in_array( $r['type'], array( 'bevestigen', 'wachtwoord' ), true ) ) {
		echo '<p class="description">Dit maakt een nieuwe, geldige link. De oude link in eerdere mails werkt dan niet meer.</p>';
	}
	echo '</td></tr>';
	echo '</tbody></table>';

	if ( $r['gemaskeerd'] ) {
		echo '<p class="description">Geheime links in deze mail zijn vervangen door [verborgen].</p>';
	}
	if ( '' !== $r['inhoud'] ) {
		$html = $r['inhoud'];
		if ( false === stripos( implode( ' ', (array) json_decode( (string) $r['headers'], true ) ), 'text/html' ) ) {
			$html = '<pre style="white-space:pre-wrap;font-family:Arial,Helvetica,sans-serif;font-size:15px">' . esc_html( $html ) . '</pre>';
		}
		echo '<h2>Inhoud</h2><iframe class="deedni-voorbeeld" sandbox="" title="Inhoud van de mail" srcdoc="' . esc_attr( $html ) . '"></iframe>';
	} else {
		echo '<p class="description">De inhoud van deze mail is niet bewaard.</p>';
	}
}
