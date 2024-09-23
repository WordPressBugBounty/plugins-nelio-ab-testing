<?php
/**
 * Displays the UI for configuring the plugin.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/views
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="experiment-results">

	<h1 class="screen-reader-text hide-if-no-js"><?php echo esc_html( $title ); ?></h1>
	<div id="results" class="experiment-results__container hide-if-no-js"></div>

	<div class="wrap hide-if-js experiment-results-no-js">
		<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>
		<div class="notice notice-error notice-alt">
			<p>
			<?php
				echo esc_html_x( 'The test results page requires JavaScript. Please enable JavaScript in your browser settings.', 'user', 'nelio-ab-testing' );
			?>
			</p>
		</div><!-- .notice -->
	</div><!-- .experiment-results-no-js -->

</div><!-- .experiment-results -->

