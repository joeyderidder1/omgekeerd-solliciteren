<?php
/**
 * Beheer: instellingen, werkgevers goedkeuren en extra kolommen in de overzichten.
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
		register_setting(
			'deedni',
			'deedni_pages',
			array(
				'type'              => 'array',
				'sanitize_callback' => function ( $value ) {
					return array_map( 'absint', (array) $value );
				},
			)
		);
		register_setting(
			'deedni',
			'deedni_werkgever_goedkeuring',
			array(
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => function ( $value ) {
					return $value ? 1 : 0;
				},
			)
		);
	}
);

function deedni_settings_page() {
	$labels = array(
		'registreren' => 'Aanmelden',
		'inloggen'    => 'Inloggen',
		'dashboard'   => 'Overzicht (dashboard)',
		'profiel'     => 'Profiel bewerken',
		'zoeken'      => 'Kandidaten zoeken',
	);
	?>
	<div class="wrap">
		<h1>deedni</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'deedni' ); ?>
			<h2>Pagina's</h2>
			<p>De plugin heeft deze pagina's bij activeren aangemaakt. Je kunt ze hernoemen of een andere pagina kiezen; zet dan wel de shortcode op die pagina.</p>
			<table class="form-table" role="presentation">
				<?php foreach ( $labels as $key => $label ) : ?>
					<tr>
						<th scope="row"><label for="deedni-page-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => 'deedni_pages[' . $key . ']',
									'id'                => 'deedni-page-' . $key,
									'selected'          => deedni_page_id( $key ),
									'show_option_none'  => '— Kies een pagina —',
									'option_none_value' => 0,
								)
							);
							?>
							<code>[deedni_<?php echo esc_html( $key ); ?>]</code>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<h2>Werkgevers</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Goedkeuring</th>
					<td>
						<label><input type="checkbox" name="deedni_werkgever_goedkeuring" value="1" <?php checked( get_option( 'deedni_werkgever_goedkeuring', 1 ) ); ?>>
						Nieuwe werkgevers pas laten zoeken nadat ik ze heb goedgekeurd</label>
						<p class="description">Je keurt een werkgever goed bij Gebruikers. Aanbevolen, zodat alleen echte bedrijven profielen zien.</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
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
	$profile = deedni_profile_id( $user->ID );
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
			$id = deedni_profile_id( $user_id );
			return $id ? '<a href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( deedni_code( $id ) ) . '</a>' : 'Kandidaat';
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
		if ( $screen && 'users' === $screen->id && ! empty( $_GET['deedni_goedgekeurd'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>Werkgever goedgekeurd. Er is een mail verstuurd.</p></div>';
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
		switch ( $column ) {
			case 'kandidaat':
				$id = (int) deedni_field( $post_id, 'kandidaat' );
				echo $id ? '<a href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( deedni_code( $id ) ) . '</a>' : '–';
				break;
			case 'bedrijf':
				$id = (int) deedni_field( $post_id, 'bedrijf' );
				echo $id ? esc_html( get_the_title( $id ) ) : '–';
				break;
			case 'salaris':
				echo esc_html( deedni_euro( deedni_field( $post_id, 'salaris_min' ) ) . '–' . deedni_number( deedni_field( $post_id, 'salaris_max' ) ) );
				break;
			case 'status':
				echo esc_html( deedni_label( 'aanbod_status', deedni_field( $post_id, 'status' ) ) );
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
				$new['zichtbaar'] = 'Zichtbaar';
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
			echo deedni_field( $post_id, 'profiel_zichtbaar' ) && deedni_profile_complete( $post_id ) ? 'Ja' : 'Nee';
		}
	},
	10,
	2
);
