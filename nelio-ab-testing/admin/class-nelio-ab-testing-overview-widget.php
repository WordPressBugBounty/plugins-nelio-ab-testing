<?php
/**
 * Adds overview widget to WordPress’ dashboard.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin
 * @since      6.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * An overview widget in the Dashboard.
 */
class Nelio_AB_Testing_Overview_Widget {

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		if ( nelioab()->is_ready() ) {
			add_action( 'admin_head', array( $this, 'maybe_add_overview_widget_style' ) );
			add_action( 'wp_dashboard_setup', array( $this, 'add_widget' ) );
			add_action( 'wp_ajax_nab_fetch_news', array( $this, 'fetch_news' ) );
		}
	}

	/**
	 * Callback to add the overview widget.
	 *
	 * @return void
	 */
	public function add_widget() {
		wp_add_dashboard_widget(
			'nab-dashboard-overview',
			_x( 'Nelio A/B Testing Overview', 'text', 'nelio-ab-testing' ),
			array( $this, 'render_widget' )
		);

		// Move our widget to top.
		/** @var array<array<array<array<mixed>>>> */
		global $wp_meta_boxes;
		$dashboard = $wp_meta_boxes['dashboard']['normal']['core'] ?? null;
		if ( empty( $dashboard['nab-dashboard-overview'] ) ) {
			return; // @codeCoverageIgnore
		}

		$ours = array( 'nab-dashboard-overview' => $dashboard['nab-dashboard-overview'] );
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_meta_boxes['dashboard']['normal']['core'] = array_merge( $ours, $dashboard );
	}

	/**
	 * AJAX callback to retrieve and return the news.
	 *
	 * @return void
	 */
	public function fetch_news() {
		$news = $this->get_news( 'fetch' );
		if ( empty( $news ) ) {
			echo '';
			nab_die();
		}

		printf( '<h3>%s</h3>', esc_html_x( 'News & Updates', 'text', 'nelio-ab-testing' ) );
		echo '<ul>';
		array_walk( $news, array( $this, 'render_single_news' ) );
		echo '</ul>';
		nab_die();
	}

	/**
	 * Callback function to render the widget.
	 *
	 * @return void
	 */
	public function render_widget() {
		$this->render_title();
		$this->render_experiments();
		$this->render_news();
		$this->render_actions();
	}

	/**
	 * Callback to render the given experiment.
	 *
	 * @param \Nelio_AB_Testing_Experiment $e Experiment.
	 *
	 * @return void
	 */
	public function render_experiment( $e ) {
		$link   = $e->get_url();
		$title  = trim( $e->get_name() );
		$title  = empty( $title ) ? esc_html_x( 'Unnamed test', 'text', 'nelio-ab-testing' ) : $title;
		$format = esc_html_x( 'M d, h:ia', 'PHP datetime format', 'nelio-ab-testing' );
		$date   = get_the_modified_date( $format, $e->ID );

		$results    = in_array( $e->get_status(), array( 'running', 'finished' ), true );
		$icon       = $results ? 'visibility' : 'edit';
		$capability = $results ? 'read_nab_results' : 'edit_nab_experiments';

		echo '<li class="nab-experiment">';

		if ( current_user_can( $capability ) ) {
			printf( '<a href="%s">', esc_url( $link ) );
		} else {
			echo '<span>';
		}
		printf(
			'%s <span class="dashicons dashicons-%s"></span>',
			esc_html( $title ),
			esc_attr( $icon )
		);
		echo( current_user_can( $capability ) ? '</a>' : '</span>' );

		if ( ! empty( $date ) && is_string( $date ) ) {
			printf(
				' <span class="nab-experiment__date">%s</span>',
				esc_html( $date )
			);
		}

		echo '</li>';
	}

	/**
	 * Callback to render a news item.
	 *
	 * @param TNews $n News item.
	 *
	 * @return void
	 *
	 * @template TNews of array{
	 *  title: string,
	 *  link: string,
	 *  type: string,
	 *  excerpt: string,
	 * }
	 */
	public function render_single_news( $n ) {
		echo '<div class="nab-single-news">';

		echo '<div class="nab-single-news__header">';
		printf(
			'<span class="nab-single-news__type nab-single-news__type--is-%s">%s</span> ',
			esc_attr( $n['type'] ),
			// @codeCoverageIgnoreStart
			'release' === $n['type']
				? esc_html_x( 'NEW', 'text', 'nelio-ab-testing' )
				: esc_html_x( 'INFO', 'text', 'nelio-ab-testing' )
			// @codeCoverageIgnoreEnd
		);
		printf(
			'<a class="nab-single-news__title" href="%s" target="_blank">%s</a>',
			esc_url( $n['link'] ),
			esc_html( $n['title'] )
		);
		echo '</div>';

		printf(
			'<div class="nab-single-news__excerpt">%s</div>',
			esc_html( $n['excerpt'] )
		);

		echo '</div>';
	}

	/**
	 * Adds the overview widget’s style.
	 *
	 * @return void
	 */
	public function maybe_add_overview_widget_style() {
		$screen = get_current_screen();
		$screen = ! empty( $screen ) ? $screen->id : '';
		if ( 'dashboard' !== $screen ) {
			return;
		}
		?>
		<style type="text/css">
		#nab-dashboard-overview .inside { margin: 0; padding: 0; }
		#nab-dashboard-overview h3 {
			font-weight: bold;
			border-bottom: 1px solid var(--nab-color__border-light, #eee);
			padding: 0.5em 1em;
		}
		#nab-dashboard-overview a { text-decoration: none; }

		#nab-dashboard-overview .nab-header {
			align-items: center;
			box-shadow: 0 5px 8px rgba(0, 0, 0, 0.05);
			display: flex;
			gap: 0.5em;
			padding: 0.5em 1em;
		}
		#nab-dashboard-overview .nab-header__icon { width: 3em; line-height: 1; }
		#nab-dashboard-overview .nab-header__version p {  font-size: 0.9em; margin: 0; padding: 0; }

		#nab-dashboard-overview .nab-experiments { padding-top: 0.5em; }
		#nab-dashboard-overview .nab-experiment { margin: 0 1em 1em; }
		#nab-dashboard-overview .nab-experiment:last-child { margin-bottom: 0; }
		#nab-dashboard-overview .nab-experiment .dashicons { color: var(--nab-text--dark, #666); font-size: 1.3em; }
		#nab-dashboard-overview .nab-experiment__date { color: var(--nab-text--grey, #888); }

		#nab-dashboard-overview .nab-news { padding-top: 0.5em; }
		#nab-dashboard-overview .nab-single-news { margin: 0 1em 1em; }
		#nab-dashboard-overview .nab-single-news:last-child { margin-bottom: 0; }
		#nab-dashboard-overview .nab-single-news__header { font-size: 14px; margin-bottom: 0.5em; }
		#nab-dashboard-overview .nab-single-news__type {
			background: #0a875a;
			color: white;
			font-size: 0.75em;
			padding: 3px 6px;
			border-radius: 3px;
			text-transform: uppercase;
		}
		#nab-dashboard-overview .nab-single-news__type--is-release { background: #c92c2c; }

		#nab-dashboard-overview .nab-actions {
			border-top: 1px solid var(--nab-color__border-light, #eee);
			display: flex;
			gap: 1em;
			padding: 1em;
		}
		#nab-dashboard-overview .nab-actions > span:not(:last-child) {
			border-right: 1px solid var(--nab-color__border-light, #eee);
			padding-right: 1em;
		}
		#nab-dashboard-overview .nab-actions .dashicons { color: var(--nab-text--dark, #666); font-size: 1.3em; }
		</style>
		<?php
	}

	/**
	 * Renders the widget’s title.
	 *
	 * @return void
	 */
	private function render_title() {
		nab_require_wp_file( '/wp-admin/includes/class-wp-filesystem-base.php' );
		nab_require_wp_file( '/wp-admin/includes/class-wp-filesystem-direct.php' );
		$filesystem = new \WP_Filesystem_Direct( true );
		$icon       = $filesystem->get_contents( nelioab()->plugin_path . '/assets/dist/images/logo.svg' );
		$icon       = is_string( $icon ) ? $icon : '';
		$icon       = str_replace( 'fill="black"', 'fill="currentcolor"', $icon );
		$icon       = str_replace( "\n", ' ', $icon );
		printf(
			'<div class="nab-header"><div class="nab-header__icon">%s</div><div class="nab-header__version"><p>%s</p><p>%s</p></div></div>',
			wp_kses(
				$icon,
				array(
					'svg'  => array(
						'version' => true,
						'xmlns'   => true,
						'viewbox' => true,
					),
					'path' => array(
						'd'    => true,
						'fill' => true,
					),
				)
			),
			esc_html( 'Nelio A/B Testing v' . nelioab()->plugin_version ),
			/**
			* Filters the extra version in overview widget.
			*
			* @param string $version Extra version. Default: empty string.
			*
			* @since 6.2.0
			*/
			esc_html( apply_filters( 'nab_extra_version_in_overview_widget', '' ) )
		);
	}

	/**
	 * Renders the latest experiments (if any) inside the widget.
	 *
	 * @return void
	 */
	private function render_experiments() {
		$experiments = $this->get_last_experiments();
		if ( empty( $experiments ) ) {
			return;
		}
		echo '<div class="nab-experiments">';
		printf( '<h3>%s</h3>', esc_html_x( 'Recently Updated', 'text (tests)', 'nelio-ab-testing' ) );
		echo '<ul>';
		array_walk( $experiments, array( $this, 'render_experiment' ) );
		echo '</ul>';
		echo '</div>';
	}

	/**
	 * Renders the news.
	 *
	 * @return void
	 */
	private function render_news() {
		$news = $this->get_news( 'cache' );
		if ( empty( $news ) ) {
			echo '<div class="nab-news"><div class="spinner is-active"></div></div>';
			printf(
				'<script type="text/javascript">fetch(%s).then((r)=>r.text()).then((d)=>{document.querySelector(".nab-news").innerHTML=d;})</script>',
				wp_json_encode( add_query_arg( 'action', 'nab_fetch_news', admin_url( 'admin-ajax.php' ) ) )
			);
			return;
		}

		echo '<div class="nab-news">';
		printf( '<h3>%s</h3>', esc_html_x( 'News & Updates', 'text', 'nelio-ab-testing' ) );
		echo '<ul>';
		array_walk( $news, array( $this, 'render_single_news' ) );
		echo '</ul>';
		echo '</div>';
	}

	/**
	 * Renders the widget actions.
	 *
	 * @return void
	 */
	private function render_actions() {
		echo '<div class="nab-actions">';
		if ( current_user_can( 'edit_nab_experiments' ) ) {
			printf(
				'<span><a href="%s">%s</a></span>',
				esc_url( add_query_arg( 'post_type', 'nab_experiment', admin_url( 'edit.php' ) ) ),
				esc_html_x( 'Tests', 'text', 'nelio-ab-testing' )
			);
		}

		printf(
			'<span><a href="%s" target="_blank">%s <span class="dashicons dashicons-external"></span></a></span>',
			esc_url(
				add_query_arg(
					array(
						'utm_source'   => 'nelio-ab-testing',
						'utm_medium'   => 'plugin',
						'utm_campaign' => 'support',
						'utm_content'  => 'overview-widget',
					),
					'https://neliosoftware.com/blog'
				)
			),
			esc_html_x( 'Blog', 'text', 'nelio-ab-testing' )
		);

		printf(
			'<span><a href="%s" target="_blank">%s <span class="dashicons dashicons-external"></span></a></span>',
			esc_url(
				add_query_arg(
					array(
						'utm_source'   => 'nelio-ab-testing',
						'utm_medium'   => 'plugin',
						'utm_campaign' => 'support',
						'utm_content'  => 'overview-widget',
					),
					'https://neliosoftware.com/testing/help'
				)
			),
			esc_html_x( 'Help', 'text', 'nelio-ab-testing' )
		);
		echo '</div>';
	}

	/**
	 * Returns the latest experiments.
	 *
	 * @return list<\Nelio_AB_Testing_Experiment>
	 */
	private function get_last_experiments() {
		$experiments = get_posts(
			array(
				'post_type'   => 'nab_experiment',
				'count'       => 5,
				'post_status' => array( 'draft', 'nab_ready', 'nab_scheduled', 'nab_running', 'nab_paused', 'nab_paused_draft', 'nab_finished' ),
			)
		);
		$experiments = array_map( 'nab_get_experiment', $experiments );
		$experiments = array_filter( $experiments, fn( $e ) => ! is_wp_error( $e ) );
		return array_values( $experiments );
	}

	/**
	 * Retrieves the latest news from Nelio Software’s blog.
	 *
	 * @param 'fetch'|'cache' $mode Where to get the data from.
	 *
	 * @return list<array{
	 *   title: string,
	 *   link: string,
	 *   type: string,
	 *   excerpt: string,
	 * }>
	 */
	private function get_news( $mode ) {
		if ( 'fetch' === $mode ) {
			$rss = fetch_feed( 'https://neliosoftware.com/overview-widget/?tag=nab,test-of-the-month,case-study' );
			if ( is_wp_error( $rss ) ) {
				return array();
			}
			$news = $rss->get_items( 0, 3 );
			$news = array_map(
				function ( $n ) {
					return array(
						'title'   => $n->get_title(),
						'link'    => $n->get_permalink(),
						'type'    => $n->get_description(),
						'excerpt' => $n->get_content(),
					);
				},
				$news
			);
			set_transient( 'nab_news', $news, WEEK_IN_SECONDS );
		}

		/**
		 * Type safety.
		 *
		 * @var list<array{
		 *   title: string,
		 *   link: string,
		 *   type: string,
		 *   excerpt: string,
		 * }>
		 */
		$news = get_transient( 'nab_news' );
		return empty( $news ) ? array() : $news;
	}
}
