<?php

class ESS_Helpers {

	public static function validate_demo_files( $import_files ) {
		$import_file_info = array();

		foreach ( $import_files as $import_file ) {
			if ( self::is_validate_demo_name( $import_file ) ) {
				$import_file_info[] = $import_file;
			}
		}

		return $import_file_info;
	}

	private static function is_validate_demo_name( $import_file ) {
		if ( empty( $import_file['file_name'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Download import files. Content .xml and widgets .wie|.json files.
	 *
	 * @param  array  $import_file array with import file details.
	 * @param  string $date_start string of date and time.
	 * @return array|WP_Error array of paths to the downloaded files or WP_Error object with error message.
	 */
	public static function download_demo_files( $import_file, $date_start = '' ) {

		$downloaded_files = array();
		$upload_dir       = wp_upload_dir();
		$upload_path      = apply_filters( 'everse-starter-sites/upload_file_path', trailingslashit( $upload_dir['path'] ) );

		// ----- Set content file path -----
		// Check if 'file_url' is not defined. That would mean a local file.
		if ( empty( $import_file['file_url'] ) ) {
			if ( file_exists( $import_file['local_file'] ) ) {
				$downloaded_files['content'] = $import_file['local_file'];
			}
			else {
				return new WP_Error(
					'url_or_local_file_not_defined',
					sprintf(
						__( '"file_url" or "local_file" for %s%s%s are not defined!', 'everse-starter-sites' ),
						'<strong>',
						$import_file['file_name'],
						'</strong>'
					)
				);
			}
		} else {

			// Retrieve demo data content from the URL.
			$import_content = self::get_import_content_from_url( $import_file['file_url'], $import_file['file_name'] );

			// Return from this function if there was an error.
			if ( is_wp_error( $import_content ) ) {
				return $import_content;
			}

			// Setup filename path to save the data content.
			$import_file_path = $upload_path . apply_filters( 'everse-starter-sites/content_file_prefix', 'demo-content-import-file_' ) . $date_start . apply_filters( 'everse-starter-sites/content_file_suffix_and_file_extension', '.xml' );

			// Write data content to the file and return the file path on successful write.
			$downloaded_files['content'] = self::write_to_file( $import_content, $import_file_path );

			// Return from this function if there was an error.
			if ( is_wp_error( $downloaded_files['content'] ) ) {
				return $downloaded_files['content'];
			}
		}

		// Get widgets file as well. If defined!
		if ( ! empty( $import_file['widget_file_url'] ) ) {

			// Retrieve widget content from the URL.
			$import_widgets_content = self::get_import_content_from_url( $import_file['widget_file_url'], $import_file['file_name'] );

			// Return from this function if there was an error.
			if ( is_wp_error( $import_widgets_content ) ) {
				return $import_widgets_content;
			}

			// Setup filename path to save the widget content.
			$import_widgets_file_path = $upload_path . apply_filters( 'everse-starter-sites/widgets_file_prefix', 'demo-widgets-import-file_' ) . $date_start . apply_filters( 'everse-starter-sites/widgets_file_suffix_and_file_extension', '.json' );

			// Write widget content to the file and return the file path on successful write.
			$downloaded_files['widgets'] = self::write_to_file( $import_widgets_content, $import_widgets_file_path );

			// Return from this function if there was an error.
			if ( is_wp_error( $downloaded_files['widgets'] ) ) {
				return $downloaded_files['widgets'];
			}
		}
		else if ( ! empty( $import_file['local_widget_file'] ) ) {
			if ( file_exists( $import_file['local_widget_file'] ) ) {
				$downloaded_files['widgets'] = $import_file['local_widget_file'];
			}
		}

		// Get customizer import file as well. If defined!
		if ( ! empty( $import_file['customizer_url'] ) ) {

			// Retrieve customizer content from the URL.
			$import_customizer_content = self::get_import_content_from_url( $import_file['customizer_url'], $import_file['file_name'] );

			// Return from this function if there was an error.
			if ( is_wp_error( $import_customizer_content ) ) {
				return $import_customizer_content;
			}

			// Setup filename path to save the customizer content.
			$import_customizer_file_path = $upload_path . apply_filters( 'everse-starter-sites/customizer_file_prefix', 'demo-customizer-import-file_' ) . $date_start . apply_filters( 'everse-starter-sites/customizer_file_suffix_and_file_extension', '.dat' );

			// Write customizer content to the file and return the file path on successful write.
			$downloaded_files['customizer'] = self::write_to_file( $import_customizer_content, $import_customizer_file_path );

			// Return from this function if there was an error.
			if ( is_wp_error( $downloaded_files['customizer'] ) ) {
				return $downloaded_files['customizer'];
			}
		}
		else if ( ! empty( $import_file['local_customizer_file'] ) ) {
			if ( file_exists( $import_file['local_customizer_file'] ) ) {
				$downloaded_files['customizer'] = $import_file['local_customizer_file'];
			}
		}

		return $downloaded_files;
	}

	/**
	 * Helper function: get content from an url.
	 *
	 * @param string $url URL to the content file.
	 * @param string $file_name optional, name of the file (used in the error reports).
	 * @return string|WP_Error, content from the URL or WP_Error object with error message
	 */
	private static function get_import_content_from_url( $url, $file_name = 'Import file' ) {

		// Test if the URL to the file is defined.
		if ( empty( $url ) ) {
			return new WP_Error(
				'url_not_defined',
				sprintf(
					__( 'URL for %s%s%s file is not defined!', 'everse-starter-sites' ),
					'<strong>',
					$file_name,
					'</strong>'
				)
			);
		}

		// Get file content from the server.
		$response = wp_remote_get(
			$url,
			array( 'timeout' => apply_filters( 'everse-starter-sites/timeout_for_downloading_file', 20 ) )
		);

		if ( is_wp_error( $response ) || 200 !== $response['response']['code'] ) {

			// Collect the right format of error data (array or WP_Error).
			$response_error = self::get_error_from_response( $response );

			return new WP_Error(
				'file_fetching_error',
				sprintf(
					__( 'An error occurred while fetching %s%s%s file from the server!%sReason: %s - %s.', 'everse-starter-sites' ),
					'<strong>',
					$file_name,
					'</strong>',
					'<br>',
					$response_error['error_code'],
					$response_error['error_message']
				) . '<br>' .
				apply_filters( 'everse-starter-sites/message_after_fetching_file', '' )
			);
		}

		// Return content retrieved from the URL.
		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Write content to a file.
	 *
	 * @param string $content content to be saved to the file.
	 * @param string $file_path file path where the content should be saved.
	 * @return string|WP_Error path to the saved file or WP_Error object with error message.
	 */
	public static function write_to_file( $content, $file_path ) {

		// Verify WP file-system credentials.
		$verified_credentials = self::check_wp_filesystem_credentials();

		if ( is_wp_error( $verified_credentials ) ) {
			return $verified_credentials;
		}

		// By this point, the $wp_filesystem global should be working, so let's use it to create a file.
		global $wp_filesystem;

		if ( ! $wp_filesystem->put_contents( $file_path, $content ) ) {
			return new WP_Error(
				'failed_writing_file_to_server',
				sprintf(
					__( 'An error occurred while writing file to your server! Tried to write a file to: %s%s.', 'everse-starter-sites' ),
					'<br>',
					$file_path
				)
			);
		}

		// Return the file path on successful file write.
		return $file_path;
	}

	/**
	 * Append content to the file.
	 *
	 * @param string $content content to be saved to the file.
	 * @param string $file_path file path where the content should be saved.
	 * @param string $separator_text separates the existing content of the file with the new content.
	 * @return boolean|WP_Error, path to the saved file or WP_Error object with error message.
	 */
	public static function append_to_file( $content, $file_path, $separator_text = '' ) {

		// Verify WP file-system credentials.
		$verified_credentials = self::check_wp_filesystem_credentials();

		if ( is_wp_error( $verified_credentials ) ) {
			return $verified_credentials;
		}

		// By this point, the $wp_filesystem global should be working, so let's use it to create a file.
		global $wp_filesystem;

		$existing_data = '';
		if ( file_exists( $file_path ) ) {
			$existing_data = $wp_filesystem->get_contents( $file_path );
		}

		// Style separator.
		$separator = PHP_EOL . '---' . $separator_text . '---' . PHP_EOL;

		if ( ! $wp_filesystem->put_contents( $file_path, $existing_data . $separator . $content . PHP_EOL ) ) {
			return new WP_Error(
				'failed_writing_file_to_server',
				sprintf(
					__( 'An error occurred while writing file to your server! Tried to write a file to: %s%s.', 'everse-starter-sites' ),
					'<br>',
					$file_path
				)
			);
		}

		return true;
	}

	/**
	 * Get data from a file
	 *
	 * @param string $file_path file path where the content should be saved.
	 * @return string $data, content of the file or WP_Error object with error message.
	 */
	public static function data_from_file( $file_path ) {

		// Verify WP file-system credentials.
		$verified_credentials = self::check_wp_filesystem_credentials();

		if ( is_wp_error( $verified_credentials ) ) {
			return $verified_credentials;
		}

		// By this point, the $wp_filesystem global should be working, so let's use it to read a file.
		global $wp_filesystem;

		$data = $wp_filesystem->get_contents( $file_path );

		if ( ! $data ) {
			return new WP_Error(
				'failed_reading_file_from_server',
				sprintf(
					__( 'An error occurred while reading a file from your server! Tried reading file from path: %s%s.', 'everse-starter-sites' ),
					'<br>',
					$file_path
				)
			);
		}

		// Return the file data.
		return $data;
	}

	/**
	 * Helper function: check for WP file-system credentials needed for reading and writing to a file.
	 *
	 * @return boolean|WP_Error
	 */
	private static function check_wp_filesystem_credentials() {

		// Check if the file-system method is 'direct', if not display an error.
		if ( ! ( 'direct' === get_filesystem_method() ) ) {
			return new WP_Error(
				'no_direct_file_access',
				sprintf(
					__( 'This WordPress page does not have %sdirect%s write file access. This plugin needs it in order to save the demo import xml file to the upload directory of your site.', 'everse-starter-sites' ),
					'<strong>',
					'</strong>'
				)
			);
		}

		// Get plugin page settings.
		$plugin_page_setup = apply_filters( 'everse-starter-sites/plugin_page_setup', array(
				'parent_slug' => 'themes.php',
				'page_title'  => esc_html__( 'Everse Starter Sites' , 'everse-starter-sites' ),
				'menu_title'  => esc_html__( 'Everse Starter Sites' , 'everse-starter-sites' ),
				'capability'  => 'import',
				'menu_slug'   => 'everse-starter-sites',
			)
		);

		// Get user credentials for WP file-system API.
		$import_page_url = wp_nonce_url( $plugin_page_setup['parent_slug'] . '?page=' . $plugin_page_setup['menu_slug'], $plugin_page_setup['menu_slug'] );

		if ( false === ( $creds = request_filesystem_credentials( $import_page_url, '', false, false, null ) ) ) {
			return new WP_error(
				'filesystem_credentials_could_not_be_retrieved',
				__( 'An error occurred while retrieving reading/writing permissions to your server (could not retrieve WP filesystem credentials)!', 'everse-starter-sites' )
			);
		}

		// Now we have credentials, try to get the wp_filesystem running.
		if ( ! WP_Filesystem( $creds ) ) {
			return new WP_Error(
				'wrong_login_credentials',
				__( 'Your WordPress login credentials don\'t allow to use WP_Filesystem!', 'everse-starter-sites' )
			);
		}

		return true;
	}

	/**
	 * Helper function: get the right format of response errors
	 *
	 * @param array|WP_Error $response array or WP_Error.
	 * @return array, with error code and error message.
	 */
	private static function get_error_from_response( $response ) {
		$response_error = array();

		if ( is_array( $response ) ) {
			$response_error['error_code']    = $response['response']['code'];
			$response_error['error_message'] = $response['response']['message'];
		}
		else {
			$response_error['error_code']    = $response->get_error_code();
			$response_error['error_message'] = $response->get_error_message();
		}

		return $response_error;
	}

	/**
	 * Get log file path
	 *
	 * @param string $date_start date|time|timestamp to use in the log filename.
	 * @return string, path to the log file
	 */
	public static function get_log_path( $date_start = '' ) {

		$upload_dir  = wp_upload_dir();
		$upload_path = apply_filters( 'everse-starter-sites/upload_file_path', trailingslashit( $upload_dir['path'] ) );

		$log_path = $upload_path . apply_filters( 'everse-starter-sites/log_file_prefix', 'log_file_' ) . $date_start . apply_filters( 'everse-starter-sites/log_file_suffix_and_file_extension', '.txt' );

		self::register_file_as_media_attachment( $log_path );

		return $log_path;
	}

	/**
	 * Register file as attachment to the Media page.
	 *
	 * @param string $log_path log file path.
	 * @return void
	 */
	public static function register_file_as_media_attachment( $log_path ) {

		// Check the type of file.
		$log_mimes = array( 'txt' => 'text/plain' );
		$filetype  = wp_check_filetype( basename( $log_path ), apply_filters( 'everse-starter-sites/file_mimes', $log_mimes ) );

		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           => self::get_log_url( $log_path ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => apply_filters( 'everse-starter-sites/attachment_prefix', esc_html__( 'Everse Starter Sites - ', 'everse-starter-sites' ) ) . preg_replace( '/\.[^.]+$/', '', basename( $log_path ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Insert the file as attachment in Media page.
		$attach_id = wp_insert_attachment( $attachment, $log_path );
	}


	/**
	 * Get log file url
	 *
	 * @param string $log_path log path to use for the log filename.
	 * @return string, url to the log file.
	 */
	public static function get_log_url( $log_path ) {

		$upload_dir = wp_upload_dir();
		$upload_url = apply_filters( 'everse-starter-sites/upload_file_url', trailingslashit( $upload_dir['url'] ) );

		return $upload_url . basename( $log_path );
	}

	/**
	 * Check if the AJAX call is valid.
	 */
	public static function verify_ajax_call() {

		check_ajax_referer( 'ess-ajax-verification', 'security' );

		// Check if user has the WP capability to import data.
		if ( ! current_user_can( 'import' ) ) {
			wp_die(
				sprintf(
					__( '%sYour user role is not high enough. You do not have permission to import demo data.%s', 'everse-starter-sites' ),
					'<div class="notice  notice-error is-dismissible"><p>',
					'</p></div>'
				)
			);
		}
	}

	/**
	 * Get import file information and max execution time.
	 *
	 * @param array $selected_import_files array of selected import files.
	 */
	public static function import_file_info( $selected_import_files ) {
		return PHP_EOL .
		sprintf(
			__( 'Initial max execution time = %s', 'everse-starter-sites' ),
			ini_get( 'max_execution_time' )
		) . PHP_EOL .
		sprintf(
			__( 'Files info:%1$sSite URL = %2$s%1$sData file = %3$s%1$sWidget file = %4$s%1$sCustomizer file = %5$s', 'everse-starter-sites' ),
			PHP_EOL,
			get_site_url(),
			$selected_import_files['content'],
			empty( $selected_import_files['widgets'] ) ? esc_html__( 'not defined!', 'everse-starter-sites' ) : $selected_import_files['widgets'],
			empty( $selected_import_files['customizer'] ) ? esc_html__( 'not defined!', 'everse-starter-sites' ) : $selected_import_files['customizer']
		);
	}

	/**
	 * Write the error to the log file and send the AJAX response.
	 *
	 * @param string $error_text text to display in the log file and in the AJAX response.
	 * @param string $log_file_path path to the log file.
	 * @param string $separator title separating the old and new content.
	 */
	public static function log_error_and_send_ajax_response( $error_text, $log_file_path, $separator = '' ) {

		// Add this error to log file.
		$log_added = self::append_to_file(
			$error_text,
			$log_file_path,
			$separator
		);

		// Send JSON Error response to the AJAX call.
		wp_send_json( $error_text );
	}

	public static function get_all_demo_categories( $demo_imports ) {
		$categories = array();

		foreach ( $demo_imports as $item ) {
			if ( ! isset( $item['categories'] ) ) {
				continue;
			}
			
			if ( ! empty( $item['categories'] ) && is_array( $item['categories'] ) ) {
				foreach ( $item['categories'] as $category ) {
					$categories[ sanitize_key( $category ) ] = $category;
				}
			}
		}

		if ( empty( $categories ) ) {
			return false;
		}

		return $categories;
	}

	public static function get_demo_item_categories( $item ) {
		$sanitized_categories = array();

		if ( isset( $item['categories'] ) ) {
			foreach ( $item['categories'] as $category ) {
				$sanitized_categories[] = sanitize_key( $category );
			}
		}

		if ( ! empty( $sanitized_categories ) ) {
			return implode( ',', $sanitized_categories );
		}

		return false;
	}

	public static function set_ess_data_transient( $data ) {
		set_transient( 'ESS_importer_data', $data, 0.1 * HOUR_IN_SECONDS );
	}
}
