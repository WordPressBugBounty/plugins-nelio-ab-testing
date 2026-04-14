<?php

defined( 'ABSPATH' ) || exit;

/**
 * Main class.
 */
class Nelio_AB_Testing {

	/** @var string */
	public $plugin_file;

	/** @var string */
	public $plugin_path;

	/** @var string */
	public $plugin_url;

	/** @var string */
	public $plugin_version;

	/** @var string */
	public $rest_namespace;

	/** @var Nelio_AB_Testing|null */
	private static $instance;

	/** @var Nelio_AB_Testing_Experiment_Manager|null */
	private $manager = null;

	/** @var Nelio_AB_Testing_Runtime|null */
	private $runtime = null;

	/**
	 * Private constructor.
	 *
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_AB_Testing
	 */
	public static function instance() {
		// @codeCoverageIgnoreStart
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->load_dependencies();
			self::$instance->init();
		}
		// @codeCoverageIgnoreEnd

		return self::$instance;
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
	 * Returns runtime, if available.
	 *
	 * @return Nelio_AB_Testing_Runtime
	 */
	public function runtime() {
		if ( is_null( $this->runtime ) ) {
			$this->runtime = new Nelio_AB_Testing_Runtime();
		}
		return $this->runtime;
	}

	/**
	 * Gets experiment manager.
	 *
	 * @return Nelio_AB_Testing_Experiment_Manager
	 */
	public function manager() {
		if ( is_null( $this->manager ) ) {
			$this->manager = new Nelio_AB_Testing_Experiment_Manager();
			$this->manager->init();

		}
		return $this->manager;
	}

	/**
	 * Loads plugin’s basic dependencies.
	 *
	 * This includes the autoloader, helper functions, and all hooks.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		$data                 = get_file_data( untrailingslashit( __DIR__ ) . '/nelio-ab-testing.php', array( 'Version' ), 'plugin' );
		$this->plugin_version = $data[0];
		$this->plugin_path    = untrailingslashit( plugin_dir_path( __FILE__ ) );
		$this->plugin_url     = untrailingslashit( plugin_dir_url( __FILE__ ) );
		$this->plugin_file    = 'nelio-ab-testing/nelio-ab-testing.php';
		$this->rest_namespace = 'nab/v1';

		require_once $this->plugin_path . '/vendor/autoload.php';
		require_once $this->plugin_path . '/includes/utils/functions/index.php';
		include_once $this->plugin_path . '/includes/hooks/index.php';
	}

	/**
	 * Initializes main classes and hooks into WordPress.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'admin_init', array( $this, 'add_privacy_policy' ) );

		$essentials = array(
			new Nelio_AB_Testing_Install(),
			new Nelio_AB_Testing_Capability_Manager(),
			new Nelio_AB_Testing_Admin(),
			new Nelio_AB_Testing_Account_REST_Controller(),
			Nelio_AB_Testing_Settings::instance(),
		);
		array_walk( $essentials, fn( $instance ) => $instance->init() );

		if ( ! $this->is_ready() ) {
			return;
		}

		$extra_instances = array(
			new Nelio_AB_Testing_Alternative_Content_Manager(),
			new Nelio_AB_Testing_Alternative_Preview(),
			new Nelio_AB_Testing_Css_Selector_Finder(),
			new Nelio_AB_Testing_Experiment_Post_Type_Register(),
			new Nelio_AB_Testing_Experiment_Scheduler(),
			new Nelio_AB_Testing_Heatmap_Renderer(),
			new Nelio_AB_Testing_Logger(),
			new Nelio_AB_Testing_Mailer(),
			new Nelio_AB_Testing_Overview_Widget(),
			new Nelio_AB_Testing_Public(),
			new Nelio_AB_Testing_Public_Result(),
			new Nelio_AB_Testing_Quick_Experiment_Menu(),
			new Nelio_AB_Testing_Quota_Checker(),
			new Nelio_AB_Testing_Tracking(),

			new Nelio_AB_Testing_AI_REST_Controller(),
			new Nelio_AB_Testing_Cloud_Proxy_REST_Controller(),
			new Nelio_AB_Testing_Experiment_REST_Controller(),
			new Nelio_AB_Testing_Generic_REST_Controller(),
			new Nelio_AB_Testing_Menu_REST_Controller(),
			new Nelio_AB_Testing_Plugin_REST_Controller(),
			new Nelio_AB_Testing_Post_REST_Controller(),
			new Nelio_AB_Testing_Template_REST_Controller(),
			new Nelio_AB_Testing_Theme_REST_Controller(),
		);
		array_walk( $extra_instances, fn( $instance ) => $instance->init() );
	}

	/**
	 * Callback to extend privacy policy info with Nelio A/B Testing’s details.
	 *
	 * @return void
	 */
	public function add_privacy_policy() {
		if ( ! $this->is_ready() ) {
			return;
		}

		ob_start();
		include nelioab()->plugin_path . '/includes/data/privacy-policy.php';
		$content = ob_get_contents();
		ob_end_clean();
		assert( is_string( $content ) );

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
