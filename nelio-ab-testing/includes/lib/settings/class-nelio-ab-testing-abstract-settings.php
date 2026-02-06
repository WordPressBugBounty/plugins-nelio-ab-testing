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
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings
 * @since      5.0.0
 */
abstract class Nelio_AB_Testing_Abstract_Settings {

	/**
	 * The name that identifies Nelio A/B Testing's Settings
	 *
	 * @since  5.0.0
	 * @var    string
	 */
	private $name;

	/**
	 * An array of settings that have been requested and where not found in the associated get_option entry.
	 *
	 * @since  5.0.0
	 * @var    array<string,mixed>
	 */
	private $default_values;

	/**
	 * An array with the tabs
	 *
	 * @since  5.0.0
	 * @var    list<TSettings_Tab>
	 */
	private $tabs;

	/**
	 * The name of the tab we're about to print.
	 *
	 * This is an aux var for enclosing all fields within a tab.
	 *
	 * @since  5.0.0
	 * @var    string
	 */
	private $current_tab_name = '';

	/**
	 * The name of the tab that's currently visible.
	 *
	 * This variable depends on the value of `$_GET['tab']`.
	 *
	 * @since  5.0.0
	 * @var    string
	 */
	private $opened_tab_name = '';

	/**
	 * Whether the section whose fields we’re rendering is already disabled.
	 *
	 * @since  8.0.0
	 * @var    boolean
	 */
	private $is_section_already_disabled = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $name The name of this options group.
	 *
	 * @since  5.0.0
	 */
	protected function __construct( $name ) {

		$this->default_values = array();
		$this->tabs           = array();
		$this->name           = $name;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 *
	 * @since  5.0.0
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
	 *
	 * @since  5.0.0
	 */
	abstract public function set_tabs();

	/**
	 * This function sets the real tabs.
	 *
	 * @param list<TSettings_Tab> $tabs An array with the available tabs and the fields within each tab.
	 *
	 * @return void
	 *
	 * @since  5.0.0
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
		$this->opened_tab_name = $this->tabs[0]['name'];
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
	 * Returns the value of the given setting.
	 *
	 * @param string $name  The name of the parameter whose value we want to obtain.
	 *
	 * @return mixed  The concrete value of the specified parameter.
	 *                If the setting has never been saved and it registered no
	 *                default value (during the construction of `Nelio_AB_Testing_Settings`),
	 *                then the parameter `$value` will be returned instead.
	 *
	 * @since  5.0.0
	 *
	 * @throws Exception If settings are called before `plugins_loaded`.
	 */
	public function get( $name ) {

		if ( ! doing_action( 'plugins_loaded' ) && ! did_action( 'plugins_loaded' ) ) {
			throw new Exception( esc_html_x( 'Nelio A/B Testing settings should be used after plugins_loaded.', 'error', 'nelio-ab-testing' ) );
		}

		if ( ! $this->is_setting_disabled( $name ) ) {

			/** @var array<string,mixed> */
			$settings = get_option( $this->get_name(), array() );
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
	 * Checks if the given setting is disabled or not.
	 *
	 * @param string $name The name of the field.
	 *
	 * @return bool whether the given setting is disabled or not.
	 *
	 * @since  5.0.0
	 */
	public function is_setting_disabled( $name ) {

		$field = $this->get_field( $name );
		if ( empty( $field ) ) {
			return false;
		}

		$config = isset( $field['config'] ) ? $field['config'] : array();

		/**
		 * Whether the given setting is disabled or not.
		 *
		 * @param bool                   $disabled     whether this setting is disabled or not. Default: `false`.
		 * @param string                 $name         name of the parameter.
		 * @param TSettings_Field_Config $extra_config extra config options.
		 *
		 * @since 5.0.0
		 */
		return apply_filters( 'nab_is_setting_disabled', false, $name, $config );
	}

	/**
	 * Looks for the default value of $name (if any) and saves it in the default values array.
	 *
	 * @param string $name The name of the field whose default value we want to obtain.
	 *
	 * @return void
	 *
	 * @since  5.0.0
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
	 *
	 * @since  5.0.0
	 */
	private function get_field( $name ) {

		foreach ( $this->tabs as $tab ) {
			if ( empty( $tab['fields'] ) ) {
				continue;
			}

			foreach ( $tab['fields'] as $f ) {
				switch ( $f['type'] ) {
					case 'section':
						break;

					case 'custom':
						if ( $f['name'] === $name ) {
							return $f;
						}
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
	 * Registers all settings in WordPress using the Settings API.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function register() {

		foreach ( $this->tabs as $tab ) {
			$this->register_tab( $tab );
		}
	}

	/**
	 * Returns the "name" of the settings script (as used in `wp_register_script`).
	 *
	 * @return string the "name" of the settings script (as used in `wp_register_script`).
	 *
	 * @since  5.0.0
	 */
	public function get_generic_script_name() {

		return $this->name . '-abstract-settings-js';
	}

	/**
	 * Enqueues all required scripts.
	 *
	 * @return void
	 *
	 * @since  5.0.0
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
	 * Registers the given tab in the Settings page.
	 *
	 * @param TSettings_Tab $tab A list with all fields.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	private function register_tab( $tab ) {

		// Create a default section (which will also be used for enclosing all
		// fields within the current tab).
		$section = 'nelio-ab-testing-' . $tab['name'] . '-opening-section';
		add_settings_section(
			$section,
			'',
			array( $this, 'open_tab_content' ),
			$this->get_settings_page_name()
		);

		if ( isset( $tab['partial'] ) ) {
			$section = 'nelio-ab-testing-' . $tab['name'] . '-tab-content';
			add_settings_section(
				$section,
				'',
				array( $this, 'print_tab_content' ),
				$this->get_settings_page_name()
			);
		}

		$fields = isset( $tab['fields'] ) ? $tab['fields'] : array();
		foreach ( $fields as $field ) {

			$defaults = array( 'ui' => fn() => array() );

			/** @var TSettings_Field */
			$field = wp_parse_args( $field, $defaults );
			$field = array_merge( $field, $field['ui']() );
			unset( $field['ui'] );

			$setting = false;

			/** @var TSettings_Field|TSettings_Section $field */
			switch ( $field['type'] ) {

				case 'section':
					$section = $field['name'];
					add_settings_section(
						$field['name'],
						$this->get_field_label( $field ),
						'__return_null',
						$this->get_settings_page_name()
					);
					break;

				case 'textarea':
					$setting = new Nelio_AB_Testing_Text_Area_Setting(
						$field['name'],
						$field['desc'] ?? '',
						$field['more'] ?? '',
						$field['placeholder'] ?? ''
					);

					$value = $this->get( $field['name'] );
					$value = is_string( $value ) ? $value : ( $field['default'] ?? '' );
					$setting->set_value( $value );

					$setting->register(
						$this->get_field_label( $field ),
						$this->get_settings_page_name(),
						$section,
						$this->get_option_group(),
						$this->get_name()
					);
					break;

				case 'email':
				case 'number':
				case 'password':
				case 'private_text':
				case 'text':
					$setting = new Nelio_AB_Testing_Input_Setting(
						$field['name'],
						$field['desc'] ?? '',
						$field['more'] ?? '',
						$field['type'],
						$field['placeholder'] ?? ''
					);

					$value = $this->get( $field['name'] );
					$value = is_string( $value ) ? $value : ( $field['default'] ?? '' );
					$setting->set_value( $value );

					$setting->register(
						$this->get_field_label( $field ),
						$this->get_settings_page_name(),
						$section,
						$this->get_option_group(),
						$this->get_name()
					);
					break;

				case 'checkbox':
					$setting = new Nelio_AB_Testing_Checkbox_Setting(
						$field['name'],
						$field['desc'] ?? '',
						$field['more'] ?? ''
					);

					$value = $this->get( $field['name'] );
					$value = is_bool( $value ) ? $value : ! empty( $field['default'] );
					$setting->set_value( $value );

					$setting->register(
						$this->get_field_label( $field ),
						$this->get_settings_page_name(),
						$section,
						$this->get_option_group(),
						$this->get_name()
					);
					break;

				case 'range':
					$setting = new Nelio_AB_Testing_Range_Setting(
						$field['name'],
						$field['desc'] ?? '',
						$field['more'] ?? '',
						$field['args']
					);

					$value = $this->get( $field['name'] );
					$value = is_int( $value ) ? $value : ( $field['default'] ?? 0 );
					$setting->set_value( $value );

					$setting->register(
						$this->get_field_label( $field ),
						$this->get_settings_page_name(),
						$section,
						$this->get_option_group(),
						$this->get_name()
					);
					break;

				case 'radio':
					$setting = new Nelio_AB_Testing_Radio_Setting(
						$field['name'],
						$field['desc'] ?? '',
						$field['more'] ?? '',
						$field['options']
					);

					$value = $this->get( $field['name'] );
					$value = is_string( $value ) ? $value : ( $field['default'] ?? '' );
					$setting->set_value( $value );

					$setting->register(
						$this->get_field_label( $field ),
						$this->get_settings_page_name(),
						$section,
						$this->get_option_group(),
						$this->get_name()
					);
					break;

				case 'select':
					$setting = new Nelio_AB_Testing_Select_Setting(
						$field['name'],
						$field['desc'] ?? '',
						$field['more'] ?? '',
						$field['options']
					);

					$value = $this->get( $field['name'] );
					$value = is_string( $value ) ? $value : ( $field['default'] ?? '' );
					$setting->set_value( $value );

					$setting->register(
						$this->get_field_label( $field ),
						$this->get_settings_page_name(),
						$section,
						$this->get_option_group(),
						$this->get_name()
					);
					break;

				case 'custom':
					$setting = $field['instance'];

					$value = $this->get( $setting->get_name() );
					$setting->set_value( $value );
					$setting->set_desc( ! empty( $field['desc'] ) );

					$required_plan = $field['config']['required-plan'] ?? false;
					$enabled       = empty( $required_plan ) || nab_is_subscribed_to( $required_plan );
					$setting->mark_as_disabled( ! $enabled );

					$setting->register(
						$this->get_field_label( $field ),
						$this->get_settings_page_name(),
						$section,
						$this->get_option_group(),
						$this->get_name()
					);
					break;

			}

			if ( ! empty( $setting ) && $this->is_setting_disabled( $setting->get_name() ) ) {
				$setting->set_as_disabled( true );
			}
		}

		// Close tab.
		$section = 'nelio-ab-testing-' . $tab['name'] . '-closing-section';
		add_settings_section(
			$section,
			'',
			array( $this, 'close_tab_content' ),
			$this->get_settings_page_name()
		);
	}

	/**
	 * Opens a DIV tag for enclosing the contents of a tab.
	 *
	 * If the tab we're opening is the first one, we also print the actual tabs.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function open_tab_content() {

		// Print the actual tabs (if there's more than one tab).
		if ( count( $this->tabs ) === 1 ) {

			$this->current_tab_name = $this->tabs[0]['name'];
			$this->opened_tab_name  = $this->tabs[0]['name'];

		} elseif ( count( $this->tabs ) > 1 && ! $this->current_tab_name ) {

			$tabs       = array_map( fn( $t ) => array_merge( $t, array( 'label' => $t['label']() ) ), $this->tabs );
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
	 * Prints the contents of a tab that uses the `partial` option.
	 *
	 * @param array{id:string} $args the ID, title, and callback info of this section.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function print_tab_content( $args ) {

		$name = $args['id'];
		$name = preg_replace( '/^nelio-ab-testing-/', '', $name );
		$name = is_string( $name ) ? $name : '';
		$name = preg_replace( '/-tab-content$/', '', $name );
		$name = is_string( $name ) ? $name : '';

		foreach ( $this->tabs as $tab ) {
			if ( $tab['name'] === $name && isset( $tab['partial'] ) ) {
				include $tab['partial'];
			}
		}
	}

	/**
	 * Closes a tab div.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function close_tab_content() {

		echo '</div>';
	}

	/**
	 * Get the name of the option group.
	 *
	 * @return string the name of the settings.
	 *
	 * @since  5.0.0
	 */
	public function get_name() {
		return $this->name . '_settings';
	}

	/**
	 * Get the name of the option group.
	 *
	 * @return string the name of the option group.
	 *
	 * @since  5.0.0
	 */
	public function get_option_group() {
		return $this->name . '_group';
	}

	/**
	 * Get the name of the option group.
	 *
	 * @return string the name of the option group.
	 *
	 * @since  5.0.0
	 */
	public function get_settings_page_name() {
		return $this->name . '-settings-page';
	}

	/**
	 * Returns the label of the given field.
	 *
	 * @param TSettings_Field|TSettings_Section $field Field.
	 *
	 * @return string
	 */
	private function get_field_label( $field ) {
		$label  = $field['label'];
		$toggle = $field['config']['visibility-toggle'] ?? '';
		if ( $toggle ) {
			$label = sprintf(
				'<span data-nab-visibility-toggle="%1$s">%2$s</span>',
				esc_attr( $toggle ),
				$field['label']
			);
		}
		if ( isset( $field['icon'] ) ) {
			$icon = $field['icon'];
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

		$required_plan = $field['config']['required-plan'] ?? false;
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
			$plan_label = 'premium';
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
			esc_attr( "setting:{$field['name']}" ),
			esc_attr( $required_plan )
		);
	}
}
