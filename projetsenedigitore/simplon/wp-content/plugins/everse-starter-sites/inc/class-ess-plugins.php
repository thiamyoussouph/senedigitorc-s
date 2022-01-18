<?php

class ESS_Plugins {

	/**
	 * Instance
	 *
	 * @var $Everse_Demo_Import
	 */
	private static $instance;

	/**
	* Holds arrays of plugin details.
	*
	* @since 1.0.0
	* @since 2.5.0 the array has the plugin slug as an associative key.
	*
	* @var array
	*/
	public $plugins = array();

	/**
	 * Regular expression to test if a URL is a WP plugin repo URL.
	 *
	 * @const string Regex.
	 *
	 */
	const _WP_REPO_REGEX = '|^http[s]?://wordpress\.org/(?:extend/)?plugins/|';

	/**
	 * Arbitrary regular expression to test if a string starts with a URL.
	 *
	 * @const string Regex.
	 *
	 */
	const _IS_URL_REGEX = '|^http[s]?://|';

	/**
	 * Initiator
	 *
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function get_plugins_list( $plugins ) {

		$plugins_list = [];

		if ( ! isset( $plugins ) ) {
			return $plugins_list;
		}

		$plugins = $this->plugins_list( $plugins );

		foreach ( $plugins['all'] as $slug => $plugin ) {

			if ( isset( $plugins['install'][ $slug ] ) ) {
				$status = esc_html__( 'Not Installed', 'everse-starter-sites' );
			}

			if ( isset( $plugins['update'][ $slug ] ) ) {
				$status = esc_html__( 'Update Required', 'everse-starter-sites' );
			}

			if ( isset( $plugins['activate'][ $slug ] ) ) {
				$status = esc_html__( 'Installed But Not Active', 'everse-starter-sites' );
			}

			$plugin['status'] = $status;
			$plugins_list[] = $plugin;
		}

		return $plugins_list;
	}

	public function plugins_list( $plugins_list ) {

		$plugins  = [
			'all'      => [],
			'install'  => [],
			'update'   => [],
			'activate' => [],
		];

		if ( empty( $plugins_list ) ) {
			return $plugins;
		}

		$validate_plugins = [];

		foreach ( $plugins_list as $key => $value ) {
			$validate_plugins[ $value['slug'] ] = $this->validate_plugins( $value );
		}

		foreach ( $validate_plugins as $slug => $plugin ) {

			if ( $this->is_plugin_active( $plugin ) && false === $this->does_plugin_have_update( $plugin ) ) {
				// No need to display plugins if they are installed, up-to-date and active.
				continue;
			} else {
				$plugins['all'][ $slug ] = $plugin;

				if ( ! $this->is_plugin_installed( $plugin ) ) {
					$plugins['install'][ $slug ] = $plugin;
				} else {
					if ( false !== $this->does_plugin_have_update( $plugin ) ) {
						$plugins['update'][ $slug ] = $plugin;
					}
					if ( $this->can_plugin_activate( $plugin ) ) {
						$plugins['activate'][ $slug ] = $plugin;
					}
				}
			}
		}

		return $plugins;
	}

	public function can_plugin_activate( $slug ) {
		return ( ! $this->is_plugin_active( $slug ) && ! $this->does_plugin_require_update( $slug ) );
	}

	protected function _get_plugin_source_type( $source ) {
		if ( 'repo' === $source || preg_match( self::_WP_REPO_REGEX, $source ) ) {
			return 'repo';
		} elseif ( preg_match( self::_IS_URL_REGEX, $source ) ) {
			return 'external';
		} else {
			return 'bundled';
		}
	}


	/**
	* Retrieve the download URL for a package.
	*
	* @since 1.0.7
	*
	* @param string $plugin Plugin.
	* @return string Plugin download URL or path to local file or empty string if undetermined.
	*/
	public function get_download_url( $plugin ) {
		$dl_source = '';

		switch ( $plugin['source_type'] ) {
			case 'repo':
				return $this->get_wp_repo_download_url( $plugin['slug'] );
			case 'external':
				return $plugin['source'];
			case 'bundled':
				return '' . $plugin['source'];
		}

		return $dl_source; // Should never happen.
	}


	/**
	* Retrieve the download URL for a WP repo package.
	*
	* @since 1.0.7
	*
	* @param string $slug Plugin slug.
	* @return string Plugin download URL.
	*/
	protected function get_wp_repo_download_url( $slug ) {
		$source = '';
		$api    = $this->get_plugins_api( $slug );

		if ( false !== $api && isset( $api->download_link ) ) {
			$source = $api->download_link;
		}

		return $source;
	}


	/**
	* Try to grab information from WordPress API.
	*
	* @since 1.0.7
	*
	* @param string $slug Plugin slug.
	* @return object Plugins_api response object on success, WP_Error on failure.
	*/
	public function get_plugins_api( $slug ) {
		static $api = array(); // Cache received responses.

		if ( ! isset( $api[ $slug ] ) ) {

			$response = plugins_api(
				'plugin_information',
				array(
					'slug'   => $slug
				)
			);

			$api[ $slug ] = false;

			if ( is_wp_error( $response ) ) {
				wp_die( esc_html( 'Something went wrong with the plugin API.', 'everse-starter-sites' ) );
			} else {
				$api[ $slug ] = $response;
			}
		}

		return $api[ $slug ];
	}


	/**
	 * Helper function to extract the plugin file path from the
	 * plugin slug, if the plugin is installed.
	 *
	 * @param string $slug Plugin slug (typically folder name) as provided by the developer.
	 *
	 * @return string|bool Either plugin file path for plugin if installed, or false.
	 */
	public function get_plugin_basename_from_slug( $slug ) {

		$keys = array_keys( get_plugins() );

		foreach ( $keys as $key ) {
			if ( preg_match( '/^' . $slug . '\//', $key ) ) {
				return $key;
			}
		}

		return $slug;
	}

	public function validate_plugins( $plugin ) {

		if ( empty( $plugin['slug'] ) || empty( $plugin['name'] ) ) {
			return false;
		}

		if ( empty( $plugin['slug'] ) || ! is_string( $plugin['slug'] ) ) {
			return false;
		}

		$defaults = array(
			'name'               => '',      // String
			'slug'               => '',      // String
			'source'             => 'repo',  // String
			'required'           => false,   // Boolean
			'version'            => '',      // String
			'force_activation'   => false,   // Boolean
			'force_deactivation' => false,   // Boolean
			'external_url'       => '',      // String
			'is_callable'        => '',      // String|Array.
		);

		// Prepare the received data.
		$plugin = wp_parse_args( $plugin, $defaults );

		// Standardize the received slug.
		$plugin['slug'] = $this->sanitize_key( $plugin['slug'] );

		// Forgive users for using string versions of booleans or floats for version number.
		$plugin['version']            = (string) empty( $plugin['version'] ) ? '' : $plugin['version'];
		$plugin['source']             = empty( $plugin['source'] ) ? 'repo' : $plugin['source'];
		$plugin['required']           = $plugin['required'];
		$plugin['force_activation']   = $plugin['force_activation'];
		$plugin['force_deactivation'] = $plugin['force_deactivation'];

		// Enrich the received data.
		$plugin['file_path']   = $this->get_plugin_basename_from_slug( $plugin['slug'] );
		$plugin['source_type'] = $this->_get_plugin_source_type( $plugin['source'] );

		$this->plugins[ $plugin['slug'] ]    = $plugin;

		return $plugin;
	}

	public function sanitize_key( $key ) {
		return preg_replace( '`[^A-Za-z0-9_-]`', '', $key );
	}

	public function is_plugin_active( $slug ) {
		return ( ( ! empty( $slug['is_callable'] ) && is_callable( $slug['is_callable'] ) ) || is_plugin_active( $slug['file_path'] ) );
	}

	public function get_installed_version( $slug ) {

		$installed_plugins = get_plugins();

		if ( ! empty( $installed_plugins[ $slug['file_path'] ]['Version'] ) ) {
			return $installed_plugins[ $slug['file_path'] ]['Version'];
		}

		return false;
	}

	public function does_plugin_require_update( $slug ) {
		$installed_version = $this->get_installed_version( $slug );

		if ( ! isset( $slug['version'] ) ) {
			return false;
		}
		
		$minimum_version   = $slug['version'];

		return version_compare( $minimum_version, $installed_version, '>' );
	}

	public function does_plugin_have_update( $slug ) {

		// Presume bundled and external plugins will point to a package which meets the minimum required version.
		if ( ! isset( $slug['source_type'] ) ) {
			return false;
		}

		if ( 'repo' !== $slug['source_type'] ) {
			if ( $this->does_plugin_require_update( $slug ) ) {
				return $slug['version'];
			}

			return false;
		}

		$repo_updates = get_site_transient( 'update_plugins' );

		if ( isset( $repo_updates->response[ $slug['file_path'] ]->new_version ) ) {
			return $repo_updates->response[ $slug['file_path'] ]->new_version;
		}

		return false;
	}

	/**
	 * Check if a plugin is installed. Does not take must-use plugins into account.
	 *
	 * @param string $plugin Plugin.
	 *
	 * @return bool True if installed, false otherwise.
	 */
	public function is_plugin_installed( $plugin ) {
		$installed_plugins = get_plugins();
		return ( ! empty( $installed_plugins[ $plugin['file_path'] ] ) );
	}
}

ESS_Plugins::get_instance();