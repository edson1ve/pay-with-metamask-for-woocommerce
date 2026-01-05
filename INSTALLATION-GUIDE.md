# GOLDT Web3 Hybrid Payments - Installation & Usage Guide

## 📦 Quick Start

### Step 1: Installation

1. **Download** the plugin or clone from repository
2. **Upload** to `/wp-content/plugins/` directory
3. **Activate** the plugin from WordPress admin (Plugins → Installed Plugins)
4. You should see "GOLDT Web3 Hybrid Payments" activated

### Step 2: Basic Configuration

1. Navigate to **WooCommerce → Settings → Payments**
2. Find "GOLDT Web3 Hybrid Payments" and click **Manage**
3. **Essential Settings**:
   - ✅ Check "Enable GOLDT Web3 Hybrid Payments"
   - 🔑 Enter your **Wallet Address** (where you'll receive payments)
   - 🌐 Select your **Blockchain Network** (BSC recommended)
   - 🪙 Choose **Allowed Tokens** (GOLDT, GOLDVE, etc.)

4. Click **Save changes**

### Step 3: Test the Setup

1. Create a test product in WooCommerce
2. Add to cart and proceed to checkout
3. You should see "Cryptocurrency Payment" as an option
4. Select it to see MetaMask integration

## 🔧 Detailed Configuration

### Payment Gateway Settings

```
WooCommerce → Settings → Payments → GOLDT Web3 Hybrid Payments
```

#### 1. General Settings

| Setting | Description | Recommended Value |
|---------|-------------|-------------------|
| **Enable/Disable** | Turn gateway on/off | Enabled |
| **Title** | Name shown to customers | "Cryptocurrency Payment" |
| **Description** | Payment method description | "Pay with cryptocurrency using MetaMask..." |

#### 2. Web3 Settings

| Setting | Description | Recommended Value |
|---------|-------------|-------------------|
| **Enable Web3 Payments** | Allow MetaMask payments | Enabled |
| **Enable Web2 Fallback** | Allow traditional payments | Enabled (for users without MetaMask) |
| **Wallet Address** | Your receiving address | Your BSC/ETH wallet |
| **Blockchain Network** | Network for transactions | Binance Smart Chain (56) |

#### 3. Oracle Settings

| Setting | Description | Default Value |
|---------|-------------|---------------|
| **Oracle Endpoint** | GOLDT price API URL | `https://goldt.criptoinversiones.net/api/rates.php` |
| **Cache Duration** | Rate caching time | 60 seconds |

#### 4. Token Settings

| Setting | Description | Default Tokens |
|---------|-------------|----------------|
| **Allowed Tokens** | Tokens customers can use | GOLDT, GOLDVE, BNBV, GPOOL, FGVAULT |

#### 5. Order Settings

| Setting | Description | Recommended Value |
|---------|-------------|-------------------|
| **Payment Complete Status** | Order status after payment | Processing |

## 💳 Customer Experience

### For Customers WITH MetaMask

1. **At Checkout**:
   - Select "Cryptocurrency Payment"
   - Plugin automatically detects MetaMask
   - Shows available tokens

2. **Token Selection**:
   - Customer selects preferred token (GOLDT, GOLDVE, etc.)
   - Real-time price calculation displays
   - Shows exact token amount to pay

3. **Payment**:
   - Click "Pay with MetaMask"
   - MetaMask popup opens
   - Customer confirms transaction
   - Payment processes on blockchain

4. **Confirmation**:
   - Order status updates automatically
   - Transaction hash saved
   - Customer sees blockchain confirmation

### For Customers WITHOUT MetaMask

1. **At Checkout**:
   - Select "Cryptocurrency Payment"
   - Plugin detects no MetaMask
   - Shows Web2 fallback options

2. **Alternative Payment**:
   - Proceeds with traditional WooCommerce checkout
   - Uses standard payment gateways

## 🎨 Customization

### Token Configuration

#### View Available Tokens

```php
// In your theme's functions.php
add_action('init', function() {
    if (class_exists('GOLDT_Tokens')) {
        $tokens = GOLDT_Tokens::get_instance();
        $all_tokens = $tokens->get_all_tokens();
        
        foreach ($all_tokens as $symbol => $token) {
            error_log("Token: {$token['name']} ({$symbol})");
        }
    }
});
```

#### Add Custom Token

```php
add_action('init', function() {
    if (class_exists('GOLDT_Tokens')) {
        $tokens = GOLDT_Tokens::get_instance();
        
        $tokens->add_custom_token(array(
            'symbol'      => 'CUSTOM',
            'name'        => 'Custom Token',
            'decimals'    => 18,
            'contract'    => '0x1234...', // Your token contract
            'chain_id'    => 56, // BSC
            'type'        => 'ERC20',
            'logo'        => 'https://yoursite.com/logo.png',
            'description' => 'My custom payment token',
        ));
    }
});
```

### Styling Customization

Add custom CSS to your theme:

```css
/* Customize payment button */
.goldt-pay-button {
    background: #your-color !important;
    border-radius: 10px !important;
}

/* Customize token selector */
.goldt-select {
    border-color: #your-color !important;
}

/* Customize price display */
.goldt-price-display {
    background: #your-bg-color !important;
}
```

## 🔍 Monitoring & Management

### View Transactions

```
WooCommerce → GOLDT Transactions
```

Shows all cryptocurrency transactions with:
- Order ID (clickable to view order)
- Transaction hash (with explorer link)
- Token used and amounts
- Status and timestamp

### Individual Order Details

When viewing an order that used GOLDT payment, you'll see:
- Token symbol
- Amount paid in crypto
- Customer wallet address
- Transaction hash
- Link to blockchain explorer

### Transaction Logs

```
WooCommerce → Status → Logs
```

Select log source: `goldt-web3-hybrid-payments`

Shows detailed plugin activity including:
- Oracle requests
- Price calculations
- Transaction submissions
- Errors and warnings

## ⚠️ Troubleshooting

### Issue: MetaMask Not Detected

**Solution**:
1. Ensure MetaMask extension is installed
2. Check browser console for JavaScript errors
3. Verify Web3.js library is loading correctly
4. Clear browser cache

### Issue: Wrong Network Error

**Solution**:
1. Check your configured Chain ID in settings
2. Customer needs to switch to matching network in MetaMask
3. Verify network is supported (Ethereum, BSC, Polygon)

### Issue: Oracle Connection Failed

**Solution**:
1. Verify oracle endpoint URL is correct
2. Check server can access external URLs
3. Test oracle connection via admin panel
4. Check cache duration settings

### Issue: Transaction Not Updating Order

**Solution**:
1. Verify REST API is accessible (`/wp-json/goldt/v1/`)
2. Check permalinks (Settings → Permalinks → Save)
3. Ensure order ID is valid
4. Check WooCommerce order status settings

### Issue: Invalid Wallet Address

**Solution**:
1. Wallet address must start with `0x`
2. Must be exactly 42 characters long
3. Must contain only hexadecimal characters (0-9, a-f)
4. Example: `0x1234567890123456789012345678901234567890`

## 🔐 Security Best Practices

### 1. Wallet Security

✅ **DO**:
- Use a dedicated business wallet for receiving payments
- Keep private keys secure and offline
- Use hardware wallet for high-value transactions
- Regularly monitor wallet activity

❌ **DON'T**:
- Share private keys
- Use same wallet for testing and production
- Store private keys in plugin settings

### 2. Server Security

✅ **DO**:
- Keep WordPress and plugins updated
- Use SSL certificate (HTTPS)
- Enable WordPress security features
- Regular backups

❌ **DON'T**:
- Use outdated PHP versions
- Disable security plugins
- Ignore security updates

### 3. Testing

✅ **DO**:
- Test on testnet first (BSC Testnet, Goerli)
- Use small amounts for production testing
- Verify transaction confirmations
- Check order status updates

❌ **DON'T**:
- Test with production funds initially
- Skip testing on staging environment

## 📊 Performance Optimization

### 1. Cache Configuration

Optimal cache duration for oracle rates:
- **High traffic stores**: 30-60 seconds
- **Medium traffic**: 60-120 seconds  
- **Low traffic**: 120-300 seconds

### 2. Database Optimization

The plugin creates one database table. To optimize:

```sql
-- Run occasionally to clean old completed transactions
DELETE FROM wp_goldt_transactions 
WHERE status = 'completed' 
AND created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

### 3. Asset Loading

Assets only load on:
- Checkout page
- Order confirmation page
- Admin transaction pages

No impact on other pages.

## 🌐 Multi-Language Support

### Enable Translations

1. Install and activate WPML or Polylang
2. Navigate to translation settings
3. The plugin is translation-ready with text domain: `goldt-web3`

### Available Strings

All customer-facing text can be translated:
- Payment gateway title and description
- Button labels
- Error messages
- Transaction details

## 📈 Analytics & Reporting

### Track Cryptocurrency Sales

Use WooCommerce reports with payment method filter:

```
WooCommerce → Analytics → Orders
Filter by payment method: GOLDT Web3 Hybrid Payments
```

### Custom Reporting

Query transactions programmatically:

```php
global $wpdb;
$table = $wpdb->prefix . 'goldt_transactions';

// Get total volume by token
$results = $wpdb->get_results("
    SELECT token_symbol, 
           COUNT(*) as count,
           SUM(amount_fiat) as total_fiat,
           SUM(amount_token) as total_tokens
    FROM $table
    WHERE status = 'completed'
    GROUP BY token_symbol
");
```

## 🚀 Going Live Checklist

Before launching to production:

- [ ] Tested on testnet successfully
- [ ] Wallet address configured correctly
- [ ] Oracle endpoint returning valid data
- [ ] All allowed tokens configured with contracts
- [ ] SSL certificate installed (HTTPS)
- [ ] Tested complete checkout flow
- [ ] Verified order status updates
- [ ] Transaction logging working
- [ ] Block explorer links working
- [ ] Admin notifications configured
- [ ] Customer email templates reviewed
- [ ] Backup system in place
- [ ] Monitoring enabled

## 📞 Getting Help

- **Documentation**: README-GOLDT.md
- **GitHub Issues**: https://github.com/edson1ve/goldt-web3-hybrid-payments/issues
- **WooCommerce Logs**: Check `goldt-web3-hybrid-payments` log source
- **WordPress Debug**: Enable WP_DEBUG for detailed error messages

## 🎓 Learning Resources

### Understanding Blockchain Payments

- [MetaMask Documentation](https://docs.metamask.io/)
- [Web3.js Documentation](https://web3js.readthedocs.io/)
- [ERC-20 Token Standard](https://ethereum.org/en/developers/docs/standards/tokens/erc-20/)
- [BSC Documentation](https://docs.bnbchain.org/)

### WooCommerce Development

- [WooCommerce Documentation](https://woocommerce.com/documentation/)
- [WooCommerce REST API](https://woocommerce.github.io/woocommerce-rest-api-docs/)

---

**Need more help?** Open an issue on GitHub or consult the comprehensive README-GOLDT.md file.
