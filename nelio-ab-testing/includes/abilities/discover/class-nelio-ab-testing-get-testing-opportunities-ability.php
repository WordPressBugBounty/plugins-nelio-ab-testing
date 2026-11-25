<?php
/**
 * Ability for retrieving recommendation-engine testing opportunities.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Recommendation_Engine\Engine;
use Nelio_AB_Testing\Recommendation_Engine\Experiment_Repository;
use Nelio_AB_Testing\Recommendation_Engine\Opportunity;
use Nelio_AB_Testing\Recommendation_Engine\Providers\High_Traffic_Provider;
use Nelio_AB_Testing\Recommendation_Engine\Providers\Key_Pages_Provider;
use Nelio_AB_Testing\Recommendation_Engine\Providers\Recently_Finished_Experiment_Provider;
use Nelio_AB_Testing\Recommendation_Engine\Providers\Recently_Published_Content_Provider;
use Nelio_AB_Testing\Recommendation_Engine\Providers\Stale_Experiments_Provider;
use Nelio_AB_Testing\Recommendation_Engine\Providers\Traffic\Traffic_Provider_Factory;

/** Returns opportunities produced by Nelio's recommendation engine. */
class Nelio_AB_Testing_Get_Testing_Opportunities_Ability extends Nelio_AB_Testing_Ability {

	/** Register the ability. */
	public function register() {
		$target = array(
			'type'        => 'object',
			'description' => 'Content or URL identified by the recommendation engine as worth considering for testing.',
			'oneOf'       => array(
				$this->object_schema(
					array(
						'type'     => array(
							'type'        => 'string',
							'enum'        => array( 'post' ),
							'description' => 'Identifies a WordPress content target.',
						),
						'postType' => array(
							'type'        => 'string',
							'description' => 'WordPress post type of the target.',
						),
						'postId'   => array(
							'type'        => 'integer',
							'description' => 'WordPress identifier of the target.',
						),
						'title'    => array(
							'type'        => 'string',
							'description' => 'Title of the target.',
						),
						'url'      => array(
							'type'        => 'string',
							'description' => 'URL of the target.',
						),
					),
					array( 'type', 'postType', 'postId', 'title', 'url' )
				),
				$this->object_schema(
					array(
						'type'  => array(
							'type'        => 'string',
							'enum'        => array( 'url' ),
							'description' => 'Identifies a URL target.',
						),
						'title' => array(
							'type'        => 'string',
							'description' => 'Title or label associated with the target URL.',
						),
						'url'   => array(
							'type'        => 'string',
							'description' => 'URL of the target.',
						),
					),
					array( 'type', 'title', 'url' )
				),
			),
		);

		$references_object = array(
			'type'                 => 'object',
			'description'          => 'Inputs that can be passed to content-discovery abilities when the opportunity targets WordPress content.',
			'properties'           => array(
				'findContent'    => $this->object_schema(
					array(
						'query'    => array(
							'type'        => 'string',
							'description' => 'Search text or identifier to pass to content discovery.',
						),
						'postType' => array(
							'type'        => 'string',
							'description' => 'Logical content type to search.',
						),
					),
					array( 'query', 'postType' )
				),
				'inspectContent' => $this->object_schema(
					array(
						'id'   => array(
							'type'        => 'integer',
							'description' => 'Identifier of the content to inspect.',
						),
						'type' => array(
							'type'        => 'string',
							'description' => 'Logical content type to inspect.',
						),
					),
					array( 'id', 'type' )
				),
			),
			'required'             => array(),
			'additionalProperties' => false,
		);
		$references        = array(
			'oneOf' => array(
				$references_object,
				array(
					'type'        => 'array',
					'maxItems'    => 0,
					'description' => 'No content-discovery references are available for this opportunity.',
				),
			),
		);

		$opportunity = $this->object_schema(
			array(
				'type'       => array(
					'type'        => 'string',
					'description' => 'Recommendation category produced by Nelio’s engine.',
				),
				'score'      => array(
					'type'        => 'integer',
					'description' => 'Relevance score assigned by Nelio’s recommendation engine.',
				),
				'target'     => $target,
				'meta'       => array(
					'type'                 => 'object',
					'description'          => 'Evidence and explanation supplied by the recommendation provider. Its fields depend on the opportunity type.',
					'additionalProperties' => true,
				),
				'references' => $references,
			),
			array( 'type', 'score', 'target', 'meta', 'references' )
		);

		wp_register_ability(
			'nelio-ab-testing/get-testing-opportunities',
			array(
				'label'               => _x( 'Get Nelio testing opportunities', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Retrieves ranked testing opportunities identified by Nelio’s recommendation engine. It recommends content that may be worth testing and explains why each item is considered an opportunity. It does not recommend a specific test, variant change, or hypothesis for that content; the agent must determine those separately.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'limit' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 100,
							'default'     => 25,
							'description' => 'Maximum number of recommendations to return.',
						),
					),
					array()
				),
				'output_schema'       => $this->object_schema(
					array(
						'opportunities' => array(
							'type'        => 'array',
							'description' => 'Recommendations produced by Nelio’s engine, including only evidence supplied by that engine.',
							'items'       => $opportunity,
						),
						'count'         => array(
							'type'        => 'integer',
							'description' => 'Number of recommendations returned.',
						),
					)
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
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Check whether the current user may read recommendations.
	 *
	 * @param array<array-key,mixed> $input Input.
	 * @return true|WP_Error
	 */
	public function permissions( $input ) {
		return current_user_can( 'read_nab_results' )
			? true
			: new WP_Error( 'missing-capability', 'You cannot read Nelio testing opportunities on this site.', array( 'status' => 403 ) );
	}

	/**
	 * Retrieve ranked opportunities from the recommendation engine.
	 *
	 * @param array<array-key,mixed> $input Input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$limit         = min( 100, max( 1, absint( $input['limit'] ?? 25 ) ) );
		$engine        = $this->create_engine();
		$opportunities = array_slice( $engine->get_opportunities(), 0, $limit );
		$result        = array_map( array( $this, 'serialize_opportunity' ), $opportunities );
		return $this->bounded_response(
			array(
				'opportunities' => $result,
				'count'         => count( $result ),
			)
		);
	}

	/**
	 * Create the recommendation engine with the same providers used by the dashboard.
	 *
	 * @return Engine Recommendation engine.
	 */
	private function create_engine() {
		$experiment_repo = new Experiment_Repository();
		$post_types      = array_merge(
			array( 'page', 'post' ),
			\Nelio_AB_Testing\Experiment_Library\Post_Experiment\get_testable_custom_post_types()
		);
		return new Engine(
			array(
				new High_Traffic_Provider( Traffic_Provider_Factory::make(), $experiment_repo ),
				new Key_Pages_Provider( $experiment_repo ),
				new Recently_Finished_Experiment_Provider( $experiment_repo ),
				new Recently_Published_Content_Provider( $experiment_repo, $post_types ),
				new Stale_Experiments_Provider( $experiment_repo ),
			),
			$experiment_repo
		);
	}

	/**
	 * Convert an engine opportunity into an ability response.
	 *
	 * @param Opportunity $opportunity Engine opportunity.
	 * @return array<string,mixed>
	 */
	private function serialize_opportunity( $opportunity ) {
		$data               = $opportunity->jsonSerialize();
		$data['references'] = $this->get_references( $opportunity->get_target() );
		return $data;
	}

	/**
	 * Add identifiers understood by the content-discovery abilities.
	 *
	 * @param array<string,mixed> $target Engine target.
	 * @return array<string,mixed>
	 */
	private function get_references( $target ) {
		if ( 'post' !== ( $target['type'] ?? null ) ) {
			return array();
		}
		$post_id   = absint( $target['postId'] ?? 0 );
		$post_type = is_string( $target['postType'] ?? null ) ? $target['postType'] : '';
		return array(
			'findContent'    => array(
				'query'    => is_string( $target['title'] ?? null ) ? $target['title'] : (string) $post_id,
				'postType' => $post_type,
			),
			'inspectContent' => array(
				'id'   => $post_id,
				'type' => $post_type,
			),
		);
	}
}
