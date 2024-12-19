<?php

namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * A class with several helper functions to work with widgets.
 *
 * @since      5.0.0
 */
class Widgets_Helper {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @var    Nelio_AB_Testing
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Widgets_Helper the single instance of this class.
	 *
	 * @since  5.0.0
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	/**
	 * Duplicates all the widgets in each source sidebar to the corresponding dest sidebar.
	 *
	 * Source and destination sidebars should have the same number of elements. If they don’t, the function will just quit.
	 *
	 * @param array $src_sidebars  source sidebars.
	 * @param array $dest_sidebars destination sidebars.
	 *
	 * @since  5.0.0
	 */
	public function duplicate_sidebars( $src_sidebars, $dest_sidebars ) {

		if ( count( $src_sidebars ) !== count( $dest_sidebars ) ) {
			return;
		}//end if

		$num_of_sidebars  = count( $src_sidebars );
		$sidebars_widgets = get_option( 'sidebars_widgets' );

		for ( $i = 0; $i < $num_of_sidebars; ++$i ) {

			$src_id  = $src_sidebars[ $i ];
			$dest_id = $dest_sidebars[ $i ];

			if ( is_array( $src_id ) && isset( $src_id['id'] ) ) {
				$src_id = $src_id['id'];
			}//end if

			if ( ! isset( $sidebars_widgets[ $src_id ] ) ) {
				continue;
			}//end if

			$sidebars_widgets[ $dest_id ] = $this->duplicate_widgets_in_sidebar( $sidebars_widgets, $src_id );

		}//end for

		update_option( 'sidebars_widgets', $sidebars_widgets );
	}//end duplicate_sidebars()

	/**
	 * Removes the alternative sidebars that belong to the given experiment and
	 * alternative.
	 *
	 * @param array $alternative_sidebar_ids IDs of the alternative sidebars.
	 *
	 * @since  5.0.0
	 * @SuppressWarnings( PHPMD.LongVariable )
	 */
	public function remove_alternative_sidebars( $alternative_sidebar_ids ) {

		$sidebars_widgets = get_option( 'sidebars_widgets' );
		foreach ( $alternative_sidebar_ids as $sidebar_id ) {
			$this->remove_widgets( $sidebars_widgets[ $sidebar_id ] );
			unset( $sidebars_widgets[ $sidebar_id ] );
		}//end foreach

		update_option( 'sidebars_widgets', $sidebars_widgets );
	}//end remove_alternative_sidebars()

	private function get_widget_index( $widget ) {

		return absint( preg_replace( '/^.*-([0-9]+)$/', '$1', $widget ) );
	}//end get_widget_index()

	private function remove_widgets( $widgets ) {

		foreach ( $widgets as $widget ) {
			$this->remove_widget( $widget );
		}//end foreach
	}//end remove_widgets()

	private function remove_widget( $widget ) {

		$kind      = $this->get_widget_kind( $widget );
		$widget_id = $this->get_widget_index( $widget );

		$definitions = get_option( 'widget_' . $kind, array() );
		unset( $definitions[ $widget_id ] );
		update_option( 'widget_' . $kind, $definitions );
	}//end remove_widget()

	private function duplicate_widgets_in_sidebar( $sidebars_widgets, $sidebar_id ) {

		$all_widgets = $this->extract_all_widgets( $sidebars_widgets );

		$result = array();
		foreach ( $sidebars_widgets[ $sidebar_id ] as $widget ) {

			$new_widget = $this->duplicate_widget_considering_all_widget_indexes( $widget, $all_widgets );
			array_push( $result, $new_widget );
			array_push( $all_widgets, $new_widget );

		}//end foreach

		return $result;
	}//end duplicate_widgets_in_sidebar()

	private function extract_all_widgets( $sidebars_widgets ) {

		$result = array();
		foreach ( $sidebars_widgets as $widgets ) {

			if ( ! is_array( $widgets ) ) {
				continue;
			}//end if

			$result = array_merge( $result, $widgets );

		}//end foreach

		return $result;
	}//end extract_all_widgets()

	private function duplicate_widget_considering_all_widget_indexes( $widget, $all_widgets ) {

		$new_widget = $this->get_new_widget_name( $widget, $all_widgets );
		$this->copy_widget( $widget, $new_widget );

		return $new_widget;
	}//end duplicate_widget_considering_all_widget_indexes()

	private function get_new_widget_name( $widget, $all_widgets ) {

		$kind   = $this->get_widget_kind( $widget );
		$new_id = $this->generate_new_widget_id_for_kind( $kind, $all_widgets );

		return $kind . '-' . $new_id;
	}//end get_new_widget_name()

	private function get_widget_kind( $widget ) {

		return preg_replace( '/^(.*)-[0-9]+$/', '$1', $widget );
	}//end get_widget_kind()

	private function generate_new_widget_id_for_kind( $kind, $all_widgets ) {

		$indexes = $this->get_used_indexes( $kind, $all_widgets );

		return max( $indexes ) + 1;
	}//end generate_new_widget_id_for_kind()

	private function get_used_indexes( $kind, $all_widgets ) {

		$widgets = array_filter(
			$all_widgets,
			function ( $widget ) use ( $kind ) {
				return 0 === strpos( $widget, $kind . '-' );
			}
		);

		$indexes = array_map( array( $this, 'get_widget_index' ), $widgets );
		array_push( $indexes, 0 );
		sort( $indexes );

		return $indexes;
	}//end get_used_indexes()

	private function copy_widget( $src_widget, $dest_widget ) {

		$kind        = $this->get_widget_kind( $src_widget );
		$definitions = get_option( 'widget_' . $kind, array() );

		$src_id  = $this->get_widget_index( $src_widget );
		$dest_id = $this->get_widget_index( $dest_widget );

		$definitions[ $dest_id ] = $definitions[ $src_id ];
		update_option( 'widget_' . $kind, $definitions );
	}//end copy_widget()
}//end class
