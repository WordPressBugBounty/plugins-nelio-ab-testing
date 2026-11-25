<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/css experiment type.
 *
 * @return TExperiment_Type_AI
 */
function register_ai_definition() {
	return array(
		'selection' => array(
			'strategy'                => 'preferred',
			'requiresExplicitRequest' => false,
			'useWhen'                 => 'Prefer it first for small visual, styling, image, or wording changes that may be applied with CSS or text changes. Before choosing this test type, request selector discovery for every URL where an element or content change is required and verify that every requested change can be implemented with the exact selectors returned for that URL. If any required change cannot be implemented with those selectors, do not choose a CSS test; consider a JavaScript or full content test instead. Do not treat an ID, class, selector copied from inspected content, or selector that merely appears to match the page as valid on its own; even an ID or class is valid only when selector discovery returns that exact selector for the URL. If discovery reports that a URL has not been processed, ask the user to visit the provided URL and tell you when the selectors are ready; then request discovery again before deciding whether CSS is feasible. It can target one page or multiple pages through its scope. When using it instead of a full content test for a single piece of content, set its scope to that content’s exact URL and no other URL.',
		),
		'schema'    => array(
			'original'    => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance' => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
				),
				'additionalProperties' => false,
			),
			'alternative' => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'  => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'name'    => array(
						'type'        => 'string',
						'description' => 'Name of the alternative.',
					),
					'css'     => array(
						'type'        => 'string',
						'description' => 'CSS rules applied by the alternative.',
					),
					'content' => array(
						'type'        => 'array',
						'description' => 'Element or image changes applied to the rendered page.',
						'items'       => array(
							'oneOf' => array(
								array(
									'type'                 => 'object',
									'properties'           => array(
										'type'     => array(
											'type'        => 'string',
											'const'       => 'element',
											'description' => 'Identifies this change as an HTML element change.',
										),
										'selector' => array(
											'type'        => 'string',
											'description' => 'CSS selector of the element to change. Selector discovery is mandatory for the target URL. MUST use the exact selector returned for that URL; an ID, class, selector copied from inspected content, or selector that merely appears to match the page is not valid on its own.',
										),
										'html'     => array(
											'type'        => 'string',
											'description' => 'HTML that replaces the selected element. When the replacement contains HTML tags, use only the tags listed in `allowedTags` for this selector by selector discovery. Use formatting tags such as `strong`, `em`, or `u` sparingly; they are allowed, but emphasis is generally unnecessary unless the requested change specifically calls for it.',
										),
									),
									'required'             => array( 'type', 'selector', 'html' ),
									'additionalProperties' => false,
								),
								array(
									'type'                 => 'object',
									'properties'           => array(
										'type'     => array(
											'type'        => 'string',
											'const'       => 'image',
											'description' => 'Identifies this change as an image change.',
										),
										'selector' => array(
											'type'        => 'string',
											'description' => 'CSS selector of the image to change. Selector discovery is mandatory for the target URL. MUST use the exact selector returned for that URL; an ID, class, selector copied from inspected content, or selector that merely appears to match the page is not valid on its own.',
										),
										'src'      => array(
											'type'        => 'string',
											'description' => 'Image URL applied to the selected image.',
										),
										'alt'      => array(
											'type'        => 'string',
											'description' => 'Alternative text applied to the selected image.',
										),
									),
									'required'             => array( 'type', 'selector', 'src', 'alt' ),
									'additionalProperties' => false,
								),
							),
						),
					),
				),
				'required'             => array( 'name', 'css', 'content' ),
				'additionalProperties' => false,
			),
		),
	);
}
add_filter( 'nab_nab/css_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates CSS test control attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TAttributes            $attributes Control attributes.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate_control_attributes( $validation, $attributes ) {
	$sanitized = sanitize_control_attributes( $attributes );
	$issues    = nab_ai_compare_attributes( $attributes, $sanitized );
	foreach ( $issues as $issue ) {
		if ( in_array( $issue['type'], array( 'removed', 'changed' ), true ) ) {
			$validation['issues'][] = $issue['message'];
		}
	}
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/css_ai_validate_control_attributes', __NAMESPACE__ . '\ai_validate_control_attributes', 10, 2 );
/**
 * Validates CSS test variant attributes.
 *
 * @param TAttributes_Validation $validation    Previous validation result.
 * @param TAttributes            $attributes    Variant attributes.
 * @param int                    $experiment_id Experiment identifier.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate_alternative_attributes( $validation, $attributes, $experiment_id ) {
	$sanitized = sanitize_alternative_attributes( $attributes );
	$issues    = nab_ai_compare_attributes( $attributes, $sanitized );
	foreach ( $issues as $issue ) {
		if ( in_array( $issue['type'], array( 'removed', 'changed' ), true ) ) {
			$validation['issues'][] = $issue['message'];
		}
	}
	$content = $attributes['content'] ?? array();
	if ( ! is_array( $content ) ) {
		$content = array();
	}
	foreach ( $content as $change ) {
		if ( ! is_array( $change ) ) {
			continue;
		}
		if ( 'element' !== ( $change['type'] ?? '' ) ) {
			continue;
		}

		$html = $change['html'] ?? '';
		$html = is_string( $html ) ? trim( $html ) : '';
		if ( empty( $html ) ) {
			$validation['issues'][] = 'Content changes can’t be empty.';
		}
	}

	if ( ! empty( $content ) ) {
		$url = get_unique_scope_url( $experiment_id );
		if ( false === $url ) {
			$validation['issues'][] = 'This variant cannot be edited through the agent because its test scope does not identify one unique URL where the CSS content changes will run. Ask the user to edit the CSS test manually in the browser.';
		} else {
			$discovery = \Nelio_AB_Testing_Css_Selector_Discovery_Store::get( $url );
			if ( ! is_array( $discovery ) || 'ready' !== $discovery['status'] ) {
				$validation['issues'][] = sprintf(
					'Selector discovery has not been completed for `%s`. Ask the user to complete selector discovery for this URL before editing the CSS variant through the agent.',
					$url
				);
			} else {
				foreach ( $content as $change ) {
					if ( ! is_array( $change ) ) {
						continue;
					}

					$selector = $change['selector'] ?? '';
					if ( ! is_string( $selector ) || ! isset( $discovery['selectors'][ $selector ] ) ) {
						$validation['issues'][] = sprintf(
							'The `%s` selector is not available in selector discovery for `%s`.',
							is_string( $selector ) ? $selector : '',
							$url
						);
						continue;
					}

					if ( 'element' !== ( $change['type'] ?? '' ) ) {
						continue;
					}

					$allowed_tags = $discovery['selectors'][ $selector ]['allowedTags'];
					$html         = $change['html'] ?? '';
					if ( ! is_string( $html ) || ! is_valid_content_html( $html, $allowed_tags ) ) {
						$validation['issues'][] = sprintf(
							'The HTML for selector `%1$s` uses tags that are not allowed for the discovered element. Allowed tags: %2$s.',
							$selector,
							implode( ', ', array_map( fn( $t ) => "`$t`", $allowed_tags ) )
						);
					} elseif ( in_array( 'li', $allowed_tags, true ) && 1 !== preg_match( '/<\s*li\b/i', $html ) ) {
						$validation['issues'][] = sprintf(
							'The HTML for selector `%s` must contain one or more top-level `li` elements because this selector corresponds to a list element.',
							$selector
						);
					}
				}
			}
		}
	}
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/css_ai_validate_alternative_attributes', __NAMESPACE__ . '\ai_validate_alternative_attributes', 10, 3 );

/**
 * Returns the unique URL targeted by a CSS test scope.
 *
 * @param int $experiment_id Experiment identifier.
 *
 * @return string|false Unique URL, or false when the scope does not identify one.
 */
function get_unique_scope_url( $experiment_id ) {
	if ( $experiment_id <= 0 ) {
		return false;
	}

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return false;
	}

	$scope = $experiment->get_scope();
	if ( 1 !== count( $scope ) ) {
		return false;
	}

	$rule       = $scope[0];
	$attributes = $rule['attributes'];
	if ( 'exact' !== $attributes['type'] ) {
		return false;
	}

	$url = nab_resolve_normalized_url( $attributes['value'] );
	if ( '' === $url || false !== nab_ai_is_url_invalid( $url ) ) {
		return false;
	}

	$home = untrailingslashit( nab_home_url() );
	if ( 0 !== strpos( untrailingslashit( $url ), $home ) ) {
		return false;
	}

	return $url;
}

/**
 * Checks whether element content uses the allowed HTML structure.
 *
 * @param mixed        $html         HTML content.
 * @param list<string> $allowed_tags Allowed HTML tags.
 *
 * @return bool Whether the content is valid.
 */
function is_valid_content_html( $html, $allowed_tags ) {
	if ( ! is_string( $html ) ) {
		return false;
	}

	$matches = array();
	if ( false === preg_match_all( '/<!--.*?-->|<\/?\s*[a-z][^>]*>/is', $html, $matches, PREG_OFFSET_CAPTURE ) ) {
		return false; // @codeCoverageIgnore
	}

	$stack                 = array();
	$offset                = 0;
	$has_list_items        = false;
	$has_top_level_text    = false;
	$has_top_level_nonlist = false;
	foreach ( $matches[0] as $match ) {
		$token = $match[0];
		$start = $match[1];
		$text  = substr( $html, $offset, $start - $offset );
		if ( empty( $stack ) && '' !== trim( $text ) ) {
			if ( $has_list_items ) {
				return false;
			}
			$has_top_level_text = true;
		}
		$offset = $start + strlen( $token );

		if ( 0 === strpos( $token, '<!--' ) ) {
			return false;
		}

		$parts = array();
		if ( 1 !== preg_match( '/^<\s*(\/?)\s*([a-z][a-z0-9]*)\b([^>]*)>$/i', $token, $parts ) ) {
			return false;
		}

		$is_closing = '/' === $parts[1];
		$tag        = strtolower( $parts[2] );
		if ( ! in_array( $tag, $allowed_tags, true ) ) {
			return false;
		}

		if ( $is_closing ) {
			if ( '' !== trim( $parts[3] ) ) {
				return false;
			}
			if ( empty( $stack ) || array_pop( $stack ) !== $tag ) {
				return false;
			}
			continue;
		}

		if ( '/>' === substr( trim( $token ), -2 ) || ( 'li' === $tag && ! empty( $stack ) ) ) {
			return false;
		}
		if ( ! are_html_tag_attributes_valid( $tag, $parts[3] ) ) {
			return false;
		}
		if ( 'a' === $tag && in_array( 'a', $stack, true ) ) {
			return false;
		}
		if ( empty( $stack ) ) {
			if ( 'li' === $tag ) {
				if ( $has_top_level_text || $has_top_level_nonlist ) {
					return false;
				}
				$has_list_items = true;
			} elseif ( $has_list_items ) {
				return false;
			} else {
				$has_top_level_nonlist = true;
			}
		}
		$stack[] = $tag;
	}

	if ( ! empty( $stack ) ) {
		return false;
	}

	$tail = substr( $html, $offset );
	return $has_list_items ? '' === trim( $tail ) : true;
}

/**
 * Checks whether an HTML tag contains only supported attributes.
 *
 * @param string $tag       HTML tag name.
 * @param string $attribute_text Raw attribute text.
 *
 * @return bool Whether the attributes are supported.
 */
function are_html_tag_attributes_valid( $tag, $attribute_text ) {
	$attribute_text = trim( $attribute_text );
	if ( '' === $attribute_text ) {
		return true;
	}

	if ( 'a' !== $tag ) {
		return false;
	}

	$offset          = 0;
	$allowed         = array( 'target', 'href', 'rel' );
	$attribute_count = strlen( $attribute_text );
	while ( $offset < $attribute_count ) {
		$matches = array();
		if ( 1 !== preg_match( '/\G\s*([a-z_:][a-z0-9_.:-]*)(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\'`=<>]+))?/i', $attribute_text, $matches, 0, $offset ) ) {
			return false;
		}

		if ( ! in_array( strtolower( $matches[1] ), $allowed, true ) ) {
			return false;
		}
		$offset += strlen( $matches[0] );
	}

	return true;
}
