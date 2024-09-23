<?php
/**
 * Some helper functions to work with experiments.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The admin-specific functionality of the plugin.
 */
class Nelio_AB_Testing_Experiment_Helper {

	protected static $instance;

	private $running_experiments;
	private $running_heatmaps;
	private $full_preview_urls;
	private $partial_url_to_preview_url_list;

	public static function instance() {

		if ( is_null( self::$instance ) ) {

			self::$instance = new self();

			self::$instance->running_experiments             = false;
			self::$instance->running_heatmaps                = false;
			self::$instance->full_preview_urls               = array();
			self::$instance->partial_url_to_preview_url_list = array();

		}//end if

		return self::$instance;

	}//end instance()

	public function get_non_empty_name( $experiment ) {

		$name = trim( $experiment->get_name() );
		$id   = $experiment->get_id();

		if ( empty( $name ) ) {
			return "{$id}";
		}//end if

		$pattern = '“%s” (%d)';
		return sprintf( $pattern, $name, $id );

	}//end get_non_empty_name()

	public function get_running_experiments() {

		if ( false !== $this->running_experiments ) {
			return $this->running_experiments;
		}//end if

		$this->running_experiments = array_map(
			function( $experiment_id ) {
				return Nelio_AB_Testing_Experiment::get_experiment( $experiment_id );
			},
			nab_get_running_experiment_ids()
		);

		return $this->running_experiments;

	}//end get_running_experiments()

	public function get_running_heatmaps() {

		if ( false !== $this->running_heatmaps ) {
			return $this->running_heatmaps;
		}//end if

		$this->running_heatmaps = array_map(
			function( $experiment_id ) {
				return Nelio_AB_Testing_Experiment::get_experiment( $experiment_id );
			},
			nab_get_running_heatmap_ids()
		);

		return $this->running_heatmaps;

	}//end get_running_heatmaps()

	public function does_overlap_with_running_experiment( $candidate ) {

		/**
		 * Filters whether scope overlap detection should be ignored when starting a new test.
		 *
		 * @param boolean $ignore     if overlap detection should be ignored should run or not. Default: `false`.
		 * @param object  $experiment the experiment we're trying to start.
		 *
		 * @since 5.3.3
		 */
		if ( apply_filters( 'nab_skip_scope_overlap_detection', false, $candidate ) ) {
			return false;
		}//end if

		$experiments = array_values(
			array_filter(
				$this->get_running_experiments(),
				function( $experiment ) use ( &$candidate ) {
					return $experiment->get_type() === $candidate->get_type() && $experiment->get_id() !== $candidate->get_id();
				}
			)
		);

		foreach ( $experiments as $experiment ) {
			if ( $this->is_tested_element_the_same( $experiment, $candidate ) ) {
				if ( $this->do_scopes_overlap( $experiment, $candidate ) ) {
					return $experiment;
				}//end if
			}//end if
			if ( $this->do_post_alternatives_overlap( $experiment, $candidate ) ) {
				return $experiment;
			}//end if
		}//end foreach

		return false;

	}//end does_overlap_with_running_experiment()

	public function get_preview_url_from_scope( $scope, $experiment_id, $alternative_id ) {

		if ( empty( $experiment_id ) || empty( $alternative_id ) ) {
			return false;
		}//end if

		$url = nab_home_url();
		if ( ! empty( $scope ) ) {
			$url = $this->find_preview_url_in_scope( $scope );
		}//end if

		if ( $url ) {
			return $url;
		}//end if

		if ( 'control' === $alternative_id ) {
			return nab_home_url();
		}//end if

		return false;

	}//end get_preview_url_from_scope()

	private function find_preview_url_in_scope( $scope ) {

		$url = $this->find_exact_local_url_in_scope( $scope );
		if ( $url ) {
			return $url;
		}//end if

		$url = $this->find_local_url_in_scope_from_partial_specification( $scope );
		if ( $url ) {
			return $url;
		}//end if

		return false;

	}//end find_preview_url_in_scope()

	public function find_local_url_in_scope_from_partial_specification( $scope ) {

		$scope = array_filter(
			$scope,
			function( $candidate ) {
				return 'partial' === $candidate['attributes']['type'];
			}
		);

		$partials = array_map(
			function( $candidate ) {
				return $candidate['attributes']['value'];
			},
			$scope
		);

		foreach ( $partials as $partial ) {

			if ( isset( $this->partial_url_to_preview_url_list[ $partial ] ) ) {
				$url = $this->partial_url_to_preview_url_list[ $partial ];
				if ( $url ) {
					return $url;
				} elseif ( false === $url ) {
					continue;
				}//end if
			}//end if

			$url = $this->find_url_from_partial( $partial );
			$this->partial_url_to_preview_url_list[ $partial ] = $url;
			if ( $url ) {
				return $url;
			}//end if
		}//end foreach

		return false;

	}//end find_local_url_in_scope_from_partial_specification()

	/**
	 * Checks all running experiments and adds alternative post IDs to the given IDs.
	 *
	 * @param array $ids list of post IDs.
	 *
	 * @return array list of post IDs (including variants).
	 *
	 * @since 6.0.4
	 */
	public function add_alternative_post_ids( $ids ) {
		$alt_ids = $this->get_alternative_post_ids();
		$result  = array();

		foreach ( $ids as $id ) {
			$result = array_merge(
				$result,
				nab_array_get( $alt_ids, $id, array( $id ) )
			);
		}//end foreach

		return $result;
	}//end add_alternative_post_ids()

	private function find_exact_local_url_in_scope( $scope ) {

		$scope = array_filter(
			$scope,
			function( $candidate ) {
				return 'exact' === $candidate['attributes']['type'];
			}
		);

		$urls = array_map(
			function( $candidate ) {
				return $candidate['attributes']['value'];
			},
			$scope
		);

		foreach ( $urls as $url ) {

			if ( isset( $this->full_preview_urls[ $url ] ) ) {
				if ( $this->full_preview_urls[ $url ] ) {
					return $this->full_preview_urls[ $url ];
				} else {
					continue;
				}//end if
			}//end if

			if ( ! $this->is_local_url( $url ) ) {
				$this->full_preview_urls[ $url ] = false;
				continue;
			}//end if

			$clean_url = $this->clean_url( $url );
			if ( ! $this->is_url_valid( $clean_url ) ) {
				$this->full_preview_urls[ $url ] = false;
				continue;
			}//end if

			$this->full_preview_urls[ $url ] = $clean_url;
			return $clean_url;

		}//end foreach

		return false;

	}//end find_exact_local_url_in_scope()

	private function is_local_url( $url ) {

		$clean_home_url = preg_replace( '/^https?:/', '', nab_home_url() );
		$url            = preg_replace( '/^https?:/', '', $url );
		return 0 === strpos( $url, $clean_home_url );

	}//end is_local_url()

	private function clean_url( $url ) {

		$clean_home_url = preg_replace( '/^https?:/', '', nab_home_url() );
		$url            = preg_replace( '/^https?:/', '', $url );
		return nab_home_url() . str_replace( $clean_home_url, '', $url );

	}//end clean_url()

	private function is_url_valid( $url ) {

		/**
		 * Filters whether the plugin should check if the given URL exists or not.
		 *
		 * @param boolean $check if the check should run or not. Default: `false`.
		 * @param string  $url   the URL on which the check should run.
		 *
		 * @since 5.0.0
		 */
		if ( ! apply_filters( 'nab_check_validity_of_preview_url', false, $url ) ) {
			return true;
		}//end if

		$response = wp_remote_head( $url );
		if ( is_wp_error( $response ) ) {
			return false;
		}//end if

		return in_array( wp_remote_retrieve_response_code( $response ), array( 200, 301, 302 ), true );

	}//end is_url_valid()

	private function find_url_from_partial( $partial ) {

		if ( $this->seems_valid_full_url( $partial ) ) {
			$url = $this->get_full_url_from_partial( $partial );
			if ( ! empty( $url ) ) {
				return $url;
			}//end if
		}//end if

		$post_name = '%' . $partial . '%';
		$post_name = preg_replace( '/^%\//', '', $post_name );
		$post_name = preg_replace( '/\/%$/', '', $post_name );

		if ( 0 <= strpos( $post_name, '/' ) ) {
			$post_name = preg_replace( '/.*\/([^\/]*)/', '$1', $post_name );
		}//end if

		$url = $this->find_url_from_post_name( $post_name );
		if ( -1 === strpos( $url, $partial ) ) {
			return false;
		}//end if

		return $url;

	}//end find_url_from_partial()

	private function find_url_from_post_name( $name ) {

		$key       = "nab_permalink_for_$name";
		$permalink = wp_cache_get( $key );
		if ( $permalink ) {
			return $permalink;
		}//end if

		global $wpdb;
		$result    = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				"SELECT ID, post_type
				 FROM $wpdb->posts
				 WHERE
				   post_status IN ( 'publish', 'draft' ) AND
				   post_name LIKE %s
				 LIMIT 1",
				$this->esc_like( $name )
			)
		);
		$permalink = false;

		if ( ! empty( $result ) ) {
			$result = $result[0];
			if ( 'page' === $result->post_type ) {
				$permalink = get_page_link( $result->ID );
			} else {
				$permalink = get_permalink( $result->ID );
			}//end if
		}//end if

		wp_cache_set( $key, $permalink );
		return $permalink;

	}//end find_url_from_post_name()

	private function esc_like( $value ) {

		$value = explode( '%', $value );

		global $wpdb;
		$value = array_map(
			function( $fragment ) use ( $wpdb ) {
				return $wpdb->esc_like( $fragment );
			},
			$value
		);

		$value = implode( '%', $value );
		return $value;

	}//end esc_like()

	private function is_tested_element_the_same( $one_experiment, $another_experiment ) {

		$one_control     = $one_experiment->get_alternative( 'control' );
		$another_control = $another_experiment->get_alternative( 'control' );

		if ( ! isset( $one_control['attributes'] ) || ! isset( $another_control['attributes'] ) ) {
			return false;
		}//end if

		if ( empty( $one_control['attributes'] ) && empty( $another_control['attributes'] ) ) {
			return true;
		}//end if

		return wp_json_encode( $one_control['attributes'] ) === wp_json_encode( $another_control['attributes'] );

	}//end is_tested_element_the_same()

	private function do_post_alternatives_overlap( $exp_a, $exp_b ) {
		$ids_a   = $this->get_post_ids( $exp_a );
		$ids_b   = $this->get_post_ids( $exp_b );
		$all_ids = array_merge( $ids_a, $ids_b );
		return count( array_unique( $all_ids ) ) < count( $all_ids );
	}//end do_post_alternatives_overlap()

	private function get_post_ids( $exp ) {
		$alts = $exp->get_alternatives();
		$alts = wp_list_pluck( $alts, 'attributes' );
		$alts = array_filter(
			$alts,
			function( $alt ) {
				return isset( $alt['postId'] );
			}
		);
		$ids  = wp_list_pluck( $alts, 'postId' );
		return array_values( array_filter( $ids ) );
	}//end get_post_ids()

	private function do_scopes_overlap( $one_experiment, $another_experiment ) {
		$one_scope     = $one_experiment->get_scope();
		$another_scope = $another_experiment->get_scope();

		if ( empty( $one_scope ) || empty( $another_scope ) ) {
			return true;
		}//end if

		foreach ( $one_scope as $one_rule ) {
			$one_url = $this->get_url_from_scope_rule( $one_experiment, $one_rule );
			foreach ( $another_scope as $another_rule ) {
				$another_url = $this->get_url_from_scope_rule( $another_experiment, $another_rule );
				if ( $this->do_scope_urls_overlap( $one_url, $another_url ) ) {
					return true;
				}//end if
			}//end foreach
		}//end foreach

		return false;
	}//end do_scopes_overlap()

	private function get_url_from_scope_rule( $experiment, $rule ) {

		if ( 'tested-post' === $rule['attributes']['type'] ) {
			$post_id = $experiment->get_tested_element();
			return array(
				'type' => 'exact',
				'url'  => get_permalink( $post_id ),
			);
		}//end if

		return array(
			'type' => 'exact' === $rule['attributes']['type'] ? 'exact' : 'partial',
			'url'  => $rule['attributes']['value'],
		);

	}//end get_url_from_scope_rule()

	private function do_scope_urls_overlap( $a, $b ) {
		if ( empty( $a['url'] ) || empty( $b['url'] ) ) {
			return false;
		}//end if

		if ( 'exact' === $a['type'] && 'exact' === $b['type'] ) {
			return $a['url'] === $b['url'];
		}//end if

		if ( 'partial' === $a['type'] ) {
			return false !== stripos( $b['url'], $a['url'] );
		}//end if

		if ( 'partial' === $b['type'] ) {
			return false !== stripos( $a['url'], $b['url'] );
		}//end if

		return false;
	}//end do_scope_urls_overlap()

	private function seems_valid_full_url( $partial ) {

		if ( 0 === strpos( $partial, 'http://' ) ) {
			return true;
		}//end if

		if ( 0 === strpos( $partial, 'https://' ) ) {
			return true;
		}//end if

		return false;

	}//end seems_valid_full_url()

	private function get_full_url_from_partial( $partial ) {

		$post_id = nab_url_to_postid( $partial );
		if ( $post_id ) {
			return get_permalink( $post_id );
		}//end if

		return false;

	}//end get_full_url_from_partial()

	private function get_alternative_post_ids() {
		$result = array();

		$runtime     = Nelio_AB_Testing_Runtime::instance();
		$experiments = $runtime->get_relevant_running_experiments();
		foreach ( $experiments as $experiment ) {
			$experiment_type = $experiment->get_type();

			/**
			 * Filters the name of the attribute (if any) that contains an alternative post ID. If none, return `false`.
			 *
			 * @param boolean|string $alt_post_attr name of the attribute that contains an alternative post ID. `false` otherwise.
			 *
			 * @since 5.2.7
			 */
			$alt_post_attr = apply_filters( "nab_{$experiment_type}_alternative_post_attribute", false );
			if ( $alt_post_attr ) {
				$alternatives = wp_list_pluck( $experiment->get_alternatives(), 'attributes' );
				$post_ids     = wp_list_pluck( $alternatives, $alt_post_attr );
				$post_ids     = array_values( array_filter( array_map( 'absint', $post_ids ) ) );
				if ( ! empty( $post_ids ) ) {
					$result[ $post_ids[0] ] = $post_ids;
				}//end if
			}//end if
		}//end foreach

		return $result;
	}//end get_alternative_post_ids()

}//end class
