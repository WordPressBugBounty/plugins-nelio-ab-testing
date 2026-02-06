<?php
/**
 * Displays a radio setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings/partials
 * @since      5.0.0
 */

/**
 * List of vars used in this partial:
 *
 * @var Nelio_AB_Testing_Abstract_Setting $this This setting.
 *
 * @var list<array{value:string, label:string, desc?:string}> $options The list of options.
 * @var string   $name     The name of this field.
 * @var boolean  $disabled Whether this radio is disabled or not.
 * @var string   $value    The concrete value of this field (or an empty string).
 * @var string   $desc     Optional. The description of this field.
 * @var string   $more     Optional. A link with more information about this field.
 */

?>

<?php
foreach ( $options as $nab_option ) {
	?>
	<p
		<?php
		if ( $disabled ) {
			echo 'style="opacity:0.6"';
		}
		?>
	><input type="radio"
		name="<?php echo esc_attr( $name ); ?>"
		value="<?php echo esc_attr( $nab_option['value'] ); ?>"
		<?php disabled( $disabled ); ?>
		<?php checked( $nab_option['value'] === $value ); ?> />
		<?php
			$this->print_html( $nab_option['label'] );
		?>
	</p>
	<?php
}
?>

<?php
$nab_described_options = array();
foreach ( $options as $nab_option ) {
	if ( isset( $nab_option['desc'] ) ) {
		array_push( $nab_described_options, $nab_option );
	}
}

if ( ! empty( $desc ) ) {
	?>
	<div class="setting-help" style="display:none;">
	<p
		<?php
		if ( $disabled ) {
			echo 'style="opacity:0.6"';
		}
		?>
	><span class="description">
		<?php
		$this->print_html( $desc );
		if ( ! empty( $more ) ) {
			?>
			<a href="<?php echo esc_url( $more ); ?>"><?php echo esc_html_x( 'Read more…', 'user', 'nelio-ab-testing' ); ?></a>
			<?php
		}
		?>
		</span></p>

		<?php
		if ( count( $nab_described_options ) > 0 ) {
			?>
			<ul
				style="list-style-type:disc;margin-left:3em;"
				<?php
				if ( $disabled ) {
					echo 'style="opacity:0.6"';
				}
				?>
			>
				<?php
				foreach ( $nab_described_options as $nab_option ) {
					?>
					<li><span class="description">
						<strong><?php $this->print_html( $nab_option['label'] ); ?>.</strong>
						<?php $this->print_html( $nab_option['desc'] ); ?>
					</span></li>
					<?php
				}
				?>
			</ul>
			<?php
		}
		?>

	</div>
	<?php
}
?>
