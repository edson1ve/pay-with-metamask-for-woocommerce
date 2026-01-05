<?php
/**
 * GOLDT Tokens Class
 *
 * Manages GOLDT ecosystem tokens configuration and validation.
 *
 * @package GOLDT_Web3_Hybrid_Payments
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GOLDT_Tokens
 *
 * Handles token configuration, validation, and management for the
 * GOLDT ecosystem tokens.
 *
 * @since 1.0.0
 */
class GOLDT_Tokens {

	/**
	 * Single instance of the class
	 *
	 * @var GOLDT_Tokens|null
	 */
	private static $instance = null;

	/**
	 * Default GOLDT ecosystem tokens
	 *
	 * @var array
	 */
	private $default_tokens;

	/**
	 * Custom tokens added by admin
	 *
	 * @var array
	 */
	private $custom_tokens;

	/**
	 * Get single instance
	 *
	 * @return GOLDT_Tokens
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
		$this->init_default_tokens();
		$this->load_custom_tokens();
	}

	/**
	 * Initialize default GOLDT ecosystem tokens
	 *
	 * @since 1.0.0
	 */
	private function init_default_tokens() {
		$this->default_tokens = array(
			'GOLDT' => array(
				'symbol'       => 'GOLDT',
				'name'         => 'GOLDT Token',
				'decimals'     => 18,
				'contract'     => '', // To be configured by admin.
				'chain_id'     => 56, // BSC.
				'type'         => 'ERC20',
				'logo'         => GOLDT_URL . 'assets/img/goldt-logo.png',
				'description'  => __( 'Main GOLDT ecosystem token', 'goldt-web3' ),
			),
			'GOLDVE' => array(
				'symbol'       => 'GOLDVE',
				'name'         => 'GOLDVE Token',
				'decimals'     => 18,
				'contract'     => '', // To be configured by admin.
				'chain_id'     => 56, // BSC.
				'type'         => 'ERC20',
				'logo'         => GOLDT_URL . 'assets/img/goldve-logo.png',
				'description'  => __( 'GOLDVE ecosystem token', 'goldt-web3' ),
			),
			'BNBV' => array(
				'symbol'       => 'BNBV',
				'name'         => 'BNBV Token',
				'decimals'     => 18,
				'contract'     => '', // To be configured by admin.
				'chain_id'     => 56, // BSC.
				'type'         => 'ERC20',
				'logo'         => GOLDT_URL . 'assets/img/bnbv-logo.png',
				'description'  => __( 'BNBV ecosystem token', 'goldt-web3' ),
			),
			'GPOOL' => array(
				'symbol'       => 'GPOOL',
				'name'         => 'GPOOL Token',
				'decimals'     => 18,
				'contract'     => '', // To be configured by admin.
				'chain_id'     => 56, // BSC.
				'type'         => 'ERC20',
				'logo'         => GOLDT_URL . 'assets/img/gpool-logo.png',
				'description'  => __( 'GPOOL ecosystem token', 'goldt-web3' ),
			),
			'FGVAULT' => array(
				'symbol'       => 'FGVAULT',
				'name'         => 'FGVAULT Token',
				'decimals'     => 18,
				'contract'     => '', // To be configured by admin.
				'chain_id'     => 56, // BSC.
				'type'         => 'ERC20',
				'logo'         => GOLDT_URL . 'assets/img/fgvault-logo.png',
				'description'  => __( 'FGVAULT ecosystem token', 'goldt-web3' ),
			),
		);
	}

	/**
	 * Load custom tokens from database
	 *
	 * @since 1.0.0
	 */
	private function load_custom_tokens() {
		$this->custom_tokens = get_option( 'goldt_custom_tokens', array() );
	}

	/**
	 * Get all available tokens
	 *
	 * Returns both default and custom tokens.
	 *
	 * @param bool $enabled_only Return only enabled tokens.
	 * @return array Array of token data.
	 * @since 1.0.0
	 */
	public function get_all_tokens( $enabled_only = false ) {
		$all_tokens = array_merge( $this->default_tokens, $this->custom_tokens );

		if ( $enabled_only ) {
			$allowed = goldt_get_setting( 'allowed_tokens', array() );
			$all_tokens = array_filter(
				$all_tokens,
				function( $token ) use ( $allowed ) {
					return in_array( $token['symbol'], $allowed, true );
				}
			);
		}

		return $all_tokens;
	}

	/**
	 * Get token by symbol
	 *
	 * @param string $symbol Token symbol.
	 * @return array|null Token data or null if not found.
	 * @since 1.0.0
	 */
	public function get_token( $symbol ) {
		$symbol = strtoupper( sanitize_text_field( $symbol ) );
		$all_tokens = $this->get_all_tokens();

		return isset( $all_tokens[ $symbol ] ) ? $all_tokens[ $symbol ] : null;
	}

	/**
	 * Validate token data
	 *
	 * @param array $token_data Token data to validate.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 * @since 1.0.0
	 */
	public function validate_token( $token_data ) {
		$required_fields = array( 'symbol', 'name', 'decimals', 'contract', 'chain_id' );

		foreach ( $required_fields as $field ) {
			if ( ! isset( $token_data[ $field ] ) || empty( $token_data[ $field ] ) ) {
				return new WP_Error(
					'missing_field',
					sprintf(
						/* translators: %s: field name */
						__( 'Missing required field: %s', 'goldt-web3' ),
						$field
					)
				);
			}
		}

		// Validate contract address.
		if ( ! goldt_sanitize_eth_address( $token_data['contract'] ) ) {
			return new WP_Error(
				'invalid_contract',
				__( 'Invalid contract address format', 'goldt-web3' )
			);
		}

		// Validate decimals.
		$decimals = absint( $token_data['decimals'] );
		if ( $decimals < 0 || $decimals > 18 ) {
			return new WP_Error(
				'invalid_decimals',
				__( 'Decimals must be between 0 and 18', 'goldt-web3' )
			);
		}

		// Validate chain ID.
		$chain_id = absint( $token_data['chain_id'] );
		if ( $chain_id <= 0 ) {
			return new WP_Error(
				'invalid_chain_id',
				__( 'Invalid chain ID', 'goldt-web3' )
			);
		}

		return true;
	}

	/**
	 * Add custom token
	 *
	 * @param array $token_data Token data.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 * @since 1.0.0
	 */
	public function add_custom_token( $token_data ) {
		// Validate token data.
		$validation = $this->validate_token( $token_data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$symbol = strtoupper( sanitize_text_field( $token_data['symbol'] ) );

		// Check if token already exists.
		if ( isset( $this->default_tokens[ $symbol ] ) || isset( $this->custom_tokens[ $symbol ] ) ) {
			return new WP_Error(
				'token_exists',
				__( 'Token with this symbol already exists', 'goldt-web3' )
			);
		}

		// Sanitize token data.
		$sanitized = array(
			'symbol'      => $symbol,
			'name'        => sanitize_text_field( $token_data['name'] ),
			'decimals'    => absint( $token_data['decimals'] ),
			'contract'    => goldt_sanitize_eth_address( $token_data['contract'] ),
			'chain_id'    => absint( $token_data['chain_id'] ),
			'type'        => isset( $token_data['type'] ) ? sanitize_text_field( $token_data['type'] ) : 'ERC20',
			'logo'        => isset( $token_data['logo'] ) ? esc_url_raw( $token_data['logo'] ) : '',
			'description' => isset( $token_data['description'] ) ? sanitize_textarea_field( $token_data['description'] ) : '',
		);

		// Add to custom tokens.
		$this->custom_tokens[ $symbol ] = $sanitized;

		// Save to database.
		update_option( 'goldt_custom_tokens', $this->custom_tokens );

		goldt_log( "Added custom token: {$symbol}" );

		return true;
	}

	/**
	 * Update token
	 *
	 * @param string $symbol     Token symbol.
	 * @param array  $token_data Updated token data.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 * @since 1.0.0
	 */
	public function update_token( $symbol, $token_data ) {
		$symbol = strtoupper( sanitize_text_field( $symbol ) );

		// Can only update custom tokens or default token contracts.
		if ( isset( $this->custom_tokens[ $symbol ] ) ) {
			// Validate new data.
			$validation = $this->validate_token( $token_data );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			// Update custom token.
			$this->custom_tokens[ $symbol ] = array_merge(
				$this->custom_tokens[ $symbol ],
				$token_data
			);

			update_option( 'goldt_custom_tokens', $this->custom_tokens );
			goldt_log( "Updated custom token: {$symbol}" );
			return true;
		}

		if ( isset( $this->default_tokens[ $symbol ] ) ) {
			// For default tokens, only allow updating the contract address.
			if ( isset( $token_data['contract'] ) ) {
				$contract = goldt_sanitize_eth_address( $token_data['contract'] );
				if ( ! $contract ) {
					return new WP_Error( 'invalid_contract', __( 'Invalid contract address', 'goldt-web3' ) );
				}

				$this->default_tokens[ $symbol ]['contract'] = $contract;
				update_option( 'goldt_default_token_contracts', array_column( $this->default_tokens, 'contract', 'symbol' ) );
				goldt_log( "Updated contract for default token: {$symbol}" );
				return true;
			}
		}

		return new WP_Error( 'token_not_found', __( 'Token not found', 'goldt-web3' ) );
	}

	/**
	 * Remove custom token
	 *
	 * @param string $symbol Token symbol.
	 * @return bool True on success, false on failure.
	 * @since 1.0.0
	 */
	public function remove_custom_token( $symbol ) {
		$symbol = strtoupper( sanitize_text_field( $symbol ) );

		// Can only remove custom tokens.
		if ( isset( $this->custom_tokens[ $symbol ] ) ) {
			unset( $this->custom_tokens[ $symbol ] );
			update_option( 'goldt_custom_tokens', $this->custom_tokens );
			goldt_log( "Removed custom token: {$symbol}" );
			return true;
		}

		return false;
	}

	/**
	 * Get enabled tokens
	 *
	 * @return array Array of enabled token symbols.
	 * @since 1.0.0
	 */
	public function get_enabled_tokens() {
		return goldt_get_setting( 'allowed_tokens', array( 'GOLDT', 'GOLDVE', 'BNBV', 'GPOOL', 'FGVAULT' ) );
	}

	/**
	 * Check if token is enabled
	 *
	 * @param string $symbol Token symbol.
	 * @return bool True if enabled.
	 * @since 1.0.0
	 */
	public function is_token_enabled( $symbol ) {
		$enabled = $this->get_enabled_tokens();
		return in_array( strtoupper( $symbol ), $enabled, true );
	}

	/**
	 * Get token contract address
	 *
	 * @param string $symbol Token symbol.
	 * @return string|false Contract address or false if not set.
	 * @since 1.0.0
	 */
	public function get_token_contract( $symbol ) {
		$token = $this->get_token( $symbol );
		return ( $token && ! empty( $token['contract'] ) ) ? $token['contract'] : false;
	}

	/**
	 * Get ERC20 token ABI
	 *
	 * Returns standard ERC20 ABI for token interactions.
	 *
	 * @return array ERC20 ABI.
	 * @since 1.0.0
	 */
	public function get_erc20_abi() {
		return array(
			// balanceOf.
			array(
				'constant'      => true,
				'inputs'        => array(
					array( 'name' => '_owner', 'type' => 'address' ),
				),
				'name'          => 'balanceOf',
				'outputs'       => array(
					array( 'name' => 'balance', 'type' => 'uint256' ),
				),
				'type'          => 'function',
			),
			// transfer.
			array(
				'constant'      => false,
				'inputs'        => array(
					array( 'name' => '_to', 'type' => 'address' ),
					array( 'name' => '_value', 'type' => 'uint256' ),
				),
				'name'          => 'transfer',
				'outputs'       => array(
					array( 'name' => '', 'type' => 'bool' ),
				),
				'type'          => 'function',
			),
			// decimals.
			array(
				'constant'      => true,
				'inputs'        => array(),
				'name'          => 'decimals',
				'outputs'       => array(
					array( 'name' => '', 'type' => 'uint8' ),
				),
				'type'          => 'function',
			),
		);
	}
}
