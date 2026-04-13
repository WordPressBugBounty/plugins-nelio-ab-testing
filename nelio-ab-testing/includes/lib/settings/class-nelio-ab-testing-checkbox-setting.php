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
 * @extends \Nelio_AB_Testing_Abstract_Setting<boolean>
 *
 * @since 5.0.0
 */
class Nelio_AB_Testing_Checkbox_Setting extends Nelio_AB_Testing_Abstract_Setting {

	// @Implements
	/** . @SuppressWarnings( PHPMD.UnusedLocalVariable, PHPMD.ShortVariableName ) */
	public function display() { // @codingStandardsIgnoreLine
		// Preparing data for the partial.
		$id       = $this->option_name . '_' . str_replace( '_', '-', $this->name );
		$name     = $this->option_name . '[' . $this->name . ']';
		$desc     = $this->desc;
		$more     = $this->more;
		$checked  = $this->value;
		$disabled = $this->is_disabled();
		include $this->get_partial_full_path( '/nelio-ab-testing-checkbox-setting.php' );
	}

	// @Implements
	protected function do_sanitize( $input ) { // @codingStandardsIgnoreLine
		$possible_values      = array( 'on', '1', 'true', true );
		$input[ $this->name ] = in_array( $input[ $this->name ] ?? null, $possible_values, true );
		return $input;
	}
}
