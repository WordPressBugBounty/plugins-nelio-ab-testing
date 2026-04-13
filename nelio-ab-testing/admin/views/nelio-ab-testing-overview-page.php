<?php

defined( 'ABSPATH' ) || exit;

/**
 * List of vars used in this partial:
 *
 * @var string $title Title of the page.
 */
?>

<div class="nab-experiment-overview wrap">

	<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>

	<span id="nab-overview-title-action"></span>

	<div class="notice notice-error notice-alt hide-if-js">
		<p>
		<?php
			echo esc_html_x( 'The overview requires JavaScript. Please enable JavaScript in your browser settings.', 'user', 'nelio-ab-testing' );
		?>
		</p>
	</div><!-- .notice -->

	<div id="nab-overview" class="experiment-overview__container hide-if-no-js"></div>

</div><!-- .experiment-overview -->

