(function ($, wc_stripe) {

    function Utils() {
    }

    Utils.prototype.is_currency_supported = function () {
        return this.params.currencies.indexOf(this.get_currency()) > -1;
    }

    /**
     * Product page integration
     * @constructor
     */
    function AfterpayProduct(params) {
        wc_stripe.BaseGateway.call(this, params);
        wc_stripe.ProductGateway.call(this);
        $(document.body).on('change', '[name="quantity"]', this.mount_message.bind(this, true));
    }

    AfterpayProduct.prototype = $.extend({}, wc_stripe.BaseGateway.prototype, wc_stripe.ProductGateway.prototype, Utils.prototype, wc_stripe.Afterpay.prototype);

    AfterpayProduct.prototype.initialize = function () {
        if (!this.msgElement && this.is_currency_supported()) {
            this.create_element();
            this.mount_message();
            this.add_eligibility('#wc-stripe-afterpay-product-msg', this.get_product_price());
        }
    }

    AfterpayProduct.prototype.get_product_price = function (cents) {
        var qty = $('[name="quantity"]').val();
        if (!qty) {
            qty = 0;
        }
        if (cents) {
            return this.get_product_data().price_cents * parseInt(qty);
        }
        return this.get_product_data().price * parseInt(qty);
    }

    AfterpayProduct.prototype.create_element = function () {
        this.msgElement = this.elements.create('afterpayClearpayMessage', $.extend({}, this.params.msg_options, {
            amount: this.get_product_price(true),
            currency: this.get_currency()
        }));
    }

    AfterpayProduct.prototype.found_variation = function () {
        wc_stripe.ProductGateway.prototype.found_variation.apply(this, arguments);
        this.mount_message(true);
    }

    AfterpayProduct.prototype.mount_message = function (update) {
        if (this.msgElement) {
            if (update) {
                this.msgElement.update({
                    amount: this.get_product_price(true),
                    currency: this.get_currency(),
                    isEligible: this.is_eligible(this.get_product_price())
                })
            }
            var $el = $('#wc-stripe-afterpay-product-msg');
            if (!$el.length) {
                if ($('.summary .price').length) {
                    $('.summary .price').append('<div id="wc-stripe-afterpay-product-msg"></div>');
                } else {
                    if ($('.price').length) {
                        $($('.price')[0]).append('<div id="wc-stripe-afterpay-product-msg"></div>');
                    }
                }
            }
            this.msgElement.mount('#wc-stripe-afterpay-product-msg');
        }

    }

    /**
     * Cart page integration
     * @constructor
     */
    function AfterpayCart(params) {
        wc_stripe.BaseGateway.call(this, params);
        wc_stripe.CartGateway.call(this);
    }

    AfterpayCart.prototype = $.extend({}, wc_stripe.BaseGateway.prototype, wc_stripe.CartGateway.prototype, Utils.prototype, wc_stripe.Afterpay.prototype);

    AfterpayCart.prototype.initialize = function () {
        if (!this.msgElement && $(this.container).length && this.is_currency_supported()) {
            this.create_element();
            this.mount_message();
            this.add_eligibility('#wc-stripe-afterpay-cart-container', this.get_total_price());
        }
    }

    AfterpayCart.prototype.create_element = function () {
        this.msgElement = this.elements.create('afterpayClearpayMessage', $.extend({}, this.params.msg_options, {
            amount: this.get_total_price_cents(),
            currency: this.get_currency()
        }));
    }

    AfterpayCart.prototype.mount_message = function (update) {
        if (update && this.msgElement) {
            this.msgElement.update({
                amount: this.get_total_price_cents(),
                currency: this.get_currency(),
                isEligible: true
            })
        }
        var $el = $('#wc-stripe-afterpay-cart-container');
        if (!$el.length) {
            $('.cart_totals table.shop_table > tbody').append('<tr id="wc-stripe-afterpay-cart-container"><td colspan="2"><div id="wc-stripe-afterpay-cart-msg"></div></td></tr>');
        }
        this.msgElement.mount('#wc-stripe-afterpay-cart-msg');
    }

    AfterpayCart.prototype.updated_html = function () {
        if (!$(this.container).length || !this.is_currency_supported()) {
            return;
        }
        this.mount_message(true);
        this.add_eligibility('#wc-stripe-afterpay-cart-container', this.get_total_price());
    }

    if (typeof wc_stripe_afterpay_product_params !== 'undefined') {
        new AfterpayProduct(wc_stripe_afterpay_product_params);
    } else if (typeof wc_stripe_afterpay_cart_params !== 'undefined') {
        new AfterpayCart(wc_stripe_afterpay_cart_params);
    }

})(jQuery, window.wc_stripe);