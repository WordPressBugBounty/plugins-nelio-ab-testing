<?php

namespace Nelio_AB_Testing\Zod;

class BooleanSchema extends Schema {

	/**
	 * Creates a boolean schema.
	 *
	 * @return BooleanSchema
	 */
	public static function make() {
		return new self();
	}

	public function parse_value( $value ) {
		if ( ! in_array( $value, array( true, false ), true ) ) {
			throw new \Exception( 'Expected boolean value.' );
		}

		return true === $value;
	}
}
