<?php
/**
 * Prints the list of tabs and highlights the first one.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/lib/settings/partials
 * @since      5.0.0
 */

/**
 * List of required vars:
 *
 * @var list<array{name:string,label:string}> $tabs the list of tabs.
 * @var string  $opened_tab the name of the currently-opened tab.
 */

?>

<h2 class="nav-tab-wrapper">
<?php
foreach ( $tabs as $nab_current_tab ) {
	if ( $nab_current_tab['name'] === $opened_tab ) {
		$nab_active = ' nav-tab-active';
	} else {
		$nab_active = '';
	}
	printf(
		'<a id="%1$s" class="nav-tab%3$s" href="#">%2$s</a>',
		esc_attr( $nab_current_tab['name'] ),
		esc_html( $nab_current_tab['label'] ),
		esc_attr( $nab_active )
	);
}
?>
</h2>
