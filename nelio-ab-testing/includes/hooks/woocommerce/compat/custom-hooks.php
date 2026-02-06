<?php
namespace Nelio_AB_Testing\WooCommerce\Compat;

defined( 'ABSPATH' ) || exit;

use function Nelio_AB_Testing\WooCommerce\Helpers\Product_Selection\is_variable_product;

/**
 * Callback to created product name hook.
 *
 * @param callable $callback Callback.
 * @param int      $priority Priority.
 * @param int      $args     Arguments.
 *
 * @return void
 */
function create_product_name_hook( $callback, $priority, $args ) {
	$replace_name = function ( $name, $item ) use ( &$callback, $args ) {
		$product_id = get_product_id( $item );
		if ( 'product' !== get_post_type( $product_id ) ) {
			return $name;
		}

		if ( ! are_custom_hooks_enabled( $product_id ) ) {
			return $name;
		}

		if ( exclude_from_testing( $product_id ) ) {
			return $name;
		}

		return run( $callback, array( $name, $product_id ), $args );
	};
	add_filter( 'the_title', $replace_name, $priority, 2 );
	// Source: includes/abstracts/abstract-wc-product.php.
	// Source: includes/class-wc-product-variation.php.
	add_filter( 'woocommerce_product_title', $replace_name, $priority, 2 );
	// Source: WC_Data » get_hook_prefix() . $prop.
	add_filter( 'woocommerce_product_get_name', $replace_name, $priority, 2 );
	// Source: WC_Data » get_hook_prefix() . $prop.
	add_filter( 'woocommerce_order_item_get_name', $replace_name, $priority, 2 );
}
add_action( 'nab_add_filter_for_woocommerce_product_name', __NAMESPACE__ . '\create_product_name_hook', 10, 3 );

/**
 * Callback to create product description hook.
 *
 * @param callable $callback Callback.
 * @param int      $priority Priority.
 * @param int      $args     Arguments.
 *
 * @return void
 */
function create_product_description_hook( $callback, $priority, $args ) {
	$replace_description = function ( $description ) use ( &$replace_description, &$callback, $priority, $args ) {
		/** @var string $description */

		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $description;
		}

		$post_id = get_the_ID();
		if ( false === $post_id ) {
			return $description;
		}

		if ( get_post_type( $post_id ) !== 'product' ) {
			return $description;
		}

		$product_id = $post_id;
		if ( ! are_custom_hooks_enabled( $product_id ) ) {
			return $description;
		}

		if ( exclude_from_testing( $product_id ) ) {
			return $description;
		}

		remove_filter( 'the_content', $replace_description, $priority );
		$result = run( $callback, array( $description, $product_id ), $args );
		add_filter( 'the_content', $replace_description, $priority );
		return $result;
	};
	add_filter( 'the_content', $replace_description, $priority );

	$replace_product_description = function ( $description, $product ) use ( &$callback, $args ) {
		$product_id = get_product_id( $product );
		return run( $callback, array( $description, $product_id ), $args );
	};
	add_filter( 'woocommerce_product_get_description', $replace_product_description, $priority, 2 );
}
add_action( 'nab_add_filter_for_woocommerce_product_description', __NAMESPACE__ . '\create_product_description_hook', 10, 3 );

/**
 * Callback to create product short description hook.
 *
 * @param callable $callback Callback.
 * @param int      $priority Priority.
 * @param int      $args     Arguments.
 *
 * @return void
 */
function create_product_short_description_hook( $callback, $priority, $args ) {
	$replace_short_description = function ( $short_description ) use ( &$replace_short_description, &$callback, $priority, $args ) {
		/** @var string $short_description */

		if ( doing_filter( 'woocommerce_archive_description' ) ) {
			return $short_description;
		}

		$post_id = get_the_ID();
		if ( false === $post_id ) {
			return $short_description;
		}

		if ( get_post_type( $post_id ) !== 'product' ) {
			return $short_description;
		}

		$product_id = $post_id;
		if ( ! are_custom_hooks_enabled( $product_id ) ) {
			return $short_description;
		}

		if ( exclude_from_testing( $product_id ) ) {
			return $short_description;
		}

		remove_filter( 'get_the_excerpt', $replace_short_description, $priority );
		remove_filter( 'woocommerce_short_description', $replace_short_description, $priority );
		$result = run( $callback, array( $short_description, $product_id ), $args );
		add_filter( 'get_the_excerpt', $replace_short_description, $priority );
		add_filter( 'woocommerce_short_description', $replace_short_description, $priority );
		return $result;
	};
	add_filter( 'get_the_excerpt', $replace_short_description, $priority );
	add_filter( 'woocommerce_short_description', $replace_short_description, $priority );

	$undo_replace_short_description = function ( $props, $item, $variation ) use ( &$replace_short_description, $priority ) {
		/** @var array{variation_description:string} $props     */
		/** @var mixed                               $item      */
		/** @var \WC_Product_Variation               $variation */

		$product_id = get_product_id( $item );
		if ( ! are_custom_hooks_enabled( $product_id ) ) {
			return $props;
		}

		remove_filter( 'woocommerce_short_description', $replace_short_description, $priority );
		$props['variation_description'] = wc_format_content( $variation->get_description() );
		add_filter( 'woocommerce_short_description', $replace_short_description, $priority );
		return $props;
	};
	add_filter( 'woocommerce_available_variation', $undo_replace_short_description, $priority, 3 );

	$replace_product_short_description = function ( $short_description, $product ) use ( &$callback, $args ) {
		/** @var string      $short_description */
		/** @var \WC_Product $product           */

		$product_id = get_product_id( $product );
		return run( $callback, array( $short_description, $product_id ), $args );
	};
	add_filter( 'woocommerce_product_get_short_description', $replace_product_short_description, $priority, 2 );
}
add_action( 'nab_add_filter_for_woocommerce_product_short_description', __NAMESPACE__ . '\create_product_short_description_hook', 10, 3 );

/**
 * Callback to pcreate product image ID hook.
 *
 * @param callable $callback Callback.
 * @param int      $priority Priority.
 * @param int      $args     Arguments.
 *
 * @return void
 */
function create_product_image_id_hook( $callback, $priority, $args ) {
	$replace_product_image_id = function ( $image_id, $product ) use ( &$callback, $args ) {
		/** @var int             $image_id */
		/** @var \WC_Product|int $product  */

		$product_id = is_int( $product ) ? $product : absint( $product->get_id() );
		if ( ! are_custom_hooks_enabled( $product_id ) ) {
			return $image_id;
		}

		if ( exclude_from_testing( $product ) ) {
			return $image_id;
		}

		return run( $callback, array( $image_id, $product_id ), $args );
	};
	// Source: WC_Data » get_hook_prefix() . $prop.
	add_filter( 'woocommerce_product_get_image_id', $replace_product_image_id, $priority, 2 );

	$replace_image_id_meta = function ( $value, $item_id, $meta_key ) use ( &$replace_product_image_id ) {
		return '_thumbnail_id' !== $meta_key ? $value : $replace_product_image_id( $value, $item_id );
	};
	add_filter( 'get_post_metadata', $replace_image_id_meta, $priority, 3 );
}
add_action( 'nab_add_filter_for_woocommerce_product_image_id', __NAMESPACE__ . '\create_product_image_id_hook', 10, 3 );

/**
 * Callback to create product gallery hook.
 *
 * @param callable $callback Callback.
 * @param int      $priority Priority.
 * @param int      $args     Arguments.
 *
 * @return void
 */
function create_product_gallery_hook( $callback, $priority, $args ) {
	$replace_gallery_image_ids = function ( $image_ids, $product ) use ( &$callback, $args ) {
		/** @var list<int>       $image_ids */
		/** @var \WC_Product|int $product   */

		$product_id = is_int( $product ) ? $product : absint( $product->get_id() );
		if ( ! are_custom_hooks_enabled( $product_id ) ) {
			return $image_ids;
		}

		if ( exclude_from_testing( $product ) ) {
			return $image_ids;
		}

		return run( $callback, array( $image_ids, $product_id ), $args );
	};
	// Source: WC_Data » get_hook_prefix() . $prop.
	add_filter( 'woocommerce_product_get_gallery_image_ids', $replace_gallery_image_ids, $priority, 2 );
}
add_action( 'nab_add_filter_for_woocommerce_product_gallery_ids', __NAMESPACE__ . '\create_product_gallery_hook', 10, 3 );

/**
 * Callback to create product regular price hook.
 *
 * @param callable $callback Callback.
 * @param int      $priority Priority.
 * @param int      $args     Arguments.
 *
 * @return void
 */
function create_product_regular_price_hook( $callback, $priority, $args ) {
	$replace_regular_price = function ( $regular_price, $product ) use ( &$callback, $args ) {
		/** @var string      $regular_price */
		/** @var \WC_Product $product       */

		if ( is_variable_product( $product ) ) {
			return $regular_price;
		}

		$product_id = absint( $product->get_id() );
		if ( ! are_custom_hooks_enabled( $product_id ) ) {
			return $regular_price;
		}

		if ( ! is_price_testing_enabled( $product_id ) ) {
			return $regular_price;
		}

		if ( exclude_from_testing( $product ) ) {
			return $regular_price;
		}

		return run( $callback, array( $regular_price, $product_id ), $args );
	};
	// Source: WC_Data » get_hook_prefix() . $prop.
	add_filter( 'woocommerce_product_get_price', $replace_regular_price, $priority, 2 );
	add_filter( 'woocommerce_product_get_regular_price', $replace_regular_price, $priority, 2 );
}
add_action( 'nab_add_filter_for_woocommerce_product_regular_price', __NAMESPACE__ . '\create_product_regular_price_hook', 10, 3 );

/**
 * Callback to create product sale price hook.
 *
 * @param callable $callback Callback.
 * @param int      $priority Priority.
 * @param int      $args     Arguments.
 *
 * @return void
 */
function create_product_sale_price_hook( $callback, $priority, $args ) {
	$replace_sale_price = function ( $sale_price, $product ) use ( &$callback, $args ) {
		/** @var string      $sale_price */
		/** @var \WC_Product $product    */

		if ( is_variable_product( $product ) ) {
			return $sale_price;
		}

		$product_id = absint( $product->get_id() );
		if ( ! are_custom_hooks_enabled( $product_id ) ) {
			return $sale_price;
		}

		if ( ! is_price_testing_enabled( $product_id ) ) {
			return $sale_price;
		}

		if ( exclude_from_testing( $product ) ) {
			return $sale_price;
		}

		$regular_price = $product->get_regular_price();

		return run( $callback, array( $sale_price, $product_id, $regular_price ), $args );
	};
	// Source: WC_Data » get_hook_prefix() . $prop.
	add_filter( 'woocommerce_product_get_price', $replace_sale_price, $priority, 2 );
	add_filter( 'woocommerce_product_get_sale_price', $replace_sale_price, $priority, 2 );
}
add_action( 'nab_add_filter_for_woocommerce_product_sale_price', __NAMESPACE__ . '\create_product_sale_price_hook', 10, 3 );

/**
 * Callback to fix product on sale.
 *
 * @param bool        $is_on_sale Is on sale.
 * @param \WC_Product $product    Product.
 *
 * @return bool
 */
function fix_product_on_sale( $is_on_sale, $product ) {

	if ( is_variable_product( $product ) ) {
		return $is_on_sale;
	}

	$product_id = absint( $product->get_id() );
	if ( ! are_custom_hooks_enabled( $product_id ) ) {
		return $is_on_sale;
	}

	if ( ! is_price_testing_enabled( $product_id ) ) {
		return $is_on_sale;
	}

	if ( exclude_from_testing( $product ) ) {
		return $is_on_sale;
	}

	$current_price = $product->get_price();
	$regular_price = $product->get_regular_price();

	return $current_price < $regular_price;
}
add_filter( 'woocommerce_product_is_on_sale', __NAMESPACE__ . '\fix_product_on_sale', 1, 2 );

/**
 * Callback to fix variable product price.
 *
 * @param array{price:array<int,string>,regular_price:array<int,string>,sale_price:array<int,string>} $prices  Prices.
 * @param \WC_Product_Variable                                                                        $product Product.
 *
 * @return array{price:array<int,string>,regular_price:array<int,string>,sale_price:array<int,string>}
 */
function fix_variable_product_price( $prices, $product ) {
	$product_id = absint( $product->get_id() );
	if ( ! are_custom_hooks_enabled( $product_id ) ) {
		return $prices;
	}

	if ( ! is_price_testing_enabled( $product_id ) ) {
		return $prices;
	}

	if ( exclude_from_testing( $product ) ) {
		return $prices;
	}

	remove_filter( 'woocommerce_variation_prices', __NAMESPACE__ . '\fix_variable_product_price', 10 );

	/** @var list<array{variation_id:int}> */
	$variations    = $product->get_available_variations();
	$variations    = array_map( fn( $v ) => wc_get_product( $v['variation_id'] ), $variations );
	$variations    = array_values( array_filter( $variations ) );
	$variation_ids = array_map( fn( $v ) => $v->get_id(), $variations );

	$prices = array_map( fn( $v ) => $v->get_price(), $variations );
	$prices = array_combine( $variation_ids, $prices );
	asort( $prices, SORT_NUMERIC );

	$regular_prices = array_map( fn( $v ) => $v->get_regular_price(), $variations );
	$regular_prices = array_combine( $variation_ids, $regular_prices );
	asort( $regular_prices, SORT_NUMERIC );

	$sale_prices = array_map( fn( $v ) => $v->get_sale_price(), $variations );
	$sale_prices = array_combine( $variation_ids, $sale_prices );
	asort( $sale_prices, SORT_NUMERIC );

	add_filter( 'woocommerce_variation_prices', __NAMESPACE__ . '\fix_variable_product_price', 10, 2 );

	return array(
		'price'         => $prices,
		'regular_price' => $regular_prices,
		'sale_price'    => $sale_prices,
	);
}
add_filter( 'woocommerce_variation_prices', __NAMESPACE__ . '\fix_variable_product_price', 10, 2 );

/**
 * Callback to fix variable product on sale.
 *
 * @param bool                 $is_on_sale Is on sale.
 * @param \WC_Product_Variable $product    Product.
 *
 * @return bool
 */
function fix_variable_product_on_sale( $is_on_sale, $product ) {
	if ( ! is_variable_product( $product ) ) {
		return $is_on_sale;
	}

	$product_id = absint( $product->get_id() );
	if ( ! are_custom_hooks_enabled( $product_id ) ) {
		return $is_on_sale;
	}

	if ( ! is_price_testing_enabled( $product_id ) ) {
		return $is_on_sale;
	}

	if ( exclude_from_testing( $product ) ) {
		return $is_on_sale;
	}

	/** @var list<\WC_Product_Variation> $variations */
	$variations = $product->get_available_variations( 'objects' );
	return nab_some( fn( $v ) => $v->get_sale_price() < $v->get_regular_price(), $variations );
}
add_filter( 'woocommerce_product_is_on_sale', __NAMESPACE__ . '\fix_variable_product_on_sale', 1, 2 );

/**
 * Callback to create variation description hook.
 *
 * @param callable $callback Callback.
 * @param int      $priority Priority.
 * @param int      $args     Arguments.
 *
 * @return void
 */
function create_variation_description_hook( $callback, $priority, $args ) {
	$replace_description = function ( $description, $variation ) use ( &$callback, $args ) {
		/** @var string                $description */
		/** @var \WC_Product_Variation $variation   */

		$product_id   = $variation->get_parent_id();
		$variation_id = absint( $variation->get_id() );
		if ( ! are_custom_hooks_enabled( $product_id ) ) {
			return $description;
		}

		if ( exclude_from_testing( $product_id ) ) {
			return $description;
		}

		return run( $callback, array( $description, $product_id, $variation_id ), $args );
	};
	// Source: WC_Data » get_hook_prefix() . $prop.
	add_filter( 'woocommerce_product_variation_get_description', $replace_description, $priority, 2 );
}
add_action( 'nab_add_filter_for_woocommerce_variation_description', __NAMESPACE__ . '\create_variation_description_hook', 10, 3 );

/**
 * Callback to create variation image ID hook.
 *
 * @param callable $callback Callback.
 * @param int      $priority Priority.
 * @param int      $args     Arguments.
 *
 * @return void
 */
function create_variation_image_id_hook( $callback, $priority, $args ) {
	$replace_image_id = function ( $image_id, $variation ) use ( &$callback, $args ) {
		/** @var int                   $image_id  */
		/** @var \WC_Product_Variation $variation */

		$product_id   = $variation->get_parent_id();
		$variation_id = absint( $variation->get_id() );
		if ( ! are_custom_hooks_enabled( $product_id ) ) {
			return $image_id;
		}

		if ( exclude_from_testing( $product_id ) ) {
			return $image_id;
		}

		return run( $callback, array( $image_id, $product_id, $variation_id ), $args );
	};
	// Source: WC_Data » get_hook_prefix() . $prop.
	add_filter( 'woocommerce_product_variation_get_image_id', $replace_image_id, $priority, 2 );
}
add_action( 'nab_add_filter_for_woocommerce_variation_image_id', __NAMESPACE__ . '\create_variation_image_id_hook', 10, 3 );

/**
 * Callback to create variation regular price hook.
 *
 * @param callable $callback Callback.
 * @param int      $priority Priority.
 * @param int      $args     Arguments.
 *
 * @return void
 */
function create_variation_regular_price_hook( $callback, $priority, $args ) {
	$replace_regular_price = function ( $regular_price, $variation ) use ( &$callback, $args ) {
		/** @var string                $regular_price */
		/** @var \WC_Product_Variation $variation     */

		$product_id   = $variation->get_parent_id();
		$variation_id = absint( $variation->get_id() );
		if ( ! are_custom_hooks_enabled( $product_id ) ) {
			return $regular_price;
		}

		if ( ! is_price_testing_enabled( $product_id ) ) {
			return $regular_price;
		}

		if ( exclude_from_testing( $variation ) ) {
			return $regular_price;
		}

		return run( $callback, array( $regular_price, $product_id, $variation_id ), $args );
	};
	add_filter( 'woocommerce_product_variation_get_price', $replace_regular_price, $priority, 2 );
	add_filter( 'woocommerce_product_variation_get_regular_price', $replace_regular_price, $priority, 2 );
	add_filter( 'woocommerce_variation_prices_regular_price', $replace_regular_price, $priority, 2 );
	add_filter( 'woocommerce_variation_prices_price', $replace_regular_price, $priority, 2 );
}
add_action( 'nab_add_filter_for_woocommerce_variation_regular_price', __NAMESPACE__ . '\create_variation_regular_price_hook', 10, 3 );

/**
 * Callback to create variation sale price hook.
 *
 * @param callable $callback Callback.
 * @param int      $priority Priority.
 * @param int      $args     Arguments.
 *
 * @return void
 */
function create_variation_sale_price_hook( $callback, $priority, $args ) {
	$replace_sale_price = function ( $sale_price, $variation ) use ( &$callback, $args ) {
		/** @var string                $sale_price */
		/** @var \WC_Product_Variation $variation  */

		$product_id   = $variation->get_parent_id();
		$variation_id = absint( $variation->get_id() );
		if ( ! are_custom_hooks_enabled( $product_id ) ) {
			return $sale_price;
		}

		if ( ! is_price_testing_enabled( $product_id ) ) {
			return $sale_price;
		}

		if ( exclude_from_testing( $variation ) ) {
			return $sale_price;
		}

		$regular_price = $variation->get_regular_price();
		return run( $callback, array( $sale_price, $product_id, $regular_price, $variation_id ), $args );
	};
	add_filter( 'woocommerce_product_variation_get_price', $replace_sale_price, $priority, 2 );
	add_filter( 'woocommerce_product_variation_get_sale_price', $replace_sale_price, $priority, 2 );
	add_filter( 'woocommerce_variation_prices_sale_price', $replace_sale_price, $priority, 2 );
	add_filter( 'woocommerce_variation_prices_price', $replace_sale_price, $priority, 2 );
}
add_action( 'nab_add_filter_for_woocommerce_variation_sale_price', __NAMESPACE__ . '\create_variation_sale_price_hook', 10, 3 );

// ========
// INTERNAL
// ========

/**
 * Get product id.
 *
 * @param mixed $item The object.
 *
 * @return int
 */
function get_product_id( $item ) {
	if ( is_int( $item ) ) {
		return absint( $item );
	}

	if ( is_object( $item ) && method_exists( $item, 'get_id' ) ) {
		return absint( $item->get_id() );
	}

	if ( is_object( $item ) && method_exists( $item, 'get_product_id' ) ) {
		return absint( $item->get_product_id() );
	}

	return 0;
}

/**
 * Whether custom hooks are enabled for the given product ID or not.
 *
 * @param int $product_id Product ID.
 *
 * @return bool
 */
function are_custom_hooks_enabled( $product_id ) {
	/**
	 * Enables (or disables) custom WooCommerce filters for a given WooCommerce product.
	 *
	 * Notice: if you want to enable custom hooks for product
	 * variations, you’ll need to enable them for their parent
	 * variable product.
	 *
	 * @param bool $is_enabled Whether custom WooCommerce filters are enabled or not. Default: `false`.
	 * @param int  $product_id WooCommerce product ID.
	 *
	 * @since 5.4.3
	 */
	return apply_filters( 'nab_enable_custom_woocommerce_hooks', false, $product_id );
}

/**
 * Whether price testing is enabled for the given product or not.
 *
 * @param int $product_id Product ID.
 *
 * @return bool
 */
function is_price_testing_enabled( $product_id ) {
	/**
	 * Enables (or disables) testing of WooCommerce product prices.
	 *
	 * @param bool $is_enabled Whether price testing is enabled or not.
	 * @param int  $product_id WooCommerce product ID.
	 *
	 * @since 6.6.0
	 */
	return apply_filters( 'nab_woocommerce_is_price_testing_enabled', true, $product_id );
}

/**
 * Whether the product is excluded from testing or not.
 *
 * @param \WC_Product|int $product Product.
 *
 * @return bool
 */
function exclude_from_testing( $product ) {
	/**
	 * Filters whether a certain tested product should indeed be tested or not.
	 *
	 * @param bool            $skip      Whether a certain tested product should indeed be tested or not. Default: `false` (meaning, it will indeed be tested)
	 * @param \WC_Product|int $product   WooCommerce product/variation or WooCommerce product ID.
	 *
	 * @since 5.5.7
	 */
	return apply_filters( 'nab_exclude_woocommerce_product_from_testing', false, $product );
}

/**
 * Helper function that runs the given callback with the provided args.
 *
 * @param callable     $callback Callback.
 * @param array<mixed> $args Args.
 * @param int          $arg_count Argument count.
 *
 * @return mixed
 */
function run( $callback, $args, $arg_count ) {
	if ( 0 === $arg_count ) {
		return call_user_func( $callback );
	}

	return $arg_count >= count( $args )
		? call_user_func_array( $callback, $args )
		: call_user_func_array( $callback, array_slice( $args, 0, $arg_count ) );
}
