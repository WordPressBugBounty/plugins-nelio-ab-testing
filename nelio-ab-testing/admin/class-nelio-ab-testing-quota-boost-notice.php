<?php

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_Quota_Boost_Notice {

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'shutdown', array( $this, 'render_notice' ) );
		add_action( 'admin_init', array( $this, 'handle_dismiss' ) );
	}

	/**
	 * Renders quota boost notice when required.
	 *
	 * @return void
	 */
	public function render_notice() {
		if ( ! is_admin() ) {
			return; // @codeCoverageIgnore
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( empty( $screen ) ) {
			return; // @codeCoverageIgnore
		}
		if (
			'nelio-a-b-testing_page_nelio-ab-testing-overview' !== $screen->id &&
			'edit-nab_experiment' !== $screen->id
		) {
			return;
		}

		$user_id = get_current_user_id();
		if ( ! empty( get_user_meta( $user_id, '_nab_quota_boost_notice_dismissed', true ) ) ) {
			return;
		}

		/** @var TAWS_Site|false */
		$site = get_transient( 'nab_site_object' );
		if ( empty( $site ) ) {
			return;
		}

		$rest     = new Nelio_AB_Testing_Account_REST_Controller();
		$response = $rest->get_site_quota();
		if ( is_wp_error( $response ) ) {
			// NOTE. Response should not be an error, because we’re getting the data from the transient.
			return; // @codeCoverageIgnore
		}

		$quota      = $response['availableQuota'];
		$expiration = ! empty( $response['boostExpiration'] ) ? strtotime( $response['boostExpiration'] ) : 0;
		if ( $expiration < time() ) {
			return;
		}

		$message = sprintf(
			/* translators: %1$s: Icon. %2$s: Number of page views. %3$s: Date. */
			esc_html_x( '%1$s Activation Boost Active. You can use up to %2$s page views until %3$s to run tests and gather meaningful results.', 'text', 'nelio-ab-testing' ),
			'🚀',
			number_format_i18n( $quota ),
			wp_date( _x( 'F j', 'date wihout year', 'nelio-ab-testing' ), $expiration )
		);

		$action      = 'nab_quota_boost_notice';
		$dismiss_url = add_query_arg( '_wpnonce', wp_create_nonce( $action ), add_query_arg( $action, 1 ) );
		printf(
			'<script type="text/javascript">wp.data.dispatch( "core/notices" ).createInfoNotice( %s, {
				isDismissible: true,
				onDismiss: () => { window.location.href = %s },
			} );</script>',
			wp_json_encode( $message ),
			wp_json_encode( $dismiss_url )
		);
	}

	/**
	 * Stores a user meta to dismiss notice and never show it again.
	 *
	 * @return void
	 */
	public function handle_dismiss() {
		if ( empty( $_GET['nab_quota_boost_notice'] ) ) {
			return; // @codeCoverageIgnore
		}

		check_admin_referer( 'nab_quota_boost_notice' );

		update_user_meta(
			get_current_user_id(),
			'_nab_quota_boost_notice_dismissed',
			'true'
		);

		wp_safe_redirect(
			remove_query_arg( array( 'nab_quota_boost_notice', '_wpnonce' ) )
		);
		exit; // @codeCoverageIgnore
	}
}
