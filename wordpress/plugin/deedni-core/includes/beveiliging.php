<?php
/**
 * Beveiliging: alles wat niet bij één formulier hoort.
 *
 *  - Beveiligingsheaders (clickjacking, MIME-sniffing, HSTS).
 *  - Geen gebruikersnamen of e-mailadressen laten raden: auteurspagina's, REST /wp/v2/users, oEmbed,
 *    sitemaps, foutmeldingen op wp-login.php en "wachtwoord vergeten".
 *  - Pogingen tellen en afremmen: inloggen, registreren, wachtwoord vergeten, aanbiedingen sturen.
 *  - E-mailadres bevestigen bij een nieuw account (tegen account pre-hijacking en user enumeration).
 *  - Na een nieuw wachtwoord alle oude sessies afmelden.
 *  - Kandidaten nooit in lijsten, feeds of zoekresultaten; cv's niet via bijlagepagina's.
 *  - XML-RPC uit en geen WordPress-versie in de HTML.
 *
 * We bewaren geen IP-adressen: tellers gebruiken een hash met de geheime sleutel van de site en
 * verlopen vanzelf.
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * Tellers (rate limiting)
 * ========================================================================= */

/**
 * Het IP-adres van de bezoeker. Staat de site achter een proxy of CDN (bijvoorbeeld Cloudflare),
 * zet dan het echte adres via het filter 'deedni_ip', anders delen alle bezoekers één teller.
 */
function deedni_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return (string) apply_filters( 'deedni_ip', $ip );
}

function deedni_teller_sleutel( $soort, $wie ) {
	return 'deedni_rl_' . $soort . '_' . substr( hash_hmac( 'sha256', strtolower( trim( (string) $wie ) ), wp_salt( 'auth' ) ), 0, 32 );
}

/** Hoe vaak is dit binnen het lopende venster gebeurd? */
function deedni_teller( $soort, $wie ) {
	$v = get_transient( deedni_teller_sleutel( $soort, $wie ) );
	return is_array( $v ) && $v['tot'] > time() ? (int) $v['n'] : 0;
}

/** Eén keer tellen. Het venster begint bij de eerste poging en duurt $duur seconden. */
function deedni_tel( $soort, $wie, $duur ) {
	$sleutel = deedni_teller_sleutel( $soort, $wie );
	$v       = get_transient( $sleutel );
	if ( ! is_array( $v ) || $v['tot'] <= time() ) {
		$v = array( 'n' => 0, 'tot' => time() + $duur );
	}
	$v['n']++;
	set_transient( $sleutel, $v, max( 1, $v['tot'] - time() ) );
	return $v['n'];
}

function deedni_teller_wissen( $soort, $wie ) {
	delete_transient( deedni_teller_sleutel( $soort, $wie ) );
}

function deedni_te_vaak( $soort, $wie, $max ) {
	return deedni_teller( $soort, $wie ) >= $max;
}

/** Grenzen. Aan te passen met het filter 'deedni_grenzen'. */
function deedni_grens( $naam ) {
	$grenzen = apply_filters(
		'deedni_grenzen',
		array(
			'login_ip'      => array( 20, 15 * MINUTE_IN_SECONDS ),  // mislukte inlogpogingen per IP
			'login_account' => array( 10, 15 * MINUTE_IN_SECONDS ),  // mislukte pogingen per e-mailadres
			'registreren'   => array( 10, HOUR_IN_SECONDS ),         // registratiepogingen per IP
			'wachtwoord'    => array( 5, HOUR_IN_SECONDS ),          // "wachtwoord vergeten" per IP
			'mail'          => array( 3, HOUR_IN_SECONDS ),          // mails over een account per e-mailadres
			'aanbod'        => array( 25, DAY_IN_SECONDS ),          // nieuwe aanbiedingen per werkgever
		)
	);
	return isset( $grenzen[ $naam ] ) ? $grenzen[ $naam ] : array( 10, HOUR_IN_SECONDS );
}

function deedni_grens_bereikt( $naam, $wie ) {
	return deedni_te_vaak( $naam, $wie, deedni_grens( $naam )[0] );
}

function deedni_grens_tel( $naam, $wie ) {
	return deedni_tel( $naam, $wie, deedni_grens( $naam )[1] );
}

/* =========================================================================
 * Inloggen: afremmen en geen verschil tussen "onbekend adres" en "fout wachtwoord"
 * ========================================================================= */

/**
 * Draait na de standaardcontrole van WordPress (prioriteit 20), zodat een blokkade altijd wint.
 * Geldt voor het eigen inlogformulier, wp-login.php en XML-RPC.
 */
add_filter(
	'authenticate',
	function ( $user, $username ) {
		$username = (string) $username;
		if ( '' === $username ) {
			return $user;
		}
		if ( deedni_grens_bereikt( 'login_ip', deedni_ip() ) || deedni_grens_bereikt( 'login_account', $username ) ) {
			return new WP_Error( 'deedni_te_vaak', 'Te veel inlogpogingen. Wacht een kwartier en probeer het dan opnieuw.' );
		}
		if ( $user instanceof WP_User && deedni_onbevestigd( $user->ID ) ) {
			// Pas na het juiste wachtwoord: zo verraadt deze melding niet of een adres bestaat.
			deedni_stuur_bevestiging( $user->ID );
			return new WP_Error( 'deedni_onbevestigd', 'Je hebt je e-mailadres nog niet bevestigd. We hebben de link opnieuw gestuurd. Klik erop en log daarna in.' );
		}
		return $user;
	},
	100,
	2
);

add_action(
	'wp_login_failed',
	function ( $username, $error = null ) {
		if ( $error instanceof WP_Error && array_intersect( $error->get_error_codes(), array( 'deedni_te_vaak', 'deedni_onbevestigd' ) ) ) {
			return;
		}
		deedni_grens_tel( 'login_ip', deedni_ip() );
		deedni_grens_tel( 'login_account', $username );
	},
	10,
	2
);

add_action(
	'wp_login',
	function ( $user_login, $user ) {
		deedni_teller_wissen( 'login_account', $user_login );
		if ( $user instanceof WP_User ) {
			deedni_teller_wissen( 'login_account', $user->user_email );
		}
	},
	10,
	2
);

/** wp-login.php: één algemene melding bij een onbekend adres of een fout wachtwoord. */
add_filter(
	'login_errors',
	function ( $melding ) {
		global $errors;
		if ( $errors instanceof WP_Error && array_intersect( $errors->get_error_codes(), array( 'invalid_username', 'invalid_email', 'incorrect_password', 'invalidcombo' ) ) ) {
			return '<strong>Fout:</strong> dat e-mailadres of wachtwoord klopt niet.';
		}
		return $melding;
	}
);

/* =========================================================================
 * Wachtwoord vergeten: altijd dezelfde reactie, en niet eindeloos mails laten versturen
 * ========================================================================= */

add_action(
	'lostpassword_post',
	function ( $errors ) {
		if ( ! isset( $GLOBALS['pagenow'] ) || 'wp-login.php' !== $GLOBALS['pagenow'] ) {
			return; // Bijvoorbeeld een beheerder die vanuit wp-admin een resetlink stuurt.
		}
		if ( $errors instanceof WP_Error && array_diff( $errors->get_error_codes(), array( 'invalid_email', 'invalidcombo' ) ) ) {
			return; // Bijvoorbeeld een leeg veld: die melding mag blijven.
		}
		$login   = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$user    = $login ? ( is_email( $login ) ? get_user_by( 'email', $login ) : get_user_by( 'login', $login ) ) : false;
		$te_vaak = deedni_grens_bereikt( 'wachtwoord', deedni_ip() ) || ( $user && deedni_grens_bereikt( 'mail', $user->user_email ) );
		deedni_grens_tel( 'wachtwoord', deedni_ip() );
		if ( $user && ! $te_vaak ) {
			deedni_grens_tel( 'mail', $user->user_email );
			return; // WordPress stuurt de mail en stuurt daarna zelf door naar "kijk in je mail".
		}
		// Onbekend adres of te vaak: niets versturen, maar precies dezelfde pagina tonen.
		wp_safe_redirect( site_url( 'wp-login.php?checkemail=confirm', 'login' ) );
		exit;
	}
);

/** Nieuw wachtwoord gekozen: alle oude sessies afmelden, en het e-mailadres is daarmee bewezen. */
add_action(
	'after_password_reset',
	function ( $user ) {
		if ( ! $user instanceof WP_User ) {
			return;
		}
		WP_Session_Tokens::get_instance( $user->ID )->destroy_all();
		if ( deedni_onbevestigd( $user->ID ) ) {
			deedni_account_bevestigd( $user->ID );
		}
	}
);

/* Ook bij een wachtwoordwijziging via het profiel in wp-admin: andere sessies afmelden. */
add_action(
	'profile_update',
	function ( $user_id, $oud, $nieuw = array() ) {
		if ( isset( $nieuw['user_pass'] ) && $oud instanceof WP_User && $nieuw['user_pass'] !== $oud->user_pass && get_current_user_id() === (int) $user_id ) {
			$token = wp_get_session_token();
			if ( $token ) {
				WP_Session_Tokens::get_instance( $user_id )->destroy_others( $token );
			}
		}
	},
	10,
	3
);

/* =========================================================================
 * E-mailadres bevestigen bij een nieuw account
 * ========================================================================= */

function deedni_email_bevestigen_aan() {
	return (bool) get_option( 'deedni_email_bevestigen', 1 );
}

/** Heeft dit account zijn e-mailadres nog niet bevestigd? Oude accounts (van voor deze versie) tellen als bevestigd. */
function deedni_onbevestigd( $user_id ) {
	return (bool) get_user_meta( (int) $user_id, 'deedni_bevestig', true );
}

function deedni_bevestig_url( $user_id, $sleutel ) {
	return add_query_arg(
		array(
			'deedni_bevestig' => (int) $user_id,
			'sleutel'         => rawurlencode( $sleutel ),
		),
		home_url( '/' )
	);
}

/** Een nieuwe bevestigingslink maken en mailen (niet vaker dan de grens 'mail'). */
function deedni_stuur_bevestiging( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user || deedni_grens_bereikt( 'mail', $user->user_email ) ) {
		return false;
	}
	deedni_grens_tel( 'mail', $user->user_email );
	$sleutel = wp_generate_password( 32, false, false );
	$oud     = get_user_meta( $user_id, 'deedni_bevestig', true );
	update_user_meta(
		$user_id,
		'deedni_bevestig',
		array(
			'hash'    => wp_hash( $sleutel ),
			'tot'     => time() + 2 * DAY_IN_SECONDS,
			'gemaakt' => is_array( $oud ) && ! empty( $oud['gemaakt'] ) ? (int) $oud['gemaakt'] : time(),
		)
	);
	// Tekst aan te passen bij Formulieren & mail > Mailteksten. De e-maillog bewaart de link nooit leesbaar.
	return deedni_mail_sjabloon(
		'bevestigen',
		$user->user_email,
		array(
			'naam'  => $user->display_name,
			'email' => $user->user_email,
			'link'  => deedni_bevestig_url( $user_id, $sleutel ),
		),
		array( 'gebruiker' => $user_id )
	);
}

/** Iemand probeert een account te maken met een adres dat al bestaat: mail de eigenaar, verder niets. */
function deedni_mail_bestaand_account( $email ) {
	if ( deedni_grens_bereikt( 'mail', $email ) ) {
		return false;
	}
	deedni_grens_tel( 'mail', $email );
	$user = get_user_by( 'email', $email );
	return deedni_mail_sjabloon(
		'bestaand_account',
		$email,
		array(
			'email' => $email,
			'link'  => wp_lostpassword_url(),
		),
		array( 'gebruiker' => $user ? $user->ID : 0 )
	);
}

/** Het e-mailadres is bewezen: markeren en de mails sturen die anders bij het registreren gingen. */
function deedni_account_bevestigd( $user_id ) {
	delete_user_meta( $user_id, 'deedni_bevestig' );
	if ( deedni_is_werkgever( $user_id ) && get_option( 'deedni_werkgever_goedkeuring', 1 ) ) {
		deedni_mail_admin_new_werkgever( $user_id );
	}
	deedni_mail_welcome( $user_id );
	do_action( 'deedni_account_bevestigd', $user_id );
}

/** De link uit de bevestigingsmail. Logt niet automatisch in, zodat een virusscanner die de link opent niets kan. */
add_action(
	'template_redirect',
	function () {
		if ( empty( $_GET['deedni_bevestig'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$user_id = absint( $_GET['deedni_bevestig'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$sleutel = isset( $_GET['sleutel'] ) ? sanitize_text_field( wp_unslash( $_GET['sleutel'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$data    = get_user_meta( $user_id, 'deedni_bevestig', true );
		$naar    = array( 'redirect_to' => rawurlencode( deedni_page_url( 'profiel' ) ) );
		if ( is_array( $data ) && $sleutel && $data['tot'] > time() && hash_equals( (string) $data['hash'], wp_hash( $sleutel ) ) ) {
			deedni_account_bevestigd( $user_id );
			$naar['deedni_melding'] = 'bevestigd';
		} elseif ( get_userdata( $user_id ) && ! deedni_onbevestigd( $user_id ) ) {
			$naar['deedni_melding'] = 'bevestigd'; // Link al eerder gebruikt (bijvoorbeeld door een virusscanner).
		} else {
			$naar['deedni_melding'] = 'link_verlopen';
		}
		nocache_headers();
		wp_safe_redirect( deedni_page_url( 'inloggen', $naar ) );
		exit;
	},
	2
);

/** Onbevestigde accounts na 7 dagen opruimen, zodat niemand een adres van een ander kan "bezetten". */
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'deedni_opruimen' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'deedni_opruimen' );
		}
	}
);

add_action(
	'deedni_opruimen',
	function () {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		$users = get_users(
			array(
				'role__in' => array( 'deedni_kandidaat', 'deedni_werkgever' ),
				'meta_key' => 'deedni_bevestig', // phpcs:ignore WordPress.DB.SlowDBQuery
				'number'   => 200,
				'fields'   => 'ID',
			)
		);
		foreach ( $users as $user_id ) {
			$data = get_user_meta( $user_id, 'deedni_bevestig', true );
			if ( is_array( $data ) && ! empty( $data['gemaakt'] ) && $data['gemaakt'] < time() - 7 * DAY_IN_SECONDS ) {
				foreach ( array( 'kandidaat', 'bedrijf' ) as $type ) {
					$profiel = deedni_profile_id( $user_id, $type );
					if ( $profiel ) {
						wp_delete_post( $profiel, true );
					}
				}
				wp_delete_user( $user_id );
			}
		}
	}
);

/* =========================================================================
 * Gebruikers niet laten raden
 * ========================================================================= */

/** Auteurspagina's en ?author=1 bestaan niet (ze tonen de gebruikersnaam in de URL). */
add_action(
	'template_redirect',
	function () {
		if ( current_user_can( 'list_users' ) ) {
			return;
		}
		if ( is_author() || isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			remove_action( 'template_redirect', 'redirect_canonical' );
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	},
	0
);

/** REST: de gebruikerslijst alleen voor wie berichten mag bewerken. Ingelogde gebruikers zien wel zichzelf (/users/me). */
add_filter(
	'rest_pre_dispatch',
	function ( $result, $server, $request ) {
		$route = untrailingslashit( $request->get_route() );
		if ( ! preg_match( '#^/wp/v2/users(/|$)#', $route ) || current_user_can( 'edit_posts' ) ) {
			return $result;
		}
		if ( is_user_logged_in() && '/wp/v2/users/me' === $route ) {
			return $result;
		}
		return new WP_Error( 'rest_user_cannot_view', 'Geen toegang.', array( 'status' => rest_authorization_required_code() ) );
	},
	10,
	3
);

/** oEmbed: geen auteursnaam. */
add_filter(
	'oembed_response_data',
	function ( $data ) {
		unset( $data['author_name'], $data['author_url'] );
		return $data;
	}
);

/** Geen sitemap met gebruikers. */
add_filter(
	'wp_sitemaps_add_provider',
	function ( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	},
	10,
	2
);

/* =========================================================================
 * Kandidaten, bedrijven en aanbiedingen alleen als losse pagina (met toegangscontrole)
 * ========================================================================= */

/**
 * ?post_type=kandidaat, /feed/?post_type=kandidaat en zoekresultaten zouden een lijst met codes en de voornaam
 * (als auteur) tonen. Alleen één profiel openen kan, en daar controleert deedni_guard_pages wie het mag zien.
 */
add_filter(
	'request',
	function ( $qv ) {
		if ( is_admin() ) {
			return $qv;
		}
		$types = isset( $qv['post_type'] ) ? (array) $qv['post_type'] : array();
		if ( array_intersect( $types, array( 'kandidaat', 'bedrijf', 'aanbod' ) ) && empty( $qv['name'] ) && empty( $qv['p'] ) && empty( $qv['kandidaat'] ) ) {
			return array( 'error' => '404' ); // Alle andere variabelen weg, anders draait een feed de lijst toch.
		}
		return $qv;
	}
);

/** Bijlagen van kandidaten (cv's) hebben geen eigen pagina en geen doorverwijzing naar het bestand. */
add_action(
	'template_redirect',
	function () {
		if ( ! is_attachment() || deedni_is_beheerder() ) {
			return;
		}
		$ouder = (int) wp_get_post_parent_id( get_queried_object_id() );
		if ( $ouder && in_array( get_post_type( $ouder ), array( 'kandidaat', 'aanbod' ), true ) ) {
			remove_action( 'template_redirect', 'redirect_canonical' );
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	},
	0
);

/**
 * Cv's komen in een eigen map met een willekeurige bestandsnaam, zodat het adres niet te raden is.
 * Downloaden gaat altijd via deedni_cv_url() met toegangscontrole. Zet op de server ook een regel die
 * /wp-content/uploads/deedni-cv/ helemaal afschermt (zie INSTALLATIE.md); voor Apache zetten we zelf
 * een .htaccess neer.
 */
function deedni_cv_map( $dirs ) {
	$dirs['subdir'] = '/deedni-cv';
	$dirs['path']   = $dirs['basedir'] . '/deedni-cv';
	$dirs['url']    = $dirs['baseurl'] . '/deedni-cv';
	if ( wp_mkdir_p( $dirs['path'] ) ) {
		if ( ! file_exists( $dirs['path'] . '/index.php' ) ) {
			file_put_contents( $dirs['path'] . '/index.php', "<?php\n// Stil.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		if ( ! file_exists( $dirs['path'] . '/.htaccess' ) ) {
			file_put_contents( $dirs['path'] . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
	return $dirs;
}

function deedni_cv_bestandsnaam( $file ) {
	$ext          = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
	$file['name'] = 'cv-' . wp_generate_password( 24, false, false ) . ( $ext ? '.' . $ext : '' );
	return $file;
}

/* =========================================================================
 * Headers, XML-RPC en versienummer
 * ========================================================================= */

/**
 * Beveiligingsheaders op de site (wp-admin en wp-login.php regelt WordPress zelf).
 * Liever via de server? Filter 'deedni_beveiligingsheaders' en geef een lege lijst terug.
 */
add_action(
	'send_headers',
	function () {
		if ( headers_sent() ) {
			return;
		}
		$headers = array(
			'X-Frame-Options'         => 'SAMEORIGIN',
			'Content-Security-Policy' => "frame-ancestors 'self'; base-uri 'self'; object-src 'none'; form-action 'self'",
			'X-Content-Type-Options'  => 'nosniff',
			'Referrer-Policy'         => 'strict-origin-when-cross-origin',
			'Permissions-Policy'      => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
		);
		if ( is_ssl() ) {
			$headers['Strict-Transport-Security'] = 'max-age=31536000';
		}
		foreach ( (array) apply_filters( 'deedni_beveiligingsheaders', $headers ) as $naam => $waarde ) {
			header( $naam . ': ' . $waarde );
		}
	}
);

/** Persoonlijke pagina's niet in de cache van de browser of een proxy. */
add_action(
	'template_redirect',
	function () {
		if ( is_user_logged_in() && ( is_singular( 'kandidaat' ) || deedni_is_page( 'dashboard' ) || deedni_is_page( 'profiel' ) || deedni_is_page( 'zoeken' ) ) ) {
			nocache_headers();
		}
	},
	20
);

add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
add_filter(
	'wp_headers',
	function ( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
);
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );
