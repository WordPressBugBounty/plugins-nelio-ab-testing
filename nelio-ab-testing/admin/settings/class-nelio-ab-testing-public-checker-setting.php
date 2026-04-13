<?php
/**
 * This file contains the public checker setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      6.2.0
 */

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * This class represents the public checker setting.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      8.3.0
 */
class Nelio_AB_Testing_Public_Checker_Setting extends Nelio_AB_Testing_Abstract_React_Setting {

	public function __construct( $name ) {
		parent::__construct(
			$name,
			Z::object(
				array(
					'enabled'      => Z::boolean()->catch( true ),
					'includeNames' => Z::boolean()->catch( false ),
				)
			)->catch(
				array(
					'enabled'      => true,
					'includeNames' => false,
				)
			),
			'PublicCheckerSetting'
		);
	}
}
