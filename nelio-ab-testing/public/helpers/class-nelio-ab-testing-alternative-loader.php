<?php
/**
 * This class adds the required scripts in the front-end to enable alternative loading.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public/helpers
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds the required scripts in the front-end to enable alternative loading.
 */
class Nelio_AB_Testing_Alternative_Loader {

	protected static $instance;

	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	public function init() {
		add_action( 'nab_relevant_high_priority_experiments_loaded', array( $this, 'add_alternative_loading_hooks' ) );
		add_action( 'nab_relevant_mid_priority_experiments_loaded', array( $this, 'add_alternative_loading_hooks' ) );
		add_action( 'nab_relevant_low_priority_experiments_loaded', array( $this, 'add_alternative_loading_hooks' ) );
		add_action( 'nab_relevant_custom_priority_experiments_loaded', array( $this, 'add_alternative_loading_hooks' ) );
		add_action( 'wp_head', array( $this, 'maybe_add_overlay' ), 1 );
		add_action( 'get_canonical_url', array( $this, 'fix_canonical_url' ), 50 );
		add_action( 'body_class', array( $this, 'maybe_add_variant_in_body' ) );
	}//end init()

	public function maybe_add_overlay() {
		if ( nab_is_split_testing_disabled() ) {
			return;
		}//end if

		$runtime     = Nelio_AB_Testing_Runtime::instance();
		$experiments = $runtime->get_relevant_running_experiments();
		if ( empty( $experiments ) ) {
			return;
		}//end if

		nab_print_loading_overlay();
	}//end maybe_add_overlay()

	public function fix_canonical_url( $url ) {
		if ( is_singular() ) {
			return get_permalink();
		}//end if
		$runtime       = Nelio_AB_Testing_Runtime::instance();
		$requested_alt = $runtime->get_alternative_from_request();
		return $requested_alt ? $runtime->get_untested_url() : $url;
	}//end fix_canonical_url()

	public function maybe_add_variant_in_body( $classes ) {
		$runtime = Nelio_AB_Testing_Runtime::instance();
		$count   = $this->get_number_of_alternatives();
		$alt     = $runtime->get_alternative_from_request();
		if ( ! empty( $count ) ) {
			$classes[] = 'nab';
			$classes[] = "nab-{$alt}";
		}//end if
		return $classes;
	}//end maybe_add_variant_in_body()

	public function add_alternative_loading_hooks( $experiments ) {

		if ( ! is_array( $experiments ) ) {
			$experiments = array( $experiments );
		}//end if

		$runtime       = Nelio_AB_Testing_Runtime::instance();
		$requested_alt = $runtime->get_alternative_from_request();

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
			 * @param array  $alternative    attributes of the active alternative.
			 * @param array  $control        attributes of the control version.
			 * @param int    $experiment_id  experiment ID.
			 * @param string $alternative_id alternative ID.
			 *
			 * @since 5.0.0
			 */
			do_action( "nab_{$experiment_type}_load_alternative", $alternative['attributes'], $control['attributes'], $experiment->get_id(), $alternative['id'] );

		}//end foreach
	}//end add_alternative_loading_hooks()

	public function get_number_of_alternatives() {

		$gcd = function ( $n, $m ) use ( &$gcd ) {
			if ( 0 === $n || 0 === $m ) {
				return 1;
			}//end if
			if ( $n === $m && $n > 1 ) {
				return $n;
			}//end if
			return $m < $n ? $gcd( $n - $m, $n ) : $gcd( $n, $m - $n );
		};

		$lcm = function ( $n, $m ) use ( &$gcd ) {
			return $m * ( $n / $gcd( $n, $m ) );
		};

		$runtime      = Nelio_AB_Testing_Runtime::instance();
		$experiments  = $runtime->get_relevant_running_experiments();
		$alternatives = array_unique(
			array_map(
				function ( $experiment ) {
					return count( $experiment->get_alternatives() );
				},
				$experiments
			)
		);

		if ( empty( $alternatives ) ) {
			return 0;
		}//end if

		return array_reduce( $alternatives, $lcm, 1 );
	}//end get_number_of_alternatives()
}//end class
