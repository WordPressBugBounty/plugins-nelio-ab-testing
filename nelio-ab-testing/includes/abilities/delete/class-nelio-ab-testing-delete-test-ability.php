<?php
/**
 * Ability for permanently deleting a trashed Nelio test.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Permanently deletes Nelio tests from the WordPress Trash. */
class Nelio_AB_Testing_Delete_Test_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/delete-test',
			array(
				'label'               => _x( 'Permanently delete a Nelio test', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Permanently deletes a Nelio test. In general, ask the user to move the test to the Trash first and use this ability only after the user explicitly confirms permanent deletion, because moving a test to the Trash can be undone while permanent deletion cannot. When the WordPress Trash is enabled, the test must already be in the Trash; when the Trash is disabled, the test can be deleted directly. This operation removes the test and its generated content permanently.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the test to delete permanently. When the WordPress Trash is enabled, the test must already be in the Trash.',
						),
					)
				),
				'output_schema'       => $this->object_schema(
					array(
						'testId'  => array(
							'type'        => 'integer',
							'description' => 'Identifier of the permanently deleted test.',
						),
						'deleted' => array(
							'type'        => 'boolean',
							'description' => 'Whether the test was permanently deleted.',
						),
					),
					array( 'testId', 'deleted' )
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
	 * Checks whether the current user may permanently delete the test.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the test may be deleted; otherwise, an error.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		return $experiment->can_be_permanently_deleted();
	}

	/**
	 * Permanently deletes the requested trashed test.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return array<string,mixed>|WP_Error Deletion result or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$test_id = $this->integer_input( $input, 'testId' );
		$result  = wp_delete_post( $test_id, true );
		if ( ! $result ) {
			return new WP_Error( 'delete-failed', 'The test could not be permanently deleted.' ); // @codeCoverageIgnore
		}

		return array(
			'testId'  => $test_id,
			'deleted' => true,
		);
	}
}
