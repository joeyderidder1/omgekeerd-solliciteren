<?php
/**
 * E-mails. Allemaal via wp_mail, dus een SMTP-plugin (bijvoorbeeld WP Mail SMTP) werkt vanzelf.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Verstuurt een eenvoudige HTML-mail. Filter 'deedni_mail' om onderwerp of tekst aan te passen.
 */
function deedni_mail( $to, $subject, $paragraphs, $button_text = '', $button_url = '' ) {
	$mail = apply_filters( 'deedni_mail', compact( 'to', 'subject', 'paragraphs', 'button_text', 'button_url' ) );
	if ( ! $mail ) {
		return false;
	}

	$body  = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.5;color:#16213A;max-width:560px">';
	$body .= '<p style="font-size:24px;font-weight:800;margin:0 0 24px">deedni<span style="color:#C2410C">.</span></p>';
	foreach ( $mail['paragraphs'] as $p ) {
		$body .= '<p style="margin:0 0 16px">' . $p . '</p>';
	}
	if ( $mail['button_text'] && $mail['button_url'] ) {
		$body .= '<p style="margin:24px 0"><a href="' . esc_url( $mail['button_url'] ) . '" style="display:inline-block;background:#C2410C;color:#fff;font-weight:700;text-decoration:none;padding:14px 24px;border-radius:999px">' . esc_html( $mail['button_text'] ) . '</a></p>';
	}
	$body .= '</div>';

	return wp_mail( $mail['to'], $mail['subject'], $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
}

function deedni_mail_welcome( $user_id ) {
	$user = get_userdata( $user_id );
	if ( deedni_is_werkgever( $user ) ) {
		$text = deedni_werkgever_goedgekeurd( $user )
			? 'Je account is klaar. Vul je bedrijfsprofiel aan en begin met zoeken.'
			: 'We controleren je account, meestal binnen één werkdag. Je krijgt een mail zodra je kunt zoeken. Vul intussen je bedrijfsprofiel aan.';
	} else {
		$text = 'Maak je profiel compleet. Zodra het zichtbaar is, kunnen bedrijven je een aanbod sturen. Ze zien je naam pas als jij ja zegt.';
	}
	deedni_mail(
		$user->user_email,
		'Welkom bij deedni',
		array( 'Hoi ' . esc_html( $user->first_name ? $user->first_name : $user->display_name ) . ',', $text ),
		'Naar je profiel',
		deedni_page_url( 'profiel' )
	);
}

function deedni_mail_admin_new_werkgever( $user_id ) {
	$user    = get_userdata( $user_id );
	$bedrijf = get_the_title( deedni_profile_id( $user_id ) );
	deedni_mail(
		get_option( 'admin_email' ),
		'Nieuwe werkgever: ' . $bedrijf,
		array(
			esc_html( $bedrijf ) . ' (' . esc_html( $user->user_email ) . ') heeft zich aangemeld als werkgever.',
			'Keur het account goed bij Gebruikers, dan kan deze werkgever kandidaten zoeken.',
		),
		'Account bekijken',
		admin_url( 'user-edit.php?user_id=' . $user_id )
	);
}

function deedni_mail_werkgever_approved( $user_id ) {
	$user = get_userdata( $user_id );
	deedni_mail(
		$user->user_email,
		'Je kunt nu kandidaten zoeken op deedni',
		array( 'Goed nieuws: je account is goedgekeurd. Je kunt nu anonieme profielen bekijken en een aanbod sturen.' ),
		'Kandidaten zoeken',
		deedni_page_url( 'zoeken' )
	);
}

function deedni_mail_new_offer( $aanbod_id ) {
	$kandidaat_id = (int) deedni_field( $aanbod_id, 'kandidaat' );
	$user         = get_userdata( get_post_field( 'post_author', $kandidaat_id ) );
	$bedrijf      = get_the_title( (int) deedni_field( $aanbod_id, 'bedrijf' ) );
	if ( ! $user ) {
		return;
	}
	deedni_mail(
		$user->user_email,
		'Nieuw aanbod van ' . $bedrijf,
		array(
			'Hoi ' . esc_html( deedni_field( $kandidaat_id, 'voornaam' ) ? deedni_field( $kandidaat_id, 'voornaam' ) : $user->display_name ) . ',',
			esc_html( $bedrijf ) . ' wil je graag spreken voor de functie <strong>' . esc_html( deedni_field( $aanbod_id, 'functie' ) ) . '</strong>, met een salaris van ' . esc_html( deedni_euro( deedni_field( $aanbod_id, 'salaris_min' ) ) . ' tot ' . deedni_euro( deedni_field( $aanbod_id, 'salaris_max' ) ) ) . ' bruto per maand.',
			'Het bedrijf ziet je naam pas als jij ja zegt.',
		),
		'Aanbod bekijken',
		deedni_page_url( 'dashboard' )
	);
}

function deedni_mail_offer_response( $aanbod_id ) {
	$werkgever    = get_userdata( get_post_field( 'post_author', $aanbod_id ) );
	$kandidaat_id = (int) deedni_field( $aanbod_id, 'kandidaat' );
	$accepted     = 'geaccepteerd' === deedni_field( $aanbod_id, 'status' );
	if ( ! $werkgever ) {
		return;
	}
	$functie = esc_html( deedni_field( $aanbod_id, 'functie' ) );
	if ( $accepted ) {
		$name = trim( deedni_field( $kandidaat_id, 'voornaam' ) . ' ' . deedni_field( $kandidaat_id, 'achternaam' ) );
		deedni_mail(
			$werkgever->user_email,
			deedni_code( $kandidaat_id ) . ' heeft je aanbod geaccepteerd',
			array(
				'Goed nieuws: ' . esc_html( $name ) . ' (' . deedni_code( $kandidaat_id ) . ') heeft ja gezegd op je aanbod voor ' . $functie . '.',
				'Naam, cv en contactgegevens staan nu in je overzicht. Neem snel contact op.',
			),
			'Contactgegevens bekijken',
			deedni_page_url( 'dashboard' )
		);
	} else {
		deedni_mail(
			$werkgever->user_email,
			deedni_code( $kandidaat_id ) . ' heeft je aanbod afgewezen',
			array( deedni_code( $kandidaat_id ) . ' heeft je aanbod voor ' . $functie . ' vriendelijk afgewezen. Er staan nog meer marketeers voor je klaar.' ),
			'Verder zoeken',
			deedni_page_url( 'zoeken' )
		);
	}
}

function deedni_mail_hired( $aanbod_id ) {
	deedni_mail(
		get_option( 'admin_email' ),
		'Aanname gemeld: ' . get_the_title( (int) deedni_field( $aanbod_id, 'bedrijf' ) ),
		array(
			esc_html( get_the_title( (int) deedni_field( $aanbod_id, 'bedrijf' ) ) ) . ' meldt dat ' . deedni_code( (int) deedni_field( $aanbod_id, 'kandidaat' ) ) . ' is aangenomen als ' . esc_html( deedni_field( $aanbod_id, 'functie' ) ) . '.',
			'Tijd om te factureren.',
		),
		'Aanbod bekijken',
		admin_url( 'post.php?action=edit&post=' . (int) $aanbod_id )
	);
}
