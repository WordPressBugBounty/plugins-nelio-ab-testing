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
	class="nab-modern-checkbox"
><span
	class="components-checkbox-control__input-container"
><input
	type="checkbox"
	id="<?php echo 'nab-' . esc_attr( $id ); ?>"
	name="<?php echo esc_attr( $name ); ?>"
	class="components-checkbox-control__input"
	<?php disabled( $disabled ); ?>
	<?php checked( $checked ); ?>
><svg
	xmlns="http://www.w3.org/2000/svg"
	viewBox="0 0 24 24"
	width="24"
	height="24"
	role="presentation"
	class="components-checkbox-control__checked"
	aria-hidden="true"
	focusable="false">
	<path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path>
</svg></span>
<?php
printf( '<label for="nab-%s">', esc_attr( $id ) );
$this->print_html( $desc ); // @codingStandardsIgnoreLine
echo '</label>';
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
