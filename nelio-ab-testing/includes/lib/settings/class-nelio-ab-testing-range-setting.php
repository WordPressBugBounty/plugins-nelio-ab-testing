<?php
/**
 * This file contains the Range Setting class.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class represents a Range setting.
 *
 * Ranges are sliders that specify a minimum and a maximum values,
 * as well as the size of its steps.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings
 * @since      5.0.0
 */
class Nelio_AB_Testing_Range_Setting extends Nelio_AB_Testing_Abstract_Setting {

	/**
	 * The current value of this field.
	 *
	 * @since  5.0.0
	 * @var    int
	 */
	protected $value;

	/**
	 * Minimum value for the range.
	 *
	 * @since  5.0.0
	 * @var    int
	 */
	protected $min;

	/**
	 * Maximum value for the range.
	 *
	 * @since  5.0.0
	 * @var    int
	 */
	protected $max;

	/**
	 * The value in which the range decrements or increments
	 *
	 * @since  5.0.0
	 * @var    int
	 */
	protected $step;

	/**
	 * Additional text that describes the range value
	 *
	 * @since  5.0.0
	 * @var    string
	 */
	protected $verbose_value;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param string $name The name that identifies this setting.
	 * @param string $desc A text that describes this field.
	 * @param string $more A link pointing to more information about this field.
	 * @param array  $args A set of specific attributes for the range.
	 *
	 * @since  5.0.0
	 */
	public function __construct( $name, $desc, $more, $args ) {

		parent::__construct( $name, $desc, $more );
		$this->verbose_value = $args['label'];
		$this->min           = $args['min'];
		$this->max           = $args['max'];
		$this->step          = $args['step'];
	}//end __construct()

	/**
	 * Sets the value of this field to the given number.
	 *
	 * @param integer $value The current value of this field.
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
		$id            = str_replace( '_', '-', $this->name );
		$name          = $this->option_name . '[' . $this->name . ']';
		$desc          = $this->desc;
		$more          = $this->more;
		$value         = $this->value;
		$verbose_value = $this->verbose_value;
		$min           = $this->min;
		$max           = $this->max;
		$step          = $this->step;
		$disabled      = $this->is_disabled();
		// phpcs:ignore
		include $this->get_partial_full_path( '/nelio-ab-testing-range-setting.php' );
	}//end display()

	// @Implements
	protected function do_sanitize( $input ) { // @codingStandardsIgnoreLine

		if ( ! isset( $input[ $this->name ] ) ) {
			$input[ $this->name ] = $this->value;
		} else {
			$input[ $this->name ] = intval( $input[ $this->name ] );
		}//end if
		return $input;
	}//end do_sanitize()
}//end class
