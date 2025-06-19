<?php
/**
 * This file contains the AI Privacy Settings.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

/**
 * This class represents the AI Privacy Settings.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      8.0.0
 */
class Nelio_AB_Testing_AI_Privacy_Settings extends Nelio_AB_Testing_Abstract_React_Setting {

	public function __construct() {
		parent::__construct( 'ai_privacy_settings', 'AiPrivacySettings' );
	}//end __construct()

	// @Overrides
	// phpcs:ignore
	protected function get_field_attributes() {
		$settings = Nelio_AB_Testing_Settings::instance();
		$value    = $settings->get( $this->name );
		$value    = is_array( $value ) ? $value : array();
		return $value;
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
				'postTypes'                   => array( 'page', 'post' ),
				'isWooCommerceEnabled'        => true,
				'includeWooCommerceOrderInfo' => true,
			)
		);

		asort( $value['postTypes'] );
		$value['postTypes'] = array_values( $value['postTypes'] );

		if (
			nab_is_subscribed_to_addon( 'nelio-ai' ) &&
			! empty( nab_array_get( $input, 'is_nelio_ai_enabled' ) )
		) {
			update_option( 'nab_show_ai_setup_screen', 'no' );
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
				esc_html_x( 'Allows Nelio A/B Testing to access your Google Analytics 4 data, enabling more accurate and relevant test candidate suggestions powered by Nelio AI.', 'text', 'nelio-ab-testing' ),
			);
			?>
		</div>
		<?php
	}//end display()

	private function get_field_id() {
		return str_replace( '_', '-', $this->name );
	}//end get_field_id()
}//end class
