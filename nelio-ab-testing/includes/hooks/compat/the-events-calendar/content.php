<?php

namespace Nelio_AB_Testing\Compat\The_Events_Calendar;

defined( 'ABSPATH' ) || exit;

use function add_action;

/**
 * Callback to overwrite tribe event tables.
 *
 * @param int $dest_id Destination post.
 * @param int $src_id  Source post.
 *
 * @return void
 */
function maybe_overwrite_event_tables( $dest_id, $src_id ) {
	if ( 'tribe_events' !== get_post_type( $src_id ) ) {
		return;
	}

	$event_maps = duplicate_events( $dest_id, $src_id );
	duplicate_occurrences( $event_maps, $dest_id, $src_id );
}
add_action( 'nab_overwrite_post', __NAMESPACE__ . '\maybe_overwrite_event_tables', 10, 2 );

/**
 * Duplicates events.
 *
 * @param int $dest_id Destination post.
 * @param int $src_id  Source post.
 *
 * @return array<int,int>
 */
function duplicate_events( $dest_id, $src_id ) {
	/** @var \wpdb $wpdb */
	global $wpdb;
	$table = "{$wpdb->prefix}tec_events";

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			'DELETE FROM %i WHERE post_id = %d',
			$table,
			$dest_id
		) ?? ''
	);

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$src_rows = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM %i WHERE post_id = %d ORDER BY event_id',
			$table,
			$src_id
		),
		ARRAY_A
	);

	if ( empty( $src_rows ) ) {
		return array();
	}

	/** @var list<int> */
	$src_event_ids = wp_list_pluck( $src_rows, 'event_id' );

	$src_rows = array_map( fn( $r ) => remove( $r, 'event_id' ), $src_rows );
	/** @var list<array{post_id:int}> $src_rows */
	foreach ( $src_rows as $row ) {
		$row['post_id'] = $dest_id;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $table, $row );
	}

	/** @var list<int> */
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$dest_event_ids = $wpdb->get_col(
		$wpdb->prepare(
			'SELECT event_id FROM %i WHERE post_id = %d ORDER BY event_id',
			$table,
			$dest_id
		)
	);

	return array_combine( $src_event_ids, $dest_event_ids );
}

/**
 * Duplicates occurrences.
 *
 * @param array<int,int> $event_maps Destination post.
 * @param int            $dest_id    Destination post.
 * @param int            $src_id     Source post.
 *
 * @return void
 */
function duplicate_occurrences( $event_maps, $dest_id, $src_id ) {
	if ( empty( $event_maps ) ) {
		return;
	}

	/** @var \wpdb $wpdb */
	global $wpdb;
	$table = "{$wpdb->prefix}tec_occurrences";

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			'DELETE FROM %i WHERE post_id = %d',
			$table,
			$dest_id
		) ?? ''
	);

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$src_rows = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM %i WHERE post_id = %d ORDER BY occurrence_id',
			$table,
			$src_id
		),
		ARRAY_A
	);

	if ( empty( $src_rows ) ) {
		return;
	}

	$src_rows = array_map( fn( $r ) => remove( $r, 'occurrence_id' ), $src_rows );
	$src_rows = array_map( fn( $r ) => remove( $r, 'hash' ), $src_rows );
	/** @var list<array{post_id:int,event_id:int,hash:string}> $src_rows */
	foreach ( $src_rows as $row ) {
		$ori_event_id = $row['event_id'];
		$new_event_id = $event_maps[ $ori_event_id ] ?? 0;
		if ( empty( $new_event_id ) ) {
			continue;
		}
		$row['post_id']  = $dest_id;
		$row['event_id'] = $new_event_id;
		$row['hash']     = sha1( implode( ':', $row ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $table, $row );
	}
}

/**
 * Creates a callback that removes the given key from an array.
 *
 * @param array<mixed> $row    Row of data.
 * @param string       $column Column to remove.
 *
 * @return array<mixed>
 */
function remove( $row, $column ) {
	if ( isset( $row[ $column ] ) ) {
		unset( $row[ $column ] );
	}
	return $row;
}
