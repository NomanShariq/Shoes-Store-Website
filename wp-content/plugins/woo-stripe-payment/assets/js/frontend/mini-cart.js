(function ($, wc_stripe) {

    /**
     *
     * @param container
     * @constructor
     */
    function MiniCart(params) {
        this.message_container = '.widget_shopping_cart_content';
        wc_stripe.BaseGateway.call(this, params, container);
    }

    MiniCart.prototype.on_token_received = function () {
        this.block();
        this.block_cart();
        wc_stripe.BaseGateway.prototype.on_token_received.apply(this, arguments);
    }

    MiniCart.prototype.block_cart = function () {
        $(this.container).find('.wc-stripe-minicart-overlay').addClass('active');
    }

    MiniCart.prototype.unblock_cart = function () {
        $(this.container).find('.wc-stripe-minicart-overlay').removeClass('active');
    }

    MiniCart.prototype.get_gateway_data = function () {
        var key = ".woocommerce_" + this.gateway_id + "_gateway_data";
        var data = $('.woocommerce-mini-cart__buttons').find(key).data('gateway');
        if (!data) {
            data = $(key).data('gateway');
        }
        return !!data ? data : {};
    }

    /*------------------------- GPay -------------------------*/
    function GPay(params) {
        MiniCart.apply(this, arguments);
    }

    GPay.prototype = Object.assign({}, wc_stripe.BaseGateway.prototype, MiniCart.prototype, wc_stripe.GooglePay.prototype);

    GPay.prototype.initialize = function () {
        this.createPaymentsClient();
        this.isReadyToPay().then(function () {
            this.append_button();
        }.bind(this));
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.create_button = function () {
        wc_stripe.GooglePay.prototype.create_button.apply(this, arguments);
        this.append_button();
    }

    GPay.prototype.append_button = function () {
        $(this.container).find('.wc-stripe-gpay-mini-cart').empty();
        $(this.container).find('.wc-stripe-gpay-mini-cart').append(this.$button).show();
    }

    /*------------------------- ApplePay -------------------------*/
    function ApplePay(params) {
        MiniCart.apply(this, arguments);
    }

    ApplePay.prototype = Object.assign({}, wc_stripe.BaseGateway.prototype, MiniCart.prototype, wc_stripe.ApplePay.prototype);


    ApplePay.prototype.initialize = function () {
        wc_stripe.ApplePay.prototype.initialize.apply(this, arguments);
    }

    ApplePay.prototype.append_button = function () {
        $(this.container).find('.wc-stripe-applepay-mini-cart').empty();
        $(this.container).find('.wc-stripe-applepay-mini-cart').append(this.$button).show();
    }

    /*------------------------- PaymentRequest -------------------------*/
    function PaymentRequest(params) {
        MiniCart.apply(this, arguments);
    }

    PaymentRequest.prototype = Object.assign({}, wc_stripe.BaseGateway.prototype, MiniCart.prototype, wc_stripe.PaymentRequest.prototype);

    PaymentRequest.prototype.initialize = function () {
        wc_stripe.PaymentRequest.prototype.initialize.apply(this, arguments);
    }

    PaymentRequest.prototype.create_button = function () {
        this.append_button();
    }

    PaymentRequest.prototype.append_button = function () {
        $(this.container).find('.wc-stripe-payment-request-mini-cart').empty().show();
        this.paymentRequestButton.mount($(this.container).find('.wc-stripe-payment-request-mini-cart').first()[0]);
    }

    function Afterpay(params) {
        MiniCart.apply(this, arguments);
    }

    Afterpay.prototype = Object.assign({}, wc_stripe.BaseGateway.prototype, MiniCart.prototype, wc_stripe.Afterpay.prototype);

    Afterpay.prototype.is_currency_supported = function () {
        return this.params.currencies.indexOf(this.get_currency()) > -1;
    }

    Afterpay.prototype.initialize = function () {
        if ($(this.container).length && this.is_currency_supported()) {
            this.create_element();
            this.mount_message();
        }
    }

    Afterpay.prototype.create_element = function () {
        return this.elements.create('afterpayClearpayMessage', $.extend({}, this.params.msg_options, {
            amount: this.get_total_price_cents(),
            currency: this.get_currency(),
            isEligible: this.is_eligible(parseFloat(this.get_total_price()))
        }));
    }

    Afterpay.prototype.mount_message = function () {
        var $el = $('.wc-stripe-afterpay-minicart-msg');
        if (!$el.length) {
            $('.woocommerce-mini-cart__total').after('<p class="wc-stripe-afterpay-minicart-msg buttons"></p>');
        }
        var elements = document.querySelectorAll('.wc-stripe-afterpay-minicart-msg');
        if (elements) {
            elements.forEach(function (el) {
                this.create_element().mount(el);
                this.add_eligibility(el, parseFloat(this.get_total_price()));
            }.bind(this));
        }
    }

    /*-------------------------------------------------------------------------*/

    var gateways = [], container = null;

    if (typeof wc_stripe_googlepay_mini_cart_params !== 'undefined') {
        gateways.push([GPay, wc_stripe_googlepay_mini_cart_params]);
    }
    if (typeof wc_stripe_applepay_mini_cart_params !== 'undefined') {
        gateways.push([ApplePay, wc_stripe_applepay_mini_cart_params]);
    }
    if (typeof wc_stripe_payment_request_mini_cart_params !== 'undefined') {
        gateways.push([PaymentRequest, wc_stripe_payment_request_mini_cart_params]);
    }
    if (typeof wc_stripe_afterpay_mini_cart_params !== 'undefined') {
        gateways.push([Afterpay, wc_stripe_afterpay_mini_cart_params]);
    }

    function load_mini_cart() {
        $('.woocommerce-mini-cart__buttons').each(function (idx, el) {
            if ($(el).find('.wc_stripe_mini_cart_payment_methods').length) {
                var $parent = $(el).parent();
                if ($parent.length) {
                    var class_name = 'wc-stripe-mini-cart-idx-' + idx;
                    $parent.addClass(class_name);
                    if(!$parent.find('.wc-stripe-minicart-overlay').length){
                        $parent.prepend('<div class="wc-stripe-minicart-overlay"></div>');
                    }

                    container = '.' + class_name + ' .woocommerce-mini-cart__buttons';
                    gateways.forEach(function (gateway) {
                        new gateway[0](gateway[1]);
                    })
                }
            }
        });
    }

    $(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function () {
        setTimeout(load_mini_cart, 250);
    });

    setTimeout(load_mini_cart, 500);

}(jQuery, window.wc_stripe));