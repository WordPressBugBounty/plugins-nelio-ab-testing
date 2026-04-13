<?php

namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function array_filter;
use function array_keys;
use function str_replace;
use function strpos;

/**
 * Class responsible for tracking which experiments have been seen and, therefore, require page view tracking.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<TWidget_Control_Attributes,TWidget_Alternative_Attributes>
 */
class Alternative_Widget_Loader extends \Nelio_AB_Testing_Alternative_Loader {

	public function init() {
		add_filter( 'sidebar_widgets', array( $this, 'replace_sidebar_widgets' ) );
	}

	/**
	 * Callback to replace sidebar widgets.
	 *
	 * @param array<string,list<mixed>> $sidebars_widgets Sidebars widgets.
	 *
	 * @return array<string,list<mixed>>
	 */
	public function replace_sidebar_widgets( $sidebars_widgets ) {
		$prefix = get_sidebar_prefix( $this->experiment_id, $this->alternative_id );

		$sidebars_widgets = array_filter(
			$sidebars_widgets,
			function ( $sidebar ) use ( $prefix ) {
				/** @var string $sidebar */

				return 0 === strpos( $sidebar, $prefix );
			},
			ARRAY_FILTER_USE_KEY
		);

		$keys = array_keys( $sidebars_widgets );
		foreach ( $keys as $key ) {
			$new_key                      = str_replace( $prefix, '', $key );
			$sidebars_widgets[ $new_key ] = $sidebars_widgets[ $key ];
			unset( $sidebars_widgets[ $key ] );
		}

		return $sidebars_widgets;
	}
}
