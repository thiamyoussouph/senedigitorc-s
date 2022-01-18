<?php

class ESS_Importer {

	private $importer;

	private $microtime;

	public $logger;

	private $ess;

	public function __construct( $importer_options = array(), $logger = null ) {

		// Include files that are needed for WordPress Importer v2.
		$this->include_required_files();

		// Set the WordPress Importer v2 as the importer used in this plugin.
		// More: https://github.com/humanmade/WordPress-Importer.
		$this->importer = new ESS_WXR_Importer( $importer_options );

		// Set logger to the importer.
		$this->logger = $logger;
		if ( ! empty( $this->logger ) ) {
			$this->set_logger( $this->logger );
		}

		// WooCommerce product attributes registration.
		if ( class_exists( 'WooCommerce' ) ) {
			add_filter( 'wxr_importer.pre_process.term', array( $this, 'woocommerce_product_attributes_registration' ), 10, 1 );
		}

		$this->ess = Everse_Starter_Sites::getInstance();
	}

	/**
	 * Include required files.
	 */
	private function include_required_files() {
		defined( 'WP_LOAD_IMPORTERS' ) || define( 'WP_LOAD_IMPORTERS', true );
		require_once ABSPATH . '/wp-admin/includes/class-wp-importer.php';
		require ESS_DIR_PATH . 'inc/class-ess-wxr-importer.php';
	}

	/**
	 * Imports content from a WordPress export file.
	 *
	 * @param string $data_file path to xml file, file with WordPress export data.
	 */
	public function import( $data_file ) {
		$this->importer->import( $data_file );
	}

	/**
	 * Set the logger used in the import
	 *
	 * @param object $logger logger instance.
	 */
	public function set_logger( $logger ) {
		$this->importer->set_logger( $logger );
	}

	/**
	 * Get all protected variables from the HM_WXR_Importer needed for continuing the import.
	 */
	public function get_importer_data() {
		return $this->importer->get_importer_data();
	}

	/**
	 * Sets all protected variables from the HM_WXR_Importer needed for continuing the import.
	 *
	 * @param array $data with set variables.
	 */
	public function set_importer_data( $data ) {
		$this->importer->set_importer_data( $data );
	}

	public function import_content( $import_file_path ) {
		$this->microtime = microtime( true );

		// Increase PHP max execution time.
		set_time_limit( apply_filters( 'everse-starter-sites/set_time_limit_for_demo_data_import', 300 ) );

		// Disable import of authors.
		add_filter( 'wxr_importer.pre_process.user', '__return_false' );

		// Check, if we need to send another AJAX request and set the importing author to the current user.
		add_filter( 'wxr_importer.pre_process.post', array( $this, 'new_ajax_request_maybe' ) );

		// Disables generation of multiple image sizes (thumbnails) in the content import step.
		if ( ! apply_filters( 'everse-starter-sites/regenerate_thumbnails_in_content_import', true ) ) {
			add_filter( 'intermediate_image_sizes_advanced',
				function() {
					return null;
				}
			);
		}

		// Import content.
		if ( ! empty( $import_file_path ) ) {
			ob_start();
				$this->import( $import_file_path );
			$message = ob_get_clean();
		}

		// Return any error messages for the front page output (errors, critical, alert and emergency level messages only).
		return $this->logger->error_output;
	}

	/**
	 * Check if we need to create a new AJAX request, so that server does not timeout.
	 *
	 * @param array $data current post data.
	 * @return array
	 */
	public function new_ajax_request_maybe( $data ) {
		$time = microtime( true ) - $this->microtime;

		if ( $time > apply_filters( 'everse-starter-sites/time_for_one_ajax_call', 25 ) ) {
			$response = array(
				'status'  => 'newAJAX',
				'message' => 'Time for new AJAX request!: ' . $time,
			);

			// Add any output to the log file and clear the buffers.
			$message = ob_get_clean();

			if ( ! empty( $message ) ) {
				$this->ess->append_to_frontend_error_messages( $message );
			}

			// Add message to log file.
			$log_added = ESS_Helpers::append_to_file(
				__( 'New AJAX call!' , 'everse-starter-sites' ) . PHP_EOL . $message,
				$this->ess->get_log_file_path(),
				''
			);

			$this->set_current_import_data();

			// Send the request for a new AJAX call.
			wp_send_json( $response );
		}

		// Set importing author to the current user.
		// Fixes the [WARNING] Could not find the author for ... log warning messages.
		$current_user_obj    = wp_get_current_user();
		$data['post_author'] = $current_user_obj->user_login;

		return $data;
	}

	private function set_current_import_data() {
		$data = array_merge( $this->ess->get_current_importer_data(), $this->get_importer_data() );

		ESS_Helpers::set_ess_data_transient( $data );
	}

	/**
	* Hook into the pre-process term filter of the content import and register the
	* custom WooCommerce product attributes, so that the terms can then be imported normally.
	*
	* This should probably be removed once the WP importer 2.0 support is added in WooCommerce.
	*
	* Fixes: [WARNING] Failed to import pa_size L warnings in content import.
	* Code from: woocommerce/includes/admin/class-wc-admin-importers.php (ver 2.6.9).
	*
	* Github issue: https://github.com/awesomemotive/one-click-demo-import/issues/71
	*
	* @param  array $date The term data to import.
	* @return array       The unchanged term data.
	*/
	public function woocommerce_product_attributes_registration( $data ) {
		global $wpdb;

		if ( strstr( $data['taxonomy'], 'pa_' ) ) {
			if ( ! taxonomy_exists( $data['taxonomy'] ) ) {
				$attribute_name = wc_sanitize_taxonomy_name( str_replace( 'pa_', '', $data['taxonomy'] ) );

				// Create the taxonomy
				if ( ! in_array( $attribute_name, wc_get_attribute_taxonomies() ) ) {
					$attribute = array(
						'attribute_label'   => $attribute_name,
						'attribute_name'    => $attribute_name,
						'attribute_type'    => 'select',
						'attribute_orderby' => 'menu_order',
						'attribute_public'  => 0
					);
					$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );
					delete_transient( 'wc_attribute_taxonomies' );
				}

				// Register the taxonomy now so that the import works!
				register_taxonomy(
					$data['taxonomy'],
					apply_filters( 'woocommerce_taxonomy_objects_' . $data['taxonomy'], array( 'product' ) ),
					apply_filters( 'woocommerce_taxonomy_args_' . $data['taxonomy'], array(
						'hierarchical' => true,
						'show_ui'      => false,
						'query_var'    => true,
						'rewrite'      => false,
					) )
				);
			}
		}

		return $data;
	}
}
