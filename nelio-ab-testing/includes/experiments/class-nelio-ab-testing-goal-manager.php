<?php
/**
 * Sanitizes and validates experiment goals.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Zod;

/**
 * Manages experiment goals.
 */
class Nelio_AB_Testing_Goal_Manager {

	/**
	 * Returns the conversion action types that support purchase settings.
	 *
	 * @return list<string> Purchase conversion action types.
	 */
	public static function get_purchase_action_types() {
		$types = nelioab()->get_conversion_action_types();
		return array_keys(
			array_filter(
				$types,
				fn( $type ) => ! empty( $type['isPurchaseAction'] )
			)
		);
	}

	/**
	 * Sanitizes experiment goals and their conversion actions.
	 *
	 * @param list<array<string,mixed>> $goals          Goals.
	 * @param string                    $experiment_type Experiment type.
	 * @param int                       $experiment_id   Experiment identifier.
	 *
	 * @return list<TGoal> Sanitized goals.
	 */
	public static function sanitize_experiment_goals( $goals, $experiment_type, $experiment_id ) {
		return self::sanitize_goals( $goals, $experiment_type, $experiment_id );
	}

	/**
	 * Sanitizes reusable goals and their conversion actions.
	 *
	 * @param list<array<string,mixed>> $goals Goals.
	 *
	 * @return list<TGoal> Sanitized goals.
	 */
	public static function sanitize_reusable_goals( $goals ) {
		return self::sanitize_goals( $goals, '', 0 );
	}

	/**
	 * Sanitizes goals and their conversion actions.
	 *
	 * @param list<array<string,mixed>> $goals           Goals.
	 * @param string                    $experiment_type Experiment type.
	 * @param int                       $experiment_id   Experiment identifier.
	 *
	 * @return list<TGoal> Sanitized goals.
	 */
	private static function sanitize_goals( $goals, $experiment_type, $experiment_id ) {
		$schema = self::schema();
		$goals  = array_map(
			fn( $goal ) => $schema->safe_parse( $goal ),
			$goals
		);

		$goals = array_map(
			fn( $parsed ) => $parsed['success'] ? $parsed['data'] : null,
			$goals
		);
		/** @var list<TGoal> $goals */
		$goals = array_values( array_filter( $goals ) );
		if ( '' !== $experiment_type ) {
			$goals = array_map(
				function ( $goal, $goal_index ) use ( $experiment_type, $experiment_id ) {
					/**
					 * Filters an experiment goal before sanitizing its conversion actions.
					 *
					 * @param TGoal $goal           Goal.
					 * @param int   $goal_index     Goal index.
					 * @param int   $experiment_id Experiment identifier.
					 *
					 * @since 9.0.0
					 */
					$result = apply_filters( "nab_{$experiment_type}_sanitize_goal", $goal, $goal_index, $experiment_id );
					/** @var TGoal $result */
					return $result;
				},
				$goals,
				array_keys( $goals )
			);
		}

		foreach ( $goals as &$goal ) {
			$goal['attributes'] = self::sanitize_goal_attributes( $goal['attributes'], $goal );
			foreach ( $goal['conversionActions'] as &$action ) {
				$action['attributes'] = Nelio_AB_Testing_Conversion_Action_Manager::sanitize_attributes( $action['attributes'], $action );
				$action['scope']      = Nelio_AB_Testing_Conversion_Action_Manager::sanitize_scope( $action['scope'], $action );
			}
		}

		/** @var list<TGoal> $goals */
		return $goals;
	}

	/**
	 * Normalizes experiment goals before saving them.
	 *
	 * @param list<TGoal> $goals Goals.
	 *
	 * @return list<TGoal> Sanitized goals.
	 */
	public static function normalize_pre_save( $goals ) {
		foreach ( $goals as &$goal ) {
			foreach ( $goal['conversionActions'] as &$action ) {
				$action['attributes'] = Nelio_AB_Testing_Conversion_Action_Manager::normalize_attributes_pre_save( $action['attributes'], $action );
				$action['scope']      = Nelio_AB_Testing_Conversion_Action_Manager::normalize_scope_pre_save( $action['scope'], $action );
			}
		}

		return $goals;
	}

	/**
	 * Validates experiment goals and their conversion actions.
	 *
	 * @param list<array<string,mixed>> $goals         Goals.
	 * @param int                       $experiment_id Optional. Experiment identifier. Default: `0`.
	 *
	 * @return true|WP_Error True when all goals are valid; otherwise, an error.
	 */
	public static function ai_validate( $goals, $experiment_id = 0 ) {
		if ( empty( $goals ) ) {
			return new WP_Error( 'invalid-goal', 'The test must contain at least one goal.' );
		}

		foreach ( $goals as $goal ) {
			$validation = self::ai_validate_goal( $goal, $experiment_id );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}
		}

		return true;
	}

	/**
	 * Validates one experiment goal and its conversion actions.
	 *
	 * @param mixed $goal          Goal.
	 * @param int   $experiment_id Optional. Experiment identifier. Default: `0`.
	 *
	 * @return true|WP_Error True when the goal is valid; otherwise, an error.
	 */
	public static function ai_validate_goal( $goal, $experiment_id = 0 ) {
		$parsed_goal = self::schema()->safe_parse( $goal );
		if ( ! $parsed_goal['success'] ) {
			return new WP_Error(
				'invalid-goal',
				_x( 'The goal does not have the required structure.', 'text', 'nelio-ab-testing' )
			);
		}

		/** @var TGoal $goal */
		$goal                 = $parsed_goal['data'];
		$sanitized_attributes = self::sanitize_goal_attributes( $goal['attributes'], $goal );
		$attribute_issues     = array_map(
			fn( $issue ) => $issue['message'],
			nab_ai_compare_attributes( $goal['attributes'], $sanitized_attributes )
		);
		if ( ! empty( $attribute_issues ) ) {
			return new WP_Error(
				'invalid-goal',
				sprintf(
					'The goal attributes contain unsupported values: %s',
					implode( ' ', $attribute_issues )
				)
			);
		}

		if ( empty( $goal['conversionActions'] ) ) {
			return new WP_Error( 'invalid-goal', 'Every test goal must contain at least one conversion action.' );
		}

		foreach ( $goal['conversionActions'] as $action ) {
			$action_type = $action['type'];
			if ( ! nelioab()->get_conversion_action_type( $action_type ) ) {
				return new WP_Error(
					'invalid-goal',
					sprintf(
						/* translators: %s: Conversion action type. */
						_x( 'The conversion action type `%s` is not registered on this site.', 'text', 'nelio-ab-testing' ),
						$action_type
					)
				);
			}

			$scope_validation = Nelio_AB_Testing_Conversion_Action_Manager::ai_validate_scope( $action );
			if ( 'valid' !== $scope_validation['status'] ) {
				$issues = implode( ' ', $scope_validation['issues'] );
				if ( '' === $issues ) {
					$issues = 'The conversion action scope could not be validated.';
				}
				return new WP_Error(
					'invalid-goal',
					sprintf( 'The conversion action type `%s` has an invalid scope: %s', $action_type, $issues )
				);
			}

			$validation = Nelio_AB_Testing_Conversion_Action_Manager::ai_validate_attributes( $action, $experiment_id );
			if ( 'valid' !== $validation['status'] ) {
				$issues = implode( ' ', $validation['issues'] );
				if ( '' === $issues ) {
					$issues = _x( 'The conversion action attributes could not be validated.', 'text', 'nelio-ab-testing' );
				}
				return new WP_Error(
					'invalid-goal',
					sprintf(
						// translators: %1$s: Conversion action type, %2$s: Validation issues.
						_x( 'The conversion action type `%1$s` has invalid attributes: %2$s', 'text', 'nelio-ab-testing' ),
						$action_type,
						$issues
					)
				);
			}
		}

		return true;
	}

	/**
	 * Sanitizes goal attributes according to the conversion actions it contains.
	 *
	 * @param TAttributes $attributes Goal attributes.
	 * @param TGoal       $goal       Goal.
	 *
	 * @return TAttributes Sanitized goal attributes.
	 */
	private static function sanitize_goal_attributes( $attributes, $goal ) {
		/**
		 * Filters goal attributes according to the conversion actions it contains.
		 *
		 * @param TAttributes $attributes Goal attributes.
		 * @param TGoal       $goal       Goal.
		 *
		 * @since 9.0.0
		 */
		$result = apply_filters( 'nab_sanitize_goal_attributes', $attributes, $goal );

		/** @var TAttributes $result */
		return $result;
	}

	/**
	 * Returns the schema for an experiment goal.
	 *
	 * @return Nelio_AB_Testing\Zod\ObjectSchema Goal schema.
	 */
	private static function schema() {
		return Zod::object(
			array(
				'id'                => Zod::string()->trim()->min( 1 ),
				'attributes'        => Zod::object(
					array(
						'name' => Zod::string()->default( '' )->trim()->catch( '' ),
					)
				)->loose(),
				'conversionActions' => Zod::array(
					Zod::object(
						array(
							'id'         => Zod::string(),
							'type'       => Zod::string()->trim()->min( 1 ),
							'attributes' => Zod::object( array() )->loose(),
							'scope'      => Zod::object(
								array(
									'type' => Zod::enum(
										array( 'all-pages', 'test-scope', 'php-function', 'post-ids', 'urls' )
									),
								)
							)->loose(),
						)
					)
				),
			)
		);
	}
}
