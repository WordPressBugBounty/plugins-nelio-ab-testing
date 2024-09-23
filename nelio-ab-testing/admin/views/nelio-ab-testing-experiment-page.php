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

<div class="experiment-editor">

	<h1 class="screen-reader-text hide-if-no-js"><?php echo esc_html( $title ); ?></h1>
	<div id="nab-editor" class="experiment-editor__container hide-if-no-js"></div>

	<div class="wrap hide-if-js experiment-editor-no-js">
		<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>
		<div class="notice notice-error notice-alt">
			<p>
			<?php
				echo esc_html_x( 'The test editor requires JavaScript. Please enable JavaScript in your browser settings.', 'user', 'nelio-ab-testing' );
			?>
			</p>
		</div><!-- .notice -->
	</div><!-- .experiment-editor-no-js -->

</div><!-- .experiment-editor -->

