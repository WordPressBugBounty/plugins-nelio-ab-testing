<?php
/**
 * Ability for creating an empty Nelio draft test.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates empty draft tests of AI-supported registered types.
 */
class Nelio_AB_Testing_Create_Draft_Test_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/create-draft-test',
			array(
				'label'               => _x( 'Create a Nelio draft test', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Creates an empty draft test of an AI-supported registered type. It does not configure the control, create variants, configure goals or segments, and it never starts the test. After creation, configure the control before adding generated variants, then configure goals, segments, scope, and basic settings as needed. It returns the test identifier and edit URL so the agent can tell the user that the draft was created and explain that it can be configured through the other Nelio abilities or manually in the dashboard before being started.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'type'        => array(
							'type'        => 'string',
							'description' => 'Identifier of the registered test type to create. The type must include an ai definition.',
						),
						'name'        => array(
							'type'        => 'string',
							'description' => 'Name for the draft test. It should briefly identify the test or its hypothesis.',
						),
						'description' => array(
							'type'        => 'string',
							'description' => 'Optional explanation of the test hypothesis or objective.',
						),
					),
					array( 'type', 'name' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'test' => $this->object_schema(
							array(
								'id'          => array(
									'type'        => 'integer',
									'description' => 'Identifier of the newly created draft test.',
								),
								'type'        => array(
									'type'        => 'string',
									'description' => 'Registered test type used to create the test.',
								),
								'name'        => array(
									'type'        => 'string',
									'description' => 'Name assigned to the draft test.',
								),
								'description' => array(
									'type'        => 'string',
									'description' => 'Explanation of the test hypothesis or objective.',
								),
								'status'      => array(
									'type'        => 'string',
									'enum'        => array( 'draft' ),
									'description' => 'Current status of the test. This ability always creates a draft.',
								),
								'editUrl'     => array(
									'type'        => 'string',
									'description' => 'Dashboard URL for editing the test.',
								),
							),
							array( 'id', 'type', 'name', 'description', 'status', 'editUrl' )
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
	 * Check whether the current user may create a test.
	 *
	 * @param array<array-key,mixed> $input Input.
	 *
	 * @return true|WP_Error
	 */
	public function permissions( $input ) {
		return current_user_can( 'edit_nab_experiments' )
			? true
			: new WP_Error( 'missing-capability', 'You cannot create Nelio tests on this site.', array( 'status' => 403 ) );
	}

	/**
	 * Create an empty draft test.
	 *
	 * @param array<array-key,mixed> $input Input.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$type        = is_string( $input['type'] ?? null ) ? trim( $input['type'] ) : '';
		$name        = is_string( $input['name'] ?? null ) ? sanitize_text_field( $input['name'] ) : '';
		$description = is_string( $input['description'] ?? null ) ? $this->text( $input['description'] ) : '';
		if ( '' === $name ) {
			return new WP_Error( 'invalid-test-name', 'The test name cannot be empty.' );
		}

		$definition = '' !== $type ? nelioab()->get_experiment_type( $type ) : null;
		if ( ! is_array( $definition ) ) {
			return new WP_Error( 'invalid-test-type', 'The requested test type is not registered on this site.' );
		}

		if ( ! nelioab()->does_test_type_support_ai( $type ) ) {
			return new WP_Error( 'unsupported-test-type', 'The requested test type cannot be configured through AI abilities.' );
		}

		$experiment = nab_create_experiment( $type );
		if ( is_wp_error( $experiment ) ) {
			return $experiment; // @codeCoverageIgnore
		}

		$experiment->set_name( $name );
		$experiment->set_description( $description );
		$experiment->set_status( 'draft' );
		$experiment->save();

		return array(
			'test' => array(
				'id'          => $experiment->ID,
				'type'        => $type,
				'name'        => $experiment->get_name(),
				'description' => $experiment->get_description(),
				'status'      => $experiment->get_status(),
				'editUrl'     => $experiment->get_url(),
			),
		);
	}
}
