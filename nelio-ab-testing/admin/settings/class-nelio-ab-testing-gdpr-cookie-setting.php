<?php
/**
 * This file contains the GDPR cookie setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.2.0
 */

defined( 'ABSPATH' ) || exit;

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
	}

	// @Overrides
	protected function get_field_attributes() {
		$placeholder = apply_filters( 'nab_gdpr_cookie', false );
		$placeholder = is_string( $placeholder ) ? $placeholder : '';

		$settings            = Nelio_AB_Testing_Settings::instance();
		$gdpr_cookie_setting = $settings->get( 'gdpr_cookie_setting' );
		return array_merge( $gdpr_cookie_setting, array( '_placeholder' => $placeholder ) );
	}

	// @Implements
	public function do_sanitize( $input ) {

		$value = isset( $input[ $this->name ] ) ? $input[ $this->name ] : '';
		$value = is_string( $value ) ? $value : '';
		$value = sanitize_text_field( $value );
		$value = json_decode( $value, true );
		$value = is_array( $value ) ? $value : array();

		$value = wp_parse_args(
			$value,
			array(
				'name'  => '',
				'value' => '',
			)
		);

		$input[ $this->name ] = $value;
		return $input;
	}

	// @Overrides
	public function display() {
		printf( '<div id="%s"><span class="nab-dynamic-setting-loader"></span></div>', esc_attr( $this->get_field_id() ) );
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
