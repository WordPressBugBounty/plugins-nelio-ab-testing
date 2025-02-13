<?php

namespace Nelio_AB_Testing\Experiment_Library\JavaScript_Experiment;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_nab/javascript_get_page_view_tracking_location', fn() => 'script' );
