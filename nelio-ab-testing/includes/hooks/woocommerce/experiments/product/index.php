<?php
/**
 * This file defines hooks to filters and actions for product experiments.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/hooks
 * @since      7.3.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'nab_is_nab/wc-product_woocommerce_experiment', '__return_true' );

require_once __DIR__ . '/interface-irunning-alternative-product.php';
require_once __DIR__ . '/class-running-alternative-product.php';
require_once __DIR__ . '/class-running-control-product.php';

require_once __DIR__ . '/post-type.php';
require_once __DIR__ . '/legacy/index.php';

require_once __DIR__ . '/attributes.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/edit.php';
require_once __DIR__ . '/load.php';
require_once __DIR__ . '/preview.php';
require_once __DIR__ . '/tracking.php';

require_once __DIR__ . '/editor/index.php';
