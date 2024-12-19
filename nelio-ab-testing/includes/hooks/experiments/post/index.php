<?php
/**
 * This file defines hooks to filters and actions for alternative experiments
 * that test a WordPress post (or page or CPT).
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/library
 * @since      5.0.0
 */

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/attributes.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/edit.php';
require_once __DIR__ . '/load.php';
require_once __DIR__ . '/preview.php';
require_once __DIR__ . '/tracking.php';
