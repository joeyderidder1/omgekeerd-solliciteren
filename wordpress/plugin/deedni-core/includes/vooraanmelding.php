<?php
/**
 * Vooraanmelding: een tijdelijk aanmeldformulier zonder account (werkzoekende of werkgever, naam, e-mail,
 * optioneel bedrijf en functie) en een overzicht in wp-admin onder "Aanmeldingen" met aantallen,
 * "uitgenodigd ja/nee", verwijderen en export naar CSV.
 *
 * Gegevens staan in een eigen tabel ({prefix}deedni_aanmeldingen). Er wordt geen IP-adres bewaard.
 * Bij elke aanmelding bewaren we wanneer en met welke tekst iemand toestemming gaf.
 */

defined( 'ABSPATH' ) || exit;

define( 'DEEDNI_AANMELDINGEN_DB', '1' );

function deedni_aanmeldingen_tabel() {
	global $wpdb;
	return $wpdb->prefix . 'deedni_aanmeldingen';
}

/** Tabel aanmaken of bijwerken. Draait bij activeren en na een update van de plugin (zip vervangen). */
function deedni_aanmeldingen_installeer() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$tabel = deedni_aanmeldingen_tabel();
	$cs    = $wpdb->get_charset_collate();
	dbDelta(
		"CREATE TABLE {$tabel} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			aangemeld datetime NOT NULL,
			doelgroep varchar(20) NOT NULL default 'kandidaat',
			naam varchar(190) NOT NULL default '',
			email varchar(190) NOT NULL default '',
			bedrijf varchar(190) NOT NULL default '',
			functie varchar(190) NOT NULL default '',
			toestemming datetime NOT NULL,
			toestemming_tekst text NOT NULL,
			uitgenodigd tinyint(1) NOT NULL default 0,
			pagina varchar(255) NOT NULL default '',
			PRIMARY KEY  (id),
			KEY email (email),
			KEY doelgroep (doelgroep)
		) {$cs};"
	);
	update_option( 'deedni_aanmeldingen_db', DEEDNI_AANMELDINGEN_DB );
}

/** Na het vervangen van de zip draait de activatie niet: dan hier de tabel en de nieuwe pagina klaarzetten. */
add_action(
	'init',
	function () {
		if ( DEEDNI_AANMELDINGEN_DB !== get_option( 'deedni_aanmeldingen_db' ) ) {
			deedni_aanmeldingen_installeer();
			deedni_create_pages();
		}
	},
	30
);

function deedni_doelgroepen() {
	return array(
		'kandidaat' => 'Werkzoekende',
		'werkgever' => 'Werkgever',
	);
}

/** Aanmeldingen ophalen. $doelgroep: '', 'kandidaat' of 'werkgever'. */
function deedni_aanmeldingen( $doelgroep = '', $zoek = '' ) {
	global $wpdb;
	$tabel = deedni_aanmeldingen_tabel();
	$waar  = array( '1=1' );
	$args  = array();
	if ( isset( deedni_doelgroepen()[ $doelgroep ] ) ) {
		$waar[] = 'doelgroep = %s';
		$args[] = $doelgroep;
	}
	if ( '' !== $zoek ) {
		$like   = '%' . $wpdb->esc_like( $zoek ) . '%';
		$waar[] = '(naam LIKE %s OR email LIKE %s OR bedrijf LIKE %s OR functie LIKE %s)';
		array_push( $args, $like, $like, $like, $like );
	}
	$sql = "SELECT * FROM {$tabel} WHERE " . implode( ' AND ', $waar ) . ' ORDER BY aangemeld DESC, id DESC';
	return $wpdb->get_results( $args ? $wpdb->prepare( $sql, $args ) : $sql, ARRAY_A ); // phpcs:ignore WordPress.DB
}

/** Aantallen per doelgroep, plus totaal, uitgenodigd en de laatste 7 dagen. */
function deedni_aanmeldingen_tellers() {
	global $wpdb;
	$tabel = deedni_aanmeldingen_tabel();
	$t     = array( 'totaal' => 0, 'kandidaat' => 0, 'werkgever' => 0, 'uitgenodigd' => 0, 'week' => 0 );
	foreach ( (array) $wpdb->get_results( "SELECT doelgroep, COUNT(*) AS n FROM {$tabel} GROUP BY doelgroep", ARRAY_A ) as $r ) { // phpcs:ignore WordPress.DB
		if ( isset( $t[ $r['doelgroep'] ] ) ) {
			$t[ $r['doelgroep'] ] = (int) $r['n'];
		}
		$t['totaal'] += (int) $r['n'];
	}
	$t['uitgenodigd'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tabel} WHERE uitgenodigd = 1" ); // phpcs:ignore WordPress.DB
	$t['week']        = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabel} WHERE aangemeld >= %s", gmdate( 'Y-m-d H:i:s', time() - WEEK_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB
	return $t;
}

/* =========================================================================
 * Formulier op de site (blok "Vooraanmelding")
 * ========================================================================= */

deedni_blok(
	'vooraanmelding',
	array(
		'titel'        => 'Vooraanmelding (zonder account)',
		'beschrijving' => 'Een kort aanmeldformulier voor de testfase: werkzoekende of werkgever, naam, e-mail en optioneel bedrijf of functie. Je ziet de aanmeldingen in wp-admin onder Aanmeldingen.',
		'icoon'        => 'clipboard',
		'stijl'        => 'deedni-home',
		'achtergrond'  => 'zacht',
		'anker'        => 'vooraanmelden',
		'uitleg'       => 'Aanmeldingen vind je in wp-admin onder Aanmeldingen. Daar zie je de aantallen, vink je aan wie je hebt uitgenodigd en download je alles als CSV.',
		'velden'       => array(
			'kop'             => array( 'type' => 'text', 'label' => 'Kop', 'groep' => 'Teksten', 'standaard' => 'Doe mee met de *test*' ),
			'inleiding'       => array( 'type' => 'textarea', 'label' => 'Inleiding', 'groep' => 'Teksten', 'standaard' => 'deedni is bijna klaar. Meld je aan, dan krijg je als een van de eersten een uitnodiging om het uit te proberen. Je maakt nu nog geen account.' ),
			'punten'          => array(
				'type'      => 'lijst',
				'label'     => 'Punten naast het formulier',
				'groep'     => 'Teksten',
				'velden'    => array( 'tekst' => array( 'type' => 'text', 'label' => 'Tekst' ) ),
				'standaard' => array(
					array( 'tekst' => 'Je krijgt alleen een mail als de test begint.' ),
					array( 'tekst' => 'Kosteloos, ook voor bedrijven tijdens de test.' ),
					array( 'tekst' => 'Afmelden kan altijd, we verwijderen dan je gegevens.' ),
				),
			),
			'label_k'         => array( 'type' => 'text', 'label' => 'Keuze 1', 'groep' => 'Formulier', 'standaard' => 'Ik zoek werk' ),
			'label_w'         => array( 'type' => 'text', 'label' => 'Keuze 2', 'groep' => 'Formulier', 'standaard' => 'Ik zoek personeel' ),
			'start'           => array(
				'type'      => 'keuze',
				'label'     => 'Welke keuze staat al aan?',
				'groep'     => 'Formulier',
				'opties'    => array( 'kandidaat' => 'Keuze 1 (werkzoekende)', 'werkgever' => 'Keuze 2 (werkgever)' ),
				'standaard' => 'kandidaat',
				'help'      => 'Een link met ?voor=werkgever of ?voor=kandidaat zet de keuze ook goed.',
			),
			'bedrijf_tonen'   => array( 'type' => 'toggle', 'label' => 'Vraag naar de bedrijfsnaam (werkgevers)', 'groep' => 'Formulier', 'standaard' => true ),
			'functie_tonen'   => array( 'type' => 'toggle', 'label' => 'Vraag naar functie of vak', 'groep' => 'Formulier', 'standaard' => true ),
			'knop'            => array( 'type' => 'text', 'label' => 'Tekst op de knop', 'groep' => 'Formulier', 'standaard' => 'Aanmelden' ),
			'toestemming'     => array( 'type' => 'textarea', 'label' => 'Tekst bij het toestemmingsvakje', 'groep' => 'Formulier', 'standaard' => 'Ik geef deedni toestemming om mijn naam en e-mailadres te bewaren en mij te mailen over de test. Je gegevens worden niet gedeeld en na de test verwijderd. Lees de {privacyverklaring}.', 'help' => '{privacyverklaring} wordt een link naar je privacyverklaring (Instellingen > Privacy).' ),
			'bedankt_kop'     => array( 'type' => 'text', 'label' => 'Kop na aanmelden', 'groep' => 'Na het aanmelden', 'standaard' => 'Je staat op de lijst' ),
			'bedankt_tekst'   => array( 'type' => 'textarea', 'label' => 'Tekst na aanmelden', 'groep' => 'Na het aanmelden', 'standaard' => 'Bedankt! We mailen je zodra de test begint. Wil je je toch afmelden? Stuur dan een mail, dan verwijderen we je gegevens.' ),
		),
		'render'       => 'deedni_render_vooraanmelding',
	)
);

function deedni_privacy_link( $tekst ) {
	$url  = get_privacy_policy_url();
	$link = $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">privacyverklaring</a>' : 'privacyverklaring';
	return str_replace( '{privacyverklaring}', $link, esc_html( $tekst ) );
}

function deedni_render_vooraanmelding( $a ) {
	$flash   = deedni_flash( 'vooraanmelding' );
	$klaar   = ! $flash && ! empty( $_GET['aangemeld'] ); // phpcs:ignore WordPress.Security.NonceVerification
	$err     = $flash && isset( $flash['errors'] ) ? $flash['errors'] : array();
	$voor    = isset( $_GET['voor'] ) ? sanitize_key( wp_unslash( $_GET['voor'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$data    = $flash && isset( $flash['data'] ) ? $flash['data'] : array(
		'doelgroep' => isset( deedni_doelgroepen()[ $voor ] ) ? $voor : $a['start'],
		'naam'      => '',
		'email'     => '',
		'bedrijf'   => '',
		'functie'   => '',
		'akkoord'   => false,
	);
	$wg      = 'werkgever' === $data['doelgroep'];
	$fout    = function ( $key ) use ( $err ) {
		return isset( $err[ $key ] ) ? '<span class="field-error" id="va-fout-' . esc_attr( $key ) . '">' . esc_html( $err[ $key ] ) . '</span>' : '';
	};
	$invalid = function ( $key ) use ( $err ) {
		return isset( $err[ $key ] ) ? ' aria-invalid="true" aria-describedby="va-fout-' . esc_attr( $key ) . '"' : '';
	};
	$kop_id  = deedni_uid( 'vooraanmelding-kop' );

	$h  = '<section class="section deedni-vooraanmelding' . deedni_achtergrond_klasse( $a ) . '"' . deedni_anker_attr( $a ) . ' aria-labelledby="' . esc_attr( $kop_id ) . '"><div class="wrap va-grid">';
	$h .= '<div class="va-tekst stack">' . deedni_sec_head( $kop_id, $a['kop'], $a['inleiding'], 'h2', false );
	$punten = array_filter( (array) $a['punten'], function ( $p ) { return ! empty( $p['tekst'] ); } );
	if ( $punten ) {
		$h .= '<ul class="va-punten">';
		foreach ( $punten as $p ) {
			$h .= '<li>' . deedni_icon( 'check', 20 ) . '<span>' . esc_html( $p['tekst'] ) . '</span></li>';
		}
		$h .= '</ul>';
	}
	$h .= '</div>';

	if ( $klaar ) {
		$h .= '<div class="card va-card va-klaar" role="status" tabindex="-1" data-focus><span class="va-vink" aria-hidden="true">' . deedni_icon( 'check', 28 ) . '</span><h3>' . esc_html( $a['bedankt_kop'] ) . '</h3><p>' . esc_html( $a['bedankt_tekst'] ) . '</p></div>';
		return $h . deedni_vooraanmelding_script() . '</div></section>';
	}

	$h .= '<form class="card va-card" method="post" action="' . esc_url( remove_query_arg( array( 'aangemeld', 'deedni_melding' ) ) . '#vooraanmelden' ) . '" novalidate' . ( $err ? ' data-focus' : '' ) . '>';
	$h .= '<input type="hidden" name="deedni_action" value="vooraanmelden">' . deedni_nonce_field( 'deedni_vooraanmelden' );
	if ( isset( $err['form'] ) ) {
		$h .= deedni_notice( esc_html( $err['form'] ), 'error' );
	} elseif ( $err ) {
		$h .= deedni_notice( 'Er ontbreekt nog iets. Kijk de velden met een rode tekst na.', 'error' );
	}
	$h .= '<fieldset class="stack-s"><legend>Wat zoek je?</legend><div class="rolkeuze">';
	$h .= '<label><input type="radio" name="doelgroep" value="kandidaat"' . checked( ! $wg, true, false ) . '>' . esc_html( $a['label_k'] ) . '</label>';
	$h .= '<label><input type="radio" name="doelgroep" value="werkgever"' . checked( $wg, true, false ) . '>' . esc_html( $a['label_w'] ) . '</label></div></fieldset>';
	$h .= '<div class="field"><label for="va-naam">Je naam</label><input id="va-naam" name="naam" autocomplete="name" required maxlength="190" value="' . esc_attr( $data['naam'] ) . '"' . $invalid( 'naam' ) . '>' . $fout( 'naam' ) . '</div>';
	$h .= '<div class="field"><label for="va-mail">E-mailadres</label><input id="va-mail" name="email" type="email" autocomplete="email" required maxlength="190" value="' . esc_attr( $data['email'] ) . '"' . $invalid( 'email' ) . '>' . $fout( 'email' ) . '</div>';
	if ( $a['bedrijf_tonen'] ) {
		$h .= '<div class="field" data-va-voor="werkgever"><label for="va-bedrijf">Bedrijf <span class="va-opt">(optioneel)</span></label><input id="va-bedrijf" name="bedrijf" autocomplete="organization" maxlength="190" value="' . esc_attr( $data['bedrijf'] ) . '"></div>';
	}
	if ( $a['functie_tonen'] ) {
		$h .= '<div class="field"><label for="va-functie"><span data-va-label="kandidaat"' . ( $wg ? ' hidden' : '' ) . '>Je vak</span><span data-va-label="werkgever"' . ( $wg ? '' : ' hidden' ) . '>Je functie</span> <span class="va-opt">(optioneel)</span></label><input id="va-functie" name="functie" maxlength="190" value="' . esc_attr( $data['functie'] ) . '" aria-describedby="va-functie-hint"><span class="hint" id="va-functie-hint"><span data-va-label="kandidaat"' . ( $wg ? ' hidden' : '' ) . '>Bijvoorbeeld hovenier, accountant of webdesigner.</span><span data-va-label="werkgever"' . ( $wg ? '' : ' hidden' ) . '>Bijvoorbeeld HR-manager of eigenaar.</span></span></div>';
	}
	$h .= '<label class="check"><input type="checkbox" name="akkoord" value="1" required' . checked( ! empty( $data['akkoord'] ), true, false ) . $invalid( 'akkoord' ) . '><span>' . deedni_privacy_link( $a['toestemming'] ) . '</span></label>' . $fout( 'akkoord' );
	$h .= '<p class="hp" aria-hidden="true"><label>Website <input type="text" name="website_url" tabindex="-1" autocomplete="off"></label></p>';
	$h .= '<div><button class="btn btn--primary" type="submit">' . esc_html( $a['knop'] ) . '</button></div>';
	$h .= '</form>';
	$h .= deedni_vooraanmelding_script();
	return $h . '</div></section>';
}

/** Velden per keuze tonen en na het versturen naar de melding springen. Zonder JavaScript staat alles er gewoon. */
function deedni_vooraanmelding_script() {
	return '<script>(function(){var g=document.currentScript.parentNode,f=g.querySelector("form");'
		. 'function s(){if(!f)return;var c=f.querySelector(\'input[name="doelgroep"]:checked\'),v=c?c.value:"kandidaat";'
		. 'f.querySelectorAll("[data-va-voor]").forEach(function(e){e.hidden=e.getAttribute("data-va-voor")!==v;});'
		. 'f.querySelectorAll("[data-va-label]").forEach(function(e){e.hidden=e.getAttribute("data-va-label")!==v;});}'
		. 'if(f){f.addEventListener("change",s);s();}'
		. 'var d=g.querySelector("[data-focus]");if(d){var e=d.querySelector("[aria-invalid]")||d;setTimeout(function(){e.focus({preventScroll:true});d.scrollIntoView({block:"center"});},50);}})();</script>';
}

/** Verwerken van het formulier (via deedni_handle_forms). */
function deedni_handle_vooraanmelden() {
	if ( ! deedni_nonce_ok( 'deedni_vooraanmelden' ) ) {
		deedni_fout( 'vooraanmelding', array( 'form' => 'Je sessie is verlopen. Probeer het opnieuw.' ) );
		return;
	}
	$terug = deedni_terug_url( home_url( '/' ) );
	// Honeypot: dit veld is onzichtbaar voor mensen. Doe alsof het gelukt is.
	if ( deedni_post( 'website_url' ) ) {
		wp_safe_redirect( add_query_arg( 'aangemeld', 1, $terug ) . '#vooraanmelden' );
		exit;
	}

	$data = array(
		'doelgroep' => 'werkgever' === deedni_post( 'doelgroep' ) ? 'werkgever' : 'kandidaat',
		'naam'      => mb_substr( deedni_post( 'naam' ), 0, 190 ),
		'email'     => mb_substr( deedni_post( 'email', 'email' ), 0, 190 ),
		'bedrijf'   => mb_substr( deedni_post( 'bedrijf' ), 0, 190 ),
		'functie'   => mb_substr( deedni_post( 'functie' ), 0, 190 ),
		'akkoord'   => ! empty( $_POST['akkoord'] ), // phpcs:ignore WordPress.Security.NonceVerification
	);
	if ( 'kandidaat' === $data['doelgroep'] ) {
		$data['bedrijf'] = '';
	}

	$errors = array();
	if ( '' === $data['naam'] ) {
		$errors['naam'] = 'Vul je naam in.';
	}
	if ( ! is_email( $data['email'] ) ) {
		$errors['email'] = 'Dit e-mailadres klopt niet. Bijvoorbeeld: naam@voorbeeld.nl';
	}
	if ( ! $data['akkoord'] ) {
		$errors['akkoord'] = 'Zet een vinkje als je toestemming geeft. Zonder toestemming kunnen we je niet uitnodigen.';
	}

	// Niet te vaak achter elkaar vanaf hetzelfde adres (we bewaren alleen een hash, een uur lang).
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$sleut = 'deedni_va_' . md5( wp_salt() . $ip );
	$keer  = (int) get_transient( $sleut );
	if ( $keer >= 10 ) {
		$errors['form'] = 'Er zijn te veel aanmeldingen vanaf dit adres. Probeer het over een uur opnieuw.';
	}

	if ( $errors ) {
		deedni_fout( 'vooraanmelding', $errors, $data );
		return;
	}
	set_transient( $sleut, $keer + 1, HOUR_IN_SECONDS );

	global $wpdb;
	$tabel    = deedni_aanmeldingen_tabel();
	$nu       = current_time( 'mysql', true );
	$velden   = array(
		'doelgroep'         => $data['doelgroep'],
		'naam'              => $data['naam'],
		'bedrijf'           => $data['bedrijf'],
		'functie'           => $data['functie'],
		'toestemming'       => $nu,
		'toestemming_tekst' => deedni_vooraanmelding_toestemming_tekst(),
		'pagina'            => mb_substr( wp_make_link_relative( $terug ), 0, 255 ),
	);
	$bestaand = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tabel} WHERE email = %s LIMIT 1", $data['email'] ) ); // phpcs:ignore WordPress.DB
	if ( $bestaand ) {
		// Dubbele aanmelding: gegevens bijwerken, zonder te verraden dat het adres al bekend was.
		$wpdb->update( $tabel, $velden, array( 'id' => (int) $bestaand ) ); // phpcs:ignore WordPress.DB
	} else {
		$wpdb->insert( $tabel, array_merge( $velden, array( 'aangemeld' => $nu, 'email' => $data['email'] ) ) ); // phpcs:ignore WordPress.DB
		if ( get_option( 'deedni_aanmelding_mail', 0 ) ) {
			deedni_mail_sjabloon(
				'beheer_aanmelding',
				get_option( 'admin_email' ),
				array(
					'naam'      => $data['naam'],
					'email'     => $data['email'],
					'doelgroep' => strtolower( deedni_doelgroepen()[ $data['doelgroep'] ] ),
					'link'      => admin_url( 'admin.php?page=deedni-aanmeldingen' ),
				)
			);
		}
	}
	do_action( 'deedni_vooraanmelding', $data );

	if ( deedni_is_ajax() ) {
		wp_send_json( array( 'ok' => true ) );
	}
	wp_safe_redirect( add_query_arg( 'aangemeld', 1, $terug ) . '#vooraanmelden' );
	exit;
}

/**
 * De toestemmingstekst die de bezoeker zag. We lezen die van het blok op de pagina waar het formulier
 * stond, zodat je later kunt aantonen waar iemand precies mee akkoord ging.
 */
function deedni_vooraanmelding_toestemming_tekst() {
	$standaard = deedni_blok_defs()['vooraanmelding']['velden']['toestemming']['standaard'];
	$pagina_id = url_to_postid( deedni_terug_url( home_url( '/' ) ) );
	if ( ! $pagina_id && 'page' === get_option( 'show_on_front' ) ) {
		$pagina_id = (int) get_option( 'page_on_front' );
	}
	if ( $pagina_id ) {
		foreach ( parse_blocks( (string) get_post_field( 'post_content', $pagina_id ) ) as $blok ) {
			if ( 'deedni/vooraanmelding' === $blok['blockName'] && ! empty( $blok['attrs']['toestemming'] ) ) {
				$standaard = $blok['attrs']['toestemming'];
				break;
			}
		}
	}
	return str_replace( '{privacyverklaring}', 'privacyverklaring', $standaard );
}

/* =========================================================================
 * wp-admin: Aanmeldingen
 * ========================================================================= */

add_action(
	'admin_menu',
	function () {
		$t     = deedni_aanmeldingen_tellers();
		$badge = $t['totaal'] - $t['uitgenodigd'];
		add_menu_page(
			'Aanmeldingen',
			'Aanmeldingen' . ( $badge > 0 ? ' <span class="awaiting-mod count-' . (int) $badge . '"><span class="pending-count">' . (int) $badge . '</span></span>' : '' ),
			'manage_options',
			'deedni-aanmeldingen',
			'deedni_aanmeldingen_pagina',
			'dashicons-clipboard',
			26
		);
	}
);

/** Acties: uitgenodigd aan/uit, verwijderen, alles verwijderen, export, mail-instelling. */
add_action(
	'admin_post_deedni_aanmeldingen',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		check_admin_referer( 'deedni_aanmeldingen' );
		global $wpdb;
		$tabel = deedni_aanmeldingen_tabel();
		$wat   = isset( $_REQUEST['wat'] ) ? sanitize_key( wp_unslash( $_REQUEST['wat'] ) ) : '';
		$id    = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0;
		$ids   = isset( $_POST['ids'] ) ? array_filter( array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) ) : array();
		$terug = array( 'page' => 'deedni-aanmeldingen' );
		foreach ( array( 'doelgroep', 's' ) as $k ) {
			if ( ! empty( $_REQUEST[ $k ] ) ) {
				$terug[ $k ] = sanitize_text_field( wp_unslash( $_REQUEST[ $k ] ) );
			}
		}

		if ( 'export' === $wat ) {
			deedni_aanmeldingen_export( isset( $terug['doelgroep'] ) ? $terug['doelgroep'] : '', isset( $terug['s'] ) ? $terug['s'] : '' );
		}

		if ( 'bulk' === $wat ) {
			$bulk = isset( $_POST['bulk'] ) ? sanitize_key( wp_unslash( $_POST['bulk'] ) ) : '';
			$wat  = array( 'ja' => 'uitgenodigd', 'nee' => 'niet_uitgenodigd', 'weg' => 'verwijderen' )[ $bulk ] ?? '';
		} elseif ( $id ) {
			$ids = array( $id );
		}

		$klaar = '';
		if ( $ids && in_array( $wat, array( 'uitgenodigd', 'niet_uitgenodigd' ), true ) ) {
			foreach ( $ids as $i ) {
				$wpdb->update( $tabel, array( 'uitgenodigd' => 'uitgenodigd' === $wat ? 1 : 0 ), array( 'id' => $i ) ); // phpcs:ignore WordPress.DB
			}
			$klaar = 'bijgewerkt';
		} elseif ( $ids && 'verwijderen' === $wat ) {
			foreach ( $ids as $i ) {
				$wpdb->delete( $tabel, array( 'id' => $i ) ); // phpcs:ignore WordPress.DB
			}
			$klaar = 'verwijderd';
		} elseif ( 'alles_verwijderen' === $wat && isset( $_POST['zeker'] ) && 'VERWIJDER' === sanitize_text_field( wp_unslash( $_POST['zeker'] ) ) ) {
			$wpdb->query( "DELETE FROM {$tabel}" ); // phpcs:ignore WordPress.DB
			$klaar = 'alles_weg';
			unset( $terug['doelgroep'], $terug['s'] );
		} elseif ( 'alles_verwijderen' === $wat ) {
			$klaar = 'niet_zeker';
		} elseif ( 'mail' === $wat ) {
			update_option( 'deedni_aanmelding_mail', empty( $_POST['aan'] ) ? 0 : 1 );
			$klaar = 'opgeslagen';
		}
		$terug['klaar'] = $klaar;
		wp_safe_redirect( add_query_arg( $terug, admin_url( 'admin.php' ) ) );
		exit;
	}
);

/** CSV met puntkomma's en een BOM, zodat Excel (Nederlandse instellingen) het meteen goed opent. */
function deedni_aanmeldingen_export( $doelgroep = '', $zoek = '' ) {
	$rijen = deedni_aanmeldingen( $doelgroep, $zoek );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="aanmeldingen-' . ( $doelgroep ? $doelgroep . '-' : '' ) . wp_date( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fputcsv( $out, array( 'Aangemeld', 'Doelgroep', 'Naam', 'E-mail', 'Bedrijf', 'Functie of vak', 'Uitgenodigd', 'Toestemming gegeven', 'Toestemmingstekst', 'Pagina' ), ';' );
	foreach ( $rijen as $r ) {
		$rij = array(
			wp_date( 'Y-m-d H:i', strtotime( $r['aangemeld'] . ' UTC' ) ),
			deedni_doelgroepen()[ $r['doelgroep'] ] ?? $r['doelgroep'],
			$r['naam'],
			$r['email'],
			$r['bedrijf'],
			$r['functie'],
			$r['uitgenodigd'] ? 'ja' : 'nee',
			wp_date( 'Y-m-d H:i', strtotime( $r['toestemming'] . ' UTC' ) ),
			$r['toestemming_tekst'],
			$r['pagina'],
		);
		// Geen formules laten uitvoeren in Excel (CSV-injectie).
		$rij = array_map(
			function ( $v ) {
				return preg_match( '/^[=+\-@\t\r]/', (string) $v ) ? "'" . $v : $v;
			},
			$rij
		);
		fputcsv( $out, $rij, ';' );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}

function deedni_aanmeldingen_pagina() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification
	$doelgroep = isset( $_GET['doelgroep'] ) && isset( deedni_doelgroepen()[ $_GET['doelgroep'] ] ) ? sanitize_key( $_GET['doelgroep'] ) : '';
	$zoek      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$klaar     = isset( $_GET['klaar'] ) ? sanitize_key( $_GET['klaar'] ) : '';
	// phpcs:enable
	$t      = deedni_aanmeldingen_tellers();
	$rijen  = deedni_aanmeldingen( $doelgroep, $zoek );
	$post   = admin_url( 'admin-post.php' );
	$nonce  = wp_create_nonce( 'deedni_aanmeldingen' );
	$basis  = admin_url( 'admin.php?page=deedni-aanmeldingen' );
	$extra  = array_filter( array( 'doelgroep' => $doelgroep, 's' => $zoek ) );
	$actie  = function ( $wat, $id = 0 ) use ( $post, $nonce, $extra ) {
		return add_query_arg( array_merge( array( 'action' => 'deedni_aanmeldingen', 'wat' => $wat, 'id' => $id, '_wpnonce' => $nonce ), $extra ), $post );
	};
	$meldingen = array(
		'bijgewerkt' => 'Bijgewerkt.',
		'verwijderd' => 'Verwijderd.',
		'alles_weg'  => 'Alle aanmeldingen zijn verwijderd.',
		'niet_zeker' => 'Niets verwijderd. Typ VERWIJDER in het vak om alles te verwijderen.',
		'opgeslagen' => 'Opgeslagen.',
	);
	$pagina_met_blok = deedni_page_id( 'vooraanmelden' );
	?>
	<div class="wrap deedni-va">
		<h1 class="wp-heading-inline">Aanmeldingen</h1>
		<a class="page-title-action" href="<?php echo esc_url( $actie( 'export' ) ); ?>">Download als CSV<?php echo $doelgroep ? ' (' . esc_html( strtolower( deedni_doelgroepen()[ $doelgroep ] ) ) . 's)' : ''; ?></a>
		<hr class="wp-header-end">
		<?php if ( isset( $meldingen[ $klaar ] ) ) : ?>
			<div class="notice <?php echo 'niet_zeker' === $klaar ? 'notice-warning' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html( $meldingen[ $klaar ] ); ?></p></div>
		<?php endif; ?>
		<p class="deedni-va-intro">Mensen die zich hebben aangemeld via het formulier <em>Vooraanmelding</em><?php if ( $pagina_met_blok && get_post_status( $pagina_met_blok ) ) : ?> (op de pagina <a href="<?php echo esc_url( get_permalink( $pagina_met_blok ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $pagina_met_blok ) ); ?></a>)<?php endif; ?>. Zij hebben nog geen account. Vink aan wie je hebt uitgenodigd, dan zie je in één oogopslag wie nog op een uitnodiging wacht.</p>

		<div class="deedni-va-tegels">
			<div class="deedni-va-tegel"><b><?php echo (int) $t['totaal']; ?></b><span>aanmeldingen</span></div>
			<div class="deedni-va-tegel"><b><?php echo (int) $t['kandidaat']; ?></b><span>werkzoekenden</span></div>
			<div class="deedni-va-tegel"><b><?php echo (int) $t['werkgever']; ?></b><span>werkgevers</span></div>
			<div class="deedni-va-tegel"><b><?php echo (int) $t['week']; ?></b><span>in de laatste 7 dagen</span></div>
			<div class="deedni-va-tegel"><b><?php echo (int) $t['uitgenodigd']; ?> / <?php echo (int) $t['totaal']; ?></b><span>uitgenodigd</span></div>
		</div>

		<ul class="subsubsub">
			<?php
			$tabs = array( '' => array( 'Alle', $t['totaal'] ), 'kandidaat' => array( 'Werkzoekenden', $t['kandidaat'] ), 'werkgever' => array( 'Werkgevers', $t['werkgever'] ) );
			$i    = 0;
			foreach ( $tabs as $k => $tab ) :
				?>
				<li><a href="<?php echo esc_url( $k ? add_query_arg( 'doelgroep', $k, $basis ) : $basis ); ?>"<?php echo $k === $doelgroep ? ' class="current" aria-current="page"' : ''; ?>><?php echo esc_html( $tab[0] ); ?> <span class="count">(<?php echo (int) $tab[1]; ?>)</span></a><?php echo ++$i < count( $tabs ) ? ' |' : ''; ?></li>
			<?php endforeach; ?>
		</ul>
		<form method="get" class="search-box">
			<input type="hidden" name="page" value="deedni-aanmeldingen">
			<?php if ( $doelgroep ) : ?><input type="hidden" name="doelgroep" value="<?php echo esc_attr( $doelgroep ); ?>"><?php endif; ?>
			<label class="screen-reader-text" for="deedni-va-zoek">Zoek in aanmeldingen</label>
			<input type="search" id="deedni-va-zoek" name="s" value="<?php echo esc_attr( $zoek ); ?>" placeholder="Naam, e-mail of bedrijf">
			<input type="submit" class="button" value="Zoeken">
		</form>

		<form method="post" action="<?php echo esc_url( $post ); ?>">
			<input type="hidden" name="action" value="deedni_aanmeldingen">
			<input type="hidden" name="wat" value="bulk">
			<?php foreach ( $extra as $k => $v ) : ?><input type="hidden" name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $v ); ?>"><?php endforeach; ?>
			<?php wp_nonce_field( 'deedni_aanmeldingen' ); ?>
			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<label for="deedni-va-bulk" class="screen-reader-text">Actie voor de aangevinkte aanmeldingen</label>
					<select name="bulk" id="deedni-va-bulk">
						<option value="">Actie voor aangevinkte rijen</option>
						<option value="ja">Uitgenodigd: ja</option>
						<option value="nee">Uitgenodigd: nee</option>
						<option value="weg">Verwijderen</option>
					</select>
					<input type="submit" class="button action" value="Toepassen">
				</div>
				<div class="tablenav-pages one-page"><span class="displaying-num"><?php echo (int) count( $rijen ); ?> <?php echo 1 === count( $rijen ) ? 'aanmelding' : 'aanmeldingen'; ?></span></div>
			</div>
			<table class="wp-list-table widefat fixed striped deedni-va-tabel">
				<thead>
					<tr>
						<td class="manage-column column-cb check-column"><label class="screen-reader-text" for="deedni-va-alles">Alles selecteren</label><input id="deedni-va-alles" type="checkbox"></td>
						<th scope="col" class="col-datum">Aangemeld</th>
						<th scope="col">Naam</th>
						<th scope="col">E-mail</th>
						<th scope="col" class="col-wie">Doelgroep</th>
						<th scope="col">Bedrijf / functie</th>
						<th scope="col" class="col-uit">Uitgenodigd</th>
						<th scope="col" class="col-acties"><span class="screen-reader-text">Acties</span></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( ! $rijen ) : ?>
					<tr class="no-items"><td colspan="8"><?php echo $zoek || $doelgroep ? 'Geen aanmeldingen gevonden.' : 'Nog geen aanmeldingen. Zet het blok <strong>Vooraanmelding (zonder account)</strong> op een pagina en deel de link.'; // phpcs:ignore WordPress.Security.EscapeOutput ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $rijen as $r ) : ?>
					<tr>
						<th scope="row" class="check-column"><input type="checkbox" name="ids[]" value="<?php echo (int) $r['id']; ?>" aria-label="<?php echo esc_attr( 'Selecteer ' . $r['naam'] ); ?>"></th>
						<td class="col-datum"><?php echo esc_html( wp_date( 'j M Y, H:i', strtotime( $r['aangemeld'] . ' UTC' ) ) ); ?></td>
						<td><strong><?php echo esc_html( $r['naam'] ); ?></strong></td>
						<td><a href="mailto:<?php echo esc_attr( $r['email'] ); ?>"><?php echo esc_html( $r['email'] ); ?></a></td>
						<td class="col-wie"><span class="deedni-va-label deedni-va-label--<?php echo esc_attr( $r['doelgroep'] ); ?>"><?php echo esc_html( deedni_doelgroepen()[ $r['doelgroep'] ] ?? $r['doelgroep'] ); ?></span></td>
						<td><?php echo esc_html( implode( ' · ', array_filter( array( $r['bedrijf'], $r['functie'] ) ) ) ); ?></td>
						<td class="col-uit">
							<?php if ( $r['uitgenodigd'] ) : ?>
								<a class="deedni-va-jn is-ja" href="<?php echo esc_url( $actie( 'niet_uitgenodigd', $r['id'] ) ); ?>" title="Klik om terug te zetten naar nee">Ja</a>
							<?php else : ?>
								<a class="deedni-va-jn" href="<?php echo esc_url( $actie( 'uitgenodigd', $r['id'] ) ); ?>" title="Klik als je deze persoon hebt uitgenodigd">Nee</a>
							<?php endif; ?>
						</td>
						<td class="col-acties"><a class="deedni-va-weg" href="<?php echo esc_url( $actie( 'verwijderen', $r['id'] ) ); ?>" onclick="return window.confirm('<?php echo esc_js( 'Aanmelding van ' . $r['naam'] . ' verwijderen?' ); ?>');">Verwijderen</a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</form>

		<div class="deedni-va-onder">
			<form method="post" action="<?php echo esc_url( $post ); ?>" class="deedni-va-kaart">
				<h2>Melding per e-mail</h2>
				<input type="hidden" name="action" value="deedni_aanmeldingen">
				<input type="hidden" name="wat" value="mail">
				<?php wp_nonce_field( 'deedni_aanmeldingen' ); ?>
				<label><input type="checkbox" name="aan" value="1" <?php checked( (bool) get_option( 'deedni_aanmelding_mail', 0 ) ); ?>> Stuur mij een mail bij elke nieuwe aanmelding (naar <?php echo esc_html( get_option( 'admin_email' ) ); ?>)</label>
				<p class="description">Werkt pas als e-mail op de site werkt (bijvoorbeeld met de gratis plugin WP Mail SMTP).</p>
				<p><input type="submit" class="button" value="Opslaan"></p>
			</form>
			<form method="post" action="<?php echo esc_url( $post ); ?>" class="deedni-va-kaart deedni-va-gevaar">
				<h2>Na de test: alles verwijderen</h2>
				<input type="hidden" name="action" value="deedni_aanmeldingen">
				<input type="hidden" name="wat" value="alles_verwijderen">
				<?php wp_nonce_field( 'deedni_aanmeldingen' ); ?>
				<p>Je hebt beloofd de gegevens na de test te verwijderen. Download eerst de CSV als je die nog nodig hebt. Dit kan niet ongedaan worden.</p>
				<p><label for="deedni-va-zeker">Typ <code>VERWIJDER</code> om te bevestigen</label><br><input type="text" id="deedni-va-zeker" name="zeker" autocomplete="off"></p>
				<p><input type="submit" class="button button-link-delete" value="Alle <?php echo (int) $t['totaal']; ?> aanmeldingen verwijderen"></p>
			</form>
		</div>
	</div>
	<script>
	(function(){var a=document.getElementById('deedni-va-alles');if(!a)return;a.addEventListener('change',function(){document.querySelectorAll('.deedni-va-tabel tbody input[type=checkbox]').forEach(function(c){c.checked=a.checked;});});})();
	</script>
	<style>
	.deedni-va-intro{max-width:52em;font-size:14px}
	.deedni-va-tegels{display:flex;flex-wrap:wrap;gap:12px;margin:16px 0 8px}
	.deedni-va-tegel{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:14px 18px;min-width:130px;display:flex;flex-direction:column;gap:2px}
	.deedni-va-tegel b{font-size:26px;line-height:1.1;color:#1d2327}
	.deedni-va-tegel span{color:#50575e}
	.deedni-va .search-box{margin:8px 0}
	.deedni-va-tabel .col-datum{width:150px}.deedni-va-tabel .col-wie{width:110px}.deedni-va-tabel .col-uit{width:100px}.deedni-va-tabel .col-acties{width:100px}
	.deedni-va-label{display:inline-block;padding:2px 8px;border-radius:99px;font-size:12px;font-weight:600;background:#e7ecff;color:#2842E0}
	.deedni-va-label--werkgever{background:#fff4cc;color:#3d3000}
	.deedni-va-jn{display:inline-block;min-width:44px;text-align:center;padding:3px 10px;border-radius:99px;border:1px solid #c3c4c7;color:#50575e;text-decoration:none;font-weight:600}
	.deedni-va-jn.is-ja{background:#e6f4ea;border-color:#68b27c;color:#1e6b34}
	.deedni-va-jn:hover{border-color:#2271b1;color:#2271b1}
	.deedni-va-weg{color:#b32d2e}
	.deedni-va-onder{display:flex;flex-wrap:wrap;gap:16px;margin-top:24px}
	.deedni-va-kaart{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:4px 18px 8px;flex:1 1 320px;max-width:560px}
	.deedni-va-kaart h2{font-size:15px}
	.deedni-va-gevaar{border-color:#e6b8b8}
	@media (max-width:782px){.deedni-va-tabel .col-datum,.deedni-va-tabel .col-acties{width:auto}}
	</style>
	<?php
}

/* =========================================================================
 * AVG: Extra > Persoonsgegevens exporteren/wissen vindt ook de aanmeldingen
 * ========================================================================= */

add_filter(
	'wp_privacy_personal_data_exporters',
	function ( $exporters ) {
		$exporters['deedni-aanmeldingen'] = array(
			'exporter_friendly_name' => 'deedni vooraanmelding',
			'callback'               => function ( $email ) {
				global $wpdb;
				$tabel = deedni_aanmeldingen_tabel();
				$items = array();
				foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tabel} WHERE email = %s", $email ), ARRAY_A ) as $r ) { // phpcs:ignore WordPress.DB
					$data = array();
					foreach ( array( 'aangemeld' => 'Aangemeld', 'doelgroep' => 'Doelgroep', 'naam' => 'Naam', 'email' => 'E-mail', 'bedrijf' => 'Bedrijf', 'functie' => 'Functie', 'toestemming' => 'Toestemming gegeven', 'toestemming_tekst' => 'Toestemmingstekst' ) as $k => $label ) {
						$data[] = array( 'name' => $label, 'value' => $r[ $k ] );
					}
					$items[] = array( 'group_id' => 'deedni-aanmelding', 'group_label' => 'Vooraanmelding', 'item_id' => 'aanmelding-' . $r['id'], 'data' => $data );
				}
				return array( 'data' => $items, 'done' => true );
			},
		);
		return $exporters;
	}
);

add_filter(
	'wp_privacy_personal_data_erasers',
	function ( $erasers ) {
		$erasers['deedni-aanmeldingen'] = array(
			'eraser_friendly_name' => 'deedni vooraanmelding',
			'callback'             => function ( $email ) {
				global $wpdb;
				$weg = (int) $wpdb->delete( deedni_aanmeldingen_tabel(), array( 'email' => $email ) ); // phpcs:ignore WordPress.DB
				return array( 'items_removed' => $weg > 0, 'items_retained' => false, 'messages' => array(), 'done' => true );
			},
		);
		return $erasers;
	}
);

/** Tekst voor de privacyverklaring (Instellingen > Privacy > hulp bij de tekst). */
add_action(
	'admin_init',
	function () {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content(
				'deedni vooraanmelding',
				'<p>Als je je aanmeldt voor de test, bewaren we je naam, e-mailadres, of je werk of personeel zoekt en (als je die invult) je bedrijf en functie. We bewaren ook wanneer je toestemming gaf. We gebruiken dit alleen om je uit te nodigen voor de test en verwijderen het na de test, of eerder als je daarom vraagt. We bewaren geen IP-adres.</p>'
			);
		}
	}
);
