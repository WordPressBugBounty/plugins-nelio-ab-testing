<?php

defined( 'ABSPATH' ) || exit;

?><!DOCTYPE html>
<html>
	<head>

		<meta name="viewport" content="width=device-width" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php echo esc_html_x( 'Nelio A/B Testing - Heatmap Viewer', 'text', 'nelio-ab-testing' ); ?></title>

		<?php
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'admin_enqueue_scripts' );
		print_admin_styles();
		wp_print_head_scripts();
		?>

	</head>

	<body class="wp-core-ui">

		<main id="nab-main" class="hide-if-no-js"></main>
		<?php
		wp_print_footer_scripts();
		?>

	</body>
</html>
