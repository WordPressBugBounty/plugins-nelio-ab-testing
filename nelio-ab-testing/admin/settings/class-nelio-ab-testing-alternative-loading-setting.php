<?php
/**
 * This file contains the setting for alternative loading.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      7.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class represents the setting for alternative loading.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      7.0.0
 */
class Nelio_AB_Testing_Alternative_Loading_Setting extends Nelio_AB_Testing_Abstract_React_Setting {

	public function __construct() {
		parent::__construct( 'alternative_loading', 'AlternativeLoadingSetting' );
	}

	// @Overrides
	protected function get_field_attributes() {
		$settings = Nelio_AB_Testing_Settings::instance();
		return $settings->get( 'alternative_loading' );
	}

	// @Implements
	public function do_sanitize( $input ) {

		$value = isset( $input[ $this->name ] ) ? $input[ $this->name ] : '';
		$value = is_string( $value ) ? $value : '';
		$value = sanitize_text_field( $value );
		$value = json_decode( $value, true );
		$value = is_array( $value ) ? $value : array();

		$input[ $this->name ] = array(
			'mode'                      => ! empty( $value['mode'] ) ? $value['mode'] : 'redirection',
			'lockParticipationSettings' => ! empty( $value['lockParticipationSettings'] ),
			'redirectIfCookieIsMissing' => ! empty( $value['redirectIfCookieIsMissing'] ),
		);

		return $input;
	}

	// @Overrides
	public function display() {
		printf( '<div id="%s"><span class="nab-dynamic-setting-loader"></span></div>', esc_attr( $this->get_field_id() ) );
	}

	/**
	 * Returns the ID of this field.
	 *
	 * @return string
	 */
	private function get_field_id() {
		return str_replace( '_', '-', $this->name );
	}
}
