<?php
/**
 * This file defines some additional hooks to make Nelio A/B Testing compatible with third-party plugins and themes.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/library
 * @since      5.0.0
 */


defined( 'ABSPATH' ) || exit;

require_once dirname( __FILE__ ) . '/acf/index.php';
require_once dirname( __FILE__ ) . '/beaver/index.php';
require_once dirname( __FILE__ ) . '/cache/index.php';
require_once dirname( __FILE__ ) . '/contact-form-7/index.php';
require_once dirname( __FILE__ ) . '/custom-permalinks/index.php';
require_once dirname( __FILE__ ) . '/divi/index.php';
require_once dirname( __FILE__ ) . '/elementor/index.php';
require_once dirname( __FILE__ ) . '/fluent-forms/index.php';
require_once dirname( __FILE__ ) . '/formidable-forms/index.php';
require_once dirname( __FILE__ ) . '/forminator/index.php';
require_once dirname( __FILE__ ) . '/gp-premium/index.php';
require_once dirname( __FILE__ ) . '/gravity-forms/index.php';
require_once dirname( __FILE__ ) . '/hubspot/index.php';
require_once dirname( __FILE__ ) . '/instabuilder2/index.php';
require_once dirname( __FILE__ ) . '/leadpages/index.php';
require_once dirname( __FILE__ ) . '/nelio-forms/index.php';
require_once dirname( __FILE__ ) . '/ninja-forms/index.php';
require_once dirname( __FILE__ ) . '/optimizepress/index.php';
require_once dirname( __FILE__ ) . '/polylang/index.php';
require_once dirname( __FILE__ ) . '/the-events-calendar/index.php';
require_once dirname( __FILE__ ) . '/wpforms/index.php';
require_once dirname( __FILE__ ) . '/wpml/index.php';
require_once dirname( __FILE__ ) . '/yoast/index.php';
