<?php

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class to keep track of hooks added by a specific type of test.
 *
 * @template TLocal_Control_Attributes     TAttributes
 * @template TLocal_Alternative_Attributes TAttributes
 */
abstract class Nelio_AB_Testing_Alternative_Loader {
	/** @var TLocal_Control_Attributes */
	protected $control;

	/** @var TLocal_Alternative_Attributes|TLocal_Control_Attributes */
	protected $alternative;

	/** @var int */
	protected $experiment_id;

	/** @var string */
	protected $alternative_id;

	/**
	 * Constructor.
	 *
	 * @param TLocal_Alternative_Attributes|TLocal_Control_Attributes $alternative    Alternative attributes.
	 * @param TLocal_Control_Attributes                               $control        Control attributes.
	 * @param int                                                     $experiment_id  Experiment ID.
	 * @param string                                                  $alternative_id Alternative ID.
	 */
	public function __construct( $alternative, $control, $experiment_id, $alternative_id ) {
		$this->alternative    = $alternative;
		$this->control        = $control;
		$this->experiment_id  = $experiment_id;
		$this->alternative_id = $alternative_id;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	abstract public function init();
}
