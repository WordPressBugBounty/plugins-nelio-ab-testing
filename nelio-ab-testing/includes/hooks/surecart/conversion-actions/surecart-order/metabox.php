<?php

namespace Nelio_AB_Testing\SureCart\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

function maybe_add_testing_meta_box() {
	$screen = get_current_screen();
	if ( 'surecart_page_sc-orders' !== $screen->id ) {
		return;
	}//end if

	if ( ! current_user_can( 'read_nab_results' ) ) {
		return;
	}//end if

	if ( ! isset( $_GET['action'] ) || ! isset( $_GET['id'] ) || 'edit' !== $_GET['action'] ) { // phpcs:ignore
		return;
	}//end if

	$order_id = sanitize_text_field( $_GET['id'] ); // phpcs:ignore

	add_action(
		'admin_footer',
		function () use ( $order_id ) {
			render_meta_box( $order_id );
		},
		999
	);
}//end maybe_add_testing_meta_box()
add_action( 'current_screen', __NAMESPACE__ . '\maybe_add_testing_meta_box' );

function render_meta_box( $order_id ) {
	$order = \SureCart\Models\Order::find( $order_id );
	if ( empty( $order ) ) {
		return;
	}//end if

	$checkout_id = $order->getAttribute( 'checkout' );
	$checkout    = \SureCart\Models\Checkout::find( $checkout_id );
	if ( empty( $checkout ) ) {
		return;
	}//end if

	$metadata = $checkout->getAttribute( 'metadata' );
	if ( empty( $metadata ) ) {
		return;
	}//end if

	$data = $metadata->nabmetadata;
	if ( empty( $data ) ) {
		return;
	}//end if

	$value = json_decode( $data, ARRAY_A );
	if ( empty( $value ) || ! is_array( $value ) ) {
		return;
	}//end if

	$synched_goals = $value['_nab_synched_goals'];
	$exp_alt_map   = $value['_nab_experiments_with_page_view'];
	$experiments   = get_experiments( $exp_alt_map );
	$synched_goals = ! empty( $synched_goals ) ? $synched_goals : array();

	$is_experiment = function ( $id ) {
		return function ( $sync_goal ) use ( $id ) {
			return 0 === strpos( $sync_goal, "{$id}:" );
		};
	};

	$get_goal_index = function ( $sync_goal ) {
		return absint( explode( ':', $sync_goal )[1] );
	};

	?>
	<script type="text/template" id="nab-surecart-metabox">
	<div id="surecart-order-nab" class="surecart-order-data">
		<h3 style="margin: 0;font-weight: 600;font-size: 1em;">
			<?php echo esc_html_x( 'Nelio A/B Testing', 'text', 'nelio-ab-testing' ); ?>
		</h3>

		<div class="inside">
			<div class="surecart-admin-box">
				<div class="surecart-admin-box-inside">
					<p><?php echo esc_html_x( 'Tests in which the visitor participated and the variant they saw:', 'text', 'nelio-ab-testing' ); ?></p>
					<ul style="font-size: 13px;margin:0;">
					<?php
					foreach ( $experiments as $experiment ) {
						$id = $experiment['id'];
						$sg = array_filter( $synched_goals, $is_experiment( $id ) );
						$sg = array_map( $get_goal_index, $sg );
						render_experiment( $experiment, array_values( $sg ) );
					}//end foreach
					?>
					</ul>
				</div>
			</div>
		</div>
	</div>
	</script>
	<script type="text/javascript">
		document.addEventListener( 'DOMContentLoaded', function() {
			const domObserver = new MutationObserver( ( _, observer ) => {
				const el = [ ...document.querySelectorAll( 'sc-text' ) ]
					.find( el => el.textContent.includes( 'nabmetadata' ) );
				if ( el ) {
					const templateContent = document.getElementById( 'nab-surecart-metabox' ).innerHTML;
					const container = el.parentElement;
					container.innerHTML = templateContent;
					if ( container.previousSibling ) {
						container.style.marginTop = '1em';
					}//end if
					if ( container.nextSibling ) {
						container.style.marginBottom = '1em';
					}//end if
					observer.disconnect();
				}//end if
			});

			domObserver.observe( document.getElementById( 'app' ), { childList: true, subtree: true } );
		});
	</script>
	<?php
}//end render_meta_box()

function render_experiment( $exp, $synched_goals ) {
	$alt = chr( ord( 'A' ) + $exp['alt'] );
	$alt = sprintf(
		/* translators: variant letter (A, B, C, ...) */
		_x( 'variant %s', 'text', 'nelio-ab-testing' ),
		esc_html( $alt )
	);

	$edd_goals = array_map(
		function ( $g ) {
			$actions = wp_list_pluck( $g['conversionActions'], 'type' );
			$actions = array_values( array_unique( $actions ) );
			return count( $actions ) === 1 && 'nab/surecart-order' === $actions[0];
		},
		$exp['goals']
	);
	$edd_goals = array_keys( array_filter( $edd_goals ) );

	if ( empty( $synched_goals ) ) {
		$exp_status = _x( 'Not Synched', 'text (order sync status)', 'nelio-ab-testing' );
	} elseif ( count( $synched_goals ) < count( $edd_goals ) ) {
		$exp_status = _x( 'Partially Synched', 'text (order sync status)', 'nelio-ab-testing' );
	} else {
		$exp_status = _x( 'Synched', 'text (order sync status)', 'nelio-ab-testing' );
	}//end if

	$style = 'list-style:disc; margin-left: 1.2em';
	if ( $exp['link'] ) {
		printf(
			'<li style="%s"><a href="%s">%s</a> (%s)<br>%s: <em>%s</em></li>',
			esc_attr( $style ),
			esc_url( $exp['link'] ),
			esc_html( $exp['name'] ),
			esc_html( $alt ),
			esc_html_x( 'Status', 'text', 'nelio-ab-testing' ),
			esc_html( $exp_status )
		);
	} else {
		printf(
			'<li style="%s">%s (%s)<br>%s: <em>%s</em></li>',
			esc_attr( $style ),
			esc_html( $exp['name'] ),
			esc_html( $alt ),
			esc_html_x( 'Status', 'text', 'nelio-ab-testing' ),
			esc_html( $exp_status )
		);
	}//end if
}//end render_experiment()

function get_experiments( $exp_alt_map ) {
	global $wpdb;

	$exp_ids = array_map( 'absint', array_keys( $exp_alt_map ) );

	// phpcs:ignore
	$experiments = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID AS id, post_title AS name, post_status as status
			 FROM $wpdb->posts p
			 WHERE p.post_type = %s AND p.ID IN (%2\$s)", // phpcs:ignore
			array( 'nab_experiment', implode( ',', $exp_ids ) )
		),
		ARRAY_A
	);
	$experiments = array_combine(
		wp_list_pluck( $experiments, 'id' ),
		$experiments
	);

	return array_map(
		function ( $id ) use ( &$exp_alt_map, &$experiments ) {
			/* translators: test ID */
			$unknown = _x( 'Test %d is no longer available', 'text', 'nelio-ab-testing' );

			$goals = get_post_meta( $id, '_nab_goals', true );
			$goals = empty( $goals ) ? array() : $goals;

			$res = array(
				'id'    => $id,
				'link'  => false,
				'name'  => sprintf( $unknown, $id ),
				'alt'   => isset( $exp_alt_map[ $id ] ) ? $exp_alt_map[ $id ] : 0,
				'goals' => $goals,
			);

			if ( isset( $experiments[ $id ] ) ) {
				$exp = $experiments[ $id ];

				$res['name'] = $exp['name'];
				if ( in_array( $exp['status'], array( 'nab_running', 'nab_finished' ), true ) ) {
					$res['link'] = add_query_arg(
						array(
							'page'       => 'nelio-ab-testing-experiment-view',
							'experiment' => $id,
						),
						admin_url( 'admin.php' )
					);
				}//end if
			}//end if

			return $res;
		},
		$exp_ids
	);
}//end get_experiments()
