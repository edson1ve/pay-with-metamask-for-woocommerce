/**
 * GOLDT Web3 Handler
 *
 * Handles MetaMask detection, wallet connection, and transaction processing.
 *
 * @package GOLDT_Web3_Hybrid_Payments
 * @since 1.0.0
 */

(function($) {
    'use strict';

    const GoldtWeb3Handler = {
        web3: null,
        accounts: [],
        chainId: null,
        selectedToken: null,
        orderTotal: 0,

        /**
         * Initialize the handler
         */
        init: function() {
            this.chainId = goldtWeb3.chainId;
            this.detectMetaMask();
            this.bindEvents();
            
            // Get order total from WooCommerce
            this.getOrderTotal();
        },

        /**
         * Get order total from WooCommerce
         */
        getOrderTotal: function() {
            const totalElement = $('.order-total .woocommerce-Price-amount');
            if (totalElement.length) {
                const totalText = totalElement.text().replace(/[^0-9.]/g, '');
                this.orderTotal = parseFloat(totalText) || 0;
            }
        },

        /**
         * Detect MetaMask
         */
        detectMetaMask: function() {
            const detector = $('#goldt-metamask-detector');
            
            if (typeof window.ethereum !== 'undefined' && window.ethereum.isMetaMask) {
                detector.html('<span class="goldt-success">✓ MetaMask detected</span>');
                $('#goldt-token-selector').show();
                $('#goldt-pay-metamask').show();
                this.web3 = new Web3(window.ethereum);
                this.checkNetwork();
            } else {
                detector.html('<span class="goldt-error">✗ MetaMask not found. <a href="https://metamask.io" target="_blank">Install MetaMask</a></span>');
                $('#goldt-web2-section').show();
            }
        },

        /**
         * Check if user is on correct network
         */
        checkNetwork: async function() {
            try {
                const currentChainId = await window.ethereum.request({ method: 'eth_chainId' });
                const expectedChainId = '0x' + parseInt(this.chainId).toString(16);
                
                if (currentChainId !== expectedChainId) {
                    this.showNetworkWarning();
                }
            } catch (error) {
                console.error('Error checking network:', error);
            }
        },

        /**
         * Show network warning
         */
        showNetworkWarning: function() {
            const detector = $('#goldt-metamask-detector');
            detector.append('<div class="goldt-warning">⚠ Please switch to ' + this.getNetworkName(this.chainId) + '</div>');
        },

        /**
         * Get network name
         */
        getNetworkName: function(chainId) {
            const networks = {
                '1': 'Ethereum Mainnet',
                '5': 'Goerli Testnet',
                '56': 'Binance Smart Chain',
                '97': 'BSC Testnet',
                '137': 'Polygon',
                '80001': 'Mumbai Testnet'
            };
            return networks[chainId] || 'Network ID ' + chainId;
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;

            // Token selector change
            $('#goldt_selected_token').on('change', function() {
                self.selectedToken = $(this).val();
                self.updatePrice();
            });

            // Pay with MetaMask button
            $('#goldt-pay-metamask').on('click', function(e) {
                e.preventDefault();
                self.processWeb3Payment();
            });

            // Trigger initial price calculation
            if ($('#goldt_selected_token').length) {
                this.selectedToken = $('#goldt_selected_token').val();
                this.updatePrice();
            }
        },

        /**
         * Update price display
         */
        updatePrice: async function() {
            const display = $('#goldt-price-display');
            display.html('<span class="goldt-loading">Calculating...</span>');

            try {
                const response = await fetch(goldtWeb3.restUrl + 'get-price', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        token_symbol: this.selectedToken,
                        fiat_amount: this.orderTotal
                    })
                });

                const data = await response.json();

                if (data.success) {
                    const tokenAmount = parseFloat(data.data.token_amount).toFixed(8);
                    const priceUsd = parseFloat(data.data.price_usd).toFixed(6);
                    
                    display.html(
                        '<div class="goldt-price-info">' +
                        '<strong>Amount to pay:</strong> ' + tokenAmount + ' ' + this.selectedToken +
                        '<br><small>Price: $' + priceUsd + ' USD per token</small>' +
                        '</div>'
                    );
                } else {
                    display.html('<span class="goldt-error">Failed to fetch price</span>');
                }
            } catch (error) {
                console.error('Error fetching price:', error);
                display.html('<span class="goldt-error">Error fetching price</span>');
            }
        },

        /**
         * Process Web3 payment
         */
        processWeb3Payment: async function() {
            const button = $('#goldt-pay-metamask');
            button.prop('disabled', true).text('Processing...');

            try {
                // Request account access
                const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
                this.accounts = accounts;

                if (accounts.length === 0) {
                    throw new Error(goldtWeb3.messages.connectWallet);
                }

                const fromAddress = accounts[0];

                // Check network
                const currentChainId = await window.ethereum.request({ method: 'eth_chainId' });
                const expectedChainId = '0x' + parseInt(this.chainId).toString(16);

                if (currentChainId !== expectedChainId) {
                    await this.switchNetwork(expectedChainId);
                }

                // Get token data
                const tokenData = goldtWeb3.tokens[this.selectedToken];
                if (!tokenData) {
                    throw new Error('Invalid token selected');
                }

                // Get price calculation
                const priceResponse = await fetch(goldtWeb3.restUrl + 'get-price', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        token_symbol: this.selectedToken,
                        fiat_amount: this.orderTotal
                    })
                });

                const priceData = await priceResponse.json();
                if (!priceData.success) {
                    throw new Error('Failed to calculate token amount');
                }

                const tokenAmount = priceData.data.token_amount;
                const amountInWei = this.web3.utils.toWei(tokenAmount.toString(), 'ether');

                // Send transaction
                let txHash;
                if (tokenData.contract && tokenData.contract !== '') {
                    // ERC20 token transfer
                    txHash = await this.sendTokenTransaction(
                        fromAddress,
                        tokenData.contract,
                        goldtWeb3.walletAddress,
                        amountInWei
                    );
                } else {
                    // Native token transfer (ETH, BNB, etc.)
                    txHash = await this.sendNativeTransaction(
                        fromAddress,
                        goldtWeb3.walletAddress,
                        amountInWei
                    );
                }

                // Submit transaction to backend
                await this.submitTransaction(txHash, fromAddress, tokenAmount);

                // Success - redirect to order confirmation
                window.location.href = goldtWeb3.returnUrl || window.location.href;

            } catch (error) {
                console.error('Payment error:', error);
                alert(error.message || goldtWeb3.messages.transactionFailed);
                button.prop('disabled', false).text('Pay with MetaMask');
            }
        },

        /**
         * Switch network
         */
        switchNetwork: async function(chainId) {
            try {
                await window.ethereum.request({
                    method: 'wallet_switchEthereumChain',
                    params: [{ chainId: chainId }],
                });
            } catch (switchError) {
                // This error code indicates that the chain has not been added to MetaMask
                if (switchError.code === 4902) {
                    throw new Error('Please add this network to MetaMask first');
                }
                throw switchError;
            }
        },

        /**
         * Send ERC20 token transaction
         */
        sendTokenTransaction: async function(from, contractAddress, to, amount) {
            const contract = new this.web3.eth.Contract([
                {
                    "constant": false,
                    "inputs": [
                        { "name": "_to", "type": "address" },
                        { "name": "_value", "type": "uint256" }
                    ],
                    "name": "transfer",
                    "outputs": [{ "name": "", "type": "bool" }],
                    "type": "function"
                }
            ], contractAddress);

            const txHash = await contract.methods.transfer(to, amount).send({ from: from });
            return txHash.transactionHash;
        },

        /**
         * Send native token transaction
         */
        sendNativeTransaction: async function(from, to, amount) {
            const transactionParameters = {
                from: from,
                to: to,
                value: '0x' + parseInt(amount).toString(16),
            };

            const txHash = await window.ethereum.request({
                method: 'eth_sendTransaction',
                params: [transactionParameters],
            });

            return txHash;
        },

        /**
         * Submit transaction to backend
         */
        submitTransaction: async function(txHash, fromAddress, tokenAmount) {
            const orderId = this.getOrderId();

            const response = await fetch(goldtWeb3.restUrl + 'submit-transaction', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    tx_hash: txHash,
                    from_address: fromAddress,
                    token_symbol: this.selectedToken,
                    token_amount: tokenAmount
                })
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to submit transaction');
            }

            return data;
        },

        /**
         * Get order ID from page
         */
        getOrderId: function() {
            // Try to get from hidden input
            const orderIdInput = $('input[name="goldt_order_id"]');
            if (orderIdInput.length) {
                return orderIdInput.val();
            }

            // Try to get from URL
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('order_id') || 0;
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#goldt-payment-fields').length) {
            GoldtWeb3Handler.init();
        }
    });

})(jQuery);
