(function ($, wc_stripe) {

    /**
     * @constructor
     */
    function GPay() {
        wc_stripe.BaseGateway.call(this, wc_stripe_googlepay_cart_params);
        wc_stripe.CartGateway.call(this);
        window.addEventListener('hashchange', this.hashchange.bind(this));
    }

    /**
     * [prototype description]
     * @type {[type]}
     */
    GPay.prototype = $.extend({}, wc_stripe.BaseGateway.prototype, wc_stripe.CartGateway.prototype, wc_stripe.GooglePay.prototype);

    /**
     * @return {[type]}
     */
    GPay.prototype.initialize = function () {
        this.createPaymentsClient();
        this.isReadyToPay().then(function () {
            $(this.container).show().addClass('active').parent().addClass('active');
            this.add_cart_totals_class();
        }.bind(this))
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.create_button = function () {
        wc_stripe.GooglePay.prototype.create_button.apply(this, arguments);
        $('#wc-stripe-googlepay-container').append(this.$button);
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.updated_html = function () {
        if (this.can_pay) {
            this.create_button();
            $(this.container).show().addClass('active').parent().addClass('active');
            this.add_cart_totals_class();
        }
    }

    /**
     * @param  {[type]}
     * @return {[null]}
     */
    GPay.prototype.payment_data_updated = function (response, event) {
        if (event.callbackTrigger === "SHIPPING_ADDRESS") {
            $(document.body).trigger('wc_update_cart');
        }
    }

    new GPay();

}(jQuery, window.wc_stripe))