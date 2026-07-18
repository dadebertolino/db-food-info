<?php
/**
 * DB_GitHub_Updater — aggiornamenti automatici da GitHub Releases.
 * Componente condiviso della DB Plugin Suite.
 *
 * NOTA: se nel tuo repository esiste già la versione canonica di questa
 * classe (references/GITHUB-UPDATER.md), sostituisci questo file con quella.
 *
 * @package DB_Plugin_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DB_GitHub_Updater' ) ) {

	class DB_GitHub_Updater {

		private $file;
		private $slug;
		private $basename;
		private $owner;
		private $repo;
		private $version;

		public function __construct( $file, $owner, $repo ) {
			$this->file     = $file;
			$this->owner    = $owner;
			$this->repo     = $repo;
			$this->basename = plugin_basename( $file );
			$this->slug     = dirname( $this->basename );

			$data          = get_file_data( $file, array( 'Version' => 'Version' ) );
			$this->version = $data['Version'];

			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
			add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
			add_filter( 'upgrader_source_selection', array( $this, 'fix_source_dir' ), 10, 4 );
		}

		private function get_release() {
			$cache_key = 'dbgu_' . md5( $this->owner . $this->repo );
			$release   = get_transient( $cache_key );
			if ( false !== $release ) {
				return $release;
			}
			$response = wp_remote_get(
				sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', $this->owner, $this->repo ),
				array( 'timeout' => 10, 'headers' => array( 'Accept' => 'application/vnd.github+json' ) )
			);
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				set_transient( $cache_key, array(), HOUR_IN_SECONDS );
				return array();
			}
			$release = json_decode( wp_remote_retrieve_body( $response ), true );
			set_transient( $cache_key, $release, 6 * HOUR_IN_SECONDS );
			return $release;
		}

		public function check_update( $transient ) {
			if ( empty( $transient->checked ) ) {
				return $transient;
			}
			$release = $this->get_release();
			if ( empty( $release['tag_name'] ) ) {
				return $transient;
			}
			$new_version = ltrim( $release['tag_name'], 'v' );
			if ( version_compare( $new_version, $this->version, '<=' ) ) {
				return $transient;
			}
			$package = ! empty( $release['assets'][0]['browser_download_url'] )
				? $release['assets'][0]['browser_download_url']
				: $release['zipball_url'];

			$transient->response[ $this->basename ] = (object) array(
				'slug'        => $this->slug,
				'plugin'      => $this->basename,
				'new_version' => $new_version,
				'url'         => $release['html_url'],
				'package'     => $package,
			);
			return $transient;
		}

		public function plugin_info( $result, $action, $args ) {
			if ( 'plugin_information' !== $action || empty( $args->slug ) || $args->slug !== $this->slug ) {
				return $result;
			}
			$release = $this->get_release();
			if ( empty( $release['tag_name'] ) ) {
				return $result;
			}
			return (object) array(
				'name'          => $this->slug,
				'slug'          => $this->slug,
				'version'       => ltrim( $release['tag_name'], 'v' ),
				'author'        => '<a href="https://www.davidebertolino.it">Davide Bertolino</a>',
				'homepage'      => $release['html_url'],
				'sections'      => array( 'changelog' => wp_kses_post( wpautop( $release['body'] ?? '' ) ) ),
				'download_link' => ! empty( $release['assets'][0]['browser_download_url'] )
					? $release['assets'][0]['browser_download_url']
					: $release['zipball_url'],
			);
		}

		public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra ) {
			if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->basename ) {
				return $source;
			}
			$desired = trailingslashit( $remote_source ) . $this->slug . '/';
			if ( untrailingslashit( $source ) !== untrailingslashit( $desired ) && is_dir( $source ) ) {
				global $wp_filesystem;
				if ( $wp_filesystem && $wp_filesystem->move( $source, $desired ) ) {
					return $desired;
				}
			}
			return $source;
		}
	}
}
