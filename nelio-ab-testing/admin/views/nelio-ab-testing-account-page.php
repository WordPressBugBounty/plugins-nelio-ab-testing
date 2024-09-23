<?php
/**
 * Displays the UI for managing the account.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/views
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="account wrap">

	<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>
	<div class="notice notice-error notice-alt hide-if-js">
		<p>
		<?php
			echo esc_html_x( 'The account page requires JavaScript. Please enable JavaScript in your browser settings.', 'user', 'nelio-ab-testing' );
		?>
		</p>
	</div><!-- .notice -->

	<div id="account" class="nab-account hide-if-no-js"></div>

</div><!-- .account -->
