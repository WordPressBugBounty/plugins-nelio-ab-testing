<?php
/**
 * This file contains a viewer for the external page script.
 */

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * This class displays the external page script.
 */
class Nelio_AB_Testing_External_Page_Script extends Nelio_AB_Testing_Abstract_React_Setting {

	public function __construct( $name ) {
		parent::__construct(
			$name,
			Z::literal( null ),
			'ExternalPageScriptSetting'
		);
	}

	// @Overrides
	protected function get_field_attributes() {
		return $this->get_script();
	}

	// @Overrides
	public function print_description() {
		// @codeCoverageIgnoreStart
		?>
		<div class="setting-help" style="display:none;">
			<p><span class="description">
			<?php
				echo wp_kses(
					_x( 'This script loads test variants and tracks events on pages built with external services but still served from your WordPress domain.', 'text', 'nelio-ab-testing' ) .
						' ' .
						_x( 'Add it at the very top of the <code>head</code> of the external page as a manual substitute for the script our plugin would normally insert automatically.', 'user', 'nelio-ab-testing' ),
					array( 'code' => array() )
				);
			?>
			</span><p>
		</div>
		<?php
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Generates the script.
	 *
	 * @return array{bgcolor:string,value:string,minified:string}
	 */
	private function get_script() {
		// @codeCoverageIgnoreStart
		/** @var WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			nab_require_wp_file( '/wp-admin/includes/file.php' );
		}
		WP_Filesystem();

		$filename = nelioab()->plugin_path . '/includes/hooks/compat/external-page-script/script.js';
		$script   = '';
		if ( file_exists( $filename ) ) {
			$script = $wp_filesystem->get_contents( $filename );
			$script = is_string( $script ) ? $script : '';
		}

		$script = preg_replace( '/\t/', '  ', $script );
		$script = is_string( $script ) ? $script : '';
		$script = trim( $script );

		/** This filter is documented in includes/utils/functions/helpers.php' */
		$bgcolor = apply_filters( 'nab_alternative_loading_overlay_color', '#fff' );
		$bgcolor = is_string( $bgcolor ) ? $bgcolor : '#fff';

		return array(
			'bgcolor'  => $bgcolor,
			'value'    => $script,
			'minified' => nab_minify_js( $script ),
		);
		// @codeCoverageIgnoreEnd
	}
}
