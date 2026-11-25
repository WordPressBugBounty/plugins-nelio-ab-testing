<?php
/**
 * Ability for duplicating a Nelio test.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Duplicates an existing Nelio test. */
class Nelio_AB_Testing_Duplicate_Test_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/duplicate-test',
			array(
				'label'               => _x( 'Duplicate a Nelio test', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Creates a new test by duplicating an existing Nelio test, preserving its supported configuration while generating new identifiers and duplicated variant content where necessary. Before calling this ability, first read the source test to confirm that it exists and to learn its current name and configuration. Then ask the user for the new test name, unless the user has authorized you to choose one based on the source test name. The original test is not modified, and the duplicated test is not started automatically.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the existing test to duplicate.',
						),
						'name'   => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => 'Name for the duplicated test. Obtain this from the user after reading the source test, unless the user has authorized you to derive a name from the source test name.',
						),
					),
					array( 'testId', 'name' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'test' => $this->object_schema(
							array(
								'id'       => array(
									'type'        => 'integer',
									'description' => 'Identifier of the duplicated test.',
								),
								'name'     => array(
									'type'        => 'string',
									'description' => 'Name of the duplicated test.',
								),
								'type'     => array(
									'type'        => 'string',
									'description' => 'Registered type of the duplicated test.',
								),
								'status'   => array(
									'type'        => 'string',
									'description' => 'Current status of the duplicated test.',
								),
								'adminUrl' => array(
									'type'        => 'string',
									'description' => 'Dashboard URL associated with the duplicated test.',
								),
							),
							array( 'id', 'name', 'type', 'status', 'adminUrl' )
						),
					),
					array( 'test' )
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
						'destructive' => false,
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Checks whether the current user may duplicate the requested test.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier and new name.
	 *
	 * @return true|WP_Error True when the test may be duplicated; otherwise, an error.
	 */
	public function permissions( $input ) {
		$name = is_string( $input['name'] ?? null ) ? trim( $input['name'] ) : '';
		if ( '' === $name ) {
			return new WP_Error( 'invalid-test-name', 'A name for the duplicated test is required.' );
		}

		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		return $experiment->can_be_duplicated();
	}

	/**
	 * Duplicates the requested test and applies the requested name.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier and new name.
	 *
	 * @return array<string,array<string,int|string>>|WP_Error The duplicated test or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( ! is_wp_error( $experiment ) );

		$duplicated = $experiment->duplicate();
		if ( is_wp_error( $duplicated ) ) {
			return $duplicated; // @codeCoverageIgnore
		}

		$name = is_string( $input['name'] ?? null ) ? trim( $input['name'] ) : '';
		$duplicated->set_name( $name );
		$duplicated->save();

		return array(
			'test' => array(
				'id'       => $duplicated->ID,
				'name'     => $duplicated->get_name(),
				'type'     => $duplicated->get_type(),
				'status'   => $duplicated->get_status(),
				'adminUrl' => $duplicated->get_url(),
			),
		);
	}
}
