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

<div class="experiment-overview wrap">

	<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>

	<span id="overview-title-action"></span>

	<div class="notice notice-error notice-alt hide-if-js">
		<p>
		<?php
			echo esc_html_x( 'The overview requires JavaScript. Please enable JavaScript in your browser settings.', 'user', 'nelio-ab-testing' );
		?>
		</p>
	</div><!-- .notice -->

	<div id="overview" class="experiment-overview__container hide-if-no-js"></div>

</div><!-- .experiment-overview -->

