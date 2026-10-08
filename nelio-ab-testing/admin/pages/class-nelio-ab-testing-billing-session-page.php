<?php
/**
 * Handles the billing session admin-post page.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Opens billing sessions without rendering the normal WordPress admin chrome.
 */
class Nelio_AB_Testing_Billing_Session_Page {

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_post_nab_create_billing_session', array( $this, 'open_billing_session' ) );
	}

	/**
	 * Opens a billing session from a chrome-less admin-post page.
	 *
	 * @return void
	 */
	public function open_billing_session() {
		if ( ! current_user_can( 'manage_nab_account' ) ) {
			$this->die_on_billing_session_error( _x( 'You’re not allowed to manage this subscription.', 'user', 'nelio-ab-testing' ) );
		}

		$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, 'nab_create_billing_session_' . get_current_user_id() ) ) {
			$this->die_on_billing_session_error( _x( 'Invalid billing session request.', 'text', 'nelio-ab-testing' ) );
		}

		$subscription = sanitize_text_field( wp_unslash( $_GET['subscription'] ?? '' ) );
		if ( empty( $subscription ) ) {
			$this->die_on_billing_session_error( _x( 'Invalid billing session request.', 'text', 'nelio-ab-testing' ) );
		}

		$url = ( new Nelio_AB_Testing_Account_REST_Controller() )->get_billing_session_url( $subscription );
		if ( is_wp_error( $url ) ) {
			$this->die_on_billing_session_error( $url->get_error_message() );
		}

		nocache_headers();
		header( 'Referrer-Policy: no-referrer' );
		wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit; // @codeCoverageIgnore
	}

	/**
	 * Renders a minimal error page for billing session errors.
	 *
	 * @param string $message Error message.
	 *
	 * @return never
	 */
	private function die_on_billing_session_error( $message ) {
		// @codeCoverageIgnoreStart
		status_header( 403 );
		nocache_headers();
		header( 'Referrer-Policy: no-referrer' );
		?>
		<!doctype html>
		<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>" />
				<meta name="viewport" content="width=device-width, initial-scale=1" />
				<meta name="referrer" content="no-referrer" />
				<title><?php echo esc_html_x( 'Billing Session Error', 'text', 'nelio-ab-testing' ); ?></title>
			</head>
			<body>
				<p><?php echo esc_html( $message ); ?></p>
			</body>
		</html>
		<?php
		exit;
		// @codeCoverageIgnoreEnd
	}
}
