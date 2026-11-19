<?php
/**
 * Een anoniem profiel in de zoekresultaten. Overschrijven in je thema: /deedni/kandidaat-kaart.php
 *
 * @var int $id Kandidaat-ID.
 */

defined( 'ABSPATH' ) || exit;

$jaren  = (int) deedni_field( $id, 'ervaring_jaren' );
$regio  = deedni_field( $id, 'regio' ) ? deedni_field( $id, 'regio' ) : implode( ', ', deedni_term_names( $id, 'provincie' ) );
$sub    = array_filter( array( $jaren ? sprintf( '%d jaar ervaring', $jaren ) : '', $regio, deedni_label( 'werkvorm', deedni_field( $id, 'werkvorm' ) ) ) );
$tags   = array_slice( array_merge( deedni_term_names( $id, 'specialisme' ), deedni_term_names( $id, 'vaardigheid' ) ), 0, 7 );
$status = deedni_field( $id, 'zoekstatus' );
$sent   = deedni_offers_between( $id, get_current_user_id() );
?>
<article class="card dn-card">
	<div class="card-top">
		<span>Kandidaat <?php echo esc_html( deedni_code( $id ) ); ?></span>
		<?php if ( $sent ) : ?>
			<span class="badge badge--new">Aanbod gestuurd</span>
		<?php elseif ( 'actief' === $status ) : ?>
			<span class="badge badge--ok">Actief op zoek</span>
		<?php else : ?>
			<span class="badge badge--soft">Staat open</span>
		<?php endif; ?>
	</div>
	<div>
		<h3 class="card-title"><a href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php echo esc_html( deedni_field( $id, 'functietitel' ) ); ?></a></h3>
		<p class="card-sub"><?php echo esc_html( implode( ' · ', $sub ) ); ?></p>
	</div>
	<?php if ( $tags ) : ?>
		<div class="tags">
			<?php foreach ( $tags as $tag ) : ?>
				<span class="tag"><?php echo esc_html( $tag ); ?></span>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<div class="facts">
		<div class="fact"><small>Salaris vanaf</small><strong><?php echo deedni_field( $id, 'salaris_min' ) ? esc_html( deedni_euro( deedni_field( $id, 'salaris_min' ) ) ) : 'Open'; ?></strong></div>
		<div class="fact"><small>Uren</small><strong><?php echo esc_html( deedni_label( 'uren', deedni_field( $id, 'uren' ) ) ? str_replace( ' uur', '', deedni_label( 'uren', deedni_field( $id, 'uren' ) ) ) : '–' ); ?></strong></div>
		<div class="fact"><small>Beschikbaar</small><strong><?php echo esc_html( deedni_label( 'beschikbaarheid', deedni_field( $id, 'beschikbaarheid' ) ) ? deedni_label( 'beschikbaarheid', deedni_field( $id, 'beschikbaarheid' ) ) : '–' ); ?></strong></div>
	</div>
	<div class="card-actions">
		<a class="btn btn--accent" href="<?php echo esc_url( get_permalink( $id ) ); ?>">Bekijk profiel<span class="visually-hidden"> van <?php echo esc_html( deedni_code( $id ) ); ?></span></a>
	</div>
</article>
