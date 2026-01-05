<?php
/**
 * GOLDT Admin Class
 *
 * Handles all admin functionality.
 *
 * @package GOLDT_Web3_Hybrid_Payments
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GOLDT_Admin
 *
 * Manages admin panel, settings, and transaction management.
 *
 * @since 1.0.0
 */
class GOLDT_Admin {

	/**
	 * Single instance of the class
	 *
	 * @var GOLDT_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get single instance
	 *
	 * @return GOLDT_Admin
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'handle_custom_query_var' ), 10, 2 );
	}

	/**
	 * Add admin menu pages
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		// Transactions page under WooCommerce.
		add_submenu_page(
			'woocommerce',
			__( 'GOLDT Transactions', 'goldt-web3' ),
			__( 'GOLDT Transactions', 'goldt-web3' ),
			'manage_woocommerce',
			'goldt-transactions',
			array( $this, 'render_transactions_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 * @since 1.0.0
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only on GOLDT admin pages.
		if ( false === strpos( $hook, 'goldt' ) && false === strpos( $hook, 'wc-settings' ) ) {
			return;
		}

		// Admin styles.
		wp_enqueue_style(
			'goldt-admin',
			GOLDT_URL . 'assets/css/admin.css',
			array(),
			GOLDT_VERSION
		);

		// Admin scripts.
		wp_enqueue_script(
			'goldt-admin',
			GOLDT_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			GOLDT_VERSION,
			true
		);

		wp_localize_script(
			'goldt-admin',
			'goldtAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => goldt_create_nonce( 'goldt_admin' ),
			)
		);
	}

	/**
	 * Register plugin settings
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting( 'goldt_settings_group', 'goldt_settings' );
	}

	/**
	 * Display admin notices
	 *
	 * @since 1.0.0
	 */
	public function display_admin_notices() {
		// Check if wallet address is set.
		$wallet_address = goldt_get_setting( 'wallet_address' );
		if ( empty( $wallet_address ) && $this->is_goldt_settings_page() ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: settings page URL */
							__( '<strong>GOLDT Web3 Hybrid Payments:</strong> Please configure your wallet address in the %s.', 'goldt-web3' ),
							'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=goldt_gateway' ) ) . '">' . __( 'payment settings', 'goldt-web3' ) . '</a>'
						)
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Check if current page is GOLDT settings page
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	private function is_goldt_settings_page() {
		$screen = get_current_screen();
		return $screen && ( false !== strpos( $screen->id, 'wc-settings' ) || false !== strpos( $screen->id, 'goldt' ) );
	}

	/**
	 * Render transactions page
	 *
	 * @since 1.0.0
	 */
	public function render_transactions_page() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'goldt_transactions';
		$per_page = 20;
		$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$offset = ( $current_page - 1 ) * $per_page;

		// Get total count.
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
		$total_pages = ceil( $total_items / $per_page );

		// Get transactions.
		$transactions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		?>
		<div class="wrap goldt-admin-page">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'GOLDT Transactions', 'goldt-web3' ); ?></h1>
			
			<hr class="wp-header-end">

			<?php if ( empty( $transactions ) ) : ?>
				<div class="goldt-no-transactions">
					<p><?php esc_html_e( 'No transactions found.', 'goldt-web3' ); ?></p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped goldt-transactions-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'ID', 'goldt-web3' ); ?></th>
							<th><?php esc_html_e( 'Order', 'goldt-web3' ); ?></th>
							<th><?php esc_html_e( 'Transaction Hash', 'goldt-web3' ); ?></th>
							<th><?php esc_html_e( 'Token', 'goldt-web3' ); ?></th>
							<th><?php esc_html_e( 'Amount (Fiat)', 'goldt-web3' ); ?></th>
							<th><?php esc_html_e( 'Amount (Token)', 'goldt-web3' ); ?></th>
							<th><?php esc_html_e( 'Status', 'goldt-web3' ); ?></th>
							<th><?php esc_html_e( 'Date', 'goldt-web3' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $transactions as $transaction ) : ?>
							<tr>
								<td><?php echo absint( $transaction->id ); ?></td>
								<td>
									<?php
									$order = wc_get_order( $transaction->order_id );
									if ( $order ) {
										echo '<a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . absint( $transaction->order_id ) . '</a>';
									} else {
										echo '#' . absint( $transaction->order_id );
									}
									?>
								</td>
								<td>
									<code class="goldt-tx-hash-short" title="<?php echo esc_attr( $transaction->tx_hash ); ?>">
										<?php echo esc_html( substr( $transaction->tx_hash, 0, 10 ) . '...' . substr( $transaction->tx_hash, -8 ) ); ?>
									</code>
									<?php
									$explorer_url = goldt_get_explorer_url( $transaction->tx_hash, $transaction->chain_id );
									if ( $explorer_url ) :
										?>
										<a href="<?php echo esc_url( $explorer_url ); ?>" target="_blank" rel="noopener" class="goldt-explorer-icon" title="<?php esc_attr_e( 'View on explorer', 'goldt-web3' ); ?>">
											<span class="dashicons dashicons-external"></span>
										</a>
									<?php endif; ?>
								</td>
								<td><strong><?php echo esc_html( $transaction->token_symbol ); ?></strong></td>
								<td><?php echo wc_price( $transaction->amount_fiat ); ?></td>
								<td><?php echo esc_html( goldt_format_crypto_amount( $transaction->amount_token ) ); ?> <?php echo esc_html( $transaction->token_symbol ); ?></td>
								<td>
									<span class="goldt-status goldt-status-<?php echo esc_attr( $transaction->status ); ?>">
										<?php echo esc_html( ucfirst( $transaction->status ) ); ?>
									</span>
								</td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $transaction->created_at ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( $total_pages > 1 ) : ?>
					<div class="tablenav bottom">
						<div class="tablenav-pages">
							<?php
							echo paginate_links(
								array(
									'base'      => add_query_arg( 'paged', '%#%' ),
									'format'    => '',
									'prev_text' => __( '&laquo;', 'goldt-web3' ),
									'next_text' => __( '&raquo;', 'goldt-web3' ),
									'total'     => $total_pages,
									'current'   => $current_page,
								)
							);
							?>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle custom query vars for orders
	 *
	 * @param array $query      Query vars.
	 * @param array $query_vars Query vars.
	 * @return array
	 * @since 1.0.0
	 */
	public function handle_custom_query_var( $query, $query_vars ) {
		if ( ! empty( $query_vars['goldt_payment'] ) ) {
			$query['meta_query'][] = array(
				'key'   => '_payment_method',
				'value' => 'goldt_gateway',
			);
		}

		return $query;
	}
}
