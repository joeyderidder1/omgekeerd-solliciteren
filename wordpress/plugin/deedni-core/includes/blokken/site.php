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
		'beschrijving' => 'Logo en het menu. Elke rol (bezoeker, kandidaat, werkgever) krijgt een eigen menu. De links zelf pas je aan onder Weergave > Menu\'s.',
		'icoon'        => 'menu',
		'multiple'     => false,
		'uitleg'       => 'De links in het menu pas je aan onder Weergave > Menu\'s. Daar staat een menu voor bezoekers, kandidaten en werkgevers, plus de accountmenu\'s onder de initialen.',
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
	$zoek = deedni_can_search() ? ' data-zoek-url="' . esc_url( deedni_page_url( 'zoeken' ) ) . '"' : '';
	$h   .= '<header class="site-header" id="site-header" data-rol="' . esc_attr( $rol ) . '"' . $zoek . '><div class="wrap header-row">';
	$h .= deedni_site_logo( $a );
	$h .= '<button class="menu-toggle" id="menu-toggle" type="button" aria-expanded="false" aria-controls="site-nav"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>Menu</button>';
	$h .= '<nav class="site-nav" id="site-nav" aria-label="Hoofdmenu">';
	foreach ( $menu as $i => $item ) {
		$teller = $item['teller'] ? '<span class="nav-badge"><span class="visually-hidden">, </span>' . (int) $item['teller'] . '<span class="visually-hidden"> nieuw</span></span>' : '';
		if ( ! empty( $item['kinderen'] ) ) {
			// Uitklapmenu: opent met hover (met een "veilige driehoek" naar het menu toe) of met klikken.
			$sub = 'sub-' . $rol . '-' . $i;
			$h  .= '<div class="uitklap sub' . ( $item['huidig'] ? ' is-current' : '' ) . '" data-uitklap>';
			$h  .= '<button type="button" class="sub-knop uitklap-knop" aria-expanded="false" aria-controls="' . esc_attr( $sub ) . '">' . esc_html( $item['tekst'] ) . $teller . str_replace( '<svg ', '<svg class="chev" ', deedni_icon( 'chev', 14 ) ) . '</button>';
			$h  .= '<ul class="uitklap-menu sub-menu" id="' . esc_attr( $sub ) . '">';
			$url = trim( (string) $item['url'] );
			if ( $url && '#' !== $url && false === strpos( $url, '#' ) ) {
				$h .= '<li><a href="' . esc_url( $url ) . '"' . ( $item['huidig'] && ! array_filter( wp_list_pluck( $item['kinderen'], 'huidig' ) ) ? ' aria-current="page"' : '' ) . '>' . esc_html( $item['tekst'] ) . '</a></li>';
			}
			foreach ( $item['kinderen'] as $kind ) {
				$h .= '<li><a href="' . esc_url( $kind['url'] ) . '"' . ( $kind['huidig'] ? ' aria-current="page"' : '' ) . '>' . esc_html( $kind['tekst'] ) . '</a></li>';
			}
			$h .= '</ul></div>';
			continue;
		}
		$h .= '<a href="' . esc_url( $item['url'] ) . '"' . ( $item['huidig'] ? ' aria-current="page"' : '' ) . '>' . esc_html( $item['tekst'] ) . $teller . '</a>';
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
			$wat  = 'kandidaat';
		}
		$account    = deedni_menu_items( 'deedni-account-' . $rol );
		$in_account = (bool) array_filter( wp_list_pluck( $account, 'huidig' ) );
		$h .= '<div class="acct uitklap' . ( $in_account ? ' is-current' : '' ) . '" data-uitklap>';
		$h .= '<button type="button" class="who uitklap-knop" id="acct-knop" aria-expanded="false" aria-controls="acct-menu"><span><span class="visually-hidden">Account van </span>' . esc_html( $naam ) . '</span><b aria-hidden="true">' . esc_html( deedni_initialen( $naam ) ) . '</b>' . str_replace( '<svg ', '<svg class="chev" ', deedni_icon( 'chev', 14 ) ) . '</button>';
		$h .= '<ul class="acct-menu uitklap-menu" id="acct-menu"><li class="acct-wie">' . esc_html( $naam ) . ', ingelogd als ' . esc_html( $wat ) . '</li>';
		// Snel naar een pagina met Ctrl K / Cmd K. deedni.js toont deze regel alleen met een toetsenbord en muis.
		$h .= '<li class="snel-li" hidden><button type="button" class="snel-knop" data-snel>Snel naar…<kbd class="toets" data-toets="k"></kbd></button></li>';
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
		'beschrijving' => 'Onderkant van elke pagina: een donker blok met een oproep en knop, het logo met een korte tekst, kolommen met links en een regel met het jaartal.',
		'icoon'        => 'editor-insertmore',
		'multiple'     => false,
		'uitleg'       => 'De kolommen pas je aan onder Weergave > Menu\'s > "Links onderaan (footer)". Een link met links eronder (sleep ze een stap naar rechts) wordt een kolom met een kop. Losse links zonder links eronder komen in de onderste regel, naast het jaartal. Is het menu leeg, dan staan er standaardkolommen.',
		'velden'       => array(
			'cta_wie'    => array(
				'type'      => 'keuze',
				'label'     => 'Wie ziet het donkere blok?',
				'opties'    => array(
					'bezoeker' => 'Alleen bezoekers (niet ingelogd)',
					'iedereen' => 'Iedereen',
					'niemand'  => 'Niemand (blok uit)',
				),
				'standaard' => 'bezoeker',
				'groep'     => 'Oproep',
			),
			'cta_kop'    => array( 'type' => 'text', 'label' => 'Kop', 'standaard' => 'Laat je vinden door bedrijven die bij je passen.', 'groep' => 'Oproep' ),
			'cta_tekst'  => array( 'type' => 'text', 'label' => 'Tekst', 'standaard' => 'Gratis voor werkzoekenden. Bedrijven zoeken anoniem en betalen pas bij een aanname.', 'groep' => 'Oproep' ),
			'cta_knop'   => array( 'type' => 'text', 'label' => 'Knop', 'standaard' => 'Maak gratis je profiel', 'groep' => 'Oproep' ),
			'cta_link'   => array( 'type' => 'link', 'label' => 'Link van de knop', 'standaard' => 'pagina:registreren', 'groep' => 'Oproep' ),
			'cta2_tekst' => array( 'type' => 'text', 'label' => 'Tweede link', 'standaard' => 'Ik zoek personeel', 'groep' => 'Oproep' ),
			'cta2_link'  => array( 'type' => 'link', 'label' => 'Waar de tweede link heen gaat', 'standaard' => 'pagina:zoeken', 'groep' => 'Oproep' ),
			'logo_tekst' => array( 'type' => 'text', 'label' => 'Logo als tekst', 'standaard' => '', 'help' => 'Leeg laten = de naam van de site.', 'groep' => 'Logo en tekst' ),
			'logo'       => array( 'type' => 'image', 'label' => 'Logo als afbeelding (optioneel)', 'standaard' => array(), 'groep' => 'Logo en tekst' ),
			'slogan'     => array( 'type' => 'text', 'label' => 'Korte tekst onder het logo', 'standaard' => 'Bedrijven kiezen zelf hun nieuwe collega.', 'groep' => 'Logo en tekst' ),
			'tekst'      => array( 'type' => 'text', 'label' => 'Onderste regel', 'standaard' => '© {jaar} deedni.', 'help' => '{jaar} wordt het huidige jaar.', 'groep' => 'Logo en tekst' ),
		),
		'render'       => 'deedni_render_voet',
	)
);

/** Standaardkolommen als het footermenu geen kolommen heeft. Alleen pagina's die bestaan. */
function deedni_voet_standaard_kolommen() {
	$pagina = function ( $key, $tekst ) {
		$id = deedni_page_id( $key );
		return $id && 'publish' === get_post_status( $id ) ? array( 'tekst' => $tekst, 'url' => get_permalink( $id ), 'huidig' => is_page( $id ) ) : null;
	};
	$slug = function ( $slugs, $tekst ) {
		foreach ( (array) $slugs as $s ) {
			$p = get_page_by_path( $s );
			if ( $p && 'publish' === $p->post_status ) {
				return array( 'tekst' => $tekst, 'url' => get_permalink( $p ), 'huidig' => is_page( $p->ID ) );
			}
		}
		return null;
	};
	$home     = home_url( '/' );
	$platform = array(
		array( 'tekst' => 'Zo werkt het', 'url' => $home . '#zo-werkt-het', 'huidig' => false ),
		array( 'tekst' => 'Prijzen', 'url' => $home . '#prijzen', 'huidig' => false ),
		$pagina( 'zoeken', 'Kandidaten zoeken' ),
	);
	if ( ! is_user_logged_in() ) {
		$platform[] = $pagina( 'inloggen', 'Inloggen' );
	}
	$privacy = get_privacy_policy_url();
	return array(
		array( 'tekst' => 'Platform', 'kinderen' => array_values( array_filter( $platform ) ) ),
		array( 'tekst' => 'Over deedni', 'kinderen' => array_values( array_filter( array( $pagina( 'over-ons', 'Over ons' ), $pagina( 'blog', 'Blog' ), $pagina( 'nieuws', 'Nieuws' ), $pagina( 'contact', 'Contact' ) ) ) ) ),
		array( 'tekst' => 'Juridisch', 'kinderen' => array_values( array_filter( array(
			$privacy ? array( 'tekst' => 'Privacy', 'url' => $privacy, 'huidig' => false ) : null,
			$slug( array( 'algemene-voorwaarden', 'voorwaarden' ), 'Algemene voorwaarden' ),
			$slug( array( 'cookies', 'cookiebeleid', 'cookieverklaring' ), 'Cookies' ),
		) ) ) ),
	);
}

function deedni_render_voet( $a ) {
	$a = wp_parse_args( $a, array( 'cta_wie' => 'bezoeker', 'cta_kop' => '', 'cta_tekst' => '', 'cta_knop' => '', 'cta_link' => '', 'cta2_tekst' => '', 'cta2_link' => '', 'slogan' => '', 'tekst' => '' ) );
	$h = '<footer class="site-footer site-footer--v2"><div class="wrap footer-stapel">';

	$cta = 'iedereen' === $a['cta_wie'] || ( 'bezoeker' === $a['cta_wie'] && 'bezoeker' === deedni_rol() );
	if ( $cta && '' !== trim( (string) $a['cta_kop'] ) ) {
		$h .= '<div class="footer-cta"><span class="footer-gloed" aria-hidden="true"></span><div class="footer-cta-tekst"><p class="footer-cta-kop">' . esc_html( $a['cta_kop'] ) . '</p>';
		if ( $a['cta_tekst'] ) {
			$h .= '<p class="footer-cta-lead">' . esc_html( $a['cta_tekst'] ) . '</p>';
		}
		$h .= '</div><div class="footer-cta-knoppen">';
		if ( $a['cta_knop'] ) {
			$h .= '<a class="footer-pil" href="' . esc_url( deedni_link( $a['cta_link'] ) ) . '">' . esc_html( $a['cta_knop'] ) . '<span class="footer-pil-ico" aria-hidden="true">' . deedni_icon( 'arrow', 18 ) . '</span></a>';
		}
		if ( $a['cta2_tekst'] ) {
			$h .= '<a class="footer-link2" href="' . esc_url( deedni_link( $a['cta2_link'] ) ) . '">' . esc_html( $a['cta2_tekst'] ) . '</a>';
		}
		$h .= '</div></div>';
	}

	// Kolommen: menu-items met kinderen. Losse items gaan naar de onderste regel.
	$menu     = deedni_menu_items( 'deedni-voet' );
	$kolommen = array_values( array_filter( $menu, function ( $l ) {
		return ! empty( $l['kinderen'] );
	} ) );
	$los      = array_values( array_filter( $menu, function ( $l ) {
		return empty( $l['kinderen'] );
	} ) );
	if ( ! $kolommen ) {
		$kolommen = deedni_voet_standaard_kolommen();
		// Staat de privacyverklaring al in een kolom, dan niet nog eens onderaan.
		$los = array_values( array_filter( $los, function ( $l ) {
			return untrailingslashit( $l['url'] ) !== untrailingslashit( (string) get_privacy_policy_url() );
		} ) );
	}
	$h .= '<div class="footer-midden"><div class="footer-merk">' . deedni_site_logo( $a );
	if ( $a['slogan'] ) {
		$h .= '<p>' . esc_html( $a['slogan'] ) . '</p>';
	}
	$h .= '</div><div class="footer-kolommen">';
	foreach ( $kolommen as $i => $kol ) {
		if ( empty( $kol['kinderen'] ) ) {
			continue;
		}
		$kid = 'voet-kol-' . $i;
		$h  .= '<nav class="footer-kol" aria-labelledby="' . $kid . '"><p class="footer-kol-kop" id="' . $kid . '">' . esc_html( $kol['tekst'] ) . '</p><ul>';
		foreach ( $kol['kinderen'] as $l ) {
			$h .= '<li><a href="' . esc_url( $l['url'] ) . '"' . ( ! empty( $l['huidig'] ) ? ' aria-current="page"' : '' ) . '>' . esc_html( $l['tekst'] ) . '</a></li>';
		}
		$h .= '</ul></nav>';
	}
	$h .= '</div></div><div class="footer-onder"><span>' . esc_html( str_replace( '{jaar}', gmdate( 'Y' ), $a['tekst'] ) ) . '</span>';
	if ( $los ) {
		$h .= '<nav aria-label="Links onderaan"><ul>';
		foreach ( $los as $l ) {
			$h .= '<li><a href="' . esc_url( $l['url'] ) . '"' . ( $l['huidig'] ? ' aria-current="page"' : '' ) . '>' . esc_html( $l['tekst'] ) . '</a></li>';
		}
		$h .= '</ul></nav>';
	}
	return $h . '</div></div></footer>';
}
