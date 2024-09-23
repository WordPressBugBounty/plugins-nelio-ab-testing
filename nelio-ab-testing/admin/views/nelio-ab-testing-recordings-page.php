<?php
/**
 * Displays the UI for the recordings.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/views
 * @since      6.4.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="recordings wrap">

	<div class="notice notice-error notice-alt hide-if-js">
		<p>
		<?php
			echo esc_html_x( 'The account page requires JavaScript. Please enable JavaScript in your browser settings.', 'user', 'nelio-ab-testing' );
		?>
		</p>
	</div><!-- .notice -->

	<div id="recordings" class="nab-recordings hide-if-no-js"></div>

</div><!-- .recordings -->
