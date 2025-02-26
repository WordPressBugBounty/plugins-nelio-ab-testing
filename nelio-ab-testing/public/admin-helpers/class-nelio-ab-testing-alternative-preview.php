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

	protected static $instance;

	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	public function init() {

		add_filter( 'body_class', array( $this, 'maybe_add_preview_class' ) );
		add_action( 'nab_public_init', array( $this, 'run_preview_hook_if_preview_mode_is_active' ) );
		add_filter( 'nab_disable_split_testing', array( $this, 'should_split_testing_be_disabled' ) );
		add_filter( 'nab_simulate_anonymous_visitor', array( $this, 'should_simulate_anonymous_visitor' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_add_preview_script' ) );
		add_action( 'wp_head', array( $this, 'maybe_add_overlay' ), 1 );
		add_action( 'wp_footer', array( $this, 'fix_links_in_preview' ), 99 );
	}//end init()

	public function maybe_add_preview_class( $classes ) {
		if ( ! nab_is_preview() ) {
			return $classes;
		}//end if

		$exp_id  = $this->get_experiment_id();
		$alt_idx = $this->get_alternative_index();

		$exp = nab_get_experiment( $exp_id );
		if ( ! empty( $exp ) && false !== $alt_idx ) {
			$classes[] = 'nab';
			$classes[] = "nab-{$alt_idx}";
		}//end if

		$classes[] = 'nab-preview';
		return array_values( array_unique( $classes ) );
	}//end maybe_add_preview_class()

	public function should_split_testing_be_disabled( $disabled ) {

		if ( nab_is_preview() ) {
			return true;
		}//end if

		return $disabled;
	}//end should_split_testing_be_disabled()

	public function should_simulate_anonymous_visitor( $anonymize ) {

		if ( nab_is_preview() ) {
			return true;
		}//end if

		return $anonymize;
	}//end should_simulate_anonymous_visitor()

	public function run_preview_hook_if_preview_mode_is_active() {

		if ( ! nab_is_preview() ) {
			return;
		}//end if

		if ( ! $this->is_preview_mode_valid() ) {
			wp_die( esc_html_x( 'Preview link expired.', 'text', 'nelio-ab-testing' ), 400 );
		}//end if

		$experiment_id = $this->get_experiment_id();

		$experiment = nab_get_experiment( $experiment_id );
		if ( empty( $experiment ) ) {
			return;
		}//end if

		$alt_idx = $this->get_alternative_index();
		if ( 'finished' === $experiment->get_status() && 0 === $alt_idx ) {
			$alternative = $experiment->get_alternative( 'control_backup' );
		} else {
			$alternative = nab_array_get( $experiment->get_alternatives(), $alt_idx, false );
		}//end if

		if ( empty( $alternative ) ) {
			return;
		}//end if

		$control         = $experiment->get_alternative( 'control' );
		$experiment_type = $experiment->get_type();
		$alternative_id  = 'control_backup' === $alternative['id'] ? 'control' : $alternative['id'];

		/**
		 * Fires when a certain alternative is about to be previewed.
		 *
		 * Use this action to add any hooks that your experiment type might require in order
		 * to properly visualize the alternative.
		 *
		 * @param array  $alternative    attributes of the active alternative.
		 * @param array  $control        attributes of the control version.
		 * @param int    $experiment_id  experiment ID.
		 * @param string $alternative_id alternative ID.
		 *
		 * @since 5.0.0
		 */
		do_action( "nab_{$experiment_type}_preview_alternative", $alternative['attributes'], $control['attributes'], $experiment_id, $alternative_id );
	}//end run_preview_hook_if_preview_mode_is_active()

	public function maybe_add_overlay() {
		if ( ! nab_is_preview() ) {
			return;
		}//end if
		nab_print_loading_overlay();
	}//end maybe_add_overlay()

	public function maybe_add_preview_script() {
		if ( ! nab_is_preview() ) {
			return;
		}//end if

		$experiment_id = $this->get_experiment_id();
		$alt_idx       = $this->get_alternative_index();
		$experiment    = nab_get_experiment( $experiment_id );
		$summary       = $experiment->summarize( true );
		$summary       = array_merge(
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
	}//end maybe_add_preview_script()

	public function fix_links_in_preview() {

		if ( ! nab_is_preview() || nab_is_heatmap() ) {
			return;
		}//end if

		if ( ! $this->is_preview_mode_valid() ) {
			wp_die( esc_html_x( 'Preview link expired.', 'text', 'nelio-ab-testing' ), 400 );
		}//end if

		$experiment_id = $this->get_experiment_id();
		$experiment    = nab_get_experiment( $experiment_id );
		if ( empty( $experiment ) ) {
			return;
		}//end if

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
	}//end fix_links_in_preview()

	private function is_preview_mode_valid() {

		$experiment_id = $this->get_experiment_id();
		$alt_idx       = $this->get_alternative_index();
		$timestamp     = $this->get_timestamp();
		$nonce         = $this->get_nonce();
		$secret        = nab_get_api_secret();

		if ( md5( "nab-preview-{$experiment_id}-{$alt_idx}-{$timestamp}-{$secret}" ) !== $nonce ) {
			return false;
		}//end if

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
		}//end if

		return true;
	}//end is_preview_mode_valid()

	private function get_experiment_id() {

		if ( ! isset( $_GET['experiment'] ) ) { // phpcs:ignore
			return false;
		}//end if

		return absint( $_GET['experiment'] ); // phpcs:ignore
	}//end get_experiment_id()

	private function get_alternative_index() {

		if ( ! isset( $_GET['alternative'] ) ) { // phpcs:ignore
			return false;
		}//end if

		if ( ! is_numeric( $_GET['alternative'] ) ) { // phpcs:ignore
			return false;
		}//end if

		return absint( $_GET['alternative'] ); // phpcs:ignore
	}//end get_alternative_index()

	private function get_timestamp() {

		if ( ! isset( $_GET['timestamp'] ) ) { // phpcs:ignore
			return false;
		}//end if

		return absint( $_GET['timestamp'] ); // phpcs:ignore
	}//end get_timestamp()

	private function get_nonce() {

		if ( ! isset( $_GET['nabnonce'] ) ) { // phpcs:ignore
			return false;
		}//end if

		return sanitize_text_field( $_GET['nabnonce'] ); // phpcs:ignore
	}//end get_nonce()

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
		 * @param array $args The arguments that should be added in URL to allow preview browsing.
		 *
		 * @since 6.0.9
		 */
		$args = apply_filters( 'nab_preview_browsing_args', $args );

		?>
		<script type="text/javascript">
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
					a.style.cursor = 'not-allowed';
					a.href = 'javascript:void(0);';
				} else {
					a.href = previewUrl;
				}
			} );
		</script>
		<?php
	}//end enable_preview_browsing()
}//end class
