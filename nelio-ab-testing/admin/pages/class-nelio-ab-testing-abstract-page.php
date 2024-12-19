<?php
/**
 * Abstract class that implements a page.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

/**
 * A class that represents a page.
 */
abstract class Nelio_AB_Testing_Abstract_Page {

	protected $parent_slug;
	protected $page_title;
	protected $menu_title;
	protected $capability;
	protected $menu_slug;
	protected $mode;

	public function __construct( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, string $mode = 'regular-page' ) {

		$this->parent_slug = $parent_slug;
		$this->page_title  = $page_title;
		$this->menu_title  = $menu_title;
		$this->capability  = $capability;
		$this->menu_slug   = $menu_slug;
		$this->mode        = $mode;
	}//end __construct()

	public function init() {

		$this->add_page();
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
	}//end init()

	public function add_page() {

		add_submenu_page(
			$this->parent_slug,
			$this->page_title,
			$this->menu_title,
			$this->capability,
			$this->menu_slug,
			$this->get_render_function()
		);
	}//end add_page()

	abstract public function display();

	public function maybe_enqueue_assets() {

		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}//end if

		$this->enqueue_assets();
	}//end maybe_enqueue_assets()

	abstract protected function enqueue_assets();

	private function get_render_function() {

		switch ( $this->mode ) {

			case 'extends-existing-page':
				return null;

			case 'regular-page':
			default:
				return array( $this, 'display' );

		}//end switch
	}//end get_render_function()

	protected function is_current_screen_this_page() {

		if ( 0 === strpos( $this->menu_slug, 'edit.php?post_type=' ) ) {
			$post_type = str_replace( 'edit.php?post_type=', '', $this->menu_slug );
			return (
				isset( $_GET['post_type'] ) && // phpcs:ignore
				sanitize_text_field( $_GET['post_type'] ) === $post_type // phpcs:ignore
			);
		}//end if

		return (
			isset( $_GET['page'] ) &&  // phpcs:ignore
			sanitize_text_field( $_GET['page'] ) === $this->menu_slug // phpcs:ignore
		);
	}//end is_current_screen_this_page()
}//end class
