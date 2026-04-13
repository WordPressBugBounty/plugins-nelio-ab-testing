<?php
/**
 * This file contains the GDPR cookie setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.2.0
 */

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * This class represents the GDPR cookie setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.2.0
 */
class Nelio_AB_Testing_GDPR_Cookie_Setting extends Nelio_AB_Testing_Abstract_React_Setting {

	public function __construct( $name ) {
		parent::__construct(
			$name,
			Z::object(
				array(
					'name'  => Z::string()->catch( '' ),
					'value' => Z::string()->catch( '' ),
				)
			)->catch(
				array(
					'name'  => '',
					'value' => '',
				)
			),
			'GdprCookieSetting'
		);
	}

	// @Overrides
	protected function get_field_attributes() {
		$placeholder = apply_filters( 'nab_gdpr_cookie', false );
		$placeholder = is_string( $placeholder ) ? $placeholder : '';
		$settings    = Nelio_AB_Testing_Settings::instance();
		$value       = $settings->get( 'gdpr_cookie_setting' );
		return array_merge( $value, array( '_placeholder' => $placeholder ) );
	}

	// @Overrides
	public function print_description() {
		// @codeCoverageIgnoreStart
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
		// @codeCoverageIgnoreEnd
	}
}
