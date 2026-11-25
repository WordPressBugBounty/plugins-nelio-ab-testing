<?php
/**
 * Base class for Nelio A/B Testing abilities.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Base class shared by Nelio A/B Testing abilities. */
abstract class Nelio_AB_Testing_Ability {

	/**
	 * Register the ability.
	 *
	 * @return void
	 */
	abstract public function register();

	/**
	 * Check whether the current user may execute the ability.
	 *
	 * @param array<array-key,mixed> $input Input.
	 *
	 * @return true|WP_Error
	 */
	abstract public function permissions( $input );

	/**
	 * Execute the ability.
	 *
	 * @param array<array-key,mixed> $input Input.
	 * @return array<string,mixed>|WP_Error
	 */
	abstract public function execute( $input );

	/**
	 * Generates an object schema.
	 *
	 * @param array<string,mixed> $properties Object properties.
	 * @param list<string>|null   $required Required properties.
	 *
	 * @return array<string,mixed>
	 */
	protected function object_schema( $properties, $required = null ) {
		return array(
			'type'                 => 'object',
			'properties'           => $properties,
			'required'             => $required ?? array_keys( $properties ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Sanitize and bound untrusted site text.
	 *
	 * @param mixed $value Site text.
	 * @return string
	 */
	protected function text( $value ) {
		return mb_substr( wp_strip_all_tags( is_string( $value ) ? $value : '' ), 0, 1000 );
	}

	/**
	 * Reject responses larger than the ability response limit.
	 *
	 * @template T of mixed
	 *
	 * @param T $result Ability response.
	 *
	 * @return T|WP_Error
	 */
	protected function bounded_response( $result ) {
		return strlen( (string) wp_json_encode( $result ) ) > 65536 ? new WP_Error( 'response-too-large', 'This test exceeds the ability response limit. Open it in the Nelio dashboard.' ) : $result;
	}

	/**
	 * Reads an integer input value.
	 *
	 * @param array<array-key,mixed> $input Input values.
	 * @param string                 $key Input key.
	 *
	 * @return int Sanitized integer value.
	 */
	protected function integer_input( $input, $key ) {
		$value = $input[ $key ] ?? null;
		return absint( is_scalar( $value ) ? $value : null );
	}

	/**
	 * Rejects operations that are not applicable to heatmap tests.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return true|WP_Error True when the operation applies; otherwise, an error.
	 */
	protected function reject_heatmap( $experiment ) {
		if ( 'nab/heatmap' !== $experiment->get_type() ) {
			return true;
		}

		return new WP_Error(
			'unsupported-test-type',
			'This operation is not available for heatmap tests. Use the dedicated heatmap configuration abilities.'
		);
	}
}
