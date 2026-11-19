<?php
/**
 * Kleuren uit Thema-instellingen > Stijl, als CSS-variabelen over die van style.css heen.
 * Alleen kleuren die afwijken van het ontwerp worden uitgevoerd. Afgeleide kleuren (lijnen, grijze tekst,
 * lichte achtergronden) worden berekend, en tekstkleuren worden zo nodig bijgesteld zodat ze leesbaar blijven.
 */

defined( 'ABSPATH' ) || exit;

/** De kleuren uit het ontwerp. Gelijk aan de standaardwaarden in de veldgroep Stijl. */
function deedni_style_defaults() {
	return array(
		'primair'   => array( 'kleur' => '#C2410C', 'tekst' => '#FFFFFF' ),
		'secundair' => array( 'kleur' => '#F6F5F1', 'tekst' => '#16213A' ),
		'tertiair'  => array( 'kleur' => '#16213A', 'tekst' => '#FFFFFF' ),
		'licht'     => array( 'kleur' => '#FFFFFF', 'tekst' => '#16213A' ),
		'rood'      => '#B42318',
		'groen'     => '#16A34A',
	);
}

/** Geldige #RRGGBB of null. */
function dn_hex( $value ) {
	$value = is_string( $value ) ? trim( $value ) : '';
	if ( preg_match( '/^#([0-9a-f]{3})$/i', $value, $m ) ) {
		$value = '#' . $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
	}
	return preg_match( '/^#[0-9a-f]{6}$/i', $value ) ? strtoupper( $value ) : null;
}

function dn_rgb( $hex ) {
	return array( hexdec( substr( $hex, 1, 2 ) ), hexdec( substr( $hex, 3, 2 ) ), hexdec( substr( $hex, 5, 2 ) ) );
}

/** Mengt $a met $b; $t = 0 geeft $a, $t = 1 geeft $b. */
function dn_mix( $a, $b, $t ) {
	$a = dn_rgb( $a );
	$b = dn_rgb( $b );
	$out = '#';
	for ( $i = 0; $i < 3; $i++ ) {
		$out .= sprintf( '%02X', (int) round( $a[ $i ] + ( $b[ $i ] - $a[ $i ] ) * $t ) );
	}
	return $out;
}

function dn_luminance( $hex ) {
	$l = array_map(
		function ( $c ) {
			$c /= 255;
			return $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		},
		dn_rgb( $hex )
	);
	return 0.2126 * $l[0] + 0.7152 * $l[1] + 0.0722 * $l[2];
}

function dn_contrast( $a, $b ) {
	$la = dn_luminance( $a );
	$lb = dn_luminance( $b );
	return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
}

/** $color, zo weinig mogelijk richting $toward geschoven tot het contrast met $bg minstens $min is. */
function dn_readable( $color, $bg, $toward, $min = 4.5 ) {
	for ( $t = 0; $t <= 1.001; $t += 0.05 ) {
		$c = dn_mix( $color, $toward, min( 1, $t ) );
		if ( dn_contrast( $c, $bg ) >= $min ) {
			return $c;
		}
	}
	return dn_contrast( '#000000', $bg ) > dn_contrast( '#FFFFFF', $bg ) ? '#000000' : '#FFFFFF';
}

/** De ingevulde kleuren, aangevuld met het ontwerp waar iets leeg of ongeldig is. */
function deedni_style_colors() {
	$colors = deedni_style_defaults();
	if ( ! function_exists( 'get_field' ) ) {
		return $colors;
	}
	foreach ( $colors as $name => $default ) {
		$value = get_field( $name, 'option' );
		if ( is_array( $default ) ) {
			foreach ( $default as $sub => $hex ) {
				$own = is_array( $value ) && isset( $value[ $sub ] ) ? dn_hex( $value[ $sub ] ) : null;
				if ( $own ) {
					$colors[ $name ][ $sub ] = $own;
				}
			}
		} elseif ( dn_hex( $value ) ) {
			$colors[ $name ] = dn_hex( $value );
		}
	}
	return $colors;
}

/** De CSS met eigen kleuren, of een lege string als alles nog op het ontwerp staat. */
function deedni_style_css() {
	$def = deedni_style_defaults();
	$c   = deedni_style_colors();
	$var = array();

	$bg      = $c['secundair']['kleur'];
	$fg      = $c['secundair']['tekst'];
	$surface = $c['licht']['kleur'];
	$block   = $c['tertiair']['kleur'];

	if ( $c['secundair'] !== $def['secundair'] ) {
		$var['--bg']     = $bg;
		$var['--fg']     = $fg;
		$var['--muted']  = dn_readable( dn_mix( $fg, $bg, 0.3 ), $bg, $fg );
		$var['--quiet']  = dn_readable( dn_mix( $fg, $bg, 0.4 ), $bg, $fg );
		$var['--line']   = dn_mix( $bg, $fg, 0.12 );
		$var['--sunken'] = dn_mix( $bg, $fg, 0.05 );
	}
	if ( $c['licht'] !== $def['licht'] ) {
		$var['--surface']    = $surface;
		$var['--surface-fg'] = $c['licht']['tekst'];
	}
	if ( $c['tertiair'] !== $def['tertiair'] ) {
		$var['--block']       = $block;
		$var['--block-fg']    = $c['tertiair']['tekst'];
		$var['--block-muted'] = dn_readable( dn_mix( $c['tertiair']['tekst'], $block, 0.2 ), $block, $c['tertiair']['tekst'] );
		$var['--btn']         = $block;
		$var['--btn-fg']      = $c['tertiair']['tekst'];
	}
	// De primaire kleur hangt ook af van achtergrond en tekst, dus opnieuw berekenen als een daarvan verandert.
	$primair_changed = $c['primair'] !== $def['primair'];
	if ( $primair_changed || isset( $var['--bg'] ) || isset( $var['--surface'] ) ) {
		$accent              = $c['primair']['kleur'];
		$var['--accent']     = $accent;
		$var['--accent-fg']  = $c['primair']['tekst'];
		$var['--accent-text'] = dn_readable( $accent, $bg, $fg );
		$var['--accent-soft'] = dn_mix( $accent, $surface, 0.85 );
		// Gekleurde woorden in titels: grote tekst, dus 3:1 is genoeg.
		$var['--accent-heading'] = dn_readable( $accent, $bg, $fg, 3 );
		if ( ! $primair_changed ) {
			// Ontwerpkleuren houden zolang ze leesbaar zijn.
			$var['--accent-text'] = dn_contrast( '#9A3412', $bg ) >= 4.5 ? '#9A3412' : $var['--accent-text'];
			unset( $var['--accent'], $var['--accent-fg'] );
			if ( dn_contrast( $accent, $bg ) >= 3 ) {
				unset( $var['--accent-heading'] );
			}
		}
	}
	if ( $c['rood'] !== $def['rood'] || isset( $var['--surface'] ) ) {
		$var['--err'] = dn_readable( $c['rood'], $surface, $c['licht']['tekst'] );
	}
	if ( $c['groen'] !== $def['groen'] ) {
		$var['--ok-bg']    = dn_mix( $c['groen'], '#FFFFFF', 0.82 );
		$var['--ok-fg']    = dn_readable( $c['groen'], $var['--ok-bg'], '#000000' );
		$var['--ok-check'] = dn_readable( $c['groen'], $block, '#FFFFFF', 3 );
	} elseif ( isset( $var['--block'] ) ) {
		$var['--ok-check'] = dn_readable( '#86EFAC', $block, dn_luminance( $block ) < 0.2 ? '#FFFFFF' : '#000000', 3 );
	}

	$css = '';
	if ( $var ) {
		$css .= ':root{' . dn_css_vars( $var ) . '}';
	}

	// Donkere modus: alleen de primaire kleur gaat mee, op de donkere achtergrond van het thema.
	if ( deedni_dark_mode() && $primair_changed ) {
		$accent = $c['primair']['kleur'];
		$dark   = dn_css_vars(
			array(
				'--accent'      => $accent,
				'--accent-fg'   => $c['primair']['tekst'],
				'--accent-text' => dn_readable( $accent, '#0F1626', '#FFFFFF' ),
				'--accent-soft' => dn_mix( $accent, '#182238', 0.8 ),
				'--accent-heading' => dn_readable( $accent, '#0F1626', '#FFFFFF', 3 ),
			)
		);
		$css .= '@media (prefers-color-scheme: dark){:root:not([data-theme="light"]){' . $dark . '}}:root[data-theme="dark"]{' . $dark . '}';
	}
	return $css;
}

function dn_css_vars( $vars ) {
	$out = '';
	foreach ( $vars as $name => $value ) {
		$out .= $name . ':' . $value . ';';
	}
	return $out;
}

/** Staat de donkere modus aan? Standaard wel, zoals in het ontwerp. */
function deedni_dark_mode() {
	if ( ! function_exists( 'get_field' ) ) {
		return true;
	}
	$value = get_field( 'donkere_modus', 'option' );
	return null === $value || (bool) $value;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		$css = deedni_style_css();
		if ( $css ) {
			wp_add_inline_style( 'deedni', $css );
		}
	},
	20
);

// Donkere modus uit: de pagina vraagt de browser om de lichte versie.
add_filter(
	'language_attributes',
	function ( $output ) {
		return deedni_dark_mode() || is_admin() ? $output : $output . ' data-theme="light"';
	}
);
