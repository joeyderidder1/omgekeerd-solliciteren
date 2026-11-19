<?php
/**
 * ACF-koppeling.
 *
 * De veldgroepen staan als JSON in /acf-json en worden automatisch geladen. Wil je ze aanpassen in
 * ACF > Veldgroepen, kies dan eerst "Synchroniseren"; ACF bewaart wijzigingen daarna in je thema
 * (map acf-json) of in de database.
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'acf/settings/load_json',
	function ( $paths ) {
		$paths[] = DEEDNI_DIR . 'acf-json';
		return $paths;
	}
);

add_action(
	'admin_notices',
	function () {
		if ( ! function_exists( 'acf_form' ) && current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-error"><p><strong>deedni Core</strong> heeft Advanced Custom Fields nodig (de gratis versie is genoeg). Installeer en activeer ACF via Plugins &gt; Nieuwe plugin.</p></div>';
		}
	}
);

/** Het profielformulier moet acf_form_head() draaien voordat er iets naar de browser gaat. */
add_action(
	'template_redirect',
	function () {
		if ( function_exists( 'acf_form_head' ) && deedni_is_page( 'profiel' ) && is_user_logged_in() ) {
			acf_form_head();
		}
	},
	5
);

/** Op de site mag iemand alleen het eigen profiel opslaan, wat er ook in het formulier staat. */
add_filter(
	'acf/pre_save_post',
	function ( $post_id ) {
		if ( is_admin() || deedni_is_beheerder() ) {
			return $post_id;
		}
		if ( ! is_numeric( $post_id ) || (int) $post_id !== deedni_profile_id() ) {
			wp_die( 'Je kunt alleen je eigen profiel aanpassen.', 'Geen toegang', array( 'response' => 403 ) );
		}
		return $post_id;
	},
	1
);

/**
 * Na opslaan: titel en tekst gelijk trekken met de velden (zodat de zoekfunctie erop werkt) en
 * de blokkeerlijst omzetten naar losse metarijen waarop we kunnen filteren.
 */
add_action( 'acf/save_post', 'deedni_after_acf_save', 20 );

function deedni_after_acf_save( $post_id ) {
	static $running = false;
	if ( $running || ! is_numeric( $post_id ) ) {
		return;
	}
	$type = get_post_type( $post_id );

	if ( 'kandidaat' === $type ) {
		$running = true;
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_title'   => deedni_field( $post_id, 'functietitel' ) ? deedni_field( $post_id, 'functietitel' ) : deedni_code( $post_id ),
				'post_content' => (string) deedni_field( $post_id, 'samenvatting' ),
			)
		);
		$running = false;

		delete_post_meta( $post_id, '_dn_blocked_domain' );
		$lines = preg_split( '/[\s,;]+/', strtolower( (string) deedni_field( $post_id, 'blokkeerlijst' ) ) );
		foreach ( array_unique( array_filter( $lines ) ) as $line ) {
			$domain = preg_replace( '#^.*@|^(https?://)?(www\.)?|/.*$#', '', $line );
			if ( $domain && false !== strpos( $domain, '.' ) ) {
				add_post_meta( $post_id, '_dn_blocked_domain', sanitize_text_field( $domain ) );
			}
		}
	}

	if ( 'bedrijf' === $type && deedni_field( $post_id, 'bedrijfsnaam' ) ) {
		$running = true;
		wp_update_post( array( 'ID' => $post_id, 'post_title' => deedni_field( $post_id, 'bedrijfsnaam' ) ) );
		$running = false;
	}
}
