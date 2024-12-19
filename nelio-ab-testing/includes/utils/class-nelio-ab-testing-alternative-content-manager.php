<?php
/**
 * This class contains several methods to manage alternative content properly.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class contains several methods to manage alternative content properly.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @since      5.0.0
 */
class Nelio_AB_Testing_Alternative_Content_Manager {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @var    Nelio_AB_Testing_Alternative_Content_Manager
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Alternative_Content_Manager the single instance of this class.
	 *
	 * @since  5.0.0
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	/**
	 * Hooks into WordPress.
	 *
	 * @since  5.0.0
	 */
	public function init() {

		add_action( 'init', array( $this, 'register_hidden_post_status_for_alternative_content' ), 9 );
		add_filter( 'wp_get_nav_menus', array( $this, 'hide_alternative_menus' ), 10, 2 );

		add_action( 'save_post', array( $this, 'set_alternative_post_status_as_hidden' ) );
	}//end init()

	public function register_hidden_post_status_for_alternative_content() {

		$args = array(
			'exclude_from_search'       => true,
			'public'                    => false,
			'protected'                 => true,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,
		);
		register_post_status( 'nab_hidden', $args );
	}//end register_hidden_post_status_for_alternative_content()

	public function hide_alternative_menus( $menus ) {

		global $wpdb;
		$alternative_menus = $wpdb->get_col( // phpcs:ignore
			$wpdb->prepare(
				"SELECT meta.term_id
				FROM {$wpdb->termmeta} meta
				WHERE
					meta.meta_key = %s",
				'_nab_experiment'
			)
		);

		$alternative_menus = array_map( 'absint', $alternative_menus );
		return array_filter(
			$menus,
			function ( $menu ) use ( $alternative_menus ) {
				return is_object( $menu ) && ! in_array( $menu->term_id, $alternative_menus, true );
			}
		);
	}//end hide_alternative_menus()

	public function set_alternative_post_status_as_hidden( $post ) {

		$excluded_post_types = array( 'nab_experiment', 'nab_alt_product' );
		if ( in_array( get_post_type( $post ), $excluded_post_types, true ) ) {
			return;
		}//end if

		if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return;
		}//end if

		$experiment = get_post_meta( $post, '_nab_experiment', true );
		if ( empty( $experiment ) ) {
			return;
		}//end if

		if ( 'nab_hidden' === get_post_status( $post ) ) {
			return;
		}//end if

		wp_update_post(
			array(
				'ID'          => $post,
				'post_status' => 'nab_hidden',
			)
		);
	}//end set_alternative_post_status_as_hidden()
}//end class
