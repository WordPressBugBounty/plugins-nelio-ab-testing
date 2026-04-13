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
	 * Hooks into WordPress.
	 *
	 * @return void
	 * @since  5.0.0
	 */
	public function init() {

		add_action( 'init', array( $this, 'register_hidden_post_status_for_alternative_content' ), 9 );
		add_filter( 'wp_get_nav_menus', array( $this, 'hide_alternative_menus' ) );

		add_action( 'save_post', array( $this, 'set_alternative_post_status_as_hidden' ) );
		add_action( 'before_delete_post', array( $this, 'on_before_delete_post' ), 9 );
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
	 * @param WP_Term[]|int[]|string[]|string|WP_Error $menus An array of menu objects.
	 *
	 * @return WP_Term[]|int[]|string[]|string|WP_Error
	 */
	public function hide_alternative_menus( $menus ) {

		if ( is_wp_error( $menus ) || is_string( $menus ) ) {
			return $menus; // @codeCoverageIgnore
		}

		if ( empty( $menus ) ) {
			return $menus; // @codeCoverageIgnore
		}

		if ( is_string( $menus[0] ) ) {
			// NOTE. We probably need to do something here.
			return $menus; // @codeCoverageIgnore
		}

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
			array_filter( $menus, fn ( $menu ) => ! in_array( $menu instanceof WP_Term ? $menu->term_id : $menu, $alternative_menus, true ) )
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

		if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return; // @codeCoverageIgnore
		}

		if ( 'nab_hidden' === get_post_status( $post ) ) {
			return; // @codeCoverageIgnore
		}

		$excluded_post_types = array( 'nab_experiment', 'nab_alt_product' );
		if ( in_array( get_post_type( $post ), $excluded_post_types, true ) ) {
			return;
		}

		$experiment = get_post_meta( $post, '_nab_experiment', true );
		if ( empty( $experiment ) ) {
			return;
		}

		wp_update_post(
			wp_slash(
				array(
					'ID'          => $post,
					'post_status' => 'nab_hidden',
				)
			)
		);
	}

	/**
	 * Callback to delete related information on experiments being deleted.
	 *
	 * @param int $post_id the post we're about to delete.
	 *
	 * @return void
	 */
	public function on_before_delete_post( $post_id ) {

		if ( 'nab_experiment' !== get_post_type( $post_id ) ) {
			return; // @codeCoverageIgnore
		}

		$experiment = nab_get_experiment( $post_id );
		if ( is_wp_error( $experiment ) ) {
			return; // @codeCoverageIgnore
		}

		$experiment->delete_related_information();
	}
}
