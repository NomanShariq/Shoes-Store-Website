(function ($, wc_stripe) {

    /**
     * @construct
     */
    function GPay() {
        this.can_pay = false;
        wc_stripe.BaseGateway.call(this, wc_stripe_googlepay_checkout_params);
        wc_stripe.CheckoutGateway.call(this);
        window.addEventListener('hashchange', this.hashchange.bind(this));
        this.has3DSecureParams();
    }

    /**
     * [prototype description]
     * @type {[type]}
     */
    GPay.prototype = $.extend({}, wc_stripe.BaseGateway.prototype, wc_stripe.CheckoutGateway.prototype, wc_stripe.GooglePay.prototype);

    /**
     * @return {[type]}
     */
    GPay.prototype.initialize = function () {
        if (!$(this.container).length) {
            return;
        }
        // create payments client
        this.createPaymentsClient();
        this.isReadyToPay().then(function () {
            $(this.container).show();
            if (this.banner_enabled()) {
                var $button = $(this.paymentsClient.createButton($.extend({}, this.get_button_options(), {
                    onClick: this.banner_checkout.bind(this)
                })));
                $(this.banner_container).show().addClass('active').closest('.wc-stripe-banner-checkout').addClass('active');
                $(this.banner_container).empty().append($button);
                if (this.is_rectangle_button()) {
                    $button.find('button').removeClass('new_style');
                }
            }
        }.bind(this))
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.create_button = function () {
        wc_stripe.GooglePay.prototype.create_button.apply(this, arguments);
        $('#place_order').after(this.$button);
        this.trigger_payment_method_selected();
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.updated_checkout = function () {
        this.initialize();
    }

    /**
     * [banner_checkout description]
     * @return {[type]} [description]
     */
    GPay.prototype.banner_checkout = function () {
        this.set_payment_method(this.gateway_id);
        this.set_use_new_option(true);
        $('[name="terms"]').prop('checked', true);
        wc_stripe.GooglePay.prototype.start.apply(this, arguments);
    }

    GPay.prototype.on_token_received = function () {
        wc_stripe.CheckoutGateway.prototype.on_token_received.apply(this, arguments);
        if (this.payment_request_options.shippingAddressRequired) {
            this.maybe_set_ship_to_different();
        }
        this.fields.toFormFields({update_shipping_method: false});
        if (this.checkout_fields_valid()) {
            this.get_form().trigger('submit');
        }
    }

    GPay.prototype.payment_data_updated = function (response) {
        this.populate_billing_fields(response.address);
        this.fields.toFormFields({update_shipping_method: false});
    }

    new GPay();

}(jQuery, window.wc_stripe))