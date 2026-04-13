<?php

namespace Nelio_AB_Testing\Experiment_Library\Theme_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Class responsible for loading theme alternatives.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<TTheme_Control_Attributes,TTheme_Alternative_Attributes>
 */
class Alternative_Theme_Loader extends \Nelio_AB_Testing_Alternative_Loader {

	/** @var \WP_Theme */
	private $theme;

	public function init() {
		add_filter( 'option_stylesheet', array( $this, 'switch_stylesheet' ) );
		add_filter( 'option_template', array( $this, 'switch_template' ) );
	}

	/**
	 * Sets the alternative theme.
	 *
	 * @param \WP_Theme $theme Theme.
	 *
	 * @return void
	 */
	public function set_theme( $theme ) {
		$this->theme = $theme;
	}

	/**
	 * Callback to switch active stylesheet.
	 *
	 * @return string
	 */
	public function switch_stylesheet() {
		/** @var string */
		$value = $this->theme['Stylesheet'];
		return $value;
	}

	/**
	 * Callback to switch active template.
	 *
	 * @return string
	 */
	public function switch_template() {
		/** @var string */
		$value = $this->theme['Template'];
		return $value;}
}
