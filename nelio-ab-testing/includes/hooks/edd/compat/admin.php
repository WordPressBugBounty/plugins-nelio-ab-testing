<?php

namespace Nelio_AB_Testing\EasyDigitalDownloads\Compat;

function get_ecommerce_settings() {
	$statuses = edd_get_payment_statuses();
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
		'currency'           => html_entity_decode( edd_get_currency_name(), ENT_COMPAT ),
		'currencyPosition'   => edd_get_option( 'currency_position', 'before' ),
		'currencySymbol'     => html_entity_decode( edd_currency_symbol(), ENT_COMPAT ),
		'decimalSeparator'   => edd_get_option( 'decimal_separator', '.' ),
		'numberOfDecimals'   => absint( get_option( 'woocommerce_price_num_decimals', true ) ),
		'orderStatuses'      => $statuses,
		'thousandsSeparator' => edd_get_option( 'thousands_separator', ',' ),
	);

	wp_add_inline_script(
		'nab-data',
		sprintf(
			'wp.data.dispatch( "nab/data" ).receiveECommerceSettings( "edd", %s );',
			wp_json_encode( $settings )
		)
	);
}//end get_ecommerce_settings()
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\get_ecommerce_settings' );
