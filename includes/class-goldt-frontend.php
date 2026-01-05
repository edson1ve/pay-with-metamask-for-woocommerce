<?php
/**
 * GOLDT Frontend Class
 *
 * Handles all frontend functionality.
 *
 * @package GOLDT_Web3_Hybrid_Payments
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GOLDT_Frontend
 *
 * Manages frontend displays, assets, and user interactions.
 *
 * @since 1.0.0
 */
class GOLDT_Frontend {

	/**
	 * Single instance of the class
	 *
	 * @var GOLDT_Frontend|null
	 */
	private static $instance = null;

	/**
	 * Get single instance
	 *
	 * @return GOLDT_Frontend
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
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 *
	 * @since 1.0.0
	 */
	private function register_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'display_transaction_details' ) );
	}

	/**
	 * Enqueue frontend assets
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets() {
		// Only on checkout and order pages.
		if ( ! is_checkout() && ! is_order_received_page() ) {
			return;
		}

		// Enqueue styles.
		wp_enqueue_style(
			'goldt-frontend',
			GOLDT_URL . 'assets/css/frontend.css',
			array(),
			GOLDT_VERSION
		);

		if ( is_checkout() ) {
			// Enqueue scripts.
			wp_enqueue_script(
				'goldt-frontend',
				GOLDT_URL . 'assets/js/frontend.js',
				array( 'jquery' ),
				GOLDT_VERSION,
				true
			);
		}
	}

	/**
	 * Display transaction details on order page
	 *
	 * @param WC_Order $order Order object.
	 * @since 1.0.0
	 */
	public function display_transaction_details( $order ) {
		// Check if this was a GOLDT payment.
		if ( 'goldt_gateway' !== $order->get_payment_method() ) {
			return;
		}

		$tx_hash = $order->get_meta( '_goldt_tx_hash' );
		if ( ! $tx_hash ) {
			return;
		}

		$token_symbol = $order->get_meta( '_goldt_token_symbol' );
		$token_amount = $order->get_meta( '_goldt_token_amount' );
		$from_address = $order->get_meta( '_goldt_from_address' );
		$chain_id = goldt_get_setting( 'chain_id', 56 );

		?>
		<section class="goldt-transaction-details">
			<h2><?php esc_html_e( 'Blockchain Transaction Details', 'goldt-web3' ); ?></h2>
			<table class="woocommerce-table goldt-details-table">
				<tbody>
					<tr>
						<th><?php esc_html_e( 'Token:', 'goldt-web3' ); ?></th>
						<td><?php echo esc_html( $token_symbol ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Amount Paid:', 'goldt-web3' ); ?></th>
						<td><?php echo esc_html( goldt_format_crypto_amount( $token_amount ) ); ?> <?php echo esc_html( $token_symbol ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'From Address:', 'goldt-web3' ); ?></th>
						<td><code class="goldt-address"><?php echo esc_html( $from_address ); ?></code></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Transaction Hash:', 'goldt-web3' ); ?></th>
						<td><code class="goldt-tx-hash"><?php echo esc_html( $tx_hash ); ?></code></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Network:', 'goldt-web3' ); ?></th>
						<td><?php echo esc_html( goldt_get_network_name( $chain_id ) ); ?></td>
					</tr>
					<?php
					$explorer_url = goldt_get_explorer_url( $tx_hash, $chain_id );
					if ( $explorer_url ) :
						?>
					<tr>
						<th><?php esc_html_e( 'Block Explorer:', 'goldt-web3' ); ?></th>
						<td>
							<a href="<?php echo esc_url( $explorer_url ); ?>" target="_blank" rel="noopener noreferrer" class="goldt-explorer-link">
								<?php esc_html_e( 'View Transaction', 'goldt-web3' ); ?> →
							</a>
						</td>
					</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</section>
		<?php
	}
}
