<?php
/**
 * Terugval voor alles wat geen eigen template heeft (blog, archief, zoeken).
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="wrap page-head">
	<h1>
		<?php
		if ( is_search() ) {
			printf( 'Zoekresultaten voor “%s”', esc_html( get_search_query() ) );
		} elseif ( is_archive() ) {
			echo esc_html( wp_strip_all_tags( get_the_archive_title() ) );
		} elseif ( is_singular() ) {
			single_post_title();
		} else {
			echo esc_html( get_bloginfo( 'name' ) );
		}
		?>
	</h1>
</div>
<div class="wrap page-body">
	<?php if ( is_singular() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article class="entry"><?php the_content(); ?></article>
		<?php endwhile; ?>
	<?php elseif ( have_posts() ) : ?>
		<div class="post-list">
			<?php while ( have_posts() ) : the_post(); ?>
				<article class="post-card">
					<p class="meta"><?php echo esc_html( get_the_date() ); ?></p>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php the_excerpt(); ?>
				</article>
			<?php endwhile; ?>
		</div>
		<?php the_posts_navigation( array( 'prev_text' => 'Oudere berichten', 'next_text' => 'Nieuwere berichten' ) ); ?>
	<?php else : ?>
		<p>Er is hier nog niets te zien.</p>
	<?php endif; ?>
</div>
<?php
get_footer();
