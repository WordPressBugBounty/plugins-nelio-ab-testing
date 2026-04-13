<?php
/**
 * This file contains the Google Analytics Data setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      8.0.0
 */

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * This class represents the Google Analytics data setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      8.0.0
 */
class Nelio_AB_Testing_Google_Analytics_Data_Setting extends Nelio_AB_Testing_Abstract_React_Setting {

	public function __construct( $name ) {
		parent::__construct(
			$name,
			Z::object(
				array(
					'enabled'      => Z::boolean()->catch( false ),
					'propertyId'   => Z::string()->trim()->catch( '' ),
					'propertyName' => Z::string()->trim()->catch( '' ),
				)
			)
				->transform(
					function ( $value ) {
						$value = is_array( $value ) ? $value : array();
						if ( empty( $value['enabled'] ) ) {
							$value['propertyId']   = '';
							$value['propertyName'] = '';
						}
						unset( $value['enabled'] );
						return $value;
					}
				)
				->catch(
					array(
						'propertyId'   => '',
						'propertyName' => '',
					)
				),
			'GoogleAnalyticsDataSetting'
		);
	}

	// @Overrides
	protected function get_field_attributes() {
		$settings = Nelio_AB_Testing_Settings::instance();
		$value    = $settings->get( 'google_analytics_data' );
		return array_merge( $value, array( 'enabled' => ! empty( $value['propertyId'] ) ) );
	}

	// @Overrides
	public function print_description() {
		// @codeCoverageIgnoreStart
		?>
		<div class="setting-help" style="display:none;">
			<?php
			printf(
				'<div class="description"><p>%s</p></div>',
				esc_html_x( 'Allows Nelio A/B Testing to access your Google Analytics 4 data, enabling more accurate and relevant test candidate suggestions powered by Nelio AI.', 'text', 'nelio-ab-testing' )
			);
			?>
		</div>
		<?php
		// @codeCoverageIgnoreEnd
	}
}
