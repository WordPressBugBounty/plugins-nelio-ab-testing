<?php
/**
 * Abstract class that implements a page.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * A class that represents a page.
 */
abstract class Nelio_AB_Testing_Abstract_Page {

	/**
	 * Parent slug.
	 *
	 * @var string
	 */
	protected $parent_slug;

	/**
	 * Page title.
	 *
	 * @var string
	 */
	protected $page_title;

	/**
	 * Menu title.
	 *
	 * @var string
	 */
	protected $menu_title;

	/**
	 * Required capability to view page.
	 *
	 * @var string
	 */
	protected $capability;

	/**
	 * Menu slug.
	 *
	 * @var string
	 */
	protected $menu_slug;

	/**
	 * Page rendering mode.
	 *
	 * @var 'extends-existing-page'|'regular-page'
	 */
	protected $mode;

	/**
	 * Creates an instance of this class.
	 *
	 * @param string                                 $parent_slug Parent slug.
	 * @param string                                 $page_title  Page title.
	 * @param string                                 $menu_title  Menu title.
	 * @param string                                 $capability  Required capability to view this page.
	 * @param string                                 $menu_slug   Menu slug.
	 * @param 'extends-existing-page'|'regular-page' $mode        Optional. Rendering mode. Default: `regular-page`.
	 */
	public function __construct( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $mode = 'regular-page' ) {

		$this->parent_slug = $parent_slug;
		$this->page_title  = $page_title;
		$this->menu_title  = $menu_title;
		$this->capability  = $capability;
		$this->menu_slug   = $menu_slug;
		$this->mode        = $mode;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {

		$this->add_page();
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
		add_action( 'admin_head', array( $this, 'add_help_tab' ) );
	}

	/**
	 * Adds the page.
	 *
	 * @return void
	 */
	public function add_page() {

		add_submenu_page(
			$this->parent_slug,
			$this->page_title,
			$this->menu_title,
			$this->capability,
			$this->menu_slug,
			$this->get_render_function()
		);
	}

	/**
	 * Displays the page.
	 *
	 * @return void
	 */
	abstract public function display();

	/**
	 * Callback to enqueue assets if the current page is this page.
	 *
	 * @return void
	 */
	public function maybe_enqueue_assets() {

		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}

		$this->enqueue_assets();
	}

	/**
	 * Callback to add a help tab if the current page is this page and has help.
	 *
	 * @return void
	 */
	public function add_help_tab() {
		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}

		if ( ! $this->is_help_tab_enabled() ) {
			return;
		}

		$screen = get_current_screen();
		if ( empty( $screen ) ) {
			return;
		}

		$screen->add_help_tab(
			array(
				'id'       => 'nelio-ab-testing',
				'title'    => 'Nelio A/B Testing',
				'content'  => 'Loading…',
				'priority' => 10,
			)
		);
	}

	/**
	 * Whether this page has help enabled or not.
	 *
	 * @return bool
	 */
	protected function is_help_tab_enabled() {
		return false;
	}

	/**
	 * Enqueues this page’s assets.
	 *
	 * @return void
	 */
	abstract protected function enqueue_assets();

	/**
	 * Returns the appropriate render function based on the page’s mode.
	 *
	 * @return callable|''
	 */
	private function get_render_function() {

		switch ( $this->mode ) {

			case 'extends-existing-page':
				return '';

			case 'regular-page':
			default:
				return array( $this, 'display' );

		}
	}

	/**
	 * Whether the current screen is this page or not.
	 *
	 * @return bool
	 */
	protected function is_current_screen_this_page() {

		if ( 0 === strpos( $this->menu_slug, 'edit.php?post_type=' ) ) {
			$post_type = str_replace( 'edit.php?post_type=', '', $this->menu_slug );
			return (
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				isset( $_GET['post_type'] ) &&
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				is_string( $_GET['post_type'] ) &&
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) === $post_type
			);
		}

		return (
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			isset( $_GET['page'] ) &&
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			is_string( $_GET['page'] ) &&
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			sanitize_text_field( wp_unslash( $_GET['page'] ) ) === $this->menu_slug
		);
	}
}
