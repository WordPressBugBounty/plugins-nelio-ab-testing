<?php
/**
 * Ability for managing the lifecycle of a Nelio test.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Manages the lifecycle of a Nelio test. */
class Nelio_AB_Testing_Manage_Test_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/manage-test',
			array(
				'label'               => _x( 'Manage a Nelio test', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Starts, pauses, resumes, stops, or restarts an existing Nelio test. The action must match the test’s current status and the current user’s permissions. Starting, resuming, and restarting also check for overlapping running tests. Stopping a test ends it; restarting is the separate action for starting a finished test again.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the test whose lifecycle should be managed.',
						),
						'action' => array(
							'type'        => 'string',
							'enum'        => array( 'start', 'pause', 'resume', 'stop', 'restart' ),
							'description' => 'Lifecycle action to perform: start a ready or scheduled test, pause a running test, resume a paused test, stop a running or paused test, or restart a finished test.',
						),
					),
					array( 'testId', 'action' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'description' => 'Identifier of the managed test.',
						),
						'action' => array(
							'type'        => 'string',
							'enum'        => array( 'start', 'pause', 'resume', 'stop', 'restart' ),
							'description' => 'Lifecycle action that was performed.',
						),
						'status' => array(
							'type'        => 'string',
							'enum'        => array( 'draft', 'ready', 'scheduled', 'running', 'paused', 'paused_draft', 'finished' ),
							'description' => 'Status of the test after the action has completed.',
						),
						'link'   => array(
							'type'        => 'string',
							'description' => 'Dashboard link for the test. It opens the editor when the test is editable, or the results page when the test is running or finished.',
						),
					),
					array( 'testId', 'action', 'status', 'link' )
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
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Checks whether the current user may perform the requested lifecycle action.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier and action.
	 *
	 * @return true|WP_Error True when the action is allowed; otherwise, an error.
	 */
	public function permissions( $input ) {
		$action = $this->get_action( $input );
		if ( is_wp_error( $action ) ) {
			return $action;
		}

		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		switch ( $action ) {
			case 'start':
				return $experiment->can_be_started();
			case 'pause':
				return $experiment->can_be_paused();
			case 'resume':
				return $experiment->can_be_resumed();
			case 'stop':
				return $experiment->can_be_stopped();
			case 'restart':
				return $experiment->can_be_restarted();
			default:
				return new WP_Error( 'invalid-test-action', 'The requested test lifecycle action is not supported.' ); // @codeCoverageIgnore
		}
	}

	/**
	 * Performs the requested lifecycle action and returns the resulting status.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier and action.
	 *
	 * @return array<string,int|string>|WP_Error The performed action and resulting status, or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$action = $this->get_action( $input );
		if ( is_wp_error( $action ) ) {
			return $action; // @codeCoverageIgnore
		}
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( ! is_wp_error( $experiment ) );

		switch ( $action ) {
			case 'start':
				$result = $experiment->start();
				break;
			case 'pause':
				$result = $experiment->pause();
				break;
			case 'resume':
				$result = $experiment->resume();
				break;
			case 'stop':
				$result = $experiment->stop();
				break;
			case 'restart':
				$result = $experiment->restart();
				break;
			default:
				return new WP_Error( 'invalid-test-action', 'The requested test lifecycle action is not supported.' ); // @codeCoverageIgnore
		}

		if ( is_wp_error( $result ) ) {
			return $result; // @codeCoverageIgnore
		}

		return array(
			'testId' => $experiment->ID,
			'action' => $action,
			'status' => $experiment->get_status(),
			'link'   => $experiment->get_url(),
		);
	}

	/**
	 * Returns the requested lifecycle action.
	 *
	 * @param array<array-key,mixed> $input Input containing the action.
	 *
	 * @return string|WP_Error Supported action or an error.
	 */
	private function get_action( $input ) {
		$action = is_string( $input['action'] ?? null ) ? $input['action'] : '';
		if ( ! in_array( $action, array( 'start', 'pause', 'resume', 'stop', 'restart' ), true ) ) {
			return new WP_Error( 'invalid-test-action', 'The requested test lifecycle action is not supported.' );
		}

		return $action;
	}
}
