<?php

namespace Nelio_AB_Testing\WooCommerce\Compat;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to add ecommerce settings in `nab/data`.
 *
 * @return void
 */
function get_ecommerce_settings() {
	$statuses = wc_get_order_statuses();
	$statuses = array_map(
		function ( $key, $value ) {
			return array(
				'value' => $key,
				'label' => $value,
			);
		},
		array_keys( $statuses ),
		array_values( $statuses )
	);

	/** @var string */
	$currency_pos = get_option( 'woocommerce_currency_pos', 'left' );

	$settings = array(
		'currency'           => html_entity_decode( get_woocommerce_currency(), ENT_COMPAT ),
		'currencyPosition'   => strpos( $currency_pos, 'right' ) !== false ? 'after' : 'before',
		'currencySymbol'     => html_entity_decode( get_woocommerce_currency_symbol(), ENT_COMPAT ),
		'decimalSeparator'   => get_option( 'woocommerce_price_decimal_sep', '.' ) ? get_option( 'woocommerce_price_decimal_sep', '.' ) : '.',
		'numberOfDecimals'   => absint( get_option( 'woocommerce_price_num_decimals', true ) ),
		'orderStatuses'      => $statuses,
		'thousandsSeparator' => get_option( 'woocommerce_price_thousand_sep', ',' ) ? get_option( 'woocommerce_price_thousand_sep', ',' ) : ',',
	);

	wp_add_inline_script(
		'nab-data',
		sprintf(
			'wp.data.dispatch( "nab/data" ).receiveECommerceSettings( "woocommerce", %s );',
			wp_json_encode( $settings )
		)
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\get_ecommerce_settings' );
