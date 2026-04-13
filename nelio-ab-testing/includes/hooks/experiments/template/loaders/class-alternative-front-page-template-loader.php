<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Class responsible for loading template alternatives for the front page.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<TTemplate_Control_Attributes,TTemplate_Alternative_Attributes>
 */
class Alternative_Front_Page_Template_Loader extends \Nelio_AB_Testing_Alternative_Loader {

	public function init() {
		add_filter( 'template_include', array( $this, 'maybe_replace_template' ) );
	}

	/**
	 * Load alternative template.
	 *
	 * @param string $template Template.
	 *
	 * @return string
	 */
	public function maybe_replace_template( $template ) {
		if ( ! is_front_page() ) {
			return $template;
		}

		if ( false === strpos( $template, '/front-page.php' ) ) {
			return $template;
		}

		return locate_template( $this->alternative['templateId'] );
	}
}
