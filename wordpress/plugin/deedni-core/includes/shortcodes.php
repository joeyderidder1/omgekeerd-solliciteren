<?php
/**
 * Shortcodes voor de pagina's die de plugin bij activeren aanmaakt:
 * [deedni_registreren] [deedni_inloggen] [deedni_dashboard] [deedni_profiel] [deedni_zoeken]
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'deedni_registreren', 'deedni_sc_register' );
add_shortcode( 'deedni_inloggen', 'deedni_sc_login' );
add_shortcode( 'deedni_dashboard', 'deedni_sc_dashboard' );
add_shortcode( 'deedni_profiel', 'deedni_sc_profile' );
add_shortcode( 'deedni_zoeken', 'deedni_sc_search' );

/** Melding na een doorverwijzing (?deedni_melding=...). */
function deedni_message_from_url() {
	$code = isset( $_GET['deedni_melding'] ) ? sanitize_key( $_GET['deedni_melding'] ) : '';
	if ( ! $code ) {
		return '';
	}
	$messages = array(
		'opgeslagen'       => array( 'Je profiel is opgeslagen.', 'success' ),
		'geen_toegang'     => array( 'Dit profiel is niet (meer) beschikbaar.', 'error' ),
		'aanbod_verstuurd' => array( 'Je aanbod is verstuurd. Zegt de kandidaat ja, dan zie je naam, cv en contactgegevens in je overzicht.', 'success' ),
		'geaccepteerd'     => array( 'Je hebt het aanbod geaccepteerd. Het bedrijf ziet nu je naam en contactgegevens en neemt contact met je op.', 'success' ),
		'afgewezen'        => array( 'Je hebt het aanbod afgewezen. Het bedrijf krijgt een nette afwijzing en ziet je naam niet.', 'info' ),
		'aangenomen'       => array( 'Gefeliciteerd met je nieuwe collega. We nemen contact met je op over de afronding.', 'success' ),
		'login_mislukt'    => array( 'Dit e-mailadres of wachtwoord klopt niet. Probeer het opnieuw.', 'error' ),
	);
	if ( 'welkom' === $code ) {
		if ( deedni_is_werkgever() ) {
			$text = deedni_werkgever_goedgekeurd()
				? 'Welkom bij deedni. Vul je bedrijfsprofiel in, dan kun je meteen zoeken.'
				: 'Welkom bij deedni. We controleren je account, meestal binnen één werkdag. Vul intussen je bedrijfsprofiel in.';
		} else {
			$text = 'Welkom bij deedni. Vul je profiel in. Bedrijven vinden je zodra je functie, specialisme en provincie zijn ingevuld.';
		}
		return deedni_notice( $text, 'success' );
	}
	return isset( $messages[ $code ] ) ? deedni_notice( $messages[ $code ][0], $messages[ $code ][1] ) : '';
}

/* ---------- Registreren ---------- */

function deedni_sc_register() {
	if ( is_user_logged_in() ) {
		return deedni_notice( 'Je bent al ingelogd. <a href="' . esc_url( deedni_page_url( 'dashboard' ) ) . '">Naar je overzicht</a>.' );
	}
	$flash = deedni_flash( 'registreren' );
	$data  = isset( $flash['data'] ) ? $flash['data'] : array();
	$rol   = isset( $data['rol'] ) ? $data['rol'] : ( isset( $_GET['rol'] ) && 'werkgever' === $_GET['rol'] ? 'werkgever' : 'kandidaat' );

	return deedni_template(
		'registreren',
		array(
			'errors' => isset( $flash['errors'] ) ? $flash['errors'] : array(),
			'data'   => $data,
			'rol'    => $rol,
		)
	);
}

/* ---------- Inloggen ---------- */

function deedni_sc_login() {
	if ( is_user_logged_in() ) {
		return deedni_notice( 'Je bent ingelogd. <a href="' . esc_url( deedni_page_url( 'dashboard' ) ) . '">Naar je overzicht</a> of <a href="' . esc_url( wp_logout_url() ) . '">log uit</a>.' );
	}
	$redirect = isset( $_GET['redirect_to'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ), '' ) : '';
	return deedni_message_from_url() . deedni_template(
		'inloggen',
		array( 'redirect' => $redirect ? $redirect : deedni_page_url( 'dashboard' ) )
	);
}

/* ---------- Profiel bewerken (ACF-formulier) ---------- */

function deedni_sc_profile() {
	if ( ! is_user_logged_in() ) {
		return '';
	}
	if ( ! function_exists( 'acf_form' ) ) {
		return deedni_notice( 'Het profielformulier werkt pas als Advanced Custom Fields actief is.', 'error' );
	}
	$profile_id = deedni_profile_id();
	if ( ! $profile_id ) {
		return deedni_notice( deedni_is_beheerder() ? 'Je bent beheerder en hebt geen eigen profiel. Bekijk profielen in wp-admin.' : 'Er hoort geen profiel bij je account. Neem contact met ons op.', 'info' );
	}

	$werkgever = deedni_is_werkgever();
	ob_start();
	acf_form(
		array(
			'id'                 => 'deedni-profiel',
			'post_id'            => $profile_id,
			'field_groups'       => array( $werkgever ? 'group_deedni_bedrijf' : 'group_deedni_kandidaat' ),
			'post_title'         => false,
			'post_content'       => false,
			'return'             => deedni_page_url( 'profiel', array( 'deedni_melding' => 'opgeslagen' ) ),
			'submit_value'       => $werkgever ? 'Bedrijfsprofiel opslaan' : 'Profiel opslaan',
			'html_submit_button' => '<button type="submit" class="btn btn--accent">%s</button>',
			'updated_message'    => false,
			'uploader'           => 'basic',
			'form_attributes'    => array( 'class' => 'acf-form dn-acf-form' ),
		)
	);
	$form = ob_get_clean();

	return deedni_message_from_url() . deedni_template(
		'profiel',
		array(
			'profile_id' => $profile_id,
			'werkgever'  => $werkgever,
			'form'       => $form,
		)
	);
}

/* ---------- Zoeken ---------- */

function deedni_sc_search() {
	if ( ! is_user_logged_in() ) {
		return deedni_template( 'zoeken-gesloten', array( 'reason' => 'uitgelogd' ) );
	}
	if ( deedni_is_kandidaat() ) {
		return deedni_template( 'zoeken-gesloten', array( 'reason' => 'kandidaat' ) );
	}
	if ( ! deedni_can_search() ) {
		return deedni_template( 'zoeken-gesloten', array( 'reason' => 'controle' ) );
	}

	$filters = deedni_search_filters();
	$paged   = max( 1, get_query_var( 'paged' ), isset( $_GET['pagina'] ) ? absint( $_GET['pagina'] ) : 1 );
	$query   = deedni_search_query( $filters, $paged );

	return deedni_message_from_url() . deedni_template(
		'zoeken',
		array(
			'filters' => $filters,
			'query'   => $query,
			'paged'   => $paged,
		)
	);
}

/* ---------- Dashboard ---------- */

function deedni_sc_dashboard() {
	if ( ! is_user_logged_in() ) {
		return '';
	}
	$out = deedni_message_from_url();

	if ( deedni_is_werkgever() ) {
		$offers = get_posts(
			array(
				'post_type'      => 'aanbod',
				'post_status'    => 'publish',
				'author'         => get_current_user_id(),
				'posts_per_page' => 100,
			)
		);
		return $out . deedni_template( 'dashboard-werkgever', array( 'offers' => $offers, 'bedrijf_id' => deedni_profile_id() ) );
	}

	if ( deedni_is_kandidaat() ) {
		$kandidaat_id = deedni_profile_id();
		$offers       = $kandidaat_id ? get_posts(
			array(
				'post_type'      => 'aanbod',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'meta_query'     => array( array( 'key' => 'kandidaat', 'value' => $kandidaat_id ) ),
			)
		) : array();
		return $out . deedni_template( 'dashboard-kandidaat', array( 'offers' => $offers, 'kandidaat_id' => $kandidaat_id ) );
	}

	return $out . deedni_notice(
		'Je bent ingelogd als beheerder. Bekijk <a href="' . esc_url( admin_url( 'edit.php?post_type=kandidaat' ) ) . '">kandidaten</a>, <a href="' . esc_url( admin_url( 'edit.php?post_type=aanbod' ) ) . '">aanbiedingen</a> of <a href="' . esc_url( deedni_page_url( 'zoeken' ) ) . '">zoek zoals een werkgever</a>.'
	);
}

/* ---------- Kandidaatprofiel ---------- */

/** Het profiel zoals een werkgever (of de kandidaat zelf) het ziet. Het thema roept dit aan in single-kandidaat.php. */
function deedni_render_kandidaat( $kandidaat_id ) {
	$user_id = get_current_user_id();
	$open    = $user_id ? deedni_offers_between( $kandidaat_id, $user_id, array( 'verstuurd' ) ) : array();
	return deedni_message_from_url() . deedni_template(
		'kandidaat',
		array(
			'id'          => $kandidaat_id,
			'own'         => (int) get_post_field( 'post_author', $kandidaat_id ) === $user_id,
			'has_contact' => deedni_has_contact_access( $kandidaat_id ),
			'can_offer'   => deedni_can_search() && (int) get_post_field( 'post_author', $kandidaat_id ) !== $user_id,
			'open_offer'  => $open ? $open[0] : 0,
			'flash'       => deedni_flash( 'aanbod' ),
		)
	);
}

/** Zonder deedni-thema: het profiel tonen in plaats van de gewone berichtinhoud. */
add_filter(
	'the_content',
	function ( $content ) {
		if ( is_singular( 'kandidaat' ) && in_the_loop() && is_main_query() && ! current_theme_supports( 'deedni' ) ) {
			return deedni_render_kandidaat( get_the_ID() );
		}
		return $content;
	},
	20
);
