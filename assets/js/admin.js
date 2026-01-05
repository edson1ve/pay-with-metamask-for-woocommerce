/**
 * GOLDT Admin JavaScript
 *
 * Admin panel functionality.
 *
 * @package GOLDT_Web3_Hybrid_Payments
 * @since 1.0.0
 */

(function($) {
    'use strict';

    const GoldtAdmin = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.validateWalletAddress();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Validate wallet address on input
            $('#woocommerce_goldt_gateway_wallet_address').on('blur', function() {
                GoldtAdmin.validateWalletAddressField($(this));
            });

            // Test oracle connection
            $('#goldt-test-oracle').on('click', function(e) {
                e.preventDefault();
                GoldtAdmin.testOracleConnection();
            });

            // Copy transaction hash
            $('.goldt-tx-hash-short').on('click', function() {
                const fullHash = $(this).attr('title');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(fullHash).then(function() {
                        alert('Transaction hash copied to clipboard!');
                    });
                }
            });
        },

        /**
         * Validate wallet address field
         */
        validateWalletAddressField: function($field) {
            const address = $field.val().trim();
            
            if (address === '') {
                return;
            }

            if (!this.isValidEthAddress(address)) {
                $field.css('border-color', '#dc3232');
                if (!$field.next('.goldt-error-message').length) {
                    $field.after('<span class="goldt-error-message" style="color: #dc3232; display: block; margin-top: 5px;">Invalid Ethereum address format</span>');
                }
            } else {
                $field.css('border-color', '#46b450');
                $field.next('.goldt-error-message').remove();
            }
        },

        /**
         * Validate Ethereum address format
         */
        isValidEthAddress: function(address) {
            return /^0x[a-fA-F0-9]{40}$/.test(address);
        },

        /**
         * Validate wallet address on page load
         */
        validateWalletAddress: function() {
            const $field = $('#woocommerce_goldt_gateway_wallet_address');
            if ($field.length && $field.val() !== '') {
                this.validateWalletAddressField($field);
            }
        },

        /**
         * Test oracle connection
         */
        testOracleConnection: function() {
            const button = $('#goldt-test-oracle');
            const originalText = button.text();
            
            button.text('Testing...').prop('disabled', true);

            $.ajax({
                url: goldtAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'goldt_test_oracle',
                    nonce: goldtAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Oracle connection successful!\nPrice USD: $' + response.data.price_usd);
                    } else {
                        alert('Oracle connection failed: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Failed to test oracle connection');
                },
                complete: function() {
                    button.text(originalText).prop('disabled', false);
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        GoldtAdmin.init();
    });

})(jQuery);
