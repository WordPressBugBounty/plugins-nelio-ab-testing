<?php

add_cacheaction(
	'wp_cache_key',
	function () {
		// phpcs:ignore
		$alternative = isset( $_COOKIE['nabAlternative'] ) ? $_COOKIE['nabAlternative'] : false;
		if ( false === $alternative ) {
			return 'NAB-RE';
		}//end if
		return 'none' === $alternative
			? 'NAB-NO'
			: sprintf( 'NAB-%02d', abs( (int) $alternative ) );
	}
);
