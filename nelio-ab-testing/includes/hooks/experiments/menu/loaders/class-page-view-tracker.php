<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Class responsible for tracking which experiments have been seen and, therefore, require page view tracking.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<TMenu_Control_Attributes,TMenu_Alternative_Attributes>
 */
class Page_View_Tracker extends \Nelio_AB_Testing_Alternative_Loader {

	/** @var bool */
	private $did_experiment_show_alternative_content = false;

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
		add_filter( 'wp_get_nav_menu_items', array( $this, 'maybe_mark_experiment_as_shown' ), 10, 2 );
		add_filter( 'nab_nab/menu_should_trigger_footer_page_view', array( $this, 'should_footer_page_view_be_triggered' ), 10, 2 );
	}

	/**
	 * Callback to add this experiment as a tracked experiment.
	 *
	 * @param list<\WP_Post> $items Items.
	 * @param \WP_Term       $menu  Menu.
	 *
	 * @return list<\WP_Post>
	 */
	public function maybe_mark_experiment_as_shown( $items, $menu ) {
		if ( in_array( $menu->term_id, $this->tested_menus, true ) ) {
			$this->did_experiment_show_alternative_content = true;
		}
		return $items;
	}

	/**
	 * Callback to determine whether page view should be triggered in the footer or not.
	 *
	 * @param bool $result        Result.
	 * @param int  $experiment_id Experiment ID.
	 *
	 * @return bool
	 */
	public function should_footer_page_view_be_triggered( $result, $experiment_id ) {
		if ( $this->experiment_id !== $experiment_id ) {
			return $result;
		}
		return $this->did_experiment_show_alternative_content;
	}
}
