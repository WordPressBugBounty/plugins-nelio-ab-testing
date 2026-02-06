<?php
/**
 * This file contains the excluded IPs cloud setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class represents the excluded IPs cloud setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.3.0
 */
class Nelio_AB_Testing_Excluded_IPs_Setting extends Nelio_AB_Testing_Abstract_React_Setting {

	public function __construct() {
		parent::__construct( 'excluded_ips', 'ExcludedIPsSetting' );
	}

	// @Overrides
	protected function get_field_attributes() {
		return array( 'ips' => array() );
	}

	// @Implements
	public function do_sanitize( $input ) {
		if ( ! isset( $input[ $this->name ] ) ) {
			return $input;
		}

		$value = $input[ $this->name ];
		$value = is_string( $value ) ? $value : '';
		$value = sanitize_text_field( $value );
		$value = json_decode( $value, true );
		$value = is_array( $value ) ? $value : array();
		$value = $value['ips'] ?? array();
		$value = is_array( $value ) ? $value : array();
		$value = array_filter( $value, fn( $v ) => false !== filter_var( $v, FILTER_VALIDATE_IP ) );
		$value = array_values( $value );

		$input[ $this->name ] = join( "\n", $value );

		// If it’s the same value, leave.
		if ( empty( $input[ "{$this->name}_force_update" ] ) ) {
			$settings = Nelio_AB_Testing_Settings::instance();
			if ( $input[ $this->name ] === $settings->get( $this->name ) ) {
				return $input;
			}
		}

		$site   = nab_get_site_id();
		$params = array( 'excludedIPs' => $value );

		$data = array(
			'method'    => 'PUT',
			'timeout'   => absint( apply_filters( 'nab_request_timeout', 30 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => (string) wp_json_encode( $params ),
		);

		// Save it on the cloud.
		$url = nab_get_api_url( '/site/' . $site, 'wp' );
		$res = wp_remote_request( $url, $data );

		if ( is_wp_error( $res ) ) {
			unset( $input[ $this->name ] );
		}

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
				esc_html_x( 'Exclude one or more IPs from being tracked on your tests.', 'user', 'nelio-ab-testing' ),
				esc_html_x( 'Visitors from these IPs will be able to see alternative content, but their events won’t be tracked by our plugin.', 'text', 'nelio-ab-testing' ),
				esc_html_x( 'Use asterisks (*) instead of numbers to match IP subnets.', 'user', 'nelio-ab-testing' )
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
