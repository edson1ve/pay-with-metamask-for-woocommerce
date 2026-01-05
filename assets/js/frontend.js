/**
 * GOLDT Frontend JavaScript
 *
 * General frontend functionality.
 *
 * @package GOLDT_Web3_Hybrid_Payments
 * @since 1.0.0
 */

(function($) {
    'use strict';

    const GoldtFrontend = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Handle payment method selection
            $('input[name="payment_method"]').on('change', function() {
                if ($(this).val() === 'goldt_gateway') {
                    $('.goldt-payment-container').slideDown();
                } else {
                    $('.goldt-payment-container').slideUp();
                }
            });

            // Copy to clipboard functionality for addresses and hashes
            $('.goldt-tx-hash, .goldt-address').on('click', function() {
                const text = $(this).text();
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(function() {
                        alert('Copied to clipboard!');
                    });
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        GoldtFrontend.init();
    });

})(jQuery);
