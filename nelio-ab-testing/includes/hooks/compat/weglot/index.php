<?php

namespace Nelio_AB_Testing\Compat\WeGlot;

defined( 'ABSPATH' ) || exit;

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'WEGLOT_NAME' ) ) {
			return;
		}//end if
		add_filter( 'nab_current_url', fn() => weglot_get_current_full_url() );
	}
);
