<?php
/**
 * Helper functions to work with posts.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/helpers
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * A class with several helper functions to work with posts.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/helpers
 * @since      5.0.0
 */
class Nelio_AB_Testing_Post_Helper {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @var    Nelio_AB_Testing_Post_Helper|null
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Post_Helper the single instance of this class.
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
	 * This function duplicates the given post.
	 *
	 * @param int    $src_post_id the post we want to duplicate.
	 * @param string $post_name   Optional. The post name we want to use for the duplicate.
	 *
	 * @return int the ID of the new post that's a duplicate of the given
	 *             one or 0 if an error occurred.
	 *
	 * @since  5.0.0
	 */
	public function duplicate( $src_post_id, $post_name = '' ) {

		/**
		 * Runs before duplicating a post.
		 *
		 * Used to prevent default duplication method.
		 *
		 * This allows third-party plugins to duplicate a post using
		 * alternative methods. Very useful to deal with page builders.
		 *
		 * @param int $result      the ID of the new post or `0` otherwise.
		 * @param int $src_post_id the ID of the post to duplicate.
		 *
		 * @since 5.0.6
		 */
		$new_post_id = absint( apply_filters( 'nab_duplicate_post_pre', 0, $src_post_id ) );
		if ( ! empty( $new_post_id ) ) {
			wp_update_post(
				array(
					'ID'          => $new_post_id,
					'post_status' => 'nab_hidden',
					'post_name'   => ! empty( $post_name ) ? $post_name : uniqid(),
				)
			);
			return $new_post_id;
		}

		$new_post_id = wp_insert_post(
			array(
				'post_author'  => 0,
				'post_title'   => 'Nelio A/B Testing',
				'post_content' => '',
				'post_excerpt' => '',
				'post_type'    => 'post',
				'post_status'  => 'nab_hidden',
				'post_name'    => ! empty( $post_name ) ? $post_name : uniqid(),
			)
		);

		if ( empty( $new_post_id ) ) {
			return 0;
		}

		$this->overwrite( $new_post_id, $src_post_id );

		return $new_post_id;
	}

	/**
	 * This function overwites a post with the data from another post.
	 *
	 * @param int $dest_id the post we want to overwrite.
	 * @param int $src_id  the post whose data we want to use.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function overwrite( $dest_id, $src_id ) {

		$this->overwrite_post_data( $dest_id, $src_id );
		$this->overwrite_post_meta( $dest_id, $src_id );
		$this->overwrite_post_terms( $dest_id, $src_id );

		/**
		 * Runs after overwriting a WordPress post with the data from another post.
		 *
		 * @param int $dest_id the destination post that has been overwritten using the source post info.
		 * @param int $src_id  the source post that's been duplicated.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_overwrite_post', $dest_id, $src_id );
	}

	/**
	 * This function overwites the data of a post (what's in the wp_posts table)
	 * with the data from another post.
	 *
	 * @param int $dest_id the post we want to overwrite.
	 * @param int $src_id  the post whose data we want to use.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	private function overwrite_post_data( $dest_id, $src_id ) {

		$dest_id = absint( $dest_id );
		$src_id  = absint( $src_id );

		if ( empty( $dest_id ) || empty( $src_id ) ) {
			return;
		}

		$src_post = get_post( $src_id );
		if ( empty( $src_post ) ) {
			return;
		}

		$new_post = array(
			'ID'             => $dest_id,
			'comment_status' => $src_post->comment_status,
			'menu_order'     => $src_post->menu_order,
			'ping_status'    => $src_post->ping_status,
			'post_author'    => absint( $src_post->post_author ),
			'post_type'      => $src_post->post_type,
			'post_title'     => $src_post->post_title,
			'post_content'   => $src_post->post_content,
			'post_excerpt'   => $src_post->post_excerpt,
			'post_parent'    => $src_post->post_parent,
			'post_password'  => $src_post->post_password,
		);

		/**
		 * Filters whether the function `wp_slash` should be applied when inserting a duplicated post.
		 *
		 * @param boolean $apply_wp_slash Whether to apply `wp_slash` or not. Default: `true`.
		 *
		 * @since 5.1.2
		 */
		if ( apply_filters( 'nab_wp_slash_post_on_duplicate', true ) ) {
			wp_update_post( wp_slash( $new_post ) );
		} else {
			wp_update_post( $new_post );
		}
	}

	/**
	 * This function overwites the meta fields of a post with those from another post.
	 *
	 * @param int $dest_id the post whose meta fields we want to overwrite.
	 * @param int $src_id  the post whose meta fields we want to use.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	private function overwrite_post_meta( $dest_id, $src_id ) {

		$dest_type = get_post_type( $dest_id );
		if ( empty( $dest_type ) ) {
			return;
		}

		$src_metas  = $this->get_metas( $src_id );
		$dest_metas = $this->get_metas( $dest_id );
		$meta_keys  = array_merge(
			wp_list_pluck( $src_metas, 'meta_key' ),
			wp_list_pluck( $dest_metas, 'meta_key' )
		);

		/** @var list<string> */
		$meta_keys = array_values( array_unique( $meta_keys ) );

		/**
		 * Filters the list of metas that will be overwritten.
		 *
		 * @param list<string> $meta_keys      list of meta keys.
		 * @param string       $post_type type of the post that the plugin is about to overwrite.
		 *
		 * @since 7.3.0
		 */
		$meta_keys = apply_filters( 'nab_get_metas_to_overwrite', $meta_keys, $dest_type );

		$this->remove_old_metas( $dest_id, $meta_keys );

		$metas = $src_metas;
		$metas = array_filter( $metas, fn( $m ) => in_array( $m->meta_key, $meta_keys, true ) );
		$metas = array_values( $metas );
		foreach ( $metas as $meta ) {
			$this->insert_meta( $meta, $dest_id );
		}
	}

	/**
	 * This function overwites the terms in which a post appears using those from another post.
	 *
	 * @param int $dest_id the post whose terms we want to overwrite.
	 * @param int $src_id  the post whose terms we want to use.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	private function overwrite_post_terms( $dest_id, $src_id ) {

		$post_type = get_post_type( $dest_id );
		if ( empty( $post_type ) ) {
			return;
		}

		$taxonomies = array_values( get_object_taxonomies( $post_type ) );

		/**
		 * Filters the list of taxonomies that can be overwritten (if any).
		 *
		 * @param list<string> $taxonomies list of taxonomies that can be overwritten.
		 * @param string       $post_type  type of the post that the plugin is about to overwrite.
		 *
		 * @since 5.0.9
		 */
		$taxonomies = apply_filters( 'nab_get_taxonomies_to_overwrite', $taxonomies, $post_type );

		wp_delete_object_term_relationships( $dest_id, $taxonomies );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_post_terms( $src_id, $taxonomy, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $terms ) ) {
				continue;
			}
			wp_set_post_terms( $dest_id, $terms, $taxonomy );
		}
	}

	/**
	 * This function removes all the metas of a certain post (except those
	 * created by Nelio A/B Testing).
	 *
	 * @param int          $post_id the post whose meta fields we want to remove.
	 * @param list<string> $metas   list of meta keys to delete.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	private function remove_old_metas( $post_id, $metas ) {
		if ( empty( $metas ) ) {
			return;
		}

		/** @var wpdb */
		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $metas ), '%s' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM %i WHERE post_id = %d AND meta_key IN ({$placeholders})",
				array_merge(
					array( $wpdb->postmeta, $post_id ),
					$metas
				)
			) ?? ''
		);// db call ok; no-cache ok.
	}

	/**
	 * This function retrieves all the metas of a certain post (except those
	 * created by Nelio A/B Testing), directly retrieved from the database using
	 * wpdb.
	 *
	 * @param int $post_id the post whose meta fields we want to retrieve.
	 *
	 * @return list<object{meta_key: string, meta_value: string}> all the metas of a certain post (except those created by Nelio A/B Testing), directly retrieved from the database using wpdb.
	 *
	 * @since  5.0.0
	 */
	private function get_metas( $post_id ) {

		/** @var wpdb */
		global $wpdb;

		/** @var list<object{meta_key: string, meta_value: string}> */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE post_id = %d AND meta_key NOT LIKE %s',
				$wpdb->postmeta,
				$post_id,
				$wpdb->esc_like( '_nab_' ) . '%'
			)
		);// db call ok; no-cache ok.
	}

	/**
	 * This function removes all the metas of a certain post (except those
	 * created by Nelio A/B Testing).
	 *
	 * @param object{meta_key: string, meta_value: string} $meta    a meta field, as retrieved from the database.
	 * @param int                                          $post_id the post whose meta fields we want to remove.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	private function insert_meta( $meta, $post_id ) {

		/** @var wpdb */
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->postmeta,
			array(
				'post_id'    => $post_id,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'   => $meta->meta_key,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value' => $meta->meta_value,
			),
			array(
				'%d',
				'%s',
				'%s',
			)
		);
	}
}
