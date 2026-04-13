<?php

defined( 'ABSPATH' ) || exit;

/**
 * List of vars used in this partial:
 *
 * @var string $title Title of the page.
 */
?>

<div class="nab-welcome wrap">

	<h1 class="wp-heading-inline screen-reader-text"><?php echo esc_html( $title ); ?></h1>
	<div class="notice notice-error notice-alt hide-if-js">
		<p>
		<?php
			echo esc_html_x( 'This page requires JavaScript. Please enable JavaScript in your browser settings.', 'user', 'nelio-ab-testing' );
		?>
		</p>
	</div><!-- .notice -->

	<div id="nab-welcome" class="nab-welcome-container hide-if-no-js"></div>

</div><!-- .nab-welcome -->

