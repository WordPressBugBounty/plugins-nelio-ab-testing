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

	/**
	 * This instance.
	 *
	 * @var Nelio_AB_Testing_Runtime|null
	 */
	protected static $instance;

	/**
	 * Experiments by priority.
	 *
	 * @var array{high:list<Nelio_AB_Testing_Experiment>, mid:list<Nelio_AB_Testing_Experiment>, low:list<Nelio_AB_Testing_Experiment>, custom:list<Nelio_AB_Testing_Experiment>}
	 */
	private $experiments_by_priority;

	/**
	 * Relevant heatmaps.
	 *
	 * @var list<Nelio_AB_Testing_Heatmap>
	 */
	private $relevant_heatmaps;

	/**
	 * Current URL.
	 *
	 * @var string|false
	 */
	private $current_url;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Runtime
	 */
	public static function instance() {

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

		}

		return self::$instance;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		if ( nab_is_rest_api_request() ) {
			add_action( 'plugins_loaded', array( $this, 'enable_running_experiments_in_rest_request' ), 99 );
		} elseif ( wp_doing_ajax() ) {
			add_action( 'plugins_loaded', array( $this, 'enable_running_experiments_in_ajax_request' ), 99 );
		} elseif ( ! is_admin() ) {
			add_action( 'plugins_loaded', array( $this, 'compute_relevant_high_priority_experiments' ), 99 );
			add_action( 'parse_query', array( $this, 'compute_relevant_mid_priority_experiments' ), 99 );
			add_action( 'wp', array( $this, 'compute_relevant_low_priority_experiments' ), 99 );
			add_action( 'parse_query', array( $this, 'compute_relevant_heatmaps' ), 99 );
		}

		add_filter( 'get_canonical_url', array( $this, 'fix_canonical_url' ), 50 );
		add_filter( 'body_class', array( $this, 'maybe_add_variant_in_body' ) );
	}

	/**
	 * Returns relevant running experiments.
	 *
	 * @return list<Nelio_AB_Testing_Experiment> Array of relevant running experiments.
	 */
	public function get_relevant_running_experiments() {
		return array_merge(
			$this->experiments_by_priority['high'],
			$this->experiments_by_priority['mid'],
			$this->experiments_by_priority['low'],
			$this->experiments_by_priority['custom']
		);
	}

	/**
	 * Returns relevant running heatmaps.
	 *
	 * @return list<Nelio_AB_Testing_Heatmap> Array of relevant running heatmaps.
	 */
	public function get_relevant_running_heatmaps() {
		return $this->relevant_heatmaps;
	}

	/**
	 * Callback to tweak the canonical URL so that it always points to the original URL without any testing query args.
	 *
	 * @param string $url Original URL.
	 *
	 * @return string|false
	 */
	public function fix_canonical_url( $url ) {
		if ( is_singular() ) {
			return get_permalink();
		}
		$runtime = self::instance();
		return nab_get_requested_alternative() ? $runtime->get_untested_url() : $url;
	}

	/**
	 * Callback to add an additional `nab` and `nab-x` classes in the body when viewing tested content.
	 *
	 * @param list<string> $classes List of classes.
	 *
	 * @return list<string>
	 */
	public function maybe_add_variant_in_body( $classes ) {
		$runtime = self::instance();
		if ( ! $runtime->get_number_of_alternatives() ) {
			return $classes;
		}

		$alternative = nab_get_requested_alternative();
		$classes[]   = 'nab';
		$classes[]   = "nab-{$alternative}";
		return $classes;
	}

	/**
	 * Callback to add all the alternative loading hooks required by the given experiment.
	 *
	 * @param list<Nelio_AB_Testing_Experiment>|Nelio_AB_Testing_Experiment $experiments Experiments.
	 *
	 * @return void
	 */
	private function add_alternative_loading_hooks( $experiments ) {

		if ( ! is_array( $experiments ) ) {
			$experiments = array( $experiments );
		}

		$requested_alt = nab_get_requested_alternative();
		foreach ( $experiments as $experiment ) {

			$experiment_type = $experiment->get_type();

			$control      = $experiment->get_alternative( 'control' );
			$alternatives = $experiment->get_alternatives();
			$alternative  = $alternatives[ $requested_alt % count( $alternatives ) ];

			/**
			 * Fires when a certain alternative is about to be loaded as part of a split test.
			 *
			 * Use this action to add any hooks that your experiment type might require in order
			 * to properly load the alternative.
			 *
			 * @param TAlternative_Attributes|TControl_Attributes $alternative    attributes of the active alternative.
			 * @param TControl_Attributes                         $control        attributes of the control version.
			 * @param int                                         $experiment_id  experiment ID.
			 * @param string                                      $alternative_id alternative ID.
			 *
			 * @since 5.0.0
			 */
			do_action( "nab_{$experiment_type}_load_alternative", $alternative['attributes'], $control['attributes'], $experiment->get_id(), $alternative['id'] );

		}
	}

	/**
	 * Returns the current URL.
	 *
	 * @return string
	 */
	private function get_current_url() {
		return ! empty( $this->current_url ) ? $this->current_url : $this->compute_current_url();
	}

	/**
	 * Returns the untested URL.
	 *
	 * @return string
	 */
	public function get_untested_url() {
		return remove_query_arg( 'nab', $this->get_current_url() );
	}

	/**
	 * Returns the requested alternative.
	 *
	 * @return int
	 */
	public function get_alternative_from_request() {

		if ( $this->is_post_request() ) {
			if ( $this->is_tested_post_request() ) {
				return $this->get_nab_value_from_post_request();
			} else {
				return 0;
			}
		}

		$url         = $this->get_current_url();
		$alternative = $this->get_nab_query_arg( $url );
		if ( false === $alternative ) {
			$alternative = sanitize_text_field( wp_unslash( $_COOKIE['nabAlternative'] ?? '' ) );
			$alternative = absint( $alternative );
		}

		/**
		 * Filters the alternative that should be loaded for active tests.
		 *
		 * @param int|false $alternative Requested alternative.
		 *
		 * @since 7.5.2
		 */
		$alternative = apply_filters( 'nab_requested_alternative', $alternative );
		return absint( $alternative );
	}

	/**
	 * Returns whether the request method is POST and whether we're supposed to load alternative content or not.
	 *
	 * @return boolean whether the request method is POST and whether we're supposed to load alternative content or not.
	 */
	public function is_tested_post_request() {
		return (
			$this->is_post_request() &&
			$this->can_load_alternative_content_on_post_request() &&
			! empty( $this->get_nab_value_from_post_request() )
		);
	}

	/**
	 * Callback to compute high priority experiments.
	 *
	 * @return void
	 */
	public function compute_relevant_high_priority_experiments() {
		$this->compute_relevant_experiments( 'high' );
	}

	/**
	 * Callback to compute mid priority experiments.
	 *
	 * @param WP_Query $query The query.
	 *
	 * @return void
	 */
	public function compute_relevant_mid_priority_experiments( $query ) {
		if ( ! $query->is_main_query() ) {
			return;
		}
		remove_action( 'parse_query', array( $this, 'compute_relevant_mid_priority_experiments' ), 99 );
		$this->compute_relevant_experiments( 'mid' );
	}

	/**
	 * Callback to compute low priority experiments.
	 *
	 * @return void
	 */
	public function compute_relevant_low_priority_experiments() {
		$this->compute_relevant_experiments( 'low' );
	}

	/**
	 * Marks an experiment with custom priority as loaded.
	 *
	 * @param int $exp_id The experiment ID.
	 *
	 * @return void
	 *
	 * @since 7.0.6
	 */
	public function add_custom_priority_experiment( $exp_id ) {
		$exp = $this->get_custom_priority_experiment_or_die( $exp_id );
		$ids = wp_list_pluck( $this->experiments_by_priority['custom'], 'ID' );
		if ( in_array( $exp_id, $ids, true ) ) {
			return;
		}

		$this->experiments_by_priority['custom'][] = $exp;
		$this->add_alternative_loading_hooks( $exp );
	}

	/**
	 * Returns whether an experiment with custom priority is relevant or not.
	 *
	 * @param int $exp_id The experiment ID.
	 *
	 * @return boolean whether the experiment is relevant or not.
	 *
	 * @since 7.0.6
	 */
	public function is_custom_priority_experiment_relevant( $exp_id ) {
		$exp    = $this->get_custom_priority_experiment_or_die( $exp_id );
		$result = $this->filter_relevant_experiments( array( $exp ), 'custom' );
		return ! empty( $result );
	}

	/**
	 * Callback to enable experiments during a REST request.
	 *
	 * @return void
	 */
	public function enable_running_experiments_in_rest_request() {
		$rest_prefix = trailingslashit( rest_get_url_prefix() );
		$request_uri = sanitize_url( is_string( $_SERVER['REQUEST_URI'] ?? '' ) ? wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) : '' );
		$endpoint    = str_replace( $rest_prefix, '', $request_uri );
		if ( empty( $endpoint ) ) {
			return;
		}

		$experiments = array_filter(
			nab_get_running_experiments(),
			function ( $experiment ) use ( $endpoint ) {
				$experiment_type = $experiment->get_type();

				/**
				 * Filters whether the given experiment should be loaded in a REST request or not.
				 *
				 * @param boolean                     $loadable   whether experiment is loadable in REST request or not.
				 * @param string                      $endpoint   invoked endpoint on the REST API.
				 * @param Nelio_AB_Testing_Experiment $experiment the experiment.
				 *
				 * @since 7.5.1
				 */
				if ( apply_filters( "nab_is_{$experiment_type}_relevant_in_rest_request", false, $endpoint, $experiment ) ) {
					return true;
				}

				/**
				 * Filters whether the given experiment should be loaded in a REST request or not.
				 *
				 * @param boolean                     $loadable   whether experiment is loadable in REST request or not.
				 * @param string                      $endpoint   invoked endpoint on the REST API.
				 * @param Nelio_AB_Testing_Experiment $experiment the experiment.
				 *
				 * @since 7.5.1
				 */
				if ( apply_filters( 'nab_is_experiment_relevant_in_rest_request', false, $endpoint, $experiment ) ) {
					return true;
				}

				return false;
			}
		);
		$experiments = array_values( $experiments );

		$this->experiments_by_priority['custom'] = $experiments;
		$this->add_alternative_loading_hooks( $experiments );
	}

	/**
	 * Callback to enable experiments during an AJAX request.
	 *
	 * @return void
	 */
	public function enable_running_experiments_in_ajax_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ?? '' ) );
		if ( empty( $action ) ) {
			return;
		}

		$experiments = array_filter(
			nab_get_running_experiments(),
			function ( $experiment ) use ( $action ) {
				$experiment_type = $experiment->get_type();

				/**
				 * Filters whether the given experiment should be loaded in a REST request or not.
				 *
				 * @param boolean                     $loadable   whether experiment is loadable in REST request or not.
				 * @param Nelio_AB_Testing_Experiment $experiment the experiment.
				 *
				 * @since 7.5.1
				 */
				if ( apply_filters( "nab_is_{$experiment_type}_relevant_in_{$action}_ajax_request", false, $experiment ) ) {
					return true;
				}

				/**
				 * Filters whether the given experiment should be loaded in a REST request or not.
				 *
				 * @param boolean                     $loadable   whether experiment is loadable in REST request or not.
				 * @param Nelio_AB_Testing_Experiment $experiment the experiment.
				 *
				 * @since 7.5.1
				 */
				if ( apply_filters( "nab_is_experiment_relevant_in_{$action}_ajax_request", false, $experiment ) ) {
					return true;
				}

				/**
				 * Filters whether the given experiment should be loaded in a REST request or not.
				 *
				 * @param boolean                     $loadable   whether experiment is loadable in REST request or not.
				 * @param string                      $action     name of the AJAX action.
				 * @param Nelio_AB_Testing_Experiment $experiment the experiment.
				 *
				 * @since 7.5.1
				 */
				if ( apply_filters( "nab_is_{$experiment_type}_relevant_in_ajax_request", false, $action, $experiment ) ) {
					return true;
				}

				/**
				 * Filters whether the given experiment should be loaded in a REST request or not.
				 *
				 * @param boolean                     $loadable   whether experiment is loadable in REST request or not.
				 * @param string                      $action     name of the AJAX action.
				 * @param Nelio_AB_Testing_Experiment $experiment the experiment.
				 *
				 * @since 7.5.1
				 */
				if ( apply_filters( 'nab_is_experiment_relevant_in_ajax_request', false, $action, $experiment ) ) {
					return true;
				}

				return false;
			}
		);
		$experiments = array_values( $experiments );

		$this->experiments_by_priority['custom'] = $experiments;
		$this->add_alternative_loading_hooks( $experiments );
	}

	/**
	 * Callback to compute relevant heatmaps.
	 *
	 * @param WP_Query $query The query.
	 *
	 * @return void
	 */
	public function compute_relevant_heatmaps( $query ) {

		if ( ! $query->is_main_query() ) {
			return;
		}
		remove_action( 'parse_query', array( $this, 'compute_relevant_heatmaps' ), 99 );

		$untested_url = $this->get_untested_url();

		$this->relevant_heatmaps = array_values(
			array_filter(
				nab_get_running_heatmaps(),
				function ( $heatmap ) use ( $untested_url ) {
					if ( 'url' !== $heatmap->get_tracking_mode() ) {
						return nab_get_queried_object_id() === $heatmap->get_tracked_post_id();
					}

					$rule = array(
						'type'  => 'exact',
						'value' => $heatmap->get_tracked_url(),
					);
					return nab_does_rule_apply_to_url( $rule, $untested_url );
				}
			)
		);

		/**
		 * Fires after determining the list of relevant heatmaps.
		 *
		 * @param list<Nelio_AB_Testing_Heatmap> $heatmaps list of relevant heatmaps.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_relevant_heatmaps_loaded', $this->relevant_heatmaps );
	}

	/**
	 * Returns the number of combined alternatives.
	 *
	 * @return int
	 */
	public function get_number_of_alternatives() {

		$gcd = function ( int $n, int $m ) use ( &$gcd ): int {
			if ( 0 === $n || 0 === $m ) {
				return 1;
			}
			if ( $n === $m && $n > 1 ) {
				return $n;
			}
			return $m < $n ? $gcd( $n - $m, $n ) : $gcd( $n, $m - $n );
		};

		$lcm = function ( int $n, int $m ) use ( &$gcd ): int {
			return $m * ( $n / $gcd( $n, $m ) );
		};

		$experiments = $this->get_relevant_running_experiments();
		$alt_counts  = array_values( array_unique( array_map( fn( $e ) => count( $e->get_alternatives() ), $experiments ) ) );
		if ( empty( $alt_counts ) ) {
			return 0;
		}

		return array_reduce( $alt_counts, $lcm, 1 );
	}

	/**
	 * Computes the list of relevant experiments for the given priority.
	 *
	 * @param 'low'|'mid'|'high' $priority Priority.
	 *
	 * @return void
	 */
	private function compute_relevant_experiments( $priority ) {
		$experiments = $this->filter_relevant_experiments( nab_get_running_experiments(), $priority );

		/**
		 * Filters the list of `$priority` (either `high`, `mid`, or `low`) priority experiments.
		 *
		 * @param list<Nelio_AB_Testing_Experiment> $experiments list of `$priority` priority experiments.
		 *
		 * @since 7.0.0
		 */
		$experiments = apply_filters( "nab_relevant_{$priority}_priority_experiments", $experiments );

		$this->experiments_by_priority[ $priority ] = $experiments;
		$this->add_alternative_loading_hooks( $experiments );
	}

	/**
	 * Whether the current request is a POST request or not.
	 *
	 * @return bool
	 */
	private function is_post_request() {
		return (
			! nab_is_rest_api_request() &&
			! wp_doing_ajax() &&
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'POST' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) )
		);
	}

	/**
	 * Whether the plugin can attempt to load alternative content when processing a post request or not.
	 *
	 * @return bool
	 */
	private function can_load_alternative_content_on_post_request() {

		/**
		 * Filters whether the plugin can attempt to load alternative content when processing a post request or not.
		 *
		 * @param boolean $can_load whether the plugin can attempt to load alternative content when processing a post request or not. Default: `true`.
		 *
		 * @since 5.0.10
		 */
		return apply_filters( 'nab_can_load_alternative_content_on_post_request', true );
	}

	/**
	 * Returns the experiments that are active in the current request from those provided.
	 *
	 * @param list<Nelio_AB_Testing_Experiment> $experiments List of experiments.
	 * @param 'low'|'mid'|'high'|'custom'       $priority    Priority.
	 *
	 * @return list<Nelio_AB_Testing_Experiment>
	 */
	private function filter_relevant_experiments( $experiments, $priority ) {

		$relevant_experiments = array_filter(
			$experiments,
			function ( $experiment ) use ( $priority ) {

				$experiment_id   = $experiment->get_id();
				$experiment_type = $experiment->get_type();
				$control         = $experiment->get_alternative( 'control' );

				/**
				 * Filters the experiment priority, which specifies the moment at which an experiment’s relevance will be computed.
				 *
				 * @param 'low'|'mid'|'high'|'custom' $priority      Experiment priority. Default: `low`.
				 * @param TControl_Attributes         $control       original version.
				 * @param int                         $experiment_id id of the experiment.
				 *
				 * @since 7.0.0
				 */
				if ( apply_filters( "nab_{$experiment_type}_experiment_priority", 'low', $control['attributes'], $experiment_id ) !== $priority ) {
					return false;
				}

				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$args = $_GET;
				/** @var array<string,mixed> $args */
				$context = array(
					'url'    => $this->get_untested_url(),
					'args'   => $args,
					'postId' => nab_get_queried_object_id(),
				);
				if ( nab_is_experiment_relevant( $context, $experiment ) ) {
					return true;
				}

				return false;
			}
		);

		return array_values( $relevant_experiments );
	}

	/**
	 * Gets nab value from post request or cookie.
	 *
	 * @return int
	 */
	private function get_nab_value_from_post_request() {
		$cookie = sanitize_text_field( wp_unslash( $_COOKIE['nabAlternative'] ?? '' ) );
		$cookie = ! empty( $cookie ) ? $cookie : false;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$result = sanitize_text_field( wp_unslash( $_REQUEST['nab'] ?? '' ) );
		$result = ! empty( $result ) ? $result : $cookie;
		$result = 'none' === $result ? false : $result;
		return absint( $result );
	}

	/**
	 * Gets nab value from query args.
	 *
	 * @param string $url A URL.
	 *
	 * @return int|false
	 */
	private function get_nab_query_arg( $url ) {
		if ( 'redirection' !== nab_get_variant_loading_strategy() ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return absint( $_REQUEST['nab'] ?? '' );
		}

		$query = wp_parse_url( $url, PHP_URL_QUERY );
		$query = is_string( $query ) ? wp_parse_args( $query ) : array();
		$value = $query['nab'] ?? false;
		return false === $value ? false : absint( $value );
	}

	/**
	 * Computes (and returns) the current URL.
	 *
	 * @return string
	 */
	private function compute_current_url() {

		// “nab” query var and WordPress’ default public query vars (see class-wp.php).
		$query_vars = array( 'nab', 'm', 'p', 'posts', 'w', 'cat', 'withcomments', 'withoutcomments', 's', 'search', 'exact', 'sentence', 'calendar', 'page', 'paged', 'more', 'tb', 'pb', 'author', 'order', 'orderby', 'year', 'monthnum', 'day', 'hour', 'minute', 'second', 'name', 'category_name', 'tag', 'feed', 'author_name', 'pagename', 'page_id', 'error', 'attachment', 'attachment_id', 'subpost', 'subpost_id', 'preview', 'robots', 'taxonomy', 'term', 'cpage', 'post_type', 'embed', 'wc-ajax' );

		/**
		 * Filters public query vars.
		 *
		 * @param list<string> $query_vars public query vars.
		 *
		 * @since 5.0.6
		 */
		$query_vars = apply_filters( 'nab_query_vars', $query_vars );

		$url = nab_home_url( $this->get_clean_request_uri() );
		/**
		 * Filters current URL.
		 *
		 * @param string $url current URL.
		 *
		 * @since 8.1.0
		 */
		$url = apply_filters( 'nab_current_url', $url );

		$query = wp_parse_url( $url, PHP_URL_QUERY );
		$query = is_string( $query ) ? wp_parse_args( $query ) : array();
		$query = array_filter(
			$query,
			function ( $key ) use ( $query_vars ) {
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
		}

		$this->current_url = $url;
		return $url;
	}

	/**
	 * Returns a clean version of the request URI.
	 *
	 * @return string
	 */
	private function get_clean_request_uri() {

		$request_uri             = sanitize_url( is_string( $_SERVER['REQUEST_URI'] ?? '' ) ? wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) : '' );
		$request_uri_in_home_url = preg_replace( '/^https?:\/\/[^\/]+/', '', nab_home_url() );
		$request_uri_in_home_url = is_string( $request_uri_in_home_url ) ? $request_uri_in_home_url : '';

		$request_uri             = '/' . ltrim( $request_uri, '/' );
		$request_uri_in_home_url = '/' . ltrim( $request_uri_in_home_url, '/' );

		if ( 0 !== strpos( $request_uri, $request_uri_in_home_url ) ) {
			return $request_uri;
		}

		$request_uri = substr( $request_uri, strlen( $request_uri_in_home_url ) );
		if ( 0 < strlen( $request_uri ) && '/' !== $request_uri[0] ) {
			$request_uri = '/' . $request_uri;
		}

		return $request_uri;
	}

	/**
	 * Returns the requested custom priority experiment or dies.
	 *
	 * @param int $exp_id Experiment ID.
	 *
	 * @return Nelio_AB_Testing_Experiment|never
	 */
	private function get_custom_priority_experiment_or_die( $exp_id ) {
		$exps = nab_get_running_experiments();
		/** @var list<int> */
		$ids  = wp_list_pluck( $exps, 'ID' );
		$exps = array_combine( $ids, $exps );
		if ( ! isset( $exps[ $exp_id ] ) ) {
			/* translators: %d: Experiment ID. */
			wp_die( sprintf( esc_html_x( 'Custom priority experiment %d not found', 'text', 'nelio-ab-testing' ), esc_html( $exp_id ) ) );
		}

		return $exps[ $exp_id ];
	}
}
