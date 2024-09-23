<?php
/**
 * Displays a select setting.
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
 * @var array   $options The list of options.
 *                       Each of them is an array with its label, description, and so on.
 * @var boolean $value   The concrete value of this field (or an empty string).
 * @var string  $desc    Optional. The description of this field.
 * @var string  $more    Optional. A link with more information about this field.
 */

?>

<select
	id="<?php echo esc_attr( $id ); ?>"
	name="<?php echo esc_attr( $name ); ?>"
	<?php disabled( $disabled ); ?>
>

	<?php
	foreach ( $options as $option ) {
		?>
		<option value="<?php echo esc_attr( $option['value'] ); ?>"
			<?php
			if ( $option['value'] === $value ) {
				echo ' selected="selected"';
			}//end if
			?>
			<?php
			if ( ! empty( $option['disabled'] ) ) {
				echo ' disabled';
			}//end if
			?>
		>
		<?php $this->print_html( $option['label'] ); ?>
	</option>
		<?php
	}//end foreach
	?>

</select>

<?php
$described_options = array();
foreach ( $options as $option ) {
	if ( isset( $option['desc'] ) ) {
		array_push( $described_options, $option );
	}//end if
}//end foreach

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

	<?php
	if ( count( $described_options ) > 0 ) {
		?>
		<ul
			style="list-style-type:disc;margin-left:3em;"
			<?php
			if ( $disabled ) {
				echo 'style="opacity:0.6"';
			}//end if
			?>
		>
			<?php
			foreach ( $described_options as $option ) {
				?>
				<li><span class="description">
					<strong><?php $this->print_html( $option['label'] ); ?>.</strong>
					<?php $this->print_html( $option['desc'] ); ?>
				</span></li>
				<?php
			}//end foreach
			?>
		</ul>
		<?php
	}//end if
	?>

	</div>
	<?php
}//end if
?>
