<?php

/*
Plugin Name: One Click Demo Import
Plugin URI: https://wordpress.org/plugins/one-click-demo-import/
Description: Import your content, widgets and theme settings with one click. Theme authors! Enable simple demo import for your theme demo data.
Version: 2.5.0
Author: ProteusThemes
Author URI: http://www.proteusthemes.com
License: GPL3
License URI: http://www.gnu.org/licenses/gpl.html
Text Domain: pt-ocdi
*/

// Block direct access to the main plugin file.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Main plugin class with initialization tasks.
 */
class OCDI_Plugin {
	/**
	 * Constructor for this class.
	 */
	public function __construct() {
        // Set plugin constants.
        $this->set_plugin_constants();

        // Composer autoloader.
        require_once PT_OCDI_PATH . 'vendor/autoload.php';

        // Instantiate the main plugin class *Singleton*.
        $pt_one_click_demo_import = OCDI\OneClickDemoImport::get_instance();
        load_theme_textdomain('pt-ocdi', PT_OCDI_PATH . '/languages');
        add_filter( 'pt-ocdi/regenerate_thumbnails_in_content_import', '__return_false' );
        add_filter( 'pt-ocdi/disable_pt_branding', '__return_true' );

        // Register WP CLI commands
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            WP_CLI::add_command( 'ocdi list', array( 'OCDI\WPCLICommands', 'list_predefined' ) );
            WP_CLI::add_command( 'ocdi import', array( 'OCDI\WPCLICommands', 'import' ) );
        }

	}


	/**
	 * Display an admin error notice when PHP is older the version 5.3.2.
	 * Hook it to the 'admin_notices' action.
	 */
	public function old_php_admin_error_notice() {
		$message = sprintf( esc_html__( 'The %2$sOne Click Demo Import%3$s plugin requires %2$sPHP 5.3.2+%3$s to run properly. Please contact your hosting company and ask them to update the PHP version of your site to at least PHP 5.3.2.%4$s Your current version of PHP: %2$s%1$s%3$s', 'pt-ocdi' ), phpversion(), '<strong>', '</strong>', '<br>' );

		printf( '<div class="notice notice-error"><p>%1$s</p></div>', wp_kses_post( $message ) );
	}


	/**
	 * Set plugin constants.
	 *
	 * Path/URL to root of this plugin, with trailing slash and plugin version.
	 */
	private function set_plugin_constants() {
		// Path/URL to root of this plugin, with trailing slash.
		if ( ! defined( 'PT_OCDI_PATH' ) ) {
            define( 'PT_OCDI_PATH', FRAMEWORK_PATH . '/importer/' );
		}
		if ( ! defined( 'PT_OCDI_URL' ) ) {
            define( 'PT_OCDI_URL', FRAMEWORK_URI . '/importer/' );
		}

		// Action hook to set the plugin version constant.
		add_action( 'admin_init', array( $this, 'set_plugin_version_constant' ) );
	}


	/**
	 * Set plugin version constant -> PT_OCDI_VERSION.
	 */
	public function set_plugin_version_constant() {
		if ( ! defined( 'PT_OCDI_VERSION' ) ) {
			$plugin_data = get_plugin_data( __FILE__ );
			define( 'PT_OCDI_VERSION', $plugin_data['Version'] );
		}
	}
}

// Instantiate the plugin class.
$ocdi_plugin = new OCDI_Plugin();
