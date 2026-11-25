<?php
/**
 * Manages reusable goals and segments.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages reusable entities stored by Nelio A/B Testing.
 */
class Nelio_AB_Testing_Reusable_Entity_Manager {

	/**
	 * Retrieves all reusable goals.
	 *
	 * @return list<TReusable_Goal> Reusable goals.
	 */
	public static function get_reusable_goals() {
		$goals = get_option( 'nab_reusable_goals', array() );
		return $goals;
	}

	/**
	 * Sanitizes, validates, and stores all reusable goals.
	 *
	 * @param mixed $goals Reusable goals.
	 *
	 * @return list<TReusable_Goal>|WP_Error Sanitized goals or an error.
	 */
	public static function set_reusable_goals( $goals ) {
		$goals = self::sanitize_reusable_goals( $goals );
		if ( empty( $goals ) ) {
			delete_option( 'nab_reusable_goals' );
			return $goals;
		}

		$error = self::validate_reusable_goals( $goals );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		update_option( 'nab_reusable_goals', $goals );

		return $goals;
	}

	/**
	 * Sanitizes reusable goals and their conversion actions.
	 *
	 * @param mixed $goals Reusable goals.
	 *
	 * @return list<TReusable_Goal> Sanitized reusable goals.
	 */
	public static function sanitize_reusable_goals( $goals ) {
		$goals = is_array( $goals ) ? $goals : array();
		$goals = self::add_temporary_goal_ids( $goals );
		$goals = Nelio_AB_Testing_Goal_Manager::sanitize_reusable_goals( $goals );
		$goals = self::remove_empty_names( $goals );
		$goals = array_map(
			function ( $goal ) {
				unset( $goal['id'] );
				return $goal;
			},
			$goals
		);
		$goals = self::remove_duplicated_names( $goals );
		$goals = self::sort_by_name( $goals );

		return $goals;
	}

	/**
	 * Validates every conversion action in a reusable-goal collection.
	 *
	 * @param list<TReusable_Goal> $goals Reusable goals.
	 *
	 * @return true|WP_Error True when all actions are valid; otherwise, an error.
	 */
	public static function validate_reusable_goals( $goals ) {
		return Nelio_AB_Testing_Goal_Manager::ai_validate( self::add_temporary_goal_ids( $goals ) );
	}

	/**
	 * Retrieves all reusable segments.
	 *
	 * @return list<TReusable_Segment> Reusable segments.
	 */
	public static function get_reusable_segments() {
		$segments = get_option( 'nab_reusable_segments', array() );
		return $segments;
	}

	/**
	 * Sanitizes and stores all reusable segments.
	 *
	 * @param mixed $segments Reusable segments.
	 *
	 * @return list<TReusable_Segment>|WP_Error Sanitized segments or an error.
	 */
	public static function set_reusable_segments( $segments ) {
		$segments = self::sanitize_reusable_segments( $segments );
		$error    = self::validate_reusable_segments( $segments );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		if ( empty( $segments ) ) {
			delete_option( 'nab_reusable_segments' );
		} else {
			update_option( 'nab_reusable_segments', $segments );
		}
		return $segments;
	}

	/**
	 * Validates every segmentation rule in a reusable-segment collection.
	 *
	 * @param list<TReusable_Segment> $segments Reusable segments.
	 *
	 * @return true|WP_Error True when all rules are valid; otherwise, an error.
	 */
	public static function validate_reusable_segments( $segments ) {
		return Nelio_AB_Testing_Segment_Manager::ai_validate( self::add_temporary_segment_ids( $segments ) );
	}

	/**
	 * Sanitizes reusable segments.
	 *
	 * @param mixed $segments Reusable segments.
	 *
	 * @return list<TReusable_Segment> Sanitized reusable segments.
	 */
	public static function sanitize_reusable_segments( $segments ) {
		$segments = is_array( $segments ) ? $segments : array();
		$segments = self::add_temporary_segment_ids( $segments );
		$segments = Nelio_AB_Testing_Segment_Manager::sanitize( $segments );
		$segments = self::remove_empty_names( $segments );
		$segments = array_map(
			function ( $segment ) {
				unset( $segment['id'] );
				return $segment;
			},
			$segments
		);
		$segments = self::remove_duplicated_names( $segments );
		$segments = self::sort_by_name( $segments );
		return $segments;
	}

	/**
	 * Removes reusable entities without a name.
	 *
	 * @template T of array{attributes:array{name:string}}
	 * @param list<T> $elements Reusable entities.
	 *
	 * @return list<T> Reusable entities with non-empty names.
	 */
	private static function remove_empty_names( $elements ) {
		return array_values( array_filter( $elements, array( __CLASS__, 'has_name' ) ) );
	}

	/**
	 * Checks whether a reusable entity has a non-empty name.
	 *
	 * @param array{attributes:array{name:string}} $element Reusable entity.
	 *
	 * @return bool Whether the entity has a non-empty name.
	 */
	private static function has_name( $element ) {
		return ! empty( $element['attributes']['name'] );
	}

	/**
	 * Adds temporary IDs to reusable goals before using the full goal schema.
	 *
	 * @param mixed $goals Reusable goals.
	 *
	 * @return list<TGoal> Goals with temporary IDs.
	 */
	private static function add_temporary_goal_ids( $goals ) {
		$goals = is_array( $goals ) ? $goals : array();
		$goals = array_map(
			function ( $goal ) {
				if ( ! is_array( $goal ) ) {
					return $goal;
				}
				if ( empty( $goal['id'] ) ) {
					$goal['id'] = nab_uuid();
				}
				return $goal;
			},
			$goals
		);

		/** @var list<TGoal> $goals */
		return $goals;
	}

	/**
	 * Adds temporary IDs to reusable segments before using the full segment schema.
	 *
	 * @param mixed $segments Reusable segments.
	 *
	 * @return list<TSegment> Segments with temporary IDs.
	 */
	private static function add_temporary_segment_ids( $segments ) {
		$segments = is_array( $segments ) ? $segments : array();
		$segments = array_map(
			function ( $segment ) {
				if ( ! is_array( $segment ) ) {
					return $segment;
				}
				if ( empty( $segment['id'] ) ) {
					$segment['id'] = nab_uuid();
				}
				return $segment;
			},
			$segments
		);

		/** @var list<TSegment> $segments */
		return $segments;
	}

	/**
	 * Removes elements with duplicated names, keeping the last occurrence.
	 *
	 * @template T of array{attributes:array{name:string}, conversionActions?:mixed, segmentationRules?:mixed}
	 * @param list<T> $elements Reusable entities.
	 *
	 * @return list<T> Entities without duplicated names.
	 */
	private static function remove_duplicated_names( $elements ) {
		$elements = array_combine(
			array_map( fn( $element ) => mb_strtolower( $element['attributes']['name'] ), $elements ),
			$elements
		);
		return array_values( $elements );
	}

	/**
	 * Sorts reusable entities by name.
	 *
	 * @template T of array{attributes:array{name:string}, conversionActions?:mixed, segmentationRules?:mixed}
	 * @param list<T> $elements Reusable entities.
	 *
	 * @return list<T> Sorted entities.
	 */
	private static function sort_by_name( $elements ) {
		$elements = array_combine(
			array_map( fn( $element ) => mb_strtolower( $element['attributes']['name'] ), $elements ),
			$elements
		);
		ksort( $elements );
		return array_values( $elements );
	}
}
