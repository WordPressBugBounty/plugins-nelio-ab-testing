<?php

namespace Nelio_AB_Testing\Admin\Views\Overview_Dashboard_Widget;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to add the overview widget.
 *
 * @return void
 */
function add_widget() {
	wp_add_dashboard_widget(
		'nab-dashboard-overview',
		_x( 'Nelio A/B Testing Overview', 'text', 'nelio-ab-testing' ),
		__NAMESPACE__ . '\render_widget'
	);

	// Move our widget to top.
	global $wp_meta_boxes;
	if (
		! is_array( $wp_meta_boxes ) ||
			! is_array( $wp_meta_boxes['dashboard'] ) ||
			! is_array( $wp_meta_boxes['dashboard']['normal'] ) ||
			! is_array( $wp_meta_boxes['dashboard']['normal']['core'] )
	) {
		return;
	}

	$dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
	if ( empty( $dashboard['nab-dashboard-overview'] ) ) {
		return;
	}

	$ours = array( 'nab-dashboard-overview' => $dashboard['nab-dashboard-overview'] );
	$wp_meta_boxes['dashboard']['normal']['core'] = array_merge( $ours, $dashboard ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}
add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\add_widget' );

/**
 * AJAX callback to retrieve and return the news.
 *
 * @return void
 */
function fetch_news() {
	$news = get_news( 'fetch' );
	if ( empty( $news ) ) {
		echo '';
		die();
	}

	printf( '<h3>%s</h3>', esc_html_x( 'News & Updates', 'text', 'nelio-ab-testing' ) );
	echo '<ul>';
	array_walk( $news, __NAMESPACE__ . '\render_single_news' );
	echo '</ul>';
	die();
}
add_action( 'wp_ajax_nab_fetch_news', __NAMESPACE__ . '\fetch_news' );

/**
 * Callback function to render the widget.
 *
 * @return void
 */
function render_widget() {
	render_title();
	render_experiments();
	render_news();
	render_actions();
}

/**
 * Callback function to render the widget’s title.
 *
 * @return void
 */
function render_title() {
	nab_require_wp_file( '/wp-admin/includes/class-wp-filesystem-base.php' );
	nab_require_wp_file( '/wp-admin/includes/class-wp-filesystem-direct.php' );
	$filesystem = new \WP_Filesystem_Direct( true );
	$icon       = $filesystem->get_contents( nelioab()->plugin_path . '/assets/dist/images/logo.svg' );
	$icon       = is_string( $icon ) ? $icon : '';
	$icon       = str_replace( 'fill="black"', 'fill="currentcolor"', $icon );
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
 * Callback function to render the latest experiments (if any) inside the widget.
 *
 * @return void
 */
function render_experiments() {
	$experiments = get_last_experiments();
	if ( empty( $experiments ) ) {
		return;
	}
	echo '<div class="nab-experiments">';
	printf( '<h3>%s</h3>', esc_html_x( 'Recently Updated', 'text (tests)', 'nelio-ab-testing' ) );
	echo '<ul>';
	array_walk( $experiments, __NAMESPACE__ . '\render_experiment' );
	echo '</ul>';
	echo '</div>';
}

/**
 * Callback function to render the news.
 *
 * @return void
 */
function render_news() {
	$news = get_news( 'cache' );
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
	array_walk( $news, __NAMESPACE__ . '\render_single_news' );
	echo '</ul>';
	echo '</div>';
}

/**
 * Callback function to render the widget actions.
 *
 * @return void
 */
function render_actions() {
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
function get_last_experiments() {
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
 * Renders the given experiment.
 *
 * @param \Nelio_AB_Testing_Experiment $e Experiment.
 *
 * @return void
 */
function render_experiment( $e ) {
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
function get_news( $mode ) {
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

/**
 * Renders a news item.
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
function render_single_news( $n ) {
	echo '<div class="nab-single-news">';

	echo '<div class="nab-single-news__header">';
	printf(
		'<span class="nab-single-news__type nab-single-news__type--is-%s">%s</span> ',
		esc_attr( $n['type'] ),
		esc_html(
			'release' === $n['type']
				? esc_html_x( 'NEW', 'text', 'nelio-ab-testing' )
				: esc_html_x( 'INFO', 'text', 'nelio-ab-testing' )
		)
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
