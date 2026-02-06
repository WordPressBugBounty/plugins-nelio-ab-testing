<?php

/** @var array{zencache__advanced_cache?:\WebSharks\CometCache\Classes\AdvancedCache} $GLOBALS */
if ( ! empty( $GLOBALS['zencache__advanced_cache'] ) ) {
	$GLOBALS['zencache__advanced_cache']->addFilter(
		get_class( $GLOBALS['zencache__advanced_cache'] ) . '__version_salt',
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
}
