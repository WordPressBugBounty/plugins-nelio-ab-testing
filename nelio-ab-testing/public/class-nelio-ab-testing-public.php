<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The public-facing functionality of the plugin.
 */
class Nelio_AB_Testing_Public {

	/**
	 * This instance.
	 *
	 * @var Nelio_AB_Testing_Public|null
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Public
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {

		$this->load_admin_helpers();

		add_action( 'plugins_loaded', array( $this, 'maybe_init_split_testing' ), 5 );
		add_action( 'plugins_loaded', array( $this, 'nab_public_init' ), 9999 );

		add_action( 'init', array( $this, 'update_user_session_cookies' ), 1 );
		add_action( 'set_logged_in_cookie', array( $this, 'set_user_session_cookies' ), 10, 4 );
		add_action( 'clear_auth_cookie', array( $this, 'clear_user_session_cookies' ), 1 );
		add_action( 'set_current_user', array( $this, 'maybe_simulate_anonymous_visitor' ), 99 );
	}

	/**
	 * Callback to run `nab_public_init`.
	 *
	 * @return void
	 */
	public function nab_public_init() {
		if ( is_admin() ) {
			return;
		}

		/**
		 * Initializes the public facet of the plugin.
		 *
		 * Fires right after WordPress’ `plugins_loaded` action with a low priority
		 * (so that other plugins can hook into `nab_public_init` during `plugins_loaded`).
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_public_init' );
	}

	/**
	 * Callback to set session cookies (`nabIsUserLoggedIn` and `nabAlternative`).
	 *
	 * @param string|null $logged_in_cookie Unused.
	 * @param int|null    $expire           Unused.
	 * @param int         $expiration       The time when the logged-in authentication cookie expires as a UNIX timestamp.
	 * @param int         $user_id          User ID.
	 *
	 * @return void
	 */
	public function set_user_session_cookies( $logged_in_cookie, $expire, $expiration, $user_id ) {
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
		setcookie( 'nabIsUserLoggedIn', 'true', $expiration, '/' );

		if ( ! $this->is_visitor_tested( $user_id ) ) {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
			setcookie( 'nabAlternative', 'none', $expiration, '/' );
		} elseif ( 'none' === sanitize_text_field( wp_unslash( $_COOKIE['nabAlternative'] ?? '' ) ) ) {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
			setcookie( 'nabAlternative', 'none', time() - YEAR_IN_SECONDS, '/' );
		}
	}

	/**
	 * Callback to update user serssion cookies.
	 *
	 * @return void
	 */
	public function update_user_session_cookies() {
		if ( isset( $_COOKIE['nabIsUserLoggedIn'] ) && 'none' === sanitize_text_field( wp_unslash( $_COOKIE['nabAlternative'] ?? '' ) ) ) {
			return;
		}

		if ( nab_is_preview() || nab_is_heatmap() ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			return;
		}

		$this->set_user_session_cookies( null, null, time() + DAY_IN_SECONDS, $user_id );
	}

	/**
	 * Callback to clear user session cookies.
	 *
	 * @return void
	 */
	public function clear_user_session_cookies() {
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
		setcookie( 'nabIsUserLoggedIn', 'true', time() - YEAR_IN_SECONDS, '/' );
		if ( 'none' === sanitize_text_field( wp_unslash( $_COOKIE['nabAlternative'] ?? '' ) ) ) {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
			setcookie( 'nabAlternative', 'none', time() - YEAR_IN_SECONDS, '/' );
		}
	}

	/**
	 * Callback to add our split testing hooks (if split testing is not disabled).
	 *
	 * @return void
	 */
	public function maybe_init_split_testing() {
		if ( nab_is_split_testing_disabled() ) {
			return;
		}
		Nelio_AB_Testing_Runtime::instance()->init();
		Nelio_AB_Testing_Main_Script::instance()->init();
	}

	/**
	 * Callback to maybe simulate an anonymous visitor by setting the current user to none.
	 *
	 * @return void
	 */
	public function maybe_simulate_anonymous_visitor() {
		/**
		 * Simulates an anonymous visitor.
		 *
		 * When set to `true`, Nelio A/B Testing will set the current user
		 * to an anonymous (non-logged-in) user. This way, the web will look
		 * as if an anonymous users were browsing the site.
		 *
		 * @param boolean $anonymize whether the user should be set to an anonymous
		 *                           user or not. Default: `false`.
		 *
		 * @since 5.0.0
		 */
		if ( ! apply_filters( 'nab_simulate_anonymous_visitor', false ) ) {
			return;
		}

		wp_set_current_user( 0 );
	}

	/**
	 * Loads admin helpers hooks.
	 *
	 * @return void
	 */
	private function load_admin_helpers() {
		Nelio_AB_Testing_Alternative_Preview::instance()->init();
		Nelio_AB_Testing_Css_Selector_Finder::instance()->init();
		Nelio_AB_Testing_Heatmap_Renderer::instance()->init();
		Nelio_AB_Testing_Quick_Experiment_Menu::instance()->init();
	}

	/**
	 * Whether the visitor is under test.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 */
	private function is_visitor_tested( $user_id ) {
		$is_visitor_tested = true;

		if ( is_super_admin( $user_id ) ) {
			$is_visitor_tested = false;
		} elseif ( user_can( $user_id, 'edit_nab_experiments' ) ) {
			$is_visitor_tested = false;
		} elseif ( user_can( $user_id, 'read_nab_results' ) ) {
			$is_visitor_tested = false;
		}

		/**
		 * Whether the user related to the current request should be tested or not.
		 *
		 * With this filter, you can decide if the current user participates in your running experiments or not.
		 * By default, all users are tested except those that have (at least) an `editor` role.
		 *
		 * **Notice.** Our plugin uses JavaScript to load alternative content. Be careful when limiting tests
		 * in PHP, as it’s possible that your cache or CDN ends up caching these limitations and, as a result,
		 * none of your visitors are tested.
		 *
		 * @param bool $is_visitor_tested whether the user related to the current request should be tested or not.
		 * @param int  $user_id           ID of the visitor.
		 *
		 * @since 5.0.0
		 * @since 5.0.9 The `$user_id` param has been added.
		 */
		return apply_filters( 'nab_is_visitor_tested', $is_visitor_tested, $user_id );
	}
}
