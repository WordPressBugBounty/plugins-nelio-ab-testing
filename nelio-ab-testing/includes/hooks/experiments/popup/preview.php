<?php

namespace Nelio_AB_Testing\Experiment_Library\Popup_Experiment;

defined( 'ABSPATH' ) || exit;

// Each popup plugin is responsible of defining its preview link.
add_filter( 'nab_nab/popup_preview_link_alternative', '__return_false', 1 );
