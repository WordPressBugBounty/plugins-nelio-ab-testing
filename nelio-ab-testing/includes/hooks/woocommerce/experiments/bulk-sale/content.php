<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Bulk_Sale_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to backup control.
 *
 * @return TWC_Bulk_Sale_Alternative_Attributes
 */
function backup_control() {
	return array(
		'name'                        => '',
		'discount'                    => 0,
		'overwritesExistingSalePrice' => false,
	);
}
add_filter( 'nab_nab/wc-bulk-sale_backup_control', __NAMESPACE__ . '\backup_control' );
