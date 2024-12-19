<?php
/**
 * This file contains the Checkbox Setting class.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class represents a checkbox setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings
 * @since      5.0.0
 */
class Nelio_AB_Testing_Checkbox_Setting extends Nelio_AB_Testing_Abstract_Setting {

	/**
	 * Whether this checkbox is checked or not.
	 *
	 * @since  5.0.0
	 * @var    boolean
	 */
	protected $checked;

	/**
	 * Sets whether this checkbox is checked or not.
	 *
	 * @param string $option_name The name of an option to sanitize and save.
	 *
	 * @since  5.0.0
	 */
	public function set_option_name( $option_name ) {
		$this->option_name = $option_name;
	}//end set_option_name()

	/**
	 * Sets whether this checkbox is checked or not.
	 *
	 * @param boolean $value Whether this checkbox is checked or not.
	 *
	 * @since  5.0.0
	 */
	public function set_value( $value ) {

		$this->checked = $value;
	}//end set_value()

	// @Implements
	/** . @SuppressWarnings( PHPMD.UnusedLocalVariable, PHPMD.ShortVariableName ) */
	public function display() { // @codingStandardsIgnoreLine

		// Preparing data for the partial.
		$id       = str_replace( '_', '-', $this->name );
		$name     = $this->option_name . '[' . $this->name . ']';
		$desc     = $this->desc;
		$more     = $this->more;
		$checked  = $this->checked;
		$disabled = $this->is_disabled();
		// phpcs:ignore
		include $this->get_partial_full_path( '/nelio-ab-testing-checkbox-setting.php' );
	}//end display()

	// @Implements
	protected function do_sanitize( $input ) { // @codingStandardsIgnoreLine

		$value = false;

		if ( isset( $input[ $this->name ] ) ) {

			if ( 'on' === $input[ $this->name ] ) {
				$value = true;
			} elseif ( true === $input[ $this->name ] ) {
				$value = true;
			}//end if
		}//end if

		$input[ $this->name ] = $value;

		return $input;
	}//end do_sanitize()

	// @Override
	protected function generate_label() { // @codingStandardsIgnoreLine

		return sprintf(
			'<span%s>%s</span>',
			$this->is_disabled() ? ' style="opacity:0.6"' : '',
			$this->label
		);
	}//end generate_label()
}//end class
