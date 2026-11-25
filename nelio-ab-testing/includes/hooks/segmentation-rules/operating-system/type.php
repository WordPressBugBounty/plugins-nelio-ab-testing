<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Operating_System;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_segmentation_rule_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the operating system segmentation rule type definition.
 *
 * @param array<string,TSegmentation_Rule_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TSegmentation_Rule_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/operating-system'] = array(
		'name'        => 'nab/operating-system',
		'title'       => _x( 'Operating System', 'text', 'nelio-ab-testing' ),
		'description' => _x( 'Segments visitors according to their operating system.', 'text', 'nelio-ab-testing' ),
		'category'    => 'nab/technology',
		'singleton'   => true,
		'defaults'    => array(
			'condition' => 'is-equal-to',
			'values'    => array(),
		),
		'options'     => array(
			'Android'       => 'Android',
			'Chromium OS'   => 'Chromium OS',
			'Debian'        => 'Debian',
			'Linux'         => 'Linux',
			'Mint'          => 'Mint',
			'SUSE'          => 'SUSE',
			'Ubuntu'        => 'Ubuntu',
			'iOS'           => 'iOS',
			'Mac OS X'      => 'Mac OS X',
			'Windows Phone' => 'Windows Phone',
			'Windows 7'     => 'Windows 7',
			'Windows 11'    => 'Windows 11',
			'Windows Vista' => 'Windows Vista',
			'Windows XP'    => 'Windows XP',
			'Windows'       => 'Windows',
		),
	);
	return $types;
}
