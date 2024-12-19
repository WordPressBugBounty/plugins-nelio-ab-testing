<?php

namespace Nelio_AB_Testing\Compat\Optimize_Press;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function class_exists;

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'OPBuilder\Support\Tools' ) ) {
			return;
		}//end if
		add_action( 'nab_nab/page_preview_alternative', __NAMESPACE__ . '\prevent_cache_in_wrong_meta', 99, 2 );
		add_action( 'nab_nab/post_preview_alternative', __NAMESPACE__ . '\prevent_cache_in_wrong_meta', 99, 2 );
		add_action( 'nab_nab/custom-post-type_preview_alternative', __NAMESPACE__ . '\prevent_cache_in_wrong_meta', 99, 2 );
	}
);
