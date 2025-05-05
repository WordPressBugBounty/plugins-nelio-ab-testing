<?php

namespace Nelio_AB_Testing\WooCommerce\Compat;

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

	$settings = array(
		'currency'           => html_entity_decode( get_woocommerce_currency(), ENT_COMPAT ),
		'currencyPosition'   => strpos( get_option( 'woocommerce_currency_pos', 'left' ), 'right' ) !== false ? 'after' : 'before',
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
}//end get_ecommerce_settings()
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\get_ecommerce_settings' );
