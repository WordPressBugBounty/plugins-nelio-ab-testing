<?php

namespace Nelio_AB_Testing\WooCommerce\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_meta_box;

use function Nelio_AB_Testing\WooCommerce\Helpers\Product_Selection\do_products_match;

/**
 * Callback to add testing meta box to WooCommerce order pages.
 *
 * @param string   $post_type Post type.
 * @param \WP_Post $post Post.
 *
 * @return void
 */
function add_testing_meta_box( $post_type, $post ) {
	if ( ! in_array( $post_type, array( 'shop_order', 'woocommerce_page_wc-orders' ), true ) ) {
		return;
	}

	if ( ! current_user_can( 'read_nab_results' ) ) {
		return;
	}

	$order = 'shop_order' === $post_type ? wc_get_order( $post->ID ) : $post;
	if ( empty( $order ) || ! ( $order instanceof \WC_Order ) ) {
		return;
	}

	$experiments = get_experiments( $order );
	if ( empty( $experiments ) ) {
		return;
	}

	add_meta_box(
		'nelioab_testing_box',
		'Nelio A/B Testing',
		__NAMESPACE__ . '\render_meta_box',
		$post_type,
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', __NAMESPACE__ . '\add_testing_meta_box', 10, 2 );

/**
 * Callback to render meta box.
 *
 * @return void
 */
function render_meta_box() {
	$order = wc_get_order( get_the_ID() );
	if ( empty( $order ) || ! ( $order instanceof \WC_Order ) ) {
		return;
	}

	$experiments = get_experiments( $order );
	/** @var list<string>|'' */
	$synched_goals = $order->get_meta( '_nab_synched_goals', true );
	$synched_goals = ! empty( $synched_goals ) ? $synched_goals : array();

	printf(
		'<p>%s</p>',
		esc_html_x( 'Tests in which the visitor participated and the variant they saw:', 'text', 'nelio-ab-testing' )
	);

	$is_experiment = function ( $id ) {
			/** @var int $id */
		return function ( $sync_goal ) use ( $id ) {
			/** @var string $sync_goal */
			return 0 === strpos( $sync_goal, "{$id}:" );
		};
	};

	$get_goal_index = function ( $sync_goal ) {
		/** @var string $sync_goal */
		return absint( explode( ':', $sync_goal )[1] ?? '' );
	};

	$purchased_product_ids = get_product_ids( $order );

	echo '<ul>';
	foreach ( $experiments as $experiment ) {
		$id = $experiment['id'];
		$sg = array_filter( $synched_goals, $is_experiment( $id ) );
		$sg = array_map( $get_goal_index, $sg );
		render_experiment( $experiment, array_values( $sg ), $purchased_product_ids );
	}
	echo '</ul>';
}

/**
 * Renders the experiment.
 *
 * @param TWC_Metabox_Experiment $experiment            Experiment.
 * @param list<int>              $synched_goals         Synched goals.
 * @param list<int>              $purchased_product_ids Purchased product IDs.
 *
 * @return void
 */
function render_experiment( $experiment, $synched_goals, $purchased_product_ids ) {
	$alt = chr( ord( 'A' ) + $experiment['alt'] );
	$alt = sprintf(
		/* translators: %s: Variant letter (A, B, C, ...). */
		_x( 'variant %s', 'text', 'nelio-ab-testing' ),
		esc_html( $alt )
	);

	$wc_goals = array_map(
		function ( $g ) use ( $purchased_product_ids ) {
			$actions = $g['conversionActions'];
			if ( empty( $actions ) ) {
				return false;
			}
			if ( 'nab/wc-order' !== $actions[0]['type'] ) {
				return false;
			}
			/** @var TWC_Order_Attributes $action */
			$action = $actions[0]['attributes'];
			if ( ! do_products_match( $action['value'], $purchased_product_ids ) ) {
				return false;
			}
			return true;
		},
		$experiment['goals']
	);
	$wc_goals = array_keys( array_filter( $wc_goals ) );

	if ( empty( $synched_goals ) ) {
		$exp_status = _x( 'Not Synched', 'text (order sync status)', 'nelio-ab-testing' );
	} elseif ( count( $synched_goals ) < count( $wc_goals ) ) {
		$exp_status = _x( 'Partially Synched', 'text (order sync status)', 'nelio-ab-testing' );
	} else {
		$exp_status = _x( 'Synched', 'text (order sync status)', 'nelio-ab-testing' );
	}

	$style = 'list-style:disc; margin-left: 1.2em';
	if ( $experiment['link'] ) {
		printf(
			'<li style="%s"><a href="%s">%s</a> (%s)<br>%s: <em>%s</em></li>',
			esc_attr( $style ),
			esc_url( $experiment['link'] ),
			esc_html( $experiment['name'] ),
			esc_html( $alt ),
			esc_html_x( 'Status', 'text', 'nelio-ab-testing' ),
			esc_html( $exp_status )
		);
	} else {
		printf(
			'<li style="%s">%s (%s)<br>%s: <em>%s</em></li>',
			esc_attr( $style ),
			esc_html( $experiment['name'] ),
			esc_html( $alt ),
			esc_html_x( 'Status', 'text', 'nelio-ab-testing' ),
			esc_html( $exp_status )
		);
	}
}

/**
 * Returns experiments associated to the order.
 *
 * @param \WC_Order $order Order.
 *
 * @return list<TWC_Metabox_Experiment>
 */
function get_experiments( $order ) {
	/** @var \wpdb $wpdb */
	global $wpdb;

	/** @var array<int,int>|'' */
	$exp_alt_map = $order->get_meta( '_nab_experiments_with_page_view', true );
	$exp_alt_map = ! empty( $exp_alt_map ) ? $exp_alt_map : array();

	$exp_ids = array_map( fn( $k ) => absint( $k ), array_keys( $exp_alt_map ) );
	if ( empty( $exp_ids ) ) {
		return array();
	}

	$purchased_product_ids = get_product_ids( $order );
	$exp_ids               = array_values(
		array_filter(
			$exp_ids,
			function ( $eid ) use ( &$purchased_product_ids ) {
				$wc_order_actions = get_order_actions( $eid );
				foreach ( $wc_order_actions as $action ) {
					if ( do_products_match( $action['value'], $purchased_product_ids ) ) {
						return true;
					}
				}
				return false;
			}
		)
	);

	$placeholders = implode( ',', array_fill( 0, count( $exp_ids ), '%d' ) );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$experiments = $wpdb->get_results(
		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT ID AS id, post_title AS name, post_status as status FROM %i p WHERE p.post_type = %s AND p.ID IN ({$placeholders})",
			array_merge(
				array( $wpdb->posts, 'nab_experiment' ),
				$exp_ids
			)
		),
		ARRAY_A
	);

	/** @var list<array{id:int,name:string,status:string}> */
	$experiments = ! empty( $experiments ) ? $experiments : array();

	$experiments = array_combine(
		array_map( fn( $e ) => $e['id'], $experiments ),
		$experiments
	);

	return array_map(
		function ( $id ) use ( &$exp_alt_map, &$experiments ) {
			/* translators: %d: Test ID. */
			$unknown = _x( 'Test %d is no longer available', 'text', 'nelio-ab-testing' );

			$goals = get_post_meta( $id, '_nab_goals', true );
			$goals = ! empty( $goals ) ? $goals : array();

			$res = array(
				'id'    => $id,
				'link'  => false,
				'name'  => sprintf( $unknown, $id ),
				'alt'   => absint( $exp_alt_map[ $id ] ?? 0 ),
				'goals' => $goals,
			);

			if ( isset( $experiments[ $id ] ) ) {
				$exp = $experiments[ $id ];

				$res['name'] = "{$exp['name']}";
				if ( in_array( $exp['status'], array( 'nab_running', 'nab_finished' ), true ) ) {
					$res['link'] = add_query_arg(
						array(
							'page'       => 'nelio-ab-testing-experiment-view',
							'experiment' => $id,
						),
						admin_url( 'admin.php' )
					);
				}
			}

			return $res;
		},
		$exp_ids
	);
}

/**
 * Helper function to get all order actions within an experiment ID.
 *
 * @param int $experiment_id Experiment ID.
 *
 * @return array<TWC_Order_Attributes>
 */
function get_order_actions( $experiment_id ) {
	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return array();
	}

	$goals   = $experiment->get_goals();
	$actions = array_map( fn( $g ) => $g['conversionActions'], $goals );
	/** @var list<TConversion_Action> */
	$actions = array_reduce( $actions, fn( $r, $actual_actions ) => array_merge( $r, $actual_actions ), array() );
	$actions = array_filter( $actions, fn( $a ) => 'nab/wc-order' === $a['type'] );
	/** @var array<TWC_Order_Attributes> */
	$actions = array_map( fn( $a ) => $a['attributes'], $actions );
	return array_values( $actions );
}
