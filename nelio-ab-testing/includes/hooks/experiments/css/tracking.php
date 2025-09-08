<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_nab/css_get_page_view_tracking_location', fn() => 'script' );
