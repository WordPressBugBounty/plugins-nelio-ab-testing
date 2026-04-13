<?php

namespace Nelio_AB_Testing\Zod;

class UnionSchema extends Schema {

	/** @var list<Schema> */
	private array $schemas;

	/**
	 * Creates a union schema.
	 *
	 * @param non-empty-list<Schema> $schemas Schemas.
	 *
	 * @return UnionSchema
	 */
	public static function make( $schemas ) {
		$instance          = new self();
		$instance->schemas = $schemas;
		return $instance;
	}

	public function parse_value( $value ) {
		$result = array( 'success' => false );
		foreach ( $this->schemas as $schema ) {
			try {
				$result = array(
					'success' => true,
					'data'    => $schema->parse( $value ),
				);
				break;
			// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			} catch ( \Exception $e ) {
				// As soon as one element of the union successfully parses, there’s no need to continue.
			}
		}

		if ( empty( $result['success'] ) ) {
			throw new \Exception(
				sprintf(
					'Invalid value %s.',
					esc_html( gettype( $value ) )
				)
			);
		}

		return $result['data'];
	}
}
