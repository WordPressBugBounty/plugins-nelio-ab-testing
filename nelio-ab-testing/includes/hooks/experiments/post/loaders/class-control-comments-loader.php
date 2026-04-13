<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for loading control comments in post alternatives.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<TPost_Control_Attributes,TPost_Alternative_Attributes>
 */
class Control_Comments_Loader extends \Nelio_AB_Testing_Alternative_Loader {

	/**
	 * Initialize all hooks.
	 *
	 * @return void
	 */
	public function init() {
		$is_control = $this->alternative['postId'] === $this->control['postId'];
		if ( $is_control ) {
			return;
		}

		add_filter( 'comments_open', array( $this, 'replace_comments_open' ), 10, 2 );
		add_filter( 'comments_template_query_args', array( $this, 'replace_comment_query_to_load_control_comments' ) );
		add_filter( 'get_comments_number', array( $this, 'replace_comment_count' ), 10, 2 );
		add_filter( 'comment_id_fields', array( $this, 'replace_comment_form_fields_to_insert_into_control' ), 10, 3 );
	}

	/**
	 * Callback to use comment status from control.
	 *
	 * @param boolean $result  Result.
	 * @param int     $post_id Post ID.
	 *
	 * @return bool
	 */
	public function replace_comments_open( $result, $post_id ) {
		if ( $post_id !== $this->alternative['postId'] ) {
			return $result;
		}
		return comments_open( $this->control['postId'] );
	}

	/**
	 * Callback to show control comments.
	 *
	 * @param array<string,mixed> $query Query.
	 *
	 * @return array<string,mixed>
	 */
	public function replace_comment_query_to_load_control_comments( $query ) {
		if ( $query['post_id'] !== $this->alternative['postId'] ) {
			return $query;
		}
		/** @var array<string,mixed> $query */
		$query = wp_parse_args(
			array( 'post_id' => $this->control['postId'] ),
			$query
		);
		return $query;
	}

	/**
	 * Callback to show appropriate comment count.
	 *
	 * @param string $count   Comment count.
	 * @param int    $post_id Post ID.
	 *
	 * @return string
	 */
	public function replace_comment_count( $count, $post_id ) {
		if ( $post_id !== $this->alternative['postId'] ) {
			return $count;
		}

		$aux = get_post( $this->control['postId'] );
		if ( empty( $aux ) ) {
			return $count;
		}

		return $aux->comment_count;
	}

	/**
	 * Callback to use control comment form.
	 *
	 * @param string $fields      Fields.
	 * @param int    $post_id     Post ID.
	 * @param int    $reply_to_id Reply to ID.
	 *
	 * @return string
	 */
	public function replace_comment_form_fields_to_insert_into_control( $fields, $post_id, $reply_to_id ) {
		if ( $post_id !== $this->alternative['postId'] ) {
			return $fields;
		}

		$fields  = '';
		$fields .= sprintf(
			'<input type="hidden" id="%1$s" name="%1$s" value="%2$s" />',
			esc_attr( 'comment_post_ID' ),
			esc_attr( $this->control['postId'] )
		);
		$fields .= sprintf(
			'<input type="hidden" id="%1$s" name="%1$s" value="%2$s" />',
			esc_attr( 'comment_parent' ),
			esc_attr( $reply_to_id )
		);

		return $fields;
	}
}
