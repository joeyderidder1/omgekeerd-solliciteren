<?php
/**
 * ACF-blokken: de flexible content "blokken" per pagina en kleine hulpfuncties voor de templates.
 *
 * Elk blok is een eigen veldgroep "Block: ..." (inactief) die als clone in de flexible content
 * van de groep "Blokken" zit. De template van een blok staat in template-parts/blocks/{naam}.php,
 * waarbij underscores streepjes worden (afbeelding_content -> afbeelding-content.php).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Toont alle blokken van een pagina. Geeft false terug als er geen blokken zijn.
 */
function deedni_render_blocks( $post_id = null ) {
	if ( ! function_exists( 'have_rows' ) || ! have_rows( 'blokken', $post_id ) ) {
		return false;
	}
	while ( have_rows( 'blokken', $post_id ) ) {
		the_row();
		get_template_part( 'template-parts/blocks/' . str_replace( '_', '-', get_row_layout() ) );
	}
	return true;
}

/** Tekst met *sterretjes* rond het accentwoord. */
function dn_accent( $text ) {
	return preg_replace( '/\*(.+?)\*/', '<em>$1</em>', esc_html( (string) $text ) );
}

/** Tekst met **dubbele sterretjes** voor vet. */
function dn_bold( $text ) {
	return preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', esc_html( (string) $text ) );
}

/** Een tekstvak met één punt per regel, als lijst. */
function dn_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r?\n/', (string) $text ) ) ) );
}

/** Een ACF-linkveld als knop. */
function dn_link( $link, $class = 'btn btn--accent' ) {
	if ( empty( $link['url'] ) ) {
		return '';
	}
	return sprintf(
		'<a class="%s" href="%s"%s>%s</a>',
		esc_attr( $class ),
		esc_url( $link['url'] ),
		! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : '',
		esc_html( $link['title'] ? $link['title'] : $link['url'] )
	);
}

/** class- en id-attributen van een sectie, op basis van de instellingen van het blok. */
function dn_section_attrs( $extra_class = '', $default_bg = 'standaard' ) {
	$bg     = get_sub_field( 'achtergrond' ) ? get_sub_field( 'achtergrond' ) : $default_bg;
	$anchor = sanitize_title( (string) get_sub_field( 'anker' ) );
	$class  = trim( 'band ' . ( 'wit' === $bg ? 'band--surface ' : '' ) . $extra_class );
	return 'class="' . esc_attr( $class ) . '"' . ( $anchor ? ' id="' . esc_attr( $anchor ) . '"' : '' );
}

/** Boventitel, titel en intro bovenaan een sectie. */
function dn_section_head( $intro_field = 'intro' ) {
	$eyebrow = get_sub_field( 'boventitel' );
	$title   = get_sub_field( 'titel' );
	$intro   = $intro_field ? get_sub_field( $intro_field ) : '';
	if ( ! $eyebrow && ! $title && ! $intro ) {
		return;
	}
	echo '<div class="section-head">';
	if ( $eyebrow ) {
		echo '<p class="eyebrow">' . esc_html( $eyebrow ) . '</p>';
	}
	if ( $title ) {
		echo '<h2>' . dn_accent( $title ) . '</h2>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	if ( $intro ) {
		echo '<p class="lead">' . esc_html( $intro ) . '</p>';
	}
	echo '</div>';
}

function dn_check_svg() {
	return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"></path></svg>';
}

function dn_arrow_svg() {
	return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>';
}

/** Een lijst met vinkjes. */
function dn_checks( $items, $attrs = '' ) {
	$items = array_filter( (array) $items );
	if ( ! $items ) {
		return '';
	}
	$out = '<ul class="checks"' . $attrs . '>';
	foreach ( $items as $item ) {
		$out .= '<li>' . dn_check_svg() . esc_html( $item ) . '</li>';
	}
	return $out . '</ul>';
}

/** Teksten uit een repeater met één tekstveld. */
function dn_repeater_texts( $rows, $key = 'tekst' ) {
	return array_values( array_filter( wp_list_pluck( (array) $rows, $key ) ) );
}
