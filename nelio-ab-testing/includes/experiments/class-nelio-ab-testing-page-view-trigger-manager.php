<?php
/**
 * Sanitizes and normalizes page-view triggers.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages page-view triggers.
 */
class Nelio_AB_Testing_Page_View_Trigger_Manager {

	/**
	 * Sanitizes page-view triggers.
	 *
	 * @param list<TConversion_Action>    $actions    Page-view triggers.
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return list<TConversion_Action> Sanitized page-view triggers.
	 */
	public static function sanitize( $actions, $experiment ) {
		return array_map(
			function ( $action ) use ( $experiment ) {
				$action_type          = $action['type'];
				$action['attributes'] = self::sanitize_attributes( $action['attributes'], $action, $experiment );
				$action['scope']      = array( 'type' => 'test-scope' );
				return $action;
			},
			$actions
		);
	}

	/**
	 * Normalizes page-view triggers before saving them.
	 *
	 * @param list<TConversion_Action>    $actions    Page-view triggers.
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return list<TConversion_Action> Normalized page-view triggers.
	 */
	public static function normalize_pre_save( $actions, $experiment ) {
		foreach ( $actions as &$action ) {
			$action['attributes'] = self::normalize_attributes( $action['attributes'], $action, $experiment );
			$action['scope']      = array( 'type' => 'test-scope' );
		}

		return $actions;
	}

	/**
	 * Validates page-view triggers.
	 *
	 * @param list<TConversion_Action> $actions      Page-view triggers.
	 * @param int                      $experiment_id Experiment ID.
	 *
	 * @return TAttributes_Validation Validation result.
	 */
	public static function ai_validate( $actions, $experiment_id ) {
		$status = 'valid';
		$issues = array();
		foreach ( $actions as $action ) {
			$scope_type = $action['scope']['type'];
			if ( 'test-scope' !== $scope_type ) {
				$status   = 'invalid';
				$issues[] = sprintf(
					/* translators: %s: Required page-view trigger scope type. */
					_x( 'Page-view triggers must use the `%s` scope.', 'text', 'nelio-ab-testing' ),
					'test-scope'
				);
			}

			$action_type = $action['type'];
			if ( '' === $action_type ) {
				$status   = 'invalid';
				$issues[] = _x( 'Every page-view trigger must use a registered trigger type.', 'text', 'nelio-ab-testing' );
				continue;
			}

			$result = self::ai_validate_attributes( $action, $experiment_id );
			if ( 'invalid' === $result['status'] ) {
				$status = 'invalid';
			} elseif ( 'unsupported' === $result['status'] && 'valid' === $status ) {
				$status = 'unsupported';
			}
			$issues = array_merge( $issues, $result['issues'] );
		}

		return array(
			'status' => $status,
			'issues' => $issues,
		);
	}

	/**
	 * Validates one page-view trigger's attributes.
	 *
	 * @param TConversion_Action $action         Page-view trigger to validate.
	 * @param int                $experiment_id Experiment identifier.
	 *
	 * @return TAttributes_Validation Validation result.
	 */
	private static function ai_validate_attributes( $action, $experiment_id ) {
		$action_type = $action['type'];
		$result      = array(
			'status' => 'unsupported',
			'issues' => array(),
		);

		/**
		 * Filters the AI validation result for page-view-trigger attributes.
		 *
		 * @param TAttributes_Validation $validation    Default validation result.
		 * @param TConversion_Action     $action       Page-view trigger to validate.
		 * @param int                    $experiment_id Experiment identifier.
		 *
		 * @since 9.0.0
		 */
		return apply_filters( "nab_{$action_type}_ai_validate_page_view_trigger_attributes", $result, $action, $experiment_id );
	}

	/**
	 * Sanitizes a page-view trigger's attributes.
	 *
	 * @param TAttributes                 $attributes Attributes.
	 * @param TConversion_Action          $action     Page-view trigger.
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return TAttributes Sanitized attributes.
	 */
	private static function sanitize_attributes( $attributes, $action, $experiment ) {
		/**
		 * Filters a page-view trigger's attributes.
		 *
		 * @param TAttributes                 $attributes Attributes.
		 * @param TConversion_Action          $action     Page-view trigger.
		 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
		 *
		 * @since 9.0.0
		 */
		$result = apply_filters( "nab_{$action['type']}_sanitize_page_view_trigger_attributes", $attributes, $action, $experiment );
		/** @var TAttributes $result */
		return $result;
	}


	/**
	 * Normalizes a page-view trigger's attributes before saving.
	 *
	 * @param TAttributes                 $attributes Attributes.
	 * @param TConversion_Action          $action     Page-view trigger.
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return TAttributes Normalized attributes.
	 */
	private static function normalize_attributes( $attributes, $action, $experiment ) {
		/**
		 * Filters a page-view trigger's attributes before saving.
		 *
		 * @param TAttributes                 $attributes Attributes.
		 * @param TConversion_Action          $action     Page-view trigger.
		 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
		 *
		 * @since 9.0.0
		 */
		$result = apply_filters( "nab_{$action['type']}_normalize_page_view_trigger_attributes_pre_save", $attributes, $action, $experiment );
		/** @var TAttributes $result */
		return $result;
	}
}
