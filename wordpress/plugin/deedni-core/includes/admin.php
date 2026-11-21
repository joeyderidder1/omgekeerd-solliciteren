<?php
/**
 * Beheer: Instellingen > deedni, werkgevers goedkeuren, extra kolommen en een leesbaar overzicht
 * van profielen en aanbiedingen in wp-admin.
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Instellingen > deedni ---------- */

add_action(
	'admin_menu',
	function () {
		add_options_page( 'deedni', 'deedni', 'manage_options', 'deedni', 'deedni_settings_page' );
	}
);

add_action(
	'admin_init',
	function () {
		$int = function ( $min, $max ) {
			return function ( $v ) use ( $min, $max ) {
				return max( $min, min( $max, absint( $v ) ) );
			};
		};
		$bool = function ( $v ) {
			return $v ? 1 : 0;
		};
		register_setting( 'deedni', 'deedni_pages', array( 'type' => 'array', 'sanitize_callback' => function ( $v ) { return array_map( 'absint', (array) $v ); } ) );
		register_setting( 'deedni', 'deedni_werkgever_goedkeuring', array( 'type' => 'integer', 'default' => 1, 'sanitize_callback' => $bool ) );
		register_setting( 'deedni', 'deedni_email_bevestigen', array( 'type' => 'integer', 'default' => 1, 'sanitize_callback' => $bool ) );
		register_setting( 'deedni', 'deedni_wie_keek_mee', array( 'type' => 'integer', 'default' => 1, 'sanitize_callback' => $bool ) );
		register_setting( 'deedni', 'deedni_reactietermijn', array( 'type' => 'integer', 'default' => 5, 'sanitize_callback' => $int( 1, 60 ) ) );
		register_setting( 'deedni', 'deedni_actueel_dagen', array( 'type' => 'integer', 'default' => 0, 'sanitize_callback' => $int( 0, 365 ) ) );
	}
);

/** Knoppen "standaard opnieuw klaarzetten". */
add_action(
	'admin_post_deedni_herstel',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Geen toegang.' );
		}
		check_admin_referer( 'deedni_herstel' );
		$wat = isset( $_POST['wat'] ) ? sanitize_key( wp_unslash( $_POST['wat'] ) ) : '';
		if ( 'paginas' === $wat ) {
			deedni_create_pages();
		} elseif ( 'menus' === $wat ) {
			deedni_installeer_menus( true );
		} elseif ( 'home' === $wat ) {
			deedni_installeer_homepage( true );
		}
		wp_safe_redirect( admin_url( 'options-general.php?page=deedni&deedni_hersteld=' . $wat ) );
		exit;
	}
);

function deedni_settings_page() {
	$labels = array(
		'inloggen'    => 'Inloggen',
		'registreren' => 'Account maken',
		'dashboard'   => 'Overzicht',
		'profiel'     => 'Mijn profiel',
		'zoeken'      => 'Kandidaten zoeken',
		'vooraanmelden' => 'Vooraanmelding (testfase)',
	);
	?>
	<div class="wrap">
		<h1>deedni</h1>
		<?php if ( ! empty( $_GET['deedni_hersteld'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p>Klaar.</p></div>
		<?php endif; ?>
		<p>Teksten, afbeeldingen en blokken pas je aan op de pagina zelf (Pagina's &gt; bewerken). Menu's onder Weergave &gt; Menu's. Kleuren en lettertypes onder Weergave &gt; Editor &gt; Stijlen.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'deedni' ); ?>
			<h2>Pagina's</h2>
			<p>Deze pagina's maakte de plugin aan. Kies je een andere pagina, zet daar dan het bijbehorende deedni-blok op.</p>
			<table class="form-table" role="presentation">
				<?php foreach ( $labels as $key => $label ) : ?>
					<tr>
						<th scope="row"><label for="deedni-page-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => 'deedni_pages[' . esc_attr( $key ) . ']',
									'id'                => 'deedni-page-' . $key,
									'selected'          => deedni_page_id( $key ),
									'show_option_none'  => '— Kies een pagina —',
									'option_none_value' => 0,
								)
							);
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<h2>Werkgevers</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Goedkeuring</th>
					<td><label><input type="checkbox" name="deedni_werkgever_goedkeuring" value="1" <?php checked( get_option( 'deedni_werkgever_goedkeuring', 1 ) ); ?>> Nieuwe werkgevers pas laten zoeken nadat ik ze heb goedgekeurd</label>
					<p class="description">Je keurt een werkgever goed bij Gebruikers. Aanbevolen, zodat alleen echte bedrijven profielen zien.</p></td>
				</tr>
			</table>
			<h2>Accounts</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">E-mailadres bevestigen</th>
					<td><input type="hidden" name="deedni_email_bevestigen" value="0"><label><input type="checkbox" name="deedni_email_bevestigen" value="1" <?php checked( get_option( 'deedni_email_bevestigen', 1 ) ); ?>> Nieuwe accounts krijgen eerst een mail met een bevestigingslink</label>
					<p class="description">Aanbevolen. Zo kan niemand een account maken op het adres van een ander, en verraadt het formulier niet welke e-mailadressen al een account hebben. Werkt alleen als de site mail kan versturen (test dat eerst, bijvoorbeeld met WP Mail SMTP).</p></td>
				</tr>
			</table>
			<h2>Kandidaten</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="deedni-termijn">Reageren binnen</label></th>
					<td><input id="deedni-termijn" type="number" min="1" max="60" name="deedni_reactietermijn" value="<?php echo (int) get_option( 'deedni_reactietermijn', 5 ); ?>" class="small-text"> dagen
					<p class="description">Staat bij elk aanbod: "Reageer binnen 5 dagen".</p></td>
				</tr>
				<tr>
					<th scope="row">Wie keek mee</th>
					<td><label><input type="checkbox" name="deedni_wie_keek_mee" value="1" <?php checked( get_option( 'deedni_wie_keek_mee', 1 ) ); ?>> Kandidaten zien welke soort bedrijven hun profiel bekeken (zonder bedrijfsnaam)</label></td>
				</tr>
				<tr>
					<th scope="row"><label for="deedni-actueel">Alleen actuele profielen</label></th>
					<td><input id="deedni-actueel" type="number" min="0" max="365" name="deedni_actueel_dagen" value="<?php echo (int) get_option( 'deedni_actueel_dagen', 0 ); ?>" class="small-text"> dagen
					<p class="description">Profielen die langer dan zoveel dagen niet zijn bevestigd, verdwijnen uit de zoekresultaten. 0 = altijd tonen.</p></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>

		<h2>Standaard opnieuw klaarzetten</h2>
		<p>Handig als je iets kwijt bent. Bestaande pagina's en menu-items blijven staan, tenzij hieronder anders staat.</p>
		<?php
		foreach (
			array(
				'paginas' => array( 'Ontbrekende pagina\'s aanmaken', 'Maakt alleen pagina\'s aan die er niet (meer) zijn.' ),
				'menus'   => array( 'Ontbrekende menu\'s aanmaken', 'Maakt de menu\'s per rol aan als ze er niet zijn en koppelt ze aan hun plek.' ),
				'home'    => array( 'Homepage opnieuw vullen', 'Let op: zet alle homepage-blokken terug met de standaardteksten. De oude versie blijft als revisie bewaard.' ),
			) as $wat => $tekst
		) :
			?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0 0 12px">
				<input type="hidden" name="action" value="deedni_herstel"><input type="hidden" name="wat" value="<?php echo esc_attr( $wat ); ?>">
				<?php wp_nonce_field( 'deedni_herstel' ); ?>
				<button type="submit" class="button"<?php echo 'home' === $wat ? ' onclick="return confirm(\'Homepage terugzetten naar de standaard?\')"' : ''; ?>><?php echo esc_html( $tekst[0] ); ?></button>
				<span class="description"><?php echo esc_html( $tekst[1] ); ?></span>
			</form>
		<?php endforeach; ?>
	</div>
	<?php
}

/* ---------- Werkgevers goedkeuren ---------- */

function deedni_approve_werkgever( $user_id, $approved = true ) {
	$was = (bool) get_user_meta( $user_id, 'deedni_goedgekeurd', true );
	update_user_meta( $user_id, 'deedni_goedgekeurd', $approved ? 1 : 0 );
	if ( $approved && ! $was ) {
		deedni_mail_werkgever_approved( $user_id );
	}
}

add_action( 'show_user_profile', 'deedni_user_profile_fields' );
add_action( 'edit_user_profile', 'deedni_user_profile_fields' );

function deedni_user_profile_fields( $user ) {
	if ( ! deedni_is_werkgever( $user ) || ! current_user_can( 'edit_users' ) ) {
		return;
	}
	$profile = deedni_profile_id( $user->ID, 'bedrijf' );
	?>
	<h2>deedni</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">Werkgever goedgekeurd</th>
			<td>
				<label><input type="checkbox" name="deedni_goedgekeurd" value="1" <?php checked( get_user_meta( $user->ID, 'deedni_goedgekeurd', true ) ); ?>> Mag kandidaten zoeken en aanbiedingen sturen</label>
				<?php if ( $profile ) : ?>
					<p class="description"><a href="<?php echo esc_url( get_edit_post_link( $profile ) ); ?>">Bedrijfsprofiel bekijken</a></p>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	<?php
}

add_action( 'personal_options_update', 'deedni_save_user_profile_fields' );
add_action( 'edit_user_profile_update', 'deedni_save_user_profile_fields' );

function deedni_save_user_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_users' ) || ! deedni_is_werkgever( $user_id ) ) {
		return;
	}
	deedni_approve_werkgever( $user_id, ! empty( $_POST['deedni_goedgekeurd'] ) ); // phpcs:ignore WordPress.Security.NonceVerification -- WP controleert de nonce van het profielformulier.
}

add_filter(
	'manage_users_columns',
	function ( $columns ) {
		$columns['deedni'] = 'deedni';
		return $columns;
	}
);

add_filter(
	'manage_users_custom_column',
	function ( $output, $column, $user_id ) {
		if ( 'deedni' !== $column ) {
			return $output;
		}
		if ( deedni_is_kandidaat( $user_id ) ) {
			$id = deedni_profile_id( $user_id, 'kandidaat' );
			return $id ? '<a href="' . esc_url( get_edit_post_link( $id ) ) . '">Kandidaat ' . esc_html( deedni_code( $id ) ) . '</a>' : 'Kandidaat';
		}
		if ( deedni_is_werkgever( $user_id ) ) {
			if ( get_user_meta( $user_id, 'deedni_goedgekeurd', true ) || ! get_option( 'deedni_werkgever_goedkeuring', 1 ) ) {
				return 'Werkgever, goedgekeurd';
			}
			$url = wp_nonce_url( admin_url( 'users.php?deedni_goedkeuren=' . $user_id ), 'deedni_goedkeuren_' . $user_id );
			return 'Werkgever, <strong>wacht op goedkeuring</strong><br><a href="' . esc_url( $url ) . '">Goedkeuren</a>';
		}
		return $output;
	},
	10,
	3
);

add_action(
	'load-users.php',
	function () {
		if ( empty( $_GET['deedni_goedkeuren'] ) || ! current_user_can( 'edit_users' ) ) {
			return;
		}
		$user_id = absint( $_GET['deedni_goedkeuren'] );
		check_admin_referer( 'deedni_goedkeuren_' . $user_id );
		if ( deedni_is_werkgever( $user_id ) ) {
			deedni_approve_werkgever( $user_id );
		}
		wp_safe_redirect( admin_url( 'users.php?role=deedni_werkgever&deedni_goedgekeurd=1' ) );
		exit;
	}
);

add_action(
	'admin_notices',
	function () {
		$screen = get_current_screen();
		if ( $screen && 'users' === $screen->id && ! empty( $_GET['deedni_goedgekeurd'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>Werkgever goedgekeurd. Er is een mail verstuurd.</p></div>';
		}
		if ( ! wp_is_block_theme() || ( 'deedni' !== get_template() ) ) {
			if ( current_user_can( 'switch_themes' ) && $screen && in_array( $screen->id, array( 'dashboard', 'plugins', 'themes' ), true ) ) {
				echo '<div class="notice notice-warning"><p>deedni Core werkt het best met het thema <strong>deedni</strong> (versie 2). Activeer het onder Weergave &gt; Thema\'s.</p></div>';
			}
		}
	}
);

/* ---------- Kolommen ---------- */

add_filter(
	'manage_aanbod_posts_columns',
	function ( $columns ) {
		return array(
			'cb'        => $columns['cb'],
			'title'     => 'Functie',
			'kandidaat' => 'Kandidaat',
			'bedrijf'   => 'Bedrijf',
			'salaris'   => 'Salaris p/m',
			'status'    => 'Status',
			'date'      => $columns['date'],
		);
	}
);

add_action(
	'manage_aanbod_posts_custom_column',
	function ( $column, $post_id ) {
		$o = deedni_aanbod( $post_id );
		switch ( $column ) {
			case 'kandidaat':
				echo $o['kandidaat_id'] ? '<a href="' . esc_url( get_edit_post_link( $o['kandidaat_id'] ) ) . '">' . esc_html( deedni_code( $o['kandidaat_id'] ) ) . '</a>' : '–';
				break;
			case 'bedrijf':
				echo $o['bedrijf_id'] ? esc_html( get_the_title( $o['bedrijf_id'] ) ) : '–';
				break;
			case 'salaris':
				echo esc_html( deedni_euro( $o['salaris_min'] ) . '–' . deedni_number( $o['salaris_max'] ) );
				break;
			case 'status':
				echo esc_html( deedni_label( 'aanbod_status', $o['status'] ) . ( $o['ronde'] > 1 ? ' (ronde ' . $o['ronde'] . ')' : '' ) );
				break;
		}
	},
	10,
	2
);

add_filter(
	'manage_kandidaat_posts_columns',
	function ( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['code'] = 'Code';
			}
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['zichtbaar'] = 'Online';
				$new['compleet']  = 'Compleet';
			}
		}
		return $new;
	}
);

add_action(
	'manage_kandidaat_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'code' === $column ) {
			echo esc_html( deedni_code( $post_id ) );
		}
		if ( 'zichtbaar' === $column ) {
			if ( deedni_beheer_verborgen( $post_id ) ) {
				echo '<strong style="color:#b32d2e">Verborgen door beheer</strong>';
			} else {
				echo deedni_field( $post_id, 'profiel_zichtbaar' ) ? 'Ja' : 'Nee';
			}
		}
		if ( 'compleet' === $column ) {
			$c = deedni_compleet( deedni_kandidaat( $post_id ) );
			echo (int) $c['procent'] . '%';
		}
	},
	10,
	2
);

/* ---------- Leesbaar overzicht in wp-admin ---------- */

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'deedni-bedrijf', 'Bedrijfsprofiel', 'deedni_metabox_bedrijf', 'bedrijf', 'normal', 'high' );
		add_meta_box( 'deedni-aanbod', 'Aanbod', 'deedni_metabox_aanbod', 'aanbod', 'normal', 'high' );
	}
);

/** Tabel met label en waarde. */
function deedni_admin_tabel( $rijen ) {
	echo '<table class="widefat striped"><tbody>';
	foreach ( $rijen as $label => $waarde ) {
		if ( is_array( $waarde ) ) {
			$waarde = implode( ', ', array_filter( array_map( 'strval', $waarde ) ) );
		}
		echo '<tr><th style="width:200px">' . esc_html( $label ) . '</th><td>' . ( '' === (string) $waarde ? '<span style="color:#888">–</span>' : nl2br( esc_html( $waarde ) ) ) . '</td></tr>';
	}
	echo '</tbody></table>';
}

function deedni_metabox_bedrijf( $post ) {
	$b = deedni_bedrijf( $post->ID );
	$u = $b['user_id'] ? get_userdata( $b['user_id'] ) : null;
	if ( $u ) {
		echo '<p>Account: <a href="' . esc_url( get_edit_user_link( $u->ID ) ) . '">' . esc_html( $u->user_email ) . '</a> · ' . ( deedni_werkgever_goedgekeurd( $u ) ? 'goedgekeurd' : '<strong>wacht op goedkeuring</strong> (keur goed bij Gebruikers)' ) . '</p>';
	}
	deedni_admin_tabel(
		array(
			'Soort'          => $b['soort'],
			'Plaats'         => $b['plaats'],
			'Website'        => $b['website'],
			'Medewerkers'    => $b['medewerkers'],
			'KvK'            => $b['kvk_nummer'],
			'Contactpersoon' => $b['contactpersoon'],
			'Telefoon'       => $b['telefoon'],
			'Omschrijving'   => $b['omschrijving'],
			'Zoekt'          => deedni_bedrijf_voorkeuren_tekst( $b ),
		)
	);
}

function deedni_metabox_aanbod( $post ) {
	$o = deedni_aanbod( $post->ID );
	deedni_admin_tabel(
		array(
			'Status'      => deedni_label( 'aanbod_status', $o['status'] ) . ( $o['ronde'] > 1 ? ' (ronde ' . $o['ronde'] . ')' : '' ),
			'Kandidaat'   => $o['kandidaat_id'] ? deedni_code( $o['kandidaat_id'] ) : '',
			'Bedrijf'     => $o['bedrijf_id'] ? get_the_title( $o['bedrijf_id'] ) : '',
			'Functie'     => $o['functie'],
			'Salaris'     => deedni_euro( $o['salaris_min'] ) . '–' . deedni_number( $o['salaris_max'] ),
			'Uren'        => $o['uren'],
			'Contract'    => deedni_label( 'contract', $o['contract'] ),
			'Werkplek'    => deedni_label( 'werkvorm', $o['werkplek'] ),
			'Standplaats' => $o['standplaats'],
			'Bericht'     => $o['bericht'],
			'Ja, maar'    => $o['tegen']['bericht'] ? implode( ', ', array_map( function ( $v ) { return deedni_label( 'voorwaarden', $v ); }, $o['tegen']['voorwaarden'] ) ) . "\n" . $o['tegen']['bericht'] : '',
		)
	);
	$log = deedni_list( $post->ID, 'historie' );
	if ( $log ) {
		echo '<h4>Geschiedenis</h4><ul>';
		foreach ( $log as $l ) {
			echo '<li>' . esc_html( wp_date( 'j M Y H:i', (int) $l['t'] ) . ' · ' . $l['tekst'] ) . '</li>';
		}
		echo '</ul>';
	}
}

/* ---------- Vaardigheden koppelen aan vakgebieden ---------- */

/** Vinkjes met vakgebieden in het formulier van een vaardigheid. */
function deedni_vaardigheid_vakken_html( $gekozen ) {
	$h = '';
	foreach ( deedni_vakgebieden_boom() as $rij ) {
		$h .= '<label style="display:block;margin:2px 0"><input type="checkbox" name="deedni_vakgebieden[]" value="' . (int) $rij[0]->term_id . '"' . checked( in_array( (int) $rij[0]->term_id, $gekozen, true ), true, false ) . '> ' . esc_html( $rij[0]->name ) . '</label>';
	}
	return $h . '<p class="description">Kandidaten in deze vakgebieden zien deze vaardigheid als eerste. Niets aangevinkt: voor iedereen.</p>';
}

add_action(
	'vaardigheid_add_form_fields',
	function () {
		echo '<div class="form-field"><label>Vakgebieden</label>' . deedni_vaardigheid_vakken_html( array() ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
);
add_action(
	'vaardigheid_edit_form_fields',
	function ( $term ) {
		echo '<tr class="form-field"><th scope="row">Vakgebieden</th><td>' . deedni_vaardigheid_vakken_html( deedni_vaardigheid_vakgebieden( $term->term_id ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
);
foreach ( array( 'created_vaardigheid', 'edited_vaardigheid' ) as $deedni_hook ) {
	add_action(
		$deedni_hook,
		function ( $term_id ) {
			// De nonce van het termformulier van WordPress is hier al gecontroleerd.
			if ( ! current_user_can( 'manage_categories' ) || ! isset( $_POST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				return;
			}
			$ids = isset( $_POST['deedni_vakgebieden'] ) ? array_filter( array_map( 'absint', (array) $_POST['deedni_vakgebieden'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
			update_term_meta( $term_id, 'deedni_vakgebieden', $ids ? implode( ',', $ids ) : 'alle' );
		}
	);
}
add_filter(
	'manage_edit-vaardigheid_columns',
	function ( $cols ) {
		$cols['deedni_vak'] = 'Vakgebieden';
		return $cols;
	}
);
add_filter(
	'manage_vaardigheid_custom_column',
	function ( $out, $col, $term_id ) {
		if ( 'deedni_vak' !== $col ) {
			return $out;
		}
		$ids = deedni_vaardigheid_vakgebieden( $term_id );
		return $ids ? esc_html( implode( ', ', deedni_term_lijst( $ids, 'specialisme' ) ) ) : 'Alle';
	},
	10,
	3
);
