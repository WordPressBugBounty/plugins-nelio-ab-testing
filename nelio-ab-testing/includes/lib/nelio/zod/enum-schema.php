<?php

namespace Nelio_AB_Testing\Zod;

class EnumSchema extends Schema {

	/** @var list<string>|list<int> */
	private array $values;

	/**
	 * Creates an enum schema.
	 *
	 * @param non-empty-list<string>|non-empty-list<int> $values Values.
	 *
	 * @return EnumSchema
	 */
	public static function make( $values ) {
		$instance         = new self();
		$instance->values = $values;
		return $instance;
	}

	public function parse_value( $value ) {
		if ( ! is_int( $value ) && is_int( $this->values[0] ) ) {
			throw new \Exception(
				sprintf(
					'Expected int, but %s found.',
					esc_html( gettype( $value ) )
				)
			);
		}

		if ( ! is_string( $value ) && is_string( $this->values[0] ) ) {
			throw new \Exception(
				sprintf(
					'Expected string, but %s found.',
					esc_html( gettype( $value ) )
				)
			);
		}

		if ( ! in_array( $value, $this->values, true ) ) {
			throw new \Exception(
				sprintf(
					'Expected one of %1$s, but %2$s found.',
					sprintf( '(%s)', esc_html( implode( ', ', $this->values ) ) ),
					esc_html( is_string( $value ) || is_int( $value ) ? "$value" : gettype( $value ) )
				)
			);
		}

		return $value;
	}
}
