<?php
/**
 * This file defines some additional hooks to make Nelio A/B Testing compatible with third-party plugins and themes.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/library
 * @since      5.0.0
 */


defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/acf/index.php';
require_once __DIR__ . '/ajax-search-pro/index.php';
require_once __DIR__ . '/beaver/index.php';
require_once __DIR__ . '/cache/index.php';
require_once __DIR__ . '/custom-permalinks/index.php';
require_once __DIR__ . '/divi/index.php';
require_once __DIR__ . '/elementor/index.php';
require_once __DIR__ . '/external-page-script/index.php';
require_once __DIR__ . '/forms/index.php';
require_once __DIR__ . '/google-analytics-4/index.php';
require_once __DIR__ . '/gp-premium/index.php';
require_once __DIR__ . '/instabuilder2/index.php';
require_once __DIR__ . '/leadpages/index.php';
require_once __DIR__ . '/nelio-popups/index.php';
require_once __DIR__ . '/optimizepress/index.php';
require_once __DIR__ . '/permalink-manager/index.php';
require_once __DIR__ . '/polylang/index.php';
require_once __DIR__ . '/the-events-calendar/index.php';
require_once __DIR__ . '/ultimate-addons-for-gutenberg/index.php';
require_once __DIR__ . '/user-role-editor/index.php';
require_once __DIR__ . '/weglot/index.php';
require_once __DIR__ . '/wpml/index.php';
require_once __DIR__ . '/yoast/index.php';
