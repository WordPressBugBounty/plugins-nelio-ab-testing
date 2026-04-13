<?php

namespace Nelio_AB_Testing\Zod;

class NumberSchema extends Schema {

	/** @var int|null */
	private $min;

	/** @var int|null */
	private $max;

	/**
	 * Creates a number schema.
	 *
	 * @return NumberSchema
	 */
	public static function make() {
		return new self();
	}

	/**
	 * Sets the min value to 1 (included).
	 *
	 * @return static
	 */
	public function positive() {
		return $this->min( 1 );
	}

	/**
	 * Sets the min value to 0 (included).
	 *
	 * @return static
	 */
	public function nonpositive() {
		return $this->max( 0 );
	}

	/**
	 * Sets the max value to -1 (included).
	 *
	 * @return static
	 */
	public function negative() {
		return $this->max( -1 );
	}

	/**
	 * Sets the max value to 0 (included).
	 *
	 * @return static
	 */
	public function nonnegative() {
		return $this->min( 0 );
	}

	/**
	 * Sets the min value.
	 *
	 * @param int $min Min value.
	 *
	 * @return static
	 */
	public function min( $min ) {
		$this->min = $min;
		return $this;
	}

	/**
	 * Sets the max value.
	 *
	 * @param int $max Max value.
	 *
	 * @return static
	 */
	public function max( $max ) {
		$this->max = $max;
		return $this;
	}

	public function parse_value( $value ) {
		if ( ! is_numeric( $value ) || is_string( $value ) ) {
			throw new \Exception(
				sprintf(
					'Expected a number, but %s found.',
					esc_html( gettype( $value ) )
				)
			);
		}

		if ( ! is_null( $this->min ) && $value < $this->min ) {
			throw new \Exception(
				sprintf(
					'Expected a number greater than or equal to %1$s, but %2$s found.',
					esc_html( $this->min ),
					esc_html( "$value" )
				)
			);
		}

		if ( ! is_null( $this->max ) && $this->max < $value ) {
			throw new \Exception(
				sprintf(
					'Expected a number less than or equal to %1$s, but %2$s found.',
					esc_html( $this->max ),
					esc_html( "$value" )
				)
			);
		}

		return $value;
	}
}
