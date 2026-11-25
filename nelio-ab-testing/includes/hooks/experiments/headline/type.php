<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the headline experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/headline'] = array(
		'name'               => 'nab/headline',
		'category'           => 'page',
		'title'              => _x( 'Headline', 'text (experiment name)', 'nelio-ab-testing' ),
		'description'        => _x( 'Tests alternative titles, featured images, and excerpts for a post.', 'text', 'nelio-ab-testing' ),
		'labels'             => array( 'create' => _x( 'Add new A/B test of headlines', 'command', 'nelio-ab-testing' ) ),
		'supportedPostTypes' => array( 'post' ),
		'automaticGoalSetup' => true,
		'scope'              => 'urls-with-tested-post',
		'help'               => array(
			'original'    => _x( 'Headline split tests help you to improve the engagement with your audience by creating one or more variants of the title, excerpt, and featured image of a blog post. The control version (commonly known as the “A” version) is the already-existing blog post you want to test with its current headline.', 'user', 'nelio-ab-testing' ),
			'alternative' => _x( 'Here you can create and edit one or more variants of your tested post’s headline. By default, each variant you create will use the post’s current headline.', 'user', 'nelio-ab-testing' ),
		),
		'defaults'           => array(
			'original'    => array(
				'postType' => 'post',
				'postId'   => 0,
			),
			'alternative' => array(
				'name'     => '',
				'excerpt'  => '',
				'imageId'  => 0,
				'imageUrl' => '',
			),
		),
		'expected'           => array(
			'pageViews' => array(
				'average' => 15000,
				'median'  => 3000,
				'p25'     => 500,
				'p75'     => 9000,
			),
		),
	);
	return $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_type', 14 );
