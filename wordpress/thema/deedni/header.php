<?php
/**
 * Kop van elke pagina.
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip" href="#inhoud">Naar de inhoud</a>

<header class="site-header" id="site-header">
	<div class="wrap">
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>, naar de homepage">deedni<span>.</span></a>
		<button class="menu-toggle" type="button" id="menu-toggle" aria-expanded="false" aria-controls="site-nav">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>Menu
		</button>
		<nav class="nav" id="site-nav" aria-label="Hoofdmenu">
			<?php
			if ( is_user_logged_in() && function_exists( 'deedni_is_werkgever' ) ) {
				if ( deedni_can_search() ) {
					printf( '<a href="%s"%s>Kandidaten zoeken</a>', esc_url( deedni_theme_url( 'zoeken' ) ), deedni_is_page( 'zoeken' ) ? ' aria-current="page"' : '' );
				}
				if ( deedni_is_kandidaat() || deedni_is_werkgever() ) {
					printf( '<a href="%s"%s>%s</a>', esc_url( deedni_theme_url( 'profiel' ) ), deedni_is_page( 'profiel' ) ? ' aria-current="page"' : '', deedni_is_werkgever() ? 'Bedrijfsprofiel' : 'Mijn profiel' );
				}
				printf( '<a class="nav-quiet" href="%s">Uitloggen</a>', esc_url( wp_logout_url( home_url( '/' ) ) ) );
				printf( '<a class="btn btn--primary btn--sm" href="%s">Mijn deedni</a>', esc_url( deedni_theme_url( 'dashboard' ) ) );
			} else {
				deedni_theme_menu_links(
					'primary',
					array(
						'zo-werkt-het' => 'Zo werkt het',
						'privacy'      => 'Privacy',
						'prijzen'      => 'Prijzen',
						'vragen'       => 'Vragen',
					)
				);
				if ( function_exists( 'deedni_page_url' ) ) {
					printf( '<a href="%s">Inloggen</a>', esc_url( deedni_theme_url( 'inloggen' ) ) );
				}
				printf(
					'<a class="btn btn--primary btn--sm" href="%1$s" id="nav-cta" data-href-kandidaat="%1$s" data-href-werkgever="%2$s">Gratis profiel maken</a>',
					esc_url( deedni_theme_url( 'registreren', array( 'rol' => 'kandidaat' ) ) ),
					esc_url( deedni_theme_url( 'registreren', array( 'rol' => 'werkgever' ) ) )
				);
			}
			?>
		</nav>
	</div>
</header>

<main id="inhoud" tabindex="-1">
