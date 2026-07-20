<?php

namespace Nelio_AB_Testing\Recommendation_Engine;

use Nelio_AB_Testing_Experiment;
use WP_Query;

final class Experiment_Repository {

	// Cache group.
	private const CACHE_GROUP = 'nelio-ab-testing--recommendation-engine';

	// Cache keys.
	private const WAS_POST_ID_TESTED_THREE_MONTHS = 'was_{$post_id}_tested_last_3_months';
	private const WAS_POST_ID_TESTED_SIX_MONTHS   = 'was_{$post_id}_tested_last_6_months';

	private const HAS_EXPERIMENTS          = 'has_experiments';
	private const RECENT_DRAFT_EXPERIMENTS = 'recent_draft_experiments';

	private const FIVE_RECENTLY_FINISHED_EXPS  = 'five_recently_finished_exps';
	private const FIFTY_RECENTLY_FINISHED_EXPS = 'fifty_recently_finished_exps';
	private const ALL_RECENTLY_FINISHED_EXPS   = 'all_recently_finished_exps';

	private const FIVE_FINISHED_EXPS  = 'five_finished_exps';
	private const FIFTY_FINISHED_EXPS = 'fifty_finished_exps';
	private const ALL_FINISHED_EXPS   = 'all_finished_exps';

	private const TESTABLE_CONTENT_COUNT = 'testable_content_count';
	private const TESTED_CONTENT_COUNT   = 'tested_content_count';

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'save_post', array( $this, 'clear_post_related_caches' ) );

		add_action( 'nab_after_create_experiment', array( $this, 'clear_experiment_related_caches' ) );
		add_action( 'nab_start_experiment', array( $this, 'clear_experiment_related_caches' ) );
		add_action( 'nab_restart_experiment', array( $this, 'clear_experiment_related_caches' ) );
		add_action( 'nab_pause_experiment', array( $this, 'clear_experiment_related_caches' ) );
		add_action( 'nab_resume_experiment', array( $this, 'clear_experiment_related_caches' ) );
		add_action( 'nab_stop_experiment', array( $this, 'clear_experiment_related_caches' ) );

		add_action( 'nab_after_create_experiment', array( $this, 'clear_recent_draft_experiments_cache' ) );
		add_action( 'nab_save_experiment', array( $this, 'clear_recent_draft_experiments_cache' ) );
		add_action( 'nab_start_experiment', array( $this, 'clear_recent_draft_experiments_cache' ) );
	}

	/**
	 * Callback to clear post related caches.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function clear_post_related_caches( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post_types = array_merge(
			array( 'page', 'post' ),
			\Nelio_AB_Testing\Experiment_Library\Post_Experiment\get_testable_custom_post_types()
		);
		if ( ! in_array( get_post_type( $post_id ), $post_types, true ) ) {
			return;
		}

		wp_cache_delete( self::TESTABLE_CONTENT_COUNT, self::CACHE_GROUP );
		wp_cache_delete( self::TESTED_CONTENT_COUNT, self::CACHE_GROUP );
	}

	/**
	 * Callback to clear experiment related caches.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return void
	 */
	public function clear_experiment_related_caches( $experiment ) {
		$post_id = $experiment->get_tested_post();
		wp_cache_delete( str_replace( '{$post_id}', "$post_id", self::WAS_POST_ID_TESTED_THREE_MONTHS ), self::CACHE_GROUP );
		wp_cache_delete( str_replace( '{$post_id}', "$post_id", self::WAS_POST_ID_TESTED_SIX_MONTHS ), self::CACHE_GROUP );

		wp_cache_delete( self::HAS_EXPERIMENTS, self::CACHE_GROUP );

		wp_cache_delete( self::FIVE_RECENTLY_FINISHED_EXPS, self::CACHE_GROUP );
		wp_cache_delete( self::FIFTY_RECENTLY_FINISHED_EXPS, self::CACHE_GROUP );
		wp_cache_delete( self::ALL_RECENTLY_FINISHED_EXPS, self::CACHE_GROUP );

		wp_cache_delete( self::FIVE_FINISHED_EXPS, self::CACHE_GROUP );
		wp_cache_delete( self::FIFTY_FINISHED_EXPS, self::CACHE_GROUP );
		wp_cache_delete( self::ALL_FINISHED_EXPS, self::CACHE_GROUP );
	}

	/**
	 * Returns whether the given content has been tested.
	 *
	 * If `$since` is provided, only experiments completed on or after that
	 * timestamp are considered.
	 *
	 * @param int                             $post_id Post ID.
	 * @param 'last-3-months'|'last-6-months' $since   Since date.
	 *
	 * @return bool
	 */
	public function was_tested( $post_id, $since ) {
		if ( 'last-3-months' === $since ) {
			$month     = gmdate( 'Y-m-01\T00:00:00.000\Z', strtotime( '-3 months' ) );
			$cache_key = str_replace( '{$post_id}', "$post_id", self::WAS_POST_ID_TESTED_THREE_MONTHS );
		} else {
			$month     = gmdate( 'Y-m-01\T00:00:00.000\Z', strtotime( '-6 months' ) );
			$cache_key = str_replace( '{$post_id}', "$post_id", self::WAS_POST_ID_TESTED_SIX_MONTHS );
		}

		$date_key = substr( $month, 0, 4 ) . substr( $month, 5, 2 );

		/** @var boolean */
		$was_tested = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $found );
		if ( ! $found || ! $was_tested ) {
			/** @var \wpdb */
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$was_tested = 0 < $wpdb->get_var(
				$wpdb->prepare(
					'
						SELECT COUNT(DISTINCT p.ID)
						FROM %i p
						INNER JOIN %i pm1
							ON p.ID = pm1.post_id
						INNER JOIN %i pm2
							ON p.ID = pm2.post_id
						WHERE p.post_type = "nab_experiment"
							AND p.post_status = "nab_finished"
							AND pm1.meta_key = %s
							AND pm1.meta_value = %s
							AND pm2.meta_key = %s
							AND pm2.meta_value >= %s
						',
					$wpdb->posts,
					$wpdb->postmeta,
					$wpdb->postmeta,
					'_nab_tested_post_id',
					$post_id,
					'_nab_end_date',
					$date_key
				)
			);
			wp_cache_set( $cache_key, $was_tested, self::CACHE_GROUP, WEEK_IN_SECONDS );
		}

		return $was_tested;
	}

	/**
	 * Whether there are any experiments in the database or not.
	 *
	 * @return boolean
	 */
	public function has_experiments() {
		$cache_key = self::HAS_EXPERIMENTS;

		/** @var boolean */
		$has_experiments = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $found );
		if ( ! $found ) {
			$query           = new WP_Query(
				array(
					'post_type'      => 'nab_experiment',
					'posts_per_page' => 1,
					'post_status'    => 'any',
					'fields'         => 'ids',
				)
			);
			$has_experiments = ! empty( $query->posts );
			wp_cache_set( $cache_key, $has_experiments, self::CACHE_GROUP, WEEK_IN_SECONDS );
		}

		return $has_experiments;
	}

	/**
	 * Returns recently finished experiments.
	 *
	 * @param 'last5'|'last50'|'all' $limit Number of experiments.
	 *
	 * @return list<Nelio_AB_Testing_Experiment>
	 */
	public function get_recently_finished_experiments( $limit ) {
		if ( 'last5' === $limit ) {
			$cache_key = self::FIVE_RECENTLY_FINISHED_EXPS;
			$limit     = 5;
		} elseif ( 'last50' === $limit ) {
			$cache_key = self::FIFTY_RECENTLY_FINISHED_EXPS;
			$limit     = 50;
		} else {
			$cache_key = self::ALL_RECENTLY_FINISHED_EXPS;
			$limit     = -1;
		}

		/** @var list<int>|false */
		$experiment_ids = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $found );
		if ( ! $found || false === $experiment_ids ) {
			$query              = new WP_Query(
				array(
					'post_type'      => 'nab_experiment',
					'post_status'    => 'nab_finished',
					'posts_per_page' => $limit,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_key'       => '_nab_end_date',
					'orderby'        => 'meta_value',
					'order'          => 'DESC',
					'fields'         => 'ids',
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'meta_query'     => array(
						array(
							'key'     => '_nab_end_date',
							'value'   => gmdate(
								'Y-m-d\TH:i:s.000\Z',
								strtotime( '-6 months' )
							),
							'compare' => '>=',
						),
					),
				),
			);
				$experiment_ids = $query->posts;
				wp_cache_set( $cache_key, $experiment_ids, self::CACHE_GROUP, WEEK_IN_SECONDS );
		}

		$experiments = array_map( fn( $id ) => nab_get_experiment( $id ), $experiment_ids );
		$experiments = array_filter( $experiments, fn( $e ) => ! is_wp_error( $e ) );
		return array_values( $experiments );
	}

	/**
	 * Returns recent draft/ready experiments.
	 *
	 * @return list<Nelio_AB_Testing_Experiment>
	 */
	public function get_recent_drafts() {
		$cache_key = self::RECENT_DRAFT_EXPERIMENTS;

		/** @var list<int>|false */
		$experiment_ids = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $found );
		if ( ! $found || false === $experiment_ids ) {
			$query          = new WP_Query(
				array(
					'post_type'      => 'nab_experiment',
					'post_status'    => array( 'draft', 'nab_ready' ),
					'posts_per_page' => 10,
					'orderby'        => 'modified',
					'order'          => 'DESC',
					'fields'         => 'ids',
					'date_query'     => array(
						'column' => 'post_modified',
						'after'  => '1 week ago',
					),
				),
			);
			$experiment_ids = $query->posts;
			wp_cache_set( $cache_key, $experiment_ids, self::CACHE_GROUP, WEEK_IN_SECONDS );
		}

		$experiments = array_map( fn( $id ) => nab_get_experiment( $id ), $experiment_ids );
		$experiments = array_filter( $experiments, fn( $e ) => ! is_wp_error( $e ) );
		return array_values( $experiments );
	}

	/**
	 * Callback to clear the recent draft experiments cache.
	 *
	 * @return void
	 */
	public function clear_recent_draft_experiments_cache() {
		wp_cache_delete( self::RECENT_DRAFT_EXPERIMENTS, self::CACHE_GROUP );
	}

	/**
	 * Gets the number of post instances that have been tested.
	 *
	 * @return int
	 */
	public function get_tested_content_count() {
		$cache_key = self::TESTED_CONTENT_COUNT;

		/** @var int|false */
		$count = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $found );
		if ( ! $found || false === $count ) {
			/** @var \wpdb */
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$count = $wpdb->get_var(
				$wpdb->prepare(
					'
						SELECT COUNT(DISTINCT pm.meta_value)
						FROM %i p
						INNER JOIN %i pm
							ON p.ID = pm.post_id
						WHERE p.post_type = "nab_experiment"
							AND p.post_status = "nab_finished"
							AND pm.meta_key = %s
							AND pm.meta_value > 0
						',
					$wpdb->posts,
					$wpdb->postmeta,
					'_nab_tested_post_id'
				)
			);
			$count = absint( $count );
			wp_cache_set( $cache_key, $count, self::CACHE_GROUP, WEEK_IN_SECONDS );
		}

		return $count;
	}

	/**
	 * Gets the number of post instances that can be tested.
	 *
	 * @return int
	 */
	public function get_testable_content_count() {
		$cache_key = self::TESTABLE_CONTENT_COUNT;

		/** @var int|false */
		$count = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $found );
		if ( ! $found || false === $count ) {
			/** @var \wpdb */
			global $wpdb;

			$post_types   = array_merge(
				array( 'page', 'post' ),
				\Nelio_AB_Testing\Experiment_Library\Post_Experiment\get_testable_custom_post_types()
			);
			$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$count = $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT COUNT(*) FROM %i WHERE post_status = 'publish' AND post_type IN ({$placeholders})",
					array_merge(
						array( $wpdb->posts ),
						$post_types
					)
				)
			);
			$count = absint( $count );
			wp_cache_set( $cache_key, $count, self::CACHE_GROUP, WEEK_IN_SECONDS );
		}

		$tested_count = $this->get_tested_content_count();
		// This should never happen, but let’s make sure any future ratios make sense.
		if ( $tested_count > $count ) {
			return $tested_count;
		}

		return $count;
	}

	/**
	 * Returns tested content.
	 *
	 * @param 'last5'|'last50'|'all' $limit Number of experiments.
	 *
	 * @return list<Tested_Content>
	 */
	public function get_tested_content( $limit ) {
		$experiments = $this->get_finished_experiments( 'all' );
		$contents    = array_map( array( $this, 'finished_experiment_to_tested_content' ), $experiments );
		$contents    = array_values( array_filter( $contents ) );
		switch ( $limit ) {
			case 'last5':
				return array_slice( $contents, 0, 5 );
			case 'last50':
				return array_slice( $contents, 0, 50 );
			case 'all':
				return $contents;
		}
	}

	/**
	 * Returns recently tested content.
	 *
	 * @param 'last5'|'last50'|'all' $limit Number of experiments.
	 *
	 * @return list<Tested_Content>
	 */
	public function get_recently_tested_content( $limit ) {
		$experiments = $this->get_recently_finished_experiments( 'all' );
		$contents    = array_map( array( $this, 'finished_experiment_to_tested_content' ), $experiments );
		$contents    = array_values( array_filter( $contents ) );
		switch ( $limit ) {
			case 'last5':
				return array_slice( $contents, 0, 5 );
			case 'last50':
				return array_slice( $contents, 0, 50 );
			case 'all':
				return $contents;
		}
	}

	/**
	 * Callback to convert an experiment to a `Tested_Content` instance.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return Tested_Content|null
	 */
	public function finished_experiment_to_tested_content( $experiment ) {
		$end_date = $experiment->get_end_date();
		if ( 'finished' !== $experiment->get_status() || empty( $end_date ) ) {
			return null;
		}

		switch ( $experiment->get_type() ) {
			case 'nab/page':
			case 'nab/custom-post-type':
				$control   = $experiment->get_alternative( 'control' );
				$post_type = $control['attributes']['postType'] ?? null;
				$post_id   = absint( $control['attributes']['postId'] ?? null );
				$permalink = get_permalink( absint( $post_id ) );
				if ( empty( $post_type ) || empty( $post_id ) || empty( $permalink ) ) {
					return null;
				}

				return new Tested_Content(
					array(
						'type'     => 'post',
						'postType' => sanitize_text_field( $post_type ),
						'postId'   => $post_id,
						'title'    => get_the_title( $post_id ),
						'url'      => $permalink,
					),
					$experiment->get_id(),
					$end_date
				);

			case 'nab/css':
				$rules = $experiment->get_scope();
				if ( count( $rules ) !== 1 ) {
					return null;
				}

				if ( 'exact' !== $rules[0]['attributes']['type'] ) {
					return null;
				}

				$rule_value = $rules[0]['attributes']['value'];
				$post_id    = nab_url_to_postid( $rule_value );
				$post_type  = get_post_type( $post_id );
				if ( empty( $post_id ) || empty( $post_type ) ) {
					return new Tested_Content(
						array(
							'type'  => 'url',
							'title' => _x( 'URL Opportunity', 'text', 'nelio-ab-testing' ),
							'url'   => $rule_value,
						),
						$experiment->get_id(),
						$end_date
					);
				}

				return new Tested_Content(
					array(
						'type'     => 'post',
						'postType' => $post_type,
						'postId'   => $post_id,
						'title'    => get_the_title( $post_id ),
						'url'      => $rule_value,
					),
					$experiment->get_id(),
					$end_date
				);

			default:
				return null;
		}
	}

	/**
	 * Returns finished experiments.
	 *
	 * @param 'last5'|'last50'|'all' $limit Number of experiments.
	 *
	 * @return list<Nelio_AB_Testing_Experiment>
	 */
	private function get_finished_experiments( $limit ) {
		if ( 'last5' === $limit ) {
			$cache_key = self::FIVE_FINISHED_EXPS;
			$limit     = 5;
		} elseif ( 'last50' === $limit ) {
			$cache_key = self::FIFTY_FINISHED_EXPS;
			$limit     = 50;
		} else {
			$cache_key = self::ALL_FINISHED_EXPS;
			$limit     = -1;
		}

		/** @var list<int>|false */
		$experiment_ids = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $found );
		if ( ! $found || false === $experiment_ids ) {
			$query              = new WP_Query(
				array(
					'post_type'      => 'nab_experiment',
					'post_status'    => 'nab_finished',
					'posts_per_page' => $limit,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_key'       => '_nab_end_date',
					'orderby'        => 'meta_value',
					'order'          => 'DESC',
					'fields'         => 'ids',
				),
			);
				$experiment_ids = $query->posts;
				wp_cache_set( $cache_key, $experiment_ids, self::CACHE_GROUP, WEEK_IN_SECONDS );
		}

		$experiments = array_map( fn( $id ) => nab_get_experiment( $id ), $experiment_ids );
		$experiments = array_filter( $experiments, fn( $e ) => ! is_wp_error( $e ) );
		return array_values( $experiments );
	}
}
