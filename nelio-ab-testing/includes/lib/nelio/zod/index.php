<?php

namespace Nelio_AB_Testing\Zod;

require_once __DIR__ . '/abstract-schema.php';
require_once __DIR__ . '/array-schema.php';
require_once __DIR__ . '/boolean-schema.php';
require_once __DIR__ . '/enum-schema.php';
require_once __DIR__ . '/literal-schema.php';
require_once __DIR__ . '/number-schema.php';
require_once __DIR__ . '/object-schema.php';
require_once __DIR__ . '/record-schema.php';
require_once __DIR__ . '/string-schema.php';
require_once __DIR__ . '/union-schema.php';

class Zod {
	/**
	 * Creates an array schema.
	 *
	 * @param Schema $schema Schema.
	 *
	 * @return ArraySchema
	 */
	public static function array( $schema ) {
		return ArraySchema::make( $schema );
	}

	/**
	 * Creates a boolean schema.
	 *
	 * @return BooleanSchema
	 */
	public static function boolean() {
		return BooleanSchema::make();
	}

	/**
	 * Creates an enum schema.
	 *
	 * @param non-empty-list<string>|non-empty-list<int> $values Values.
	 *
	 * @return EnumSchema
	 */
	public static function enum( $values ) {
		return EnumSchema::make( $values );
	}

	/**
	 * Creates a literal schema.
	 *
	 * @param string|bool|number|null $value Value.
	 *
	 * @return LiteralSchema
	 */
	public static function literal( $value ) {
		return LiteralSchema::make( $value );
	}

	/**
	 * Creates a number schema.
	 *
	 * @return NumberSchema
	 */
	public static function number() {
		return NumberSchema::make();
	}

	/**
	 * Creates a record schema.
	 *
	 * @param Schema $key_schema   Key schema.
	 * @param Schema $value_schema Value schema.
	 *
	 * @return RecordSchema
	 */
	public static function record( $key_schema, $value_schema ) {
		return RecordSchema::make( $key_schema, $value_schema );
	}

	/**
	 * Creates a object schema.
	 *
	 * @param array<string,Schema> $schema Schema.
	 *
	 * @return ObjectSchema
	 */
	public static function object( $schema ) {
		return ObjectSchema::make( $schema );
	}

	/**
	 * Creates a string schema.
	 *
	 * @return StringSchema
	 */
	public static function string() {
		return StringSchema::make();
	}

	/**
	 * Creates a union schema.
	 *
	 * @param non-empty-list<Schema> $schemas Schemas.
	 *
	 * @return UnionSchema
	 */
	public static function union( $schemas ) {
		return UnionSchema::make( $schemas );
	}
}
