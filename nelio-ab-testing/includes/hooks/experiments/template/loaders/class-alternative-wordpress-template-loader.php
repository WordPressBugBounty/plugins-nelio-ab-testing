<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Class responsible for loading template alternatives for the front page.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<TTemplate_Control_Attributes,TTemplate_Alternative_Attributes>
 */
class Alternative_WordPress_Template_Loader extends \Nelio_AB_Testing_Alternative_Loader {

	public function init() {
		add_filter( 'get_post_metadata', array( $this, 'maybe_replace_page_template_meta' ), 10, 3 );
	}

	/**
	 * Load alternative template.
	 *
	 * @param mixed  $value     Value.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key.
	 *
	 * @return mixed
	 */
	public function maybe_replace_page_template_meta( $value, $object_id, $meta_key ) {

		if ( '_wp_page_template' !== $meta_key ) {
			return $value;
		}

		$post_type = $this->control['postType'] ?? '';
		if ( get_post_type( $object_id ) !== $post_type ) {
			return $value;
		}

		$actual_template = get_actual_template( $object_id );
		if ( '_nab_default_template' === $this->control['templateId'] ) {
			if ( 'default' === $actual_template ) {
				return $this->alternative['templateId'];
			}
			return $value;
		}

		if ( $actual_template !== $this->control['templateId'] ) {
			return $value;
		}

		if ( '_nab_default_template' === $this->alternative['templateId'] ) {
			return null;
		}

		return $this->alternative['templateId'];
	}
}
