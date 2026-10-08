<?php
/**
 * This file contains the product offers setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      8.5.5
 */

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * This class represents the product offers setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      8.5.5
 */
class Nelio_AB_Testing_Product_Offers_Setting extends Nelio_AB_Testing_Abstract_React_Setting {

	public function __construct( $name ) {
		parent::__construct(
			$name,
			Z::object(
				array(
					'hasUserObjectedToProductOffers' => Z::boolean()->catch( false ),
				),
			)
				->transform( fn( $v ) => is_array( $v ) && ! empty( $v['hasUserObjectedToProductOffers'] ) )
				->catch( false ),
			'ProductOffersSetting'
		);
	}

	// @Overrides
	protected function get_field_attributes() {
		$settings = Nelio_AB_Testing_Settings::instance();
		return array(
			'hasUserObjectedToProductOffers' => $settings->get( 'has_user_objected_to_product_offers' ),
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
				esc_html_x( 'Allows Nelio to use information about how this plugin is used to show relevant offers for Nelio A/B Testing and measure their effectiveness.', 'text', 'nelio-ab-testing' ) .
				' ' .
				esc_html_x( 'You can disable this at any time. Disabling it does not affect the plugin’s functionality.', 'text', 'nelio-ab-testing' ),
			);
			?>
		</div>
		<?php
		// @codeCoverageIgnoreEnd
	}
}
