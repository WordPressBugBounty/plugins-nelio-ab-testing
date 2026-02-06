<?php

add_cacheaction(
	'wp_cache_key',
	function () {
		/** @var string|false */
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$alternative = isset( $_COOKIE['nabAlternative'] ) ? $_COOKIE['nabAlternative'] : false;
		if ( false === $alternative ) {
			return 'NAB-RE';
		}
		return 'none' === $alternative
			? 'NAB-NO'
			: sprintf( 'NAB-%02d', abs( (int) $alternative ) );
	}
);
