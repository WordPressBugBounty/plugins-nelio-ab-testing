<?php

namespace Nelio_AB_Testing\Recommendation_Engine\Providers;

use Nelio_AB_Testing\Recommendation_Engine\Experiment_Repository;
use Nelio_AB_Testing\Recommendation_Engine\Opportunity;

final class Key_Pages_Provider implements Opportunity_Provider {

	/** @var Experiment_Repository */
	private $experiment_repo;

	/** @var int */
	private $limit;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param Experiment_Repository $experiment_repo Tested content repository.
	 * @param int                   $limit           Optional. Max number of opportunties. Default: `20`.
	 *
	 * @return void
	 */
	public function __construct( $experiment_repo, $limit = 10 ) {
		$this->experiment_repo = $experiment_repo;
		$this->limit           = absint( $limit );
	}

	/**
	 * Returns opportunities for pages that are likely to be strategically
	 * important.
	 *
	 * @return list<Opportunity>
	 */
	public function get_opportunities() {
		/** @var array<string,TKey_Page_Candidate> */
		$candidates = array();
		/** @var list<Opportunity> */
		$opportunities = array();

		$page_on_front   = absint( get_option( 'page_on_front' ) );
		$homepage_reason = _x(
			'Your homepage is a key entry point for visitors and a great candidate for optimization.',
			'text',
			'nelio-ab-testing'
		);
		if ( 'page' === get_option( 'show_on_front' ) && ! empty( $page_on_front ) ) {
			$this->add_candidate( $candidates, $page_on_front, 90, $homepage_reason );
		} else {
			$this->add_url_candidate( $candidates, nab_home_url(), 90, $homepage_reason );
		}

		$this->add_woocommerce_pages( $candidates );
		$this->add_menu_pages( $candidates );
		$this->add_matching_pages( $candidates );

		uasort(
			$candidates,
			function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		foreach ( $candidates as $candidate ) {
			if ( ! empty( $candidate['url'] ) ) {
				$opportunities[] = new Opportunity(
					'key-page',
					$candidate['score'],
					array(
						'type'  => 'url',
						'title' => nab_home_url() === $candidate['url'] ? _x( 'Home', 'text', 'nelio-ab-testing' ) : $candidate['url'],
						'url'   => $candidate['url'],
					),
					array(
						'reason' => $candidate['reason'],
					)
				);
			}

			$post_id = $candidate['postId'] ?? 0;
			if (
				empty( $post_id ) ||
				$this->experiment_repo->was_tested(
					$post_id,
					'last-3-months'
				)
			) {
				continue;
			}

			$title     = get_the_title( $post_id );
			$permalink = get_permalink( $post_id );
			if ( empty( $title ) || empty( $permalink ) ) {
				continue;
			}

			$opportunities[] = new Opportunity(
				'key-page',
				$candidate['score'],
				array(
					'type'     => 'post',
					'postType' => 'page',
					'postId'   => $post_id,
					'title'    => $title,
					'url'      => $permalink,
				),
				array(
					'reason' => $candidate['reason'],
				)
			);

			if ( count( $opportunities ) >= $this->limit ) {
				break;
			}
		}

		return $opportunities;
	}

	/**
	 * Adds a page candidate, keeping its highest score.
	 *
	 * @param array<string,TKey_Page_Candidate> $candidates Candidate map.
	 * @param int                               $post_id    Page ID.
	 * @param int                               $score      Candidate score.
	 * @param string                            $reason     Detection reason.
	 *
	 * @return void
	 */
	private function add_candidate( &$candidates, $post_id, $score, $reason ) {
		$post_id = absint( $post_id );

		if ( ! $post_id || 'publish' !== get_post_status( $post_id ) ) {
			return;
		}

		if (
			isset( $candidates[ "id:$post_id" ] ) &&
			$candidates[ "id:$post_id" ]['score'] >= $score
		) {
			return;
		}

		$candidates[ "id:$post_id" ] = array(
			'postId' => $post_id,
			'score'  => $score,
			'reason' => $reason,
		);
	}

	/**
	 * Adds a URL candidate, keeping its highest score.
	 *
	 * @param array<string,TKey_Page_Candidate> $candidates Candidate map.
	 * @param string                            $url        Page URL.
	 * @param int                               $score      Candidate score.
	 * @param string                            $reason     Detection reason.
	 *
	 * @return void
	 */
	private function add_url_candidate( &$candidates, $url, $score, $reason ) {
		if (
			isset( $candidates[ "url:$url" ] ) &&
			$candidates[ "url:$url" ]['score'] >= $score
		) {
			return;
		}

		$candidates[ "url:$url" ] = array(
			'url'    => $url,
			'score'  => $score,
			'reason' => $reason,
		);
	}

	/**
	 * Adds known WooCommerce pages.
	 *
	 * @param array<string,TKey_Page_Candidate> $candidates Candidate map.
	 *
	 * @return void
	 */
	private function add_woocommerce_pages( &$candidates ) {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return;
		}

		foreach (
			array(
				'checkout' => array(
					'score'  => 95,
					'reason' => _x( 'This is a critical conversion step—small improvements can increase sales.', 'text', 'nelio-ab-testing' ),
				),
				'cart'     => array(
					'score'  => 85,
					'reason' => _x( 'This is an important decision point where testing can reduce abandonment.', 'text', 'nelio-ab-testing' ),
				),
				'shop'     => array(
					'score'  => 80,
					'reason' => _x( 'Key discovery page that visitors use to find products and move toward purchase.', 'text', 'nelio-ab-testing' ),
				),
			) as $page => $info
		) {
			$page_id = wc_get_page_id( $page );
			if ( empty( $page_id ) ) {
				continue;
			}

			$this->add_candidate(
				$candidates,
				$page_id,
				$info['score'],
				$info['reason']
			);
		}
	}

	/**
	 * Adds pages included in navigation menus.
	 *
	 * @param array<string,TKey_Page_Candidate> $candidates Candidate map.
	 *
	 * @return void
	 */
	private function add_menu_pages( &$candidates ) {
		foreach ( wp_get_nav_menus() as $menu ) {
			/** @var list<object{type:string,object:string,object_id:int}>|false */
			$items = wp_get_nav_menu_items( $menu->term_id );
			$items = is_array( $items ) ? $items : array();
			foreach ( $items as $item ) {
				if ( 'post_type' !== $item->type || 'page' !== $item->object ) {
					continue;
				}

				$this->add_candidate(
					$candidates,
					$item->object_id,
					60,
					_x( 'This page is easy for visitors to find, making it a strong candidate for optimization.', 'text', 'nelio-ab-testing' )
				);
			}
		}
	}

	/**
	 * Adds pages whose title or slug suggests strategic importance.
	 *
	 * @param array<string,TKey_Page_Candidate> $candidates Candidate map.
	 *
	 * @return void
	 */
	private function add_matching_pages( &$candidates ) {
		$term_scores = array(
			array(
				/* translators: List of comma separated values to identify key pages in the site. Use as many words as needed to capture this concept. */
				'terms' => _x( 'pricing,plans', 'key-page-for-testing', 'nelio-ab-testing' ),
				'score' => 90,
			),
			array(
				/* translators: List of comma separated values to identify key pages in the site. Use as many words as needed to capture this concept. */
				'terms' => _x( 'checkout', 'key-page-for-testing', 'nelio-ab-testing' ),
				'score' => 95,
			),
			array(
				/* translators: List of comma separated values to identify key pages in the site. Use as many words as needed to capture this concept. */
				'terms' => _x( 'contact,get-in-touch', 'key-page-for-testing', 'nelio-ab-testing' ),
				'score' => 70,
			),
			array(
				/* translators: List of comma separated values to identify key pages in the site. Use as many words as needed to capture this concept. */
				'terms' => _x( 'signup,register', 'key-page-for-testing', 'nelio-ab-testing' ),
				'score' => 85,
			),
			array(
				/* translators: List of comma separated values to identify key pages in the site. Use as many words as needed to capture this concept. */
				'terms' => _x( 'demo', 'key-page-for-testing', 'nelio-ab-testing' ),
				'score' => 75,
			),
			array(
				/* translators: List of comma separated values to identify key pages in the site. Use as many words as needed to capture this concept. */
				'terms' => _x( 'features', 'key-page-for-testing', 'nelio-ab-testing' ),
				'score' => 70,
			),
			array(
				/* translators: List of comma separated values to identify key pages in the site. Use as many words as needed to capture this concept. */
				'terms' => _x( 'products,services', 'key-page-for-testing', 'nelio-ab-testing' ),
				'score' => 65,
			),
		);

		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
			)
		);

		foreach ( $pages as $page ) {
			$haystack = strtolower( $page->post_name );

			foreach ( $term_scores as $data ) {
				$score = $data['score'];
				$terms = array_map( fn( $v ) => trim( $v ), explode( ',', $data['terms'] ) );
				foreach ( $terms as $term ) {
					if ( false === strpos( $haystack, $term ) ) {
						continue;
					}

					$this->add_candidate(
						$candidates,
						$page->ID,
						$score,
						sprintf(
							/* translators: %s: Page slug partial. */
							_x( 'Pages with “%s” in their URL are often important and worth optimizing.', 'text', 'nelio-ab-testing' ),
							$term
						)
					);
				}
			}
		}
	}
}
