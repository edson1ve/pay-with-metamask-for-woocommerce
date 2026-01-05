<?php
/**
 * GOLDT WooCommerce Gateway Class
 *
 * Implements the WooCommerce payment gateway for GOLDT Web3 Hybrid Payments.
 *
 * @package GOLDT_Web3_Hybrid_Payments
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GOLDT_WooCommerce_Gateway
 *
 * Main payment gateway class that extends WC_Payment_Gateway.
 *
 * @since 1.0.0
 */
class GOLDT_WooCommerce_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id = 'goldt_gateway';
		$this->icon = GOLDT_URL . 'assets/img/goldt-icon.png';
		$this->has_fields = true;
		$this->method_title = __( 'GOLDT Web3 Hybrid Payments', 'goldt-web3' );
		$this->method_description = __( 'Accept cryptocurrency payments with MetaMask (Web3) or traditional payment methods (Web2) as fallback.', 'goldt-web3' );

		// Load settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled = $this->get_option( 'enabled' );
		$this->web3_enabled = $this->get_option( 'web3_enabled' );
		$this->web2_fallback = $this->get_option( 'web2_fallback' );
		$this->wallet_address = $this->get_option( 'wallet_address' );
		$this->chain_id = $this->get_option( 'chain_id' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

		// Custom hooks.
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
	}

	/**
	 * Initialize gateway settings form fields
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'goldt-web3' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable GOLDT Web3 Hybrid Payments', 'goldt-web3' ),
				'default' => 'yes',
			),
			'title' => array(
				'title'       => __( 'Title', 'goldt-web3' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'goldt-web3' ),
				'default'     => __( 'Cryptocurrency Payment', 'goldt-web3' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'goldt-web3' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'goldt-web3' ),
				'default'     => __( 'Pay with cryptocurrency using MetaMask or choose a traditional payment method.', 'goldt-web3' ),
				'desc_tip'    => true,
			),
			'web3_settings' => array(
				'title'       => __( 'Web3 Settings', 'goldt-web3' ),
				'type'        => 'title',
				'description' => __( 'Configure Web3 and MetaMask settings.', 'goldt-web3' ),
			),
			'web3_enabled' => array(
				'title'   => __( 'Enable Web3 Payments', 'goldt-web3' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable MetaMask and Web3 payments', 'goldt-web3' ),
				'default' => 'yes',
			),
			'web2_fallback' => array(
				'title'   => __( 'Enable Web2 Fallback', 'goldt-web3' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable traditional payment methods as fallback', 'goldt-web3' ),
				'default' => 'yes',
			),
			'wallet_address' => array(
				'title'       => __( 'Receiving Wallet Address', 'goldt-web3' ),
				'type'        => 'text',
				'description' => __( 'Your Ethereum/BSC wallet address to receive payments.', 'goldt-web3' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'chain_id' => array(
				'title'       => __( 'Blockchain Network', 'goldt-web3' ),
				'type'        => 'select',
				'description' => __( 'Select the blockchain network for payments.', 'goldt-web3' ),
				'default'     => '56',
				'options'     => array(
					'1'     => __( 'Ethereum Mainnet', 'goldt-web3' ),
					'5'     => __( 'Goerli Testnet', 'goldt-web3' ),
					'56'    => __( 'Binance Smart Chain', 'goldt-web3' ),
					'97'    => __( 'BSC Testnet', 'goldt-web3' ),
					'137'   => __( 'Polygon', 'goldt-web3' ),
					'80001' => __( 'Mumbai Testnet', 'goldt-web3' ),
				),
				'desc_tip'    => true,
			),
			'oracle_settings' => array(
				'title'       => __( 'Oracle Settings', 'goldt-web3' ),
				'type'        => 'title',
				'description' => __( 'Configure GOLDT oracle endpoint.', 'goldt-web3' ),
			),
			'oracle_endpoint' => array(
				'title'       => __( 'Oracle Endpoint', 'goldt-web3' ),
				'type'        => 'text',
				'description' => __( 'GOLDT oracle API endpoint URL.', 'goldt-web3' ),
				'default'     => GOLDT_ORACLE_ENDPOINT,
				'desc_tip'    => true,
			),
			'cache_duration' => array(
				'title'       => __( 'Cache Duration', 'goldt-web3' ),
				'type'        => 'number',
				'description' => __( 'How long to cache oracle rates (in seconds).', 'goldt-web3' ),
				'default'     => '60',
				'desc_tip'    => true,
			),
			'token_settings' => array(
				'title'       => __( 'Token Settings', 'goldt-web3' ),
				'type'        => 'title',
				'description' => __( 'Configure accepted tokens.', 'goldt-web3' ),
			),
			'allowed_tokens' => array(
				'title'       => __( 'Allowed Tokens', 'goldt-web3' ),
				'type'        => 'multiselect',
				'description' => __( 'Select which tokens customers can use for payment.', 'goldt-web3' ),
				'default'     => array( 'GOLDT', 'GOLDVE', 'BNBV', 'GPOOL', 'FGVAULT' ),
				'options'     => array(
					'GOLDT'    => 'GOLDT',
					'GOLDVE'   => 'GOLDVE',
					'BNBV'     => 'BNBV',
					'GPOOL'    => 'GPOOL',
					'FGVAULT'  => 'FGVAULT',
				),
				'desc_tip'    => true,
			),
			'order_settings' => array(
				'title'       => __( 'Order Settings', 'goldt-web3' ),
				'type'        => 'title',
				'description' => __( 'Configure order processing.', 'goldt-web3' ),
			),
			'payment_status' => array(
				'title'       => __( 'Payment Complete Status', 'goldt-web3' ),
				'type'        => 'select',
				'description' => __( 'Order status after successful payment.', 'goldt-web3' ),
				'default'     => 'processing',
				'options'     => array(
					'processing' => __( 'Processing', 'goldt-web3' ),
					'completed'  => __( 'Completed', 'goldt-web3' ),
					'on-hold'    => __( 'On Hold', 'goldt-web3' ),
				),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Process admin options
	 *
	 * Save settings and update global options.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function process_admin_options() {
		$saved = parent::process_admin_options();

		// Update global settings.
		goldt_update_settings(
			array(
				'enabled'         => $this->get_option( 'enabled' ),
				'web3_enabled'    => $this->get_option( 'web3_enabled' ),
				'web2_fallback'   => $this->get_option( 'web2_fallback' ),
				'wallet_address'  => $this->get_option( 'wallet_address' ),
				'chain_id'        => $this->get_option( 'chain_id' ),
				'oracle_endpoint' => $this->get_option( 'oracle_endpoint' ),
				'cache_duration'  => absint( $this->get_option( 'cache_duration' ) ),
				'allowed_tokens'  => $this->get_option( 'allowed_tokens' ),
				'payment_status'  => $this->get_option( 'payment_status' ),
			)
		);

		// Clear oracle cache when settings change.
		GOLDT_Oracle::get_instance()->clear_cache();

		return $saved;
	}

	/**
	 * Payment fields on checkout page
	 *
	 * @since 1.0.0
	 */
	public function payment_fields() {
		if ( $this->description ) {
			echo wpautop( wptexturize( esc_html( $this->description ) ) );
		}

		// Check if Web3 is enabled.
		$web3_enabled = 'yes' === $this->get_option( 'web3_enabled' );
		$web2_fallback = 'yes' === $this->get_option( 'web2_fallback' );

		if ( ! $web3_enabled && ! $web2_fallback ) {
			echo '<p class="goldt-error">' . esc_html__( 'Payment method not properly configured.', 'goldt-web3' ) . '</p>';
			return;
		}

		?>
		<div id="goldt-payment-fields" class="goldt-payment-container">
			<?php if ( $web3_enabled ) : ?>
				<div id="goldt-web3-section" class="goldt-payment-section">
					<h4><?php esc_html_e( 'Pay with Cryptocurrency', 'goldt-web3' ); ?></h4>
					<div id="goldt-metamask-detector" class="goldt-detector">
						<span class="goldt-loading"><?php esc_html_e( 'Detecting MetaMask...', 'goldt-web3' ); ?></span>
					</div>
					<div id="goldt-token-selector" class="goldt-token-selector" style="display:none;">
						<label for="goldt_selected_token"><?php esc_html_e( 'Select Token:', 'goldt-web3' ); ?></label>
						<select id="goldt_selected_token" name="goldt_selected_token" class="goldt-select">
							<?php
							$tokens = GOLDT_Tokens::get_instance();
							$allowed_tokens = $this->get_option( 'allowed_tokens' );
							foreach ( $allowed_tokens as $symbol ) {
								$token = $tokens->get_token( $symbol );
								if ( $token ) {
									echo '<option value="' . esc_attr( $symbol ) . '">' . esc_html( $token['name'] ) . ' (' . esc_html( $symbol ) . ')</option>';
								}
							}
							?>
						</select>
						<div id="goldt-price-display" class="goldt-price-display">
							<span class="goldt-loading"><?php esc_html_e( 'Calculating...', 'goldt-web3' ); ?></span>
						</div>
					</div>
					<button type="button" id="goldt-pay-metamask" class="button alt goldt-pay-button" style="display:none;">
						<?php esc_html_e( 'Pay with MetaMask', 'goldt-web3' ); ?>
					</button>
				</div>
			<?php endif; ?>

			<?php if ( $web2_fallback ) : ?>
				<div id="goldt-web2-section" class="goldt-payment-section" style="<?php echo $web3_enabled ? 'margin-top: 20px;' : ''; ?>">
					<h4><?php esc_html_e( 'Traditional Payment', 'goldt-web3' ); ?></h4>
					<p><?php esc_html_e( 'Complete the checkout to proceed with standard payment methods.', 'goldt-web3' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Load payment scripts
	 *
	 * @since 1.0.0
	 */
	public function payment_scripts() {
		if ( ! is_checkout() ) {
			return;
		}

		if ( 'no' === $this->enabled ) {
			return;
		}

		// Styles.
		wp_enqueue_style(
			'goldt-checkout',
			GOLDT_URL . 'assets/css/checkout.css',
			array(),
			GOLDT_VERSION
		);

		// Scripts are enqueued by GOLDT_Web3 class.
	}

	/**
	 * Process payment
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 * @since 1.0.0
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Check if this is a Web3 payment (will have transaction hash).
		$tx_hash = $order->get_meta( '_goldt_tx_hash' );

		if ( $tx_hash ) {
			// Web3 payment already processed via REST API.
			// Mark as complete and redirect.
			$order->payment_complete( $tx_hash );

			// Return success.
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		} elseif ( 'yes' === $this->get_option( 'web2_fallback' ) ) {
			// Web2 fallback - mark as pending and redirect.
			$order->update_status( 'pending', __( 'Awaiting traditional payment.', 'goldt-web3' ) );

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		} else {
			// No payment method available.
			wc_add_notice( __( 'Payment error: No valid payment method selected.', 'goldt-web3' ), 'error' );
			return array(
				'result' => 'failure',
			);
		}
	}

	/**
	 * Output for the order received page
	 *
	 * @param int $order_id Order ID.
	 * @since 1.0.0
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );

		$tx_hash = $order->get_meta( '_goldt_tx_hash' );
		$token_symbol = $order->get_meta( '_goldt_token_symbol' );
		$token_amount = $order->get_meta( '_goldt_token_amount' );

		if ( $tx_hash ) {
			$explorer_url = goldt_get_explorer_url( $tx_hash, $this->chain_id );
			?>
			<div class="goldt-thankyou-section">
				<h2><?php esc_html_e( 'Payment Details', 'goldt-web3' ); ?></h2>
				<ul class="goldt-payment-details">
					<li>
						<strong><?php esc_html_e( 'Token:', 'goldt-web3' ); ?></strong>
						<?php echo esc_html( $token_symbol ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Amount:', 'goldt-web3' ); ?></strong>
						<?php echo esc_html( goldt_format_crypto_amount( $token_amount ) ); ?> <?php echo esc_html( $token_symbol ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Transaction Hash:', 'goldt-web3' ); ?></strong>
						<code><?php echo esc_html( $tx_hash ); ?></code>
					</li>
					<?php if ( $explorer_url ) : ?>
					<li>
						<a href="<?php echo esc_url( $explorer_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'View on Block Explorer', 'goldt-web3' ); ?>
						</a>
					</li>
					<?php endif; ?>
				</ul>
			</div>
			<?php
		}
	}

	/**
	 * Receipt page
	 *
	 * @param int $order_id Order ID.
	 * @since 1.0.0
	 */
	public function receipt_page( $order_id ) {
		echo '<p>' . esc_html__( 'Thank you for your order.', 'goldt-web3' ) . '</p>';
	}
}
