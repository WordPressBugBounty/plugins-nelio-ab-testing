<?php
/**
 * Sanitizes and validates conversion actions.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages conversion-action attributes and scopes.
 */
class Nelio_AB_Testing_Conversion_Action_Manager {

	/**
	 * Sanitizes conversion-action attributes.
	 *
	 * @param TAttributes        $attributes Conversion-action attributes.
	 * @param TConversion_Action $action     Conversion action.
	 *
	 * @return TAttributes Sanitized attributes.
	 */
	public static function sanitize_attributes( $attributes, $action ) {
		$action_type = $action['type'];
		/**
		 * Filters a conversion action type's attributes.
		 *
		 * @param TAttributes        $attributes Conversion-action attributes.
		 * @param TConversion_Action $action     Conversion action.
		 *
		 * @since 9.0.0
		 */
		$result = apply_filters( "nab_{$action_type}_sanitize_conversion_action_attributes", $attributes, $action );

		/** @var TAttributes $result */
		return $result;
	}

	/**
	 * Normalizes conversion-action attributes before saving.
	 *
	 * @param TAttributes        $attributes Conversion-action attributes.
	 * @param TConversion_Action $action     Conversion action.
	 *
	 * @return TAttributes Normalized attributes.
	 */
	public static function normalize_attributes_pre_save( $attributes, $action ) {
		$action_type = $action['type'];
		/**
		 * Filters a conversion action type's attributes before saving.
		 *
		 * @param TAttributes        $attributes Conversion-action attributes.
		 * @param TConversion_Action $action     Conversion action.
		 *
		 * @since 9.0.0
		 */
		$result = apply_filters( "nab_{$action_type}_normalize_conversion_action_attributes_pre_save", $attributes, $action );

		/** @var TAttributes $result */
		return $result;
	}

	/**
	 * Validates conversion-action attributes for AI configuration.
	 *
	 * @param TConversion_Action $action         Conversion action.
	 * @param int                $experiment_id Experiment identifier.
	 *
	 * @return TAttributes_Validation Validation result.
	 */
	public static function ai_validate_attributes( $action, $experiment_id ) {
		$action_type = $action['type'];
		$result      = array(
			'status' => 'unsupported',
			'issues' => array(),
		);

		/**
		 * Filters the AI validation result for conversion-action attributes.
		 *
		 * @param TAttributes_Validation $validation    Default validation result.
		 * @param TConversion_Action     $action       Conversion action to validate.
		 * @param int                    $experiment_id Experiment identifier.
		 *
		 * @since 9.0.0
		 */
		return apply_filters( "nab_{$action_type}_ai_validate_conversion_action_attributes", $result, $action, $experiment_id );
	}

	/**
	 * Sanitizes a conversion-action scope.
	 *
	 * @param TConversion_Action_Scope $scope  Conversion-action scope.
	 * @param TConversion_Action       $action Conversion action.
	 *
	 * @return TConversion_Action_Scope Sanitized scope.
	 */
	public static function sanitize_scope( $scope, $action ) {
		/**
		 * Filters a conversion action's scope.
		 *
		 * @param TConversion_Action_Scope $scope  Conversion-action scope.
		 * @param TConversion_Action       $action Conversion action.
		 *
		 * @since 6.0.4
		 */
		$result = apply_filters( 'nab_sanitize_conversion_action_scope', $scope, $action );

		/** @var TConversion_Action_Scope $result */
		return $result;
	}

	/**
	 * Normalizes a conversion-action scope before saving.
	 *
	 * @param TConversion_Action_Scope $scope  Conversion-action scope.
	 * @param TConversion_Action       $action Conversion action.
	 *
	 * @return TConversion_Action_Scope Normalized scope.
	 */
	public static function normalize_scope_pre_save( $scope, $action ) {
		/**
		 * Filters a conversion action's scope before saving.
		 *
		 * @param TConversion_Action_Scope $scope  Conversion-action scope.
		 * @param TConversion_Action       $action Conversion action.
		 *
		 * @since 9.0.0
		 */
		$result = apply_filters( 'nab_normalize_conversion_action_scope_pre_save', $scope, $action );

		/** @var TConversion_Action_Scope $result */
		return $result;
	}

	/**
	 * Validates a conversion-action scope for AI configuration.
	 *
	 * @param mixed $action Conversion action.
	 *
	 * @return TAttributes_Validation Validation result.
	 */
	public static function ai_validate_scope( $action ) {
		$scope  = is_array( $action ) ? ( $action['scope'] ?? null ) : null;
		$result = array(
			'status' => 'valid',
			'issues' => array(),
		);

		if ( ! is_array( $scope ) ) {
			$result['status']   = 'invalid';
			$result['issues'][] = 'The conversion action scope must be an object.';
		} else {
			$type = is_string( $scope['type'] ?? null ) ? $scope['type'] : '';
			switch ( $type ) {
				case 'all-pages':
				case 'test-scope':
					break;

				case 'php-function':
					if ( array_key_exists( 'enabled', $scope ) && ! is_callable( $scope['enabled'] ) ) {
						$result['status']   = 'invalid';
						$result['issues'][] = 'The PHP-function conversion action scope must contain a callable `enabled` value.';
					}
					break;

				case 'post-ids':
					$ids = $scope['ids'] ?? null;
					if ( ! is_array( $ids ) || empty( $ids ) ) {
						$result['status']   = 'invalid';
						$result['issues'][] = 'A post-IDs conversion action scope must contain at least one post ID.';
						break;
					}
					foreach ( $ids as $id ) {
						if ( ! is_int( $id ) || $id < 1 ) {
							$result['status']   = 'invalid';
							$result['issues'][] = 'A post-IDs conversion action scope must contain only positive integer post IDs.';
							break;
						}
					}
					break;

				case 'urls':
					$urls = $scope['regexes'] ?? null;
					if ( ! is_array( $urls ) || empty( $urls ) ) {
						$result['status']   = 'invalid';
						$result['issues'][] = 'A URL conversion action scope must contain at least one URL pattern.';
						break;
					}
					foreach ( $urls as $url ) {
						if ( ! is_string( $url ) || '' === trim( $url ) ) {
							$result['status']   = 'invalid';
							$result['issues'][] = 'A URL conversion action scope must contain only non-empty URL patterns.';
							break;
						}
					}
					break;

				default:
					$result['status']   = 'invalid';
					$result['issues'][] = 'The conversion action scope uses an unsupported scope type.';
					break;
			}
		}

		/**
		 * Filters the AI validation result for a conversion-action scope.
		 *
		 * @param TAttributes_Validation $validation Validation result.
		 * @param mixed                  $scope      Conversion-action scope.
		 * @param mixed                  $action     Conversion action.
		 *
		 * @since 9.0.0
		 */
		return apply_filters( 'nab_ai_validate_conversion_action_scope', $result, $scope, $action );
	}
}
