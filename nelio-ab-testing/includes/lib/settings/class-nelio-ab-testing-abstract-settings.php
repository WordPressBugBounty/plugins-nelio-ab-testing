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
 *
 * @SuppressWarnings( PHPMD.CyclomaticComplexity )
 * @SuppressWarnings( PHPMD.ExcessiveClassComplexity )
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
	 * @var    array
	 */
	private $default_values;

	/**
	 * An array with the tabs
	 *
	 * Each item in this array looks like this:
	 *
	 * `
	 * array (
	 *    'name'   => a String that identifies the setting.
	 *    'label'  => the UI label of the tab.
	 *    'fields' => an array with all the fields contained in the tab.
	 * )
	 * `
	 *
	 * or this:
	 *
	 * `
	 * array (
	 *    'name'    => a String that identifies the setting.
	 *    'label'   => the UI label of the tab.
	 *    'partial' => the UI partial of this tab.
	 * )
	 * `
	 *
	 * @since  5.0.0
	 * @var    array
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
	private $current_tab_name = false;

	/**
	 * The name of the tab that's currently visible.
	 *
	 * This variable depends on the value of `$_GET['tab']`.
	 *
	 * @since  5.0.0
	 * @var    string
	 */
	private $opened_tab_name = false;

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
	}//end __construct()

	/**
	 * Add proper hooks.
	 *
	 * @since  5.0.0
	 */
	public function init() {

		add_action( 'plugins_loaded', array( $this, 'set_tabs' ), 1 );

		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
	}//end init()

	/**
	 * This function has to be implemented by the subclass and specifies which tabs
	 * are defined in the settings page.
	 *
	 * See `do_set_tabs`.
	 *
	 * @since  5.0.0
	 */
	abstract public function set_tabs();

	/**
	 * This function sets the real tabs.
	 *
	 * @param array $tabs An array with the available tabs and the fields within each tab.
	 *
	 * @since  5.0.0
	 */
	protected function do_set_tabs( $tabs ) {

		$this->tabs = $tabs;

		foreach ( $this->tabs as $key => $tab ) {

			if ( ! isset( $this->tabs[ $key ]['fields'] ) ) {
				$this->tabs[ $key ]['fields'] = array();
			}//end if

			if ( count( $this->tabs[ $key ]['fields'] ) > 0 ) {

				$tab_name = $tab['name'];

				/**
				 * Filters the sections and fields of the given tab.
				 *
				 * @param array $fields The fields (and sections) of the given tab in the settings screen.
				 *
				 * @since 5.0.0
				 */
				$this->tabs[ $key ]['fields'] = apply_filters( "nab_{$tab_name}_settings", $tab['fields'] );

			}//end if
		}//end foreach

		// Let's see which tab has to be enabled.
		$this->opened_tab_name = $this->tabs[0]['name'];
		if ( isset( $_GET['tab'] ) ) { // phpcs:ignore
			foreach ( $this->tabs as $tab ) {
				if ( $_GET['tab'] === $tab['name'] ) { // phpcs:ignore
					$this->opened_tab_name = $tab['name'];
				}//end if
			}//end foreach
		}//end if
	}//end do_set_tabs()

	/**
	 * Returns the value of the given setting.
	 *
	 * @param string $name  The name of the parameter whose value we want to obtain.
	 * @param object $value Optional. Default value if the setting is not found and
	 *                      the setting didn't define a default value already.
	 *                      Default: `false`.
	 *
	 * @return object The concrete value of the specified parameter.
	 *                If the setting has never been saved and it registered no
	 *                default value (during the construction of `Nelio_AB_Testing_Settings`),
	 *                then the parameter `$value` will be returned instead.
	 *
	 * @since  5.0.0
	 *
	 * @throws Exception If settings are called before `plugins_loaded`.
	 */
	public function get( $name, $value = false ) {

		if ( ! doing_action( 'plugins_loaded' ) && ! did_action( 'plugins_loaded' ) ) {
			throw new Exception( esc_html_x( 'Nelio A/B Testing settings should be used after plugins_loaded.', 'error', 'nelio-ab-testing' ) );
		}//end if

		if ( ! $this->is_setting_disabled( $name ) ) {

			$settings = get_option( $this->get_name(), array() );
			if ( isset( $settings[ $name ] ) ) {
				return $settings[ $name ];
			}//end if
		}//end if

		$this->maybe_set_default_value( $name );
		if ( isset( $this->default_values[ $name ] ) ) {
			return $this->default_values[ $name ];
		} else {
			return $value;
		}//end if
	}//end get()

	/**
	 * Checks if the given setting is disabled or not.
	 *
	 * @param string $name The name of the field.
	 *
	 * @returns boolean whether the given setting is disabled or not.
	 *
	 * @since  5.0.0
	 */
	public function is_setting_disabled( $name ) {

		$field = $this->get_field( $name );
		if ( empty( $field ) ) {
			return false;
		}//end if

		$config = isset( $field['config'] ) ? $field['config'] : array();

		/**
		 * Whether the given setting is disabled or not.
		 *
		 * @param boolean $disabled     whether this setting is disabled or not. Default: `false`.
		 * @param string  $name         name of the parameter.
		 * @param array   $extra_config extra config options.
		 *
		 * @since 5.0.0
		 */
		return apply_filters( 'nab_is_setting_disabled', false, $name, $config );
	}//end is_setting_disabled()

	/**
	 * Looks for the default value of $name (if any) and saves it in the default values array.
	 *
	 * @param string $name The name of the field whose default value we want to obtain.
	 *
	 * @since  5.0.0
	 */
	private function maybe_set_default_value( $name ) {

		$field = $this->get_field( $name );
		if ( $field && isset( $field['default'] ) ) {
			$this->default_values[ $name ] = $field['default'];
		}//end if
	}//end maybe_set_default_value()

	/**
	 * Returns the field with the given name.
	 *
	 * @param string $name field name.
	 *
	 * @return array|boolean the field with the given name or false if none was found.
	 *
	 * @since  5.0.0
	 */
	private function get_field( $name ) {

		foreach ( $this->tabs as $tab ) {
			foreach ( $tab['fields'] as $f ) {
				switch ( $f['type'] ) {
					case 'section':
						break;

					case 'custom':
						if ( $f['name'] === $name ) {
							return $f;
						}//end if
						break;

					case 'checkboxes':
						foreach ( $f['options'] as $option ) {
							if ( $option['name'] === $name ) {
								return $f;
							}//end if
						}//end foreach
						break;

					default:
						if ( $f['name'] === $name ) {
							return $f;
						}//end if
				}//end switch
			}//end foreach
		}//end foreach

		return false;
	}//end get_field()

	/**
	 * Registers all settings in WordPress using the Settings API.
	 *
	 * @since  5.0.0
	 */
	public function register() {

		foreach ( $this->tabs as $tab ) {
			$this->register_tab( $tab );
		}//end foreach
	}//end register()

	/**
	 * Returns the "name" of the settings script (as used in `wp_register_script`).
	 *
	 * @return string the "name" of the settings script (as used in `wp_register_script`).
	 *
	 * @since  5.0.0
	 */
	public function get_generic_script_name() {

		return $this->name . '-abstract-settings-js';
	}//end get_generic_script_name()

	/**
	 * Enqueues all required scripts.
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
	}//end register_scripts()

	/**
	 * Registers the given tab in the Settings page.
	 *
	 * @param array $tab A list with all fields.
	 *
	 * @since  5.0.0
	 *
	 * @SuppressWarnings( PHPMD.ExcessiveMethodLength )
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
		}//end if

		foreach ( $tab['fields'] as $field ) {

			$defaults = array(
				'desc' => '',
				'more' => '',
				'ui'   => fn() => array(),
			);

			$field = wp_parse_args( $field, $defaults );
			$field = array_merge( $field, $field['ui']() );

			$setting = false;

			switch ( $field['type'] ) {

				case 'section':
					$section = $field['name'];
					add_settings_section(
						$field['name'],
						$field['label'],
						'',
						$this->get_settings_page_name()
					);
					break;

				case 'textarea':
					$field = wp_parse_args( $field, array( 'placeholder' => '' ) );

					$setting = new Nelio_AB_Testing_Text_Area_Setting(
						$field['name'],
						$field['desc'],
						$field['more'],
						$field['placeholder']
					);

					$value = $this->get( $field['name'] );
					$setting->set_value( $value );

					$setting->register(
						$field['label'],
						$this->get_settings_page_name(),
						$section,
						$this->get_option_group(),
						$this->get_name()
					);
					break;

				case 'email':
				case 'number':
				case 'password':
				case 'text':
					$field = wp_parse_args( $field, array( 'placeholder' => '' ) );

					$setting = new Nelio_AB_Testing_Input_Setting(
						$field['name'],
						$field['desc'],
						$field['more'],
						$field['type'],
						$field['placeholder']
					);

					$value = $this->get( $field['name'] );
					$setting->set_value( $value );

					$setting->register(
						$field['label'],
						$this->get_settings_page_name(),
						$section,
						$this->get_option_group(),
						$this->get_name()
					);
					break;

				case 'checkbox':
					$setting = new Nelio_AB_Testing_Checkbox_Setting(
						$field['name'],
						$field['desc'],
						$field['more']
					);

					$value = $this->get( $field['name'] );
					$setting->set_value( $value );

					$setting->register(
						$field['label'],
						$this->get_settings_page_name(),
						$section,
						$this->get_option_group(),
						$this->get_name()
					);
					break;

				case 'checkboxes':
					$setting = new Nelio_AB_Testing_Checkbox_Set_Setting( $field['options'] );

					foreach ( $field['options'] as $cb ) {
						$tuple = array(
							'name'  => $cb['name'],
							'value' => $value,
						);
						$setting->set_value( $tuple );
					}//end foreach

					$setting->register(
						$field['label'],
						$this->get_settings_page_name(),
						$section,
						$this->get_option_group(),
						$this->get_name()
					);
					break;

				case 'range':
					$setting = new Nelio_AB_Testing_Range_Setting(
						$field['name'],
						$field['desc'],
						$field['more'],
						$field['args']
					);

					$value = $this->get( $field['name'] );
					$setting->set_value( $value );

					$setting->register(
						$field['label'],
						$this->get_settings_page_name(),
						$section,
						$this->get_option_group(),
						$this->get_name()
					);
					break;

				case 'radio':
					$setting = new Nelio_AB_Testing_Radio_Setting(
						$field['name'],
						$field['desc'],
						$field['more'],
						$field['options']
					);

					$value = $this->get( $field['name'] );
					$setting->set_value( $value );

					$setting->register(
						$field['label'],
						$this->get_settings_page_name(),
						$section,
						$this->get_option_group(),
						$this->get_name()
					);
					break;

				case 'select':
					$setting = new Nelio_AB_Testing_Select_Setting(
						$field['name'],
						$field['desc'],
						$field['more'],
						$field['options']
					);

					$value = $this->get( $field['name'] );
					$setting->set_value( $value );

					$setting->register(
						$field['label'],
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
					$setting->set_desc( $field['desc'] );

					$setting->register(
						$field['label'],
						$this->get_settings_page_name(),
						$section,
						$this->get_option_group(),
						$this->get_name()
					);
					break;

			}//end switch

			if ( ! empty( $setting ) && $this->is_setting_disabled( $setting->get_name() ) ) {
				$setting->set_as_disabled( true );
			}//end if
		}//end foreach

		// Close tab.
		$section = 'nelio-ab-testing-' . $tab['name'] . '-closing-section';
		add_settings_section(
			$section,
			'',
			array( $this, 'close_tab_content' ),
			$this->get_settings_page_name()
		);
	}//end register_tab()

	/**
	 * Opens a DIV tag for enclosing the contents of a tab.
	 *
	 * If the tab we're opening is the first one, we also print the actual tabs.
	 *
	 * @since  5.0.0
	 *
	 * @SuppressWarnings( PHPMD.UnusedLocalVariable )
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
			$this->current_tab_name = false;
			$num_of_tabs            = count( $this->tabs );

			for ( $i = 0; $i < $num_of_tabs - 1 && ! $this->current_tab_name; ++$i ) {
				if ( $this->tabs[ $i ]['name'] === $previous_name ) {
					$current_tab            = $this->tabs[ $i + 1 ];
					$this->current_tab_name = $current_tab['name'];
				}//end if
			}//end for
		}//end if

		// And now group all the fields under.
		if ( $this->current_tab_name === $this->opened_tab_name ) {
			echo '<div id="' . esc_attr( $this->current_tab_name ) . '-tab-content" class="tab-content">';
		} else {
			echo '<div id="' . esc_attr( $this->current_tab_name ) . '-tab-content" class="tab-content" style="display:none;">';
		}//end if
	}//end open_tab_content()

	/**
	 * Prints the contents of a tab that uses the `partial` option.
	 *
	 * @param array $args the ID, title, and callback info of this section.
	 *
	 * @since  5.0.0
	 */
	public function print_tab_content( $args ) {

		$name = $args['id'];
		$name = preg_replace( '/^nelio-ab-testing-/', '', $name );
		$name = preg_replace( '/-tab-content$/', '', $name );

		foreach ( $this->tabs as $tab ) {
			if ( $tab['name'] === $name && isset( $tab['partial'] ) ) {
				// phpcs:ignore
				include $tab['partial'];
			}//end if
		}//end foreach
	}//end print_tab_content()

	/**
	 * Closes a tab div.
	 *
	 * @since  5.0.0
	 */
	public function close_tab_content() {

		echo '</div>';
	}//end close_tab_content()

	/**
	 * Get the name of the option group.
	 *
	 * @return string the name of the settings.
	 *
	 * @since  5.0.0
	 */
	public function get_name() {
		return $this->name . '_settings';
	}//end get_name()

	/**
	 * Get the name of the option group.
	 *
	 * @return string the name of the option group.
	 *
	 * @since  5.0.0
	 */
	public function get_option_group() {
		return $this->name . '_group';
	}//end get_option_group()

	/**
	 * Get the name of the option group.
	 *
	 * @return string the name of the option group.
	 *
	 * @since  5.0.0
	 */
	public function get_settings_page_name() {
		return $this->name . '-settings-page';
	}//end get_settings_page_name()
}//end class
