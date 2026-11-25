<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the page experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_page_type( $types ) {
	$types['nab/page'] = array(
		'name'               => 'nab/page',
		'category'           => 'page',
		'title'              => _x( 'Page', 'text (experiment name)', 'nelio-ab-testing' ),
		'description'        => _x( 'Tests substantial changes to a WordPress page, including its content, layout, and template.', 'text', 'nelio-ab-testing' ),
		'labels'             => array( 'create' => _x( 'Add new A/B test of pages', 'command', 'nelio-ab-testing' ) ),
		'supportedPostTypes' => array( 'page' ),
		'scope'              => 'tested-post-with-consistency',
		'help'               => array(
			'original'    => _x( 'Page split tests help you to improve the conversion rate of your WordPress site by creating one or more variants of your pages. The control version (commonly known as the “A” version) is the already-existing page from your site (such as, for instance, the home page or a contact us page) you want to run the test on.', 'user', 'nelio-ab-testing' ),
			'alternative' => _x( 'You can create one or more variants of your tested page. By default, each variant you create will be an exact copy of the tested page. Click on the “Edit” link of each variant to tweak it as you please. When editing a variant, you can also overwrite it with the content of any existing page.', 'user', 'nelio-ab-testing' ),
		),
		'defaults'           => array(
			'original'    => array(
				'postType' => 'page',
				'postId'   => 0,
			),
			'alternative' => array(
				'name'   => '',
				'postId' => 0,
			),
		),
		'expected'           => array(
			'pageViews' => array(
				'average' => 5000,
				'median'  => 1000,
				'p25'     => 500,
				'p75'     => 3500,
			),
		),
	);
	return $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_page_type', 10 );

/**
 * Registers the post experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_post_type( $types ) {
	$types['nab/post'] = array(
		'name'               => 'nab/post',
		'category'           => 'page',
		'title'              => _x( 'Post', 'text (experiment name)', 'nelio-ab-testing' ),
		'description'        => _x( 'Tests substantial changes to a WordPress post, including its content, layout, and template.', 'text', 'nelio-ab-testing' ),
		'labels'             => array( 'create' => _x( 'Add new A/B test of posts', 'command', 'nelio-ab-testing' ) ),
		'supportedPostTypes' => array( 'post' ),
		'scope'              => 'tested-post-with-consistency',
		'help'               => array(
			'original'    => _x( 'Post split tests help you to improve the conversion rate of your WordPress blog by creating one or more variants of your posts. The control version (commonly known as the “A” version) is the already-existing post from your blog you want to run the test on.', 'user', 'nelio-ab-testing' ),
			'alternative' => _x( 'You can create one or more variants of your tested post. By default, each variant you create will be an exact copy of the tested post. Click on the “Edit” link of each variant to tweak it as you please. When editing a variant, you can also overwrite it with the content of any existing post.', 'user', 'nelio-ab-testing' ),
		),
		'defaults'           => array(
			'original'    => array(
				'postType' => 'post',
				'postId'   => 0,
			),
			'alternative' => array(
				'name'   => '',
				'postId' => 0,
			),
		),
		'expected'           => array(
			'pageViews' => array(
				'average' => 5000,
				'median'  => 1000,
				'p25'     => 500,
				'p75'     => 3500,
			),
		),
	);
	return $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_post_type', 11 );

/**
 * Registers the custom post type experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_custom_post_type( $types ) {
	$post_types = get_testable_custom_post_types();
	if ( empty( $post_types ) ) {
		return $types;
	}

	$types['nab/custom-post-type'] = array(
		'name'               => 'nab/custom-post-type',
		'category'           => 'page',
		'title'              => _x( 'Custom Post Type', 'text (experiment name)', 'nelio-ab-testing' ),
		'description'        => _x( 'Tests substantial changes to an item from a custom post type, including its content, layout, and template.', 'text', 'nelio-ab-testing' ),
		'labels'             => array( 'create' => _x( 'Add new A/B test of custom post types', 'command', 'nelio-ab-testing' ) ),
		'supportedPostTypes' => $post_types,
		'scope'              => 'tested-post-with-consistency',
		'help'               => array(
			'original'    => _x( 'Split tests of custom post types help you to improve the conversion rate of your WordPress site by creating one or more variants of your content. The control version (commonly known as the “A” version) is the already-existing content from your site you want to run the test on.', 'user', 'nelio-ab-testing' ),
			'alternative' => _x( 'You can create one or more variants of your tested element. By default, each variant you create will be an exact copy of the tested element. Click on the “Edit” link of each variant to tweak it as you please. When editing a variant, you can also overwrite it with the content of any existing element in the selected type.', 'user', 'nelio-ab-testing' ),
		),
		'defaults'           => array(
			'original'    => array(
				'postType' => '',
				'postId'   => 0,
			),
			'alternative' => array(
				'name'   => '',
				'postId' => 0,
			),
		),
		'expected'           => array(
			'pageViews' => array(
				'average' => 5000,
				'median'  => 1000,
				'p25'     => 500,
				'p75'     => 3500,
			),
		),
	);
	return $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_custom_post_type', 12 );
