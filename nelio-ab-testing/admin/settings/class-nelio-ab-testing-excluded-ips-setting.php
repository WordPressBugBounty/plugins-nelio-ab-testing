<?php
/**
 * This file contains the excluded IPs cloud setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

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
	}//end __construct()

	// @Overrides
	// phpcs:ignore
	protected function get_field_attributes() {
		return '';
	}//end get_field_attributes()

	// @Implements
	// phpcs:ignore
	public function do_sanitize( $input ) {
		if ( ! isset( $input[ $this->name ] ) ) {
			return $input;
		}//end if

		$ips = sanitize_textarea_field( $input[ $this->name ] );
		$ips = array_map( 'trim', explode( "\n", $ips ) );
		$ips = array_values( array_filter( $ips, array( $this, 'is_ip' ) ) );

		$input[ $this->name ] = join( "\n", $ips );

		// If it’s the same value, leave.
		if ( empty( $input[ "{$this->name}_force_update" ] ) ) {
			$settings = Nelio_AB_Testing_Settings::instance();
			if ( $input[ $this->name ] === $settings->get( $this->name ) ) {
				return $input;
			}//end if
		}//end if

		$site   = nab_get_site_id();
		$params = array( 'excludedIPs' => $ips );

		$data = array(
			'method'    => 'PUT',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => wp_json_encode( $params ),
		);

		// Save it on the cloud.
		$url = nab_get_api_url( '/site/' . $site, 'wp' );
		$res = wp_remote_request( $url, $data );

		if ( is_wp_error( $res ) ) {
			unset( $input[ $this->name ] );
		}//end if

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
				esc_html_x( 'Exclude one or more IPs from being tracked on your tests.', 'user', 'nelio-ab-testing' ),
				esc_html_x( 'Visitors from these IPs will be able to see alternative content, but their events won’t be tracked by our plugin.', 'text', 'nelio-ab-testing' ),
				esc_html_x( 'Use asterisks (*) instead of numbers to match IP subnets.', 'user', 'nelio-ab-testing' )
			);
			?>
		</div>
		<?php
	}//end display()

	private function get_field_id() {
		return str_replace( '_', '-', $this->name );
	}//end get_field_id()

	public function is_ip( $value ) {
		$matches = array();
		preg_match( '/^(((\d?\d?\d)|\*)\.){3}((\d?\d?\d)|\*)$/', $value, $matches );
		return ! empty( $matches );
	}//end is_ip()
}//end class
