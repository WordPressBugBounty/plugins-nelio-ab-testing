<?php
/**
 * This file contains the AI Privacy Settings.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      8.0.0
 */

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * This class represents the AI Privacy Settings.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      8.0.0
 */
class Nelio_AB_Testing_AI_Privacy_Settings extends Nelio_AB_Testing_Abstract_React_Setting {

	public function __construct( $name ) {
		parent::__construct(
			$name,
			Z::object(
				array(
					'postTypes'                   => Z::array( Z::string() )
						->transform(
							function ( $value ) {
								$value = is_array( $value ) ? $value : array( 'page', 'post' );
								asort( $value );
								$value = array_values( $value );
								return $value;
							}
						)
						->catch( array( 'page', 'post' ) ),
					'isWooCommerceEnabled'        => Z::boolean()->catch( true ),
					'includeWooCommerceOrderInfo' => Z::boolean()->catch( true ),
				)
			)->catch(
				array(
					'postTypes'                   => array( 'page', 'post' ),
					'isWooCommerceEnabled'        => true,
					'includeWooCommerceOrderInfo' => true,
				)
			),
			'AiPrivacySettings'
		);

		$instance = Nelio_AB_Testing_Settings::instance();
		$name     = $instance->get_name();
		add_action( "update_option_{$name}", array( $this, 'maybe_disable_setup_screen' ) );
	}

	/**
	 * Callback to disable setup screen from AI button in UI.
	 *
	 * @param array<mixed> $option Option.
	 *
	 * @return void
	 */
	public function maybe_disable_setup_screen( $option ) {
		if ( ! empty( $option['is_nelio_ai_enabled'] ) ) {
			update_option( 'nab_show_ai_setup_screen', 'no' );
		}
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
