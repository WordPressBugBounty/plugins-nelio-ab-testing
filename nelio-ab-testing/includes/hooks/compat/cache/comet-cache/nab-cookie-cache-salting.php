<?php

if ( ! empty( $GLOBALS['zencache__advanced_cache'] ) ) {
	$GLOBALS['zencache__advanced_cache']->add_filter(
		get_class( $GLOBALS['zencache__advanced_cache'] ) . '__version_salt',
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
}//end if
