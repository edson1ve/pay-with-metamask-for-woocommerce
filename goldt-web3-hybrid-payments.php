<?php
/**
 * Plugin Name: GOLDT Web3 Hybrid Payments
 * Plugin URI: https://github.com/edson1ve/goldt-web3-hybrid-payments
 * Description: Professional Web3 hybrid payment gateway for WooCommerce. Supports MetaMask payments with GOLDT ecosystem tokens (GOLDT, GOLDVE, BNBV, GPOOL, FGVAULT) and traditional Web2 fallback. Integrated with GOLDT oracle for real-time pricing.
 * Version: 1.0.0
 * Author: GOLDT Development Team
 * Author URI: https://goldt.criptoinversiones.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: goldt-web3
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 *
 * @package GOLDT_Web3_Hybrid_Payments
 */

/*
Copyright (C) 2026  GOLDT Development Team

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'GOLDT_VERSION', '1.0.0' );
define( 'GOLDT_FILE', __FILE__ );
define( 'GOLDT_PATH', plugin_dir_path( GOLDT_FILE ) );
define( 'GOLDT_URL', plugin_dir_url( GOLDT_FILE ) );
define( 'GOLDT_BASENAME', plugin_basename( GOLDT_FILE ) );

// Oracle endpoint.
if ( ! defined( 'GOLDT_ORACLE_ENDPOINT' ) ) {
	define( 'GOLDT_ORACLE_ENDPOINT', 'https://goldt.criptoinversiones.net/api/rates.php' );
}

/**
 * Main GOLDT Web3 Hybrid Payments class
 *
 * This is the main class that initializes the plugin and loads all required files.
 * It follows WordPress coding standards and best practices.
 *
 * @since 1.0.0
 */
final class GOLDT_Web3_Hybrid_Payments {

	/**
	 * Single instance of the class
	 *
	 * @var GOLDT_Web3_Hybrid_Payments|null
	 */
	private static $instance = null;

	/**
	 * Plugin loader instance
	 *
	 * @var GOLDT_Loader|null
	 */
	private $loader = null;

	/**
	 * Get single instance of the class
	 *
	 * @return GOLDT_Web3_Hybrid_Payments
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - Initialize the plugin
	 *
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->set_locale();
		$this->register_hooks();
	}

	/**
	 * Load required dependencies
	 *
	 * Includes all necessary files for the plugin to function.
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		// Load helper functions first.
		require_once GOLDT_PATH . 'includes/helpers.php';

		// Load core classes.
		require_once GOLDT_PATH . 'includes/class-goldt-loader.php';
		require_once GOLDT_PATH . 'includes/class-goldt-oracle.php';
		require_once GOLDT_PATH . 'includes/class-goldt-tokens.php';
		require_once GOLDT_PATH . 'includes/class-goldt-web3.php';

		// Load WooCommerce integration.
		require_once GOLDT_PATH . 'includes/class-goldt-woocommerce.php';

		// Load admin and frontend classes.
		if ( is_admin() ) {
			require_once GOLDT_PATH . 'includes/class-goldt-admin.php';
		}
		require_once GOLDT_PATH . 'includes/class-goldt-frontend.php';

		// Initialize the loader.
		$this->loader = new GOLDT_Loader();
	}

	/**
	 * Set plugin locale for internationalization
	 *
	 * @since 1.0.0
	 */
	private function set_locale() {
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
	}

	/**
	 * Load plugin text domain for translations
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'goldt-web3',
			false,
			dirname( GOLDT_BASENAME ) . '/languages/'
		);
	}

	/**
	 * Register all hooks with WordPress
	 *
	 * @since 1.0.0
	 */
	private function register_hooks() {
		// Activation and deactivation hooks.
		register_activation_hook( GOLDT_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( GOLDT_FILE, array( $this, 'deactivate' ) );

		// Check for WooCommerce dependency.
		add_action( 'plugins_loaded', array( $this, 'check_woocommerce' ), 10 );

		// Add payment gateway to WooCommerce.
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway_class' ) );

		// Add plugin action links.
		add_filter( 'plugin_action_links_' . GOLDT_BASENAME, array( $this, 'add_action_links' ) );

		// Initialize components.
		add_action( 'plugins_loaded', array( $this, 'init_components' ), 20 );
	}

	/**
	 * Check if WooCommerce is active
	 *
	 * Display admin notice if WooCommerce is not active.
	 *
	 * @since 1.0.0
	 */
	public function check_woocommerce() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}
	}

	/**
	 * Display WooCommerce missing notice
	 *
	 * @since 1.0.0
	 */
	public function woocommerce_missing_notice() {
		?>
		<div class="error">
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s: WooCommerce plugin link */
						__( '<strong>GOLDT Web3 Hybrid Payments</strong> requires WooCommerce to be installed and active. Please %s WooCommerce.', 'goldt-web3' ),
						'<a href="' . esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) ) . '">' . __( 'install', 'goldt-web3' ) . '</a>'
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add GOLDT gateway to WooCommerce
	 *
	 * @param array $gateways Existing gateways.
	 * @return array Modified gateways array.
	 * @since 1.0.0
	 */
	public function add_gateway_class( $gateways ) {
		if ( class_exists( 'WooCommerce' ) ) {
			$gateways[] = 'GOLDT_WooCommerce_Gateway';
		}
		return $gateways;
	}

	/**
	 * Add plugin action links
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 * @since 1.0.0
	 */
	public function add_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=goldt_gateway' ) ) . '">' . __( 'Settings', 'goldt-web3' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=goldt-transactions' ) ) . '">' . __( 'Transactions', 'goldt-web3' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Initialize plugin components
	 *
	 * @since 1.0.0
	 */
	public function init_components() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Initialize oracle.
		GOLDT_Oracle::get_instance();

		// Initialize tokens manager.
		GOLDT_Tokens::get_instance();

		// Initialize Web3 handler.
		GOLDT_Web3::get_instance();

		// Initialize frontend.
		GOLDT_Frontend::get_instance();

		// Initialize admin if in admin area.
		if ( is_admin() ) {
			GOLDT_Admin::get_instance();
		}
	}

	/**
	 * Plugin activation
	 *
	 * Runs when the plugin is activated.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		// Set default options.
		$default_options = array(
			'enabled'           => 'yes',
			'web3_enabled'      => 'yes',
			'web2_fallback'     => 'yes',
			'chain_id'          => '56', // BSC by default.
			'wallet_address'    => '',
			'oracle_endpoint'   => GOLDT_ORACLE_ENDPOINT,
			'cache_duration'    => 60,
			'allowed_tokens'    => array( 'GOLDT', 'GOLDVE', 'BNBV', 'GPOOL', 'FGVAULT' ),
			'payment_status'    => 'processing',
		);

		// Only set defaults if not already set.
		if ( false === get_option( 'goldt_settings' ) ) {
			add_option( 'goldt_settings', $default_options );
		}

		// Create database tables if needed.
		$this->create_database_tables();

		// Set activation timestamp.
		add_option( 'goldt_activated_time', time() );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Create database tables
	 *
	 * @since 1.0.0
	 */
	private function create_database_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'goldt_transactions';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			order_id bigint(20) NOT NULL,
			tx_hash varchar(66) NOT NULL,
			from_address varchar(42) NOT NULL,
			to_address varchar(42) NOT NULL,
			token_symbol varchar(20) NOT NULL,
			token_address varchar(42) NOT NULL,
			amount_fiat decimal(20,2) NOT NULL,
			amount_token decimal(30,10) NOT NULL,
			chain_id int(11) NOT NULL,
			oracle_rate decimal(30,10) NOT NULL,
			oracle_timestamp bigint(20) NOT NULL,
			status varchar(20) DEFAULT 'pending',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY order_id (order_id),
			KEY tx_hash (tx_hash),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Plugin deactivation
	 *
	 * Runs when the plugin is deactivated.
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {
		// Clear all transients.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_goldt_%' OR option_name LIKE '_transient_timeout_goldt_%'" );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Get loader instance
	 *
	 * @return GOLDT_Loader
	 * @since 1.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}
}

/**
 * Initialize the plugin
 *
 * @return GOLDT_Web3_Hybrid_Payments
 */
function goldt_web3_hybrid_payments() {
	return GOLDT_Web3_Hybrid_Payments::get_instance();
}

// Start the plugin.
goldt_web3_hybrid_payments();
