<?php
/**
 * Formats test data for Nelio A/B Testing abilities.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Formats tests and their related objects for ability responses.
 */
class Nelio_AB_Testing_Test_Response_Formatter {

	/**
	 * Checks whether the current user may read a test.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Test.
	 *
	 * @return true|WP_Error True when the test may be read; otherwise, an error.
	 */
	public static function can_read( $experiment ) {
		if ( ! is_user_logged_in() || ! nelioab()->is_ready() ) {
			return new WP_Error( 'missing-capability', 'You cannot read Nelio tests on this site.', array( 'status' => 403 ) );
		}
		$status    = $experiment->get_status();
		$published = in_array( $status, array( 'running', 'finished' ), true );
		if ( ! in_array( $status, array( 'draft', 'ready', 'scheduled', 'running', 'paused', 'paused_draft', 'finished' ), true ) ) {
			return new WP_Error( 'invalid-experiment', 'This test is not available.' );
		}
		if ( ! current_user_can( $published ? 'read_nab_results' : 'edit_nab_experiments' ) || ( ! $published && 'nab/php' === $experiment->get_type() && ! current_user_can( 'edit_nab_php_experiments' ) ) ) {
			return new WP_Error( 'missing-capability', 'You cannot read this Nelio test.', array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * Returns the basic test summary.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Test.
	 *
	 * @return array<string,mixed> Test summary.
	 */
	public static function summary( $experiment ) {
		return array(
			'id'       => $experiment->ID,
			'siteId'   => get_current_blog_id(),
			'name'     => $experiment->get_name(),
			'type'     => $experiment->get_type(),
			'status'   => $experiment->get_status(),
			'adminUrl' => $experiment->get_url(),
		);
	}

	/**
	 * Returns the public page-view trigger configuration.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Test.
	 *
	 * @return array<string,mixed>|WP_Error Public trigger configuration or an error.
	 */
	public static function page_view_trigger( $experiment ) {
		$triggers = $experiment->get_page_view_triggers();
		if ( empty( $triggers ) ) {
			return array( 'trigger' => 'on-page-visit' );
		}

		$trigger = $triggers[0];
		if ( 'nab/element-view' !== $trigger['type'] ) {
			return new WP_Error( 'unsupported-page-view-trigger', 'This test uses a page-view trigger that cannot be represented by the ability.' );
		}

		$attributes = $trigger['attributes'];
		$mode       = $attributes['mode'] ?? '';
		$value      = $attributes['value'] ?? '';
		if ( ! is_string( $mode ) || ! in_array( $mode, array( 'css', 'id', 'class' ), true ) || ! is_string( $value ) || '' === trim( $value ) ) {
			return new WP_Error( 'invalid-page-view-trigger', 'This test uses an invalid element-visibility page-view trigger.' );
		}

		return array(
			'trigger' => 'when-element-is-visible',
			'element' => array(
				'mode'  => $mode,
				'value' => $value,
			),
		);
	}

	/**
	 * Returns compact variant summaries.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Test.
	 *
	 * @return list<array<string,mixed>> Variant summaries.
	 */
	public static function variant_summaries( $experiment ) {
		$result = array();
		foreach ( $experiment->get_alternatives( 'full' ) as $index => $variant ) {
			$attributes = $variant['attributes'];
			$row        = array(
				'id'    => $variant['id'],
				'index' => $index,
				'label' => self::variant_label( $index ),
				'name'  => is_string( $attributes['name'] ?? null ) ? $attributes['name'] : ( 0 === $index ? 'Control' : '' ),
			);
			if ( self::is_custom_distribution_enabled() && isset( $attributes['chance'] ) && is_numeric( $attributes['chance'] ) ) {
				$row['chance'] = (float) $attributes['chance'];
			}
			$result[] = $row;
		}
		return $result;
	}

	/**
	 * Returns compact goal summaries.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Test.
	 *
	 * @return list<array<string,mixed>> Goal summaries.
	 */
	public static function goal_summaries( $experiment ) {
		$result = array();
		foreach ( $experiment->get_goals() as $index => $goal ) {
			$attributes = $goal['attributes'];
			$result[]   = array(
				'id'    => $goal['id'],
				'label' => self::goal_label( $index ),
				'name'  => $attributes['name'],
			);
		}
		return $result;
	}

	/**
	 * Returns compact segment summaries.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Test.
	 *
	 * @return list<array<string,mixed>> Segment summaries.
	 */
	public static function segment_summaries( $experiment ) {
		$result = array();
		foreach ( $experiment->get_segments() as $index => $segment ) {
			$attributes = $segment['attributes'];
			$result[]   = array(
				'id'    => $segment['id'],
				'label' => self::segment_label( $index ),
				'name'  => $attributes['name'],
			);
		}
		if ( count( $result ) > 1 ) {
			array_unshift(
				$result,
				array(
					'id'        => 'default',
					'label'     => 'Combined Segment',
					'name'      => 'Combined Segment',
					'isVirtual' => true,
				)
			);
		}
		return $result;
	}

	/**
	 * Returns a complete variant definition.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Test.
	 * @param string                      $variant_id Variant identifier.
	 *
	 * @return array<string,mixed>|WP_Error Variant definition or an error.
	 */
	public static function variant( $experiment, $variant_id ) {
		foreach ( $experiment->get_alternatives( 'full' ) as $index => $variant ) {
			if ( $variant['id'] !== $variant_id ) {
				continue;
			}
			$variant['index'] = $index;
			$variant['label'] = self::variant_label( $index );
			if ( ! self::is_custom_distribution_enabled() ) {
				unset( $variant['attributes']['chance'] );
			}
			return $variant;
		}
		return new WP_Error( 'invalid-variant', 'The requested variant does not exist.' );
	}

	/**
	 * Returns a complete goal definition.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Test.
	 * @param string                      $goal_id Goal identifier.
	 *
	 * @return array<string,mixed>|WP_Error Goal definition or an error.
	 */
	public static function goal( $experiment, $goal_id ) {
		foreach ( $experiment->get_goals() as $index => $goal ) {
			if ( $goal['id'] !== $goal_id ) {
				continue;
			}
			$goal['label'] = self::goal_label( $index );
			if ( ! self::is_custom_goal_tracking_enabled() ) {
				foreach ( $goal['conversionActions'] as &$action ) {
					unset( $action['scope'] );
				}
				unset( $action );
			} else {
				foreach ( $goal['conversionActions'] as &$action ) {
					if ( ! self::supports_custom_scope( $action['type'] ) ) {
						unset( $action['scope'] );
					}
				}
				unset( $action );
			}
			foreach ( $goal['conversionActions'] as &$action ) {
				unset( $action['id'] );
			}
			unset( $action );
			return $goal;
		}
		return new WP_Error( 'invalid-goal', 'The requested goal does not exist.' );
	}

	/**
	 * Returns a complete segment definition.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Test.
	 * @param string                      $segment_id Segment identifier.
	 *
	 * @return array<string,mixed>|WP_Error Segment definition or an error.
	 */
	public static function segment( $experiment, $segment_id ) {
		foreach ( $experiment->get_segments() as $index => $segment ) {
			if ( $segment['id'] !== $segment_id ) {
				continue;
			}
			$segment['label'] = self::segment_label( $index );
			foreach ( $segment['segmentationRules'] as &$rule ) {
				unset( $rule['id'] );
			}
			unset( $rule );
			return $segment;
		}
		return new WP_Error( 'invalid-segment', 'The requested segment does not exist.' );
	}

	/**
	 * Returns a positional variant label.
	 *
	 * @param int $index Zero-based variant index.
	 *
	 * @return string Positional label.
	 */
	private static function variant_label( $index ) {
		$letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$letter  = $index < strlen( $letters ) ? $letters[ $index ] : (string) ( $index + 1 );
		return 'Variant ' . ( $letter );
	}

	/**
	 * Checks whether custom variant distribution is enabled.
	 *
	 * @return bool Whether custom variant distribution is enabled.
	 */
	private static function is_custom_distribution_enabled() {
		return ! empty( Nelio_AB_Testing_Settings::instance()->get( 'is_alternative_distribution_allowed' ) );
	}

	/**
	 * Checks whether conversion actions may define their own scopes.
	 *
	 * @return bool Whether the site goal-tracking mode is custom.
	 */
	private static function is_custom_goal_tracking_enabled() {
		return 'custom' === Nelio_AB_Testing_Settings::instance()->get( 'goal_tracking' );
	}

	/**
	 * Checks whether an action type supports customizable scopes.
	 *
	 * @param string $action_type Conversion action type.
	 *
	 * @return bool Whether the action type supports customizable scopes.
	 */
	private static function supports_custom_scope( $action_type ) {
		$definition = nelioab()->get_conversion_action_type( $action_type );
		return is_array( $definition ) && true === ( $definition['supportsCustomScope'] ?? false );
	}

	/**
	 * Returns a numbered goal label.
	 *
	 * @param int $index Zero-based goal index.
	 *
	 * @return string Numbered goal label.
	 */
	private static function goal_label( $index ) {
		return 'Goal ' . ( $index + 1 );
	}

	/**
	 * Returns a numbered segment label.
	 *
	 * @param int $index Zero-based segment index.
	 *
	 * @return string Numbered segment label.
	 */
	private static function segment_label( $index ) {
		return 'Segment ' . ( $index + 1 );
	}
}
