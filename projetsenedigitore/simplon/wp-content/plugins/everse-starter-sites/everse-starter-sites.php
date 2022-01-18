<?php

/**
 * Plugin Name: Everse Starter Sites
 * Plugin URI: https://deothemes.com/wordpress-themes/everse-multi-purpose-elementor-wordpress-theme/
 * Description: Import free and premium starter sites for Everse theme demo content, widgets and theme settings with just one click.
 * Version: 1.1
 * Author: DeoThemes
 * Author URI: https://deothemes.com
 * License: GPLv3 or later
 * Text Domain: everse-starter-sites
 * Domain Path: /languages/
 *
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly.
}


if ( function_exists( 'everse_starter_sites_fs' ) ) {
    everse_starter_sites_fs()->set_basename( false, __FILE__ );
} else {
    
    if ( !function_exists( 'everse_starter_sites_fs' ) ) {
        // Create a helper function for easy SDK access.
        function everse_starter_sites_fs()
        {
            global  $everse_starter_sites_fs ;
            
            if ( !isset( $everse_starter_sites_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $everse_starter_sites_fs = fs_dynamic_init( array(
                    'id'                             => '6923',
                    'slug'                           => 'everse-starter-sites',
                    'premium_slug'                   => 'everse-starter-sites-pro',
                    'type'                           => 'plugin',
                    'public_key'                     => 'pk_5fff3b0ad5803aeb17f565eb6df33',
                    'is_premium'                     => false,
                    'premium_suffix'                 => 'Pro',
                    'bundle_id'                      => '6922',
                    'bundle_public_key'              => 'pk_e5182d74589ae449896b63b934820',
                    'bundle_license_auto_activation' => true,
                    'navigation'                     => 'tabs',
                    'has_addons'                     => false,
                    'has_paid_plans'                 => true,
                    'menu'                           => array(
                    'slug'    => 'everse-starter-sites',
                    'support' => false,
                    'parent'  => array(
                    'slug' => 'themes.php',
                ),
                ),
                    'is_live'                        => true,
                ) );
            }
            
            return $everse_starter_sites_fs;
        }
        
        // Init Freemius.
        everse_starter_sites_fs();
        // Signal that SDK was initiated.
        do_action( 'everse_starter_sites_fs_loaded' );
    }
    
    /**
     * ESS Plugin Class
     *
     * The main class that initiates and runs the plugin.
     *
     * @since 1.0.0
     */
    
    if ( !class_exists( 'Everse_Starter_Sites_Plugin' ) ) {
        class Everse_Starter_Sites_Plugin
        {
            public function __construct()
            {
                /**
                 * Display error message if PHP version is older than 5.6.
                 * Otherwise execute the main plugin class.
                 */
                
                if ( version_compare( phpversion(), '5.6', '<' ) ) {
                    add_action( 'admin_notices', array( $this, 'everse_old_php_admin_notice' ) );
                } else {
                    $this->everse_plugin_constants();
                    register_activation_hook( __FILE__, array( $this, 'activate_everse_starter_sites' ) );
                    register_deactivation_hook( __FILE__, array( $this, 'deactivate_everse_starter_sites' ) );
                    if ( !class_exists( 'Everse_Starter_Sites' ) ) {
                        require_once ESS_DIR_PATH . 'inc/class-ess-main.php';
                    }
                }
            
            }
            
            public function everse_old_php_admin_notice()
            {
                $message = sprintf(
                    esc_html__( 'The %2$sEverse Starter Sites%3$s plugin requires %2$sPHP 5.6+%3$s to run properly. Please contact your hosting company and ask them to update the PHP version of your site to at least PHP 5.6.%4$s Your current version of PHP: %2$s%1$s%3$s', 'everse-starter-sites' ),
                    phpversion(),
                    '<strong>',
                    '</strong>',
                    '<br>'
                );
                printf( '<div class="notice notice-error"><p>%1$s</p></div>', wp_kses_post( $message ) );
            }
            
            /**
             * Plugin activator.
             */
            public function activate_everse_starter_sites()
            {
            }
            
            /**
             * Plugin deactivator.
             */
            public function deactivate_everse_starter_sites()
            {
            }
            
            /**
             * Set plugin constants.
             */
            private function everse_plugin_constants()
            {
                // Path/URL to root of this plugin, with trailing slash.
                if ( !defined( 'ESS_DIR_PATH' ) ) {
                    define( 'ESS_DIR_PATH', plugin_dir_path( __FILE__ ) );
                }
                if ( !defined( 'ESS_DIR_URL' ) ) {
                    define( 'ESS_DIR_URL', plugin_dir_url( __FILE__ ) );
                }
                if ( !defined( 'ESS_VERSION' ) ) {
                    define( 'ESS_VERSION', '1.1' );
                }
            }
        
        }
        // Instantiate the plugin class.
        $Everse_Starter_Sites_Plugin = new Everse_Starter_Sites_Plugin();
    }

}
