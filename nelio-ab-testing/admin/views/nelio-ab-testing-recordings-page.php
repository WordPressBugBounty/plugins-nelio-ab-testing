<?php

defined( 'ABSPATH' ) || exit;

?>

<div class="nab-recordings wrap">

	<div class="notice notice-error notice-alt hide-if-js">
		<p>
		<?php
			echo esc_html_x( 'The account page requires JavaScript. Please enable JavaScript in your browser settings.', 'user', 'nelio-ab-testing' );
		?>
		</p>
	</div><!-- .notice -->

	<div id="nab-recordings" class="nab-recordings hide-if-no-js"></div>

</div><!-- .recordings -->
