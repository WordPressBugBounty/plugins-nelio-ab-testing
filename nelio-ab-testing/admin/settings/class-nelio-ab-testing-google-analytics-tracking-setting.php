<?php
/**
 * This file contains the Google Analytics tracking setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      8.0.0
 */

defined( 'ABSPATH' ) || exit;

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
	}

	// @Overrides
	protected function get_field_attributes() {
		$settings = Nelio_AB_Testing_Settings::instance();
		return $settings->get( 'google_analytics_tracking' );
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
				'enabled'       => false,
				'measurementId' => '',
				'apiSecret'     => '',
			)
		);

		if ( empty( $value['enabled'] ) ) {
			$value['measurementId'] = '';
			$value['apiSecret']     = '';
		}

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
				'<div class="description"><p>%s</p></div>',
				esc_html_x( 'Grants Nelio A/B Testing access to your Google Analytics 4 account to automatically send A/B testing events (such as views and conversions). This allows you to track and analyze experiment performance directly in your Google Analytics reports.', 'text', 'nelio-ab-testing' )
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
