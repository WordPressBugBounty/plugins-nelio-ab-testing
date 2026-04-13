<?php
/**
 * This file contains the setting for alternative loading.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      7.0.0
 */

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * This class represents the setting for alternative loading.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/settings
 * @since      7.0.0
 */
class Nelio_AB_Testing_Alternative_Loading_Setting extends Nelio_AB_Testing_Abstract_React_Setting {

	public function __construct( $name ) {
		parent::__construct(
			$name,
			Z::object(
				array(
					'mode'                      => Z::enum( array( 'redirection', 'cookie' ) )->catch( 'redirection' ),
					'lockParticipationSettings' => Z::boolean()->catch( false ),
					'redirectIfCookieIsMissing' => Z::boolean()->catch( false ),
				)
			)->catch(
				array(
					'mode'                      => 'redirection',
					'lockParticipationSettings' => false,
					'redirectIfCookieIsMissing' => false,
				)
			),
			'AlternativeLoadingSetting'
		);
	}
}
