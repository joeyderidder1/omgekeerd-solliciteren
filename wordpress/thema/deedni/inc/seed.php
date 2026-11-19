<?php
/**
 * Startinhoud: maakt een homepage met dezelfde secties en teksten als het ontwerp, als ACF-blokken.
 * Draait bij het activeren van het thema en via de knop "Homepage vullen" (Thema-instellingen of de lege homepage).
 */

defined( 'ABSPATH' ) || exit;

/** De blokken van de standaard homepage. */
function deedni_seed_blocks() {
	$usps = function ( $items ) {
		return array_map(
			function ( $t ) {
				return array( 'tekst' => $t );
			},
			$items
		);
	};

	return array(
		array(
			'acf_fc_layout'               => 'hero',
			'toon_keuze'                  => 1,
			'knop_2_tekst'                => 'Zo werkt het',
			'knop_2_link'                 => array( 'title' => 'Zo werkt het', 'url' => '#zo-werkt-het', 'target' => '' ),
			'notitie'                     => 'Nu voor **online marketing**. Meer branches volgen.',
			'marketeer_keuze'             => 'Ik zoek werk',
			'marketeer_titel'             => 'Bedrijven komen *naar jou*.',
			'marketeer_intro'             => 'Maak een anoniem profiel als online marketeer. Bedrijven sturen je een aanbod met functie en salaris. Jij beslist wie je naam te zien krijgt.',
			'marketeer_knop_tekst'        => 'Maak gratis je profiel',
			'marketeer_usps'              => $usps( array( 'Altijd gratis', 'Anoniem tot jouw ja', 'Elk aanbod met salaris' ) ),
			'marketeer_demo_label'        => 'Zo ziet een aanbod eruit',
			'marketeer_demo_badge'        => 'Nieuw aanbod',
			'marketeer_demo_meta'         => 'reageer binnen 5 werkdagen',
			'marketeer_demo_titel'        => 'Senior performance marketeer',
			'marketeer_demo_subtitel'     => 'Online marketingbureau · Utrecht',
			'marketeer_demo_feiten'       => array(
				array( 'label' => 'Salaris p/m', 'waarde' => '€4.200–4.800' ),
				array( 'label' => 'Uren', 'waarde' => '32–40' ),
				array( 'label' => 'Werkplek', 'waarde' => 'Hybride' ),
			),
			'marketeer_demo_quote'        => '"Je cases in e-commerce sluiten precies aan op onze klanten. Je krijgt bij ons eigen accounts en ruimte om een team op te bouwen."',
			'marketeer_demo_vergrendeld'  => 0,
			'marketeer_demo_knop_1'       => 'Interesse, deel mijn naam',
			'marketeer_demo_reactie_1'    => 'Je naam en contactgegevens zijn gedeeld. Het bureau neemt contact met je op.',
			'marketeer_demo_knop_2'       => 'Nee, bedankt',
			'marketeer_demo_reactie_2'    => 'Afgewezen. Het bureau krijgt een nette afwijzing en ziet je naam niet.',
			'werkgever_keuze'             => 'Ik zoek personeel',
			'werkgever_titel'             => 'Kies zelf je nieuwe *collega*.',
			'werkgever_intro'             => 'Zoek tussen online marketeers die openstaan voor een overstap. Stuur een aanbod met salaris. Zegt iemand ja, dan krijg je naam, cv en contactgegevens.',
			'werkgever_knop_tekst'        => 'Bekijk kandidaten',
			'werkgever_usps'              => $usps( array( 'Zoeken is gratis', 'Betaal pas bij resultaat', 'Alleen actuele profielen' ) ),
			'werkgever_demo_label'        => 'Zo ziet een profiel eruit',
			'werkgever_demo_badge'        => 'Actief op zoek',
			'werkgever_demo_meta'         => 'Kandidaat K-2041',
			'werkgever_demo_titel'        => 'Senior SEA- en performance marketeer',
			'werkgever_demo_subtitel'     => '7 jaar ervaring · Utrecht + 30 km · hybride',
			'werkgever_demo_feiten'       => array(),
			'werkgever_demo_tags'         => 'Google Ads, Meta Ads, GA4, Merchant Center',
			'werkgever_demo_vergrendeld'  => 1,
			'werkgever_demo_knop_1'       => 'Stuur een aanbod',
			'werkgever_demo_reactie_1'    => 'Aanbod verstuurd. Zegt K-2041 ja, dan zie je naam, cv en contactgegevens.',
			'anker'                       => '',
		),
		array(
			'acf_fc_layout' => 'chips',
			'label'         => 'Specialisten in online marketing',
			'labels'        => $usps( array( 'SEA en performance', 'SEO en content', 'Data en analytics', 'Marketing automation en CRM', 'CRO en UX', 'Social advertising', 'E-mailmarketing' ) ),
			'achtergrond'   => 'standaard',
		),
		array(
			'acf_fc_layout'  => 'stappen',
			'boventitel'     => 'Zo werkt het',
			'titel'          => 'Omgekeerd solliciteren, in vijf stappen',
			'intro'          => 'Het bedrijf doet de eerste stap. De marketeer houdt de regie.',
			'stappen'        => array(
				array( 'wie' => 'Marketeer', 'stijl' => 'licht', 'titel' => 'Maakt een anoniem profiel', 'tekst' => 'Specialisme, tools, ervaring en wensen. Geen naam of foto.' ),
				array( 'wie' => 'Werkgever', 'stijl' => 'donker', 'titel' => 'Zoekt en filtert', 'tekst' => 'Op specialisme, tools, regio en beschikbaarheid.' ),
				array( 'wie' => 'Werkgever', 'stijl' => 'donker', 'titel' => 'Stuurt een aanbod', 'tekst' => 'Met functie, salarisrange en een persoonlijk bericht.' ),
				array( 'wie' => 'Marketeer', 'stijl' => 'licht', 'titel' => 'Zegt ja of nee', 'tekst' => 'Nee is anoniem. Het bedrijf krijgt een nette afwijzing.' ),
				array( 'wie' => 'Samen', 'stijl' => 'accent', 'titel' => 'Maken kennis', 'tekst' => 'Na een ja ziet het bedrijf naam, cv en contactgegevens.' ),
			),
			'legenda_licht'  => 'Marketeer',
			'legenda_donker' => 'Werkgever',
			'achtergrond'    => 'wit',
			'anker'          => 'zo-werkt-het',
		),
		array(
			'acf_fc_layout' => 'kolommen',
			'boventitel'    => 'Privacy',
			'titel'         => 'Beoordeeld op wat je kunt, niet op wie je bent',
			'intro'         => 'Je profiel bestaat uit drie lagen. Bedrijven zien wat ze nodig hebben om te kiezen. Wie je bent, horen ze pas als jij dat wilt.',
			'kolommen'      => array(
				array( 'icoon' => 'oog', 'stijl' => 'licht', 'titel' => 'Iedereen ziet', 'items' => "Functie en specialisme\nTools en certificaten\nJaren ervaring en soort bedrijven\nRegio, uren en werkplek\nBeschikbaarheid en salarisindicatie" ),
				array( 'icoon' => 'plus', 'stijl' => 'licht', 'titel' => 'Jij kiest of je het toevoegt', 'items' => "Een korte introductie\nCases en portfolio, zonder klantnamen\nWat je zoekt in een werkgever\nReferenties" ),
				array( 'icoon' => 'slot', 'stijl' => 'donker', 'titel' => 'Pas na jouw ja', 'items' => "Naam en contactgegevens\nJe volledige cv\nJe huidige werkgever\nLinkedIn-profiel" ),
			),
			'notitie'       => 'Zet je huidige werkgever op je blokkeerlijst, dan ziet die je profiel nooit. We vragen niet naar leeftijd, geslacht, afkomst of gezondheid, en bedrijven kunnen daar niet op filteren.',
			'achtergrond'   => 'standaard',
			'anker'         => 'privacy',
		),
		array(
			'acf_fc_layout' => 'vergelijking',
			'boventitel'    => 'Waarom deedni',
			'titel'         => 'Anders dan een vacaturesite of bureau',
			'kop_1'         => 'Vacaturesite',
			'kop_2'         => 'Recruitmentbureau',
			'kop_3'         => 'deedni',
			'rijen'         => array(
				array( 'label' => 'Wie zet de eerste stap', 'waarde_1' => 'De kandidaat solliciteert', 'waarde_2' => 'De recruiter belt', 'waarde_3' => 'Het bedrijf stuurt een aanbod' ),
				array( 'label' => 'Salaris vooraf bekend', 'waarde_1' => 'Soms', 'waarde_2' => 'Meestal pas later', 'waarde_3' => 'Altijd' ),
				array( 'label' => 'Anoniem tot jouw ja', 'waarde_1' => 'Nee', 'waarde_2' => 'Nee', 'waarde_3' => 'Ja' ),
				array( 'label' => 'Bereikt mensen met een baan', 'waarde_1' => 'Weinig', 'waarde_2' => 'Ja', 'waarde_3' => 'Ja' ),
				array( 'label' => 'Kosten voor werkgevers', 'waarde_1' => 'Per advertentie of klik', 'waarde_2' => 'Vaak 20 tot 25% van een jaarsalaris', 'waarde_3' => 'Alleen bij resultaat' ),
			),
			'achtergrond'   => 'wit',
		),
		array(
			'acf_fc_layout'      => 'prijzen',
			'boventitel'         => 'Prijzen voor werkgevers',
			'titel'              => 'Betaal pas bij resultaat',
			'intro'              => 'Zoeken, profielen bekijken en aanbiedingen sturen is gratis. Je betaalt als een marketeer ja zegt, of als je iemand aanneemt. Voor marketeers is deedni altijd gratis.',
			'pakketten'          => array(
				array( 'titel' => 'Per contact', 'voor_wie' => 'Voor af en toe een vacature', 'prijs' => '€195', 'toelichting' => 'per geaccepteerd aanbod', 'uitgelicht' => 0, 'items' => "Geen abonnement\nJe betaalt alleen als de marketeer ja zegt\n5 aanbiedingen tegelijk open" ),
				array( 'titel' => 'Abonnement', 'voor_wie' => 'Voor bureaus die vaker werven', 'prijs' => '€399', 'toelichting' => 'per maand', 'uitgelicht' => 1, 'items' => "3 geaccepteerde contacten per maand\n3 gebruikers en gedeelde shortlists\nUitgelicht bedrijfsprofiel" ),
				array( 'titel' => 'Bij aanname', 'voor_wie' => 'Geen kosten vooraf', 'prijs' => '10%', 'toelichting' => 'van het bruto jaarsalaris', 'uitgelicht' => 0, 'items' => "Je betaalt pas bij een getekend contract\nGeen kosten als het niet lukt\nOnbeperkt aanbiedingen sturen" ),
			),
			'reken_tonen'        => 1,
			'reken_titel'        => 'Wat kost een aanname?',
			'reken_hint'         => 'Bruto jaarsalaris van de nieuwe collega',
			'reken_min'          => 35000,
			'reken_max'          => 90000,
			'reken_start'        => 55000,
			'reken_procent'      => 10,
			'reken_bureau_min'   => 20,
			'reken_bureau_max'   => 25,
			'reken_label_ons'    => 'deedni, bij aanname',
			'reken_label_bureau' => 'Recruitmentbureau',
			'reken_voetnoot'     => 'Rekenvoorbeeld, exclusief btw. Bureautarieven verschillen; we rekenen met 20 tot 25%.',
			'achtergrond'        => 'standaard',
			'anker'              => 'prijzen',
		),
		array(
			'acf_fc_layout' => 'faq',
			'titel'         => 'Veelgestelde vragen',
			'vragen'        => array(
				array( 'vraag' => 'Wie ziet mijn profiel?', 'antwoord' => 'Bedrijven die op deedni zoeken, behalve de bedrijven op je blokkeerlijst. Ze zien je vaardigheden, ervaring en wensen, maar niet je naam, foto of contactgegevens.' ),
				array( 'vraag' => 'Wat kost het voor mij als marketeer?', 'antwoord' => 'Niets. Een profiel maken, je cv en portfolio uploaden en aanbiedingen ontvangen is altijd gratis.' ),
				array( 'vraag' => 'Wanneer ziet een bedrijf mijn naam?', 'antwoord' => 'Pas als jij een aanbod van dat bedrijf accepteert. Andere bedrijven blijven alleen je anonieme profiel zien.' ),
				array( 'vraag' => 'Wat als een kandidaat nee zegt?', 'antwoord' => 'Dan krijgt het bedrijf een nette afwijzing en blijft de kandidaat anoniem. Met Per contact betaal je dan niets.' ),
				array( 'vraag' => 'Waarom kan ik niet filteren op leeftijd of geslacht?', 'antwoord' => 'Omdat die niets zeggen over vakmanschap. We vragen er ook niet naar, zodat je kiest op ervaring, tools en resultaten.' ),
			),
			'achtergrond'   => 'wit',
			'anker'         => 'vragen',
		),
		array(
			'acf_fc_layout'   => 'aanmelden',
			'titel'           => 'Begin vandaag',
			'tekst_marketeer' => 'Maak in een paar minuten je anonieme profiel. Bedrijven sturen je een aanbod met functie en salaris, en jij beslist wie je naam te zien krijgt.',
			'tekst_werkgever' => 'Maak een account voor je bedrijf. Na een korte controle zoek je tussen online marketeers en stuur je een aanbod. Je betaalt pas bij resultaat.',
			'usps'            => $usps( array( 'Gratis voor marketeers', 'Anoniem tot jouw ja', 'Account opzeggen kan altijd' ) ),
			'achtergrond'     => 'standaard',
			'anker'           => 'aanmelden',
		),
	);
}

/**
 * Zet blokken om naar het formaat dat ACF verwacht bij opslaan: elke lay-out bevat één clone-veld
 * (block_{lay-out}), en daarin zitten de velden van het blok.
 */
function deedni_seed_rows( $blocks ) {
	$field   = acf_get_field( 'field_dn_blokken' );
	$clones  = array();
	foreach ( (array) $field['layouts'] as $layout ) {
		$first = isset( $layout['sub_fields'][0] ) ? $layout['sub_fields'][0] : array();
		// ACF geeft de clone soms al uitgeklapt terug; dan horen de velden direct in de rij.
		if ( isset( $first['type'] ) && 'clone' === $first['type'] ) {
			$clones[ $layout['name'] ] = $first['name'];
		}
	}
	return array_map(
		function ( $block ) use ( $clones ) {
			$layout = $block['acf_fc_layout'];
			if ( empty( $clones[ $layout ] ) ) {
				return $block;
			}
			unset( $block['acf_fc_layout'] );
			return array( 'acf_fc_layout' => $layout, $clones[ $layout ] => $block );
		},
		$blocks
	);
}

/**
 * Maakt (of vult) de homepage. Bestaande blokken worden alleen overschreven met $force.
 * Geeft het ID van de homepage terug.
 */
function deedni_seed_homepage( $force = false ) {
	if ( ! function_exists( 'update_field' ) || ! deedni_has_acf_pro() ) {
		return 0;
	}
	$front_id = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
	if ( ! $front_id ) {
		$front_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Home',
			)
		);
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_id );
	}
	if ( $force || ! get_post_meta( $front_id, 'blokken', true ) ) {
		update_field( 'field_dn_home_blokken', array( 'blokken' => deedni_seed_rows( deedni_seed_blocks() ) ), $front_id );
	}
	if ( ! get_option( 'options_usps' ) ) {
		update_field(
			'field_dn_opties_usps',
			array(
				array( 'tekst' => 'Gratis voor marketeers' ),
				array( 'tekst' => 'Anoniem tot jouw ja' ),
				array( 'tekst' => 'Elk aanbod met salaris' ),
			),
			'option'
		);
	}
	return $front_id;
}

add_action(
	'after_switch_theme',
	function () {
		// ACF laadt zijn velden op init; bij het activeren van het thema is dat al gebeurd.
		if ( 'page' !== get_option( 'show_on_front' ) ) {
			deedni_seed_homepage();
		}
	}
);

/** Knop om de homepage (opnieuw) te vullen met de standaardblokken. */
function deedni_seed_button( $label = 'Homepage vullen met de standaardblokken' ) {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return '';
	}
	return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'
		. '<input type="hidden" name="action" value="deedni_seed">'
		. wp_nonce_field( 'deedni_seed', '_wpnonce', true, false )
		. '<button type="submit" class="button button-primary btn btn--accent">' . esc_html( $label ) . '</button></form>';
}

add_action(
	'admin_post_deedni_seed',
	function () {
		check_admin_referer( 'deedni_seed' );
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( 'Geen toegang.' );
		}
		$id = deedni_seed_homepage( ! empty( $_POST['force'] ) );
		wp_safe_redirect( $id ? get_edit_post_link( $id, 'url' ) : admin_url() );
		exit;
	}
);

add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'edit_pages' ) || ! deedni_has_acf_pro() ) {
			return;
		}
		$front_id = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
		if ( $front_id && get_post_meta( $front_id, 'blokken', true ) ) {
			return;
		}
		echo '<div class="notice notice-info"><p><strong>deedni thema:</strong> de homepage heeft nog geen blokken. Maak hem aan met dezelfde secties en teksten als het ontwerp; daarna kun je alles aanpassen.</p>' . deedni_seed_button() . '<p></p></div>'; // phpcs:ignore
	}
);
