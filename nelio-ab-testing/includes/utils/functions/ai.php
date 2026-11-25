<?php
/**
 * AI-specific helper functions.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/functions
 * @since      9.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Compares regular attributes with their sanitized representation.
 *
 * @param TAttributes $attributes           Regular attributes.
 * @param TAttributes $sanitized_attributes Sanitized attributes.
 *
 * @return list<TAttributes_Validation_Issue> Differences between the two attribute sets.
 *
 * @since 9.0.0
 */
function nab_ai_compare_attributes( $attributes, $sanitized_attributes ) {
	$issues = array();
	$keys   = array_unique( array_merge( array_keys( $attributes ), array_keys( $sanitized_attributes ) ) );
	foreach ( $keys as $key ) {
		$attribute               = (string) $key;
		$in_attributes           = array_key_exists( $key, $attributes );
		$in_sanitized_attributes = array_key_exists( $key, $sanitized_attributes );
		if ( ! $in_attributes && $in_sanitized_attributes ) {
			$issues[] = array(
				'type'      => 'missing',
				'attribute' => $attribute,
				'message'   => sprintf(
					'The `%s` attribute was not provided and was added during sanitization.',
					$attribute
				),
			);
		} elseif ( $in_attributes && ! $in_sanitized_attributes ) {
			$issues[] = array(
				'type'      => 'removed',
				'attribute' => $attribute,
				'message'   => sprintf(
					'The `%s` attribute was removed during sanitization because it is not supported by this test type.',
					$attribute
				),
			);
		} elseif ( $attributes[ $key ] !== $sanitized_attributes[ $key ] ) {
			$original_value  = wp_json_encode( $attributes[ $key ] );
			$sanitized_value = wp_json_encode( $sanitized_attributes[ $key ] );
			$original_value  = false === $original_value ? 'null' : $original_value;
			$sanitized_value = false === $sanitized_value ? 'null' : $sanitized_value;
			$issues[]        = array(
				'type'      => 'changed',
				'attribute' => $attribute,
				'message'   => sprintf(
					'The `%s` attribute was changed during sanitization from `%s` to `%s`, so the supplied value is invalid.',
					$attribute,
					$original_value,
					$sanitized_value
				),
			);
		}
	}
	return $issues;
}

/**
 * Checks whether a value can be used as a URL fragment.
 *
 * @param string $value URL fragment.
 *
 * @return string|false Validation message or false when the value is valid.
 *
 * @since 9.0.0
 */
function nab_ai_is_url_fragment_invalid( $value ) {
	if ( preg_match( '/ /', $value ) ) {
		return 'Value contains spaces.';
	}

	if ( preg_match( '/[^A-Za-z0-9\-._~:\/?#[\]@!$&\'()*+,;=]/', $value ) ) {
		return 'Value contains characters that cannot be found in a URL.';
	}

	return false;
}

/**
 * Checks whether a value is a valid URL.
 *
 * @param string $value URL.
 *
 * @return string|false Validation message or false when the value is valid.
 *
 * @since 9.0.0
 */
function nab_ai_is_url_invalid( $value ) {
	$pattern = '/^(https?:\/\/)?((([a-z\d]([a-z\d-]*[a-z\d])*)\.)+[a-z]{2,}|((\d{1,3}\.){3}\d{1,3}))(:\d+)?(\/[-a-z\d%_.~+]*)*(\?[;&a-z\d%_.~+=-]*)?(#[-a-z\d_]*)?$/i';
	if ( ! preg_match( $pattern, $value ) ) {
		return 'Value is not a valid URL.';
	}

	return false;
}

/**
 * Refreshes a non-heatmap experiment's status after validating it for AI-driven editing.
 *
 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return void
 *
 * @since 9.0.0
 */
function nab_ai_refresh_experiment_status( $experiment ) {
	$experiment_type = $experiment->get_type();
	$alternatives    = $experiment->get_alternatives();

	$update_status = static function ( $validation_status ) use ( $experiment ) {
		$status = $experiment->get_status();
		if ( in_array( $status, array( 'running', 'finished', 'trash' ), true ) ) {
			return;
		}

		if ( 'issues' === $validation_status ) {
			$experiment->set_status( false !== strpos( $status, 'paused' ) ? 'paused_draft' : 'draft' );
		} elseif ( false !== strpos( $status, 'paused' ) ) {
			$experiment->set_status( 'paused' );
		} elseif ( 'scheduled' === $status && false !== $experiment->get_start_date() && strtotime( $experiment->get_start_date() ) > time() ) {
			$experiment->set_status( 'scheduled' );
		} else {
			$experiment->set_status( 'ready' );
		}
	};

	if ( '' === trim( $experiment->get_name() ) ) {
		$update_status( 'issues' );
		return;
	}

	if ( count( $alternatives ) < 2 ) {
		$update_status( 'issues' );
		return;
	}

	$control = null;
	foreach ( $alternatives as $alternative ) {
		if ( 'control' === $alternative['id'] ) {
			$control = $alternative;
		}
	}

	if ( ! is_array( $control ) ) {
		$update_status( 'issues' );
		return;
	} else {
		$validation = Nelio_AB_Testing_Variant_Manager::ai_validate( $experiment_type, 'control', $control['attributes'], $experiment->ID );
		if ( 'valid' !== $validation['status'] ) {
			$update_status( 'issues' );
			return;
		}
	}

	foreach ( $alternatives as $alternative ) {
		if ( 'control' === $alternative['id'] ) {
			continue;
		}
		$validation = Nelio_AB_Testing_Variant_Manager::ai_validate( $experiment_type, 'alternative', $alternative['attributes'], $experiment->ID );
		if ( 'valid' !== $validation['status'] ) {
			$update_status( 'issues' );
			return;
		}
	}

	$goal_validation = Nelio_AB_Testing_Goal_Manager::ai_validate( $experiment->get_goals(), $experiment->ID );
	if ( is_wp_error( $goal_validation ) ) {
		$update_status( 'issues' );
		return;
	}

	$segment_validation = Nelio_AB_Testing_Segment_Manager::ai_validate( $experiment->get_segments() );
	if ( is_wp_error( $segment_validation ) ) {
		$update_status( 'issues' );
		return;
	}

	$scope_validation = Nelio_AB_Testing_Experiment_Scope_Manager::ai_validate( $experiment->get_scope(), $experiment );
	if ( 'valid' !== $scope_validation['status'] ) {
		$update_status( 'issues' );
		return;
	}

	$trigger_validation = Nelio_AB_Testing_Page_View_Trigger_Manager::ai_validate( $experiment->get_page_view_triggers(), $experiment->ID );
	if ( 'valid' !== $trigger_validation['status'] ) {
		$update_status( 'issues' );
		return;
	}

	$update_status( 'valid' );
}

/**
 * Refreshes a heatmap's status after validating its AI-editable configuration.
 *
 * Heatmaps do not have variants, goals, segments, or regular experiment scope.
 * Their status depends on the test name, tracked element, participation rules,
 * and supported end settings instead.
 *
 * @param Nelio_AB_Testing_Heatmap $heatmap Heatmap.
 *
 * @return void
 *
 * @since 9.0.0
 */
function nab_ai_refresh_heatmap_status( $heatmap ) {
	$update_status = static function ( $validation_status ) use ( $heatmap ) {
		$status = $heatmap->get_status();
		if ( in_array( $status, array( 'running', 'finished', 'trash' ), true ) ) {
			return;
		}

		if ( 'issues' === $validation_status ) {
			$heatmap->set_status( false !== strpos( $status, 'paused' ) ? 'paused_draft' : 'draft' );
		} elseif ( false !== strpos( $status, 'paused' ) ) {
			$heatmap->set_status( 'paused' );
		} elseif ( 'scheduled' === $status && false !== $heatmap->get_start_date() && strtotime( $heatmap->get_start_date() ) > time() ) {
			$heatmap->set_status( 'scheduled' );
		} else {
			$heatmap->set_status( 'ready' );
		}
	};

	if ( '' === trim( $heatmap->get_name() ) ) {
		$update_status( 'issues' );
		return;
	}

	if ( 'confidence' === $heatmap->get_end_mode() ) {
		$update_status( 'issues' );
		return;
	}

	if ( 'post' === $heatmap->get_tracking_mode() ) {
		$post_id   = $heatmap->get_tracked_post_id();
		$post_type = $heatmap->get_tracked_post_type();
		$post      = get_post( $post_id );
		if ( ! $post_id || '' === $post_type || ! $post || $post->post_type !== $post_type ) {
			$update_status( 'issues' );
			return;
		}
	} else {
		$url = $heatmap->get_tracked_url();
		if ( '' === $url || nab_ai_is_url_invalid( $url ) ) {
			$update_status( 'issues' );
			return;
		}
	}

	$conditions = $heatmap->get_participation_conditions();
	if ( ! empty( $conditions ) ) {
		$validation = Nelio_AB_Testing_Segment_Manager::ai_validate_segment(
			array(
				'id'                => nab_uuid(),
				'attributes'        => array( 'name' => '' ),
				'segmentationRules' => $conditions,
			)
		);
		if ( is_wp_error( $validation ) ) {
			$update_status( 'issues' );
			return;
		}
	}

	$update_status( 'valid' );
}
