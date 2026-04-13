<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Class responsible for loading URL alternatives.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<TMenu_Control_Attributes,TMenu_Alternative_Attributes>
 */
class Alternative_Menu_Loader extends \Nelio_AB_Testing_Alternative_Loader {

	/** @var list<int> */
	private $tested_menus;

	/**
	 * Constructor.
	 *
	 * @param TMenu_Alternative_Attributes|TMenu_Control_Attributes $alternative    Alternative attributes.
	 * @param TMenu_Control_Attributes                              $control        Control attributes.
	 * @param int                                                   $experiment_id  Experiment ID.
	 * @param string                                                $alternative_id Alternative ID.
	 */
	public function __construct( $alternative, $control, $experiment_id, $alternative_id ) {
		parent::__construct( $alternative, $control, $experiment_id, $alternative_id );

		$this->tested_menus = array( $control['menuId'] );
		if ( ! empty( $control['testAgainstExistingMenu'] ) ) {
			$experiment = nab_get_experiment( $experiment_id );
			assert( ! is_wp_error( $experiment ) );
			$alternatives       = $experiment->get_alternatives();
			$this->tested_menus = array_map( fn( $a ) => absint( $a['attributes']['menuId'] ), $alternatives );
		}
	}

	public function init() {
		add_filter( 'wp_get_nav_menu_items', array( $this, 'maybe_replace_menu' ), 10, 3 );
	}

	/**
	 * Callback to replace tested menu with alternative one.
	 *
	 * @param list<\WP_Post>           $items Items.
	 * @param \WP_Term                 $menu  Menu.
	 * @param array{tax_query?:string} $args  Arguments.
	 *
	 * @return list<\WP_Post>
	 */
	public function maybe_replace_menu( $items, $menu, $args ) {
		if ( ! in_array( $menu->term_id, $this->tested_menus, true ) ) {
			return $items;
		}

		if ( ! is_nav_menu( $this->alternative['menuId'] ) ) {
			return $items;
		}

		if ( isset( $args['tax_query'] ) ) {
			unset( $args['tax_query'] );
		}
		remove_filter( 'wp_get_nav_menu_items', array( $this, 'maybe_replace_menu' ), 10 );
		/** @var list<\WP_Post> */
		$items = wp_get_nav_menu_items( $this->alternative['menuId'], $args );
		add_filter( 'wp_get_nav_menu_items', array( $this, 'maybe_replace_menu' ), 10, 3 );

		return $items;
	}
}
