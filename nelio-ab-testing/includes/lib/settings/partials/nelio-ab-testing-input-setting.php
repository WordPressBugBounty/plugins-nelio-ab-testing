<?php
/**
 * Displays an input setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings/partials
 * @since      5.0.0
 */

/**
 * List of vars used in this partial:
 *
 * @var string  $type        The concrete type of this input.
 * @var string  $id          The identifier of this field.
 * @var string  $name        The name of this field.
 * @var boolean $value       The concrete value of this field (or an empty string).
 * @var string  $placeholder Optional. A default placeholder.
 * @var string  $desc        Optional. The description of this field.
 * @var string  $more        Optional. A link with more information about this field.
 */

?>

<p
<?php
if ( $disabled ) {
	echo 'style="opacity:0.6"';
}//end if
?>
><input
	type="<?php echo esc_attr( $type ); ?>"
	id="<?php echo esc_attr( $id ); ?>"
	placeholder="<?php echo esc_attr( $placeholder ); ?>"
	name="<?php echo esc_attr( $name ); ?>"
	<?php disabled( $disabled ); ?>
	<?php
	if ( 'password' === $type ) {
		?>
		onchange="
			document.getElementById('<?php echo esc_attr( $id ); ?>-check').pattern = this.value;
			if ( this.value != '' ) {
				document.getElementById('<?php echo esc_attr( $id ); ?>-check').required = 'required';
			} else {
				document.getElementById('<?php echo esc_attr( $id ); ?>-check').required = undefined;
			}
		"
		<?php
	} else {
		?>
		value="<?php echo esc_attr( $value ); ?>"
		<?php
	}//end if
	?>
	/></p>
<?php
if ( 'password' === $type ) {
	?>
<p
	<?php
	if ( $disabled ) {
		echo 'style="opacity:0.6"';
	}//end if
	?>
><input
	type="<?php echo esc_attr( $type ); ?>"
	id="<?php echo esc_attr( $id ); ?>-check"
	placeholder="<?php echo esc_attr_x( 'Confirm Password…', 'user', 'nelio-ab-testing' ); ?>"
	name="<?php echo esc_attr( $name ); ?>" /></p>
	<?php
}//end if
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
