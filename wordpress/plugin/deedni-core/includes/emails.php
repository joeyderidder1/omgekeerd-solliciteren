<?php
/**
 * E-mails. Allemaal via wp_mail, dus een SMTP-plugin (bijvoorbeeld WP Mail SMTP) werkt vanzelf.
 *
 * De teksten staan in mail-sjablonen.php en zijn aan te passen in wp-admin onder
 * Formulieren & mail > Mailteksten. Elke verstuurde mail komt in de e-maillog (maillog.php).
 * Filter 'deedni_mail' om onderwerp of tekst vlak voor het versturen nog aan te passen.
 */

defined( 'ABSPATH' ) || exit;

/** De HTML van een deedni-mail: logo, alinea's (al veilige HTML) en eventueel een knop. */
function deedni_mail_html( $paragraphs, $button_text = '', $button_url = '' ) {
	$accent = '#2842E0';
	$body   = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.5;color:#0F1A3D;max-width:560px">';
	$body  .= '<p style="font-size:26px;font-weight:800;letter-spacing:-1px;margin:0 0 24px">' . esc_html( get_bloginfo( 'name' ) ) . '<span style="display:inline-block;width:13px;height:4px;background:' . $accent . ';margin-left:3px"></span></p>';
	foreach ( (array) $paragraphs as $p ) {
		$tag   = 0 === strpos( ltrim( $p ), '<ul' ) ? 'div' : 'p'; // Een lijstje mag niet in een <p>.
		$body .= '<' . $tag . ' style="margin:0 0 16px">' . $p . '</' . $tag . '>';
	}
	if ( $button_text && $button_url ) {
		$body .= '<p style="margin:24px 0"><a href="' . esc_url( $button_url ) . '" style="display:inline-block;background:' . $accent . ';color:#fff;font-weight:700;text-decoration:none;padding:14px 24px;border-radius:12px">' . esc_html( $button_text ) . '</a></p>';
	}
	return $body . '</div>';
}

function deedni_mail( $to, $subject, $paragraphs, $button_text = '', $button_url = '' ) {
	$mail = apply_filters( 'deedni_mail', compact( 'to', 'subject', 'paragraphs', 'button_text', 'button_url' ) );
	if ( ! $mail || ! $mail['to'] ) {
		return false;
	}
	$body = deedni_mail_html( $mail['paragraphs'], $mail['button_text'], $mail['button_url'] );
	$ok   = wp_mail( $mail['to'], $mail['subject'], $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
	do_action( 'deedni_mail_resultaat', $ok );
	return $ok;
}

/* ---------- Hulpjes voor de variabelen ---------- */

function deedni_mail_voornaam( $user ) {
	return $user->first_name ? $user->first_name : $user->display_name;
}

/** Alle variabelen over een aanbod. */
function deedni_mail_vars_aanbod( $aanbod_id ) {
	$a    = deedni_aanbod( $aanbod_id );
	$naam = deedni_field( $a['kandidaat_id'], 'voornaam' );
	$heel = trim( deedni_field( $a['kandidaat_id'], 'voornaam' ) . ' ' . deedni_field( $a['kandidaat_id'], 'achternaam' ) );
	$code = deedni_code( $a['kandidaat_id'] );
	return array(
		'bedrijf'        => get_the_title( $a['bedrijf_id'] ),
		'functie'        => $a['functie'],
		'salaris'        => deedni_range( $a['salaris_min'], $a['salaris_max'] ),
		'code'           => $code,
		'naam'           => $naam,
		'kandidaat_naam' => $heel ? $heel : $code,
	);
}

/* ---------- Account ---------- */

function deedni_mail_welcome( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}
	if ( deedni_is_werkgever( $user ) ) {
		$type = deedni_werkgever_goedgekeurd( $user ) ? 'welkom_werkgever' : 'welkom_werkgever_wacht';
	} else {
		$type = 'welkom_kandidaat';
	}
	return deedni_mail_sjabloon(
		$type,
		$user->user_email,
		array(
			'naam'         => deedni_mail_voornaam( $user ),
			'bedrijf'      => deedni_is_werkgever( $user ) ? get_the_title( deedni_profile_id( $user_id, 'bedrijf' ) ) : '',
			'email'        => $user->user_email,
			'link'         => deedni_page_url( 'profiel' ),
			'extra_velden' => function_exists( 'deedni_formulier_extra_html' ) ? deedni_formulier_extra_html( 'registreren', get_user_meta( $user_id, 'deedni_extra_registratie', true ) ) : '',
		),
		array( 'gebruiker' => $user_id )
	);
}

function deedni_mail_admin_new_werkgever( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}
	return deedni_mail_sjabloon(
		'beheer_nieuwe_werkgever',
		get_option( 'admin_email' ),
		array(
			'bedrijf'      => get_the_title( deedni_profile_id( $user_id, 'bedrijf' ) ),
			'email'        => $user->user_email,
			'link'         => admin_url( 'users.php?role=deedni_werkgever' ),
			'extra_velden' => function_exists( 'deedni_formulier_extra_html' ) ? deedni_formulier_extra_html( 'registreren', get_user_meta( $user_id, 'deedni_extra_registratie', true ) ) : '',
		),
		array( 'gebruiker' => $user_id )
	);
}

function deedni_mail_werkgever_approved( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return false;
	}
	return deedni_mail_sjabloon(
		'werkgever_goedgekeurd',
		$user->user_email,
		array(
			'naam'    => deedni_mail_voornaam( $user ),
			'bedrijf' => get_the_title( deedni_profile_id( $user_id, 'bedrijf' ) ),
			'link'    => deedni_page_url( 'zoeken' ),
		),
		array( 'gebruiker' => $user_id )
	);
}

/* ---------- Aanbiedingen naar de kandidaat ---------- */

/** Een mail over een aanbod naar de kandidaat, met de tekst van $type. */
function deedni_mail_aanbod_kandidaat( $type, $aanbod_id, $extra = array() ) {
	$a    = deedni_aanbod( $aanbod_id );
	$user = get_userdata( get_post_field( 'post_author', $a['kandidaat_id'] ) );
	if ( ! $user ) {
		return false;
	}
	$vars = array_merge( deedni_mail_vars_aanbod( $aanbod_id ), $extra );
	if ( '' === $vars['naam'] ) {
		$vars['naam'] = $user->display_name;
	}
	$vars['email'] = $user->user_email;
	$vars['link']  = deedni_page_url( 'dashboard' ) . '#aanbod-' . (int) $aanbod_id;
	return deedni_mail_sjabloon( $type, $user->user_email, $vars, array( 'gebruiker' => $user->ID, 'object' => $aanbod_id ) );
}

/**
 * Oude manier (vrije tekst) om de kandidaat over een aanbod te mailen. Blijft werken voor eigen code,
 * maar deedni zelf gebruikt de Mailteksten.
 */
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
	return deedni_mail_aanbod_kandidaat( 'aanbod_nieuw', $aanbod_id, array( 'extra_velden' => deedni_mail_extra_aanbod( $aanbod_id ) ) );
}

function deedni_mail_offer_updated( $aanbod_id ) {
	$a = deedni_aanbod( $aanbod_id );
	return deedni_mail_aanbod_kandidaat( $a['akkoord'] ? 'aanbod_akkoord' : 'aanbod_aangepast', $aanbod_id, array( 'extra_velden' => deedni_mail_extra_aanbod( $aanbod_id ) ) );
}

function deedni_mail_offer_stopped( $aanbod_id ) {
	return deedni_mail_aanbod_kandidaat( 'aanbod_gestopt', $aanbod_id );
}

/** Extra velden uit het formulier "Aanbod sturen" (net ingevuld, of eerder opgeslagen bij het aanbod). */
function deedni_mail_extra_aanbod( $aanbod_id ) {
	if ( ! function_exists( 'deedni_formulier_extra_html' ) ) {
		return '';
	}
	$waarden = isset( $GLOBALS['deedni_formulier_extra']['aanbod_sturen'] ) ? $GLOBALS['deedni_formulier_extra']['aanbod_sturen'] : get_post_meta( $aanbod_id, 'deedni_extra_aanbod', true );
	return deedni_formulier_extra_html( 'aanbod_sturen', $waarden );
}

/* ---------- Reacties naar de werkgever ---------- */

function deedni_mail_offer_response( $aanbod_id ) {
	$a         = deedni_aanbod( $aanbod_id );
	$werkgever = get_userdata( $a['werkgever_id'] );
	if ( ! $werkgever ) {
		return false;
	}
	$vars          = deedni_mail_vars_aanbod( $aanbod_id );
	$vars['naam']  = deedni_mail_voornaam( $werkgever );
	$vars['email'] = $werkgever->user_email;
	switch ( $a['status'] ) {
		case 'geaccepteerd':
			$type         = 'reactie_ja';
			$vars['link'] = deedni_page_url( 'dashboard' );
			break;
		case 'tegenvoorstel':
			$type                 = 'reactie_jamaar';
			$vars['link']         = deedni_page_url( 'dashboard' ) . '#aanbod-' . (int) $aanbod_id;
			$vars['extra_velden'] = function_exists( 'deedni_formulier_extra_html' ) ? deedni_formulier_extra_html( 'aanbod_jamaar', get_post_meta( $aanbod_id, 'deedni_extra_reactie', true ) ) : '';
			break;
		default:
			$type                   = 'reactie_nee';
			$vars['kandidaat_naam'] = $vars['code']; // Bij nee blijft de kandidaat anoniem.
			$vars['link']           = deedni_page_url( 'zoeken' );
	}
	if ( 'reactie_ja' !== $type ) {
		$vars['kandidaat_naam'] = $vars['code'];
	}
	return deedni_mail_sjabloon( $type, $werkgever->user_email, $vars, array( 'gebruiker' => $werkgever->ID, 'object' => $aanbod_id ) );
}

/* ---------- Naar de beheerder ---------- */

function deedni_mail_hired( $aanbod_id ) {
	$vars         = deedni_mail_vars_aanbod( $aanbod_id );
	$vars['link'] = admin_url( 'post.php?action=edit&post=' . (int) $aanbod_id );
	unset( $vars['naam'], $vars['kandidaat_naam'] );
	return deedni_mail_sjabloon( 'beheer_aanname', get_option( 'admin_email' ), $vars, array( 'object' => $aanbod_id ) );
}
