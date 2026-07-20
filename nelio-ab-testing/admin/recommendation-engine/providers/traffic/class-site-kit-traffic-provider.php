<?php

namespace Nelio_AB_Testing\Recommendation_Engine\Providers\Traffic;

use Nelio_AB_Testing\Recommendation_Engine\Content_Traffic;

final class Site_Kit_Traffic_Provider implements Traffic_Provider {

	// @Implements
	public static function is_available() {
		return defined( 'GOOGLESITEKIT_VERSION' );
	}

	// @Implements
	public function get_top_pages( $limit = 20 ) {
		if ( ! self::is_available() ) {
			return array();
		}

		$response = wp_remote_request(
			add_query_arg(
				array( 'per_page' => absint( $limit ) ),
				rest_url( 'google-site-kit/v1/modules/analytics-4/data/top-pages' )
			),
			array(
				'method'  => 'GET',
				'timeout' => absint( apply_filters( 'nab_recommendations_request_timeout', 10 ) ),
				'headers' => array(
					'X-WP-Nonce' => wp_create_nonce( 'wp_rest' ),
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		return $this->parse_rows( json_decode( wp_remote_retrieve_body( $response ), true ), $limit );
	}

	/**
	 * Converts the response returned by the Site Kit Analytics API into a list of content traffic entries.
	 *
	 * @param mixed $rows  List of page statistics as returned by the Site Kit API.
	 * @param int   $limit Maximum number of entries to return.
	 *
	 * @return list<Content_Traffic>
	 */
	private function parse_rows( $rows, $limit ) {
		if ( ! is_array( $rows ) || ! is_array( $rows[0] ?? null ) ) {
			return array();
		}

		/** @var list<array<string,mixed>> $rows */
		$result = array();

		foreach ( $rows as $row ) {
			$url       = sanitize_text_field( $row['url'] ?? '' );
			$page_path = sanitize_text_field( $row['pagePath'] ?? '' );
			if ( empty( $url ) && empty( $page_path ) ) {
				continue;
			}

			$url   = ! empty( $url ) ? $url : home_url( $page_path );
			$views = absint( $row['screenPageViews'] ?? $row['views'] ?? 0 );

			$content = $this->to_content_traffic( $url, $views );

			if ( $content ) {
				$result[] = $content;
			}

			if ( count( $result ) >= $limit ) {
				break;
			}
		}

		return $result;
	}

	/**
	 * Converts the URL and views to a content traffic instance.
	 *
	 * @param string $url   URL.
	 * @param int    $views Number of views.
	 *
	 * @return Content_Traffic|null
	 */
	private function to_content_traffic( $url, $views ) {
		if ( ! $url || ! $views ) {
			return null;
		}

		$post_id = nab_url_to_postid( $url );
		if ( ! $post_id ) {
			return null;
		}

		$post_type = get_post_type( $post_id );
		$permalink = get_permalink( $post_id );
		if ( empty( $post_type ) || empty( $permalink ) ) {
			return null;
		}

		return new Content_Traffic(
			$post_type,
			$post_id,
			$permalink,
			$views
		);
	}
}
