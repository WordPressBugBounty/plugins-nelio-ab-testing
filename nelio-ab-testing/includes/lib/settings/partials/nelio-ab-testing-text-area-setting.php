<?php
/**
 * Displays an text area setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings/partials
 * @since      5.0.0
 */

/**
 * List of vars used in this partial:
 *
 * @var string  $id          The identifier of this field.
 * @var string  $name        The name of this field.
 * @var string  $value       The concrete value of this field (or an empty string).
 * @var string  $placeholder Optional. A default placeholder.
 * @var string  $desc        Optional. The description of this field.
 * @var string  $more        Optional. A link with more information about this field.
 */

?>

<textarea id="<?php echo esc_attr( $id ); ?>" cols="40" rows="4" placeholder="<?php echo esc_attr( $placeholder ); ?>" <?php disabled( $disabled ); ?> name="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $value ); ?></textarea>

<?php
if ( ! empty( $desc ) ) {
	?>
	<div class="setting-help" style="display:none;">
		<p
			<?php
			if ( $disabled ) {
				echo 'style="opacity:0.6"';
			}//end if
			?>
		><span class="description">
			<?php
			$this->print_html( $desc );
			if ( ! empty( $more ) ) {
				?>
				<a href="<?php echo esc_url( $more ); ?>"><?php echo esc_html_x( 'Read more…', 'user', 'nelio-ab-testing' ); ?></a>
				<?php
			}//end if
			?>
		</span></p>
	</div>
	<?php
}//end if
?>
