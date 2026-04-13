<?php

namespace Nelio_AB_Testing\Zod;

class ArraySchema extends Schema {

	/** @var int|null */
	private $min;

	/** @var int|null */
	private $max;

	/** @var Schema */
	private $schema;

	/**
	 * Creates an array schema.
	 *
	 * @param Schema $schema Schema of the items inside the array.
	 *
	 * @return ArraySchema
	 */
	public static function make( $schema ) {
		$instance         = new self();
		$instance->schema = $schema;
		return $instance;
	}

	/**
	 * Sets the min number of items.
	 *
	 * @param int $min Min number of items.
	 *
	 * @return static
	 */
	public function min( $min ) {
		$this->min = $min;
		return $this;
	}

	/**
	 * Sets the max number of items.
	 *
	 * @param int $max Max number of items.
	 *
	 * @return static
	 */
	public function max( $max ) {
		$this->max = $max;
		return $this;
	}

	/**
	 * Sets the array as non empty (i.e. min items is 1).
	 *
	 * @return static
	 */
	public function nonempty() {
		return $this->min( 1 );
	}

	/**
	 * Sets the expected array length.
	 *
	 * @param int $length Length.
	 *
	 * @return static
	 */
	public function length( $length ) {
		return $this->min( $length )->max( $length );
	}

	public function parse_value( $value ) {
		if ( ! is_array( $value ) ) {
			throw new \Exception(
				sprintf(
					'Expected an array, but %s found.',
					esc_html( gettype( $value ) )
				)
			);
		}

		if (
			! is_null( $this->min ) &&
			$this->min === $this->max &&
			count( $value ) !== $this->min
		) {
			throw new \Exception(
				sprintf(
					'Expected an array of %1$s elements, but array has %2$s elements.',
					esc_html( $this->min ),
					count( $value )
				)
			);
		}

		if ( ! is_null( $this->min ) && count( $value ) < $this->min ) {
			throw new \Exception(
				sprintf(
					'Expected an array with at least %1$s elements, but array has %2$s elements.',
					esc_html( "$this->min" ),
					count( $value )
				)
			);
		}

		if ( ! is_null( $this->max ) && $this->max < count( $value ) ) {
			throw new \Exception(
				sprintf(
					'Expected an array with up to %1$s elements, but array has %2$s elements.',
					esc_html( "$this->max" ),
					count( $value )
				)
			);
		}

		$result = array();
		foreach ( $value as $index => $item ) {
			try {
				$result[] = $this->schema->parse( $item );
			} catch ( \Exception $e ) {
				throw new \Exception( esc_html( $this->add_path( $e->getMessage(), "{$index}" ) ) );
			}
		}
		return $result;
	}
}
