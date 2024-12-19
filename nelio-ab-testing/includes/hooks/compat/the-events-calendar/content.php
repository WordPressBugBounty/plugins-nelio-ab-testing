<?php

namespace Nelio_AB_Testing\Compat\The_Events_Calendar;

defined( 'ABSPATH' ) || exit;

use function add_action;

function maybe_overwrite_event_tables( $dest_id, $src_id ) {
	if ( 'tribe_events' !== get_post_type( $src_id ) ) {
		return;
	}//end if

	$event_maps = duplicate_events( $dest_id, $src_id );
	duplicate_occurrences( $event_maps, $dest_id, $src_id );
}//end maybe_overwrite_event_tables()
add_action( 'nab_overwrite_post', __NAMESPACE__ . '\maybe_overwrite_event_tables', 10, 2 );

function duplicate_events( $dest_id, $src_id ) {
	global $wpdb;
	$table = "{$wpdb->prefix}tec_events";

	$wpdb->query( // phpcs:ignore
		$wpdb->prepare( // phpcs:ignore
			'DELETE FROM %i WHERE post_id = %d', // phpcs:ignore
			$table,
			$dest_id
		)
	);

	$src_rows = $wpdb->get_results( // phpcs:ignore
		$wpdb->prepare( // phpcs:ignore
			'SELECT * FROM %i WHERE post_id = %d ORDER BY event_id', // phpcs:ignore
			$table,
			$src_id
		),
		ARRAY_A
	);

	if ( empty( $src_rows ) ) {
		return array();
	}//end if

	$src_event_ids = wp_list_pluck( $src_rows, 'event_id' );
	$src_rows      = array_map( remove( 'event_id' ), $src_rows );

	foreach ( $src_rows as $row ) {
		$row['post_id'] = $dest_id;
		$wpdb->insert( $table, $row ); // phpcs:ignore
	}//end foreach

	$dest_event_ids = $wpdb->get_col( // phpcs:ignore
		$wpdb->prepare( // phpcs:ignore
			'SELECT event_id FROM %i WHERE post_id = %d ORDER BY event_id', // phpcs:ignore
			$table,
			$dest_id
		)
	);

	return array_combine( $src_event_ids, $dest_event_ids );
}//end duplicate_events()

function duplicate_occurrences( $event_maps, $dest_id, $src_id ) {
	if ( empty( $event_maps ) ) {
		return;
	}//end if

	global $wpdb;
	$table = "{$wpdb->prefix}tec_occurrences";

	$wpdb->query( // phpcs:ignore
		$wpdb->prepare( // phpcs:ignore
			'DELETE FROM %i WHERE post_id = %d', // phpcs:ignore
			$table,
			$dest_id
		)
	);

	$src_rows = $wpdb->get_results( // phpcs:ignore
		$wpdb->prepare( // phpcs:ignore
			'SELECT * FROM %i WHERE post_id = %d ORDER BY occurrence_id', // phpcs:ignore
			$table,
			$src_id
		),
		ARRAY_A
	);

	if ( empty( $src_rows ) ) {
		return array();
	}//end if

	$src_rows = array_map( remove( 'occurrence_id' ), $src_rows );
	$src_rows = array_map( remove( 'hash' ), $src_rows );

	foreach ( $src_rows as $row ) {
		$ori_event_id = $row['event_id'];
		$new_event_id = nab_array_get( $event_maps, $ori_event_id, 0 );
		if ( empty( $new_event_id ) ) {
			continue;
		}//end if
		$row['post_id']  = $dest_id;
		$row['event_id'] = $new_event_id;
		$row['hash']     = sha1( implode( ':', $row ) );
		$wpdb->insert( $table, $row ); // phpcs:ignore
	}//end foreach
}//end duplicate_occurrences()

function remove( $column ) {
	return function ( $row ) use ( $column ) {
		if ( isset( $row[ $column ] ) ) {
			unset( $row[ $column ] );
		}//end if
		return $row;
	};
}//end remove()
