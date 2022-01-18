<?php

class ESS_Import_Actions {

	/**
	 * The instance *Singleton* of this class
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Starter_Sites the *Singleton* instance.
	 */
	public static function getInstance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'everse-starter-sites' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'everse-starter-sites' ), '1.0' );
	}

	/**
	 * Register all action hooks for this class.
	 */

	public function register_hooks() {
		// Before content import.
		add_action( 'everse-starter-sites/before_content_import_execution', array( $this, 'before_content_import_action' ), 10, 3 );

		// After content import.
		add_action( 'everse-starter-sites/after_content_import_execution', array( $this, 'before_widget_import' ), 10, 3 );
		add_action( 'everse-starter-sites/after_content_import_execution', array( $this, 'widgets_import_start' ), 20, 3 );

		// Customizer import.
		add_action( 'everse-starter-sites/customizer_import_execution', array( $this, 'customizer_import' ), 10, 1 );

		// After full import action.
		add_action( 'everse-starter-sites/after_all_import_execution', array( $this, 'after_import_action' ), 10, 3 );

		// Special widget import cases.
		add_action( 'everse-starter-sites/widget_settings_array', array( $this, 'fix_menu_widget_ids' ) );
	}

	public function fix_menu_widget_ids( $widget ) {
		// Skip (no changes needed), if this is not a custom menu widget.
		if ( ! array_key_exists( 'nav_menu', $widget ) || empty( $widget['nav_menu'] ) || ! is_int( $widget['nav_menu'] ) ) {
			return $widget;
		}

		// Get import data, with new menu IDs.
		$ess                = Everse_Starter_Sites::getInstance();
		$content_import_data = $ess->importer->get_importer_data();
		$term_ids            = $content_import_data['mapping']['term_id'];

		// Set the new menu ID for the widget.
		$widget['nav_menu'] = $term_ids[ $widget['nav_menu'] ];

		return $widget;
	}

	public function widgets_import_start( $selected_import_files, $import_files, $selected_index ) {
		if ( ! empty( $selected_import_files['widgets'] ) ) {
			ESS_Widget_Importer::import( $selected_import_files['widgets'] );
		}
	}

	public function customizer_import( $selected_import_files ) {
		if ( ! empty( $selected_import_files['customizer'] ) ) {
			ESS_Customizer_Importer::import( $selected_import_files['customizer'] );
		}
	}

	public function before_content_import_action( $selected_import_files, $import_files, $selected_index ) {
		$this->do_import_action( 'everse-starter-sites/before_content_import', $import_files[ $selected_index ] );
	}

	public function before_widget_import( $selected_import_files, $import_files, $selected_index ) {
		$this->do_import_action( 'everse-starter-sites/before_widgets_import', $import_files[ $selected_index ] );
	}

	public function after_import_action( $selected_import_files, $import_files, $selected_index ) {
		$this->do_import_action( 'everse-starter-sites/after_import', $import_files[ $selected_index ] );
	}

	private function do_import_action( $action, $selected_import ) {

		if ( false !== has_action( $action ) ) {
			$ess          = Everse_Starter_Sites::getInstance();
			$log_file_path = $ess->get_log_file_path();

			ob_start();
				do_action( $action, $selected_import );
			$message = ob_get_clean();

			// Add this message to log file.
			$log_added = ESS_Helpers::append_to_file(
				$message,
				$log_file_path,
				$action
			);
		}
	}
}