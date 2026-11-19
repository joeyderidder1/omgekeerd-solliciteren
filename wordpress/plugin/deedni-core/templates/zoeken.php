<?php
/**
 * Zoeken en filteren voor werkgevers. Overschrijven in je thema: /deedni/zoeken.php
 *
 * @var array    $filters Actieve filters.
 * @var WP_Query $query   Resultaten.
 * @var int      $paged   Huidige pagina.
 */

defined( 'ABSPATH' ) || exit;

$base = deedni_page_url( 'zoeken' );

$select = function ( $name, $label, $options, $current, $empty = 'Alle' ) {
	echo '<div class="field"><label for="dn-f-' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label><select id="dn-f-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '">';
	echo '<option value="">' . esc_html( $empty ) . '</option>';
	foreach ( $options as $value => $text ) {
		echo '<option value="' . esc_attr( $value ) . '"' . selected( (string) $current, (string) $value, false ) . '>' . esc_html( $text ) . '</option>';
	}
	echo '</select></div>';
};
$term_options = function ( $taxonomy ) {
	return wp_list_pluck( deedni_filter_terms( $taxonomy ), 'name', 'slug' );
};
$active = array_filter( $filters );
?>
<div class="dn-search">
	<form class="dn-filters" method="get" action="<?php echo esc_url( $base ); ?>" role="search" aria-label="Kandidaten filteren">
		<div class="field">
			<label for="dn-f-q">Zoekwoord</label>
			<input id="dn-f-q" type="search" name="q" value="<?php echo esc_attr( $filters['q'] ); ?>" placeholder="Bijv. Shopify of B2B">
		</div>
		<?php
		$select( 'specialisme', 'Specialisme', $term_options( 'specialisme' ), $filters['specialisme'] );
		$select( 'provincie', 'Provincie', $term_options( 'provincie' ), $filters['provincie'] );
		$select( 'werkvorm', 'Werkplek', deedni_choices( 'werkvorm' ), $filters['werkvorm'], 'Maakt niet uit' );
		$select( 'ervaring', 'Ervaring', array( 2 => '2 jaar of meer', 5 => '5 jaar of meer', 8 => '8 jaar of meer' ), $filters['ervaring'], 'Maakt niet uit' );
		$select( 'beschikbaarheid', 'Beschikbaar', deedni_choices( 'beschikbaarheid' ), $filters['beschikbaarheid'], 'Maakt niet uit' );
		?>
		<div class="field">
			<label for="dn-f-salaris">Salariswens tot</label>
			<input id="dn-f-salaris" type="number" name="salaris_max" min="0" step="250" inputmode="numeric" value="<?php echo $filters['salaris_max'] ? esc_attr( $filters['salaris_max'] ) : ''; ?>" placeholder="Bijv. 4800">
			<span class="hint">Bruto per maand</span>
		</div>
		<details class="dn-tools"<?php echo $filters['vaardigheid'] ? ' open' : ''; ?>>
			<summary>Tools<?php echo $filters['vaardigheid'] ? ' (' . count( $filters['vaardigheid'] ) . ')' : ''; ?></summary>
			<div class="dn-tools__list">
				<?php foreach ( deedni_filter_terms( 'vaardigheid' ) as $term ) : ?>
					<label class="dn-chip-check"><input type="checkbox" name="vaardigheid[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $filters['vaardigheid'], true ) ); ?>><span><?php echo esc_html( $term->name ); ?></span></label>
				<?php endforeach; ?>
			</div>
		</details>
		<div class="field dn-check">
			<label><input type="checkbox" name="actief" value="1" <?php checked( $filters['actief'] ); ?>> Alleen actief op zoek</label>
		</div>
		<div class="dn-filters__actions">
			<button class="btn btn--primary" type="submit">Toon kandidaten</button>
			<?php if ( $active ) : ?>
				<a class="link-btn" href="<?php echo esc_url( $base ); ?>">Wis filters</a>
			<?php endif; ?>
		</div>
	</form>

	<section class="dn-results" aria-labelledby="dn-results-title">
		<h2 id="dn-results-title" class="dn-results__count">
			<?php
			/* translators: %d: aantal kandidaten */
			echo esc_html( sprintf( _n( '%d marketeer gevonden', '%d marketeers gevonden', $query->found_posts, 'deedni' ), $query->found_posts ) );
			?>
		</h2>

		<?php if ( $query->have_posts() ) : ?>
			<div class="dn-grid">
				<?php
				foreach ( $query->posts as $post ) {
					echo deedni_template( 'kandidaat-kaart', array( 'id' => $post->ID ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
			<?php
			if ( $query->max_num_pages > 1 ) {
				echo '<nav class="dn-pagination" aria-label="Paginering">';
				echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput
					array(
						'base'      => add_query_arg( 'pagina', '%#%', remove_query_arg( 'pagina' ) ),
						'format'    => '',
						'current'   => $paged,
						'total'     => $query->max_num_pages,
						'prev_text' => 'Vorige',
						'next_text' => 'Volgende',
					)
				);
				echo '</nav>';
			}
			?>
		<?php else : ?>
			<div class="dn-panel dn-empty">
				<p><strong>Geen marketeers die op alle filters passen.</strong></p>
				<p>Haal een filter weg, bijvoorbeeld een tool of de provincie.</p>
			</div>
		<?php endif; ?>
	</section>
</div>
