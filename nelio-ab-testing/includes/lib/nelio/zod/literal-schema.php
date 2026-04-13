<?php

namespace Nelio_AB_Testing\Zod;

class LiteralSchema extends Schema {

	/** @var string|bool|number|null */
	private $value;

	/**
	 * Creates a literal schema.
	 *
	 * @param string|bool|number|null $value Value.
	 *
	 * @return LiteralSchema
	 */
	public static function make( $value ) {
		$instance        = new self();
		$instance->value = $value;
		return $instance;
	}

	public function parse_value( $value ) {
		if ( gettype( $value ) !== gettype( $this->value ) ) {
			throw new \Exception(
				sprintf(
					'Expected %1$s, but %2$s found.',
					esc_html( gettype( $this->value ) ),
					esc_html( gettype( $value ) )
				)
			);
		}

		if ( $value !== $this->value ) {
			throw new \Exception(
				sprintf(
					'Expected %1$s, but %2$s found.',
					esc_html( (string) $this->value ),
					esc_html( (string) $value )
				)
			);
		}

		return $value;
	}
}
