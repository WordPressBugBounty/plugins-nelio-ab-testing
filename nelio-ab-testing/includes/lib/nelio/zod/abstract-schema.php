<?php

namespace Nelio_AB_Testing\Zod;

abstract class Schema {

	/** @var bool */
	protected bool $is_optional = false;

	/** @var mixed */
	protected $default_value = null;

	/** @var mixed */
	protected $fallback_value = null;

	/** @var callable|null */
	protected $transformation = null;

	/**
	 * Makes this instance optional.
	 *
	 * @return static
	 */
	public function optional() {
		$this->is_optional = true;
		return $this;
	}

	/**
	 * Makes this instance required.
	 *
	 * @return static
	 */
	public function required() {
		$this->is_optional = false;
		return $this;
	}

	/**
	 * Sets the default value to the given value.
	 *
	 * @param mixed $value Value.
	 *
	 * @return static
	 */
	public function default( $value ) {
		$this->is_optional   = true;
		$this->default_value = $value;
		return $this;
	}

	/**
	 * Sets a fallback value to be used in the event of a validation error.
	 *
	 * @param mixed $value Value.
	 *
	 * @return static
	 */
	public function catch( $value ) {
		$this->fallback_value = $value;
		return $this;
	}

	/**
	 * Sets a transformation.
	 *
	 * @param callable(mixed):mixed $transformation Transformation.
	 *
	 * @return static
	 */
	public function transform( $transformation ) {
		$this->transformation = $transformation;
		return $this;
	}

	/**
	 * Parses the value.
	 *
	 * @param mixed $value Value.
	 *
	 * @return array{success:true,data:mixed}|array{success:false,error:string}
	 */
	public function safe_parse( $value = null ) {
		try {
			$result = $this->parse( $value );
			return array(
				'success' => true,
				'data'    => $result,
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}
	}

	/**
	 * Parses the value.
	 *
	 * @param mixed $value Value.
	 *
	 * @return mixed
	 *
	 * @throws \Exception Throws exception if value couldn’t be parsed.
	 */
	public function parse( $value = null ) {
		if ( $this->is_optional && is_null( $value ) ) {
			return $this->default_value;
		}

		try {
			$result = $this->parse_value( $value );
		} catch ( \Exception $e ) {
			if ( ! is_null( $this->fallback_value ) ) {
				return $this->fallback_value;
			} else {
				throw $e;
			}
		}

		return is_callable( $this->transformation )
			? call_user_func( $this->transformation, $result )
			: $result;
	}

	/**
	 * Parses the value.
	 *
	 * @param mixed $value Value.
	 *
	 * @return mixed
	 *
	 * @throws \Exception Throws exception if value couldn’t be parsed.
	 */
	abstract protected function parse_value( $value );

	/**
	 * Adds path to message.
	 *
	 * @param string $message Message.
	 * @param string $key     Key.
	 *
	 * @return string
	 */
	protected function add_path( $message, $key ) {
		$message = preg_replace( '/\.$/', '', $message );
		$message = is_string( $message ) ? $message : '';
		$message = 0 !== strpos( $message, '[' ) ? "[] {$message}" : $message;
		$message = str_replace( '[', "[{$key}.", $message );
		$message = str_replace( '.]', ']', $message );
		$message = $message . '.';
		return $message;
	}
}
