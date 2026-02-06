<?php
/**
 * Abstract class that implements the `register` method of the `Nelio_AB_Testing_Setting` interface.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * A class that represents a Nelio_AB_Testing Setting.
 *
 * It only implements the `register` method, which will be common among all
 * Nelio_AB_Testing A/B Testing's settings.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings
 * @since      5.0.0
 */
abstract class Nelio_AB_Testing_Abstract_Setting implements Nelio_AB_Testing_Setting {

	/**
	 * The label associated to this setting.
	 *
	 * @since  5.0.0
	 * @var    string
	 */
	protected $label;

	/**
	 * The name that identifies this setting.
	 *
	 * @since  5.0.0
	 * @var    string
	 */
	protected $name;

	/**
	 * A text that describes this field.
	 *
	 * @since  5.0.0
	 * @var    string|bool
	 */
	protected $desc;

	/**
	 * A link pointing to more information about this field.
	 *
	 * @since  5.0.0
	 * @var    string
	 */
	protected $more;

	/**
	 * The option name in which this setting will be stored.
	 *
	 * @since  5.0.0
	 * @var    string
	 */
	protected $option_name;

	/**
	 * Whether this setting is disabled or not.
	 *
	 * @since  5.0.0
	 * @var    boolean
	 */
	protected $disabled;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param string $name The name that identifies this setting.
	 * @param string $desc Optional. A text that describes this field.
	 *                     Default: the empty string.
	 * @param string $more Optional. A link pointing to more information about this field.
	 *                     Default: the empty string.
	 *
	 * @since  5.0.0
	 */
	public function __construct( $name, $desc = '', $more = '' ) {

		$this->name     = $name;
		$this->desc     = $desc;
		$this->more     = $more;
		$this->disabled = false;
	}

	/**
	 * Returns the name that identifies this setting.
	 *
	 * @return string The name that identifies this setting.
	 *
	 * @since  5.0.0
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Prints the description by properly escaping it.
	 *
	 * @param string $html Text with HTML code. Only some tags are supported.
	 *
	 * @return void
	 * @since  6.1.0
	 */
	public function print_html( $html ) {
		$tags = array(
			'<code>'    => '%1$s',
			'</code>'   => '%2$s',
			'<strong>'  => '%3$s',
			'</strong>' => '%4$s',
		);

		foreach ( $tags as $tag => $placeholder ) {
			$html = str_replace( $tag, $placeholder, $html );
		}

		printf(
			esc_html( $html ),
			'<code>',
			'</code>',
			'<strong>',
			'</strong>'
		);
	}

	// @Implements
	public function register( $label, $page, $section, $option_group, $option_name ) { // @codingStandardsIgnoreLine

		$this->label       = $label;
		$this->option_name = $option_name;

		register_setting(
			$option_group,
			$option_name,
			array( $this, 'sanitize' )
		);

		$label = $this->generate_label();
		add_settings_field(
			$this->name,
			$label,
			array( $this, 'display' ),
			$page,
			$section
		);
	}

	/**
	 * This function generates a label for this field.
	 *
	 * In particular, it adds the `label` tag and a help icon (if a description
	 * was provided).
	 *
	 * @return string the label for this field.
	 *
	 * @since  5.0.0
	 */
	protected function generate_label() {

		$label = sprintf(
			'<label for="%s"%s>%s</label>',
			$this->option_name,
			'',
			$this->label
		);

		if ( ! empty( $this->desc ) ) {
			$img    = $this->get_asset_full_url( '/images/help.png' );
			$label .= '<img class="nelio-ab-testing-help" style="float:right;margin-right:-15px;cursor:pointer;" src="' . $img . '" height="16" width="16" />';
		}

		return $label;
	}

	/**
	 * Returns whether the current setting is disabled or not.
	 *
	 * @return boolean whether the current setting is disabled or not.
	 *
	 * @since  5.0.0
	 */
	protected function is_disabled() {

		return $this->disabled;
	}

	/**
	 * Sets the field as disabled/enabled.
	 *
	 * @param boolean $disabled Whether the setting should be disabled or not.
	 *
	 * @return void
	 * @since  5.0.0
	 */
	public function set_as_disabled( $disabled ) {

		$this->disabled = $disabled;
	}

	// @Implements
	public function sanitize( $input ) {

		if ( $this->is_disabled() ) {
			if ( isset( $input[ $this->name ] ) ) {
				unset( $input[ $this->name ] );
			}
			return $input;
		}

		return $this->do_sanitize( $input );
	}

	/**
	 * This function implement the actual sanitization process.
	 *
	 * @param array<string,mixed> $input list of input values.
	 *
	 * @return array<string,mixed> list of output values.
	 *
	 * @since  5.0.0
	 */
	abstract protected function do_sanitize( $input );

	/**
	 * Returns the full URL to the given asset.
	 *
	 * @param string $asset an asset path relative to /assets/.
	 *
	 * @return string the full URL to the given asset.
	 *
	 * @since  5.0.0
	 */
	protected function get_asset_full_url( $asset ) {
		return untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/assets' . $asset;
	}

	/**
	 * Returns the full path to the given partial.
	 *
	 * @param string $partial a partial path relative to /partials/.
	 *
	 * @return string the full path to the given partials.
	 *
	 * @since  5.0.0
	 */
	protected function get_partial_full_path( $partial ) {
		return untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/partials' . $partial;
	}
}
