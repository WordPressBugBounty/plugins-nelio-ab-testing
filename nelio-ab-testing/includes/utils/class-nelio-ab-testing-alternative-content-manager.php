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
	 * @var    Nelio_AB_Testing_Alternative_Content_Manager|null
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

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 * @since  5.0.0
	 */
	public function init() {

		add_action( 'init', array( $this, 'register_hidden_post_status_for_alternative_content' ), 9 );
		add_filter( 'wp_get_nav_menus', array( $this, 'hide_alternative_menus' ) );

		add_action( 'save_post', array( $this, 'set_alternative_post_status_as_hidden' ) );
	}

	/**
	 * Callback to register the `nab_hidden` post status.
	 *
	 * @return void
	 */
	public function register_hidden_post_status_for_alternative_content() {

		$args = array(
			'exclude_from_search'       => true,
			'public'                    => false,
			'protected'                 => true,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,
		);
		register_post_status( 'nab_hidden', $args );
	}

	/**
	 * Callback to hide alternative menus.
	 *
	 * @param list<WP_Term> $menus An array of menu objects.
	 *
	 * @return list<WP_Term>
	 */
	public function hide_alternative_menus( $menus ) {

		/** @var wpdb */
		global $wpdb;
		/** @var list<mixed> */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$alternative_menus = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT meta.term_id FROM %i meta WHERE meta.meta_key = %s',
				$wpdb->termmeta,
				'_nab_experiment'
			)
		);

		$alternative_menus = array_map( 'absint', $alternative_menus );
		return array_values(
			array_filter( $menus, fn ( $menu ) => ! in_array( $menu->term_id, $alternative_menus, true ) )
		);
	}

	/**
	 * Callback to set the status of an alternative post to `nab_hidden` on save.
	 *
	 * @param int $post Post ID.
	 *
	 * @return void
	 */
	public function set_alternative_post_status_as_hidden( $post ) {

		$excluded_post_types = array( 'nab_experiment', 'nab_alt_product' );
		if ( in_array( get_post_type( $post ), $excluded_post_types, true ) ) {
			return;
		}

		if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return;
		}

		$experiment = get_post_meta( $post, '_nab_experiment', true );
		if ( empty( $experiment ) ) {
			return;
		}

		if ( 'nab_hidden' === get_post_status( $post ) ) {
			return;
		}

		wp_update_post(
			array(
				'ID'          => $post,
				'post_status' => 'nab_hidden',
			)
		);
	}
}
