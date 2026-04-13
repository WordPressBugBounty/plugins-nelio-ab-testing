<?php

defined( 'ABSPATH' ) || exit;

/**
 * List of vars used in this partial:
 *
 * @var string $title Title of the page.
 */
?>

<div class="experiment-results">

	<h1 class="screen-reader-text hide-if-no-js"><?php echo esc_html( $title ); ?></h1>
	<div id="nab-results" class="experiment-results__container hide-if-no-js"></div>

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

