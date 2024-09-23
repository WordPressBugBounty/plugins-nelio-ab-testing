<?php
/**
 * Displays a simple UI for debugging an experiment.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/views
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="experiment-debug wrap">

	<h1 class="wp-heading-inline"><?php echo esc_html_x( 'Test Debug', 'text', 'nelio-ab-testing' ); ?></h1>

	<?php
	if ( is_wp_error( $experiment ) ) {
		printf(
			'<h2>%s</h2><p><a class="button" href="%s">%s</a></p>',
			/* translators: experiment ID */
			sprintf( esc_html_x( 'Test “%d” not found.', 'text', 'nelio-ab-testing' ), esc_html( $experiment_id ) ),
			esc_url( admin_url( 'admin.php?page=nelio-ab-testing' ) ),
			esc_html_x( 'Back to Overview', 'command', 'nelio-ab-testing' )
		);
		return;
	}//end if
	?>

	<div>
		<textarea id="experiment-debug-data" readonly style="background:#fcfcfc; border:1px solid grey; width:100%; overflow:auto; height:calc(100vh - 18em ); min-height: 30em; padding:1em; font-family:monospace; white-space:pre;">
			<?php
			$aux = Nelio_AB_Testing_Experiment_REST_Controller::instance();
			echo 'test = ' . wp_json_encode( $aux->json( $experiment ), JSON_PRETTY_PRINT ) . ';';
			?>
		</textarea>
	</div>

</div><!-- .experiment-debug -->
