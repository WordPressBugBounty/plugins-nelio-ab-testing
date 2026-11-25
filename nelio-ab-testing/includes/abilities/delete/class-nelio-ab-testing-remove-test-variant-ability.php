<?php
/**
 * Ability for removing a variant from an editable Nelio test.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Removes variants from editable tests.
 */
class Nelio_AB_Testing_Remove_Test_Variant_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Register the ability.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/remove-test-variant',
			array(
				'label'               => _x( 'Remove a test variant', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Removes a variant and its generated content from an existing editable Nelio test. The control cannot be removed and the test is never started. Use the variant UUID when invoking this ability and the current positional label when referring to the variant in messages to the user. It returns the updated ordered variant list because positional labels can change after a variant is removed. Removing a variant clears existing explicit `chance` values, so configure the complete distribution again afterward.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId'    => array(
							'type'        => 'integer',
							'description' => 'Identifier of the editable test.',
						),
						'variantId' => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => 'Internal UUID of the variant to remove. Do not use its positional label, because labels can change when another variant is removed.',
						),
					),
					array( 'testId', 'variantId' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'testId'         => array(
							'type'        => 'integer',
							'description' => 'Identifier of the editable test from which the variant was removed.',
						),
						'removedVariant' => $this->object_schema(
							array(
								'id'   => array(
									'type'        => 'string',
									'description' => 'Identifier of the removed variant.',
								),
								'name' => array(
									'type'        => 'string',
									'description' => 'Brief description previously assigned to the removed variant.',
								),
							),
							array( 'id', 'name' )
						),
						'variants'       => array(
							'type'        => 'array',
							'description' => 'Remaining variants in their current order. Positional labels may change after a variant is removed.',
							'items'       => $this->object_schema(
								array(
									'id'    => array(
										'type'        => 'string',
										'description' => 'Internal UUID of the variant.',
									),
									'label' => array(
										'type'        => 'string',
										'description' => 'Current positional label for the variant, such as Variant B or Variant C.',
									),
									'name'  => array(
										'type'        => 'string',
										'description' => 'Brief description of the changes applied to the variant.',
									),
								)
							),
						),
					),
					array( 'testId', 'removedVariant', 'variants' )
				),
				'permission_callback' => array( $this, 'permissions' ),
				'execute_callback'    => array( $this, 'execute' ),
				'meta'                => array(
					'show_in_rest' => false,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
						'server' => 'nelio-ab-testing',
					),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => true,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Check whether the current user may remove a variant.
	 *
	 * @param array<array-key,mixed> $input Input.
	 *
	 * @return true|WP_Error True when the user can remove a variant; otherwise, an error explaining why the test cannot be edited.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( absint( $input['testId'] ?? 0 ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}
		$heatmap_check = $this->reject_heatmap( $experiment );
		if ( is_wp_error( $heatmap_check ) ) {
			return $heatmap_check;
		}

		$can_be_edited = $experiment->can_be_edited();
		if ( is_wp_error( $can_be_edited ) ) {
			return $can_be_edited;
		}

		if ( ! nelioab()->does_test_type_support_ai( $experiment->get_type() ) ) {
			return new WP_Error( 'unsupported-test-type', 'This test type is registered but cannot be configured through AI abilities.' );
		}

		return true;
	}

	/**
	 * Remove a variant from an editable test.
	 *
	 * @param array<array-key,mixed> $input Input.
	 *
	 * @return array<string,mixed>|WP_Error The removed variant and the updated variant list, or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$experiment = nab_get_experiment( absint( $input['testId'] ?? 0 ) );
		assert( ! is_wp_error( $experiment ) );

		$id = $input['variantId'] ?? '';
		if ( ! is_string( $id ) || '' === $id ) {
			return new WP_Error( 'missing-variant-id', 'A variant ID is required to remove a variant.' );
		}
		if ( 'control' === $id ) {
			return new WP_Error( 'invalid-variant', 'The control cannot be removed with this ability.' );
		}

		$variant = $experiment->get_alternative( $id );
		if ( ! is_array( $variant ) ) {
			return new WP_Error( 'invalid-variant', 'The requested variant does not exist or cannot be removed.' );
		}

		$alternatives = $experiment->get_alternatives();
		if ( count( $alternatives ) <= 2 ) {
			return new WP_Error( 'invalid-variant-count', 'The requested variant cannot be removed because a test must retain the control and at least one other variant.' );
		}

		$alternatives              = array_values(
			array_filter( $alternatives, static fn ( $alternative ) => $alternative['id'] !== $id )
		);
		$has_explicit_distribution = false;
		foreach ( $alternatives as $alternative ) {
			if ( is_numeric( $alternative['attributes']['chance'] ?? null ) ) {
				$has_explicit_distribution = true;
				break;
			}
		}
		if ( $has_explicit_distribution ) {
			foreach ( $alternatives as &$alternative ) {
				unset( $alternative['attributes']['chance'] );
			}
			unset( $alternative );
		}
		$experiment->set_alternatives( $alternatives );
		nab_ai_refresh_experiment_status( $experiment );
		$experiment->save();

		$variants = array();
		foreach ( $alternatives as $index => $alternative ) {
			if ( 'control' === $alternative['id'] ) {
				continue;
			}
			$variants[] = array(
				'id'    => $alternative['id'],
				'label' => $this->get_variant_label( $index ),
				'name'  => is_string( $alternative['attributes']['name'] ?? null ) ? $alternative['attributes']['name'] : '',
			);
		}

		return array(
			'testId'         => $experiment->ID,
			'removedVariant' => array(
				'id'   => $id,
				'name' => is_string( $variant['attributes']['name'] ?? null ) ? $variant['attributes']['name'] : '',
			),
			'variants'       => $variants,
		);
	}

	/**
	 * Generate a positional label for a variant.
	 *
	 * @param int $index Zero-based variant index.
	 *
	 * @return string
	 */
	private function get_variant_label( $index ) {
		$number  = $index + 1;
		$letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$letter  = $index < strlen( $letters ) ? $letters[ $index ] : "$number";
		return 'Variant ' . ( $letter );
	}
}
