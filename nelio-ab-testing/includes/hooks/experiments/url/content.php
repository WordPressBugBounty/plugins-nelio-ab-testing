<?php

namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

// Heatmap link is essentially the preview link which will need some extra params to load the heatmap renderer on top of it.
add_filter( 'nab_nab/url_heatmap_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 2 );
