<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with Divi.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/library
 * @since      5.0.4
 */

namespace Nelio_AB_Testing\Compat\Divi;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/load.php';
require_once __DIR__ . '/preview.php';
require_once __DIR__ . '/wordpress.php';
