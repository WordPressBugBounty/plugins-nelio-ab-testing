<?php

defined( 'ABSPATH' ) || exit;

/**
 * Main class.
 */
class Nelio_AB_Testing {

	/**
	 * This instance.
	 *
	 * @var Nelio_AB_Testing|null
	 */
	private static $instance;

	/**
	 * Plugin’s main file.
	 *
	 * @var string
	 */
	public $plugin_file;

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	public $plugin_name;

	/**
	 * Plugin path.
	 *
	 * @var string
	 */
	public $plugin_path;

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	public $plugin_slug;

	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $plugin_version;

	/**
	 * Plugin’s REST namespace.
	 *
	 * @var string
	 */
	public $rest_namespace;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_AB_Testing
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->load_dependencies();
			self::$instance->install();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Loads plugin’s basic dependencies.
	 *
	 * This includes the autoloader, helper functions, and all hooks.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		$this->plugin_path    = untrailingslashit( plugin_dir_path( __FILE__ ) );
		$this->plugin_url     = untrailingslashit( plugin_dir_url( __FILE__ ) );
		$this->plugin_file    = 'nelio-ab-testing/nelio-ab-testing.php';
		$this->rest_namespace = 'nab/v1';

		require_once $this->plugin_path . '/vendor/autoload.php';
		require_once $this->plugin_path . '/includes/utils/functions/index.php';
		include_once $this->plugin_path . '/includes/hooks/index.php';

		Nelio_AB_Testing_Settings::instance()->init();
	}

	/**
	 * Initializes main classes, regardless of plugin’s status.
	 *
	 * @return void
	 */
	private function install() {
		add_action( 'plugins_loaded', array( $this, 'plugin_data_init' ), 1 );

		Nelio_AB_Testing_Install::instance()->init();
		Nelio_AB_Testing_Capability_Manager::instance()->init();
		Nelio_AB_Testing_Account_REST_Controller::instance()->init();

		if ( is_admin() ) {
			Nelio_AB_Testing_Overview_Widget::instance()->init();
			Nelio_AB_Testing_Admin::instance()->init();
		}
	}

	/**
	 * Loads remaining dependencies, if plugin is ready.
	 *
	 * @return void
	 */
	private function init() {
		if ( ! $this->is_ready() ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'add_privacy_policy' ) );

		$this->init_common_helpers();
		$this->init_rest_controllers();
	}

	/**
	 * Returns whether the plugin is properly configured or not (i.e. it has a site id).
	 *
	 * @return boolean
	 */
	public function is_ready() {
		return ! empty( nab_get_site_id() );
	}

	/**
	 * Inits all common helpers.
	 *
	 * @return void
	 */
	private function init_common_helpers() {
		Nelio_AB_Testing_Alternative_Content_Manager::instance()->init();
		Nelio_AB_Testing_Experiment_Post_Type_Register::instance()->init();
		Nelio_AB_Testing_Experiment_Scheduler::instance()->init();
		Nelio_AB_Testing_Logger::instance()->init();
		Nelio_AB_Testing_Mailer::instance()->init();
		Nelio_AB_Testing_Public::instance()->init();
		Nelio_AB_Testing_Public_Result::instance()->init();
		Nelio_AB_Testing_Quota_Checker::instance()->init();
		Nelio_AB_Testing_Tracking::instance()->init();
	}

	/**
	 * Inits REST controllers.
	 *
	 * @return void
	 */
	private function init_rest_controllers() {
		Nelio_AB_Testing_AI_REST_Controller::instance()->init();
		Nelio_AB_Testing_Cloud_Proxy_REST_Controller::instance()->init();
		Nelio_AB_Testing_Experiment_REST_Controller::instance()->init();
		Nelio_AB_Testing_Generic_REST_Controller::instance()->init();
		Nelio_AB_Testing_Menu_REST_Controller::instance()->init();
		Nelio_AB_Testing_Plugin_REST_Controller::instance()->init();
		Nelio_AB_Testing_Post_REST_Controller::instance()->init();
		Nelio_AB_Testing_Template_REST_Controller::instance()->init();
		Nelio_AB_Testing_Theme_REST_Controller::instance()->init();
	}

	/**
	 * Callback to initialize plugin data.
	 *
	 * @return void
	 */
	public function plugin_data_init() {
		$data = get_file_data( untrailingslashit( __DIR__ ) . '/nelio-ab-testing.php', array( 'Plugin Name', 'Version' ), 'plugin' );

		$this->plugin_name    = $data[0];
		$this->plugin_version = $data[1];
		$this->plugin_slug    = plugin_basename( __FILE__ );
	}

	/**
	 * Callback to extend privacy policy info with Nelio A/B Testing’s details.
	 *
	 * @return void
	 */
	public function add_privacy_policy() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		ob_start();
		include nelioab()->plugin_path . '/includes/data/privacy-policy.php';
		$content = ob_get_contents();
		$content = is_string( $content ) ? $content : '';
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
	}
}
