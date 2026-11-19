<?php
/**
 * Voet van elke pagina.
 */

defined( 'ABSPATH' ) || exit;
?>
</main>

<footer class="site-footer">
	<div class="wrap">
		<span class="logo">deedni<span>.</span></span>
		<nav aria-label="Footer">
			<?php
			deedni_theme_menu_links(
				'footer',
				array(
					'zo-werkt-het' => 'Zo werkt het',
					'prijzen'      => 'Prijzen',
					'vragen'       => 'Vragen',
					'privacy'      => 'Privacy',
				)
			);
			if ( get_privacy_policy_url() ) {
				printf( '<a href="%s">Privacyverklaring</a>', esc_url( get_privacy_policy_url() ) );
			}
			?>
		</nav>
		<span><?php echo function_exists( 'get_field' ) && get_field( 'footer_tekst', 'option' ) ? esc_html( get_field( 'footer_tekst', 'option' ) ) : '© ' . esc_html( wp_date( 'Y' ) ) . ' deedni'; ?></span>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
