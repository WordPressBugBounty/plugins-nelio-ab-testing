<?php
/**
 * The file that includes installation-related functions and actions.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @since      6.0.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class configures WordPress and installs some capabilities.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @since      6.0.1
 */
class Nelio_AB_Testing_Capability_Manager {

	/**
	 * The single instance of this class.
	 *
	 * @since  6.0.1
	 * @var    Nelio_AB_Testing_Capability_Manager
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Capability_Manager the single instance of this class.
	 *
	 * @since  6.0.1
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	public function init() {
		$main_file = nelioab()->plugin_path . '/nelio-ab-testing.php';
		register_activation_hook( $main_file, array( $this, 'add_capabilities' ) );
		register_deactivation_hook( $main_file, array( $this, 'remove_capabilities' ) );
		add_action( 'nab_updated', array( $this, 'maybe_add_capabilities_on_update' ), 10, 2 );

		add_filter( 'ure_capabilities_groups_tree', array( $this, 'add_ure_group' ) );
		add_filter( 'ure_custom_capability_groups', array( $this, 'add_nab_capabilities_to_ure_group' ), 10, 2 );
	}//end init()

	/**
	 * Adds custom Nelio A/B Testing’s capabilities from admin admin and editor roles.
	 *
	 * @since 6.0.1
	 */
	public function add_capabilities() {
		$roles = array( 'administrator', 'editor' );
		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$caps = $this->get_role_capabilities( $role_name );
				foreach ( $caps as $cap ) {
					$role->add_cap( $cap );
				}//end foreach
			}//end if
		}//end foreach
	}//end add_capabilities()

	/**
	 * Removes custom Nelio A/B Testing’s capabilities from admin admin and editor roles.
	 *
	 * @since 6.0.1
	 */
	public function remove_capabilities() {
		$roles = array( 'administrator', 'editor' );
		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$caps = $this->get_role_capabilities( $role_name );
				foreach ( $caps as $cap ) {
					$role->remove_cap( $cap );
				}//end foreach
			}//end if
		}//end foreach
	}//end remove_capabilities()

	/**
	 * Checks if we’re updating from a version prior to 6.0.1 and, if so, it adds the required capabilities.
	 *
	 * @param string $_            this version.
	 * @param string $prev_version previous version.
	 *
	 * @since 6.0.1
	 */
	public function maybe_add_capabilities_on_update( $_, $prev_version ) {
		if ( version_compare( $prev_version, '6.0.1', '<' ) ) {
			$this->remove_legacy_capabilities();
			$this->add_capabilities();
		}//end if
	}//end maybe_add_capabilities_on_update()

	/**
	 * Returns all the custom capabilities defined by Nelio A/B Testing.
	 *
	 * @return array list of capabilities
	 *
	 * @since 6.0.1
	 */
	public function get_all_capabilities() {
		return $this->get_role_capabilities( 'administrator' );
	}//end get_all_capabilities()

	/**
	 * Adds Nelio A/B Testing group in User Role Editor plugin.
	 *
	 * @param array $groups List of groups.
	 *
	 * @return array List of groups with Nelio A/B Testing group.
	 *
	 * @since 6.0.1
	 */
	public function add_ure_group( $groups ) {
		$groups['nelio_ab_testing'] = array(
			'caption' => 'Nelio A/B Testing',
			'parent'  => 'custom',
			'level'   => 2,
		);
		return $groups;
	}//end add_ure_group()

	/**
	 * Adds Nelio A/B Testing capabilities in our own group in User Role Editor plugin.
	 *
	 * @param array  $groups      List of groups.
	 * @param string $capability Capability ID.
	 *
	 * @return array List of groups where the given capability belongs to.
	 *
	 * @since 6.0.1
	 */
	public function add_nab_capabilities_to_ure_group( $groups, $capability ) {
		if ( false !== strpos( $capability, '_nab_' ) ) {
			$groups[] = 'nelio_ab_testing';
		}//end if
		return $groups;
	}//end add_nab_capabilities_to_ure_group()

	private function remove_legacy_capabilities() {
		$roles = get_option( 'wp_user_roles' );
		$roles = is_array( $roles ) ? array_keys( $roles ) : array();
		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$caps = array_keys( $role->capabilities );
				foreach ( $caps as $cap ) {
					if ( 0 < strpos( $cap, 'nab_experiment' ) ) {
						$role->remove_cap( $cap );
					}//end if
				}//end foreach
			}//end if
		}//end foreach
	}//end remove_legacy_capabilities()

	private function get_role_capabilities( $role ) {
		$editor_caps = array(
			// Basic test management.
			'edit_nab_experiments',
			'delete_nab_experiments',

			// Manage experiment status.
			'start_nab_experiments',
			'stop_nab_experiments',
			'pause_nab_experiments',
			'resume_nab_experiments',

			// View results.
			'read_nab_results',

			// Manage settings.
			'manage_nab_options',
		);

		$admin_caps = array_merge(
			$editor_caps,
			array( 'manage_nab_account' )
		);

		$caps = array(
			'administrator' => $admin_caps,
			'editor'        => $editor_caps,
		);

		return isset( $caps[ $role ] ) ? $caps[ $role ] : array();
	}//end get_role_capabilities()
}//end class
