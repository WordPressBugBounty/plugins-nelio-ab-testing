<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function get_permalink;
use function wc_get_product;
use function Nelio_AB_Testing\WooCommerce\Helpers\Actions\notify_alternative_loaded;
use function Nelio_AB_Testing\Experiment_Library\Post_Experiment\use_control_comments_in_alternative;

// We need a “mid” priority to be able to load Elementor alternative content.
// But it can’t be “high” because, if it is, then test scope can’t be properly evaluated.
add_action( 'nab_nab/wc-product_experiment_priority', fn() => 'mid' );


function load_alternative( $alternative, $control, $experiment_id ) {

	add_filter(
		'nab_enable_custom_woocommerce_hooks',
		function ( $enabled, $product_id ) use ( $control ) {
			return $enabled || $product_id === $control['postId'];
		},
		10,
		2
	);

	add_filter(
		'nab_woocommerce_is_price_testing_enabled',
		function ( $enabled, $product_id ) use ( $control ) {
			if ( $product_id !== $control['postId'] ) {
				return $enabled;
			}//end if
			return empty( $control['disablePriceTesting'] );
		},
		10,
		2
	);

	add_action(
		'wp',
		function () use ( $control, $alternative, $experiment_id ) {
			if ( ! is_singular( 'product' ) ) {
				return;
			}//end if
			$current_id     = get_the_ID();
			$control_id     = nab_array_get( $control, 'postId', false );
			$alternative_id = nab_array_get( $alternative, 'postId', false );
			if ( $current_id === $control_id || $current_id === $alternative_id ) {
				notify_alternative_loaded( $experiment_id );
			}//end if
		}
	);

	$alt_product = get_alt_product( $alternative, $control['postId'], $experiment_id );
	if ( $alt_product->is_proper_woocommerce_product() ) {
		add_hooks_to_switch_products( $alt_product );

		/**
		 * Runs when loading an alternative WooCommerce product.
		 *
		 * @param \Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\IRunning_Alternative_Product $alt_product The product.
		 * @param number $experiment_id The experiment ID.
		 *
		 * @since 7.4.5
		 */
		do_action( 'nab_load_proper_alternative_woocommerce_product', $alt_product, $experiment_id );
	}//end if

	add_nab_filter(
		'woocommerce_product_name',
		function ( $name, $product_id ) use ( &$alt_product ) {
			if ( $product_id !== $alt_product->get_control_id() ) {
				return $name;
			}//end if

			notify_alternative_loaded( $alt_product->get_experiment_id() );
			if ( $alt_product->should_use_control_value() ) {
				return $name;
			}//end if

			return $alt_product->get_name();
		},
		1,
		2
	);

	if ( $alt_product->is_description_supported() ) {
		add_nab_filter(
			'woocommerce_product_description',
			function ( $description, $product_id ) use ( &$alt_product ) {
				if ( $product_id !== $alt_product->get_control_id() ) {
					return $description;
				}//end if

				notify_alternative_loaded( $alt_product->get_experiment_id() );
				if ( $alt_product->should_use_control_value() ) {
					return $description;
				}//end if

				return $alt_product->get_description();
			},
			1,
			2
		);
	}//end if

	add_nab_filter(
		'woocommerce_product_short_description',
		function ( $short_description, $product_id ) use ( &$alt_product ) {
			if ( $product_id !== $alt_product->get_control_id() ) {
				return $short_description;
			}//end if

			notify_alternative_loaded( $alt_product->get_experiment_id() );
			if ( $alt_product->should_use_control_value() ) {
				return $short_description;
			}//end if

			return $alt_product->get_short_description();
		},
		1,
		2
	);

	add_nab_filter(
		'woocommerce_product_image_id',
		function ( $image_id, $product_id ) use ( &$alt_product ) {
			if ( $product_id !== $alt_product->get_control_id() ) {
				return $image_id;
			}//end if

			notify_alternative_loaded( $alt_product->get_experiment_id() );
			if ( $alt_product->should_use_control_value() ) {
				return $image_id;
			}//end if

			return $alt_product->get_image_id();
		},
		1,
		2
	);

	if ( $alt_product->is_gallery_supported() ) {
		add_nab_filter(
			'woocommerce_product_gallery_ids',
			function ( $image_ids, $product_id ) use ( &$alt_product ) {
				if ( $product_id !== $alt_product->get_control_id() ) {
					return $image_ids;
				}//end if

				notify_alternative_loaded( $alt_product->get_experiment_id() );
				if ( $alt_product->should_use_control_value() ) {
					return $image_ids;
				}//end if

				return $alt_product->get_gallery_image_ids();
			},
			1,
			2
		);
	}//end if

	if ( ! $alt_product->has_variation_data() ) {

		add_nab_filter(
			'woocommerce_product_regular_price',
			function ( $price, $product_id ) use ( &$alt_product ) {
				if ( $product_id !== $alt_product->get_control_id() ) {
					return $price;
				}//end if

				notify_alternative_loaded( $alt_product->get_experiment_id() );
				if ( $alt_product->should_use_control_value() ) {
					return $price;
				}//end if

				$regular_price = $alt_product->get_regular_price();
				return empty( $regular_price ) ? $price : $regular_price;
			},
			1,
			2
		);

		if ( $alt_product->is_sale_price_supported() ) {
			add_nab_filter(
				'woocommerce_product_sale_price',
				function ( $price, $product_id, $regular_price ) use ( &$alt_product ) {
					if ( $product_id !== $alt_product->get_control_id() ) {
						return $price;
					}//end if

					notify_alternative_loaded( $alt_product->get_experiment_id() );
					if ( $alt_product->should_use_control_value() ) {
						return $price;
					}//end if

					$sale_price = $alt_product->get_sale_price();
					return empty( $sale_price ) ? $regular_price : $sale_price;
				},
				1,
				3
			);
		}//end if
	} else {

		add_nab_filter(
			'woocommerce_variation_description',
			function ( $short_description, $product_id, $variation_id ) use ( &$alt_product ) {
				if ( $product_id !== $alt_product->get_control_id() ) {
					return $short_description;
				}//end if

				notify_alternative_loaded( $alt_product->get_experiment_id() );
				if ( $alt_product->should_use_control_value() ) {
					return $short_description;
				}//end if

				return $alt_product->get_variation_field( $variation_id, 'description', '' );
			},
			1,
			3
		);

		add_nab_filter(
			'woocommerce_variation_image_id',
			function ( $image_id, $product_id, $variation_id ) use ( &$alt_product ) {
				if ( $product_id !== $alt_product->get_control_id() ) {
					return $image_id;
				}//end if

				notify_alternative_loaded( $alt_product->get_experiment_id() );
				if ( $alt_product->should_use_control_value() ) {
					return $image_id;
				}//end if

				return $alt_product->get_variation_field( $variation_id, 'imageId', 0 );
			},
			1,
			3
		);

		add_nab_filter(
			'woocommerce_variation_regular_price',
			function ( $price, $product_id, $variation_id ) use ( &$alt_product ) {
				if ( $product_id !== $alt_product->get_control_id() ) {
					return $price;
				}//end if

				notify_alternative_loaded( $alt_product->get_experiment_id() );
				if ( $alt_product->should_use_control_value() ) {
					return $price;
				}//end if

				return $alt_product->get_variation_field( $variation_id, 'regularPrice', $price );
			},
			1,
			3
		);

		add_nab_filter(
			'woocommerce_variation_sale_price',
			function ( $price, $product_id, $regular_price, $variation_id ) use ( &$alt_product ) {
				if ( $product_id !== $alt_product->get_control_id() ) {
					return $price;
				}//end if

				notify_alternative_loaded( $alt_product->get_experiment_id() );
				if ( $alt_product->should_use_control_value() ) {
					return $price;
				}//end if

				return $alt_product->get_variation_field( $variation_id, 'salePrice', $regular_price );
			},
			1,
			4
		);

	}//end if
}//end load_alternative()
add_action( 'nab_nab/wc-product_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );

function add_hooks_to_switch_products( $alt_product ) {
	add_filter(
		'posts_results',
		function ( $posts ) use ( &$alt_product ) {
			if ( ! is_singular() || ! is_main_query() ) {
				return $posts;
			}//end if
			return array_map(
				function ( $post ) use ( &$alt_product ) {
					if ( $post->ID !== $alt_product->get_control_id() ) {
						return $post;
					}//end if
					$post              = get_post( $alt_product->get_id() );
					$post->post_status = 'publish';
					global $wp_query;
					$wp_query->queried_object    = $post;
					$wp_query->queried_object_id = $post->ID;
					return $post;
				},
				$posts
			);
		}
	);

	// Use control type instead of our own nab-alt-product.
	add_filter(
		'woocommerce_product_type_query',
		function ( $type, $product_id ) use ( &$alt_product ) {
			if ( $alt_product->get_id() !== $product_id ) {
				return $type;
			}//end if
			$control = $alt_product->get_control();
			return $control->get_type();
		},
		10,
		4
	);

	// Simulate product is publish.
	add_filter(
		'woocommerce_product_get_status',
		fn( $status, $product ) => $product->get_id() === $alt_product->get_id() ? 'publish' : $status,
		10,
		2
	);

	// Retrieve control children (e.g. variations in variable product).
	add_filter(
		'woocommerce_get_children',
		function ( $children, $product ) use ( $alt_product ) {
			if ( $product->get_id() !== $alt_product->get_id() ) {
				return $children;
			}//end if
			$control = $alt_product->get_control();
			return $control->get_children();
		},
		10,
		2
	);

	// Use control ID in single screen’s add to cart action.
	$previous_global_product         = null;
	$use_control_in_add_to_cart      = function () use ( &$alt_product, &$previous_global_product ) {
		global $product;
		if ( $product->get_id() !== $alt_product->get_id() ) {
			return;
		}//end if
		$previous_global_product = $product;
		$product                 = $alt_product->get_control();
	};
	$undo_use_control_in_add_to_cart = function () use ( &$previous_global_product ) {
		global $product;
		if ( null === $previous_global_product ) {
			return;
		}//end if
		$product                 = $previous_global_product;
		$previous_global_product = null;
	};
	foreach ( array_keys( wc_get_product_types() ) as $type ) {
		add_action( "woocommerce_{$type}_add_to_cart", $use_control_in_add_to_cart, 1 );
		add_action( "woocommerce_{$type}_add_to_cart", $undo_use_control_in_add_to_cart, 99 );
	}//end foreach

	// Add control ID in WooCommerce’s cart.
	add_action(
		'woocommerce_add_to_cart_product_id',
		fn( $id ) => $id === $alt_product->get_id() ? $alt_product->get_control_id() : $id
	);

	// Make sure we use control’s link.
	$fix_link = function ( $permalink, $post_id ) use ( &$fix_link, &$alt_product ) {
		if ( ! is_int( $post_id ) ) {
			if ( is_object( $post_id ) && isset( $post_id->ID ) ) {
				$post_id = $post_id->ID;
			} else {
				$post_id = nab_url_to_postid( $permalink );
			}//end if
		}//end if

		if ( $post_id !== $alt_product->get_id() ) {
			return $permalink;
		}//end if

		remove_filter( 'post_type_link', $fix_link, 10, 2 );
		$permalink = get_permalink( $alt_product->get_control_id() );
		add_filter( 'post_type_link', $fix_link, 10, 2 );
		return $permalink;
	};
	add_filter( 'post_type_link', $fix_link, 10, 2 );

	// Add additional info tab on products if needed.
	add_filter(
		'woocommerce_product_tabs',
		function ( $tabs ) use ( &$alt_product ) {
			if ( get_the_ID() !== $alt_product->get_id() ) {
				return $tabs;
			}//end if

			if ( isset( $tabs['additional_information'] ) ) {
				return $tabs;
			}//end if

			$control = $alt_product->get_control();
			if ( $control && ( $control->has_attributes() || apply_filters( 'wc_product_enable_dimensions_display', $control->has_weight() || $control->has_dimensions() ) ) ) {
				$tabs['additional_information'] = array(
					'title'    => _x( 'Additional information', 'text (woocommerce)', 'nelio-ab-testing' ),
					'priority' => 20,
					'callback' => 'woocommerce_product_additional_information_tab',
				);
			}//end if

			return $tabs;
		}
	);

	// Use control attributes (e.g. variation types in variable product).
	add_filter(
		'woocommerce_product_get_attributes',
		function ( $attributes, $product ) use ( $alt_product ) {
			if ( $product->get_id() !== $alt_product->get_id() ) {
				return $attributes;
			}//end if
			$control = $alt_product->get_control();
			return $control->get_attributes();
		},
		10,
		2
	);

	// Use appropriate values for attributes that are a taxonomy.
	add_filter(
		'woocommerce_get_product_terms',
		function ( $terms, $product_id, $taxonomy, $args ) use ( $alt_product ) {
			if ( 0 !== strpos( $taxonomy, 'pa_' ) ) {
				return $terms;
			}//end if
			if ( $alt_product->get_id() !== $product_id ) {
				return $terms;
			}//end if
			return wc_get_product_terms( $alt_product->get_control_id(), $taxonomy, $args );
		},
		10,
		4
	);

	use_control_reviews_in_alternative( $alt_product->get_control_id(), $alt_product->get_id() );
}//end add_hooks_to_switch_products()

/**
 * Returns the alternative product.
 *
 * @param array  $alternative   alternative attributes.
 * @param number $control_id    control product’s ID.
 * @param number $experiment_id experiment ID.
 *
 * @return \Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\IRunning_Alternative_Product The product.
 */
function get_alt_product( $alternative, $control_id, $experiment_id ) {
	$alt_post_id = nab_array_get( $alternative, 'postId', 0 );
	if ( $alt_post_id === $control_id ) {
		return new Running_Control_Product( $control_id, $experiment_id );
	}//end if

	if ( is_v1_alternative( $alternative ) ) {
		return new Running_Alternative_Product_V1( $alternative, $control_id, $experiment_id );
	}//end if

	if ( is_v2_alternative( $alternative ) ) {
		return new Running_Alternative_Product_V2( $alternative, $control_id, $experiment_id );
	}//end if

	return new Running_Alternative_Product( $alternative, $control_id, $experiment_id );
}//end get_alt_product()

function use_control_reviews_in_alternative( $control_id, $alternative_id ) {
	// Use control appropriate reviews.
	use_control_comments_in_alternative( $control_id, $alternative_id );

	// Show appropriate review count.
	add_filter(
		'woocommerce_product_get_review_count',
		function ( $count, $product ) use ( $control_id, $alternative_id ) {
			if ( $product->get_id() !== $alternative_id ) {
				return $count;
			}//end if
			$control = wc_get_product( $control_id );
			return $control->get_review_count();
		},
		10,
		2
	);

	// Show appropriate review count.
	add_filter(
		'woocommerce_product_get_rating_counts',
		function ( $count, $product ) use ( $control_id, $alternative_id ) {
			if ( $product->get_id() !== $alternative_id ) {
				return $count;
			}//end if
			$control = wc_get_product( $control_id );
			return $control->get_rating_counts();
		},
		10,
		2
	);

	// Show appropriate review average.
	add_filter(
		'woocommerce_product_get_average_rating',
		function ( $count, $product ) use ( $control_id, $alternative_id ) {
			if ( $product->get_id() !== $alternative_id ) {
				return $count;
			}//end if
			$control = wc_get_product( $control_id );
			return $control->get_average_rating();
		},
		10,
		2
	);
}//end use_control_reviews_in_alternative()
