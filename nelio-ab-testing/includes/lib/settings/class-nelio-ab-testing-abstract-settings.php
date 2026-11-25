<?php
/**
 * This file contains the class for managing any plugin's settings.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class processes an array of settings and makes them available to WordPress.
 */
abstract class Nelio_AB_Testing_Abstract_Settings {

	/**
	 * The name that identifies Nelio A/B Testing's Settings
	 *
	 * @var non-empty-string
	 */
	private $name;

	/**
	 * An array of settings that have been requested and where not found in the associated get_option entry.
	 *
	 * @var array<string,mixed>
	 */
	private $default_values = array();

	/**
	 * An array with the tabs
	 *
	 * @var list<TSettings_Tab>
	 */
	private $tabs = array();

	/**
	 * The name of the tab we're about to print.
	 *
	 * This is an aux var for enclosing all fields within a tab.
	 *
	 * @var string
	 */
	private $current_tab_name = '';

	/**
	 * The name of the tab that's currently visible.
	 *
	 * This variable depends on the value of `$_GET['tab']`.
	 *
	 * @var string
	 */
	private $opened_tab_name = '';

	/**
	 * Whether the section whose fields we’re rendering is already disabled.
	 *
	 * @var boolean
	 */
	private $is_section_already_disabled = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param non-empty-string $name The name of this options group.
	 */
	protected function __construct( $name ) {
		$this->name = $name; // @codeCoverageIgnore
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'plugins_loaded', array( $this, 'set_tabs' ), 1 );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
	}

	/**
	 * This function has to be implemented by the subclass and specifies which tabs
	 * are defined in the settings page.
	 *
	 * @return void
	 *
	 * See `do_set_tabs`.
	 */
	abstract public function set_tabs();

	/**
	 * Returns the value of the given setting.
	 *
	 * @param string $name  The name of the parameter whose value we want to obtain.
	 *
	 * @return mixed  The concrete value of the specified parameter.
	 *                If the setting has never been saved and it registered no
	 *                default value (during the construction of `Nelio_AB_Testing_Settings`),
	 *                then the parameter `$value` will be returned instead.
	 *
	 * @throws Exception If settings are called before `plugins_loaded`.
	 */
	public function get( $name ) {

		/**
		* Short-circuits get experiments with page view from request.
		*
		* @param null|array<int,int> $value A dictionary of experiment IDs and variant seen. Default: `null`.
		* @param string              $name  Settings name.
		*
		* @since 8.3.0
		*/
		$pre = apply_filters( "nab_pre_get_{$name}_setting", null, $this->get_name() );
		if ( null !== $pre ) {
			return $pre;
		}

		if ( ! doing_action( 'plugins_loaded' ) && ! did_action( 'plugins_loaded' ) ) {
			throw new Exception( esc_html_x( 'Nelio A/B Testing settings should be used after plugins_loaded.', 'text (error)', 'nelio-ab-testing' ) ); // @codeCoverageIgnore
		}

		if ( ! $this->is_setting_disabled( $name ) ) {
			/** @var array<string,mixed>|false */
			$settings = get_option( $this->get_name(), false );
			// @codeCoverageIgnoreStart
			if ( false === $settings ) {
				$settings = array();
				update_option( $this->get_name(), $settings );
			}
			// @codeCoverageIgnoreEnd
			if ( isset( $settings[ $name ] ) ) {
				return $settings[ $name ];
			}
		}

		$this->maybe_set_default_value( $name );
		if ( isset( $this->default_values[ $name ] ) ) {
			return $this->default_values[ $name ];
		} else {
			return null;
		}
	}

	/**
	 * Returns the settings exposed to AI abilities.
	 *
	 * @return array<string,TSettings_AI_Field> AI setting definitions.
	 *
	 * @since 9.0.0
	 */
	public function get_ai_fields() {
		$fields = array();

		foreach ( $this->tabs as $tab ) {
			foreach ( $tab['fields'] ?? array() as $field ) {
				if ( 'section' === $field['type'] || ! isset( $field['ai'] ) ) {
					continue;
				}

				$name = $field['name'];
				$ui   = $field['ui']();
				$ai   = $field['ai'];
				$item = array(
					'name'        => $name,
					'label'       => is_string( $ui['label'] ?? null ) ? $ui['label'] : $name,
					'description' => $ai['description'],
					'access'      => $ai['access'],
				);

				if ( isset( $ai['schema'] ) ) {
					$item['schema'] = $ai['schema'];
				}

				if ( isset( $ai['is_write_allowed'] ) ) {
					$item['is_write_allowed'] = $ai['is_write_allowed'];
				}

				if ( isset( $ai['is_write_valid'] ) ) {
					$item['is_write_valid'] = $ai['is_write_valid'];
				}

				$fields[ $name ] = $item;
			}
		}

		return $fields;
	}

	/**
	 * Returns the current values and effective access of AI-compatible settings.
	 *
	 * @return list<TAI_Setting> AI setting definitions and readable values.
	 *
	 * @since 9.0.0
	 */
	public function get_ai_settings() {
		$settings = array();

		foreach ( $this->get_ai_fields() as $field ) {
			$access = $this->get_ai_access( $field );
			$item   = array(
				'name'        => $field['name'],
				'label'       => $field['label'],
				'description' => $field['description'],
				'access'      => $access,
			);

			if ( isset( $field['schema'] ) ) {
				$item['schema'] = $field['schema'];
			}

			if ( in_array( $access, array( 'full', 'readonly' ), true ) ) {
				$item['value'] = $this->get( $field['name'] );
			}

			$settings[] = $item;
		}

		return $settings;
	}

	/**
	 * Updates a partial set of AI-compatible settings.
	 *
	 * @param array<string,mixed> $values Setting values keyed by setting name.
	 *
	 * @return array<string,mixed>|WP_Error Updated values or an error.
	 *
	 * @since 9.0.0
	 */
	public function update_ai_settings( $values ) {
		$fields = $this->get_ai_fields();
		foreach ( $values as $name => $value ) {
			if ( ! isset( $fields[ $name ] ) ) {
				return new WP_Error(
					'unknown-setting',
					sprintf( 'The supplied setting `%s` is not exposed to AI abilities.', $name )
				);
			}

			$field  = $fields[ $name ];
			$access = $this->get_ai_access( $field );
			if ( 'full' !== $access ) {
				return new WP_Error(
					'setting-not-writable',
					sprintf( 'The supplied setting `%s` cannot be written with AI abilities.', $name )
				);
			}

			if ( is_callable( $field['is_write_allowed'] ?? null ) ) {
				$validation = call_user_func( $field['is_write_allowed'] );
				if ( is_wp_error( $validation ) ) {
					return $validation;
				}
			}
		}

		$stored_option = get_option( $this->get_name(), array() );
		$option        = array();
		if ( is_array( $stored_option ) ) {
			foreach ( $stored_option as $name => $value ) {
				if ( is_string( $name ) ) {
					$option[ $name ] = $value;
				}
			}
		}
		$merged = $option;
		foreach ( $values as $name => $value ) {
			$merged[ $name ] = $value;
		}

		$sanitized = $merged;
		foreach ( $values as $name => $value ) {
			$field = $this->get_field( $name );
			if ( false === $field ) {
				// @codeCoverageIgnoreStart
				return new WP_Error(
					'unknown-setting',
					sprintf( 'The supplied setting `%s` is not exposed to AI abilities.', $name )
				);
				// @codeCoverageIgnoreEnd
			}
			$ui      = $field['ui']();
			$setting = $this->create_setting( $field, $ui );
			if ( ! $setting ) {
				// @codeCoverageIgnoreStart
				return new WP_Error(
					'invalid-setting',
					sprintf( 'The supplied setting `%s` cannot be updated.', $name )
				);
				// @codeCoverageIgnoreEnd
			}

			$sanitized       = $setting->sanitize( $sanitized );
			$has_value       = array_key_exists( $name, $sanitized );
			$sanitized_value = $has_value ? $sanitized[ $name ] : null;
			$is_equal        = $has_value && $sanitized_value === $merged[ $name ];
			if ( ! $is_equal && is_callable( $fields[ $name ]['is_write_valid'] ?? null ) ) {
				$is_equal = call_user_func( $fields[ $name ]['is_write_valid'], $merged[ $name ], $sanitized_value );
			}

			if ( ! $is_equal ) {
				$provided_value  = wp_json_encode( $merged[ $name ] );
				$sanitized_value = array_key_exists( $name, $sanitized ) ? wp_json_encode( $sanitized[ $name ] ) : 'null';
				$provided_value  = false === $provided_value ? 'unserializable value' : $provided_value;
				$sanitized_value = false === $sanitized_value ? 'unserializable value' : $sanitized_value;

				return new WP_Error(
					'invalid-setting-value',
					sprintf(
						'The supplied setting `%s` was changed during sanitization from `%s` to `%s`.',
						$name,
						$provided_value,
						$sanitized_value
					)
				);
			}
		}

		$updated = $option;
		foreach ( $values as $name => $value ) {
			if ( array_key_exists( $name, $sanitized ) ) {
				$updated[ $name ] = $sanitized[ $name ];
			} else {
				unset( $updated[ $name ] );
			}
		}

		update_option( $this->get_name(), $updated );
		$fields = $this->get_ai_settings();
		$fields = array_filter( $fields, fn( $f ) => array_key_exists( 'value', $f ) );
		$keys   = array_map( fn( $f ) => $f['name'], $fields );
		$values = array_map( fn( $f ) => $f['value'], $fields );
		return array_combine( $keys, $values );
	}

	/**
	 * Registers all settings in WordPress using the Settings API.
	 *
	 * @return void
	 */
	public function register() {

		foreach ( $this->tabs as $tab ) {
			$this->register_tab( $tab );
		}
	}

	/**
	 * Returns the "name" of the settings script (as used in `wp_register_script`).
	 *
	 * @return non-empty-string the "name" of the settings script (as used in `wp_register_script`).
	 */
	public function get_generic_script_name() {

		return $this->name . '-abstract-settings-js';
	}

	/**
	 * Enqueues all required scripts.
	 *
	 * @return void
	 */
	public function register_scripts() {

		wp_register_script(
			$this->get_generic_script_name(),
			nelioab()->plugin_url . '/assets/dist/js/settings.js',
			array(),
			nelioab()->plugin_version,
			true
		);
	}

	/**
	 * Opens a DIV tag for enclosing the contents of a tab.
	 *
	 * If the tab we're opening is the first one, we also print the actual tabs.
	 *
	 * @return void
	 */
	public function open_tab_content() {

		// Print the actual tabs (if there's more than one tab).
		if ( count( $this->tabs ) === 1 ) {

			$this->current_tab_name = $this->tabs[0]['name'];
			$this->opened_tab_name  = $this->tabs[0]['name'];

		} elseif ( count( $this->tabs ) > 1 && ! $this->current_tab_name ) {

			// Print tabs when rendering first tab only, or tabs will show up everywhere.
			$tabs       = array_map( fn( $t ) => array_merge( (array) $t, array( 'label' => $t['label']() ) ), $this->tabs );
			$opened_tab = $this->opened_tab_name;
			include untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/partials/nelio-ab-testing-tabs.php';
			$this->current_tab_name = $this->tabs[0]['name'];

		} else {

			$previous_name          = $this->current_tab_name;
			$this->current_tab_name = '';
			$num_of_tabs            = count( $this->tabs );

			for ( $i = 0; $i < $num_of_tabs - 1 && ! $this->current_tab_name; ++$i ) {
				if ( $this->tabs[ $i ]['name'] === $previous_name ) {
					$current_tab            = $this->tabs[ $i + 1 ];
					$this->current_tab_name = $current_tab['name'];
				}
			}
		}

		// And now group all the fields under.
		if ( $this->current_tab_name === $this->opened_tab_name ) {
			echo '<div id="' . esc_attr( $this->current_tab_name ) . '-tab-content" class="tab-content">';
		} else {
			echo '<div id="' . esc_attr( $this->current_tab_name ) . '-tab-content" class="tab-content" style="display:none;">';
		}
	}

	/**
	 * Closes a tab div.
	 *
	 * @return void
	 */
	public function close_tab_content() {
		echo '</div>';
	}

	/**
	 * Get the name of the option group.
	 *
	 * @return string the name of the settings.
	 */
	public function get_name() {
		return $this->name . '_settings';
	}

	/**
	 * Get the name of the option group.
	 *
	 * @return string the name of the option group.
	 */
	public function get_option_group() {
		return $this->name . '_group';
	}

	/**
	 * Get the name of the option group.
	 *
	 * @return string the name of the option group.
	 */
	public function get_settings_page_name() {
		return $this->name . '-settings-page';
	}

	/**
	 * This function sets the real tabs.
	 *
	 * @param list<TSettings_Tab> $tabs An array with the available tabs and the fields within each tab.
	 *
	 * @return void
	 */
	protected function do_set_tabs( $tabs ) {

		$this->tabs = $tabs;

		foreach ( $this->tabs as $index => $tab ) {

			if ( ! empty( $this->tabs[ $index ]['fields'] ) && ! empty( $tab['fields'] ) ) {

				$tab_name = $tab['name'];

				/**
				 * Filters the sections and fields of the given tab.
				 *
				 * @param list<TSettings_Field> $fields The fields (and sections) of the given tab in the settings screen.
				 *
				 * @since 5.0.0
				 */
				$this->tabs[ $index ]['fields'] = apply_filters( "nab_{$tab_name}_settings", $tab['fields'] ); // @phpstan-ignore-line parameter.phpDocType

			}
		}

		// Let's see which tab has to be enabled.
		$this->current_tab_name = '';
		$this->opened_tab_name  = $this->tabs[0]['name'];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['tab'] ) ) {
			foreach ( $this->tabs as $tab ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( $_GET['tab'] === $tab['name'] ) {
					$this->opened_tab_name = $tab['name'];
				}
			}
		}
	}

	/**
	 * Checks if the given setting is disabled or not.
	 *
	 * @param string $name The name of the field.
	 *
	 * @return bool whether the given setting is disabled or not.
	 */
	private function is_setting_disabled( $name ) {

		$field = $this->get_field( $name );
		if ( empty( $field ) ) {
			return false;
		}

		$config        = isset( $field['config'] ) ? $field['config'] : array();
		$required_plan = $field['config']['required-plan'] ?? false;
		$disabled      = ! empty( $required_plan ) && ! nab_is_subscribed_to( $required_plan );

		/**
		 * Whether the given setting is disabled or not.
		 *
		 * @param bool                   $disabled     whether this setting is disabled or not.
		 * @param TSettings_Field_Config $extra_config extra config options.
		 *
		 * @since 8.3.0
		 */
		$disabled = apply_filters( "nab_is_{$name}_setting_disabled", $disabled, $config );

		/**
		 * Whether the given setting is disabled or not.
		 *
		 * @param bool                   $disabled     whether this setting is disabled or not.
		 * @param string                 $name         name of the parameter.
		 * @param TSettings_Field_Config $extra_config extra config options.
		 *
		 * @since 5.0.0
		 */
		$disabled = apply_filters( 'nab_is_setting_disabled', $disabled, $name, $config );

		return $disabled;
	}

	/**
	 * Returns the effective access of an AI setting.
	 *
	 * @param array<string,mixed> $field AI setting definition.
	 *
	 * @return string Effective access state.
	 */
	private function get_ai_access( $field ) {
		$access = $field['access'] ?? 'protected';
		$access = is_string( $access ) ? $access : 'protected';
		if ( ! in_array( $access, array( 'full', 'readonly' ), true ) ) {
			return $access;
		}

		$name = $field['name'] ?? '';
		return is_string( $name ) && $this->is_setting_disabled( $name ) ? 'disabled' : $access;
	}

	/**
	 * Creates and initializes a setting object from a field definition.
	 *
	 * @param TSettings_Field     $field Field definition.
	 * @param array<string,mixed> $ui UI definition.
	 *
	 * @return Nelio_AB_Testing_Abstract_React_Setting|Nelio_AB_Testing_Checkbox_Setting|Nelio_AB_Testing_Input_Setting|Nelio_AB_Testing_Radio_Setting|Nelio_AB_Testing_Range_Setting|Nelio_AB_Testing_Select_Setting|Nelio_AB_Testing_Text_Area_Setting|false Setting object or false when the field type is unsupported.
	 */
	private function create_setting( $field, $ui ) {
		$name        = $field['name'];
		$desc        = is_string( $ui['desc'] ?? null ) ? $ui['desc'] : '';
		$desc        = '' === $desc && is_string( $field['desc'] ?? null ) ? $field['desc'] : $desc;
		$more        = is_string( $ui['more'] ?? null ) ? $ui['more'] : '';
		$more        = '' === $more && is_string( $field['more'] ?? null ) ? $field['more'] : $more;
		$placeholder = is_string( $field['placeholder'] ?? null ) ? $field['placeholder'] : '';

		switch ( $field['type'] ) {
			case 'textarea':
				$setting = new Nelio_AB_Testing_Text_Area_Setting(
					$name,
					$desc,
					$more,
					$placeholder
				);
				$value   = $this->get( $name );
				$default = is_string( $field['default'] ?? null ) ? $field['default'] : '';
				$setting->set_value( is_string( $value ) ? $value : $default );
				break;

			case 'email':
			case 'number':
			case 'password':
			case 'private_text':
			case 'text':
				$setting = new Nelio_AB_Testing_Input_Setting(
					$name,
					$desc,
					$more,
					$field['type'],
					$placeholder
				);
				$value   = $this->get( $name );
				$default = is_string( $field['default'] ?? null ) ? $field['default'] : '';
				$setting->set_value( is_string( $value ) ? $value : $default );
				break;

			case 'checkbox':
				$setting = new Nelio_AB_Testing_Checkbox_Setting( $name, $desc, $more );
				$value   = $this->get( $name );
				$default = ! empty( $field['default'] );
				$setting->set_value( is_bool( $value ) ? $value : $default );
				break;

			case 'range':
				$args    = is_array( $ui['args'] ?? null ) ? $ui['args'] : array();
				$args    = array(
					'label' => is_string( $args['label'] ?? null ) ? $args['label'] : '',
					'min'   => is_int( $args['min'] ?? null ) ? $args['min'] : 0,
					'max'   => is_int( $args['max'] ?? null ) ? $args['max'] : 0,
					'step'  => is_int( $args['step'] ?? null ) ? $args['step'] : 1,
				);
				$setting = new Nelio_AB_Testing_Range_Setting( $name, $desc, $more, $args );
				$value   = $this->get( $name );
				$default = is_int( $field['default'] ?? null ) ? $field['default'] : 0;
				$setting->set_value( is_int( $value ) ? $value : $default );
				break;

			case 'radio':
				$options = $this->get_setting_options( $ui );
				$setting = new Nelio_AB_Testing_Radio_Setting( $name, $desc, $more, $options );
				$value   = $this->get( $name );
				$default = is_string( $field['default'] ?? null ) ? $field['default'] : '';
				$setting->set_value( is_string( $value ) ? $value : $default );
				break;

			case 'select':
				$options = $this->get_setting_options( $ui, true );
				$setting = new Nelio_AB_Testing_Select_Setting( $name, $desc, $more, $options );
				$value   = $this->get( $name );
				$default = is_string( $field['default'] ?? null ) ? $field['default'] : '';
				$setting->set_value( is_string( $value ) ? $value : $default );
				break;

			case 'custom':
				$class = $ui['class'] ?? null;
				if ( ! is_string( $class ) || ! is_a( $class, Nelio_AB_Testing_Abstract_React_Setting::class, true ) ) {
					return false; // @codeCoverageIgnore
				}
				$setting = new $class( $name );
				$setting->set_value( $this->get( $name ) );
				$setting->set_desc( ! empty( $desc ) );
				break;

			default:
				return false; // @codeCoverageIgnore
		}

		return $setting;
	}

	/**
	 * Returns the options defined by a radio or select setting.
	 *
	 * @param array<string,mixed> $ui UI definition.
	 * @param bool                $allow_disabled Whether options may be disabled.
	 *
	 * @return list<array{value:string,label:string,desc?:string,disabled?:bool}> Setting options.
	 */
	private function get_setting_options( $ui, $allow_disabled = false ) {
		$options = array();
		$items   = is_array( $ui['options'] ?? null ) ? $ui['options'] : array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || ! is_string( $item['value'] ?? null ) || ! is_string( $item['label'] ?? null ) ) {
				continue;
			}

			$option = array(
				'value' => $item['value'],
				'label' => $item['label'],
			);
			if ( is_string( $item['desc'] ?? null ) ) {
				$option['desc'] = $item['desc'];
			}
			if ( $allow_disabled && is_bool( $item['disabled'] ?? null ) ) {
				$option['disabled'] = $item['disabled'];
			}
			$options[] = $option;
		}

		return $options;
	}

	/**
	 * Looks for the default value of $name (if any) and saves it in the default values array.
	 *
	 * @param string $name The name of the field whose default value we want to obtain.
	 *
	 * @return void
	 */
	private function maybe_set_default_value( $name ) {

		$field = $this->get_field( $name );
		if ( $field && isset( $field['default'] ) ) {
			$this->default_values[ $name ] = $field['default'];
		}
	}

	/**
	 * Returns the field with the given name.
	 *
	 * @param string $name field name.
	 *
	 * @return TSettings_Field|false the field with the given name or false if none was found.
	 */
	private function get_field( $name ) {

		foreach ( $this->tabs as $tab ) {
			$fields = $tab['fields'] ?? array();
			foreach ( $fields as $f ) {
				switch ( $f['type'] ) {
					case 'section':
						break;

					default:
						if ( $f['name'] === $name ) {
							return $f;
						}
				}
			}
		}

		return false;
	}

	/**
	 * Registers the given tab in the Settings page.
	 *
	 * @param TSettings_Tab $tab A list with all fields.
	 *
	 * @return void
	 */
	private function register_tab( $tab ) {

		// Create a default section (which will also be used for enclosing all
		// fields within the current tab).
		$section = 'nelio-ab-testing-' . $tab['name'] . '-tab-opening-section';
		add_settings_section(
			$section,
			'',
			array( $this, 'open_tab_content' ),
			$this->get_settings_page_name()
		);

		$fields = isset( $tab['fields'] ) ? $tab['fields'] : array();
		foreach ( $fields as $field ) {

			$defaults = array( 'ui' => fn() => array() );

			/** @var TSettings_Field|TSettings_Section $field */
			$field       = wp_parse_args( $field, $defaults );
			$ui          = $field['ui']();
			$label_field = array_merge( $field, $ui );
			unset( $label_field['ui'] );

			$setting = false;

			if ( 'section' === $field['type'] ) {
				$section = $field['name'];
				add_settings_section(
					$field['name'],
					$this->get_field_label( $label_field ),
					'__return_null',
					$this->get_settings_page_name()
				);
			} else {
				$setting = $this->create_setting( $field, $ui );
			}

			if ( ! empty( $setting ) ) {
				$setting->register(
					$this->get_field_label( $label_field ),
					$this->get_settings_page_name(),
					$section,
					$this->get_option_group(),
					$this->get_name()
				);
				$setting->set_as_disabled( $this->is_setting_disabled( $setting->get_name() ) );
			}
		}

		// Close tab.
		$section = 'nelio-ab-testing-' . $tab['name'] . '-tab-closing-section';
		add_settings_section(
			$section,
			'',
			array( $this, 'close_tab_content' ),
			$this->get_settings_page_name()
		);
	}

	/**
	 * Returns the label of the given field.
	 *
	 * @param array<string,mixed> $field Field.
	 *
	 * @return string
	 */
	private function get_field_label( $field ) {
		$label  = is_string( $field['label'] ?? null ) ? $field['label'] : '';
		$config = is_array( $field['config'] ?? null ) ? $field['config'] : array();
		$toggle = is_string( $config['visibility-toggle'] ?? null ) ? $config['visibility-toggle'] : '';
		if ( $toggle ) {
			$label = sprintf(
				'<span data-nab-visibility-toggle="%1$s">%2$s</span>',
				esc_attr( $toggle ),
				$label
			);
		}
		if ( isset( $field['icon'] ) ) {
			$icon = $field['icon'];
			if ( ! is_string( $icon ) ) {
				$icon = ''; // @codeCoverageIgnore
			}
			if ( 0 === strpos( $icon, 'dashicons-' ) ) {
				$label = sprintf(
					'<span class="dashicons %1$s"></span> %2$s',
					$icon,
					$label
				);
			} else {
				$label = "{$icon} {$label}";
			}
		}

		$required_plan = is_string( $config['required-plan'] ?? null ) ? $config['required-plan'] : false;
		if ( 'section' === $field['type'] ) {
			$this->is_section_already_disabled = ! empty( $required_plan ) && ! nab_is_subscribed_to( $required_plan );
		}

		if ( 'section' !== $field['type'] && $this->is_section_already_disabled ) {
			return $label;
		}

		if ( empty( $required_plan ) ) {
			return $label;
		}

		if ( ! nab_is_subscribed() ) {
			$plan_label = _x( 'premium', 'text', 'nelio-ab-testing' ); // @codeCoverageIgnore
		} elseif ( ! nab_is_subscribed_to( $required_plan ) ) {
			$plan_label = $required_plan;
		} else {
			$plan_label = '';
		}

		if ( empty( $plan_label ) ) {
			return $label;
		}

		return sprintf(
			'%1$s <span class="nab-premium-feature-wrapper" data-setting="%2$s" data-required-plan="%3$s"></span>',
			$label,
			esc_attr( 'setting:' . ( is_string( $field['name'] ?? null ) ? $field['name'] : '' ) ),
			esc_attr( $required_plan )
		);
	}
}
