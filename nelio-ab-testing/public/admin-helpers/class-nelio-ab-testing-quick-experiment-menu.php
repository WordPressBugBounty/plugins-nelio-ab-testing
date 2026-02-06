<?php
/**
 * This class adds an option in the admin bar to quickly create, view, and
 * edit tests.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public/admin-helpers
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds the required script for previewing CSS snippets.
 */
class Nelio_AB_Testing_Quick_Experiment_Menu {

	/**
	 * This instance.
	 *
	 * @var Nelio_AB_Testing_Quick_Experiment_Menu|null
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Quick_Experiment_Menu
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
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
		if ( is_admin() ) {
			return;
		}
		add_action( 'wp_enqueue_scripts', array( $this, 'add_admin_bar_menu_script' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu_option' ), 99 );
	}

	/**
	 * Callback to add the admin bar menu script.
	 *
	 * @return void
	 */
	public function add_admin_bar_menu_script() {

		if ( ! current_user_can( 'edit_nab_experiments' ) ) {
			return;
		}

		$settings = array(
			'postId'         => is_singular() ? get_the_ID() : 0,
			'postType'       => is_singular() ? get_post_type() : false,
			'experimentType' => $this->get_type_for_new_experiment(),
			'currentUrl'     => $this->get_current_url(),
			'root'           => esc_url_raw( rest_url() ),
			'nonce'          => wp_create_nonce( 'wp_rest' ),
		);

		nab_enqueue_script_with_auto_deps(
			'nab-quick-actions',
			'quick-actions',
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_add_inline_script(
			'nab-quick-actions',
			sprintf(
				'window.nabQuickActionSettings=%s;',
				wp_json_encode( $settings )
			),
			'before'
		);
	}

	/**
	 * Callback to add items to admin bar.
	 *
	 * @param WP_Admin_Bar $admin_bar admin bar.
	 *
	 * @return void
	 */
	public function add_admin_bar_menu_option( $admin_bar ) {

		$this->maybe_add_overview_option( $admin_bar );
		$this->add_options_for_singular_post( $admin_bar );
		$this->add_heatmap_option_in_admin_bar( $admin_bar );

		$nodes = $admin_bar->get_nodes();
		$nodes = ! empty( $nodes ) ? $nodes : array();
		$nodes = wp_list_pluck( $nodes, 'parent' );
		$nodes = ! empty( $nodes ) ? $nodes : array();
		if ( ! in_array( 'nelio-ab-testing', $nodes, true ) ) {
			return;
		}

		$title = sprintf(
			'<span style="display:flex;height:100%%;">%s<span class="screen-reader-text">Nelio A/B Testing</span></span>',
			$this->get_admin_bar_logo()
		);

		$admin_bar->add_menu(
			array(
				'id'    => 'nelio-ab-testing',
				'title' => $title,
				'href'  => false,
				'meta'  => array(
					'title' => 'Nelio A/B Testing',
				),
			)
		);
	}

	/**
	 * Adds overview page menu item in admin bar.
	 *
	 * @param WP_Admin_Bar $admin_bar admin bar.
	 *
	 * @return void
	 */
	private function maybe_add_overview_option( $admin_bar ) {
		if ( ! current_user_can( 'read_nab_results' ) ) {
			return;
		}

		$admin_bar->add_menu(
			array(
				'id'     => 'nelio-ab-testing-overview',
				'parent' => 'nelio-ab-testing',
				'title'  => _x( 'Overview', 'text', 'nelio-ab-testing' ),
				'href'   => admin_url( 'admin.php?page=nelio-ab-testing-overview' ),
			)
		);
	}

	/**
	 * Adds test options in admin bar.
	 *
	 * @param WP_Admin_Bar $admin_bar admin bar.
	 *
	 * @return void
	 */
	private function add_options_for_singular_post( $admin_bar ) {

		$experiment_type = $this->get_type_for_new_experiment();
		if ( empty( $experiment_type ) ) {
			return;
		}

		$post_id    = absint( get_the_ID() );
		$experiment = $this->get_relevant_experiment( $post_id, $experiment_type );
		if ( empty( $experiment ) ) {
			if ( current_user_can( 'edit_nab_experiments' ) ) {
				$admin_bar->add_menu(
					array(
						'id'     => 'nelio-ab-testing-experiment-create',
						'parent' => 'nelio-ab-testing',
						'title'  => _x( 'Create New A/B Test', 'command', 'nelio-ab-testing' ),
						'href'   => '#',
					)
				);
			}
			return;
		}

		if ( 'running' === $experiment->get_status() ) {
			if ( current_user_can( 'read_nab_results' ) ) {
				$admin_bar->add_menu(
					array(
						'id'     => 'nelio-ab-testing-experiment-view',
						'parent' => 'nelio-ab-testing',
						'title'  => _x( 'View Running Test', 'command', 'nelio-ab-testing' ),
						'href'   => $experiment->get_url(),
					)
				);
			}
			return;
		}

		if ( current_user_can( 'edit_nab_experiments' ) ) {
			$admin_bar->add_menu(
				array(
					'id'     => 'nelio-ab-testing-experiment-edit',
					'parent' => 'nelio-ab-testing',
					'title'  => _x( 'Edit A/B Test', 'command', 'nelio-ab-testing' ),
					'href'   => $experiment->get_url(),
				)
			);
		}
	}

	/**
	 * Adds heatmap option in admin bar.
	 *
	 * @param WP_Admin_Bar $admin_bar admin bar.
	 *
	 * @return void
	 */
	private function add_heatmap_option_in_admin_bar( $admin_bar ) {

		if ( is_singular() ) {
			$heatmap = $this->get_relevant_heatmap_using_post_id( absint( get_the_ID() ) );
		} else {
			$url     = $this->get_current_url();
			$heatmap = $this->get_relevant_heatmap_using_url( $url );
			if ( ! $heatmap && trailingslashit( $url ) !== $url ) {
				$heatmap = $this->get_relevant_heatmap_using_url( trailingslashit( $url ) );
			}
		}

		if ( ! $heatmap ) {
			if ( current_user_can( 'edit_nab_experiments' ) ) {
				$admin_bar->add_menu(
					array(
						'id'     => 'nelio-ab-testing-heatmap-create',
						'parent' => 'nelio-ab-testing',
						'title'  => _x( 'Create New Heatmap', 'command', 'nelio-ab-testing' ),
						'href'   => '#',
					)
				);
			}
			return;
		}

		if ( 'running' === $heatmap->get_status() ) {
			if ( current_user_can( 'read_nab_results' ) ) {
				$admin_bar->add_menu(
					array(
						'id'     => 'nelio-ab-testing-heatmap',
						'parent' => 'nelio-ab-testing',
						'title'  => _x( 'View Heatmap', 'command', 'nelio-ab-testing' ),
						'href'   => $heatmap->get_url(),
					)
				);
			}
			return;
		}

		if ( current_user_can( 'edit_nab_experiments' ) ) {
			$admin_bar->add_menu(
				array(
					'id'     => 'nelio-ab-testing-heatmap-edit',
					'parent' => 'nelio-ab-testing',
					'title'  => _x( 'Edit Heatmap', 'command', 'nelio-ab-testing' ),
					'href'   => $heatmap->get_url(),
				)
			);
		}
	}

	/**
	 * Returns the relevant experiment.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $type    Experiment type.
	 *
	 * @return Nelio_AB_Testing_Experiment|false
	 */
	private function get_relevant_experiment( $post_id, $type ) {

		$meta_args = array(
			'relation' => 'AND',
			array(
				'key'     => '_nab_experiment_type',
				'value'   => $type,
				'compare' => '=',
			),
			array(
				'key'     => '_nab_tested_post_id',
				'value'   => $post_id,
				'compare' => '=',
			),
		);

		return $this->get_relevant_experiment_using_meta_args( $meta_args );
	}

	/**
	 * Returns the relevant heatmap.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return Nelio_AB_Testing_Experiment|false
	 */
	private function get_relevant_heatmap_using_post_id( $post_id ) {

		$meta_args = array(
			'relation' => 'AND',
			array(
				'key'     => '_nab_experiment_type',
				'value'   => 'nab/heatmap',
				'compare' => '=',
			),
			array(
				'key'     => '_nab_tracking_mode',
				'value'   => 'post',
				'compare' => '=',
			),
			array(
				'key'     => '_nab_tracked_post_id',
				'value'   => $post_id,
				'compare' => '=',
			),
		);

		return $this->get_relevant_experiment_using_meta_args( $meta_args );
	}

	/**
	 * Returns the relevant heatmap.
	 *
	 * @param string $url URL.
	 *
	 * @return Nelio_AB_Testing_Experiment|false
	 */
	private function get_relevant_heatmap_using_url( $url ) {

		$meta_args = array(
			'relation' => 'AND',
			array(
				'key'     => '_nab_experiment_type',
				'value'   => 'nab/heatmap',
				'compare' => '=',
			),
			array(
				'key'     => '_nab_tracking_mode',
				'value'   => 'url',
				'compare' => '=',
			),
			array(
				'key'     => '_nab_tracked_url',
				'value'   => $url,
				'compare' => '=',
			),
		);

		return $this->get_relevant_experiment_using_meta_args( $meta_args );
	}

	/**
	 * Returns relevant experiment.
	 *
	 * @param array<mixed> $meta_args Arguments to query the relevant experiment.
	 *
	 * @return Nelio_AB_Testing_Experiment|false
	 *
	 * @phpstan-ignore-next-line missingType.iterableValue
	 */
	private function get_relevant_experiment_using_meta_args( $meta_args ) {

		$get = function ( array $args ) {
			$result   = false;
			$wp_query = new WP_Query( $args );
			if ( $wp_query->have_posts() ) {
				$wp_query->the_post();
				$result = nab_get_experiment( absint( get_the_ID() ) );
			}
			wp_reset_postdata();
			return ! is_wp_error( $result ) ? $result : false;
		};

		$args = array(
			'post_type'     => 'nab_experiment',
			'post_per_page' => 1,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'    => $meta_args,
			'no_found_rows' => true,
		);

		$args['post_status'] = 'nab_running';
		$experiment          = $get( $args );
		if ( $experiment ) {
			return $experiment;
		}

		$args['post_status'] = array( 'nab_ready', 'nab_paused' );
		$experiment          = $get( $args );
		if ( $experiment ) {
			return $experiment;
		}

		$args['post_status'] = array( 'draft', 'nab_paused_draft' );
		$experiment          = $get( $args );
		if ( $experiment ) {
			return $experiment;
		}

		return false;
	}

	/**
	 * Returns the type for a new experiment, if it’s possible to create one. `false` otherwise.
	 *
	 * @return string|false
	 */
	private function get_type_for_new_experiment() {

		if ( ! is_singular() ) {
			return false;
		}

		switch ( get_post_type() ) {
			case 'page':
				return 'nab/page';
			case 'post':
				return 'nab/post';
			case 'product':
				return 'nab/wc-product';
			default:
				return 'nab/custom-post-type';
		}
	}

	/**
	 * Returns the current URL.
	 *
	 * @return string
	 */
	private function get_current_url() {
		/** @var WP */
		global $wp;
		return nab_home_url( add_query_arg( array(), $wp->request ) );
	}

	/**
	 * Returns the SVG logo.
	 *
	 * @return string
	 */
	private function get_admin_bar_logo() {

		$logo = file_get_contents( nelioab()->plugin_path . '/assets/dist/images/logo.svg' );
		$logo = is_string( $logo ) && ! empty( $logo ) ? $logo : 'admin-generic';

		// Make single line.
		$logo = preg_replace( '/(\s)+/', ' ', $logo );
		$logo = is_string( $logo ) ? $logo : 'admin-generic';

		// Remove XML opening tag.
		$logo = preg_replace( '/<\?xml[^?]+\?>/', '', $logo );
		$logo = is_string( $logo ) && ! empty( $logo ) ? $logo : 'admin-generic';

		// Fix size.
		$logo = str_replace( '<svg', '<svg style="width:20px"', $logo );

		// Inherit color.
		$logo = preg_replace( '/fill="[^"]+"/', 'fill="currentColor"', $logo );
		$logo = is_string( $logo ) ? $logo : 'admin-generic';

		// Clean.
		$logo = trim( $logo );

		return $logo;
	}
}
