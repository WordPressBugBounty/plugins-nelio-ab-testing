<?php
/**
 * This file has the Settings class, which defines and registers Nelio A/B Testing's Settings.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Settings class, responsible of defining, registering, and providing access to all Nelio A/B Testing's settings.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes
 * @since      5.0.0
 */
final class Nelio_AB_Testing_Settings extends Nelio_AB_Testing_Abstract_Settings {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @var    Nelio_AB_Testing_Settings
	 */
	private static $instance;

	/**
	 * Initialize the class, set its properties, and add the proper hooks.
	 *
	 * @since  5.0.0
	 */
	protected function __construct() {

		parent::__construct( 'nelio-ab-testing' );
	}//end __construct()

	/** . @Overrides */
	public function init() { // @codingStandardsIgnoreLine

		parent::init();
		add_filter( 'nab_is_setting_disabled', array( $this, 'maybe_disable_setting' ), 10, 3 );
	}//end init()

	/** . @Implements */
	public function set_tabs() { // @codingStandardsIgnoreLine

		$base_dir = nelioab()->plugin_path . '/includes';

		// Add as many tabs as you want. If you have one tab only, no tabs will be shown at all.
		$tabs = array(

			array(
				'name'   => 'nab-basic',
				'label'  => fn() => _x( 'A/B Testing', 'text (settings tab)', 'nelio-ab-testing' ),
				'fields' => include $base_dir . '/data/basic-tab.php', // phpcs:ignore
			),

		);

		/**
		 * Filters the tabs in the settings screen.
		 *
		 * @param array $tabs The tabs in the settings screen.
		 *
		 * @since 6.4.0
		 */
		$tabs = apply_filters( 'nab_tab_settings', $tabs );

		$this->do_set_tabs( $tabs );
	}//end set_tabs()

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Settings the single instance of this class.
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
	 * Callback to disable a setting depending on the current plan and the setting requirements.
	 *
	 * @param boolean $disabled whether this setting is disabled or not.
	 * @param string  $name     name of the parameter.
	 * @param array   $config   extra config options.
	 *
	 * @return boolean whether the given field should be disabled or not.
	 *
	 * @since  5.0.0
	 */
	public function maybe_disable_setting( $disabled, $name, $config ) {

		if ( empty( $config ) ) {
			return $disabled;
		}//end if

		if ( ! isset( $config['required-plan'] ) ) {
			return $disabled;
		}//end if

		$required_plan = $config['required-plan'];
		return ! nab_is_subscribed_to( $required_plan );
	}//end maybe_disable_setting()
}//end class
