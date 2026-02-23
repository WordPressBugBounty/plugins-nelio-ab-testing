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
 * @var Nelio_AB_Testing_Abstract_Setting $this This setting.
 *
 * @var string  $id       The identifier of this field.
 * @var string  $name     The name of this field.
 * @var boolean $disabled Whether this checkbox is disabled or not.
 * @var boolean $checked  Whether this checkbox is selected or not.
 * @var string  $desc     Optional. The description of this field.
 * @var string  $more     Optional. A link with more information about this field.
 */

?>

<div id="<?php echo esc_attr( "{$id}-wrapper" ); ?>">
	<p><input
		type="checkbox"
		id="<?php echo esc_attr( "{$id}" ); ?>"
		name="<?php echo esc_attr( $name ); ?>"
		<?php disabled( $disabled ); ?>
		<?php checked( $checked ); ?>
	>
	<?php
	printf( '<label for="%s">', esc_attr( $id ) );
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
	}
	?>
	</p>
</div>

<script>
(function() {
	try {
		const UncontrolledCheckbox = ( { defaultValue, label, more, ...props } ) => {
			const [ checked, onChange ] = wp.element.useState( !! defaultValue );

			more = more
				? wp.element.createElement( wp.components.ExternalLink, {
						href: more,
						children: [ <?php echo wp_json_encode( _x( 'Read more…', 'user', 'nelio-ab-testing' ) ); ?> ],
					} )
				: wp.element.createElement( wp.element.Fragment, {} );

			label = wp.element.createInterpolateElement(
				`${ label } <more />`,
				{
					'code': wp.element.createElement( 'code', {} ),
					'strong': wp.element.createElement( 'strong', {} ),
					'more': more,
				}
			);

			return wp.element.createElement(
				wp.components.CheckboxControl,
				{ ...props, checked, label, onChange }
			);
		};
		const wrapper = wp.element.createRoot( document.getElementById( <?php echo wp_json_encode( "{$id}-wrapper" ); ?> ) );
		wrapper.render(
			wp.element.createElement(
				UncontrolledCheckbox,
				<?php
					echo wp_json_encode(
						array(
							'id'                      => $id,
							'name'                    => $name,
							'label'                   => wp_kses(
								$desc,
								array(
									'code'   => array(),
									'strong' => array(),
								)
							),
							'defaultValue'            => $checked,
							'disabled'                => $disabled,
							'more'                    => $more,
							'__nextHasNoMarginBottom' => true,
						)
					);
					?>
			)
		);
	} catch( _ ) { }
})();
</script>
