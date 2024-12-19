<?php
/**
 * This file contains the setting for alternative loading.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      7.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

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
	}//end __construct()

	// @Overrides
	// phpcs:ignore
	protected function get_field_attributes() {
		$settings = Nelio_AB_Testing_Settings::instance();
		$value    = $settings->get( 'alternative_loading' );
		return is_array( $value ) ? $value : array( 'mode' => 'redirection' );
	}//end get_field_attributes()

	// @Implements
	// phpcs:ignore
	public function do_sanitize( $input ) {

		$value = false;

		if ( isset( $input[ $this->name ] ) ) {
			$value = $input[ $this->name ];
			$value = sanitize_text_field( $value );
			$value = json_decode( $value, ARRAY_A );
		}//end if

		if ( empty( $value ) || ! is_array( $value ) ) {
			$value = array();
		}//end if

		$input[ $this->name ] = array(
			'mode'                      => ! empty( $value['mode'] ) ? $value['mode'] : 'redirection',
			'lockParticipationSettings' => ! empty( $value['lockParticipationSettings'] ),
			'redirectIfCookieIsMissing' => ! empty( $value['redirectIfCookieIsMissing'] ),
		);

		return $input;
	}//end do_sanitize()

	// @Overrides
	// phpcs:ignore
	public function display() {
		printf( '<div id="%s"></div>', esc_attr( $this->get_field_id() ) );
	}//end display()

	private function get_field_id() {
		return str_replace( '_', '-', $this->name );
	}//end get_field_id()
}//end class
