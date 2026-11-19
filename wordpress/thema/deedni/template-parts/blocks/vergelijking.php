<?php
/**
 * Block: Vergelijkingstabel.
 */

defined( 'ABSPATH' ) || exit;

$table_id = 'vergelijk-' . wp_unique_id();
// Eerst de koppen ophalen: have_rows() hieronder verplaatst de context naar de rijen.
$koppen = array( get_sub_field( 'kop_1' ), get_sub_field( 'kop_2' ), get_sub_field( 'kop_3' ) );
?>
<section <?php echo dn_section_attrs( '', 'wit' ); // phpcs:ignore ?>>
	<div class="wrap">
		<div id="<?php echo esc_attr( $table_id ); ?>"><?php dn_section_head( false ); ?></div>
		<?php if ( have_rows( 'rijen' ) ) : ?>
			<div class="table-scroll" role="region" aria-labelledby="<?php echo esc_attr( $table_id ); ?>" tabindex="0">
				<table class="compare">
					<thead>
						<tr>
							<th scope="col"><span class="visually-hidden">Kenmerk</span></th>
							<th scope="col"><?php echo esc_html( $koppen[0] ); ?></th>
							<th scope="col"><?php echo esc_html( $koppen[1] ); ?></th>
							<th scope="col" class="us"><?php echo esc_html( $koppen[2] ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php while ( have_rows( 'rijen' ) ) : the_row(); ?>
							<tr>
								<th scope="row"><?php echo esc_html( get_sub_field( 'label' ) ); ?></th>
								<td><?php echo esc_html( get_sub_field( 'waarde_1' ) ); ?></td>
								<td><?php echo esc_html( get_sub_field( 'waarde_2' ) ); ?></td>
								<td class="us"><?php echo esc_html( get_sub_field( 'waarde_3' ) ); ?></td>
							</tr>
						<?php endwhile; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</section>
