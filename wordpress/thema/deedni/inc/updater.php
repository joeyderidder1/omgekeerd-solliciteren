<?php
/**
 * Updates voor deedni Core en het deedni-thema rechtstreeks uit GitHub Releases.
 *
 * WordPress vraagt een paar keer per dag of er updates zijn. Deze klasse kijkt dan naar de nieuwste
 * release van de repository en zoekt daarin de zip van dit onderdeel (deedni-core-X.Y.Z.zip of
 * deedni-thema-X.Y.Z.zip). Is X.Y.Z hoger dan de geïnstalleerde versie, dan staat de update gewoon
 * onder Dashboard > Updates, net als bij elke andere plugin.
 *
 * Dit bestand staat identiek in de plugin (includes/updater.php) en in het thema (inc/updater.php).
 * Wie het eerst laadt, definieert de klasse; de ander gebruikt die.
 *
 * Instellen in wp-config.php (allebei optioneel, nooit in de code zelf):
 *   define( 'DEEDNI_GITHUB_TOKEN', 'github_pat_...' ); // alleen nodig als de repository privé wordt
 *   define( 'DEEDNI_UPDATES_UIT', true );              // updates via GitHub uitzetten
 *
 * Beveiliging:
 * - Er wordt alleen een zip geaccepteerd van de releases van precies deze repository.
 * - Het token gaat alleen mee naar api.github.com, nooit naar de browser of een andere host.
 * - Bij een fout (GitHub onbereikbaar, rare inhoud) gebeurt er niets: dan is er gewoon geen update.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Deedni_GitHub_Updater' ) ) {

	class Deedni_GitHub_Updater {

		const REPO      = 'joeyderidder1/omgekeerd-solliciteren';
		const CACHE     = 'deedni_github_release';
		const CACHE_TTL = 6 * HOUR_IN_SECONDS;

		/** @var string 'plugin' of 'thema' */
		private $soort;
		/** @var string plugin-bestand (deedni-core/deedni-core.php) of themamap (deedni) */
		private $id;
		/** @var string voorvoegsel van de zip in de release */
		private $asset;
		/** @var string geïnstalleerde versie */
		private $versie;

		/** Eén keer per verzoek de release ophalen, ook als plugin en thema allebei vragen. */
		private static $release = null;
		private static $download_hook = false;

		public static function plugin( $bestand, $versie ) {
			return new self( 'plugin', plugin_basename( $bestand ), 'deedni-core', $versie );
		}

		public static function thema( $map, $versie ) {
			return new self( 'thema', $map, 'deedni-thema', $versie );
		}

		private function __construct( $soort, $id, $asset, $versie ) {
			if ( defined( 'DEEDNI_UPDATES_UIT' ) && DEEDNI_UPDATES_UIT ) {
				return;
			}
			$this->soort  = $soort;
			$this->id     = $id;
			$this->asset  = $asset;
			$this->versie = (string) $versie;

			if ( 'plugin' === $soort ) {
				add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'controleer' ) );
				add_filter( 'plugins_api', array( $this, 'details' ), 10, 3 );
			} else {
				add_filter( 'pre_set_site_transient_update_themes', array( $this, 'controleer' ) );
			}

			if ( ! self::$download_hook ) {
				self::$download_hook = true;
				add_filter( 'upgrader_pre_download', array( __CLASS__, 'download_prive' ), 10, 2 );
				add_action( 'upgrader_process_complete', array( __CLASS__, 'leeg_cache' ) );
			}
		}

		/** Zet de update (of "geen update") in de lijst die WordPress bijhoudt. */
		public function controleer( $transient ) {
			if ( ! is_object( $transient ) ) {
				return $transient;
			}
			$nieuw = $this->nieuwste();
			if ( ! $nieuw ) {
				return $transient;
			}

			if ( 'plugin' === $this->soort ) {
				$item = (object) array(
					'id'           => self::REPO,
					'slug'         => dirname( $this->id ),
					'plugin'       => $this->id,
					'new_version'  => $nieuw['versie'],
					'url'          => $nieuw['pagina'],
					'package'      => $nieuw['zip'],
					'requires_php' => '8.0',
				);
			} else {
				$item = array(
					'theme'        => $this->id,
					'new_version'  => $nieuw['versie'],
					'url'          => $nieuw['pagina'],
					'package'      => $nieuw['zip'],
					'requires_php' => '8.0',
				);
			}

			if ( version_compare( $nieuw['versie'], $this->versie, '>' ) ) {
				$transient->response[ $this->id ] = $item;
				unset( $transient->no_update[ $this->id ] );
			} else {
				// Ook "geen update" melden, dan toont WordPress de knop voor automatische updates.
				$transient->no_update[ $this->id ] = $item;
				unset( $transient->response[ $this->id ] );
			}
			return $transient;
		}

		/** Het venster "Details bekijken" bij Plugins. */
		public function details( $resultaat, $actie, $args ) {
			if ( 'plugin_information' !== $actie || empty( $args->slug ) || dirname( $this->id ) !== $args->slug ) {
				return $resultaat;
			}
			$nieuw = $this->nieuwste();
			if ( ! $nieuw ) {
				return $resultaat;
			}
			return (object) array(
				'name'          => 'deedni Core',
				'slug'          => $args->slug,
				'version'       => $nieuw['versie'],
				'author'        => 'deedni',
				'homepage'      => $nieuw['pagina'],
				'download_link' => $nieuw['zip'],
				'requires_php'  => '8.0',
				'last_updated'  => $nieuw['datum'],
				'sections'      => array(
					'changelog' => '<p>' . nl2br( esc_html( $nieuw['notities'] ) ) . '</p><p><a href="' . esc_url( $nieuw['pagina'] ) . '">Bekijk de release op GitHub</a></p>',
				),
			);
		}

		/** De nieuwste versie van dit onderdeel volgens de laatste release, of null. */
		private function nieuwste() {
			$release = self::release();
			if ( empty( $release['assets'] ) || ! is_array( $release['assets'] ) ) {
				return null;
			}
			$patroon = '/^' . preg_quote( $this->asset, '/' ) . '-(\d+\.\d+\.\d+)\.zip$/';
			foreach ( $release['assets'] as $asset ) {
				if ( ! is_array( $asset ) || empty( $asset['name'] ) || ! preg_match( $patroon, $asset['name'], $m ) ) {
					continue;
				}
				$zip = self::token() ? (string) $asset['url'] : (string) $asset['browser_download_url'];
				if ( ! self::is_eigen_zip( $zip ) ) {
					return null;
				}
				return array(
					'versie'   => $m[1],
					'zip'      => $zip,
					'pagina'   => 'https://github.com/' . self::REPO . '/releases/tag/' . rawurlencode( (string) $release['tag_name'] ),
					'notities' => isset( $release['body'] ) ? (string) $release['body'] : '',
					'datum'    => isset( $release['published_at'] ) ? (string) $release['published_at'] : '',
				);
			}
			return null;
		}

		/** Alleen zips uit de releases van deze repository. */
		private static function is_eigen_zip( $url ) {
			$publiek = 'https://github.com/' . self::REPO . '/releases/download/';
			$api     = 'https://api.github.com/repos/' . self::REPO . '/releases/assets/';
			return 0 === strpos( $url, $publiek ) || 0 === strpos( $url, $api );
		}

		private static function token() {
			return defined( 'DEEDNI_GITHUB_TOKEN' ) && is_string( DEEDNI_GITHUB_TOKEN ) && '' !== DEEDNI_GITHUB_TOKEN ? DEEDNI_GITHUB_TOKEN : '';
		}

		private static function headers( $accept ) {
			$headers = array(
				'Accept'               => $accept,
				'X-GitHub-Api-Version' => '2022-11-28',
			);
			if ( self::token() ) {
				$headers['Authorization'] = 'Bearer ' . self::token();
			}
			return $headers;
		}

		/** De laatste release, zes uur bewaard. "Opnieuw controleren" onder Dashboard > Updates haalt hem vers op. */
		private static function release() {
			if ( null !== self::$release ) {
				return self::$release;
			}
			$vers = isset( $_GET['force-check'] ) && current_user_can( 'update_plugins' ); // phpcs:ignore WordPress.Security.NonceVerification
			$bewaard = get_site_transient( self::CACHE );
			if ( ! $vers && is_array( $bewaard ) ) {
				self::$release = $bewaard;
				return $bewaard;
			}

			$antwoord = wp_remote_get(
				'https://api.github.com/repos/' . self::REPO . '/releases/latest',
				array(
					'timeout' => 10,
					'headers' => self::headers( 'application/vnd.github+json' ),
				)
			);
			$release = array();
			if ( ! is_wp_error( $antwoord ) && 200 === wp_remote_retrieve_response_code( $antwoord ) ) {
				$data = json_decode( wp_remote_retrieve_body( $antwoord ), true );
				if ( is_array( $data ) && ! empty( $data['tag_name'] ) ) {
					$release = array(
						'tag_name'     => (string) $data['tag_name'],
						'body'         => isset( $data['body'] ) ? (string) $data['body'] : '',
						'published_at' => isset( $data['published_at'] ) ? (string) $data['published_at'] : '',
						'assets'       => array(),
					);
					foreach ( isset( $data['assets'] ) && is_array( $data['assets'] ) ? $data['assets'] : array() as $a ) {
						$release['assets'][] = array(
							'name'                 => isset( $a['name'] ) ? (string) $a['name'] : '',
							'url'                  => isset( $a['url'] ) ? (string) $a['url'] : '',
							'browser_download_url' => isset( $a['browser_download_url'] ) ? (string) $a['browser_download_url'] : '',
						);
					}
				}
			}
			// Ook een mislukte poging bewaren (een uur), zodat een storing bij GitHub wp-admin niet traag maakt.
			set_site_transient( self::CACHE, $release, $release ? self::CACHE_TTL : HOUR_IN_SECONDS );
			self::$release = $release;
			return $release;
		}

		/**
		 * Alleen bij een privé repository: de zip via de API downloaden met het token. GitHub stuurt dan door
		 * naar een tijdelijke link op een andere host; die halen we op zonder token, zodat het token
		 * nooit buiten api.github.com komt.
		 */
		public static function download_prive( $antwoord, $package ) {
			$api = 'https://api.github.com/repos/' . self::REPO . '/releases/assets/';
			if ( false !== $antwoord || ! is_string( $package ) || 0 !== strpos( $package, $api ) || ! self::token() ) {
				return $antwoord;
			}
			$stap = wp_remote_get(
				$package,
				array(
					'timeout'     => 15,
					'redirection' => 0,
					'headers'     => self::headers( 'application/octet-stream' ),
				)
			);
			$doel = is_wp_error( $stap ) ? '' : wp_remote_retrieve_header( $stap, 'location' );
			if ( ! $doel || 0 !== strpos( $doel, 'https://' ) ) {
				return new WP_Error( 'deedni_download', 'De update kon niet bij GitHub worden opgehaald. Klopt DEEDNI_GITHUB_TOKEN in wp-config.php?' );
			}
			if ( ! function_exists( 'download_url' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			return download_url( $doel, 300 );
		}

		public static function leeg_cache() {
			delete_site_transient( self::CACHE );
			self::$release = null;
		}
	}
}
