<?php
namespace Nelio_AB_Testing\Hooks\Experiment_Scope\Preload_Query_Args;

defined( 'ABSPATH' ) || exit;

function generate() {
	$experiments = nab_get_running_experiments();
	$experiments = array_filter(
		$experiments,
		function ( $e ) {
			return false === $e->get_inline_settings();
		}
	);

	return array_map(
		function ( $e ) {
			/**
			 * .
			 *
			 * @var \Nelio_AB_Testing_Experiment $e .
			 */
			$control = $e->get_alternative( 'control' );
			$alts    = wp_list_pluck( $e->get_alternatives(), 'attributes' );
			if ( nab_array_get( $control, 'attributes.testAgainstExistingContent', false ) ) {
				$alts = wp_list_pluck( $alts, 'postId' );
				$urls = array_map( 'get_permalink', $alts );
				return array(
					'type'     => 'alt-urls',
					'altUrls'  => $urls,
					'altCount' => count( $urls ),
				);
			}//end if

			$rules = wp_list_pluck( $e->get_scope(), 'attributes' );
			if ( empty( $rules ) ) {
				return array(
					'type'     => 'scope',
					'scope'    => array( '**' ),
					'altCount' => count( $alts ),
				);
			}//end if

			if (
				'tested-url-with-query-args' === nab_array_get( $rules, '0.type' ) &&
				empty( nab_array_get( $rules, '0.value.args' ) )
			) {
				$urls = nab_array_get( $rules, '0.value.urls' );
				$urls = is_array( $urls ) ? $urls : array();
				return array(
					'type'     => 'alt-urls',
					'altUrls'  => $urls,
					'altCount' => count( $urls ),
				);
			}//end if

			$main = $e->get_tested_post();
			$urls = array_map(
				function ( $rule ) use ( $main ) {
					switch ( $rule['type'] ) {
						case 'tested-post':
							return get_permalink( $main );
						case 'tested-url-with-query-args':
							// This case is already controlled a few lines above.
							return false;
						case 'exact':
							return $rule['value'];
						case 'partial':
							return "*{$rule['value']}*";
						case 'partial-not-included':
							return "!*{$rule['value']}*";
						case 'different':
							return "!{$rule['value']}";
						default:
							return false;
					}//end switch
				},
				$rules
			);
			$urls = array_reduce(
				$urls,
				fn( $c, $v ) => array_merge( $c, is_array( $v ) ? $v : array( $v ) ),
				array()
			);
			$urls = array_values( array_filter( $urls ) );
			return array(
				'type'     => 'scope',
				'scope'    => $urls,
				'altCount' => count( $alts ),
			);
		},
		array_values( $experiments )
	);
}//end generate()
