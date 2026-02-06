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
	 * @var    Nelio_AB_Testing_Capability_Manager|null
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

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		$main_file = nelioab()->plugin_path . '/nelio-ab-testing.php';
		register_activation_hook( $main_file, array( $this, 'add_capabilities' ) );
		register_deactivation_hook( $main_file, array( $this, 'remove_capabilities' ) );
		add_action( 'nab_updated', array( $this, 'add_capabilities' ) );
	}

	/**
	 * Adds custom Nelio A/B Testing’s capabilities from admin admin and editor roles.
	 *
	 * @return void
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
				}
			}
		}

		if ( is_multisite() ) {
			$caps         = $this->get_role_capabilities( 'administrator' );
			$super_admins = get_super_admins();
			foreach ( $super_admins as $username ) {
				$user = get_user_by( 'login', $username );
				if ( $user ) {
					foreach ( $caps as $cap ) {
						$user->add_cap( $cap );
					}
				}
			}
		}
	}

	/**
	 * Removes custom Nelio A/B Testing’s capabilities from admin admin and editor roles.
	 *
	 * @return void
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
				}
			}
		}

		if ( is_multisite() ) {
			$caps         = $this->get_role_capabilities( 'administrator' );
			$super_admins = get_super_admins();
			foreach ( $super_admins as $username ) {
				$user = get_user_by( 'login', $username );
				if ( $user ) {
					foreach ( $caps as $cap ) {
						$user->remove_cap( $cap );
					}
				}
			}
		}
	}

	/**
	 * Returns all the custom capabilities defined by Nelio A/B Testing.
	 *
	 * @return list<string> list of capabilities
	 *
	 * @since 6.0.1
	 */
	public function get_all_capabilities() {
		return $this->get_role_capabilities( 'administrator' );
	}

	/**
	 * Returns nab capabilities associated to a given role.
	 *
	 * @param string $role A role.
	 *
	 * @return list<string>
	 */
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
			array(
				'manage_nab_account',
				'edit_nab_php_experiments',
			)
		);

		$caps = array(
			'administrator' => $admin_caps,
			'editor'        => $editor_caps,
		);

		return isset( $caps[ $role ] ) ? $caps[ $role ] : array();
	}
}
