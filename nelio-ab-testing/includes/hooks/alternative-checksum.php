<?php
namespace Nelio_AB_Testing\Hooks\Alternative_Checksum;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to create alternative session option if none exists.
 *
 * @return void
 */
function maybe_create_alternative_session_option() {
	$value = get_option( 'nab_alt_checksum', md5( nab_uuid() ) );
	update_option( 'nab_alt_checksum', $value );
	delete_option( 'nab_pre_alt_checksum' );
}
add_action( 'nab_start_experiment', __NAMESPACE__ . '\maybe_create_alternative_session_option' );


/**
 * Callback to delete alternative session option if none exists.
 *
 * @return void
 */
function maybe_backup_alternative_session_option() {
	$manager = nelioab()->manager();
	if ( $manager->has_running_experiments() || $manager->has_paused_experiments() ) {
		return;
	}

	$value = get_option( 'nab_alt_checksum', '' );
	if ( empty( $value ) ) {
		return;
	}

	update_option( 'nab_pre_alt_checksum', $value );
	delete_option( 'nab_alt_checksum' );
}
add_action( 'nab_stop_experiment', __NAMESPACE__ . '\maybe_backup_alternative_session_option' );


/**
 * Callback to create alternative session option if none exists.
 *
 * @return void
 */
function maybe_restore_previous_alternative_session_option() {
	$value = get_option( 'nab_alt_checksum', '' );
	if ( ! empty( $value ) ) {
		delete_option( 'nab_pre_alt_checksum' );
		return;
	}

	$pre_value = get_option( 'nab_pre_alt_checksum', '' );
	if ( empty( $pre_value ) ) {
		return;
	}

	update_option( 'nab_alt_checksum', $pre_value );
	delete_option( 'nab_pre_alt_checksum' );
}
add_action( 'nab_restart_experiment', __NAMESPACE__ . '\maybe_restore_previous_alternative_session_option' );
