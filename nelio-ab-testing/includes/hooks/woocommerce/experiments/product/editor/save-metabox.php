<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Editor;

defined( 'ABSPATH' ) || exit;

use function add_action;

use function add_meta_box;
use function remove_meta_box;

function add_save_metabox() {
	$post_id = get_the_ID();
	$product = wc_get_product( $post_id );
	if ( empty( $product ) || 'nab-alt-product' !== $product->get_type() ) {
		return;
	}//end if

	// Remove WordPress’ built-in meta box.
	remove_meta_box( 'submitdiv', 'product', 'side' );

	// Remove NAB’s meta box for saving alternatives.
	remove_meta_box( 'nelioab_edit_post_alternative_box', 'product', 'side' );

	// Add custom meta box for alternative WC products.
	add_meta_box(
		'submitdiv',
		__( 'Nelio A/B Testing', 'nelio-ab-testing' ),
		__NAMESPACE__ . '\render_save_metabox',
		'product',
		'side',
		'high',
		array(
			'__back_compat_meta_box' => true,
		)
	);
}//end add_save_metabox()
add_action( 'add_meta_boxes', __NAMESPACE__ . '\add_save_metabox', 999 );


function render_save_metabox( $post ) {
	/**
	 * .
	 *
	 * @var \Nelio_AB_Testing\WooCommerce\Experiment_Library\Full_Product_Experiment\Alternative_Product $product
	 */
	$product = wc_get_product( $post->ID );
	?>
	<div id="nab-experiment-summary" style="padding:10px 10px 0">
		<span class="spinner is-active"></span>
	</div>

	<script type="text/javascript">
		nab.initExperimentSummary(
		<?php
			echo wp_json_encode(
				array(
					'experimentId'    => absint( $product->get_experiment_id() ),
					'postBeingEdited' => $product->get_id(),
				)
			);
		?>
		);
	</script>

	<div class="submitbox" id="submitpost">
		<div id="minor-publishing">
			<div id="minor-publishing-actions" style="padding:10px">
				<div id="save-action">
					<span class="spinner"></span>
					<input
						type="submit"
						class="button"
						name="save"
						id="save-post"
						value="<?php echo esc_attr_x( 'Save Variant', 'command', 'nelio-ab-testing' ); ?>"
						style="float:right"
					>
				</div>
				<div class="clear"></div>
			</div>
		</div>
	</div>
	<?php
}//end render_save_metabox()
