<?php
/**
 * This file defines hooks to filters and actions for product experiments.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/hooks
 * @since      5.0.0
 */

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Bulk_Sale_Experiment;

defined( 'ABSPATH' ) || exit;

add_action( 'nab_is_nab/wc-bulk-sale_woocommerce_experiment', '__return_true' );

require_once __DIR__ . '/attributes.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/load.php';
require_once __DIR__ . '/preview.php';
require_once __DIR__ . '/tracking.php';
