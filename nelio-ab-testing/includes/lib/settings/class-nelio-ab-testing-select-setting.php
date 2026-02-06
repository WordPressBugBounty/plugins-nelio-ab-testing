<?php
/**
 * This file contains the Select Setting class.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class represents a Select setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings
 * @since      5.0.0
 */
class Nelio_AB_Testing_Select_Setting extends Nelio_AB_Testing_Abstract_Setting {

	/**
	 * The currently-selected value of this select.
	 *
	 * @since  5.0.0
	 * @var    string
	 */
	protected $value;

	/**
	 * The list of options.
	 *
	 * @since  5.0.0
	 * @var    list<array{value:string, label:string, desc?: string, disabled?:bool}>
	 */
	protected $options;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param string                                                                 $name    The name that identifies this setting.
	 * @param string                                                                 $desc    A text that describes this field.
	 * @param string                                                                 $more    A link pointing to more information about this field.
	 * @param list<array{value:string, label:string, desc?: string, disabled?:bool}> $options The list of options.
	 *
	 * @since  5.0.0
	 */
	public function __construct( $name, $desc, $more, $options ) {
		parent::__construct( $name, $desc, $more );
		$this->options = $options;
	}

	/**
	 * Specifies which option is selected.
	 *
	 * @param string $value The currently-selected value of this select.
	 *
	 * @since  5.0.0
	 */
	public function set_value( $value ) {
		$this->value = $value;
	}

	// @Implements
	/** . @SuppressWarnings( PHPMD.UnusedLocalVariable, PHPMD.ShortVariableName ) */
	public function display() { // @codingStandardsIgnoreLine

		// Preparing data for the partial.
		$id       = str_replace( '_', '-', $this->name );
		$name     = $this->option_name . '[' . $this->name . ']';
		$value    = $this->value;
		$options  = $this->options;
		$desc     = $this->desc;
		$more     = $this->more;
		$disabled = $this->is_disabled();
		include $this->get_partial_full_path( '/nelio-ab-testing-select-setting.php' );
	}

	// @Implements
	protected function do_sanitize( $input ) { // @codingStandardsIgnoreLine

		if ( ! isset( $input[ $this->name ] ) ) {
			$input[ $this->name ] = $this->value;
		}

		$is_value_correct = false;
		foreach ( $this->options as $option ) {
			if ( $option['value'] === $input[ $this->name ] ) {
				$is_value_correct = true;
			}
		}

		if ( ! $is_value_correct ) {
			$input[ $this->name ] = $this->value;
		}

		return $input;
	}
}
