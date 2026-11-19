<?php
/**
 * Block: Hero met doelgroepkeuze.
 */

defined( 'ABSPATH' ) || exit;

$keuze     = (bool) get_sub_field( 'toon_keuze' );
$audiences = $keuze ? array( 'marketeer' => 'kandidaat', 'werkgever' => 'werkgever' ) : array( 'marketeer' => 'kandidaat' );
$anchor    = sanitize_title( (string) get_sub_field( 'anker' ) );
$title_id  = 'hero-' . wp_unique_id();

// data-for laat de JavaScript wisselen tussen marketeer en werkgever; zonder keuze is dat niet nodig.
$for = function ( $role ) use ( $keuze ) {
	return $keuze ? ' data-for="' . esc_attr( $role ) . '"' . ( 'werkgever' === $role ? ' hidden' : '' ) : '';
};
$default_url = function ( $role ) {
	return is_user_logged_in() ? deedni_theme_url( 'dashboard' ) : deedni_theme_url( 'registreren', array( 'rol' => $role ) );
};
?>
<section class="hero" aria-labelledby="<?php echo esc_attr( $title_id ); ?>"<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?>>
	<div class="wrap">
		<div class="hero-copy">
			<?php if ( $keuze ) : ?>
				<div class="audience" role="group" aria-label="Ik ben">
					<button type="button" data-set-audience="kandidaat" aria-pressed="true"><?php echo esc_html( get_sub_field( 'marketeer_keuze' ) ); ?></button>
					<button type="button" data-set-audience="werkgever" aria-pressed="false"><?php echo esc_html( get_sub_field( 'werkgever_keuze' ) ); ?></button>
				</div>
			<?php endif; ?>

			<?php foreach ( $audiences as $who => $role ) : ?>
				<div<?php echo $for( $role ); // phpcs:ignore ?>>
					<h1<?php echo 'marketeer' === $who ? ' id="' . esc_attr( $title_id ) . '"' : ''; ?>><?php echo dn_accent( get_sub_field( $who . '_titel' ) ); // phpcs:ignore ?></h1>
				</div>
			<?php endforeach; ?>

			<?php foreach ( $audiences as $who => $role ) : ?>
				<?php if ( get_sub_field( $who . '_intro' ) ) : ?>
					<p class="lead"<?php echo $for( $role ); // phpcs:ignore ?>><?php echo esc_html( get_sub_field( $who . '_intro' ) ); ?></p>
				<?php endif; ?>
			<?php endforeach; ?>

			<div class="cta-row">
				<?php foreach ( $audiences as $who => $role ) : ?>
					<?php
					$link = get_sub_field( $who . '_knop_link' );
					$url  = ! empty( $link['url'] ) ? $link['url'] : $default_url( $role );
					$text = get_sub_field( $who . '_knop_tekst' );
					if ( ! $text && ! empty( $link['title'] ) ) {
						$text = $link['title'];
					}
					if ( $text ) :
						?>
						<a class="btn btn--accent" href="<?php echo esc_url( $url ); ?>"<?php echo $for( $role ); // phpcs:ignore ?>><?php echo esc_html( $text ) . dn_arrow_svg(); // phpcs:ignore ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php
				$link_2 = get_sub_field( 'knop_2_link' );
				if ( get_sub_field( 'knop_2_tekst' ) && ! empty( $link_2['url'] ) ) {
					printf( '<a class="btn btn--ghost" href="%s">%s</a>', esc_url( $link_2['url'] ), esc_html( get_sub_field( 'knop_2_tekst' ) ) );
				} else {
					echo dn_link( $link_2, 'btn btn--ghost' ); // phpcs:ignore
				}
				?>
			</div>

			<?php
			foreach ( $audiences as $who => $role ) {
				echo dn_checks( dn_repeater_texts( get_sub_field( $who . '_usps' ) ), $for( $role ) ); // phpcs:ignore
			}
			?>
			<?php if ( get_sub_field( 'notitie' ) ) : ?>
				<p class="branch-note"><?php echo dn_bold( get_sub_field( 'notitie' ) ); // phpcs:ignore ?></p>
			<?php endif; ?>
		</div>

		<div class="hero-demo">
			<?php foreach ( $audiences as $who => $role ) : ?>
				<?php
				if ( ! get_sub_field( $who . '_demo_titel' ) ) {
					continue;
				}
				$badge  = get_sub_field( $who . '_demo_badge' );
				$meta   = get_sub_field( $who . '_demo_meta' );
				$feiten = get_sub_field( $who . '_demo_feiten' ) ? get_sub_field( $who . '_demo_feiten' ) : array();
				$tags   = array_filter( array_map( 'trim', explode( ',', (string) get_sub_field( $who . '_demo_tags' ) ) ) );
				?>
				<div<?php echo $for( $role ); // phpcs:ignore ?>>
					<?php if ( get_sub_field( $who . '_demo_label' ) ) : ?>
						<p class="demo-label"><span><?php echo esc_html( get_sub_field( $who . '_demo_label' ) ); ?></span><span>Voorbeeld</span></p>
					<?php endif; ?>
					<article class="card<?php echo 'marketeer' === $who ? ' card--arrive' : ''; ?>" aria-label="Voorbeeld">
						<div class="card-top">
							<?php if ( 'marketeer' === $who ) : ?>
								<?php if ( $badge ) : ?><span class="badge badge--new"><?php echo esc_html( $badge ); ?></span><?php endif; ?>
								<span><?php echo esc_html( $meta ); ?></span>
							<?php else : ?>
								<span><?php echo esc_html( $meta ); ?></span>
								<?php if ( $badge ) : ?><span class="badge badge--ok"><?php echo esc_html( $badge ); ?></span><?php endif; ?>
							<?php endif; ?>
						</div>
						<div>
							<div class="card-title"><?php echo esc_html( get_sub_field( $who . '_demo_titel' ) ); ?></div>
							<?php if ( get_sub_field( $who . '_demo_subtitel' ) ) : ?>
								<div class="card-sub"><?php echo esc_html( get_sub_field( $who . '_demo_subtitel' ) ); ?></div>
							<?php endif; ?>
						</div>
						<?php if ( $tags ) : ?>
							<div class="tags"><?php foreach ( $tags as $tag ) { echo '<span class="tag">' . esc_html( $tag ) . '</span>'; } ?></div>
						<?php endif; ?>
						<?php if ( $feiten ) : ?>
							<div class="facts">
								<?php foreach ( $feiten as $feit ) : ?>
									<div class="fact"><small><?php echo esc_html( $feit['label'] ); ?></small><strong><?php echo esc_html( $feit['waarde'] ); ?></strong></div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<?php if ( get_sub_field( $who . '_demo_quote' ) ) : ?>
							<blockquote><?php echo esc_html( get_sub_field( $who . '_demo_quote' ) ); ?></blockquote>
						<?php endif; ?>
						<?php if ( get_sub_field( $who . '_demo_vergrendeld' ) ) : ?>
							<div class="locked" aria-label="Verborgen tot de kandidaat ja zegt">
								<div><span>Naam</span><i></i></div>
								<div><span>Cv en contactgegevens</span><i></i></div>
							</div>
						<?php endif; ?>
						<?php if ( get_sub_field( $who . '_demo_knop_1' ) || get_sub_field( $who . '_demo_knop_2' ) ) : ?>
							<div class="card-actions" data-demo-actions>
								<?php foreach ( array( 1, 2 ) as $n ) : ?>
									<?php if ( get_sub_field( $who . '_demo_knop_' . $n ) ) : ?>
										<?php $class = 2 === $n ? 'btn--ghost' : ( 'marketeer' === $who ? 'btn--primary' : 'btn--accent' ); ?>
										<button class="btn <?php echo esc_attr( $class ); ?>" type="button" data-demo="<?php echo esc_attr( get_sub_field( $who . '_demo_reactie_' . $n ) ); ?>"><?php echo esc_html( get_sub_field( $who . '_demo_knop_' . $n ) ); ?></button>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
							<div class="card-result" role="status" data-demo-result hidden></div>
						<?php endif; ?>
					</article>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
