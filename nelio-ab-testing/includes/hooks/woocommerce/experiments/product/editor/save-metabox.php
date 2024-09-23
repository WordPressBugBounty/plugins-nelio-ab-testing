<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Editor;

defined( 'ABSPATH' ) || exit;

use function add_action;

use function add_meta_box;
use function remove_meta_box;

function add_save_metabox() {
	remove_meta_box( 'submitdiv', 'nab_alt_product', 'side' );
	add_meta_box(
		'submitdiv',
		__( 'Nelio A/B Testing', 'nelio-ab-testing' ),
		__NAMESPACE__ . '\render_save_metabox',
		'nab_alt_product',
		'side',
		'high',
		array(
			'__back_compat_meta_box' => true,
		)
	);
}//end add_save_metabox()
add_action( 'add_meta_boxes', __NAMESPACE__ . '\add_save_metabox' );


function render_save_metabox( $post ) {
	?>
	<div id="nab-experiment-summary" style="padding:10px 10px 0">
		<span class="spinner is-active"></span>
	</div>

	<script type="text/javascript">
		nab.initExperimentSummary(
		<?php
			echo wp_json_encode(
				array(
					'experimentId'    => absint( get_post_meta( $post->ID, '_nab_experiment', true ) ),
					'postBeingEdited' => $post->ID,
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
