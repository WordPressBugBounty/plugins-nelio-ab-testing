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

	protected static $instance;

	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	public function init() {
		add_action( 'admin_init', array( $this, 'add_overview_widget' ) );
		add_action( 'admin_head', array( $this, 'add_overview_widget_style' ) );
	}//end init()

	public function add_overview_widget() {
		if ( nelioab()->is_ready() ) {
			require nelioab()->plugin_path . '/admin/views/nelio-ab-testing-overview-widget.php';
		}//end if
	}//end add_overview_widget()

	public function add_overview_widget_style() {
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
	}//end add_overview_widget_style()
}//end class
