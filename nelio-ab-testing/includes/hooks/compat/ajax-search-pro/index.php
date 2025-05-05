<?php

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_is_experiment_relevant_in_ajaxsearchpro_search_ajax_request', '__return_true' );
