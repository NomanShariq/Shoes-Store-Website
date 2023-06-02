(function ($, wc_stripe) {

    var PaymentRequest;

    // Product page functionality
    if ($(document.body).is('.single-product')) {
        /**
         * [PaymentRequest description]
         */
        PaymentRequest = function () {
            wc_stripe.BaseGateway.call(this, wc_stripe_payment_request_params);
            window.addEventListener('hashchange', this.hashchange.bind(this));
            this.old_qty = this.get_quantity();
        }

        PaymentRequest.prototype = $.extend({}, wc_stripe.BaseGateway.prototype, wc_stripe.ProductGateway.prototype, wc_stripe.PaymentRequest.prototype);

        PaymentRequest.prototype.initialize = function () {
            if (!$(this.container).length) {
                return setTimeout(this.initialize.bind(this), 1000);
            }
            wc_stripe.ProductGateway.call(this);
            wc_stripe.PaymentRequest.prototype.initialize.call(this);
        }

        /**
         * [canMakePayment description]
         * @return {[type]} [description]
         */
        PaymentRequest.prototype.canMakePayment = function () {
            wc_stripe.PaymentRequest.prototype.canMakePayment.apply(this, arguments).then(function () {
                $(document.body).on('change', '[name="quantity"]', this.maybe_calculate_cart.bind(this));
                $(this.container).parent().parent().addClass('active');
                if (!this.is_variable_product()) {
                    this.cart_calculation();
                } else {
                    if (this.variable_product_selected()) {
                        this.cart_calculation();
                        $(this.container).removeClass('processingFoundVariation');
                    } else {
                        this.disable_payment_button();
                    }
                }
            }.bind(this))
        }

        /**
         * [add_to_cart description]
         */
        PaymentRequest.prototype.maybe_calculate_cart = function (e) {
            this.disable_payment_button();
            this.old_qty = this.get_quantity();
            var variation = this.get_product_data().variation;
            if (!this.is_variable_product() || this.variable_product_selected()) {
                this.cart_calculation().then(function () {
                    if (this.is_variable_product()) {
                        this.createPaymentRequest();
                        this.createPaymentRequestButton();
                        wc_stripe.PaymentRequest.prototype.canMakePayment.apply(this, arguments).then(function () {
                            this.enable_payment_button();
                        }.bind(this));
                    } else {
                        this.enable_payment_button();
                    }
                }.bind(this));
            }
        }

        PaymentRequest.prototype.cart_calculation = function () {
            return wc_stripe.ProductGateway.prototype.cart_calculation.apply(this, arguments).then(function (data) {
                this.update_from_cart_calculation(data);
                this.paymentRequest.update(this.get_payment_request_update({
                    total: {
                        pending: false
                    }
                }));
            }.bind(this)).catch(function () {

            }.bind(this));
        }

        PaymentRequest.prototype.create_button = function () {
            $('#wc-stripe-payment-request-container').empty();
            wc_stripe.PaymentRequest.prototype.create_button.apply(this, arguments);
            this.$button = $('#wc-stripe-payment-request-container');
        }

        PaymentRequest.prototype.button_click = function (e) {
            if (this.$button.is('.disabled')) {
                e.preventDefault();
            } else if (this.get_quantity() == 0) {
                e.preventDefault();
                this.submit_error(this.params.messages.invalid_amount);
            } else {
                if (!this.needs_shipping()) {
                    this.add_to_cart();
                }
            }
        }

        PaymentRequest.prototype.found_variation = function () {
            wc_stripe.ProductGateway.prototype.found_variation.apply(this, arguments);
            if (this.can_pay) {
                this.maybe_calculate_cart();
            }
        }

        /**
         * [block description]
         * @return {[type]} [description]
         */
        PaymentRequest.prototype.block = function () {
            $.blockUI({
                message: this.adding_to_cart ? this.params.messages.add_to_cart : null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        }

    }

    // Cart page functionality
    if ($(document.body).is('.woocommerce-cart')) {
        /**
         * [PaymentRequest description]
         */
        PaymentRequest = function () {
            wc_stripe.BaseGateway.call(this, wc_stripe_payment_request_params);
            window.addEventListener('hashchange', this.hashchange.bind(this));
        }

        PaymentRequest.prototype = $.extend({}, wc_stripe.BaseGateway.prototype, wc_stripe.CartGateway.prototype, wc_stripe.PaymentRequest.prototype);

        PaymentRequest.prototype.initialize = function () {
            wc_stripe.CartGateway.call(this);
            wc_stripe.PaymentRequest.prototype.initialize.call(this);
        }

        PaymentRequest.prototype.canMakePayment = function () {
            wc_stripe.PaymentRequest.prototype.canMakePayment.apply(this, arguments).then(function () {
                $(this.container).addClass('active').parent().addClass('active');
            }.bind(this))
        }

        /**
         * [updated_html description]
         * @return {[type]} [description]
         */
        PaymentRequest.prototype.updated_html = function () {
            if (!$(this.container).length) {
                this.can_pay = false;
            }
            if (this.can_pay) {
                this.initialize();
            }
        }

        PaymentRequest.prototype.button_click = function (e) {
            this.paymentRequest.update(this.get_payment_request_update({
                total: {
                    pending: false
                }
            }));
        }

        /**
         * Called when the cart has been emptied
         * @param  {[type]} e [description]
         * @return {[type]}   [description]
         */
        PaymentRequest.prototype.cart_emptied = function (e) {
            this.can_pay = false;
        }
    }

    // Checkout page functionality
    if ($(document.body).is('.woocommerce-checkout')) {
        /**
         * [PaymentRequest description]
         */
        PaymentRequest = function () {
            wc_stripe.BaseGateway.call(this, wc_stripe_payment_request_params);
            window.addEventListener('hashchange', this.hashchange.bind(this));
        }

        PaymentRequest.prototype = $.extend({}, wc_stripe.BaseGateway.prototype, wc_stripe.CheckoutGateway.prototype, wc_stripe.PaymentRequest.prototype);

        PaymentRequest.prototype.initialize = function () {
            wc_stripe.CheckoutGateway.call(this);
            $('form.checkout').on('change', '.form-row:not(.address-field) .input-text', this.update_payment_request.bind(this));
            if ($(this.container).length) {
                wc_stripe.PaymentRequest.prototype.initialize.call(this);
            }
        }
        /**
         * [canMakePayment description]
         * @return {[type]} [description]
         */
        PaymentRequest.prototype.canMakePayment = function () {
            wc_stripe.PaymentRequest.prototype.canMakePayment.apply(this, arguments).then(function () {
                this.show_icons();
                if (this.banner_enabled()) {
                    $(this.banner_container).empty().show().append('<div id="wc-stripe-payment-request-banner"></div>');
                    $(this.banner_container).show().addClass('active').closest('.wc-stripe-banner-checkout').addClass('active');
                    var elements = this.stripe.elements();
                    var button = elements.create("paymentRequestButton", {
                        paymentRequest: this.paymentRequest,
                        style: {
                            paymentRequestButton: {
                                type: this.params.button.type,
                                theme: this.params.button.theme,
                                height: this.params.button.height
                            }
                        }
                    });
                    button.on('click', this.banner_checkout.bind(this));
                    button.mount("#wc-stripe-payment-request-banner");
                }
            }.bind(this))
        }

        /**
         * [create_button description]
         * @return {[type]} [description]
         */
        PaymentRequest.prototype.create_button = function () {
            if (this.$button) {
                this.$button.remove();
            }
            this.$button = $('<div id="wc-stripe-payment-request-container"></div>');
            $('#place_order').after(this.$button);
            wc_stripe.PaymentRequest.prototype.create_button.call(this);
            this.trigger_payment_method_selected();
        }

        /**
         * [updated_checkout description]
         * @return {[type]} [description]
         */
        PaymentRequest.prototype.updated_checkout = function () {
            if ($(this.container).length) {
                wc_stripe.PaymentRequest.prototype.initialize.call(this);
            }
        }

        /**
         * [button_click description]
         * @param  {[type]} e [description]
         * @return {[type]}   [description]
         */
        PaymentRequest.prototype.banner_checkout = function (e) {
            this.set_payment_method(this.gateway_id);
            this.set_use_new_option(true);
            $('[name="terms"]').prop('checked', true);
        }

        PaymentRequest.prototype.on_token_received = function () {
            wc_stripe.CheckoutGateway.prototype.on_token_received.apply(this, arguments);
            this.fields.toFormFields();
            if (this.payment_request_options.requestShipping) {
                this.maybe_set_ship_to_different();
            }
            if (this.checkout_fields_valid()) {
                this.get_form().trigger('submit');
            }
        }

        PaymentRequest.prototype.update_payment_request = function () {
            if ($(this.container).length) {
                wc_stripe.PaymentRequest.prototype.initialize.call(this);
            }
        }

        PaymentRequest.prototype.show_icons = function () {
            if ($(this.container).length) {
                $(this.container).find('.wc-stripe-paymentRequest-icon.gpay').show();
            }
        }
    }

    new PaymentRequest();

}(jQuery, window.wc_stripe))