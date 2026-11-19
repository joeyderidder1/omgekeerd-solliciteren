<?php
/**
 * Kop (menu per rol) en voet van de site. Ze staan in de sjabloondelen Header en Footer van het thema.
 */

defined( 'ABSPATH' ) || exit;

function deedni_site_logo( $a, $class = 'logo' ) {
	$tekst = '' !== trim( (string) $a['logo_tekst'] ) ? $a['logo_tekst'] : get_bloginfo( 'name' );
	$inner = ! empty( $a['logo']['url'] ) ? deedni_beeld( $a['logo'], array( 'class' => 'logo-img', 'alt' => $tekst ) ) : esc_html( $tekst ) . '<i aria-hidden="true"></i>';
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( home_url( '/' ) ) . '" aria-label="' . esc_attr( $tekst . ', naar de homepage' ) . '">' . $inner . '</a>';
}

deedni_blok(
	'kop',
	array(
		'titel'        => 'Kop met menu',
		'beschrijving' => 'Logo en het menu. Elke rol (bezoeker, marketeer, werkgever) krijgt een eigen menu. De links zelf pas je aan onder Weergave > Menu\'s.',
		'icoon'        => 'menu',
		'multiple'     => false,
		'uitleg'       => 'De links in het menu pas je aan onder Weergave > Menu\'s. Daar staat een menu voor bezoekers, marketeers en werkgevers, plus de accountmenu\'s onder de initialen.',
		'velden'       => array(
			'logo_tekst'   => array( 'type' => 'text', 'label' => 'Logo als tekst', 'standaard' => '', 'help' => 'Leeg laten = de naam van de site (Instellingen > Algemeen).', 'groep' => 'Logo' ),
			'logo'         => array( 'type' => 'image', 'label' => 'Logo als afbeelding (optioneel)', 'standaard' => array(), 'help' => 'Kies je een afbeelding, dan staat die er in plaats van de tekst.', 'groep' => 'Logo' ),
			'knop_tekst'   => array( 'type' => 'text', 'label' => 'Knop voor bezoekers', 'standaard' => 'Account maken', 'groep' => 'Knoppen' ),
			'knop_link'    => array( 'type' => 'link', 'label' => 'Link van de knop', 'standaard' => 'pagina:registreren', 'groep' => 'Knoppen' ),
			'uitlog_tekst' => array( 'type' => 'text', 'label' => 'Tekst voor uitloggen', 'standaard' => 'Uitloggen', 'groep' => 'Knoppen' ),
		),
		'render'       => 'deedni_render_kop',
	)
);

function deedni_render_kop( $a ) {
	$rol  = deedni_rol();
	$menu = deedni_menu_items( 'deedni-' . $rol );

	$h  = '<div class="deedni-kop">';
	$h .= '<a class="skip" href="#inhoud">Naar de inhoud</a>';
	$h .= '<header class="site-header" id="site-header" data-rol="' . esc_attr( $rol ) . '"><div class="wrap header-row">';
	$h .= deedni_site_logo( $a );
	$h .= '<button class="menu-toggle" id="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>Menu</button>';
	$h .= '<nav class="site-nav" id="site-nav" aria-label="Hoofdmenu">';
	foreach ( $menu as $item ) {
		$teller = $item['teller'] ? '<span class="nav-badge"><span class="visually-hidden">, </span>' . (int) $item['teller'] . '<span class="visually-hidden"> nieuw</span></span>' : '';
		$h     .= '<a href="' . esc_url( $item['url'] ) . '"' . ( $item['huidig'] ? ' aria-current="page"' : '' ) . '>' . esc_html( $item['tekst'] ) . $teller . '</a>';
	}

	if ( 'bezoeker' === $rol ) {
		if ( $a['knop_tekst'] ) {
			$h .= '<a class="btn btn--primary btn--small nav-cta" href="' . esc_url( deedni_link( $a['knop_link'] ) ) . '">' . esc_html( $a['knop_tekst'] ) . '</a>';
		}
	} else {
		$user = wp_get_current_user();
		if ( 'werkgever' === $rol ) {
			$b    = deedni_profile_id( $user->ID, 'bedrijf' );
			$naam = $b ? get_the_title( $b ) : $user->display_name;
			$wat  = 'werkgever';
		} else {
			$k    = deedni_profile_id( $user->ID, 'kandidaat' );
			$naam = $k && deedni_field( $k, 'voornaam' ) ? deedni_field( $k, 'voornaam' ) : ( $user->first_name ? $user->first_name : $user->display_name );
			$wat  = 'marketeer';
		}
		$account    = deedni_menu_items( 'deedni-account-' . $rol );
		$in_account = (bool) array_filter( wp_list_pluck( $account, 'huidig' ) );
		$h .= '<div class="acct' . ( $in_account ? ' is-current' : '' ) . '">';
		$h .= '<button type="button" class="who" id="acct-knop" aria-expanded="false" aria-controls="acct-menu"><span><span class="visually-hidden">Account van </span>' . esc_html( $naam ) . '</span><b aria-hidden="true">' . esc_html( deedni_initialen( $naam ) ) . '</b>' . str_replace( '<svg ', '<svg class="chev" ', deedni_icon( 'chev', 14 ) ) . '</button>';
		$h .= '<ul class="acct-menu" id="acct-menu"><li class="acct-wie">' . esc_html( $naam ) . ', ingelogd als ' . esc_html( $wat ) . '</li>';
		foreach ( $account as $item ) {
			$h .= '<li><a href="' . esc_url( $item['url'] ) . '"' . ( $item['huidig'] ? ' aria-current="page"' : '' ) . '>' . esc_html( $item['tekst'] ) . '</a></li>';
		}
		if ( deedni_is_beheerder() ) {
			$h .= '<li><a href="' . esc_url( admin_url() ) . '">Naar het dashboard van WordPress</a></li>';
		}
		$h .= '<li><a class="nav-out" href="' . esc_url( wp_logout_url() ) . '">' . esc_html( $a['uitlog_tekst'] ) . '</a></li>';
		$h .= '</ul></div>';
	}
	$h .= '</nav></div></header></div>';
	return $h;
}

deedni_blok(
	'voet',
	array(
		'titel'        => 'Voet',
		'beschrijving' => 'Onderkant van elke pagina: logo, een korte tekst en links (Weergave > Menu\'s > Links onderaan).',
		'icoon'        => 'editor-insertmore',
		'multiple'     => false,
		'velden'       => array(
			'logo_tekst' => array( 'type' => 'text', 'label' => 'Logo als tekst', 'standaard' => '', 'help' => 'Leeg laten = de naam van de site.' ),
			'logo'       => array( 'type' => 'image', 'label' => 'Logo als afbeelding (optioneel)', 'standaard' => array() ),
			'tekst'      => array( 'type' => 'text', 'label' => 'Tekst', 'standaard' => '© {jaar} deedni. Bedrijven kiezen zelf hun nieuwe collega.', 'help' => '{jaar} wordt het huidige jaar.' ),
		),
		'render'       => 'deedni_render_voet',
	)
);

function deedni_render_voet( $a ) {
	$h  = '<footer class="site-footer"><div class="wrap footer-row">';
	$h .= deedni_site_logo( $a );
	$h .= '<span>' . esc_html( str_replace( '{jaar}', gmdate( 'Y' ), $a['tekst'] ) ) . '</span>';
	$links = deedni_menu_items( 'deedni-voet' );
	if ( ! $links && get_privacy_policy_url() ) {
		$links = array( array( 'tekst' => 'Privacyverklaring', 'url' => get_privacy_policy_url(), 'huidig' => false ) );
	}
	if ( $links ) {
		$h .= '<nav class="rolwissel" aria-label="Links onderaan">';
		foreach ( $links as $l ) {
			$h .= '<a href="' . esc_url( $l['url'] ) . '"' . ( $l['huidig'] ? ' aria-current="true"' : '' ) . '>' . esc_html( $l['tekst'] ) . '</a>';
		}
		$h .= '</nav>';
	}
	return $h . '</div></footer>';
}
