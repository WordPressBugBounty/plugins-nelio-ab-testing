<?php
/**
 * Displays the UI for configuring the plugin.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/views
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="wrap">

	<h2>
		<?php echo esc_html_x( 'Nelio A/B Testing - Settings', 'text', 'nelio-ab-testing' ); ?>
	</h2>

	<?php settings_errors( 'nelio-ab-testing' ); ?>

	<form method="post" action="options.php" class="nab-settings-form">
		<?php
			$nab_settings = Nelio_AB_Testing_Settings::instance();
			settings_fields( $nab_settings->get_option_group() );
			do_settings_sections( $nab_settings->get_settings_page_name() );
			submit_button();
		?>
	</form>

	<?php
	/**
	 * Fires after the settings form.
	 *
	 * @since 6.4.0
	 */
	do_action( 'nab_settings_screen_after' );
	?>

</div><!-- .wrap -->

