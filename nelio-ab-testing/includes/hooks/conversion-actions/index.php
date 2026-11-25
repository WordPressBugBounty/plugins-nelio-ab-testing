<?php
/**
 * Loads conversion-action hooks.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/scope-sanitize.php';

require_once __DIR__ . '/custom-event/index.php';
require_once __DIR__ . '/form-submission/index.php';
require_once __DIR__ . '/page-view/index.php';
require_once __DIR__ . '/click/index.php';
require_once __DIR__ . '/click-external-link/index.php';
require_once __DIR__ . '/element-view/index.php';
require_once __DIR__ . '/engagement/index.php';
require_once __DIR__ . '/scroll/index.php';
require_once __DIR__ . '/time-on-page/index.php';
require_once __DIR__ . '/video-playback/index.php';
