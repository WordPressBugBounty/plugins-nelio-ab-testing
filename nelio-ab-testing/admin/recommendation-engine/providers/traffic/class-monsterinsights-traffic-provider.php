<?php

namespace Nelio_AB_Testing\Recommendation_Engine\Providers\Traffic;

use Nelio_AB_Testing\Recommendation_Engine\Content_Traffic;

final class MonsterInsights_Traffic_Provider implements Traffic_Provider {

	public static function is_available() {
		return defined( 'MONSTERINSIGHTS_VERSION' );
	}

	public function get_top_pages( $limit = 20 ) {
		if ( ! self::is_available() ) {
			return array();
		}

		$rows = $this->get_report_rows();

		if ( empty( $rows ) ) {
			return array();
		}

		$result = array();

		foreach ( $rows as $row ) {
			$url = isset( $row['url'] ) ? $row['url'] : ( $row['link'] ?? '' );
			if ( ! is_string( $url ) ) {
				continue;
			}

			$views   = absint( isset( $row['sessions'] ) ? $row['sessions'] : ( $row['views'] ?? 0 ) );
			$post_id = nab_url_to_postid( $url );
			if ( ! $post_id || ! $views ) {
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

	/**
	 * Returns the rows from MonsterInsights' "Top Posts" report.
	 *
	 * This method uses MonsterInsights' internal reporting API, which is not
	 * considered a public API and may change between releases. If the report
	 * cannot be retrieved, an empty array is returned.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_report_rows() {
		if ( ! function_exists( '\MonsterInsights' ) ) {
			return array();
		}

		$reporting = \MonsterInsights()->reporting;
		$report    = $reporting->get_report( 'topposts' );
		if ( ! is_array( $report ) || empty( $report['data'] ) ) {
			return array();
		}

		return $report['data'];
	}
}
