<?php
/**
 * This file contains the Google Analytics tracking setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      8.0.0
 */

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * This class represents the Google Analytics tracking setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      8.0.0
 */
class Nelio_AB_Testing_Google_Analytics_Tracking_Setting extends Nelio_AB_Testing_Abstract_React_Setting {

	public function __construct( $name ) {
		parent::__construct(
			$name,
			Z::object(
				array(
					'enabled'       => Z::boolean()->catch( false ),
					'measurementId' => Z::string()->trim()->catch( '' ),
					'apiSecret'     => Z::string()->trim()->catch( '' ),
				)
			)
				->transform(
					function ( $value ) {
						$value = is_array( $value ) ? $value : array();
						if ( empty( $value['enabled'] ) ) {
							$value['measurementId'] = '';
							$value['apiSecret']     = '';
						}
						return $value;
					}
				)
				->catch(
					array(
						'enabled'       => false,
						'measurementId' => '',
						'apiSecret'     => '',
					)
				),
			'GoogleAnalyticsTrackingSetting'
		);
	}

	// @Overrides
	public function print_description() {
		// @codeCoverageIgnoreStart
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
		// @codeCoverageIgnoreEnd
	}
}
