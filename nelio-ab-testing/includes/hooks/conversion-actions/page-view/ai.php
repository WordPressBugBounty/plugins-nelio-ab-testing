<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Page_View;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/page-view conversion action type.
 *
 * @return TConversion_Action_Type_AI
 */
function register_ai_definition() {
	$entity_schema = array(
		'type'                 => 'object',
		'properties'           => array(
			'mode'     => array(
				'type'        => 'string',
				'enum'        => array( 'id' ),
				'description' => 'Matches the page view by WordPress entity.',
			),
			'postId'   => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Identifier of the existing WordPress entity whose view is tracked.',
			),
			'postType' => array(
				'type'        => 'string',
				'minLength'   => 1,
				'description' => 'Registered WordPress entity type of the entity whose view is tracked.',
			),
			'url'      => array(
				'type'        => 'string',
				'enum'        => array( '' ),
				'description' => 'Must be an empty string when the page view is matched by WordPress entity.',
			),
		),
		'required'             => array( 'mode', 'postId', 'postType', 'url' ),
		'additionalProperties' => false,
	);
	$url_schema    = array(
		'type'                 => 'object',
		'properties'           => array(
			'mode'     => array(
				'type'        => 'string',
				'enum'        => array( 'url' ),
				'description' => 'Matches the page view by URL.',
			),
			'postId'   => array(
				'type'        => 'integer',
				'enum'        => array( 0 ),
				'description' => 'Must be zero when the page view is matched by URL.',
			),
			'postType' => array(
				'type'        => 'string',
				'enum'        => array( 'page' ),
				'description' => sprintf(
					/* translators: %s: WordPress post type. */
					'Must be `%s` when the page view is matched by URL.',
					'page'
				),
			),
			'url'      => array(
				'type'        => 'string',
				'minLength'   => 1,
				'description' => 'Full local URL whose page view is tracked.',
			),
		),
		'required'             => array( 'mode', 'postId', 'postType', 'url' ),
		'additionalProperties' => false,
	);

	return array(
		'useWhen' => 'Use it when reaching that destination is the desired outcome.',
		'schema'  => array(
			'oneOf' => array( $entity_schema, $url_schema ),
		),
	);
}
add_filter( 'nab_nab/page-view_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates page-view conversion action attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TConversion_Action     $action     Conversion action.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate( $validation, $action ) {
	$attributes = $action['attributes'];
	$sanitized  = sanitize( $attributes );
	$issues     = array_map( fn( $issue ) => $issue['message'], nab_ai_compare_attributes( $attributes, $sanitized ) );

	switch ( $attributes['mode'] ?? null ) {
		case 'id':
			$post_type = $attributes['postType'] ?? null;
			$post_id   = absint( $attributes['postId'] ?? 0 );
			if ( ! is_string( $post_type ) || ! get_post_type_object( $post_type ) ) {
				$issues[] = 'The `postType` attribute does not identify a registered WordPress entity type.';
			}
			$post = get_post( $post_id );
			if ( ! $post ) {
				$issues[] = 'The `postId` attribute does not identify an existing WordPress entity.';
			} elseif ( is_string( $post_type ) && $post->post_type !== $post_type ) {
				$issues[] = sprintf(
					'The entity identified by `postId` has type `%1$s`, but the supplied `postType` is `%2$s`.',
					$post->post_type,
					$post_type
				);
			}

			$url = $attributes['url'] ?? '';
			if ( ! is_string( $url ) || '' !== trim( $url ) ) {
				$issues[] = 'The `url` attribute is not empty even though the page view is identified by a WordPress entity.';
			}
			break;

		case 'url':
			$url = $attributes['url'] ?? null;
			$url = is_string( $url ) ? nab_resolve_normalized_url( trim( $url ) ) : '';
			if ( '' === $url ) {
				$issues[] = 'The `url` attribute is empty.';
				break;
			}
			$home_url = untrailingslashit( nab_home_url() );
			if ( 0 !== strpos( $url, $home_url ) || ( $url !== $home_url && 0 !== strpos( $url, $home_url . '/' ) ) ) {
				$issues[] = 'The `url` attribute points outside the WordPress home URL.';
			}

			$post_type = $attributes['postType'] ?? '';
			if ( 'page' !== $post_type ) {
				$issues[] = 'The `postType` attribute is not `page` when `mode` is `url`.';
			}

			$post_id = absint( $attributes['postId'] ?? 0 );
			if ( 0 !== $post_id ) {
				$issues[] = 'The `postId` attribute is not 0 when `mode` is `url`.';
			}
			break;
	}

	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/page-view_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
add_filter( 'nab_nab/page-view_ai_validate_page_view_trigger_attributes', __NAMESPACE__ . '\ai_validate', 10, 2 );
