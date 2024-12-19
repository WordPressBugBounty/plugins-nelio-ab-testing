<?php
/**
 * This file contains the Checkbox Set Setting class.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class represents a set of checkboxes setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings
 * @since      5.0.0
 */
class Nelio_AB_Testing_Checkbox_Set_Setting extends Nelio_AB_Testing_Abstract_Setting {

	/**
	 * List of checkboxes.
	 *
	 * In this particular case, the instantiated checkboxes are not directly
	 * registered. We register the whole set using this instance.
	 *
	 * @see Nelio_AB_Testing_Checkbox_Setting
	 *
	 * @since  5.0.0
	 * @var    array
	 */
	protected $checkboxes;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param array $options A list with the required information for creating checkboxes.
	 *
	 * @since  5.0.0
	 */
	public function __construct( $options ) {
		parent::__construct();
		$this->checkboxes = array();

		foreach ( $options as $option ) {

			if ( isset( $option['more'] ) ) {
				$more = $option['more'];
			} else {
				$more = '';
			}//end if

			$checkbox = new Nelio_AB_Testing_Checkbox_Setting(
				$option['name'],
				$option['desc'],
				$more
			);

			$this->checkboxes[ $option['name'] ] = $checkbox;

		}//end foreach
	}//end __construct()

	/**
	 * Sets the value of this setting to the given value.
	 *
	 * @param array $tuple A tuple with the name of the specific checkbox and its concrete value.
	 *
	 * @since  5.0.0
	 */
	public function set_value( $tuple ) {

		$name  = $tuple['name'];
		$value = $tuple['value'];

		if ( isset( $this->checkboxes[ $name ] ) ) {
			$checkbox = $this->checkboxes[ $name ];
			$checkbox->set_value( $value );
		}//end if
	}//end set_value()

	// @Implements
	public function display() { // @codingStandardsIgnoreLine

		foreach ( $this->checkboxes as $checkbox ) {
			$checkbox->display();
		}//end foreach
	}//end display()

	// @Implements
	protected function do_sanitize( $input ) { // @codingStandardsIgnoreLine

		foreach ( $this->checkboxes as $checkbox ) {
			$input = $checkbox->do_sanitize( $input );
		}//end foreach
		return $input;
	}//end do_sanitize()

	// @Overrides
	public function register( $label, $page, $section, $option_group, $option_name ) { // @codingStandardsIgnoreLine

		parent::register( $label, $page, $section, $option_group, $option_name );
		foreach ( $this->checkboxes as $checkbox ) {
			$checkbox->set_option_name( $option_name );
		}//end foreach
	}//end register()
}//end class
