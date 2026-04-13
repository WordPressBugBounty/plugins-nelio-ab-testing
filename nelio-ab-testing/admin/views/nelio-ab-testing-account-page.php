<?php

defined( 'ABSPATH' ) || exit;

/**
 * List of vars used in this partial:
 *
 * @var string $title Title of the page.
 */
?>

<div class="nab-account wrap">

	<div class="notice notice-error notice-alt hide-if-js">
		<p>
		<?php
			echo esc_html_x( 'The account page requires JavaScript. Please enable JavaScript in your browser settings.', 'user', 'nelio-ab-testing' );
		?>
		</p>
	</div><!-- .notice -->

	<div id="nab-account" class="nab-account hide-if-no-js"></div>

</div><!-- .account -->
