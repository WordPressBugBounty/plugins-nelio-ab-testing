<?php
/**
 * Some helper functions used during runtime
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public/helpers
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Some helper functions used during frontend runtime.
 */
class Nelio_AB_Testing_Runtime {

	protected static $instance;

	private $experiments_by_priority;
	private $relevant_heatmaps;

	private $current_url;

	public static function instance(): Nelio_AB_Testing_Runtime {

		if ( is_null( self::$instance ) ) {

			self::$instance = new self();

			self::$instance->current_url             = false;
			self::$instance->relevant_heatmaps       = array();
			self::$instance->experiments_by_priority = array(
				'high'   => array(),
				'mid'    => array(),
				'low'    => array(),
				'custom' => array(),
			);

		}//end if

		return self::$instance;

	}//end instance()

	public function init() {

		if ( wp_is_json_request() ) {
			add_action( 'plugins_loaded', array( $this, 'enable_running_experiments_in_rest_request' ), 99 );
		} else {
			add_action( 'plugins_loaded', array( $this, 'compute_relevant_high_priority_experiments' ), 99 );
			add_action( 'parse_query', array( $this, 'compute_relevant_mid_priority_experiments' ), 99 );
			add_action( 'wp', array( $this, 'compute_relevant_low_priority_experiments' ), 99 );
			add_action( 'parse_query', array( $this, 'compute_relevant_heatmaps' ), 99 );
		}//end if

	}//end init()

	public function get_relevant_running_experiments() {

		return array_merge(
			$this->experiments_by_priority['high'],
			$this->experiments_by_priority['mid'],
			$this->experiments_by_priority['low'],
			$this->experiments_by_priority['custom'],
		);

	}//end get_relevant_running_experiments()

	public function get_relevant_running_heatmaps() {

		return $this->relevant_heatmaps;

	}//end get_relevant_running_heatmaps()

	public function get_current_url() {

		if ( empty( $this->current_url ) ) {
			$this->compute_current_url();
		}//end if
		return $this->current_url;

	}//end get_current_url()

	public function get_untested_url() {

		return remove_query_arg( 'nab', $this->get_current_url() );

	}//end get_untested_url()

	public function get_alternative_from_request() {

		if ( $this->is_post_request() ) {
			if ( $this->is_tested_post_request() ) {
				return $this->get_nab_value_from_post_request();
			} else {
				return 0;
			}//end if
		}//end if

		$url = $this->get_current_url();
		$nab = $this->get_nab_query_arg( $url );
		return absint( $nab );

	}//end get_alternative_from_request()

	/**
	 * Returns whether the request method is POST and whether we're supposed to load alternative content or not.
	 *
	 * @return boolean whether the request method is POST and whether we're supposed to load alternative content or not.
	 */
	public function is_tested_post_request() {

		if ( ! $this->is_post_request() ) {
			return false;
		}//end if

		if ( ! $this->can_load_alternative_content_on_post_request() ) {
			return false;
		}//end if

		return false !== $this->get_nab_value_from_post_request();

	}//end is_tested_post_request()

	public function compute_relevant_high_priority_experiments() {
		$this->compute_relevant_experiments( 'high' );
	}//end compute_relevant_high_priority_experiments()

	public function compute_relevant_mid_priority_experiments( $query ) {
		if ( ! $query->is_main_query() ) {
			return;
		}//end if
		remove_action( 'parse_query', array( $this, 'compute_relevant_mid_priority_experiments' ), 99 );
		$this->compute_relevant_experiments( 'mid' );
	}//end compute_relevant_mid_priority_experiments()

	public function compute_relevant_low_priority_experiments() {
		$this->compute_relevant_experiments( 'low' );
	}//end compute_relevant_low_priority_experiments()

	/**
	 * Marks an experiment with custom priority as loaded.
	 *
	 * @param integer $exp_id The experiment ID.
	 *
	 * @since 7.0.6
	 */
	public function add_custom_priority_experiment( $exp_id ) {
		$exp = $this->get_custom_priority_experiment_or_die( $exp_id );
		$ids = wp_list_pluck( $this->experiments_by_priority['custom'], 'ID' );
		if ( in_array( $exp_id, $ids, true ) ) {
			return;
		}//end if

		$this->experiments_by_priority['custom'][] = $exp;

		/**
		 * Marks an experiment with custom priority as loaded.
		 *
		 * @param Nelio_AB_Testing_Experiment $exp The experiment.
		 *
		 * @since 7.0.6
		 */
		do_action( 'nab_relevant_custom_priority_experiment_loaded', array( $exp ) );
	}//end add_custom_priority_experiment()

	/**
	 * Returns whether an experiment with custom priority is relevant or not.
	 *
	 * @param integer $exp_id The experiment ID.
	 *
	 * @return boolean whether the experiment is relevant or not.
	 *
	 * @since 7.0.6
	 */
	public function is_custom_priority_experiment_relevant( $exp_id ) {
		$exp    = $this->get_custom_priority_experiment_or_die( $exp_id );
		$result = $this->filter_relevant_experiments( array( $exp ), 'custom' );
		return ! empty( $result );
	}//end is_custom_priority_experiment_relevant()

	public function enable_running_experiments_in_rest_request() {
		$experiments                             = nab_get_running_experiments();
		$this->experiments_by_priority['custom'] = $experiments;
		do_action( 'nab_relevant_custom_priority_experiments_loaded', $experiments );
	}//end enable_running_experiments_in_rest_request()

	public function compute_relevant_heatmaps( $query ) {

		if ( ! $query->is_main_query() ) {
			return;
		}//end if
		remove_action( 'parse_query', array( $this, 'compute_relevant_heatmaps' ), 99 );

		$untested_url = $this->get_untested_url();

		$this->relevant_heatmaps = array_filter(
			nab_get_running_heatmaps(),
			function ( $heatmap ) use ( $untested_url ) {
				if ( 'url' !== $heatmap->get_tracking_mode() ) {
					return nab_get_queried_object_id() === $heatmap->get_tracked_post_id();
				}//end if

				$expected_url = $heatmap->get_tracked_url();
				return $this->do_urls_match_exactly( $untested_url, $expected_url );
			}
		);
		$this->relevant_heatmaps = array_values( $this->relevant_heatmaps );

		/**
		 * Fires after determining the list of relevant heatmaps.
		 *
		 * @param array $heatmaps list of relevant heatmaps.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_relevant_heatmaps_loaded', $this->relevant_heatmaps );

	}//end compute_relevant_heatmaps()

	private function compute_relevant_experiments( $priority ) {
		$experiments = $this->filter_relevant_experiments( nab_get_running_experiments(), $priority );

		/**
		 * Filters the list of `$priority` (either `high`, `mid`, or `low`) priority experiments.
		 *
		 * @param array $experiments list of `$priority` priority experiments.
		 *
		 * @since 7.0.0
		 */
		$experiments = apply_filters( "nab_relevant_{$priority}_priority_experiments", $experiments );

		/**
		 * Fires after determining the list of `$priority` (either `high`, `mid`, or `low`) priority experiments.
		 *
		 * @param array $experiments list of `$priority` priority experiments.
		 *
		 * @since 7.0.0
		 */
		do_action( "nab_relevant_{$priority}_priority_experiments_loaded", $experiments );
		$this->experiments_by_priority[ $priority ] = $experiments;
	}//end compute_relevant_experiments()

	private function is_post_request() {
		return (
			isset( $_SERVER['REQUEST_METHOD'] ) &&
			'POST' === $_SERVER['REQUEST_METHOD']
		);
	}//end is_post_request()

	private function can_load_alternative_content_on_post_request() {

		/**
		 * Filters whether the plugin can attempt to load alternative content when processing a post request or not.
		 *
		 * @param boolean $can_load whether the plugin can attempt to load alternative content when processing a post request or not. Default: `true`.
		 *
		 * @since 5.0.10
		 */
		return apply_filters( 'nab_can_load_alternative_content_on_post_request', true );

	}//end can_load_alternative_content_on_post_request()

	private function filter_relevant_experiments( $experiments, $priority ) {

		$relevant_experiments = array_filter(
			$experiments,
			function( $experiment ) use ( $priority ) {

				$url             = $this->get_untested_url();
				$experiment_id   = $experiment->get_id();
				$experiment_type = $experiment->get_type();
				$control         = $experiment->get_alternative( 'control' );

				/**
				 * Filters the experiment priority, which specifies the moment at which an experiment’s relevance will be computed.
				 *
				 * @param string $priority      Experiment priority. Either `high`, `mid`, or `low`. Default: `low`.
				 * @param array  $control       original version.
				 * @param int    $experiment_id id of the experiment.
				 *
				 * @since 7.0.0
				 */
				if ( apply_filters( "nab_{$experiment_type}_experiment_priority", 'low', $control['attributes'], $experiment_id ) !== $priority ) {
					return false;
				}//end if

				/**
				 * Short-circuits whether the given experiment should be relevant or not.
				 *
				 * @param mixed   $value         whether the given experiment should be relevant or not.
				 *                               Default: `null`.
				 * @param int     $experiment_id the ID of the experiment.
				 * @param string  $url           the URL of the current request.
				 *
				 * @since 6.5.0
				 */
				$check = apply_filters( "nab_is_{$experiment_type}_relevant_in_url", null, $experiment_id, $url );
				if ( null !== $check ) {
					return ! empty( $check );
				}//end if

				$scope = $experiment->get_scope();
				if ( empty( $scope ) ) {
					/**
					 * Whether the experiment is relevant in the current request or not.
					 *
					 * @param boolean $is_excluded   whether the experiment is relevant from the current request or not.
					 *                               Default: `true`.
					 * @param array   $control       original version.
					 * @param int     $experiment_id id of the experiment.
					 *
					 * @since 7.0.0
					 */
					return apply_filters( "nab_is_{$experiment_type}_php_scope_relevant", true, $control['attributes'], $experiment_id );
				}//end if

				foreach ( $scope as $rule ) {
					if ( $this->does_scope_rule_match( $rule['attributes'], $experiment ) ) {
						return true;
					}//end if
				}//end foreach

				return false;

			}
		);

		return array_values( $relevant_experiments );

	}//end filter_relevant_experiments()

	private function get_nab_value_from_post_request() {
		if ( isset( $_COOKIE['nabAlternative'] ) ) { // phpcs:ignore
			return absint( $_COOKIE['nabAlternative'] ); // phpcs:ignore
		}//end if

		if ( isset( $_REQUEST['nab'] ) ) { // phpcs:ignore
			return absint( $_REQUEST['nab'] ); // phpcs:ignore
		}//end if

		return false;
	}//end get_nab_value_from_post_request()

	private function get_nab_query_arg( $url ) {

		if ( 'redirection' !== nab_get_variant_loading_strategy() ) {
			// phpcs:ignore
			return isset( $_REQUEST['nab'] ) ? absint( $_REQUEST['nab'] ) : 0;
		}//end if

		$query = wp_parse_args( wp_parse_url( $url, PHP_URL_QUERY ) );
		if ( ! isset( $query['nab'] ) ) {
			return false;
		}//end if

		return absint( $query['nab'] );

	}//end get_nab_query_arg()

	private function compute_current_url() {

		// “nab” query var and WordPress’ default public query vars (see class-wp.php).
		$query_vars = array( 'nab', 'm', 'p', 'posts', 'w', 'cat', 'withcomments', 'withoutcomments', 's', 'search', 'exact', 'sentence', 'calendar', 'page', 'paged', 'more', 'tb', 'pb', 'author', 'order', 'orderby', 'year', 'monthnum', 'day', 'hour', 'minute', 'second', 'name', 'category_name', 'tag', 'feed', 'author_name', 'pagename', 'page_id', 'error', 'attachment', 'attachment_id', 'subpost', 'subpost_id', 'preview', 'robots', 'taxonomy', 'term', 'cpage', 'post_type', 'embed', 'wc-ajax' );

		/**
		 * Filters public query vars.
		 *
		 * @param array $query_vars public query vars.
		 *
		 * @since 5.0.6
		 */
		$query_vars = apply_filters( 'nab_query_vars', $query_vars );

		$url   = nab_home_url( $this->get_clean_request_uri() );
		$query = wp_parse_args( wp_parse_url( $url, PHP_URL_QUERY ) );
		$query = array_filter(
			$query,
			function( $key ) use ( $query_vars ) {
				return in_array( $key, $query_vars, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		ksort( $query );
		$url = preg_replace( '/\?.*$/', '', $url );
		$url = add_query_arg( $query, $url );

		$nab = $this->get_nab_query_arg( $url );
		if ( false !== $nab ) {
			$url = remove_query_arg( 'nab', $url );
			$url = add_query_arg( 'nab', $nab, $url );
		}//end if

		$this->current_url = $url;

	}//end compute_current_url()

	private function get_clean_request_uri() {

		$request_uri             = ! empty( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';
		$request_uri_in_home_url = preg_replace( '/^https?:\/\/[^\/]+/', '', nab_home_url() );

		$request_uri             = '/' . ltrim( $request_uri, '/' );
		$request_uri_in_home_url = '/' . ltrim( $request_uri_in_home_url, '/' );

		if ( 0 !== strpos( $request_uri, $request_uri_in_home_url ) ) {
			return $request_uri;
		}//end if

		$request_uri = substr( $request_uri, strlen( $request_uri_in_home_url ) );
		if ( 0 < strlen( $request_uri ) && '/' !== $request_uri[0] ) {
			$request_uri = '/' . $request_uri;
		}//end if

		return $request_uri;

	}//end get_clean_request_uri()

	private function does_scope_rule_match( $rule, $experiment ) {

		$url = $this->get_untested_url();

		switch ( $rule['type'] ) {

			case 'tested-post':
				/**
				 * Filters whether the current request is a single post that’s tested by the given experiment.
				 *
				 * @param boolean $is_tested whether the current request is a single post that’s tested by the given experiment.
				 * @param number  $post_id   post ID of the current post (if any).
				 * @param array   $control   original version.
				 * @param number  $exp_id    ID of the experiment.
				 *
				 * @since 5.2.11
				 */
				return apply_filters( "nab_is_tested_post_by_{$experiment->get_type()}_experiment", $this->is_tested_post( nab_get_queried_object_id(), $experiment ), nab_get_queried_object_id(), $experiment->get_alternative( 'control' )['attributes'], $experiment->ID );

			case 'partial':
				return false !== stripos( $url, $rule['value'] );

			case 'exact':
				return $this->do_urls_match_exactly( $url, $rule['value'] );
		}//end switch

		return false;

	}//end does_scope_rule_match()

	private function is_tested_post( $current_post_id, $experiment ) {
		if ( ! is_singular() ) {
			return false;
		}//end if

		$control_id = absint( $experiment->get_tested_element() );
		if ( $current_post_id === $control_id ) {
			return true;
		}//end if

		$control = $experiment->get_alternative( 'control' );
		$control = $control['attributes'];
		if ( empty( $control['testAgainstExistingContent'] ) ) {
			return false;
		}//end if

		$alts = $experiment->get_alternatives();
		$pids = wp_list_pluck( wp_list_pluck( $alts, 'attributes' ), 'postId' );
		$pids = array_values( array_filter( $pids ) );
		return in_array( $current_post_id, $pids, true );
	}//end is_tested_post()

	private function do_urls_match_exactly( $actual_url, $expected_url ) {

		$actual_url   = strtolower( preg_replace( '/^[^:]+:\/\//', '', $actual_url ) );
		$expected_url = strtolower( preg_replace( '/^[^:]+:\/\//', '', $expected_url ) );

		$actual_args   = wp_parse_args( wp_parse_url( $actual_url, PHP_URL_QUERY ) );
		$expected_args = wp_parse_args( wp_parse_url( $expected_url, PHP_URL_QUERY ), $actual_args );

		ksort( $actual_args );
		ksort( $expected_args );

		$actual_url   = untrailingslashit( preg_replace( '/\?.*$/', '', $actual_url ) );
		$expected_url = untrailingslashit( preg_replace( '/\?.*$/', '', $expected_url ) );

		/**
		 * Whether to ignore query args when trying to match the current URL with a URL specified in an experiment scope.
		 *
		 * @param boolean $ignore whether to ignore query args when trying to match the URL with a URL specified in an experiment scope. Default: `false`.
		 *
		 * @since 5.0.0
		 */
		if ( ! apply_filters( 'nab_ignore_query_args_in_scope', false ) ) {
			$actual_url   = add_query_arg( $actual_args, $actual_url );
			$expected_url = add_query_arg( $expected_args, $expected_url );
		}//end if

		return $actual_url === $expected_url;

	}//end do_urls_match_exactly()

	private function get_custom_priority_experiment_or_die( $exp_id ) {
		$exps = nab_get_running_experiments();
		$exps = array_combine( wp_list_pluck( $exps, 'ID' ), $exps );
		if ( ! isset( $exps[ $exp_id ] ) ) {
			/* translators: experiment ID */
			wp_die( sprintf( esc_html_x( 'Custom priority experiment %d not found', 'text', 'nelio-ab-testing' ), esc_html( $exp_id ) ) );
		}//end if

		return $exps[ $exp_id ];
	}//end get_custom_priority_experiment_or_die()

}//end class
