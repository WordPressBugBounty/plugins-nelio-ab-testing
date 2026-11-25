<?php

namespace Nelio_AB_Testing\Experiment_Library\JavaScript_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Callback to sanitize control attributes.
 *
 * @param TAttributes $control Control attributes.
 *
 * @return TJavaScript_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'chance' => Z::number()->optional()->catch( null ),
				'code'   => Z::string()->trim()->catch( '' ),
			)
		)->catch( array( 'code' => '' ) );
	}

	$parsed = $schema->safe_parse( $control );
	assert( $parsed['success'] );
	/** @var TJavaScript_Control_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/javascript_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Callback to sanitize alternative attributes.
 *
 * @param TAttributes $alternative Alternative attributes.
 *
 * @return TJavaScript_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'   => Z::string()->trim()->catch( '' ),
				'chance' => Z::number()->optional()->catch( null ),
				'code'   => Z::string()->trim()->catch( '' ),
			)
		)->catch(
			array(
				'name' => '',
				'code' => '',
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var TJavaScript_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/javascript_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );

/**
 * Callback to set default snippet on alternative creation.
 *
 * @param TAttributes $alternative Attributes.
 *
 * @return TJavaScript_Alternative_Attributes
 */
function set_default_snippet( $alternative ) {
	$alternative['code'] = sprintf(
		"utils.domReady( function() {\n\n  // %s\n\n  done();\n} );",
		// translators: %s: Placeholder comment shown in the JavaScript alternative editor.
		_x( 'Write your code here…', 'user', 'nelio-ab-testing' )
	);
	return sanitize_alternative_attributes( $alternative );
}
add_filter( 'nab_nab/javascript_create_alternative_content', __NAMESPACE__ . '\set_default_snippet' );
