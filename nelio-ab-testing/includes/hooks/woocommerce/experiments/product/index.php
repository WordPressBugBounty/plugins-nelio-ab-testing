<?php
/**
 * This file defines hooks to filters and actions for product experiments.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/hooks
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

add_action( 'nab_is_nab/wc-product_woocommerce_experiment', '__return_true' );

require_once dirname( __FILE__ ) . '/legacy.php';

require_once dirname( __FILE__ ) . '/attributes.php';
require_once dirname( __FILE__ ) . '/content.php';
require_once dirname( __FILE__ ) . '/edit.php';
require_once dirname( __FILE__ ) . '/load.php';
require_once dirname( __FILE__ ) . '/preview.php';
require_once dirname( __FILE__ ) . '/post-type.php';
require_once dirname( __FILE__ ) . '/tracking.php';

require_once dirname( __FILE__ ) . '/editor/index.php';
