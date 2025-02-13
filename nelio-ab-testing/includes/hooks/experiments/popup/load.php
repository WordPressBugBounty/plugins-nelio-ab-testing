<?php

namespace Nelio_AB_Testing\Experiment_Library\Popup_Experiment;

defined( 'ABSPATH' ) || exit;

// Each popup plugin is responsible of defining when and how to load alternative content.
add_filter( 'nab_is_nab/popup_relevant_in_url', '__return_false', 1 );
