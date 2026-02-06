<?php

namespace Nelio_AB_Testing\Compat\User_Role_Editor;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Adds Nelio A/B Testing group in User Role Editor plugin.
 *
 * @param array<string,mixed> $groups List of groups.
 *
 * @return array<string,mixed> List of groups with Nelio A/B Testing group.
 *
 * @since 8.1.2
 */
function add_ure_group( $groups ) {
	$groups['nelio_ab_testing'] = array(
		'caption' => 'Nelio A/B Testing',
		'parent'  => 'custom',
		'level'   => 2,
	);
	return $groups;
}
add_filter( 'ure_capabilities_groups_tree', __NAMESPACE__ . '\add_ure_group' );

/**
 * Adds Nelio A/B Testing capabilities in our own group in User Role Editor plugin.
 *
 * @param list<string> $groups      List of groups.
 * @param string       $capability Capability ID.
 *
 * @return list<string> List of groups where the given capability belongs to.
 *
 * @since 8.1.2
 */
function add_nab_capabilities_to_ure_group( $groups, $capability ) {
	if ( false !== strpos( $capability, '_nab_' ) ) {
		$groups[] = 'nelio_ab_testing';
	}
	return $groups;
}
add_filter( 'ure_custom_capability_groups', __NAMESPACE__ . '\add_nab_capabilities_to_ure_group', 10, 2 );
