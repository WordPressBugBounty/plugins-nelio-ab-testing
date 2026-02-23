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
 * @var Nelio_AB_Testing_Abstract_Setting $this This setting.
 *
 * @var string  $id       The identifier of this field.
 * @var string  $name     The name of this field.
 * @var boolean $disabled Whether this select is disabled or not.
 * @var list<array{value:string, label:string, desc?: string, disabled?:bool}> $options The list of options.
 * @var string  $value    The concrete value of this field (or an empty string).
 * @var string  $desc     Optional. The description of this field.
 * @var string  $more     Optional. A link with more information about this field.
 */

?>

<div id="<?php echo esc_attr( "{$id}-wrapper" ); ?>">
	<select
		id="<?php echo esc_attr( $id ); ?>"
		name="<?php echo esc_attr( $name ); ?>"
		<?php disabled( $disabled ); ?>
	>

		<?php
		foreach ( $options as $nab_option ) {
			?>
			<option value="<?php echo esc_attr( $nab_option['value'] ); ?>"
				<?php
				if ( $nab_option['value'] === $value ) {
					echo ' selected="selected"';
				}
				?>
				<?php
				if ( ! empty( $nab_option['disabled'] ) ) {
					echo ' disabled';
				}
				?>
			>
			<?php $this->print_html( $nab_option['label'] ); ?>
		</option>
			<?php
		}
		?>

	</select>
</div>

<script>
(function() {
	try {
		const wrapper = wp.element.createRoot( document.getElementById( <?php echo wp_json_encode( "{$id}-wrapper" ); ?> ) );
		wrapper.render(
			wp.element.createElement(
				wp.components.SelectControl,
				<?php
					echo wp_json_encode(
						array(
							'id'                      => $id,
							'name'                    => $name,
							'options'                 => array_map(
								fn( $option ) => array(
									'label'    => wp_kses( $option['label'], array() ),
									'value'    => $option['value'],
									'disabled' => ! empty( $option['disabled'] ) ? true : null,
								),
								$options
							),
							'defaultValue'            => $value,
							'disabled'                => $disabled,
							'__next40pxDefaultSize'   => true,
							'__nextHasNoMarginBottom' => true,
						)
					);
					?>
			)
		);
	} catch( _ ) { }
})();
</script>

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
