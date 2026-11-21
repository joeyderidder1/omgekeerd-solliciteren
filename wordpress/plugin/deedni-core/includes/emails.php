<?php
/**
 * E-mails. Allemaal via wp_mail, dus een SMTP-plugin (bijvoorbeeld WP Mail SMTP) werkt vanzelf.
 * Filter 'deedni_mail' om onderwerp of tekst aan te passen.
 */

defined( 'ABSPATH' ) || exit;

function deedni_mail( $to, $subject, $paragraphs, $button_text = '', $button_url = '' ) {
	$mail = apply_filters( 'deedni_mail', compact( 'to', 'subject', 'paragraphs', 'button_text', 'button_url' ) );
	if ( ! $mail || ! $mail['to'] ) {
		return false;
	}
	$accent = '#2842E0';
	$body   = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.5;color:#0F1A3D;max-width:560px">';
	$body  .= '<p style="font-size:26px;font-weight:800;letter-spacing:-1px;margin:0 0 24px">' . esc_html( get_bloginfo( 'name' ) ) . '<span style="display:inline-block;width:13px;height:4px;background:' . $accent . ';margin-left:3px"></span></p>';
	foreach ( $mail['paragraphs'] as $p ) {
		$body .= '<p style="margin:0 0 16px">' . $p . '</p>';
	}
	if ( $mail['button_text'] && $mail['button_url'] ) {
		$body .= '<p style="margin:24px 0"><a href="' . esc_url( $mail['button_url'] ) . '" style="display:inline-block;background:' . $accent . ';color:#fff;font-weight:700;text-decoration:none;padding:14px 24px;border-radius:12px">' . esc_html( $mail['button_text'] ) . '</a></p>';
	}
	$body .= '</div>';
	return wp_mail( $mail['to'], $mail['subject'], $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
}

function deedni_mail_welcome( $user_id ) {
	$user = get_userdata( $user_id );
	if ( deedni_is_werkgever( $user ) ) {
		$text = deedni_werkgever_goedgekeurd( $user )
			? 'Je account is klaar. Vul je bedrijfsprofiel aan, dan berekenen we welke kandidaten het best bij jullie passen.'
			: 'We controleren je account, meestal binnen één werkdag. Je krijgt een mail zodra je kunt zoeken. Vul intussen je bedrijfsprofiel aan.';
	} else {
		$text = 'Maak je profiel compleet. Zodra het online staat, kunnen bedrijven je een aanbod sturen. Ze zien je naam pas als jij ja zegt.';
	}
	deedni_mail(
		$user->user_email,
		'Welkom bij ' . get_bloginfo( 'name' ),
		array( 'Hoi ' . esc_html( $user->first_name ? $user->first_name : $user->display_name ) . ',', $text ),
		'Naar je profiel',
		deedni_page_url( 'profiel' )
	);
}

function deedni_mail_admin_new_werkgever( $user_id ) {
	$user    = get_userdata( $user_id );
	$bedrijf = get_the_title( deedni_profile_id( $user_id, 'bedrijf' ) );
	deedni_mail(
		get_option( 'admin_email' ),
		'Nieuwe werkgever: ' . $bedrijf,
		array(
			esc_html( $bedrijf ) . ' (' . esc_html( $user->user_email ) . ') heeft zich aangemeld als werkgever.',
			'Keur het account goed bij Gebruikers, dan kan deze werkgever kandidaten zoeken.',
		),
		'Account bekijken',
		admin_url( 'users.php?role=deedni_werkgever' )
	);
}

function deedni_mail_werkgever_approved( $user_id ) {
	$user = get_userdata( $user_id );
	deedni_mail(
		$user->user_email,
		'Je kunt nu kandidaten zoeken',
		array( 'Goed nieuws: je account is goedgekeurd. Je kunt nu anonieme profielen bekijken en een aanbod sturen.' ),
		'Kandidaten zoeken',
		deedni_page_url( 'zoeken' )
	);
}

function deedni_mail_kandidaat( $aanbod_id, $subject, $paragraphs ) {
	$a    = deedni_aanbod( $aanbod_id );
	$user = get_userdata( get_post_field( 'post_author', $a['kandidaat_id'] ) );
	if ( ! $user ) {
		return;
	}
	$naam = deedni_field( $a['kandidaat_id'], 'voornaam' );
	array_unshift( $paragraphs, 'Hoi ' . esc_html( $naam ? $naam : $user->display_name ) . ',' );
	$paragraphs[] = 'Het bedrijf ziet je naam pas als jij ja zegt.';
	deedni_mail( $user->user_email, $subject, $paragraphs, 'Aanbod bekijken', deedni_page_url( 'dashboard' ) . '#aanbod-' . $aanbod_id );
}

function deedni_mail_new_offer( $aanbod_id ) {
	$a       = deedni_aanbod( $aanbod_id );
	$bedrijf = get_the_title( $a['bedrijf_id'] );
	deedni_mail_kandidaat(
		$aanbod_id,
		'Nieuw aanbod van ' . $bedrijf,
		array( esc_html( $bedrijf ) . ' wil je graag spreken voor de functie <strong>' . esc_html( $a['functie'] ) . '</strong>, met een salaris van ' . esc_html( deedni_range( $a['salaris_min'], $a['salaris_max'] ) ) . ' bruto per maand.' )
	);
}

function deedni_mail_offer_updated( $aanbod_id ) {
	$a       = deedni_aanbod( $aanbod_id );
	$bedrijf = get_the_title( $a['bedrijf_id'] );
	$text    = $a['akkoord']
		? esc_html( $bedrijf ) . ' gaat akkoord met je voorwaarde. Het aanbod is nu ' . esc_html( deedni_range( $a['salaris_min'], $a['salaris_max'] ) ) . ' per maand. Zeg je ja?'
		: esc_html( $bedrijf ) . ' heeft het aanbod aangepast na je reactie. Het nieuwe salaris is ' . esc_html( deedni_range( $a['salaris_min'], $a['salaris_max'] ) ) . ' per maand.';
	deedni_mail_kandidaat( $aanbod_id, 'Aangepast aanbod van ' . $bedrijf, array( $text ) );
}

function deedni_mail_offer_stopped( $aanbod_id ) {
	$a = deedni_aanbod( $aanbod_id );
	deedni_mail_kandidaat( $aanbod_id, 'Aanbod gestopt', array( esc_html( get_the_title( $a['bedrijf_id'] ) ) . ' gaat niet verder met het aanbod voor ' . esc_html( $a['functie'] ) . '. Je bent anoniem gebleven.' ) );
}

function deedni_mail_offer_response( $aanbod_id ) {
	$a         = deedni_aanbod( $aanbod_id );
	$werkgever = get_userdata( $a['werkgever_id'] );
	if ( ! $werkgever ) {
		return;
	}
	$code    = deedni_code( $a['kandidaat_id'] );
	$functie = esc_html( $a['functie'] );
	switch ( $a['status'] ) {
		case 'geaccepteerd':
			$name = trim( deedni_field( $a['kandidaat_id'], 'voornaam' ) . ' ' . deedni_field( $a['kandidaat_id'], 'achternaam' ) );
			deedni_mail(
				$werkgever->user_email,
				$code . ' zegt ja op je aanbod',
				array(
					'Goed nieuws: ' . esc_html( $name ? $name : $code ) . ' (' . $code . ') heeft ja gezegd op je aanbod voor ' . $functie . '.',
					'Naam, cv en contactgegevens staan nu in je overzicht. Neem snel contact op.',
				),
				'Contactgegevens bekijken',
				deedni_page_url( 'dashboard' )
			);
			break;
		case 'tegenvoorstel':
			deedni_mail(
				$werkgever->user_email,
				$code . ' reageert met "Ja, maar..."',
				array(
					$code . ' heeft interesse in je aanbod voor ' . $functie . ', met een voorwaarde.',
					'Bekijk de reactie in je overzicht. Je kunt het aanbod aanpassen, akkoord gaan of het laten vallen.',
				),
				'Reactie bekijken',
				deedni_page_url( 'dashboard' ) . '#aanbod-' . $aanbod_id
			);
			break;
		default:
			deedni_mail(
				$werkgever->user_email,
				$code . ' heeft je aanbod afgewezen',
				array( $code . ' heeft je aanbod voor ' . $functie . ' vriendelijk afgewezen en blijft anoniem. Er staan nog meer kandidaten voor je klaar.' ),
				'Verder zoeken',
				deedni_page_url( 'zoeken' )
			);
	}
}

function deedni_mail_hired( $aanbod_id ) {
	$a = deedni_aanbod( $aanbod_id );
	deedni_mail(
		get_option( 'admin_email' ),
		'Aanname gemeld: ' . get_the_title( $a['bedrijf_id'] ),
		array(
			esc_html( get_the_title( $a['bedrijf_id'] ) ) . ' meldt dat ' . deedni_code( $a['kandidaat_id'] ) . ' is aangenomen als ' . esc_html( $a['functie'] ) . ' (' . esc_html( deedni_range( $a['salaris_min'], $a['salaris_max'] ) ) . ' per maand).',
			'Tijd om te factureren.',
		),
		'Aanbod bekijken',
		admin_url( 'post.php?action=edit&post=' . (int) $aanbod_id )
	);
}
