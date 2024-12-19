<?php
/**
 * The file that includes installation-related functions and actions.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class configures WordPress and installs some capabilities.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @since      5.0.0
 */
class Nelio_AB_Testing_Install {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @var    Nelio_AB_Testing_Install
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Install the single instance of this class.
	 *
	 * @since  5.0.0
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	/**
	 * Hook in tabs.
	 *
	 * @since  5.0.0
	 */
	public function init() {

		$main_file = nelioab()->plugin_path . '/nelio-ab-testing.php';
		register_activation_hook( $main_file, array( $this, 'install' ) );
		register_deactivation_hook( $main_file, array( $this, 'uninstall' ) );

		add_action( 'admin_init', array( $this, 'check_version' ), 5 );
	}//end init()

	/**
	 * Checks the currently-installed version and, if it's old, installs the new one.
	 *
	 * @since  5.0.0
	 */
	public function check_version() {

		$last_version = get_option( 'nab_version' );
		$this_version = nelioab()->plugin_version;
		if ( ! defined( 'IFRAME_REQUEST' ) && ( $last_version !== $this_version ) ) {

			// Update version.
			update_option( 'nab_version', $this_version );

			/**
			 * Fires once the plugin has been updated.
			 *
			 * @param string $this_version current version of the plugin.
			 * @param string $last_version previous installed version of the plugin.
			 *
			 * @since 5.0.0
			 */
			do_action( 'nab_updated', $this_version, $last_version );

		}//end if
	}//end check_version()

	/**
	 * Install Nelio A/B Testing.
	 *
	 * This function registers new post types, adds a few capabilities, and more.
	 *
	 * @since  5.0.0
	 */
	public function install() {

		if ( ! defined( 'NELIO_AB_TESTING_INSTALLING' ) ) {
			define( 'NELIO_AB_TESTING_INSTALLING', true );
		}//end if

		/**
		 * Fires once the plugin has been installed.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_installed' );
	}//end install()

	/**
	 * Deactivate and uninstall Nelio A/B Testing.
	 *
	 * This function is run when the plugin is deactivated.
	 *
	 * @since  6.0.1
	 */
	public function uninstall() {
		if ( ! defined( 'NELIO_AB_TESTING_UNINSTALLING' ) ) {
			define( 'NELIO_AB_TESTING_UNINSTALLING', true );
		}//end if

		/**
		 * Fires once the plugin has been uninstalled.
		 *
		 * @since 6.0.1
		 */
		do_action( 'nab_uninstalled' );
	}//end uninstall()
}//end class
