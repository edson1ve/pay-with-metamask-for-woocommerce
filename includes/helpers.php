<?php
/**
 * GOLDT Helper Functions
 *
 * Collection of utility functions used throughout the plugin.
 *
 * @package GOLDT_Web3_Hybrid_Payments
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get plugin settings
 *
 * Retrieve all plugin settings with default fallbacks.
 *
 * @return array Plugin settings.
 * @since 1.0.0
 */
function goldt_get_settings() {
	$defaults = array(
		'enabled'           => 'yes',
		'web3_enabled'      => 'yes',
		'web2_fallback'     => 'yes',
		'chain_id'          => '56',
		'wallet_address'    => '',
		'oracle_endpoint'   => GOLDT_ORACLE_ENDPOINT,
		'cache_duration'    => 60,
		'allowed_tokens'    => array( 'GOLDT', 'GOLDVE', 'BNBV', 'GPOOL', 'FGVAULT' ),
		'payment_status'    => 'processing',
	);

	$settings = get_option( 'goldt_settings', array() );
	return wp_parse_args( $settings, $defaults );
}

/**
 * Get a specific setting value
 *
 * @param string $key     Setting key.
 * @param mixed  $default Default value if setting doesn't exist.
 * @return mixed Setting value.
 * @since 1.0.0
 */
function goldt_get_setting( $key, $default = null ) {
	$settings = goldt_get_settings();
	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
}

/**
 * Update plugin settings
 *
 * @param array $new_settings New settings to merge.
 * @return bool True on success, false on failure.
 * @since 1.0.0
 */
function goldt_update_settings( $new_settings ) {
	$current = goldt_get_settings();
	$updated = array_merge( $current, $new_settings );
	return update_option( 'goldt_settings', $updated );
}

/**
 * Format cryptocurrency amount
 *
 * Format a cryptocurrency amount with proper decimal places.
 *
 * @param string|float $amount   Amount to format.
 * @param int          $decimals Number of decimal places.
 * @return string Formatted amount.
 * @since 1.0.0
 */
function goldt_format_crypto_amount( $amount, $decimals = 8 ) {
	return number_format( (float) $amount, $decimals, '.', '' );
}

/**
 * Sanitize Ethereum address
 *
 * Validate and sanitize an Ethereum address.
 *
 * @param string $address Address to sanitize.
 * @return string|false Sanitized address or false if invalid.
 * @since 1.0.0
 */
function goldt_sanitize_eth_address( $address ) {
	// Remove whitespace.
	$address = trim( $address );

	// Check if it matches Ethereum address pattern (0x followed by 40 hex characters).
	if ( preg_match( '/^0x[a-fA-F0-9]{40}$/', $address ) ) {
		return strtolower( $address );
	}

	return false;
}

/**
 * Sanitize transaction hash
 *
 * Validate and sanitize a transaction hash.
 *
 * @param string $hash Transaction hash to sanitize.
 * @return string|false Sanitized hash or false if invalid.
 * @since 1.0.0
 */
function goldt_sanitize_tx_hash( $hash ) {
	// Remove whitespace.
	$hash = trim( $hash );

	// Check if it matches transaction hash pattern (0x followed by 64 hex characters).
	if ( preg_match( '/^0x[a-fA-F0-9]{64}$/', $hash ) ) {
		return strtolower( $hash );
	}

	return false;
}

/**
 * Get network name by chain ID
 *
 * @param int $chain_id Chain ID.
 * @return string Network name.
 * @since 1.0.0
 */
function goldt_get_network_name( $chain_id ) {
	$networks = array(
		'1'     => 'Ethereum Mainnet',
		'3'     => 'Ropsten Testnet',
		'4'     => 'Rinkeby Testnet',
		'5'     => 'Goerli Testnet',
		'56'    => 'Binance Smart Chain',
		'97'    => 'BSC Testnet',
		'137'   => 'Polygon',
		'80001' => 'Mumbai Testnet',
	);

	return isset( $networks[ $chain_id ] ) ? $networks[ $chain_id ] : __( 'Unknown Network', 'goldt-web3' );
}

/**
 * Log transaction to database
 *
 * @param array $data Transaction data.
 * @return int|false Transaction ID on success, false on failure.
 * @since 1.0.0
 */
function goldt_log_transaction( $data ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'goldt_transactions';

	$defaults = array(
		'order_id'          => 0,
		'tx_hash'           => '',
		'from_address'      => '',
		'to_address'        => '',
		'token_symbol'      => '',
		'token_address'     => '',
		'amount_fiat'       => 0,
		'amount_token'      => 0,
		'chain_id'          => 0,
		'oracle_rate'       => 0,
		'oracle_timestamp'  => time(),
		'status'            => 'pending',
	);

	$data = wp_parse_args( $data, $defaults );

	$result = $wpdb->insert(
		$table_name,
		array(
			'order_id'         => absint( $data['order_id'] ),
			'tx_hash'          => sanitize_text_field( $data['tx_hash'] ),
			'from_address'     => sanitize_text_field( $data['from_address'] ),
			'to_address'       => sanitize_text_field( $data['to_address'] ),
			'token_symbol'     => sanitize_text_field( $data['token_symbol'] ),
			'token_address'    => sanitize_text_field( $data['token_address'] ),
			'amount_fiat'      => floatval( $data['amount_fiat'] ),
			'amount_token'     => floatval( $data['amount_token'] ),
			'chain_id'         => absint( $data['chain_id'] ),
			'oracle_rate'      => floatval( $data['oracle_rate'] ),
			'oracle_timestamp' => absint( $data['oracle_timestamp'] ),
			'status'           => sanitize_text_field( $data['status'] ),
		),
		array( '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%f', '%d', '%s' )
	);

	return $result ? $wpdb->insert_id : false;
}

/**
 * Get transaction by hash
 *
 * @param string $tx_hash Transaction hash.
 * @return object|null Transaction object or null if not found.
 * @since 1.0.0
 */
function goldt_get_transaction_by_hash( $tx_hash ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'goldt_transactions';
	$tx_hash = goldt_sanitize_tx_hash( $tx_hash );

	if ( ! $tx_hash ) {
		return null;
	}

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE tx_hash = %s",
			$tx_hash
		)
	);
}

/**
 * Update transaction status
 *
 * @param int    $transaction_id Transaction ID.
 * @param string $status         New status.
 * @return bool True on success, false on failure.
 * @since 1.0.0
 */
function goldt_update_transaction_status( $transaction_id, $status ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'goldt_transactions';

	$result = $wpdb->update(
		$table_name,
		array( 'status' => sanitize_text_field( $status ) ),
		array( 'id' => absint( $transaction_id ) ),
		array( '%s' ),
		array( '%d' )
	);

	return false !== $result;
}

/**
 * Check if MetaMask is available
 *
 * This is a server-side helper that returns JavaScript to detect MetaMask.
 *
 * @return string JavaScript code to detect MetaMask.
 * @since 1.0.0
 */
function goldt_metamask_detection_js() {
	return "typeof window.ethereum !== 'undefined' && window.ethereum.isMetaMask";
}

/**
 * Generate nonce for AJAX requests
 *
 * @param string $action Action name.
 * @return string Nonce.
 * @since 1.0.0
 */
function goldt_create_nonce( $action = 'goldt_ajax' ) {
	return wp_create_nonce( $action );
}

/**
 * Verify nonce for AJAX requests
 *
 * @param string $nonce  Nonce to verify.
 * @param string $action Action name.
 * @return bool True if valid, false otherwise.
 * @since 1.0.0
 */
function goldt_verify_nonce( $nonce, $action = 'goldt_ajax' ) {
	return wp_verify_nonce( $nonce, $action );
}

/**
 * Get explorer URL for transaction
 *
 * @param string $tx_hash  Transaction hash.
 * @param int    $chain_id Chain ID.
 * @return string Explorer URL.
 * @since 1.0.0
 */
function goldt_get_explorer_url( $tx_hash, $chain_id ) {
	$explorers = array(
		'1'     => 'https://etherscan.io/tx/',
		'3'     => 'https://ropsten.etherscan.io/tx/',
		'4'     => 'https://rinkeby.etherscan.io/tx/',
		'5'     => 'https://goerli.etherscan.io/tx/',
		'56'    => 'https://bscscan.com/tx/',
		'97'    => 'https://testnet.bscscan.com/tx/',
		'137'   => 'https://polygonscan.com/tx/',
		'80001' => 'https://mumbai.polygonscan.com/tx/',
	);

	$base_url = isset( $explorers[ $chain_id ] ) ? $explorers[ $chain_id ] : '';
	return $base_url ? $base_url . $tx_hash : '';
}

/**
 * Format fiat currency
 *
 * @param float  $amount   Amount to format.
 * @param string $currency Currency code.
 * @return string Formatted currency string.
 * @since 1.0.0
 */
function goldt_format_fiat_currency( $amount, $currency = 'USD' ) {
	return wc_price( $amount, array( 'currency' => $currency ) );
}

/**
 * Debug log function
 *
 * @param mixed  $message Message to log.
 * @param string $level   Log level (info, warning, error).
 * @since 1.0.0
 */
function goldt_log( $message, $level = 'info' ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$logger = wc_get_logger();
		$context = array( 'source' => 'goldt-web3-hybrid-payments' );
		
		if ( is_array( $message ) || is_object( $message ) ) {
			$message = print_r( $message, true );
		}

		switch ( $level ) {
			case 'error':
				$logger->error( $message, $context );
				break;
			case 'warning':
				$logger->warning( $message, $context );
				break;
			default:
				$logger->info( $message, $context );
				break;
		}
	}
}
