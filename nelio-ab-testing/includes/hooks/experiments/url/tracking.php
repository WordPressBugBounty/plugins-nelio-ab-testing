<?php

namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

add_filter( 'nab_nab/url_supports_heatmaps', '__return_true' );
