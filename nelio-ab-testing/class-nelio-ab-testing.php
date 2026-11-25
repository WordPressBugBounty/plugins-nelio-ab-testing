<?php

defined( 'ABSPATH' ) || exit;

/**
 * Main class.
 */
class Nelio_AB_Testing {

	/** @var non-empty-string */
	public $plugin_file;

	/** @var string */
	public $plugin_path;

	/** @var string */
	public $plugin_url;

	/** @var string */
	public $plugin_version;

	/** @var non-falsy-string */
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
	private function __construct() {
	}

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
			new Nelio_AB_Testing_Cloud(),
			new Nelio_AB_Testing_Css_Selector_Finder(),
			new Nelio_AB_Testing_Experiment_Post_Type_Register(),
			new Nelio_AB_Testing\Recommendation_Engine\Experiment_Repository(),
			new Nelio_AB_Testing_Experiment_Scheduler(),
			new Nelio_AB_Testing_Heatmap_Renderer(),
			new Nelio_AB_Testing_Logger(),
			new Nelio_AB_Testing_Mailer(),
			new Nelio_AB_Testing_Overview_Widget(),
			new Nelio_AB_Testing_Public(),
			new Nelio_AB_Testing_Public_Result(),
			new Nelio_AB_Testing_Quick_Experiment_Menu(),
			new Nelio_AB_Testing_Quota_Boost_Notice(),
			new Nelio_AB_Testing_Quota_Checker(),
			new Nelio_AB_Testing_Tracking(),

			new Nelio_AB_Testing_Abilities(),
			new Nelio_AB_Testing_AI_REST_Controller(),
			new Nelio_AB_Testing_Experiment_REST_Controller(),
			new Nelio_AB_Testing_Generic_REST_Controller(),
			new Nelio_AB_Testing_Menu_REST_Controller(),
			new Nelio_AB_Testing_Plugin_REST_Controller(),
			new Nelio_AB_Testing_Post_REST_Controller(),
			new Nelio_AB_Testing_Proxy_REST_Controller(),
			new Nelio_AB_Testing_Recommendation_Engine_REST_Controller(),
			new Nelio_AB_Testing_Reusable_Entities_REST_Controller(),
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

	/**
	 * Retrieves the registered experiment types.
	 *
	 * @return array<string, TExperiment_Type_Definition> Experiment types keyed by name.
	 */
	public function get_experiment_types() {
		/**
		 * Filters the registered experiment types.
		 *
		 * @var array<string, TExperiment_Type_Definition> $experiment_types Experiment types keyed by name.
		 *
		 * @since 9.0.0
		 */
		$experiment_types         = apply_filters( 'nab_experiment_types', array() );
		$experiment_types_with_ai = array();
		foreach ( $experiment_types as $type_name => $experiment_type ) {
			$type_name = $experiment_type['name'];
			/**
			 * Filters the AI definition of an experiment type.
			 *
			 * @param TExperiment_Type_AI|null   $ai              AI definition, if available.
			 * @param TExperiment_Type_Definition $experiment_type Experiment type definition.
			 *
			 * @since 9.0.0
			 */
			$ai = apply_filters( "nab_{$type_name}_ai_definition", null, $experiment_type );
			unset( $experiment_type['ai'] );
			if ( null !== $ai ) {
				$experiment_type['ai'] = $ai;
			}
			$experiment_types_with_ai[ $type_name ] = $experiment_type;
		}
		return $experiment_types_with_ai;
	}

	/**
	 * Retrieves an experiment type definition by name.
	 *
	 * @param string $experiment_type Experiment type name.
	 *
	 * @return TExperiment_Type_Definition|null The experiment type definition, if registered.
	 */
	public function get_experiment_type( $experiment_type ) {
		$experiment_types = $this->get_experiment_types();
		return $experiment_types[ $experiment_type ] ?? null;
	}

	/**
	 * Checks whether a test type can be used by AI abilities.
	 *
	 * An AI-enabled test type always includes an `ai` definition, whose schema
	 * is mandatory according to the PHP type definition.
	 *
	 * @param string $experiment_type Experiment type name.
	 *
	 * @return bool Whether the test type supports AI abilities.
	 *
	 * @since 9.0.0
	 */
	public function does_test_type_support_ai( $experiment_type ) {
		$definition = $this->get_experiment_type( $experiment_type );
		return is_array( $definition ) && isset( $definition['ai'] );
	}

	/**
	 * Retrieves the registered conversion action type definitions.
	 *
	 * @return array<string,TConversion_Action_Type_Definition> Conversion action type definitions keyed by name.
	 */
	public function get_conversion_action_types() {
		/**
		 * Filters the registered conversion action type definitions.
		 *
		 * @var array<string,TConversion_Action_Type_Definition> $conversion_action_types Conversion action type definitions keyed by name.
		 *
		 * @since 9.0.0
		 */
		$conversion_action_types         = apply_filters( 'nab_conversion_action_types', array() );
		$conversion_action_types_with_ai = array();
		foreach ( $conversion_action_types as $type_name => $conversion_action_type ) {
			$type_name = $conversion_action_type['name'];
			/**
			 * Filters the AI definition of a conversion action type.
			 *
			 * @param TConversion_Action_Type_AI|null $ai                    AI definition, if available.
			 * @param TConversion_Action_Type_Definition $conversion_action_type Conversion action type definition.
			 *
			 * @since 9.0.0
			 */
			$ai = apply_filters( "nab_{$type_name}_ai_definition", null, $conversion_action_type );
			unset( $conversion_action_type['ai'] );
			if ( null !== $ai ) {
				$conversion_action_type['ai'] = $ai;
			}
			$conversion_action_types_with_ai[ $type_name ] = $conversion_action_type;
		}
		return $conversion_action_types_with_ai;
	}

	/**
	 * Retrieves a conversion action type definition by name.
	 *
	 * @param string $conversion_action_type Conversion action type name.
	 *
	 * @return TConversion_Action_Type_Definition|null The conversion action type definition, if registered.
	 */
	public function get_conversion_action_type( $conversion_action_type ) {
		$conversion_action_types = $this->get_conversion_action_types();
		return $conversion_action_types[ $conversion_action_type ] ?? null;
	}

	/**
	 * Retrieves the registered segmentation rule type definitions.
	 *
	 * @return array<string,TSegmentation_Rule_Type_Definition> Segmentation rule type definitions keyed by name.
	 */
	public function get_segmentation_rule_types() {
		/**
		 * Filters the registered segmentation rule type definitions.
		 *
		 * @var array<string,TSegmentation_Rule_Type_Definition> $segmentation_rule_types Segmentation rule type definitions keyed by name.
		 *
		 * @since 9.0.0
		 */
		$segmentation_rule_types = apply_filters( 'nab_segmentation_rule_types', array() );
		$segmentation_rule_types = array_map(
			function ( $segmentation_rule_type ) {
				$type_name = $segmentation_rule_type['name'];
				/**
				 * Filters the AI definition of a segmentation rule type.
				 *
				 * @param TSegmentation_Rule_Type_AI|null   $ai                    AI definition, if available.
				 * @param TSegmentation_Rule_Type_Definition $segmentation_rule_type Segmentation rule type definition.
				 *
				 * @since 9.0.0
				 */
				$ai = apply_filters( "nab_{$type_name}_ai_definition", null, $segmentation_rule_type );
				unset( $segmentation_rule_type['ai'] );
				if ( null !== $ai ) {
					$segmentation_rule_type['ai'] = $ai;
				}
				return $segmentation_rule_type;
			},
			$segmentation_rule_types
		);
		return $segmentation_rule_types;
	}

	/**
	 * Retrieves a segmentation rule type definition by name.
	 *
	 * @param string $segmentation_rule_type Segmentation rule type name.
	 *
	 * @return TSegmentation_Rule_Type_Definition|null The segmentation rule type definition, if registered.
	 */
	public function get_segmentation_rule_type( $segmentation_rule_type ) {
		$segmentation_rule_types = $this->get_segmentation_rule_types();
		return $segmentation_rule_types[ $segmentation_rule_type ] ?? null;
	}
}
