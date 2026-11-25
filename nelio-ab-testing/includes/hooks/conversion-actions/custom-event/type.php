<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Custom_Event;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_conversion_action_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the custom-event conversion action type definition.
 *
 * @param array<string,TConversion_Action_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TConversion_Action_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/custom-event'] = array(
		'name'                => 'nab/custom-event',
		'category'            => 'other',
		'title'               => _x( 'JavaScript Snippet', 'text', 'nelio-ab-testing' ),
		'description'         => _x( 'Counts a conversion signaled programmatically by a script.', 'text', 'nelio-ab-testing' ),
		'defaults'            => array(
			'snippet' => sprintf(
				"// %1\$s\nwindow.addEventListener( \"event-name\", () => {\n  // %2\$s\n  convert();\n} );",
				sprintf(
					/* translators: %s: event-name. */
					_x( 'Use the appropriate "%s" or create your own snippet.', 'user', 'nelio-ab-testing' ),
					'event-name'
				),
				_x( 'Add any additional logic required.', 'user', 'nelio-ab-testing' )
			),
		),
		'supportsCustomScope' => true,
	);
	return $types;
}
