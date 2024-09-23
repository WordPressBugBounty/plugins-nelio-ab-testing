<?php
/**
 * Welcome page.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/views
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="welcome wrap">

	<h1 class="wp-heading-inline screen-reader-text"><?php echo esc_html( $title ); ?></h1>
	<div class="notice notice-error notice-alt hide-if-js">
		<p>
		<?php
			echo esc_html_x( 'This page requires JavaScript. Please enable JavaScript in your browser settings.', 'user', 'nelio-ab-testing' );
		?>
		</p>
	</div><!-- .notice -->

	<div id="welcome" class="nab-welcome-container hide-if-no-js"></div>

</div><!-- .welcome -->

