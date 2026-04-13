<?php

namespace Nelio_AB_Testing\Zod;

class ObjectSchema extends Schema {

	/** @var array<string,Schema> */
	protected array $schema;

	/**
	 * Creates an object schema.
	 *
	 * @param array<string,Schema> $schema Schema definition.
	 *
	 * @return ObjectSchema
	 */
	public static function make( $schema ) {
		$instance         = new self();
		$instance->schema = $schema;
		return $instance;
	}

	/**
	 * Sets all keys in the schema as optional.
	 *
	 * @return static
	 */
	public function partial() {
		$this->schema = array_map(
			fn( $s ) => $s->optional(),
			$this->schema
		);
		return $this;
	}

	/**
	 * Sets all keys in the schema as required.
	 *
	 * @return static
	 */
	public function all_required() {
		$this->schema = array_map(
			fn( $s ) => $s->required(),
			$this->schema
		);
		return $this;
	}

	public function parse_value( $value ) {
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}

		if ( ! is_array( $value ) ) {
			throw new \Exception(
				sprintf(
					'Expected an object, but %s found.',
					esc_html( gettype( $value ) )
				)
			);
		}

		$result = array();
		foreach ( $this->schema as $prop => $schema ) {
			try {
				$result[ $prop ] = $schema->parse( isset( $value[ $prop ] ) ? $value[ $prop ] : null );
			} catch ( \Exception $e ) {
				throw new \Exception( esc_html( $this->add_path( $e->getMessage(), "{$prop}" ) ) );
			}
		}
		return array_filter( $result, fn( $p ) => ! is_null( $p ) );
	}
}
