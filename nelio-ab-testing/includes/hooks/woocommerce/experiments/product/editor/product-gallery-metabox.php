<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Editor;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_meta_box;
use function wc_clean;

function add_product_gallery_metabox() {
	add_meta_box(
		'woocommerce-product-images',
		__( 'Product gallery', 'woocommerce' ),
		__NAMESPACE__ . '\render_product_gallery_metabox',
		'nab_alt_product',
		'side',
		'low',
		array(
			'__back_compat_meta_box' => true,
		)
	);
}//end add_product_gallery_metabox()
add_action( 'add_meta_boxes', __NAMESPACE__ . '\add_product_gallery_metabox' );

function render_product_gallery_metabox( $post ) {
	$product_id = $post->ID;
	wp_nonce_field( "nab_save_product_gallery_{$product_id}", 'nab_product_gallery_nonce' );
	?>
	<div id="product_images_container">
		<ul class="product_images">
			<?php
			$product_image_gallery = explode( ',', get_post_meta( $product_id, '_product_image_gallery', true ) );

			$attachments         = array_filter( $product_image_gallery );
			$update_meta         = false;
			$updated_gallery_ids = array();

			if ( ! empty( $attachments ) ) {
				foreach ( $attachments as $attachment_id ) {
					$attachment = wp_get_attachment_image( $attachment_id, 'thumbnail' );

					// if attachment is empty skip.
					if ( empty( $attachment ) ) {
						$update_meta = true;
						continue;
					}//end if
					?>
					<li class="image" data-attachment_id="<?php echo esc_attr( $attachment_id ); ?>">
						<?php echo wp_get_attachment_image( $attachment_id, 'thumbnail' ); ?>
						<ul class="actions">
							<li><a href="#" class="delete tips" data-tip="<?php esc_attr_e( 'Delete image', 'woocommerce' ); ?>"><?php esc_html_e( 'Delete', 'woocommerce' ); ?></a></li>
						</ul>
					</li>
					<?php

					// rebuild ids to be saved.
					$updated_gallery_ids[] = $attachment_id;
				}//end foreach

				// need to update product meta to set new gallery ids.
				if ( $update_meta ) {
					update_post_meta( $post->ID, '_product_image_gallery', implode( ',', $updated_gallery_ids ) );
				}//end if
			}//end if
			?>
		</ul>

		<input type="hidden" id="product_image_gallery" name="product_image_gallery" value="<?php echo esc_attr( implode( ',', $updated_gallery_ids ) ); ?>" />

	</div>
	<p class="add_product_images hide-if-no-js">
		<a href="#" data-choose="<?php esc_attr_e( 'Add images to product gallery', 'woocommerce' ); ?>" data-update="<?php esc_attr_e( 'Add to gallery', 'woocommerce' ); ?>" data-delete="<?php esc_attr_e( 'Delete image', 'woocommerce' ); ?>" data-text="<?php esc_attr_e( 'Delete', 'woocommerce' ); ?>"><?php esc_html_e( 'Add product gallery images', 'woocommerce' ); ?></a>
	</p>

	<script type="text/javascript">
		nab.initProductGalleryMetabox();
	</script>
	<?php
}//end render_product_gallery_metabox()


function save_product_gallery( $post_id ) {
	if ( ! function_exists( 'wc_clean' ) ) {
		return;
	}//end if

	if ( 'nab_alt_product' !== get_post_type( $post_id ) ) {
		return;
	}//end if

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}//end if

	if ( ! isset( $_POST['nab_product_gallery_nonce'] ) ) {
		return;
	}//end if

	$nonce = sanitize_text_field( wp_unslash( $_POST['nab_product_gallery_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, "nab_save_product_gallery_{$post_id}" ) ) {
		return;
	}//end if

	// phpcs:ignore
	$image_gallery  = isset( $_POST['product_image_gallery'] ) ? sanitize_text_field( $_POST['product_image_gallery'] ) : '';
	$attachment_ids = array_filter( array_map( 'absint', explode( ',', $image_gallery ) ) );
	update_post_meta( $post_id, '_product_image_gallery', implode( ',', $attachment_ids ) );
}//end save_product_gallery()
add_action( 'save_post', __NAMESPACE__ . '\save_product_gallery' );
