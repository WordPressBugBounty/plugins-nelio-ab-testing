<?php

namespace Nelio_AB_Testing\Compat\UAGB;

defined( 'ABSPATH' ) || exit;

use function add_action;

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'UAGB_FILE' ) ) {
			return;
		}
		add_action( 'nab_nab/page_preview_alternative', __NAMESPACE__ . '\load_styles', 99, 2 );
		add_action( 'nab_nab/post_preview_alternative', __NAMESPACE__ . '\load_styles', 99, 2 );
		add_action( 'nab_nab/custom-post-type_preview_alternative', __NAMESPACE__ . '\load_styles', 99, 2 );
	}
);
