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
	 * @var    Nelio_AB_Testing_Settings|null
	 */
	private static $instance;

	/**
	 * Initialize the class, set its properties, and add the proper hooks.
	 *
	 * @since  5.0.0
	 */
	protected function __construct() {

		parent::__construct( 'nelio-ab-testing' );
	}

	// @Overrides
	public function init() { // @codingStandardsIgnoreLine

		parent::init();
		add_filter( 'nab_is_setting_disabled', array( $this, 'maybe_disable_setting' ), 10, 3 );
	}

	// @Implements
	public function set_tabs() { // @codingStandardsIgnoreLine

		$base_dir = nelioab()->plugin_path . '/includes';

		/** @var list<TSettings_Tab> */
		$tabs = array(

			array(
				'name'   => 'nab-basic',
				'label'  => fn() => _x( 'A/B Testing', 'text (settings tab)', 'nelio-ab-testing' ),
				'fields' => include $base_dir . '/data/basic-tab.php',
			),

		);

		/**
		 * Filters the tabs in the settings screen.
		 *
		 * @param list<TSettings_Tab> $tabs The tabs in the settings screen.
		 *
		 * @since 6.4.0
		 */
		$tabs = apply_filters( 'nab_tab_settings', $tabs );

		$this->do_set_tabs( $tabs );
	}

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Settings the single instance of this class.
	 *
	 * @since  5.0.0
	 */
	public static function instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Callback to disable a setting depending on the current plan and the setting requirements.
	 *
	 * @param boolean             $disabled whether this setting is disabled or not.
	 * @param string              $name     name of the parameter.
	 * @param array<string,mixed> $config   extra config options.
	 *
	 * @return boolean whether the given field should be disabled or not.
	 *
	 * @since  5.0.0
	 */
	public function maybe_disable_setting( $disabled, $name, $config ) {

		if ( empty( $config ) ) {
			return $disabled;
		}

		if ( ! isset( $config['required-plan'] ) || ! is_string( $config['required-plan'] ) ) {
			return $disabled;
		}

		$required_plan = $config['required-plan'];
		return ! nab_is_subscribed_to( $required_plan );
	}
}
