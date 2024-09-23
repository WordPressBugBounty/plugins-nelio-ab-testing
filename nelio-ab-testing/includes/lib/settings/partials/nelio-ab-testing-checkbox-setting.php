<?php
/**
 * Displays a checkbox setting.
 *
 * See the class `Nelio_AB_Testing_Checkbox_Setting`.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings/partials
 * @since      5.0.0
 */

/**
 * List of vars used in this partial:
 *
 * @var string  $id      The identifier of this field.
 * @var string  $name    The name of this field.
 * @var boolean $checked Whether this checkbox is selected or not.
 * @var string  $desc    Optional. The description of this field.
 * @var string  $more    Optional. A link with more information about this field.
 */

?>

<p
<?php
if ( $disabled ) {
	echo 'style="opacity:0.6"';
}//end if
?>
><input
	type="checkbox"
	id="<?php echo esc_attr( $id ); ?>"
	name="<?php echo esc_attr( $name ); ?>"
	<?php disabled( $disabled ); ?>
	<?php checked( $checked ); ?> />
<?php
$this->print_html( $desc ); // @codingStandardsIgnoreLine
if ( ! empty( $more ) ) {
	?>
	<span class="description"><a href="<?php echo esc_url( $more ); ?>">
	<?php
		echo esc_html_x( 'Read more…', 'user', 'nelio-ab-testing' );
	?>
	</a></span>
	<?php
}//end if
?>
</p>
