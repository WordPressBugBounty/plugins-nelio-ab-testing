<?php
/**
 * Formats experiment scope for Nelio A/B Testing abilities.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Converts persisted scope into the agent-facing representation.
 */
class Nelio_AB_Testing_Scope_Formatter {

	/**
	 * Formats the scope of an experiment.
	 *
	 * @param string                      $scope_support Scope support declared by the experiment type.
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return array<string,mixed> Agent-facing scope data.
	 */
	public static function format( $scope_support, $experiment ) {
		$scope      = $experiment->get_scope();
		$exclusions = $experiment->get_page_view_scope_exclusions();

		switch ( $scope_support ) {
			case 'tested-post-with-consistency':
			case 'tested-product-with-consistency':
				return array( 'globalConsistency' => empty( $scope ) );

			case 'urls':
			case 'urls-with-tested-post':
				$allowed_types = array( 'exact', 'partial', 'partial-not-included', 'different' );
				$rules         = array();
				foreach ( $scope as $rule ) {
					$id   = $rule['id'];
					$type = $rule['attributes']['type'];
					if ( ! in_array( $type, $allowed_types, true ) ) {
						continue;
					}
					$value   = isset( $rule['attributes']['value'] ) && is_string( $rule['attributes']['value'] ) ? $rule['attributes']['value'] : '';
					$rules[] = array(
						'type'           => $type,
						'value'          => nab_resolve_normalized_url( $value ),
						'trackPageViews' => ! in_array( $id, $exclusions, true ),
					);
				}
				return array( 'rules' => $rules );

			case 'tested-url-with-query-args':
				$formatted_scope = array();
				foreach ( $scope as $rule ) {
					if ( 'tested-url-with-query-args' !== $rule['attributes']['type'] ) {
						continue;
					}
					$value             = $rule['attributes']['value'];
					$urls              = array_map(
						fn( $url ) => nab_resolve_normalized_url( $url ),
						$value['urls']
					);
					$formatted_scope[] = array(
						'id'         => $rule['id'],
						'attributes' => array(
							'type'  => 'tested-url-with-query-args',
							'value' => array(
								'urls' => $urls,
								'args' => $value['args'],
							),
						),
					);
				}
				return array( 'scope' => $formatted_scope );
		}

		return array();
	}
}
