<?php
/**
 * This file contains the GDPR cookie setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

/**
 * This class represents the GDPR cookie setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.2.0
 */
class Nelio_AB_Testing_GDPR_Cookie_Setting extends Nelio_AB_Testing_Abstract_React_Setting {

	public function __construct() {
		parent::__construct( 'gdpr_cookie_setting', 'GdprCookieSetting' );
	}//end __construct()

	// @Overrides
	// phpcs:ignore
	protected function get_field_attributes() {
		$settings = Nelio_AB_Testing_Settings::instance();

		$gdpr_cookie_setting = $settings->get( 'gdpr_cookie_setting' );
		$gdpr_cookie_setting = is_array( $gdpr_cookie_setting ) ? $gdpr_cookie_setting : array(
			'name'  => '',
			'value' => '',
		);

		$placeholder = apply_filters( 'nab_gdpr_cookie', false );
		$placeholder = is_string( $placeholder ) ? $placeholder : '';

		return array_merge( $gdpr_cookie_setting, array( '_placeholder' => $placeholder ) );
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

		if ( empty( $value ) ) {
			$value = array();
		}//end if

		$value = wp_parse_args(
			$value,
			array(
				'name'  => '',
				'value' => '',
			)
		);

		$input[ $this->name ] = $value;
		return $input;
	}//end do_sanitize()

	// @Overrides
	// phpcs:ignore
	public function display() {
		printf( '<div id="%s"></div>', esc_attr( $this->get_field_id() ) );
		?>
		<div class="setting-help" style="display:none;">
			<?php
			printf(
				'<div class="description"><p>%s</p><p>%s</p><p>%s</p></div>',
				esc_html_x( 'The name of the cookie that should exist if GDPR has been accepted and, therefore, tracking is allowed. Leave empty if you don’t need to adhere to GDPR and want to test all your visitors.', 'user', 'nelio-ab-testing' ),
				esc_html_x( 'If you want to, you can also specify the value the cookie should have to enable visitor tracking.', 'user', 'nelio-ab-testing' ),
				esc_html_x( 'Use asterisks (*) to match any number of characters at any point.', 'user', 'nelio-ab-testing' )
			);
			?>
		</div>
		<?php
	}//end display()

	private function get_field_id() {
		return str_replace( '_', '-', $this->name );
	}//end get_field_id()
}//end class
