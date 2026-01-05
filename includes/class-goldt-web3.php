<?php
/**
 * GOLDT Web3 Class
 *
 * Handles Web3 and MetaMask integration.
 *
 * @package GOLDT_Web3_Hybrid_Payments
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GOLDT_Web3
 *
 * Manages Web3 functionality including MetaMask detection,
 * transaction building, and REST API endpoints.
 *
 * @since 1.0.0
 */
class GOLDT_Web3 {

	/**
	 * Single instance of the class
	 *
	 * @var GOLDT_Web3|null
	 */
	private static $instance = null;

	/**
	 * Get single instance
	 *
	 * @return GOLDT_Web3
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
		$this->register_rest_routes();
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 *
	 * @since 1.0.0
	 */
	private function register_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_web3_scripts' ) );
	}

	/**
	 * Enqueue Web3 scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_web3_scripts() {
		// Only load on checkout page.
		if ( ! is_checkout() ) {
			return;
		}

		// Enqueue Web3.js library from CDN.
		wp_enqueue_script(
			'web3js',
			'https://cdn.jsdelivr.net/npm/web3@1.8.2/dist/web3.min.js',
			array(),
			'1.8.2',
			true
		);

		// Enqueue our Web3 handler.
		wp_enqueue_script(
			'goldt-web3-handler',
			GOLDT_URL . 'assets/js/web3-handler.js',
			array( 'jquery', 'web3js' ),
			GOLDT_VERSION,
			true
		);

		// Localize script with data.
		wp_localize_script(
			'goldt-web3-handler',
			'goldtWeb3',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'restUrl'       => rest_url( 'goldt/v1/' ),
				'nonce'         => goldt_create_nonce( 'goldt_web3' ),
				'chainId'       => goldt_get_setting( 'chain_id', '56' ),
				'walletAddress' => goldt_get_setting( 'wallet_address', '' ),
				'tokens'        => $this->get_tokens_for_js(),
				'messages'      => array(
					'connectWallet'     => __( 'Please connect your MetaMask wallet', 'goldt-web3' ),
					'wrongNetwork'      => __( 'Please switch to the correct network', 'goldt-web3' ),
					'transactionSent'   => __( 'Transaction sent! Waiting for confirmation...', 'goldt-web3' ),
					'transactionFailed' => __( 'Transaction failed. Please try again.', 'goldt-web3' ),
					'paymentSuccess'    => __( 'Payment successful!', 'goldt-web3' ),
				),
			)
		);
	}

	/**
	 * Get tokens data for JavaScript
	 *
	 * @return array Tokens array formatted for JS.
	 * @since 1.0.0
	 */
	private function get_tokens_for_js() {
		$tokens_manager = GOLDT_Tokens::get_instance();
		$all_tokens = $tokens_manager->get_all_tokens( true );

		$js_tokens = array();
		foreach ( $all_tokens as $symbol => $token ) {
			$js_tokens[ $symbol ] = array(
				'symbol'   => $token['symbol'],
				'name'     => $token['name'],
				'decimals' => $token['decimals'],
				'contract' => $token['contract'],
				'logo'     => $token['logo'],
			);
		}

		return $js_tokens;
	}

	/**
	 * Register REST API routes
	 *
	 * @since 1.0.0
	 */
	private function register_rest_routes() {
		add_action( 'rest_api_init', array( $this, 'register_api_endpoints' ) );
	}

	/**
	 * Register API endpoints
	 *
	 * @since 1.0.0
	 */
	public function register_api_endpoints() {
		// Get price endpoint.
		register_rest_route(
			'goldt/v1',
			'/get-price',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'api_get_price' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'token_symbol' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'fiat_amount' => array(
						'required'          => true,
						'type'              => 'number',
						'sanitize_callback' => 'floatval',
					),
				),
			)
		);

		// Submit transaction endpoint.
		register_rest_route(
			'goldt/v1',
			'/submit-transaction',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'api_submit_transaction' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'order_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'tx_hash' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'from_address' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'token_symbol' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'token_amount' => array(
						'required'          => true,
						'type'              => 'number',
						'sanitize_callback' => 'floatval',
					),
				),
			)
		);

		// Get tokens endpoint.
		register_rest_route(
			'goldt/v1',
			'/get-tokens',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_tokens' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * API: Get price for token amount
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 * @since 1.0.0
	 */
	public function api_get_price( $request ) {
		$token_symbol = $request->get_param( 'token_symbol' );
		$fiat_amount = $request->get_param( 'fiat_amount' );

		// Get oracle instance.
		$oracle = GOLDT_Oracle::get_instance();

		// Calculate token amount.
		$calculation = $oracle->calculate_token_amount( $fiat_amount, $token_symbol );

		if ( is_wp_error( $calculation ) ) {
			return new WP_Error(
				'calculation_failed',
				$calculation->get_error_message(),
				array( 'status' => 400 )
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $calculation,
			),
			200
		);
	}

	/**
	 * API: Submit transaction
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 * @since 1.0.0
	 */
	public function api_submit_transaction( $request ) {
		$order_id = $request->get_param( 'order_id' );
		$tx_hash = $request->get_param( 'tx_hash' );
		$from_address = $request->get_param( 'from_address' );
		$token_symbol = $request->get_param( 'token_symbol' );
		$token_amount = $request->get_param( 'token_amount' );

		// Validate order.
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error(
				'invalid_order',
				__( 'Invalid order ID', 'goldt-web3' ),
				array( 'status' => 400 )
			);
		}

		// Validate transaction hash.
		$tx_hash = goldt_sanitize_tx_hash( $tx_hash );
		if ( ! $tx_hash ) {
			return new WP_Error(
				'invalid_tx_hash',
				__( 'Invalid transaction hash', 'goldt-web3' ),
				array( 'status' => 400 )
			);
		}

		// Validate addresses.
		$from_address = goldt_sanitize_eth_address( $from_address );
		if ( ! $from_address ) {
			return new WP_Error(
				'invalid_address',
				__( 'Invalid from address', 'goldt-web3' ),
				array( 'status' => 400 )
			);
		}

		// Check if transaction already exists.
		$existing = goldt_get_transaction_by_hash( $tx_hash );
		if ( $existing ) {
			return new WP_Error(
				'duplicate_transaction',
				__( 'Transaction already submitted', 'goldt-web3' ),
				array( 'status' => 400 )
			);
		}

		// Get token info.
		$tokens = GOLDT_Tokens::get_instance();
		$token = $tokens->get_token( $token_symbol );
		if ( ! $token ) {
			return new WP_Error(
				'invalid_token',
				__( 'Invalid token', 'goldt-web3' ),
				array( 'status' => 400 )
			);
		}

		// Get oracle rate.
		$oracle = GOLDT_Oracle::get_instance();
		$rate_data = $oracle->get_token_rate( $token_symbol );
		if ( is_wp_error( $rate_data ) ) {
			return new WP_Error(
				'rate_fetch_failed',
				$rate_data->get_error_message(),
				array( 'status' => 500 )
			);
		}

		// Log transaction.
		$transaction_id = goldt_log_transaction(
			array(
				'order_id'         => $order_id,
				'tx_hash'          => $tx_hash,
				'from_address'     => $from_address,
				'to_address'       => goldt_get_setting( 'wallet_address', '' ),
				'token_symbol'     => $token_symbol,
				'token_address'    => $token['contract'],
				'amount_fiat'      => $order->get_total(),
				'amount_token'     => $token_amount,
				'chain_id'         => goldt_get_setting( 'chain_id', 56 ),
				'oracle_rate'      => $rate_data['price_usd'],
				'oracle_timestamp' => $rate_data['timestamp'],
				'status'           => 'pending',
			)
		);

		if ( ! $transaction_id ) {
			return new WP_Error(
				'log_failed',
				__( 'Failed to log transaction', 'goldt-web3' ),
				array( 'status' => 500 )
			);
		}

		// Update order metadata.
		$order->add_meta_data( '_goldt_tx_hash', $tx_hash, true );
		$order->add_meta_data( '_goldt_token_symbol', $token_symbol, true );
		$order->add_meta_data( '_goldt_token_amount', $token_amount, true );
		$order->add_meta_data( '_goldt_from_address', $from_address, true );
		$order->add_meta_data( '_goldt_transaction_id', $transaction_id, true );
		$order->save();

		// Update order status.
		$payment_status = goldt_get_setting( 'payment_status', 'processing' );
		$order->update_status(
			$payment_status,
			sprintf(
				/* translators: 1: token amount, 2: token symbol, 3: transaction hash */
				__( 'Web3 payment received: %1$s %2$s (TX: %3$s)', 'goldt-web3' ),
				goldt_format_crypto_amount( $token_amount ),
				$token_symbol,
				$tx_hash
			)
		);

		goldt_log( "Transaction submitted for order {$order_id}: {$tx_hash}" );

		return new WP_REST_Response(
			array(
				'success'        => true,
				'transaction_id' => $transaction_id,
				'explorer_url'   => goldt_get_explorer_url( $tx_hash, goldt_get_setting( 'chain_id', 56 ) ),
				'message'        => __( 'Payment submitted successfully', 'goldt-web3' ),
			),
			200
		);
	}

	/**
	 * API: Get available tokens
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 * @since 1.0.0
	 */
	public function api_get_tokens( $request ) {
		$tokens_manager = GOLDT_Tokens::get_instance();
		$tokens = $tokens_manager->get_all_tokens( true );

		return new WP_REST_Response(
			array(
				'success' => true,
				'tokens'  => array_values( $tokens ),
			),
			200
		);
	}
}
