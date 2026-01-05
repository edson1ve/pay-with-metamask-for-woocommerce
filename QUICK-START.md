# ⚡ GOLDT Web3 Hybrid Payments - Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Install the Plugin (1 minute)

**Option A: Upload via WordPress Admin**
1. Download plugin ZIP from GitHub
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose ZIP file and click "Install Now"
5. Click "Activate"

**Option B: Manual Upload**
1. Upload `goldt-web3-hybrid-payments` folder to `/wp-content/plugins/`
2. Go to WordPress Admin → Plugins
3. Find "GOLDT Web3 Hybrid Payments"
4. Click "Activate"

### Step 2: Basic Configuration (2 minutes)

1. Navigate to **WooCommerce → Settings → Payments**
2. Find **"GOLDT Web3 Hybrid Payments"**
3. Click **"Manage"**

#### Minimum Required Settings:

```
✅ Enable GOLDT Web3 Hybrid Payments: [✓]
✅ Wallet Address: 0xYourWalletAddress
✅ Blockchain Network: Binance Smart Chain (56)
✅ Allowed Tokens: [✓] GOLDT [✓] GOLDVE [✓] BNBV
```

4. Click **"Save changes"**

### Step 3: Test It! (2 minutes)

1. Create a test product
2. Add to cart → Checkout
3. Select "Cryptocurrency Payment"
4. See MetaMask integration in action!

## 🎯 That's It!

Your store now accepts cryptocurrency payments via MetaMask!

## 📖 Next Steps

- Read [INSTALLATION-GUIDE.md](INSTALLATION-GUIDE.md) for detailed configuration
- Read [README-GOLDT.md](README-GOLDT.md) for complete documentation
- Check [PROJECT-SUMMARY.md](PROJECT-SUMMARY.md) for technical details

## ⚙️ Configuration Examples

### Testnet Configuration (for testing)
```
Network: BSC Testnet (97)
Wallet: Your testnet wallet address
Tokens: GOLDT, GOLDVE
```

### Mainnet Configuration (for production)
```
Network: Binance Smart Chain (56)
Wallet: Your production wallet address
Tokens: All 5 tokens enabled
Oracle Cache: 60 seconds
```

## 🔧 Token Contract Configuration

After initial setup, configure token contracts:

1. Go to **WooCommerce → GOLDT Transactions**
2. Configure contract addresses for each token
3. Contract addresses should be obtained from GOLDT team

Example:
```
GOLDT: 0x1234567890123456789012345678901234567890
GOLDVE: 0x2345678901234567890123456789012345678901
BNBV: 0x3456789012345678901234567890123456789012
```

## ✅ Verification Checklist

After setup, verify:

- [ ] Plugin is activated
- [ ] No error messages in admin
- [ ] Payment gateway appears at checkout
- [ ] MetaMask detection works
- [ ] Token selector shows tokens
- [ ] Price calculation works
- [ ] Oracle endpoint responds (check logs)

## 🆘 Quick Troubleshooting

**Issue**: MetaMask not detected  
**Fix**: Ensure MetaMask extension is installed in browser

**Issue**: Wrong network error  
**Fix**: Match plugin network setting with MetaMask network

**Issue**: Oracle connection failed  
**Fix**: Check oracle endpoint URL in settings

**Issue**: Payment gateway not showing  
**Fix**: Enable payment gateway in WooCommerce settings

## 📞 Need Help?

- **Full Docs**: [README-GOLDT.md](README-GOLDT.md)
- **Installation**: [INSTALLATION-GUIDE.md](INSTALLATION-GUIDE.md)  
- **Technical**: [PROJECT-SUMMARY.md](PROJECT-SUMMARY.md)
- **Logs**: WooCommerce → Status → Logs → goldt-web3-hybrid-payments

## 🎊 You're Ready!

Your WooCommerce store now supports:
- ✅ MetaMask cryptocurrency payments
- ✅ Real-time GOLDT oracle pricing
- ✅ 5 GOLDT ecosystem tokens
- ✅ Traditional payment fallback
- ✅ Multi-chain support
- ✅ Complete transaction tracking

**Happy selling! 🚀**

---

**Version**: 1.0.0  
**License**: GPL v2 or later  
**Created by**: GOLDT Development Team
