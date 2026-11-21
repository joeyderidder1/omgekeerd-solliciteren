<?php
/**
 * Cijfers voor de kandidaat: hoe vaak is het profiel bekeken en door wat voor bedrijven ("Wie keek mee").
 * Een werkgever die hetzelfde profiel binnen 12 uur opnieuw opent, telt één keer.
 */

defined( 'ABSPATH' ) || exit;

function deedni_log_bekeken( $kandidaat_id, $user_id ) {
	$bedrijf = deedni_profile_id( $user_id, 'bedrijf' );
	$log     = deedni_list( $kandidaat_id, '_dn_bekeken' );
	foreach ( array_reverse( $log ) as $item ) {
		if ( (int) $item['u'] === (int) $user_id && $item['t'] > time() - 12 * HOUR_IN_SECONDS ) {
			return;
		}
	}
	$log[] = array( 't' => time(), 'u' => (int) $user_id, 'b' => (int) $bedrijf );
	update_post_meta( $kandidaat_id, '_dn_bekeken', array_slice( $log, -300 ) );
}

function deedni_aantal_bekeken( $kandidaat_id, $dagen = 7 ) {
	$sinds = time() - $dagen * DAY_IN_SECONDS;
	return count(
		array_filter(
			deedni_list( $kandidaat_id, '_dn_bekeken' ),
			function ( $i ) use ( $sinds ) {
				return $i['t'] >= $sinds;
			}
		)
	);
}

/** De laatste bedrijven die keken, zonder naam: "Een hoveniersbedrijf in Utrecht". */
function deedni_wie_keek_mee( $kandidaat_id, $aantal = 3 ) {
	$out  = array();
	$seen = array();
	foreach ( array_reverse( deedni_list( $kandidaat_id, '_dn_bekeken' ) ) as $item ) {
		if ( isset( $seen[ $item['u'] ] ) ) {
			continue;
		}
		$seen[ $item['u'] ] = true;
		$b                  = $item['b'] ? deedni_bedrijf( $item['b'] ) : null;
		$soort              = $b && $b['soort'] ? mb_strtolower( $b['soort'] ) : 'bedrijf';
		$tekst              = 'Een ' . $soort . ( $b && $b['plaats'] ? ' in ' . $b['plaats'] : '' );
		$out[]              = array( 'tekst' => $tekst, 'wanneer' => deedni_geleden( $item['t'] ) );
		if ( count( $out ) >= $aantal ) {
			break;
		}
	}
	return $out;
}
