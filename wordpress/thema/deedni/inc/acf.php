<?php
/**
 * ACF-instellingen van het thema: JSON in /acf-json, de optiepagina en de klassieke editor voor pagina's.
 */

defined( 'ABSPATH' ) || exit;

/** Heeft deze site de ACF-onderdelen die de blokken nodig hebben (flexible content, repeater, clone)? */
function deedni_has_acf_pro() {
	return function_exists( 'acf_get_field_type' ) && acf_get_field_type( 'flexible_content' ) && acf_get_field_type( 'clone' );
}

// Wijzigingen in de veldgroepen worden in het thema bewaard (acf-json), zodat ze mee kunnen in versiebeheer.
add_filter(
	'acf/settings/save_json',
	function () {
		return get_stylesheet_directory() . '/acf-json';
	}
);

add_action(
	'acf/init',
	function () {
		if ( function_exists( 'acf_add_options_page' ) ) {
			acf_add_options_page(
				array(
					'page_title' => 'Thema-instellingen',
					'menu_title' => 'Thema-instellingen',
					'menu_slug'  => 'deedni-instellingen',
					'capability' => 'edit_theme_options',
					'icon_url'   => 'dashicons-admin-customizer',
					'position'   => 59,
					'redirect'   => false,
				)
			);
		}
	}
);

// Pagina's worden opgebouwd met ACF-blokken, dus de blokeditor van WordPress is daar niet nodig.
add_filter(
	'use_block_editor_for_post_type',
	function ( $use, $post_type ) {
		return 'page' === $post_type ? false : $use;
	},
	10,
	2
);

add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'activate_plugins' ) || deedni_has_acf_pro() ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>deedni thema:</strong> de pagina-blokken gebruiken flexible content, repeaters, clones en een optiepagina. Daarvoor heb je <strong>ACF Pro</strong> nodig, of de gratis plugin <strong>Secure Custom Fields</strong>.</p></div>';
	}
);

/**
 * Vangnet voor naadloze clones in flexible content. Sommige versies van ACF/Secure Custom Fields klappen
 * alleen de eerste clone in een lijst uit, waardoor de velden van de andere blokken onvindbaar zijn.
 * Dit filter klapt de clones die nog over zijn alsnog uit. Zijn ze al uitgeklapt, dan doet het niets.
 */
add_filter(
	'acf/get_fields',
	function ( $fields ) {
		if ( ! is_array( $fields ) || ! function_exists( 'acf_is_filter_enabled' ) || ! acf_is_filter_enabled( 'clone' ) ) {
			return $fields;
		}
		$i = 0;
		while ( $i < count( $fields ) ) {
			$field = isset( $fields[ $i ] ) ? $fields[ $i ] : null;
			if ( is_array( $field ) && isset( $field['type'], $field['display'] ) && 'clone' === $field['type'] && 'seamless' === $field['display'] && ! empty( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
				array_splice( $fields, $i, 1, $field['sub_fields'] );
				continue;
			}
			++$i;
		}
		return $fields;
	},
	6
);
