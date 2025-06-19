<?php
/**
 * This file contains the Google Analytics tracking setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

/**
 * This class represents the Google Analytics tracking setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      8.0.0
 */
class Nelio_AB_Testing_Google_Analytics_Tracking_Setting extends Nelio_AB_Testing_Abstract_React_Setting {

	public function __construct() {
		parent::__construct( 'google_analytics_tracking', 'GoogleAnalyticsTrackingSetting' );
	}//end __construct()

	// @Overrides
	// phpcs:ignore
	protected function get_field_attributes() {
		$settings = Nelio_AB_Testing_Settings::instance();
		return $settings->get( $this->name );
	}//end get_field_attributes()

	// @Implements
	// phpcs:ignore
	public function do_sanitize( $input ) {

		$value = isset( $input[ $this->name ] ) ? $input[ $this->name ] : '';
		$value = sanitize_text_field( $value );
		$value = json_decode( $value, ARRAY_A );
		$value = is_array( $value ) ? $value : array();
		$value = wp_parse_args(
			$value,
			array(
				'enabled'       => false,
				'measurementId' => '',
				'apiSecret'     => '',
			)
		);

		if ( empty( $value['enabled'] ) ) {
			$value['measurementId'] = '';
			$value['apiSecret']     = '';
		}//end if

		$input[ $this->name ] = $value;
		return $input;
	}//end do_sanitize()

	// @Overrides
	// phpcs:ignore
	public function display() {
		printf( '<div id="%s"><span class="nab-dynamic-setting-loader"></span></div>', esc_attr( $this->get_field_id() ) );
		?>
		<div class="setting-help" style="display:none;">
			<?php
			printf(
				'<div class="description"><p>%s</p></div>',
				esc_html_x( 'Grants Nelio A/B Testing access to your Google Analytics 4 account to automatically send A/B testing events (such as views and conversions). This allows you to track and analyze experiment performance directly in your Google Analytics reports.', 'text', 'nelio-ab-testing' ),
			);
			?>
		</div>
		<?php
	}//end display()

	private function get_field_id() {
		return str_replace( '_', '-', $this->name );
	}//end get_field_id()
}//end class
