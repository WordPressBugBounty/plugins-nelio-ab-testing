<?php

namespace Nelio_AB_Testing\Recommendation_Engine\Providers\Traffic;

use Nelio_AB_Testing\Recommendation_Engine\Content_Traffic;

final class Jetpack_Traffic_Provider implements Traffic_Provider {

	// @Implements
	public static function is_available() {
		return class_exists( '\Jetpack_Options' )
			&& absint( \Jetpack_Options::get_option( 'id' ) )
			&& ! empty( \Jetpack_Options::get_option( 'blog_token' ) )
			&& is_string( \Jetpack_Options::get_option( 'blog_token' ) );
	}

	// @Implements
	public function get_top_pages( $limit = 20 ) {
		if ( ! self::is_available() ) {
			return array();
		}

		/** @var int */
		$site_id = absint( \Jetpack_Options::get_option( 'id' ) );

		/** @var string */
		$token = \Jetpack_Options::get_option( 'blog_token' );

		$response = wp_remote_request(
			add_query_arg(
				array(
					'period' => 'month',
					'limit'  => absint( $limit ),
				),
				"https://public-api.wordpress.com/rest/v1.1/sites/{$site_id}/stats/views/posts"
			),
			array(
				'method'  => 'GET',
				'timeout' => absint( apply_filters( 'nab_recommendations_request_timeout', 10 ) ),
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		/** @var array{posts?:mixed}|false */
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body ) || empty( $body['posts'] ) || ! is_array( $body['posts'] ) ) {
			return array();
		}

		/** @var array{posts:array<int,array<string,mixed>>} $body */
		return $this->parse_posts( $body['posts'], $limit );
	}

	/**
	 * Converts the response returned by the Jetpack Stats API into a list of
	 * content traffic entries.
	 *
	 * @param array<int,array<string,mixed>> $posts List of post stats as returned by the Jetpack Stats API.
	 * @param int                            $limit Maximum number of entries to return.
	 *
	 * @return list<Content_Traffic>
	 */
	private function parse_posts( $posts, $limit ) {
		$result = array();

		foreach ( $posts as $post ) {
			$post_id = absint( isset( $post['post_id'] ) ? $post['post_id'] : 0 );
			$views   = absint( isset( $post['views'] ) ? $post['views'] : 0 );

			if ( ! $post_id || ! $views || ! get_post( $post_id ) ) {
				continue;
			}

			$post_type = get_post_type( $post_id );
			$permalink = get_permalink( $post_id );
			if ( empty( $post_type ) || empty( $permalink ) ) {
				continue;
			}

			$result[] = new Content_Traffic(
				$post_type,
				$post_id,
				$permalink,
				$views
			);

			if ( count( $result ) >= $limit ) {
				break;
			}
		}

		return $result;
	}
}
