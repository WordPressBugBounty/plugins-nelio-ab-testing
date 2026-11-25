<?php
/**
 * Ability for moving a Nelio test to the Trash.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Moves Nelio tests to the WordPress Trash. */
class Nelio_AB_Testing_Trash_Test_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/trash-test',
			array(
				'label'               => _x( 'Move a Nelio test to the Trash', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Moves an existing Nelio test to the WordPress Trash. It does not permanently delete the test and does not modify its configuration. Use the restore-test ability to restore a test from the Trash.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the test to move to the Trash.',
						),
					)
				),
				'output_schema'       => $this->test_schema(),
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
	 * Checks whether the current user may move the test to the Trash.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the test may be trashed; otherwise, an error.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		return $experiment->can_be_trashed();
	}

	/**
	 * Moves the requested test to the Trash.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return array<string,mixed>|WP_Error The trashed test or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$test_id = $this->integer_input( $input, 'testId' );
		$result  = wp_trash_post( $test_id );
		if ( ! $result ) {
			return new WP_Error( 'trash-failed', 'The test could not be moved to the Trash.' ); // @codeCoverageIgnore
		}

		$experiment = nab_get_experiment( $test_id );
		assert( ! is_wp_error( $experiment ) );

		return array(
			'test' => array(
				'id'       => $experiment->ID,
				'name'     => $experiment->get_name(),
				'type'     => $experiment->get_type(),
				'status'   => $experiment->get_status(),
				'adminUrl' => $experiment->get_url(),
			),
		);
	}

	/**
	 * Returns the output schema for a test lifecycle response.
	 *
	 * @return array<string,mixed> Test response schema.
	 */
	private function test_schema() {
		return $this->object_schema(
			array(
				'test' => $this->object_schema(
					array(
						'id'       => array(
							'type'        => 'integer',
							'description' => 'Identifier of the test.',
						),
						'name'     => array(
							'type'        => 'string',
							'description' => 'Current name of the test.',
						),
						'type'     => array(
							'type'        => 'string',
							'description' => 'Registered type of the test.',
						),
						'status'   => array(
							'type'        => 'string',
							'enum'        => array( 'trash' ),
							'description' => 'Current status of the test. This response always reports trash.',
						),
						'adminUrl' => array(
							'type'        => 'string',
							'description' => 'Dashboard URL associated with the test.',
						),
					),
					array( 'id', 'name', 'type', 'status', 'adminUrl' )
				),
			),
			array( 'test' )
		);
	}
}
