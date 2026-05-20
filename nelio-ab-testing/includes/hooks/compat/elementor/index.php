<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with Elementor.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/library
 * @since      5.0.4
 */

namespace Nelio_AB_Testing\Compat\Elementor;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/forms.php';
require_once __DIR__ . '/popups/index.php';
require_once __DIR__ . '/posts/index.php';
require_once __DIR__ . '/templates/index.php';
