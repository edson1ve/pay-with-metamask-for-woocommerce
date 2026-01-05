<?php
/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled.
 *
 * @package GOLDT_Web3_Hybrid_Payments
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Uninstall GOLDT Web3 Hybrid Payments plugin
 *
 * Removes all plugin data including:
 * - Options
 * - Transients
 * - Database tables
 * - User meta
 */

global $wpdb;

// Remove plugin options.
delete_option( 'goldt_settings' );
delete_option( 'goldt_custom_tokens' );
delete_option( 'goldt_default_token_contracts' );
delete_option( 'goldt_activated_time' );

// Remove WooCommerce gateway settings.
delete_option( 'woocommerce_goldt_gateway_settings' );

// Remove all transients.
$wpdb->query(
	"DELETE FROM {$wpdb->options} 
	WHERE option_name LIKE '_transient_goldt_%' 
	OR option_name LIKE '_transient_timeout_goldt_%'"
);

// Drop custom database table.
$table_name = $wpdb->prefix . 'goldt_transactions';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

// Remove user meta data.
$wpdb->query(
	"DELETE FROM {$wpdb->usermeta} 
	WHERE meta_key LIKE 'goldt_%'"
);

// Clear any cached data.
wp_cache_flush();
