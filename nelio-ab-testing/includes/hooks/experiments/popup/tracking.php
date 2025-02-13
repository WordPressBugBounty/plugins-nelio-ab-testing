<?php
namespace Nelio_AB_Testing\Experiment_Library\Popup_Experiment;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_nab/popup_get_page_view_tracking_location', fn() => 'script' );
