<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

function duplicate_alternative( $_, $source ) {
	return $source;
}//end duplicate_alternative()
add_filter( 'nab_nab/css_duplicate_alternative_content', __NAMESPACE__ . '\duplicate_alternative', 10, 2 );
