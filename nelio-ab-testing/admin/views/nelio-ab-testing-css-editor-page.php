<?php
/**
 * This template is used for editing and previewing alternative CSS snippets.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/views/partials/pages/css-editor
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

?><!DOCTYPE html>
<html>
	<head>

		<meta name="viewport" content="width=device-width" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php echo esc_html_x( 'Nelio A/B Testing - CSS Editor', 'text', 'nelio-ab-testing' ); ?></title>

		<?php
		do_action( 'admin_enqueue_scripts' );
		print_admin_styles();
		wp_print_head_scripts();
		?>

	</head>

	<body class="wp-core-ui">

		<main id="nab-css-editor" class="hide-if-no-js"></main>
		<?php
			wp_print_footer_scripts();
		?>

	</body>
</html>
