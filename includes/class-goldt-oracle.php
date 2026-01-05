<?php
/**
 * GOLDT Oracle Class
 *
 * Handles integration with the GOLDT oracle API for real-time price data.
 *
 * @package GOLDT_Web3_Hybrid_Payments
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GOLDT_Oracle
 *
 * Manages communication with the GOLDT oracle endpoint and provides
 * real-time pricing data with caching support.
 *
 * @since 1.0.0
 */
class GOLDT_Oracle {

	/**
	 * Single instance of the class
	 *
	 * @var GOLDT_Oracle|null
	 */
	private static $instance = null;

	/**
	 * Oracle endpoint URL
	 *
	 * @var string
	 */
	private $endpoint;

	/**
	 * Cache duration in seconds
	 *
	 * @var int
	 */
	private $cache_duration;

	/**
	 * Get single instance
	 *
	 * @return GOLDT_Oracle
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->endpoint = goldt_get_setting( 'oracle_endpoint', GOLDT_ORACLE_ENDPOINT );
		$this->cache_duration = goldt_get_setting( 'cache_duration', 60 );
	}

	/**
	 * Get token rate from oracle
	 *
	 * Fetches real-time price data from the GOLDT oracle endpoint.
	 *
	 * @param string $token_symbol Token symbol (GOLDT, GOLDVE, etc.).
	 * @return array|WP_Error Rate data or error.
	 * @since 1.0.0
	 */
	public function get_token_rate( $token_symbol ) {
		// Check cache first.
		$cache_key = 'goldt_rate_' . sanitize_key( $token_symbol );
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			goldt_log( "Using cached rate for {$token_symbol}" );
			return $cached;
		}

		// Fetch from oracle.
		$response = wp_remote_get(
			$this->endpoint,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			goldt_log( 'Oracle request failed: ' . $response->get_error_message(), 'error' );
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			$error = new WP_Error(
				'oracle_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Oracle returned status code: %d', 'goldt-web3' ),
					$status_code
				)
			);
			goldt_log( 'Oracle returned error status: ' . $status_code, 'error' );
			return $error;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$error = new WP_Error(
				'oracle_parse_error',
				__( 'Failed to parse oracle response', 'goldt-web3' )
			);
			goldt_log( 'Failed to parse oracle JSON response', 'error' );
			return $error;
		}

		// Parse the oracle response.
		$rate_data = $this->parse_oracle_response( $data, $token_symbol );

		if ( is_wp_error( $rate_data ) ) {
			return $rate_data;
		}

		// Cache the result.
		set_transient( $cache_key, $rate_data, $this->cache_duration );

		goldt_log( "Fetched new rate for {$token_symbol}: " . print_r( $rate_data, true ) );

		return $rate_data;
	}

	/**
	 * Parse oracle response
	 *
	 * Extracts and formats rate data from the oracle API response.
	 *
	 * @param array  $data         Oracle API response.
	 * @param string $token_symbol Token symbol.
	 * @return array|WP_Error Parsed rate data or error.
	 * @since 1.0.0
	 */
	private function parse_oracle_response( $data, $token_symbol ) {
		// Expected structure from GOLDT oracle:
		// {
		//   "price_usd": "1.25",
		//   "rate_inverse": "0.8",
		//   "trend": "up",
		//   "contract": "0x...",
		//   "timestamp": 1234567890
		// }

		if ( ! isset( $data['price_usd'] ) ) {
			return new WP_Error(
				'oracle_missing_data',
				__( 'Oracle response missing price_usd field', 'goldt-web3' )
			);
		}

		return array(
			'symbol'         => $token_symbol,
			'price_usd'      => floatval( $data['price_usd'] ),
			'rate_inverse'   => isset( $data['rate_inverse'] ) ? floatval( $data['rate_inverse'] ) : ( 1 / floatval( $data['price_usd'] ) ),
			'trend'          => isset( $data['trend'] ) ? sanitize_text_field( $data['trend'] ) : 'stable',
			'contract'       => isset( $data['contract'] ) ? sanitize_text_field( $data['contract'] ) : '',
			'timestamp'      => isset( $data['timestamp'] ) ? absint( $data['timestamp'] ) : time(),
			'cached_at'      => time(),
		);
	}

	/**
	 * Calculate token amount from fiat
	 *
	 * Converts a fiat amount to token amount based on current oracle rate.
	 *
	 * @param float  $fiat_amount  Amount in fiat currency (USD).
	 * @param string $token_symbol Token symbol.
	 * @return array|WP_Error Calculation result or error.
	 * @since 1.0.0
	 */
	public function calculate_token_amount( $fiat_amount, $token_symbol ) {
		$rate_data = $this->get_token_rate( $token_symbol );

		if ( is_wp_error( $rate_data ) ) {
			return $rate_data;
		}

		$token_amount = $fiat_amount * $rate_data['rate_inverse'];

		return array(
			'fiat_amount'     => floatval( $fiat_amount ),
			'token_amount'    => $token_amount,
			'token_symbol'    => $token_symbol,
			'price_usd'       => $rate_data['price_usd'],
			'rate_inverse'    => $rate_data['rate_inverse'],
			'oracle_timestamp' => $rate_data['timestamp'],
		);
	}

	/**
	 * Clear rate cache
	 *
	 * Remove cached rates for a specific token or all tokens.
	 *
	 * @param string|null $token_symbol Token symbol or null for all.
	 * @return bool True on success.
	 * @since 1.0.0
	 */
	public function clear_cache( $token_symbol = null ) {
		if ( null === $token_symbol ) {
			// Clear all GOLDT rate caches.
			global $wpdb;
			$wpdb->query(
				"DELETE FROM {$wpdb->options} 
				WHERE option_name LIKE '_transient_goldt_rate_%' 
				OR option_name LIKE '_transient_timeout_goldt_rate_%'"
			);
			goldt_log( 'Cleared all oracle rate caches' );
		} else {
			$cache_key = 'goldt_rate_' . sanitize_key( $token_symbol );
			delete_transient( $cache_key );
			goldt_log( "Cleared cache for {$token_symbol}" );
		}

		return true;
	}

	/**
	 * Test oracle connection
	 *
	 * Verifies that the oracle endpoint is accessible and returning valid data.
	 *
	 * @return array Test result with status and message.
	 * @since 1.0.0
	 */
	public function test_connection() {
		// Try to fetch rate for GOLDT token.
		$rate_data = $this->get_token_rate( 'GOLDT' );

		if ( is_wp_error( $rate_data ) ) {
			return array(
				'success' => false,
				'message' => $rate_data->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Oracle connection successful', 'goldt-web3' ),
			'data'    => $rate_data,
		);
	}

	/**
	 * Get multiple token rates
	 *
	 * Fetches rates for multiple tokens in batch.
	 *
	 * @param array $token_symbols Array of token symbols.
	 * @return array Array of rate data keyed by symbol.
	 * @since 1.0.0
	 */
	public function get_multiple_rates( $token_symbols ) {
		$rates = array();

		foreach ( $token_symbols as $symbol ) {
			$rate_data = $this->get_token_rate( $symbol );
			
			if ( ! is_wp_error( $rate_data ) ) {
				$rates[ $symbol ] = $rate_data;
			} else {
				$rates[ $symbol ] = array(
					'error' => $rate_data->get_error_message(),
				);
			}
		}

		return $rates;
	}

	/**
	 * Set custom endpoint
	 *
	 * Allows changing the oracle endpoint at runtime.
	 *
	 * @param string $endpoint New endpoint URL.
	 * @return bool True on success.
	 * @since 1.0.0
	 */
	public function set_endpoint( $endpoint ) {
		if ( filter_var( $endpoint, FILTER_VALIDATE_URL ) ) {
			$this->endpoint = esc_url_raw( $endpoint );
			return true;
		}
		return false;
	}

	/**
	 * Get current endpoint
	 *
	 * @return string Current oracle endpoint.
	 * @since 1.0.0
	 */
	public function get_endpoint() {
		return $this->endpoint;
	}
}
