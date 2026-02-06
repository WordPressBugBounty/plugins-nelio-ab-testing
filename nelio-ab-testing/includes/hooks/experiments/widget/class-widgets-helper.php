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
	 * @var    Widgets_Helper|null
	 */
	protected static $instance = null;

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
		}

		return self::$instance;
	}

	/**
	 * Duplicates all the widgets in each source sidebar to the corresponding dest sidebar.
	 *
	 * Source and destination sidebars should have the same number of elements. If they don’t, the function will just quit.
	 *
	 * @param list<string> $src_sidebars  source sidebars.
	 * @param list<string> $dest_sidebars destination sidebars.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function duplicate_sidebars( $src_sidebars, $dest_sidebars ) {

		if ( count( $src_sidebars ) !== count( $dest_sidebars ) ) {
			return;
		}

		$num_of_sidebars = count( $src_sidebars );
		/** @var array<string,list<string>> */
		$sidebars_widgets = get_option( 'sidebars_widgets' );
		for ( $i = 0; $i < $num_of_sidebars; ++$i ) {

			$src_id  = $src_sidebars[ $i ];
			$dest_id = $dest_sidebars[ $i ];

			if ( ! isset( $sidebars_widgets[ $src_id ] ) ) {
				continue;
			}

			$sidebars_widgets[ $dest_id ] = $this->duplicate_widgets_in_sidebar( $sidebars_widgets, $src_id );

		}

		update_option( 'sidebars_widgets', $sidebars_widgets );
	}

	/**
	 * Removes the alternative sidebars that belong to the given experiment and
	 * alternative.
	 *
	 * @param list<string> $alternative_sidebar_ids IDs of the alternative sidebars.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function remove_alternative_sidebars( $alternative_sidebar_ids ) {

		/** @var array<string,list<string>> */
		$sidebars_widgets = get_option( 'sidebars_widgets' );
		foreach ( $alternative_sidebar_ids as $sidebar_id ) {
			$this->remove_widgets( $sidebars_widgets[ $sidebar_id ] );
			unset( $sidebars_widgets[ $sidebar_id ] );
		}

		update_option( 'sidebars_widgets', $sidebars_widgets );
	}

	/**
	 * Gets widget index.
	 *
	 * @param string $widget Widget.
	 *
	 * @return int
	 */
	private function get_widget_index( $widget ) {
		return absint( preg_replace( '/^.*-([0-9]+)$/', '$1', $widget ) );
	}

	/**
	 * Removes widgets.
	 *
	 * @param list<string> $widgets Widgets.
	 *
	 * @return void
	 */
	private function remove_widgets( $widgets ) {
		foreach ( $widgets as $widget ) {
			$this->remove_widget( $widget );
		}
	}

	/**
	 * Removes wiget.
	 *
	 * @param string $widget Widget.
	 *
	 * @return void
	 */
	private function remove_widget( $widget ) {
		$kind      = $this->get_widget_kind( $widget );
		$widget_id = $this->get_widget_index( $widget );

		/** @var array<int,mixed> */
		$definitions = get_option( 'widget_' . $kind, array() );
		unset( $definitions[ $widget_id ] );
		update_option( 'widget_' . $kind, $definitions );
	}

	/**
	 * Duplicates widgets in sidebar.
	 *
	 * @param array<string,list<string>> $sidebars_widgets Sidebars widgets.
	 * @param string                     $sidebar_id       Sidebar ID.
	 *
	 * @return list<string>
	 */
	private function duplicate_widgets_in_sidebar( $sidebars_widgets, $sidebar_id ) {
		$all_widgets = $this->extract_all_widgets( $sidebars_widgets );

		/** @var list<string> */
		$result = array();

		foreach ( $sidebars_widgets[ $sidebar_id ] as $widget ) {
			$new_widget = $this->duplicate_widget_considering_all_widget_indexes( $widget, $all_widgets );
			array_push( $result, $new_widget );
			array_push( $all_widgets, $new_widget );
		}

		return $result;
	}

	/**
	 * Extracts all widgets.
	 *
	 * @param array<string,list<string>|null> $sidebars_widgets Sidebars widgets.
	 *
	 * @return list<string>
	 */
	private function extract_all_widgets( $sidebars_widgets ) {
		$result = array();
		foreach ( $sidebars_widgets as $widgets ) {
			if ( ! is_array( $widgets ) ) {
				continue;
			}
			$result = array_merge( $result, $widgets );
		}

		return $result;
	}

	/**
	 * Duplicates widget considering all widget indexes.
	 *
	 * @param string       $widget Widget.
	 * @param list<string> $all_widgets All widgets.
	 *
	 * @return string
	 */
	private function duplicate_widget_considering_all_widget_indexes( $widget, $all_widgets ) {

		$new_widget = $this->get_new_widget_name( $widget, $all_widgets );
		$this->copy_widget( $widget, $new_widget );

		return $new_widget;
	}

	/**
	 * Gets new widget name.
	 *
	 * @param string       $widget      Widget.
	 * @param list<string> $all_widgets All widgets.
	 *
	 * @return string
	 */
	private function get_new_widget_name( $widget, $all_widgets ) {

		$kind   = $this->get_widget_kind( $widget );
		$new_id = $this->generate_new_widget_id_for_kind( $kind, $all_widgets );

		return $kind . '-' . $new_id;
	}

	/**
	 * Gets widget kind.
	 *
	 * @param string $widget Widget.
	 *
	 * @return string
	 */
	private function get_widget_kind( $widget ) {
		$kind = preg_replace( '/^(.*)-[0-9]+$/', '$1', $widget );
		return is_string( $kind ) ? $kind : '';
	}

	/**
	 * Generates new widget ID for kind.
	 *
	 * @param string       $kind        Kind.
	 * @param list<string> $all_widgets All widgets.
	 *
	 * @return int
	 */
	private function generate_new_widget_id_for_kind( $kind, $all_widgets ) {
		$indexes = $this->get_used_indexes( $kind, $all_widgets );
		return max( $indexes ) + 1;
	}

	/**
	 * Gets used indexes.
	 *
	 * @param string       $kind        Kind.
	 * @param list<string> $all_widgets All widgets.
	 *
	 * @return non-empty-list<int>
	 */
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
	}

	/**
	 * Copies widget.
	 *
	 * @param string $src_widget  Source widget.
	 * @param string $dest_widget Destination widget.
	 *
	 * @return void
	 */
	private function copy_widget( $src_widget, $dest_widget ) {

		$kind = $this->get_widget_kind( $src_widget );
		/** @var array<int,mixed> */
		$definitions = get_option( 'widget_' . $kind, array() );

		$src_id  = $this->get_widget_index( $src_widget );
		$dest_id = $this->get_widget_index( $dest_widget );

		$definitions[ $dest_id ] = $definitions[ $src_id ];
		update_option( 'widget_' . $kind, $definitions );
	}
}
