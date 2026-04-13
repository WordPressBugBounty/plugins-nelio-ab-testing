<?php
/**
 * This file defines a helper class to add react-based components in our settings screen.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.1.0
 */

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;

/**
 * Helper class to add react-based components.
 *
 * @extends Nelio_AB_Testing_Abstract_Setting<mixed>
 *
 * @since 6.1.0
 */
abstract class Nelio_AB_Testing_Abstract_React_Setting extends Nelio_AB_Testing_Abstract_Setting {

	/**
	 * Whether it has description.
	 *
	 * @var bool
	 */
	protected $desc;

	/**
	 * Zod schema.
	 *
	 * @var Schema
	 */
	protected $schema;

	/**
	 * The React component name.
	 *
	 * @var string
	 */
	protected $component;

	/**
	 * Whether the setting is disabled or not.
	 *
	 * @var bool
	 */
	protected $disabled;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param string $name      The name that identifies this setting.
	 * @param Schema $schema    Schema to validate data.
	 * @param string $component The React component that will render this setting.
	 */
	public function __construct( $name, $schema, $component ) {
		parent::__construct( $name );
		$this->schema    = $schema;
		$this->component = $component;
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Sets the internal description.
	 *
	 * @param bool $desc New description.
	 *
	 * @return void
	 */
	final public function set_desc( $desc ) {
		$this->desc = $desc; // @codeCoverageIgnore
	}

	/**
	 * Whether the setting is disabled or not.
	 *
	 * @return bool
	 */
	final public function is_disabled() {
		return $this->disabled; // @codeCoverageIgnore
	}

	/**
	 * Marks the setting as enabled/disabled.
	 *
	 * @param bool $disabled Wheter it’s disabled or not.
	 *
	 * @return void
	 */
	final public function mark_as_disabled( $disabled ) {
		$this->disabled = $disabled; // @codeCoverageIgnore
	}

	// @Implements
	final public function display() {
		// @codeCoverageIgnoreStart
		printf( '<div id="%s"><span class="nab-dynamic-setting-loader"></span></div>', esc_attr( $this->get_field_id() ) );
		$this->print_description();
		// @codeCoverageIgnoreEnd
	}

	// @Implements
	final public function do_sanitize( $input ) {
		$value = isset( $input[ $this->name ] ) ? $input[ $this->name ] : '';
		$value = is_string( $value ) ? $value : '';
		$value = sanitize_text_field( $value );
		$value = json_decode( $value, true );

		$parsed = $this->schema->safe_parse( $value );
		if ( $parsed['success'] && ! is_null( $parsed['data'] ) ) {
			$input[ $this->name ] = $parsed['data'];
		} else {
			unset( $input[ $this->name ] );
		}

		return $input;
	}

	/**
	 * Callback function to enqueue required assets (script and styles).
	 *
	 * @return void
	 */
	public function enqueue_assets() {

		$screen = get_current_screen();
		if ( empty( $screen ) || 'nelio-a-b-testing_page_nelio-ab-testing-settings' !== $screen->id ) {
			return; // @codeCoverageIgnore
		}

		wp_enqueue_style(
			'nab-individual-settings',
			nelioab()->plugin_url . '/assets/dist/css/individual-settings.css',
			array( 'nab-components' ),
			nab_get_script_version( 'individual-settings' )
		);
		nab_enqueue_script_with_auto_deps( 'nab-individual-settings', 'individual-settings', true );

		$settings = array(
			'component'  => $this->component,
			'id'         => $this->get_field_id(),
			'name'       => $this->option_name . '[' . $this->name . ']',
			'value'      => $this->value,
			'attributes' => $this->get_field_attributes(),
			'disabled'   => $this->disabled,
		);

		wp_add_inline_script(
			'nab-individual-settings',
			sprintf(
				'nab.initField( %s, %s );',
				wp_json_encode( $this->get_field_id() ),
				wp_json_encode( $settings )
			)
		);
	}

	// @codeCoverageIgnoreStart
	/**
	 * Prints the description.
	 *
	 * @return void
	 */
	protected function print_description() {
		// No description by default.
	}
	// @codeCoverageIgnoreEnd

	/**
	 * Returns the list of attributes used by this setting.
	 *
	 * @return mixed
	 */
	protected function get_field_attributes() {
		return $this->value;
	}

	/**
	 * Returns the ID of this field.
	 *
	 * @return string
	 */
	private function get_field_id() {
		return str_replace( '_', '-', $this->name );
	}
}
