<?php

namespace Nelio_AB_Testing\Zod;

class RecordSchema extends Schema {

	/** @var Schema */
	protected $key_schema;

	/** @var Schema */
	protected $value_schema;

	/**
	 * Creates a record schema.
	 *
	 * @param Schema $key_schema   Key schema.
	 * @param Schema $value_schema Value schema.
	 *
	 * @return RecordSchema
	 */
	public static function make( $key_schema, $value_schema ) {
		$instance               = new self();
		$instance->key_schema   = $key_schema;
		$instance->value_schema = $value_schema;
		return $instance;
	}

	public function parse_value( $value ) {
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}

		if ( ! is_array( $value ) ) {
			throw new \Exception(
				sprintf(
					'Expected a record, but %s found.',
					esc_html( gettype( $value ) )
				)
			);
		}

		$result = array();
		foreach ( $value as $key => $val ) {
			try {
				$this->key_schema->parse( $key );
			} catch ( \Exception $e ) {
				throw new \Exception( esc_html( $this->add_path( 'Invalid key: ' . $e->getMessage(), "{$key}" ) ) );
			}

			try {
				$result[ $key ] = $this->value_schema->parse( $val );
			} catch ( \Exception $e ) {
				throw new \Exception( esc_html( $this->add_path( 'Invalid value: ' . $e->getMessage(), "{$key}" ) ) );
			}
		}

		return array_filter( $result, fn( $p ) => ! is_null( $p ) );
	}
}
