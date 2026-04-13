<?php
/**
 * This file contains the excluded IPs cloud setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.3.0
 */

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * This class represents the excluded IPs cloud setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.3.0
 */
class Nelio_AB_Testing_Excluded_IPs_Setting extends Nelio_AB_Testing_Abstract_React_Setting {

	public function __construct( $name ) {
		parent::__construct(
			$name,
			Z::object(
				array(
					'ips'               => Z::array( Z::string() )
						->transform(
							function ( $ips ) {
								$ips = is_array( $ips ) ? $ips : array();
								$ips = array_filter( $ips, fn( $v ) => is_string( $v ) );
								$ips = array_filter(
									$ips,
									fn( $v ) => false !== filter_var( $v, FILTER_VALIDATE_IP ) || preg_match( '/^[\da-f:.*]+$/i', $v )
								);
								return array_values( $ips );
							}
						)
						->catch( array() ),
					'shouldForceUpdate' => Z::boolean()->catch( false ),
				),
			)
				->transform(
					function ( $value ) {
						$value        = is_array( $value ) ? $value : array();
						$value['ips'] = is_array( $value['ips'] ) ? $value['ips'] : array();

						$result = join( "\n", $value['ips'] );
						$result = ! empty( $result ) ? $result : null;

						// If it’s the same value, leave.
						if ( empty( $value['shouldForceUpdate'] ) && $result === $this->value ) {
							return $result;
						}

						// Save it on the cloud.
						$site     = nab_get_site_id();
						$params   = array( 'excludedIPs' => $value['ips'] );
						$data     = array(
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
						$url      = nab_get_api_url( '/site/' . $site, 'wp' );
						$response = wp_remote_request( $url, $data );
						$body     = nab_extract_response_body( $response );

						if ( is_wp_error( $body ) ) {
							return null;
						}

						return $result;
					}
				)
				->catch( null ),
			'ExcludedIPsSetting'
		);
	}

	// @Overrides
	protected function get_field_attributes() {
		return array( 'ips' => array() );
	}

	// @Overrides
	public function print_description() {
		// @codeCoverageIgnoreStart
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
		// @codeCoverageIgnoreEnd
	}
}
