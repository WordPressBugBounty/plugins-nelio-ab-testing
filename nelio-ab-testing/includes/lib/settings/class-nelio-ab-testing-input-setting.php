<?php
/**
 * This file contains the Input Setting class.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class represents an input setting.
 *
 * There are different type of inputs:
 *  * Text,
 *  * Email,
 *  * Number, and
 *  * Password.
 *
 * Depending on the specific type used, the user interface may vary a
 * little bit and will only accept a certain type of data. Moreover,
 * the specific type also modifies the sanitization function.
 *
 * @extends \Nelio_AB_Testing_Abstract_Setting<string>
 *
 * @since 5.0.0
 */
class Nelio_AB_Testing_Input_Setting extends Nelio_AB_Testing_Abstract_Setting {

	/**
	 * The specific type of this HTML input element.
	 *
	 * @var 'text'|'private_text'|'password'|'email'|'number'
	 */
	protected $type;

	/**
	 * A placeholder text to be displayed when the field is empty.
	 *
	 * @var string
	 */
	protected $placeholder;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param string                                            $name        The name that identifies this setting.
	 * @param string                                            $desc        A text that describes this field.
	 * @param string                                            $more        A link pointing to more information about this field.
	 * @param 'text'|'private_text'|'password'|'email'|'number' $type        The specific type of this input.
	 *                                                                       It can be `text`, `email`, `number`, and `password`.
	 * @param string                                            $placeholder A placeholder text to be displayed when the field is empty.
	 *
	 * @since  5.0.0
	 */
	public function __construct( $name, $desc, $more, $type, $placeholder = '' ) {
		parent::__construct( $name, $desc, $more );
		$this->type        = $type;
		$this->placeholder = $placeholder;
	}

	// @Implements
	/** . @SuppressWarnings( PHPMD.UnusedLocalVariable, PHPMD.ShortVariableName ) */
	public function display() { // @codingStandardsIgnoreLine
		// Preparing data for the partial.
		$id          = $this->option_name . '_' . str_replace( '_', '-', $this->name );
		$name        = $this->option_name . '[' . $this->name . ']';
		$desc        = $this->desc;
		$more        = $this->more;
		$value       = $this->value;
		$type        = $this->type;
		$placeholder = $this->placeholder;
		$disabled    = $this->is_disabled();
		include $this->get_partial_full_path( '/nelio-ab-testing-input-setting.php' );
	}

	// @Implements
	protected function do_sanitize( $input ) { // @codingStandardsIgnoreLine
		if ( ! isset( $input[ $this->name ] ) ) {
			$input[ $this->name ] = $this->value;
		}

		$value = $input[ $this->name ];
		switch ( $this->type ) {
			case 'text':
			case 'private_text':
			case 'password':
				$value = $this->sanitize_text( $value );
				break;
			case 'email':
				$value = $this->sanitize_email( $value );
				break;
			case 'number':
				$value = $this->sanitize_number( $value );
				break;
		}

		$input[ $this->name ] = $value;

		return $input;
	}

	/**
	 * This function sanitizes the input value.
	 *
	 * @param mixed $value The current value that has to be sanitized.
	 *
	 * @return string The input text properly sanitized.
	 *
	 * @see    sanitize_text_field
	 * @since  5.0.0
	 */
	private function sanitize_text( $value ) {
		$value = is_string( $value ) ? $value : '';
		return sanitize_text_field( $value );
	}

	/**
	 * This function sanitizes the email.
	 *
	 * @param mixed $value The current value that has to be sanitized.
	 *
	 * @return string The input text properly sanitized.
	 *
	 * @since  5.0.0
	 */
	private function sanitize_email( $value ) {
		$value = is_string( $value ) ? $value : '';
		return sanitize_email( $value );
	}

	/**
	 * This function checks that the input value is a number and converts it to an actual integer.
	 *
	 * @param mixed $value The current value that has to be sanitized.
	 *
	 * @return string The input text converted into a number.
	 *
	 * @since  5.0.0
	 */
	private function sanitize_number( $value ) {
		$value = is_string( $value ) ? $value : '0';
		$value = intval( $value );
		return "{$value}";
	}
}
