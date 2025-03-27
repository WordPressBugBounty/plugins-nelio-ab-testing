<?php
/**
 * The plugin bootstrap file
 *
 * Plugin Name:       Nelio AB Testing
 * Plugin URI:        https://neliosoftware.com/testing/
 * Description:       Optimize your site based on data, not opinions. With this plugin, you will be able to perform AB testing (and more) on your WordPress site.
 * Version:           7.4.6
 *
 * Author:            Nelio Software
 * Author URI:        https://neliosoftware.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * Requires at least: 6.3
 * Requires PHP:      7.4
 *
 * Text Domain:       nelio-ab-testing
 *
 * @since   5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

define( 'NELIO_AB_TESTING', true );

function nelioab() {
	return Nelio_AB_Testing::instance();
}//end nelioab()

/**
 * Main class.
 */
class Nelio_AB_Testing { // phpcs:ignore


	private static $instance = null;

	public $plugin_file;
	public $plugin_name;
	public $plugin_name_sanitized;
	public $plugin_path;
	public $plugin_slug;
	public $plugin_url;
	public $plugin_version;
	public $rest_namespace;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->load_dependencies();
			self::$instance->install();
			self::$instance->init();
		}//end if

		return self::$instance;
	}//end instance()

	private function load_dependencies() {
		$this->plugin_path    = untrailingslashit( plugin_dir_path( __FILE__ ) );
		$this->plugin_url     = untrailingslashit( plugin_dir_url( __FILE__ ) );
		$this->plugin_file    = 'nelio-ab-testing/nelio-ab-testing.php';
		$this->rest_namespace = 'nab/v1';

		// phpcs:ignore
		require_once $this->plugin_path . '/vendor/autoload.php';
		// phpcs:ignore
		require_once $this->plugin_path . '/includes/utils/functions/index.php';
		// phpcs:ignore
		include_once $this->plugin_path . '/includes/hooks/index.php';
	}//end load_dependencies()

	private function install() {
		add_action( 'init', array( $this, 'load_i18n_strings' ), 1 );
		add_action( 'plugins_loaded', array( $this, 'plugin_data_init' ), 1 );

		$aux = Nelio_AB_Testing_Install::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Capability_Manager::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Account_REST_Controller::instance();
		$aux->init();

		if ( is_admin() ) {
			$aux = Nelio_AB_Testing_Overview_Widget::instance();
			$aux->init();
		}//end if

		if ( is_admin() && ! wp_doing_ajax() ) {
			$aux = Nelio_AB_Testing_Admin::instance();
			$aux->init();
		}//end if
	}//end install()

	private function init() {
		if ( ! $this->is_ready() ) {
			return;
		}//end if

		add_action( 'admin_init', array( $this, 'add_privacy_policy' ) );

		$this->init_common_helpers();
		$this->init_rest_controllers();

		if ( ! is_admin() ) {
			$aux = Nelio_AB_Testing_Public::instance();
			$aux->init();

			$aux = Nelio_AB_Testing_Public_Result::instance();
			$aux->init();
		}//end if

		$aux = Nelio_AB_Testing_Tracking::instance();
		$aux->init();
	}//end init()

	public function is_ready() {
		return ! empty( nab_get_site_id() );
	}//end is_ready()

	private function init_common_helpers() {
		$aux = Nelio_AB_Testing_Experiment_Post_Type_Register::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Alternative_Content_Manager::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Settings::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Experiment_Scheduler::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Logger::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Quota_Checker::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Mailer::instance();
		$aux->init();
	}//end init_common_helpers()

	private function init_rest_controllers() {
		$aux = Nelio_AB_Testing_Cloud_Proxy_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Experiment_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Generic_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Menu_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Plugin_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Post_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Template_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Theme_REST_Controller::instance();
		$aux->init();
	}//end init_rest_controllers()

	public function load_i18n_strings() {
		load_plugin_textdomain( 'nelio-ab-testing', false, basename( $this->plugin_path ) . '/languages/' );
	}//end load_i18n_strings()

	public function plugin_data_init() {
		$data = get_file_data( __FILE__, array( 'Plugin Name', 'Version' ), 'plugin' );

		$this->plugin_name           = $data[0];
		$this->plugin_version        = $data[1];
		$this->plugin_slug           = plugin_basename( __FILE__, '.php' );
		$this->plugin_name_sanitized = basename( __FILE__, '.php' );
	}//end plugin_data_init()

	public function add_privacy_policy() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}//end if

		ob_start();
		// phpcs:ignore
		include nelioab()->plugin_path . '/includes/data/privacy-policy.php';
		$content = ob_get_contents();
		ob_end_clean();

		/**
		 * Filters the content of Nelio A/B Testing’s privacy policy.
		 *
		 * The suggested text is a proposal that should be included in the site’s
		 * privacy policy. It contains information about how the plugin works, what
		 * information is stored in Nelio’s clouds, which cookies are used, etc.
		 *
		 * The text will be shown on the Privacy Policy Guide screen.
		 *
		 * @param string $content the content of Nelio A/B Testing’s privacy policy.
		 *
		 * @since 5.0.0
		 */
		$content = wp_kses_post( apply_filters( 'nab_privacy_policy_content', wpautop( $content ) ) );
		wp_add_privacy_policy_content( 'Nelio A/B Testing', $content );
	}//end add_privacy_policy()
}//end class

// Start plugin.
nelioab();
