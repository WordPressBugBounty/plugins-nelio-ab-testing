<?php
/**
 * Displays the UI for rendering public results.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/templates
 * @since      7.1.1
 */

defined( 'ABSPATH' ) || exit;

$aux        = new Nelio_AB_Testing_Results_Page();
$is_heatmap = $aux->is_heatmap_request();
$page_title = $is_heatmap ?
	esc_html_x( 'Nelio A/B Testing - Heatmap Viewer', 'text', 'nelio-ab-testing' ) :
	esc_html_x( 'Nelio A/B Testing - Results', 'text', 'nelio-ab-testing' );
$handle     = $is_heatmap ? 'nab-heatmap-results-page' : 'nab-results-page';

?><!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="width=device-width" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php echo $page_title; // phpcs:ignore ?></title>
		<?php do_action( 'wp_enqueue_scripts' ); ?>
		<?php wp_print_styles( array( $handle ) ); ?>
	</head>

	<body class="wp-core-ui">

	<?php if ( $is_heatmap ) { ?>

		<main id="nab-main" class="hide-if-no-js"></main>

	<?php } else { ?>

		<div class="experiment-results">

			<div id="results" class="experiment-results__container"></div>

		</div><!-- .experiment-results -->

	<?php }//end if ?>

		<?php wp_print_scripts( array( $handle ) ); ?>

	</body>
</html>
