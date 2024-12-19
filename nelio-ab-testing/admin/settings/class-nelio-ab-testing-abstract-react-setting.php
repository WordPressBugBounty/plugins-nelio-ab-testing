<?php
/**
 * This file defines a helper class to add react-based components in our settings screen.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

/**
 * Helper class to add react-based components.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.1.0
 */
abstract class Nelio_AB_Testing_Abstract_React_Setting extends Nelio_AB_Testing_Abstract_Setting {

	protected $value;
	protected $desc;
	protected $component;

	public function __construct( $name, $component ) {
		parent::__construct( $name );
		$this->component = $component;
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}//end __construct()

	public function set_value( $value ) {
		$this->value = $value;
	}//end set_value()

	public function set_desc( $desc ) {
		$this->desc = $desc;
	}//end set_desc()

	public function enqueue_assets() {

		$screen = get_current_screen();
		if ( 'nelio-a-b-testing_page_nelio-ab-testing-settings' !== $screen->id ) {
			return;
		}//end if

		wp_enqueue_style(
			'nab-individual-settings',
			nelioab()->plugin_url . '/assets/dist/css/individual-settings.css',
			array( 'nab-components' ),
			nab_get_script_version( 'individual-settings' )
		);
		nab_enqueue_script_with_auto_deps( 'nab-individual-settings', 'individual-settings', true );

		$settings = array(
			'component'  => $this->component,
			'id'         => $this->get_field_id(),
			'name'       => $this->option_name . '[' . $this->name . ']',
			'value'      => $this->value,
			'attributes' => $this->get_field_attributes(),
		);

		wp_add_inline_script(
			'nab-individual-settings',
			sprintf(
				'nab.initField( %s, %s );',
				wp_json_encode( $this->get_field_id() ),
				wp_json_encode( $settings )
			)
		);
	}//end enqueue_assets()

	// @Implements
	// phpcs:ignore
	public function display() {
		printf( '<div id="%s"></div>', esc_attr( $this->get_field_id() ) );
	}//end display()

	private function get_field_id() {
		return str_replace( '_', '-', $this->name );
	}//end get_field_id()

	protected function get_field_attributes() {
		return array();
	}//end get_field_attributes()
}//end class
