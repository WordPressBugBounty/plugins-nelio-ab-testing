<?php

namespace Nelio_AB_Testing\Compat\Beaver;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function class_exists;

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'FLBuilderModel' ) ) {
			return;
		}//end if
		add_action( 'nab_nab/page_preview_alternative', __NAMESPACE__ . '\use_alternative_id_during_beaver_render', 10, 2 );
		add_action( 'nab_nab/post_preview_alternative', __NAMESPACE__ . '\use_alternative_id_during_beaver_render', 10, 2 );
		add_action( 'nab_nab/custom-post-type_preview_alternative', __NAMESPACE__ . '\use_alternative_id_during_beaver_render', 10, 2 );
	}
);
