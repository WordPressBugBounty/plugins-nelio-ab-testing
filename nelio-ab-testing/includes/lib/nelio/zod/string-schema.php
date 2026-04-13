<?php

namespace Nelio_AB_Testing\Zod;

class StringSchema extends Schema {

	/** @var int|null */
	private $min;

	/** @var int|null */
	private $max;

	/** @var string|null */
	private $regex;

	/** @var bool */
	private $should_trim = false;

	/**
	 * Creates a string schema.
	 *
	 * @return StringSchema
	 */
	public static function make() {
		return new self();
	}

	/**
	 * Sets the min length.
	 *
	 * @param int $min Min length.
	 *
	 * @return static
	 */
	public function min( $min ) {
		$this->min = $min;
		return $this;
	}

	/**
	 * Sets the max length.
	 *
	 * @param int $max Max length.
	 *
	 * @return static
	 */
	public function max( $max ) {
		$this->max = $max;
		return $this;
	}

	/**
	 * Sets the length.
	 *
	 * @param int $length Length.
	 *
	 * @return static
	 */
	public function length( $length ) {
		return $this->min( $length )->max( $length );
	}

	/**
	 * Sets the regex.
	 *
	 * @param string $regex Regular expression.
	 *
	 * @return static
	 */
	public function regex( $regex ) {
		$this->regex = $regex;
		return $this;
	}

	/**
	 * Sets the schema to trim string after successful parsing.
	 *
	 * @return static
	 */
	public function trim() {
		$this->should_trim = true;
		return $this;
	}

	public function parse_value( $value ) {
		if ( ! is_string( $value ) ) {
			throw new \Exception(
				sprintf(
					'Expected a string, but %s found.',
					esc_html( gettype( $value ) )
				)
			);
		}

		if ( $this->should_trim ) {
			$value = trim( $value );
		}

		if (
			! is_null( $this->min ) &&
			$this->min === $this->max &&
			mb_strlen( $value ) !== $this->min
		) {
			throw new \Exception(
				sprintf(
					'Expected a string with length %1$s, but string is %2$s characters long.',
					esc_html( $this->min ),
					esc_html( (string) mb_strlen( $value ) )
				)
			);
		}

		if ( ! is_null( $this->min ) && mb_strlen( $value ) < $this->min ) {
			throw new \Exception(
				sprintf(
					'Expected a string with length greater than or equal to %1$s, but string is %2$s characters long.',
					esc_html( "$this->min" ),
					esc_html( (string) mb_strlen( $value ) )
				)
			);
		}

		if ( ! is_null( $this->max ) && $this->max < mb_strlen( $value ) ) {
			throw new \Exception(
				sprintf(
					'Expected a string with length less than or equal to %1$s, but string is %2$s characters long.',
					esc_html( "$this->max" ),
					esc_html( (string) mb_strlen( $value ) )
				)
			);
		}

		if ( ! is_null( $this->regex ) && 1 !== preg_match( $this->regex, $value ) ) {
			throw new \Exception(
				sprintf(
					'String doesn\'t match regex "%s".',
					esc_html( $this->regex )
				)
			);
		}

		return $value;
	}
}
