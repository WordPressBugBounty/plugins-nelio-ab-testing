<?php
/**
 * This file contains the Text Area Setting class.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class represents a text area setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings
 * @since      5.0.0
 */
class Nelio_AB_Testing_Text_Area_Setting extends Nelio_AB_Testing_Abstract_Setting {

	/**
	 * The concrete value of this field.
	 *
	 * @since  5.0.0
	 * @var    string
	 */
	protected $value;

	/**
	 * A placeholder text to be displayed when the field is empty.
	 *
	 * @since  5.0.0
	 * @var    string
	 */
	protected $placeholder;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param string $name        The name that identifies this setting.
	 * @param string $desc        A text that describes this field.
	 * @param string $more        A link pointing to more information about this field.
	 * @param string $placeholder A placeholder text to be displayed when the field is empty.
	 *
	 * @since  5.0.0
	 */
	public function __construct( $name, $desc, $more, $placeholder = '' ) {
		parent::__construct( $name, $desc, $more );
		$this->placeholder = $placeholder;
	}//end __construct()

	/**
	 * Sets the value of this field to the given string.
	 *
	 * @param string $value The value of this field.
	 *
	 * @since  5.0.0
	 */
	public function set_value( $value ) {
		$this->value = $value;
	}//end set_value()

	// @Implements
	/** . @SuppressWarnings( PHPMD.UnusedLocalVariable, PHPMD.ShortVariableName ) */
	public function display() { // @codingStandardsIgnoreLine

		// Preparing data for the partial.
		$id          = str_replace( '_', '-', $this->name );
		$name        = $this->option_name . '[' . $this->name . ']';
		$desc        = $this->desc;
		$more        = $this->more;
		$value       = $this->value;
		$placeholder = $this->placeholder;
		$disabled    = $this->is_disabled();
		// phpcs:ignore
		include $this->get_partial_full_path( '/nelio-ab-testing-text-area-setting.php' );
	}//end display()

	// @Implements
	protected function do_sanitize( $input ) { // @codingStandardsIgnoreLine

		if ( ! isset( $input[ $this->name ] ) ) {
			$input[ $this->name ] = $this->value;
		}//end if

		$value                = $this->sanitize_text( $input[ $this->name ] );
		$input[ $this->name ] = $value;

		return $input;
	}//end do_sanitize()

	/**
	 * This function sanitizes the input value.
	 *
	 * @param string $value The current value that has to be sanitized.
	 *
	 * @return string The input text properly sanitized.
	 *
	 * @see    sanitize_text_field
	 * @since  5.0.0
	 */
	private function sanitize_text( $value ) {
		return sanitize_textarea_field( $value );
	}//end sanitize_text()
}//end class
