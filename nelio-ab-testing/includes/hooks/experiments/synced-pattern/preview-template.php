<html>
	<head>
		<?php wp_head(); ?>
		<style>
			body:before,
			body:after {
				display: none !important;
			}

			body {
				display: flex !important;
				flex-direction: column !important;
				min-height: 90vh !important;
				justify-content: center !important;
			}

			body > .nab-pattern {
				padding: 2em;
			}
		</style>
	</head>
	<body>
		<div class="nab-pattern">
			<?php
			/**
			 * Runs on special template to preview synced patterns.
			 *
			 * @since 7.5.0
			 */
			do_action( 'nab_preview_synced_pattern' );
			?>
		</div>
		<?php wp_footer(); ?>
	</body>
</html>
