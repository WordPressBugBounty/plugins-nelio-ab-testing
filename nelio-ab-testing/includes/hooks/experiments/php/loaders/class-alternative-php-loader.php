<?php

namespace Nelio_AB_Testing\Experiment_Library\Php_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for tracking which experiments have been seen and, therefore, require page view tracking.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<TPhp_Control_Attributes,TPhp_Alternative_Attributes>
 */
class Alternative_Php_Loader extends \Nelio_AB_Testing_Alternative_Loader {

	public function init() {
		\nab_eval_php( $this->alternative['snippet'] ?? '' );
	}
}
