<?php
/**
 * Main Everse Starter Site plugin class/file.
 *
 * @package everse-starter-sites
 */

class Everse_Starter_Sites {

	/**
	 * The instance *Singleton* of this class
	 *
	 * @var object
	 */
	private static $instance;

	public $importer;

	private $plugin_page;

	public $import_files;

	public $log_file_path;

	private $selected_index;

	private $selected_import_files;

	public $frontend_error_messages = array();

	private $before_content_executed = false;

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

	protected function __construct() {

		$this->include_required_files();

		add_action( 'admin_menu', [ $this, 'admin_menu' ], 9 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'wp_ajax_ess_ajax_get_demo_data', [ $this, 'everse_get_demo_data' ] );
		add_action( 'wp_ajax_ESS_import_demo_data', [ $this, 'everse_install_plugins' ] );
		add_action( 'wp_ajax_ESS_import_content', [ $this, 'everse_import_content' ] );
		add_action( 'wp_ajax_ess_import_customizer_data', [ $this, 'everse_import_customizer_data' ] );
		add_action( 'wp_ajax_ess_after_import_data', [ $this, 'everse_after_all_import_data' ], 99 );
		add_action( 'after_setup_theme', [ $this, 'setup_plugin_data' ] );
		
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );

		add_action( 'admin_footer', [ $this, 'load_popup_template' ] );

		add_action( 'everse-starter-sites/before_content_import', [ $this, 'before_import' ] );

		add_action( 'everse-starter-sites/after_import', [ $this, 'after_import' ] );

		everse_starter_sites_fs()->add_filter( 'plugin_icon', [ $this, 'plugin_custom_icon' ] );

		// Admin notice
		add_action( 'admin_notices', [ $this, 'admin_notice' ] );
		add_action( 'admin_head', [ $this, 'admin_notice_dismiss' ] );

		// Disable Woo Wizard
		add_filter('woocommerce_enable_setup_wizard', '__return_false');
		add_filter('woocommerce_show_admin_notice', '__return_false');
		add_filter('woocommerce_prevent_automatic_wizard_redirect', '__return_false');
	}


	/**
	* Before import
	*
	* The hook runs before import is completed.
	*
	* @since 1.0.5
	*/
	public function before_import( $selected_import ) {

		switch ( $selected_import['file_name'] ) {
			case 'Traveling':			

				update_option( 'everse_extensions_settings', array(
					'projects' 			=> '0',
					'services' 			=> '0',
					'tours' 				=> '1',
					'destinations'  => '1'
				) );
				
				if ( class_exists( 'Everse_Core_Extensions_Manager' ) ) {
					$manager = new Everse_Core_Extensions_Manager();
					$manager->register_cpt();
				}

				update_option( 'elementor_cpt_support', array(
					'post',
					'page',
					'tours',
					'destinations',
					'theme_template'
				) );
				break;			
		}

		// Reset permalinks to plain
		$permalinks = get_option('permalink_structure');

		// Abort if already saved to something else
		if ( ! $permalinks ) {
			return;
		}

		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure(null); 
		update_option( 'rewrite_rules', FALSE );
		$wp_rewrite->flush_rules( true );
	}

	/**
	* After import
	*
	* The hook runs after import is completed.
	*
	* @since 1.0.0
	*/
	public function after_import( $selected_import ) {
		// Assign menus to their locations.
		$primary_menu = get_term_by( 'name', 'Primary Menu', 'nav_menu' );
		$footer_bottom_menu = get_term_by( 'name', 'Footer Bottom Menu', 'nav_menu' );

		set_theme_mod( 'nav_menu_locations', array(
				'primary-menu' => $primary_menu->term_id,
				'footer-bottom-menu' => $footer_bottom_menu->term_id
			)
		);

		$front_page_id = get_page_by_title( 'Home' );
		$blog_page_id  = get_page_by_title( 'Blog' );

		update_option( 'page_on_front', $front_page_id->ID );
		update_option( 'show_on_front', 'page' );

		switch ( $selected_import['file_name'] ) {

			case 'Business':
			case 'Resume':
			case 'Construction':
			case 'Mobile App':
			case 'SaaS':
			case 'Consulting':
			case 'Fitness':
			case 'Christmas Promo':
			case 'Marketing Agency':
			case 'Wedding':
			case 'Digital Agency':
				update_option( 'elementor_active_kit', 9999 );

			case 'Business':
			case 'Digital Agency':
				update_option( 'page_for_posts', $blog_page_id->ID );				
				break;

			case 'Restaurant':
				update_option( 'elementor_active_kit', 556 );
				update_option( 'elementor_cpt_support', array(
					'post',
					'page',
					'theme_template'
				) );
				break;

			case 'Medical':
				update_option( 'elementor_active_kit', 81 );
				update_option( 'elementor_cpt_support', array(
					'post',
					'page',
					'theme_template'
				) );
				break;

			case 'Apparel Store':
				update_option( 'elementor_active_kit', 9999 );
				update_option( 'woocommerce_shop_page_id', 198 );
				update_option( 'woocommerce_cart_page_id', 199 );
				update_option( 'woocommerce_checkout_page_id', 200 );
				update_option( 'woocommerce_myaccount_page_id', 201 );
				update_option( 'elementor_cpt_support', array(
					'post',
					'page',
					'theme_template'
				) );
				break;

			case 'COVID-19':
				update_option( 'elementor_active_kit', 1560 );
				break;

			case 'Law Firm':
				update_option( 'elementor_active_kit', 537 );
				update_option( 'page_for_posts', $blog_page_id->ID );
				break;

			case 'LearnDash Courses':
				update_option( 'elementor_active_kit', 103 );
				$learndash_settings_theme_ld30 = array(
					'color_primary' => '#18C1CA',
					'color_secondary' => '#18c1ca',
					'color_tertiary' => '',
					'focus_mode_enabled' => 'yes',
					'focus_mode_content_width' => 'default',
					'login_mode_enabled' => 'yes',
					'login_logo' => 144,
					'responsive_video_enabled' => 'yes'
				);
				update_option( 'learndash_settings_theme_ld30', $learndash_settings_theme_ld30 );
				update_option( 'elementor_cpt_support', array(
					'post',
					'page',
					'theme_template'
				) );
				break;

			case 'Furniture Shop':
				update_option( 'elementor_active_kit', 9999 );
				update_option( 'woocommerce_shop_page_id', 523 );
				update_option( 'woocommerce_cart_page_id', 524 );
				update_option( 'woocommerce_checkout_page_id', 526 );
				update_option( 'woocommerce_myaccount_page_id', 528 );
				update_option( 'page_for_posts', $blog_page_id->ID );
				update_option( 'elementor_cpt_support', array(
					'post',
					'page',
					'theme_template'
				) );
				break;

			case 'Medical Supplies Store':
				update_option( 'elementor_active_kit', 9999 );
				update_option( 'woocommerce_shop_page_id', 585 );
				update_option( 'woocommerce_cart_page_id', 587 );
				update_option( 'woocommerce_checkout_page_id', 589 );
				update_option( 'woocommerce_myaccount_page_id', 591 );
				update_option( 'elementor_cpt_support', array(
					'post',
					'page',
					'theme_template'
				) );
				break;

			case 'Photography':
				update_option( 'elementor_active_kit', 9999 );

				if ( class_exists( 'RevSlider' ) ) {
					$slider_array = array(
						trailingslashit( ESS_DIR_PATH ) . 'inc/slides/photography-slider.zip',
						trailingslashit( ESS_DIR_PATH ) . 'inc/slides/video-modal.zip',
					);

					$slider = new RevSlider();

					foreach( $slider_array as $filepath ) {
						$slider->importSliderFromPost( true, true, $filepath );  
					}

					echo esc_html__( 'Slider processed', 'everse-starter-sites' );
				}
				break;

			case 'Traveling':
				update_option( 'elementor_active_kit', 9999 );

				if ( class_exists( 'RevSlider' ) ) {
					$slider_array = array(
						trailingslashit( ESS_DIR_PATH ) . 'inc/slides/traveling-hero.zip',
					);

					$slider = new RevSlider();

					foreach( $slider_array as $filepath ) {
						$slider->importSliderFromPost( true, true, $filepath );  
					}

					echo esc_html__( 'Slider processed', 'everse-starter-sites' );
				}
				break;
			
			default:
				break;
		}

		// Flush permalinks.
		flush_rewrite_rules();
		
	}

	public function load_popup_template() {

		global $pagenow;

        // Display on the demos pages
        if ('themes.php' == $pagenow && ( isset( $_GET['page'] ) && 'everse-starter-sites' == $_GET['page'] ) ) : ?>
	        	
	        <div id="ess-demo-popup-content"></div>
    	<?php
    	endif;
	}

	public function everse_get_demo_data() {

		if ( ! wp_verify_nonce( $_POST['ajax_nonce'], 'ess-ajax-verification' ) ) {
			wp_send_json( esc_html__( 'This action was stopped for security purposes', 'everse-starter-sites' ) );
		}

		$this->selected_index = empty( sanitize_text_field( $_POST['demo_name'] ) ) ? 0 : absint( $_POST['demo_name'] );

		if ( ! $this->import_files[ $this->selected_index ] ) {
			wp_send_json( esc_html__( 'This action was stopped for security purposes', 'everse-starter-sites' ) );
		}

		if ( isset( $this->import_files[ $this->selected_index ] ) ) {
			$this->import_files[ $this->selected_index ]['required_plugins'] = ESS_Plugins::get_instance()->get_plugins_list( $this->import_files[ $this->selected_index ]['required_plugins'] );
		}

		$this->import_files[ $this->selected_index ]['is_activated'] = false;

		if ( isset( $this->import_files[ $this->selected_index ]['is_pro'] ) && isset( $this->import_files[ $this->selected_index ]['required_plugins'] ) && everse_starter_sites_fs()->is_plan__premium_only( 'pro' ) ) {
			$this->import_files[ $this->selected_index ]['is_activated'] = true;
		}

		if ( empty( $this->import_files[ $this->selected_index ]['required_plugins'] ) ) {
			unset( $this->import_files[ $this->selected_index ]['required_plugins'] );
		}

		wp_send_json( $this->import_files[ $this->selected_index ] );
	}

	public function admin_enqueue_scripts( $hook ) {

		if ( $this->plugin_page === $hook ) {

			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_style( 'wp-jquery-ui-dialog' );

			wp_enqueue_script( 'ess-main-js', ESS_DIR_URL . 'assets/js/main.js', array( 'jquery', 'jquery-ui-dialog' ), ESS_VERSION, true );

			$theme_url = 'https://deothemes.com/wordpress-themes/everse-multi-purpose-elementor-wordpress-theme/';

			// Get theme data.
			$theme = wp_get_theme();

			wp_localize_script( 'ess-main-js', 'everse_starter_sites',
				array(
					'ajax_url'         => admin_url( 'admin-ajax.php' ),
					'ajax_nonce'       => wp_create_nonce( 'ess-ajax-verification' ),
					'import_files'       => $this->import_files,
					'theme_screenshot' => $theme->get_screenshot(),
					'strings'          => array(
						'pro_label'			=> esc_html__( 'Pro', 'everse-starter-sites' ),
						'pro_heading'		=> sprintf( 'This is premium template available with "%s" package. you can <a href="%s" target="_blank">%s</a>. If you already own an %s pack, read an article to know <a href="%s" target="_blank">%s</a>.', 'Everse Pro', esc_url( $theme_url . '?utm_source=starter-sites-link' ), esc_html__( 'purchase it from here', 'everse-starter-sites' ), 'Everse Pro', esc_url('https://docs.deothemes.com/everse/knowledgebase/importing-pro-templates/'), esc_html__( 'How you can import a premium website demo.', 'everse-starter-sites' ) ),
						'plugins_title'		=> sprintf( '<h2>%s</h2><p class="required-plugins-text">%s</p>', esc_html__( 'Required Plugins', 'everse-starter-sites' ), esc_html__( 'The following plugins will be installed and activated automatically.', 'everse-starter-sites' ) ),
						'learndash_text' => sprintf( '<h2>%s</h2><p class="required-plugins-text">%s</p>', esc_html__( 'Required Third Party Plugins', 'everse-starter-sites' ), esc_html__( 'This starter template requires premium plugins. As these are third party premium plugins, you\'ll need to purchase, install and activate them first.', 'everse-starter-sites' ) ),
						'proBtnText'      	=> esc_html__( 'Get Everse Pro Bundle ', 'everse-starter-sites' ),
						'proBtnURL'      	=> esc_url( $theme_url . '?utm_source=starter-sites-button' ),
						'cancel'            => esc_html__( 'Cancel', 'everse-starter-sites' ),
						'yes'            	=> esc_html__( 'Import', 'everse-starter-sites' ),
						'install_plugins' => esc_html__( 'Install and Activate Plugins', 'everse-starter-sites' ),
						'installing_plugins' => esc_html__( 'Installing, please wait', 'everse-starter-sites' ),
						'plugins_installed' => esc_html__( 'All plugins successfully installed and activated. ', 'everse-starter-sites' ),
					),
				)
			);

			wp_enqueue_style( 'ess-main-css', ESS_DIR_URL . 'assets/css/main.css', array() , ESS_VERSION );
		}
	}

	public function setup_plugin_data() {
		if ( ! ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) ) {
			return;
		}

		$this->import_files = ESS_Helpers::validate_demo_files( $this->load_import_files() );

		$ImportActions = new ESS_Import_Actions();
		$ImportActions->register_hooks();

		// Importer options array.
		$importer_options = apply_filters( 'everse-starter-sites/importer_options', array(
			'fetch_attachments' => true,
		) );

		// Logger options for the logger used in the importer.
		$logger_options = apply_filters( 'everse-starter-sites/logger_options', array(
			'logger_min_level' => 'warning',
		) );

		// Configure logger instance and set it to the importer.
		$this->logger            = new ESS_Logger();
		$this->logger->min_level = $logger_options['logger_min_level'];

		// Create importer instance with proper parameters.
		$this->importer = new ESS_Importer( $importer_options, $this->logger );
	}

	public function load_import_files() {
		$data = [];
		$base_url = 'https://everse.deothemes.com/demos';
		$theme = get_option('stylesheet');
		$customizer_file = ( 'everse-pro' === $theme ) ? 'customizer-pro.dat' : 'customizer.dat';
		$plugins = array(
			'revslider' 	=> 'https://everse.deothemes.com/plugins/revslider.6.5.6.zip',
			'everse-core'	=> 'https://everse.deothemes.com/plugins/everse-core.zip',
			'eversor'			=> 'https://everse.deothemes.com/plugins/eversor.zip'
		);

		$data = [

			array(
				'is_pro'				=> true,
				'categories'		=> [ 'Pro', 'Business' ],
				'file_name'			=> 'Traveling',
				'preview'				=> 'https://everse.deothemes.com/traveling',
				'file_url' 				 => $base_url . '/pro/21/demo-content.xml',
				'widget_file_url'  => $base_url . '/pro/21/widgets.wie',
				'customizer_url' 	 => $base_url . '/pro/21/customizer.dat',
				'preview_image' 	 => $base_url . '/pro/21/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
					array(
						'name'    => 'Mailchimp for WordPress',
						'slug'    => 'mailchimp-for-wp',
					),
					array(
						'name'    => 'Slider Revolution',
						'slug'    => 'revslider',
						'source'	=> $plugins['revslider'],
					),
					array(
						'name'		=> 'Everse Core',
						'slug'		=> 'everse-core',
						'source'	=> $plugins['everse-core'],
					),
					array(
						'name'		=> 'Eversor',
						'slug'		=> 'eversor',
						'source'	=> $plugins['eversor'],
					),		
				),
			),

			array(
				'is_pro'				=> true,
				'categories'		=> [ 'Pro', 'Business' ],
				'file_name'			=> 'Photography',
				'preview'				=> 'https://everse.deothemes.com/photography',
				'file_url' 				 => $base_url . '/pro/20/demo-content.xml',
				'widget_file_url'  => $base_url . '/pro/20/widgets.wie',
				'customizer_url' 	 => $base_url . '/pro/20/customizer.dat',
				'preview_image' 	 => $base_url . '/pro/20/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
					array(
						'name'    => 'Modula Image Gallery',
						'slug'    => 'modula-best-grid-gallery',
					),
					array(
						'name'    => 'Slider Revolution',
						'slug'    => 'revslider',
						'source'	=> $plugins['revslider'],
					),
					array(
						'name'		=> 'Everse Core',
						'slug'		=> 'everse-core',
						'source'	=> $plugins['everse-core'],
					),
					array(
						'name'		=> 'Eversor',
						'slug'		=> 'eversor',
						'source'	=> $plugins['eversor'],
					),	
				),
			),

			array(
				'categories'		=> [ 'Free', 'Business' ],
				'file_name'			=> 'Digital Agency',
				'preview'				=> 'https://everse.deothemes.com/digital-agency',
				'file_url' 				 => $base_url . '/free/19/demo-content.xml',
				'widget_file_url'  => $base_url . '/free/19/widgets.wie',
				'customizer_url' 	 => $base_url . '/free/19/' . $customizer_file,
				'preview_image' 	 => $base_url . '/free/19/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
					array(
						'name'    => 'Mailchimp for WordPress',
						'slug'    => 'mailchimp-for-wp',
					),
				),
			),

			array(
				'is_pro'				=> true,
				'categories'		=> [ 'Pro', 'eCommerce' ],
				'file_name'			=> 'Medical Supplies Store',
				'preview'				=> 'https://everse.deothemes.com/medical-supplies-store',
				'file_url' 				 => $base_url . '/pro/18/demo-content.xml',
				'widget_file_url'  => $base_url . '/pro/18/widgets.wie',
				'customizer_url' 	 => $base_url . '/pro/18/customizer.dat',
				'preview_image' 	 => $base_url . '/pro/18/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
					array(
						'name'    => 'WooCommerce',
						'slug'    => 'woocommerce',
					),
					array(
						'name'    => 'Mailchimp for WordPress',
						'slug'    => 'mailchimp-for-wp',
					),
					array(
						'name'		=> 'Everse Core',
						'slug'		=> 'everse-core',
						'source'	=> $plugins['everse-core'],
					),
					array(
						'name'		=> 'Eversor',
						'slug'		=> 'eversor',
						'source'	=> $plugins['eversor'],
					),
				),
			),

			array(
				'is_pro'				=> true,
				'categories'		=> [ 'Pro', 'eCommerce' ],
				'file_name'			=> 'Apparel Store',
				'preview'				=> 'https://everse.deothemes.com/apparel-store',
				'file_url' 				 => $base_url . '/pro/05/demo-content.xml',
				'widget_file_url'  => $base_url . '/pro/05/widgets.wie',
				'customizer_url'   => $base_url . '/pro/05/customizer.dat',
				'preview_image' 	 => $base_url . '/pro/05/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
					array(
						'name'    => 'WooCommerce',
						'slug'    => 'woocommerce',
					),
					array(
						'name'    => 'Mailchimp for WordPress',
						'slug'    => 'mailchimp-for-wp',
					),
					array(
						'name'		=> 'Everse Core',
						'slug'		=> 'everse-core',
						'source'	=> $plugins['everse-core'],
					),
					array(
						'name'		=> 'Eversor',
						'slug'		=> 'eversor',
						'source'	=> $plugins['eversor'],
					),
				),
			),			

			array(
				'categories'		=> [ 'Free', 'Onepage' ],
				'file_name'			=> 'COVID-19',
				'preview'				=> 'https://everse.deothemes.com/covid-19',
				'file_url' 				 => $base_url . '/free/06/demo-content.xml',
				'widget_file_url'  => $base_url . '/free/06/widgets.wie',
				'customizer_url'   => $base_url . '/free/06/' . $customizer_file,
				'preview_image' 	 => $base_url . '/free/06/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
				),
			),

			array(
				'is_pro'				=> true,
				'categories'		=> [ 'Pro', 'Business' ],
				'file_name'			=> 'Restaurant',
				'preview'				=> 'https://everse.deothemes.com/restaurant',
				'file_url' 				 => $base_url . '/pro/02/demo-content.xml',
				'widget_file_url'  => $base_url . '/pro/02/widgets.wie',
				'customizer_url' 	 => $base_url . '/pro/02/customizer.dat',
				'preview_image' 	 => $base_url . '/pro/02/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'  	=> 'Menu - Ordering - Reservations',
						'slug'  	=> 'menu-ordering-reservations',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
					array(
						'name'		=> 'Everse Core',
						'slug'		=> 'everse-core',
						'source'	=> $plugins['everse-core'],
					),
					array(
						'name'		=> 'Eversor',
						'slug'		=> 'eversor',
						'source'	=> $plugins['eversor'],
					),
				),
			),			

			array(
				'categories'		=> [ 'Free', 'eCommerce' ],
				'file_name'			=> 'Furniture Shop',
				'preview'				=> 'https://everse.deothemes.com/furniture-shop',
				'file_url' 				 => $base_url . '/free/16/demo-content.xml',
				'widget_file_url'  => $base_url . '/free/16/widgets.wie',
				'customizer_url' 	 => $base_url . '/free/16/' . $customizer_file,
				'preview_image' 	 => $base_url . '/free/16/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
					array(
						'name'    => 'WooCommerce',
						'slug'    => 'woocommerce',
					),
				),
			),
			
			array(
				'is_pro'				=> true,
				'categories'		=> [ 'Pro', 'Business' ],
				'file_name'			=> 'Medical',
				'preview'				=> 'https://everse.deothemes.com/medical',
				'file_url' 				 => $base_url . '/pro/03/demo-content.xml',
				'widget_file_url'  => $base_url . '/pro/03/widgets.wie',
				'customizer_url' 	 => $base_url . '/pro/03/customizer.dat',
				'preview_image' 	 => $base_url . '/pro/03/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
					array(
						'name'		=> 'Everse Core',
						'slug'		=> 'everse-core',
						'source'	=> $plugins['everse-core'],
					),
					array(
						'name'		=> 'Eversor',
						'slug'		=> 'eversor',
						'source'	=> $plugins['eversor'],
					),
				),
			),

			array(
				'is_pro'				=> true,
				'is_learndash'	=> true,
				'categories'		=> [ 'Pro', 'Business', 'eCommerce' ],
				'file_name'			=> 'LearnDash Courses',
				'preview'				=> 'https://everse.deothemes.com/learndash-courses',
				'file_url' 				 => $base_url . '/pro/13/demo-content.xml',
				'widget_file_url'  => $base_url . '/pro/13/widgets.wie',
				'customizer_url'	 => $base_url . '/pro/13/customizer.dat',
				'preview_image' 	 => $base_url . '/pro/13/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
					array(
						'name'    => 'WooCommerce',
						'slug'    => 'woocommerce',
					),
					array(
						'name'		=> 'Everse Core',
						'slug'		=> 'everse-core',
						'source'	=> $plugins['everse-core'],
					),
					array(
						'name'		=> 'Eversor',
						'slug'		=> 'eversor',
						'source'	=> $plugins['eversor'],
					),
				),
				'learndash_plugins' => array(
					array(
						'name' => 'LearnDash LMS',
						'url'  => 'https://www.learndash.com/'
					),
					array(
						'name' => 'LearnDash Course Grid',
						'url'  => 'https://www.learndash.com/add-on/course-grid/'
					),
					array(
						'name' => 'LearnDash WooCommerce Integration',
						'url'  => 'https://www.learndash.com/add-on/woocommerce/'
					),
				),
			),		

			array(
				'is_pro'				=> true,
				'categories'		=> [ 'Pro', 'Business' ],
				'file_name'			=> 'Construction',
				'preview'				=> 'https://everse.deothemes.com/construction',
				'file_url' 				 => $base_url . '/pro/07/demo-content.xml',
				'widget_file_url'  => $base_url . '/pro/07/widgets.wie',
				'customizer_url'   => $base_url . '/pro/07/customizer.dat',
				'preview_image' 	 => $base_url . '/pro/07/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
					array(
						'name'		=> 'Everse Core',
						'slug'		=> 'everse-core',
						'source'	=> $plugins['everse-core'],
					),
					array(
						'name'		=> 'Eversor',
						'slug'		=> 'eversor',
						'source'	=> $plugins['eversor'],
					),
				),
			),

			array(
				'categories'		=> [ 'Free', 'Onepage' ],
				'file_name'			=> 'Mobile App',
				'preview'				=> 'https://everse.deothemes.com/mobile-app',
				'file_url' 				 => $base_url . '/free/08/demo-content.xml',
				'widget_file_url'  => $base_url . '/free/08/widgets.wie',
				'customizer_url' 	 => $base_url . '/free/08/' . $customizer_file,
				'preview_image' 	 => $base_url . '/free/08/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
				),
			),

			array(
				'categories'		=> [ 'Free', 'Onepage' ],
				'file_name'			=> 'SaaS',
				'preview'				=> 'https://everse.deothemes.com/saas',
				'file_url' 				 => $base_url . '/free/09/demo-content.xml',
				'widget_file_url'  => $base_url . '/free/09/widgets.wie',
				'customizer_url'   => $base_url . '/free/09/' . $customizer_file,
				'preview_image' 	 => $base_url . '/free/09/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Mailchimp for WordPress',
						'slug'    => 'mailchimp-for-wp',
					),
				),
			),

			array(
				'is_pro'				=> true,
				'categories'		=> [ 'Pro', 'Business' ],
				'file_name'			=> 'Consulting',
				'preview'				=> 'https://everse.deothemes.com/consulting',
				'file_url' 				 => $base_url . '/pro/10/demo-content.xml',
				'widget_file_url'  => $base_url . '/pro/10/widgets.wie',
				'customizer_url' 	 => $base_url . '/pro/10/customizer.dat',
				'preview_image' 	 => $base_url . '/pro/10/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
					array(
						'name'		=> 'Everse Core',
						'slug'		=> 'everse-core',
						'source'	=> $plugins['everse-core'],
					),
					array(
						'name'		=> 'Eversor',
						'slug'		=> 'eversor',
						'source'	=> $plugins['eversor'],
					),
				),
			),

			array(
				'categories'		=> [ 'Free', 'Onepage' ],
				'file_name'			=> 'Fitness',
				'preview'				=> 'https://everse.deothemes.com/fitness',
				'file_url' 				 => $base_url . '/free/11/demo-content.xml',
				'widget_file_url'  => $base_url . '/free/11/widgets.wie',
				'customizer_url' 	 => $base_url . '/free/11/' . $customizer_file,
				'preview_image' 	 => $base_url . '/free/11/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
				),
			),

			array(
				'categories'		=> [ 'Free', 'Business' ],
				'file_name'			=> 'Law Firm',
				'preview'				=> 'https://everse.deothemes.com/law-firm',
				'file_url' 				 => $base_url . '/free/12/demo-content.xml',
				'widget_file_url'  => $base_url . '/free/12/widgets.wie',
				'customizer_url'	 => $base_url . '/free/12/' . $customizer_file,
				'preview_image' 	 => $base_url . '/free/12/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
				),
			),			

			array(
				'categories'		=> [ 'Free', 'Onepage' ],
				'file_name'			=> 'Christmas Promo',
				'preview'				=> 'https://everse.deothemes.com/christmas-promo',
				'file_url' 				 => $base_url . '/free/14/demo-content.xml',
				'widget_file_url'  => $base_url . '/free/14/widgets.wie',
				'customizer_url'	 => $base_url . '/free/14/' . $customizer_file,
				'preview_image' 	 => $base_url . '/free/14/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Mailchimp for WordPress',
						'slug'    => 'mailchimp-for-wp',
					),
				),
			),

			array(
				'is_pro'				=> true,
				'categories'		=> [ 'Pro', 'Business' ],
				'file_name'			=> 'Marketing Agency',
				'preview'				=> 'https://everse.deothemes.com/marketing-agency',
				'file_url' 				 => $base_url . '/pro/15/demo-content.xml',
				'widget_file_url'  => $base_url . '/pro/15/widgets.wie',
				'customizer_url' 	 => $base_url . '/pro/15/customizer.dat',
				'preview_image' 	 => $base_url . '/pro/15/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
					array(
						'name'		=> 'Everse Core',
						'slug'		=> 'everse-core',
						'source'	=> $plugins['everse-core'],
					),
					array(
						'name'		=> 'Eversor',
						'slug'		=> 'eversor',
						'source'	=> $plugins['eversor'],
					),
				),
			),


			array(
				'categories'		=> [ 'Free', 'Onepage' ],
				'file_name'			=> 'Resume',
				'preview'				=> 'https://everse.deothemes.com/resume',
				'file_url' 				 => $base_url . '/free/04/demo-content.xml',
				'widget_file_url'  => $base_url . '/free/04/widgets.wie',
				'customizer_url'   => $base_url . '/free/04/' . $customizer_file,
				'preview_image' 	 => $base_url . '/free/04/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
				),
			),

			array(
				'categories'		=> [ 'Free', 'Onepage' ],
				'file_name'			=> 'Wedding',
				'preview'				=> 'https://everse.deothemes.com/wedding',
				'file_url' 				 => $base_url . '/free/17/demo-content.xml',
				'widget_file_url'  => $base_url . '/free/17/widgets.wie',
				'customizer_url'   => $base_url . '/free/17/' . $customizer_file,
				'preview_image' 	 => $base_url . '/free/17/preview.jpg',
				'required_plugins' => array(
					array(
						'name'    => 'Kirki Customizer Framework',
						'slug'    => 'kirki',
					),
					array(
						'name'  	=> 'Elementor',
						'slug'  	=> 'elementor',
					),
					array(
						'name'    => 'Contact Form 7',
						'slug'    => 'contact-form-7',
					),
				),
			),

			array(
				'file_name'			=> 'Business',
				'categories'		=> [ 'Free', 'Business' ],
				'preview'				=> 'https://everse.deothemes.com',
				'file_url'				 => $base_url . '/free/01/demo-content.xml',
				'widget_file_url'	 => $base_url . '/free/01/widgets.wie',
				'customizer_url'	 => $base_url . '/free/01/' . $customizer_file,
				'preview_image' 	 => $base_url . '/free/01/preview.jpg',
				'required_plugins' => [
					array(
						'name'      => 'Kirki Customizer Framework',
						'slug'      => 'kirki',
						'required'  => true,
					),		

					array(
						'name'			=> 'Elementor',
						'slug'			=> 'elementor',
						'required'	=> true,
					),		

					array(
						'name'      => 'Contact Form 7',
						'slug'      => 'contact-form-7',
						'required'  => true,
					),
				]
			),

		];

		return apply_filters( 'everse_starter_sites_import_files', $data );
	}

	/**
	 * Import demo data.
	 * The hook runs on Import button click.
	 * 
	 * @since 1.0.0
	 */
	public function everse_import_demo_data() {

		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		ESS_Helpers::verify_ajax_call();

		// Install and activate plugins
		$this->everse_install_plugins();
		
	}

	/**
	 * Import content
	 * 
	 * @since 1.1
	 */
	public function everse_import_content() {

		// Try to update PHP memory limit (so that it does not run out of it).
		ini_set( 'memory_limit', apply_filters( 'everse-starter-sites/import_memory_limit', '350M' ) );

		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		ESS_Helpers::verify_ajax_call();

		// Is this a new AJAX call to continue the previous import?
		$use_existing_importer_data = $this->use_existing_importer_data();

		if ( ! $use_existing_importer_data ) {

			// Set the AJAX call number.
			$this->ajax_call_number = empty( $this->ajax_call_number ) ? 0 : $this->ajax_call_number;

			// Create a date and time string to use for demo and log file names.
			$demo_import_start_time = date( apply_filters( 'everse-starter-sites/date_format_for_file_names', 'Y-m-d__H-i-s' ) );

			// Define log file path.
			$this->log_file_path = ESS_Helpers::get_log_path( $demo_import_start_time );

			// Get selected file index or set it to 0.
			$this->selected_index = empty( $_POST['selected'] ) ? 0 : absint( $_POST['selected'] );

			if ( ! empty( $this->import_files[ $this->selected_index ] ) ) {

				// Download the import files (content and widgets files) and save it to variable for later use.
				$this->selected_import_files = ESS_Helpers::download_demo_files(
					$this->import_files[ $this->selected_index ],
					$demo_import_start_time
				);

				// Check Errors.
				if ( is_wp_error( $this->selected_import_files ) ) {

					// Write error to log file and send an AJAX response with the error.
					ESS_Helpers::log_error_and_send_ajax_response(
						$this->selected_import_files->get_error_message(),
						$this->log_file_path,
						esc_html__( 'Downloaded files', 'everse-starter-sites' )
					);
				}

				// Add this message to log file.
				$log_added = ESS_Helpers::append_to_file(
					sprintf(
						__( 'The import files for: %s were successfully downloaded!', 'everse-starter-sites' ),
						$this->import_files[ $this->selected_index ]['file_name']
					) . ESS_Helpers::import_file_info( $this->selected_import_files ),
					$this->log_file_path,
					esc_html__( 'Downloaded files' , 'everse-starter-sites' )
				);
			}
			else {

				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'everse-starter-sites' ) );
			}
		}

		// Save the initial import data as a transient, so other import parts (in new AJAX calls) can use that data.
		ESS_Helpers::set_ess_data_transient( $this->get_current_importer_data() );

		// Before content import
		if ( ! $this->before_content_executed ) {
			$this->before_content_executed = true;
			do_action( 'everse-starter-sites/before_content_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index );
		}

		// Run import content
		if ( ! empty( $this->selected_import_files['content'] ) ) {
			$this->append_to_frontend_error_messages( $this->importer->import_content( $this->selected_import_files['content'] ) );
		}

		// After content import
		do_action( 'everse-starter-sites/after_content_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index );

		ESS_Helpers::set_ess_data_transient( $this->get_current_importer_data() );

		// Request the customizer import AJAX call.
		if ( ! empty( $this->selected_import_files['customizer'] ) ) {
			wp_send_json( array( 'status' => 'customizerAJAX' ) );
		}

		// Request the after all import AJAX call.
		if ( false !== has_action( 'everse-starter-sites/after_all_import_execution' ) ) {
			wp_send_json( array( 'status' => 'afterAllImportAJAX' ) );
		}

		$this->final_report();
	}


	/**
	 * Install and activate all the required plugins.
	 *
	 * @return boolean
	 */
	public function everse_install_plugins() {

		$this->selected_index = empty( sanitize_text_field( $_POST['selected'] ) ) ? 0 : absint( $_POST['selected'] );

		if ( ! $this->import_files[ $this->selected_index ] ) {
			wp_send_json( esc_html__( 'This action was stopped for security purposes', 'everse-starter-sites' ) );
		}

		if ( isset( $this->import_files[ $this->selected_index ]['required_plugins'] ) ) {

			foreach ( $this->import_files[ $this->selected_index ]['required_plugins'] as $plugin ) {

				$plugin['file_path'] = ESS_Plugins::get_instance()->get_plugin_basename_from_slug( $plugin['slug'] );

				$plugin = ESS_Plugins::get_instance()->validate_plugins( $plugin );

				if ( ESS_Plugins::get_instance()->is_plugin_active( $plugin ) && false === ESS_Plugins::get_instance()->does_plugin_have_update( $plugin ) ) {
					// No need to display plugins if they are installed, up-to-date and active.
					continue;
				} else {

					// Check if user has the WP capability to install plugins.
					if ( ! current_user_can( 'install_plugins' ) ) {
						return false;
					}

					if ( ! function_exists( 'plugins_api' ) ) {
						require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
					}

					// Check for file system permissions.
					$this->filesystem_permissions_allowed();

					// Do not allow WordPress to search/download translations, as this will break JS output.
					remove_action( 'upgrader_process_complete', [ 'Language_Pack_Upgrader', 'async_upgrade' ], 20 );

					/* If we arrive here, we have the filesystem. */

					// Prep variables for Plugin_Installer_Skin class.
					$extra         = array();
					$extra['slug'] = $plugin['slug']; // Needed for potentially renaming of directory name.
					$source        = ESS_Plugins::get_instance()->get_download_url( $plugin );
					$api           = ( 'repo' === $plugin['source_type'] ) ? ESS_Plugins::get_instance()->get_plugins_api( $plugin['slug'] ) : null;
					$api           = ( false !== $api ) ? $api : null;

					$skin_args = array(
						'type'   => 'web',
						'plugin' => '',
						'api'    => $api,
						'extra'  => $extra,
					);

					// Needed for Plugin_Upgrader and Plugin_Installer_Skin
					if ( ! class_exists( 'Plugin_Upgrader', false ) ) {
						require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
					}

					//$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( $skin_args ) );

					// Installer / Upgrader skin
					$skin     = new WP_Ajax_Upgrader_Skin( $skin_args );
					$upgrader = new Plugin_Upgrader( $skin );

					// Install only if not installed
					if ( ! ESS_Plugins::get_instance()->is_plugin_installed( $plugin ) ) {
						$upgrader->install( $source );
					}

					// Activate the plugin if the plugin is already installed.
					if ( $api->name ) {

						$log_added = ESS_Helpers::append_to_file(
							$api->name,
							$this->log_file_path,
							esc_html__( 'Installing Plugins' , 'everse-starter-sites' )
						);

						activate_plugin( ESS_Plugins::get_instance()->get_plugin_basename_from_slug( $api->slug ) );
					} else {

						// ESS_Helpers::log_error_and_send_ajax_response(
						// 	'There was an error installing '. $api->name .'.',
						// 	$this->log_file_path,
						// 	esc_html__( 'Installing Plugins', 'everse-starter-sites' )
						// );

						activate_plugin( ESS_Plugins::get_instance()->get_plugin_basename_from_slug( $plugin['slug'] ) );
					}

				}
			}
		}

		wp_send_json( array( 'status' => 'pluginsInstalled' ) );
	}

	/**
	* Helper function to check for the filesystem permissions.
	*
	* @return bool
	*/
	private function filesystem_permissions_allowed() {

		// Pass necessary information via URL if WP_Filesystem is needed.
		$url = $this->get_ess_url();

		$method = ''; // Leave blank so WP_Filesystem can populate it as necessary.

		$creds = request_filesystem_credentials( $url, '', false, false, null );

		if ( false === $creds ) {
			return true;
		}

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( esc_url_raw( $url ), $method, true, false, array() ); // Setup WP_Filesystem.
			return true;
		}

	}


	/**
	 * Retrieve the URL to the TGMPA Install page.
	 *
	 * I.e. depending on the config settings passed something along the lines of:
	 * http://example.com/wp-admin/themes.php?page=tgmpa-install-plugins
	 *
	 * @since 1.0.7
	 *
	 * @return string Properly encoded URL (not escaped).
	 */
	public function get_ess_url() {
		static $url;

		if ( ! isset( $url ) ) {
			$parent = 'themes.php';
			if ( false === strpos( $parent, '.php' ) ) {
				$parent = 'admin.php';
			}
			$url = add_query_arg(
				array(
					'page' => urlencode( 'everse-starter-sites' ),
				),
				self_admin_url( $parent )
			);
		}

		return $url;
	}


	public function everse_import_customizer_data() {

		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		ESS_Helpers::verify_ajax_call();

		// Get existing import data.
		if ( $this->use_existing_importer_data() ) {
			do_action( 'everse-starter-sites/customizer_import_execution', $this->selected_import_files );
		}

		// Request the after all import AJAX call.
		if ( false !== has_action( 'everse-starter-sites/after_all_import_execution' ) ) {
			wp_send_json( array( 'status' => 'afterAllImportAJAX' ) );
		}

		$this->final_report();
	}

	/**
	 * AJAX callback for the after all import action.
	 */
	public function everse_after_all_import_data() {

		ESS_Helpers::verify_ajax_call();

		// Get existing import data.
		if ( $this->use_existing_importer_data() ) {
			do_action( 'everse-starter-sites/after_all_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index );
		}

		$this->final_report();
	}

	/**
	 * Send a JSON response with final report.
	 */
	private function final_report() {
		// Delete importer data transient for current import.
		delete_transient( 'ESS_importer_data' );

		// Display final messages (success or error messages).
		if ( empty( $this->frontend_error_messages ) ) {
			
			$response['message'] = sprintf(
				__( '%1$s%3$sThat\'s it, all done!%4$s%2$sThe import has finished. Please %6$scheck your site%7$s and make sure that everything has imported correctly.%5$s', 'everse-starter-sites' ),
				'<div class="notice  notice-success is-dismissible"><p>',
				'<br>',
				'<strong>',
				'</strong>',
				'</p></div>',
				'<a href="' . esc_url( home_url( '/' ) ) . '">',
				'</a>'
			);
		}
		else {
			$response['message'] = $this->frontend_error_messages_display() . '<br>';
			$response['message'] .= sprintf(
				__( '%1$sThe demo import has finished, but there were some import errors.%2$sMore details about the errors can be found in this %3$s%5$slog file%6$s%4$s%7$s', 'everse-starter-sites' ),
				'<div class="notice  notice-error is-dismissible"><p>',
				'<br>',
				'<strong>',
				'</strong>',
				'<a href="' . ESS_Helpers::get_log_url( $this->log_file_path ) .'" target="_blank">',
				'</a>',
				'</p></div>'
			);
		}

		wp_send_json( $response );
	}

	/**
	 * Get content importer data, so we can continue the import with this new AJAX request.
	 *
	 * @return boolean
	 */
	private function use_existing_importer_data() {
		if ( $data = get_transient( 'ESS_importer_data' ) ) {
			$this->frontend_error_messages = empty( $data['frontend_error_messages'] ) ? array() : $data['frontend_error_messages'];
			$this->log_file_path           = empty( $data['log_file_path'] ) ? '' : $data['log_file_path'];
			$this->selected_index          = empty( $data['selected_index'] ) ? 0 : $data['selected_index'];
			$this->selected_import_files   = empty( $data['selected_import_files'] ) ? array() : $data['selected_import_files'];
			$this->import_files            = empty( $data['import_files'] ) ? array() : $data['import_files'];
			$this->before_content_executed  = empty( $data['before_content_executed'] ) ? false : $data['before_content_executed'];
			$this->importer->set_importer_data( $data );

			return true;
		}

		return false;
	}


	/**
	 * Get the current state of selected data.
	 *
	 * @return array
	 */
	public function get_current_importer_data() {
		return array(
			'frontend_error_messages' => $this->frontend_error_messages,
			'log_file_path'           => $this->log_file_path,
			'selected_index'          => $this->selected_index,
			'selected_import_files'   => $this->selected_import_files,
			'import_files'            => $this->import_files,
			'before_content_executed'  => $this->before_content_executed,
		);
	}

	public function append_to_frontend_error_messages( $text ) {
		$lines = array();

		if ( ! empty( $text ) ) {
			$text = str_replace( '<br>', PHP_EOL, $text );
			$lines = explode( PHP_EOL, $text );
		}

		foreach ( $lines as $line ) {
			if ( ! empty( $line ) && ! in_array( $line , $this->frontend_error_messages ) ) {
				$this->frontend_error_messages[] = $line;
			}
		}
	}

	public function frontend_error_messages_display() {
		$output = '';

		if ( ! empty( $this->frontend_error_messages ) ) {
			foreach ( $this->frontend_error_messages as $line ) {
				$output .= esc_html( $line );
				$output .= '<br>';
			}
		}

		return $output;
	}

	public function get_log_file_path() {
		return $this->log_file_path;
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'everse-starter-sites', false, plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/languages' );
	}

	/**
	* Change plugin icon
	*
	* @since 1.0.0
	*/
	public function plugin_custom_icon() {
		return ESS_DIR_PATH . 'assets/img/theme-icon.jpg';
	}

	/**
	* Display admin notice.
	*/
	public function admin_notice() {
		$theme = get_option('stylesheet');
		global $pagenow;

		if ( 'themes.php' == $pagenow && ( isset( $_GET['page'] ) && 'everse-starter-sites' == $_GET['page'] ) ) {
			
			if ( 'everse-pro' === $theme || 'everse' === $theme ) {
				return;
			}

			if ( get_user_meta( get_current_user_id(), 'ess_dismissed_notice', true ) ) {
				return;
			}			

			?>
			<div class="everse-starter-sites-notice notice notice-info is-dismissible">
				<p><?php echo wp_kses_post( 'This plugin is created only for the <strong>Everse theme</strong>. You should have the Everse theme installed and activated on your website before importing.' ); ?></p>
				<p>
					<a class="button button-primary" href="https://deothemes.com/freebies/everse-free-elementor-wordpress-theme/" target="_blank">
						<?php echo esc_html__( 'Download Now', 'everse-starter-sites' ); ?>
					</a>				
					<span style="margin: 0.5em 0 0 0.5em; clear: both;">
						<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'ess-dismiss', 'dismiss_admin_notices' ), 'ess-dismiss-' . get_current_user_id() ) ); ?>" class="dismiss-notice" target="_parent">
							<?php echo esc_html__( 'Dismiss', 'everse-starter-sites' ); ?>
						</a>
					</span>
				</p>
			</div>
			<?php
		}
	}	


	/**
	* Register dismissal of admin notices.
	*
	* Acts on the dismiss link in the admin nag messages.
	* If clicked, the admin notice disappears and will no longer be visible to this user.
	*
	*/
	public function admin_notice_dismiss() {
		if ( isset( $_GET['ess-dismiss'] ) && check_admin_referer( 'ess-dismiss-' . get_current_user_id() ) ) {
			update_user_meta( get_current_user_id(), 'ess_dismissed_notice', 1 );
		}
	}


	public function admin_menu() {

		$plugin_page = apply_filters( 'everse-starter-sites/plugin_page_setup', array(
				'parent_slug' => 'themes.php',
				'page_title'  => esc_html__( 'Everse Starter Sites' , 'everse-starter-sites' ),
				'menu_title'  => esc_html__( 'Everse Starter Sites' , 'everse-starter-sites' ),
				'capability'  => 'import',
				'menu_slug'   => 'everse-starter-sites',
			)
		);

		$this->plugin_page = add_submenu_page( $plugin_page['parent_slug'], $plugin_page['page_title'], $plugin_page['menu_title'], $plugin_page['capability'], $plugin_page['menu_slug'], [ $this, 'display_import_output' ] );
	}

	public function display_import_output() {
		include_once ESS_DIR_PATH . 'templates/admin/importer.php';
	}

	private function include_required_files() {

		require ESS_DIR_PATH . 'inc/class-ess-helpers.php';
		require ESS_DIR_PATH . 'inc/register-actions.php';
		require ESS_DIR_PATH . 'inc/class-ess-plugins.php';
		require ESS_DIR_PATH . 'inc/class-ess-importer.php';
		require ESS_DIR_PATH . 'inc/class-ess-widget-importer.php';
		require ESS_DIR_PATH . 'inc/class-ess-customizer-importer.php';
		require ESS_DIR_PATH . 'inc/class-ess-logger.php';
	}
}

Everse_Starter_Sites::getInstance();
