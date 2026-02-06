<?php
/**
 * Nelio A/B Testing subscription-related functions.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/functions
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This function returns the current subscription plan, if any.
 *
 * @return string|false name of the current subscription, or `false` if it has none.
 *
 * @since 5.0.0
 */
function nab_get_subscription() {
	return get_option( 'nab_subscription', false );
}

/**
 * This function returns the current subscription addons, if any.
 *
 * @return list<string> list of addons.
 *
 * @since 6.4.0
 */
function nab_get_subscription_addons() {
	return get_option( 'nab_subscription_addons', array() );
}

/**
 * Returns whether the current user is a paying customer or not.
 *
 * @return boolean whether the current user is a paying customer or not.
 *
 * @since 5.0.0
 */
function nab_is_subscribed() {
	$subscription = nab_get_subscription();
	return ! empty( $subscription );
}

/**
 * This helper function updates the current subscription.
 *
 * @param string $plan The plan of the subscription.
 *
 * @return void
 * @since 5.0.0
 */
function nab_update_subscription( $plan ) {
	if ( empty( $plan ) || 'free' === $plan ) {
		delete_option( 'nab_subscription' );
	} else {
		update_option( 'nab_subscription', $plan );
	}
}

/**
 * Returns the plan of a product.
 *
 * @param string $product The product path.
 *
 * @return 'basic'|'professional'|'enterprise'|'free'|'quota'
 *
 * @since 5.0.0
 */
function nab_get_plan( $product ) {
	switch ( $product ) {
		case 'nab-basic-monthly':
		case 'nab-basic-yearly':
			return 'basic';
		case 'nab-pro-monthly':
		case 'nab-pro-yearly':
			return 'professional';
		case 'nab-enterprise-monthly':
		case 'nab-enterprise-yearly':
			return 'enterprise';
		case 'nab-extra-quota':
			return 'quota';
		default:
			return 'free';
	}
}

/**
 * Returns the interval of a product.
 *
 * @param string $product The product path.
 *
 * @return string The interval of the plan (month or year).
 *
 * @since 5.0.0
 */
function nab_get_period( $product ) {
	switch ( $product ) {
		case 'nab-basic-monthly':
		case 'nab-pro-monthly':
		case 'nab-enterprise-monthly':
			return 'month';
		case 'nab-basic-yearly':
		case 'nab-pro-yearly':
		case 'nab-enterprise-yearly':
			return 'year';
		case 'nab-extra-quota':
			return 'unlimited';
		default:
			return strpos( $product, 'month' ) !== false ? 'month' : 'year';
	}
}

/**
 * Returns whether the current subscription plan is the one specified.
 *
 * @param string $expected_plan the expected plan.
 * @param string $mode          whether the matching mode should be exact or any plan above the specified one works too. Default: or-above.
 *
 * @return boolean whether the actual plan is the expected plan (or above it, depending on the mode).
 *
 * @since 5.0.0
 */
function nab_is_subscribed_to( $expected_plan, $mode = 'or-above' ) {

	if ( ! nab_is_subscribed() ) {
		return false;
	}

	$plans = array( 'basic', 'professional', 'enterprise' );

	$actual_plan          = nab_get_subscription();
	$actual_plan_position = array_search( $actual_plan, $plans, true );

	$expected_plan_position = array_search( $expected_plan, $plans, true );

	if ( false === $actual_plan_position ) {
		return false;
	}

	if ( false === $expected_plan_position ) {
		return false;
	}

	if ( 'or-above' !== $mode ) {
		return $expected_plan_position === $actual_plan_position;
	}

	return $actual_plan_position >= $expected_plan_position;
}

/**
 * Returns whether the current user is paying the addon or not.
 *
 * @param string $addon_name The name of the addon.
 *
 * @return boolean whether the current user is paying the addon or not.
 *
 * @since 6.4.0
 */
function nab_is_subscribed_to_addon( $addon_name ) {
	$addons = nab_get_subscription_addons();
	foreach ( $addons as $addon ) {
		if ( strpos( $addon, $addon_name ) === 0 ) {
			return true;
		}
	}
	return false;
}

/**
 * This helper function updates the current subscription addons.
 *
 * @param list<string> $addons The addons of the subscription.
 *
 * @return void
 *
 * @since 6.4.0
 */
function nab_update_subscription_addons( $addons ) {
	if ( empty( $addons ) ) {
		delete_option( 'nab_subscription_addons' );
	} else {
		update_option( 'nab_subscription_addons', $addons );
	}
}

/**
 * Returns whether Nelio AI features are available or not.
 *
 * @return boolean whether Nelio AI features are available or not.
 *
 * @since 8.0.0
 */
function nab_is_ai_active() {
	$available = nab_is_subscribed_to_addon( 'nelio-ai' );
	$settings  = Nelio_AB_Testing_Settings::instance();
	$enabled   = ! empty( $settings->get( 'is_nelio_ai_enabled' ) );
	return $available && $enabled;
}
