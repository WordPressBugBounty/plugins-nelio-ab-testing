<?php
/**
 * This class checks if there's a special parameter in the URL that tells
 * WordPress that an alternative should be previewed. If it exists, then a
 * special filter runs so that the associated experiment type can add the
 * expected hooks to show the variant.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public/admin-helpers
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds the required script for previewing CSS snippets.
 */
class Nelio_AB_Testing_Alternative_Preview {

	/**
	 * This instance.
	 *
	 * @var Nelio_AB_Testing_Alternative_Preview|null
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Alternative_Preview
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

		add_filter( 'body_class', array( $this, 'maybe_add_preview_class' ) );
		add_action( 'nab_public_init', array( $this, 'run_preview_hook_if_preview_mode_is_active' ) );
		add_filter( 'nab_disable_split_testing', array( $this, 'should_split_testing_be_disabled' ) );
		add_filter( 'nab_simulate_anonymous_visitor', array( $this, 'should_simulate_anonymous_visitor' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_add_preview_script' ) );
		add_action( 'wp_head', array( $this, 'maybe_add_overlay' ), 1 );
		add_action( 'wp_footer', array( $this, 'fix_links_in_preview' ), 99 );
	}

	/**
	 * Callback to add an additional `nab-preview` class in the body when previewing variants.
	 *
	 * @param list<string> $classes List of classes.
	 *
	 * @return list<string>
	 */
	public function maybe_add_preview_class( $classes ) {
		if ( ! nab_is_preview() ) {
			return $classes;
		}

		$exp_id  = $this->get_experiment_id();
		$alt_idx = $this->get_alternative_index();

		$exp = nab_get_experiment( $exp_id );
		if ( ! is_wp_error( $exp ) ) {
			$classes[] = 'nab';
			$classes[] = "nab-{$alt_idx}";
		}

		$classes[] = 'nab-preview';
		return array_values( array_unique( $classes ) );
	}

	/**
	 * Callback to disable split testing in preview.
	 *
	 * @param bool $disabled Whether split testing is disabled or not.
	 *
	 * @return bool
	 */
	public function should_split_testing_be_disabled( $disabled ) {

		if ( nab_is_preview() ) {
			return true;
		}

		return $disabled;
	}

	/**
	 * Callback to set current visitor to none when needed.
	 *
	 * @param bool $anonymize Whether the current user is anonymous or not.
	 *
	 * @return bool
	 */
	public function should_simulate_anonymous_visitor( $anonymize ) {

		if ( nab_is_preview() ) {
			return true;
		}

		return $anonymize;
	}

	/**
	 * Callback to run preview hook during preview.
	 *
	 * @return void
	 */
	public function run_preview_hook_if_preview_mode_is_active() {

		if ( ! nab_is_preview() ) {
			return;
		}

		if ( ! $this->is_preview_mode_valid() ) {
			wp_die( esc_html_x( 'Preview link expired.', 'text', 'nelio-ab-testing' ), 400 );
		}

		$experiment_id = $this->get_experiment_id();

		$experiment = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return;
		}

		$alt_idx = $this->get_alternative_index();
		if ( 'finished' === $experiment->get_status() && 0 === $alt_idx ) {
			$alternative = $experiment->get_alternative( 'control_backup' );
		} else {
			$alternative = $experiment->get_alternatives()[ $alt_idx ] ?? false;
		}

		if ( empty( $alternative ) ) {
			return;
		}

		$control         = $experiment->get_alternative( 'control' );
		$experiment_type = $experiment->get_type();
		$alternative_id  = 'control_backup' === $alternative['id'] ? 'control' : $alternative['id'];

		/**
		 * Fires when a certain alternative is about to be previewed.
		 *
		 * Use this action to add any hooks that your experiment type might require in order
		 * to properly visualize the alternative.
		 *
		 * @param TAlternative_Attributes|TControl_Attributes $alternative    attributes of the active alternative.
		 * @param TControl_Attributes                         $control        attributes of the control version.
		 * @param int                                         $experiment_id  experiment ID.
		 * @param string                                      $alternative_id alternative ID.
		 *
		 * @since 5.0.0
		 */
		do_action( "nab_{$experiment_type}_preview_alternative", $alternative['attributes'], $control['attributes'], $experiment_id, $alternative_id );
	}

	/**
	 * Callback to add loading overlay in preview.
	 *
	 * @return void
	 */
	public function maybe_add_overlay() {
		if ( ! nab_is_preview() ) {
			return;
		}
		nab_print_loading_overlay();
	}

	/**
	 * Callback to add preview scripts in preview.
	 *
	 * @return void
	 */
	public function maybe_add_preview_script() {
		if ( ! nab_is_preview() ) {
			return;
		}

		$experiment_id = $this->get_experiment_id();
		$alt_idx       = $this->get_alternative_index();
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return;
		}

		$summary = $experiment->summarize( true );
		$summary = array_merge(
			$summary,
			array( 'alternative' => $alt_idx )
		);

		nab_enqueue_script_with_auto_deps(
			'nelio-ab-testing-experiment-previewer',
			'experiment-previewer',
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		wp_add_inline_script(
			'nelio-ab-testing-experiment-previewer',
			sprintf( 'window.nabExperiment=%s;', wp_json_encode( $summary ) ),
			'before'
		);
	}

	/**
	 * Callback to disable links in preview if `nab_is_preview_browsing_enabled` says so.
	 *
	 * @return void
	 */
	public function fix_links_in_preview() {

		if ( ! nab_is_preview() || nab_is_heatmap() ) {
			return;
		}

		if ( ! $this->is_preview_mode_valid() ) {
			wp_die( esc_html_x( 'Preview link expired.', 'text', 'nelio-ab-testing' ), 400 );
		}

		$experiment_id = $this->get_experiment_id();
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return;
		}

		$experiment_type = $experiment->get_type();

		/**
		 * Filters whether user should be able to browse site on preview mode or not.
		 *
		 * @param boolean $is_browsing_enabled Whether site browsing is enabled on preview mode. Default: `false`.
		 * @param string  $experiment_type     Type of the experiment.
		 *
		 * @since 6.0.9
		 */
		$is_enabled = apply_filters( 'nab_is_preview_browsing_enabled', false, $experiment_type );

		$this->enable_preview_browsing( $is_enabled );
	}

	/**
	 * Whether the preview URL is still valid and, therefore, preview should be accessible.
	 *
	 * @return bool
	 */
	private function is_preview_mode_valid() {

		$experiment_id = $this->get_experiment_id();
		$alt_idx       = $this->get_alternative_index();
		$timestamp     = $this->get_timestamp();
		$nonce         = $this->get_nonce();
		$secret        = nab_get_api_secret();

		if ( md5( "nab-preview-{$experiment_id}-{$alt_idx}-{$timestamp}-{$secret}" ) !== $nonce ) {
			return false;
		}

		/**
		 * Filters the alternative preview duration in minutes. If set to 0, the preview link never expires.
		 *
		 * @param number $duration Duration in minutes. If 0, the preview link never expires. Default: 30.
		 *
		 * @since 5.1.2
		 */
		$duration = absint( apply_filters( 'nab_alternative_preview_link_duration', 30 ) );
		if ( ! empty( $duration ) && 60 * $duration < absint( time() - $timestamp ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Gets current experiment from global `$_GET` variable.
	 *
	 * @return int
	 */
	private function get_experiment_id() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['experiment'] ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return absint( $_GET['experiment'] );
	}

	/**
	 * Gets current alternative index from global `$_GET` variable.
	 *
	 * @return int
	 */
	private function get_alternative_index() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['alternative'] ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return absint( $_GET['alternative'] );
	}

	/**
	 * Gets timestamp from `$_GET` variable.
	 *
	 * @return int
	 */
	private function get_timestamp() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['timestamp'] ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return absint( $_GET['timestamp'] );
	}

	/**
	 * Returns nonce from `$_GET` variable.
	 *
	 * @return string
	 */
	private function get_nonce() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return sanitize_text_field( wp_unslash( $_GET['nabnonce'] ?? '' ) );
	}

	/**
	 * Prints inline script that tweaks internal links to either allow browsing (adding required args) or disables browsing.
	 *
	 * @param bool $is_enabled Whether browsing is enabled or not.
	 *
	 * @return void
	 */
	private function enable_preview_browsing( $is_enabled ) {

		$args = array(
			'nab-preview' => 1,
			'experiment'  => $this->get_experiment_id(),
			'alternative' => $this->get_alternative_index(),
			'timestamp'   => $this->get_timestamp(),
			'nabnonce'    => $this->get_nonce(),
		);

		/**
		 * Filters the arguments that should be added in URL to allow preview browsing.
		 *
		 * @param array<string,mixed> $args The arguments that should be added in URL to allow preview browsing.
		 *
		 * @since 6.0.9
		 */
		$args = apply_filters( 'nab_preview_browsing_args', $args );

		?>
		<script type="text/javascript">
		(function() {
		[ ...document.querySelectorAll( 'a' ) ]
			.filter( ( a ) => !! a.href )
			.filter( ( a ) => /^\//.test( a.href ) || /^https?:\/\//.test( a.href ) )
			.filter( ( a ) => ! /\.(gif|png|jpe?g|webp|bmp)\b/.test( a.href.toLowerCase() ) )
			.filter( ( a ) => ! a.href.includes( '#' ) )
			.forEach( ( a ) => {
				const args = <?php echo wp_json_encode( $args ); ?>;
				let previewUrl = new URL( a.href, document.location.href );
				Object.keys( args ).forEach( ( name ) => {
					previewUrl.searchParams.set( name, args[ name ] );
				} );
				previewUrl = previewUrl.href;
				a.dataset.previewUrl = previewUrl;
				a.dataset.url = a.href;

				const safeUrl = ( a.href ?? '' ).replace( /^https?:\/\//, 'https://' );
				const homeUrl = <?php echo wp_json_encode( str_replace( 'http://', 'https://', home_url( '/' ) ) ); ?>;
				if ( ! <?php echo wp_json_encode( $is_enabled ); ?> || ! safeUrl || ! safeUrl.startsWith( homeUrl ) ) {
					a.className += ' nab-disabled-link';
					a.style.cursor = 'not-allowed';
					a.href = 'javascript:void(0);';
				} else {
					a.href = previewUrl;
				}
			} );

		const tooltip = document.createElement( 'div' );
		const style = {
			backdropFilter: 'blur(4px)',
			background: '#000c',
			border: '2px solid #fff',
			color: '#fff',
			display: 'none',
			fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
			fontSize: '12px',
			fontWeight: 'normal',
			left: '0px',
			padding: '.5em 1em',
			position: 'fixed',
			top: '0px',
			zIndex: '9999999',
		};
		tooltip.className = "nab-disabled-link-tooltip";
		tooltip.textContent = <?php echo wp_json_encode( _x( 'Link disabled during preview', 'text', 'nelio-ab-testing' ) ); ?>;
		Object.keys( style ).forEach( ( k ) => {
				tooltip.style[ k ] = style[ k ];
		} );
		document.body.append( tooltip );
		document.addEventListener( 'mousemove', ( ev )=> {
			if ( ! ev.target?.closest( '.nab-disabled-link' ) ) {
				tooltip.style.display = 'none';
				return;
			}
			const x = ev.clientX;
			const y = ev.clientY;
			const w = window.innerWidth;
			const h = window.innerHeight;

			tooltip.style.left = x <= w * 0.8 ? `${ x }px` : '';
			tooltip.style.right = x > w * 0.8 ? `${ w - x }px` : '';
			tooltip.style.top = y <= h * 0.2 ? `${ y }px` : '';
			tooltip.style.bottom = y > h * 0.2 ? `${ h - y }px` : '';

			tooltip.style.transform = ( w * 0.2 <= x && x <= w * 0.8 ? 'translateX(-50%)' : '' ) +
				(y <= h * 0.2 ? ' translateY(90%)' : ' translateY(-90%)');

			tooltip.style.display = 'block';
		} );
		})();
		</script>
		<?php
	}
}
