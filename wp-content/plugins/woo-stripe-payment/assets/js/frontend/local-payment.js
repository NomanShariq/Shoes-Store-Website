(function ($, wc_stripe) {

    /**
     * [LocalPayment description]
     */
    function LocalPayment(params) {
        wc_stripe.BaseGateway.call(this, params);
        wc_stripe.CheckoutGateway.call(this);

        $(document.body).on('click', '#place_order', this.place_order.bind(this));

        if (this.is_current_page('order_pay')) {
            $('#order_review').on('submit', this.process_order_pay.bind(this));
        }

        this.maybe_hide_gateway();
    }

    LocalPayment.prototype = $.extend({}, wc_stripe.BaseGateway.prototype, wc_stripe.CheckoutGateway.prototype);


    LocalPayment.prototype.initialize = function () {
        this.mount_button();
    }

    LocalPayment.prototype.elementType = null;

    LocalPayment.prototype.is_active = function () {
        return $('#wc_stripe_local_payment_' + this.gateway_id).data('active');
    }

    LocalPayment.prototype.maybe_hide_gateway = function () {
        if (!this.is_active()) {
            $(this.container).hide();
            if (this.is_gateway_selected()) {
                $('li[class*="payment_method_stripe_"]').filter(':visible').eq(0).find('[name="payment_method"]').prop('checked', true).trigger('click');
            }
        } else {
            $(this.container).show();
        }
    }

    /**
     * [createSource description]
     * @return {[type]} [description]
     */
    LocalPayment.prototype.createSource = function () {
        return new Promise(function (resolve, reject) {
            var handler = function (result) {
                if (result.error) {
                    this.submit_error(result.error);
                } else {
                    this.payment_token_received = true;
                    this.set_nonce(result.source.id);
                    this.get_form().trigger('submit');
                }
                resolve();
            }.bind(this);
            if (this.elementType != null) {
                if (this.confirmation_method) {
                    if (this.confirmation_obj) {
                        this.processConfirmation(this.confirmation_obj);
                    } else {
                        if (this.isValidElement()) {
                            this.payment_token_received = true;
                            this.get_form().trigger('submit');
                        } else {
                            return this.submit_error({
                                code: 'empty_element_' + this.params.local_payment_type,
                                message: wc_stripe_messages.empty_element
                            });
                        }
                    }
                } else {
                    this.stripe.createSource(this.element, this.getSourceArgs()).then(handler).catch(function (e) {
                        this.submit_error(e.message);
                    }.bind(this))
                }
            } else {
                this.payment_token_received = true;
                this.get_form().trigger('submit');
            }
        }.bind(this));
    }

    LocalPayment.prototype.place_order = function (e) {
        if (this.is_gateway_selected()) {
            if (!this.payment_token_received && !this.is_saved_method_selected()) {
                e.preventDefault();
                if (this.is_change_payment_method()) {
                    this.process_setup_intent();
                } else {
                    this.createSource();
                }
            }
        }
    }

    LocalPayment.prototype.process_setup_intent = function () {
        // create the setup intent
        this.block();
        this.create_setup_intent().then(function () {
            this.stripe[this.setupActionMethod](this.client_secret, this.get_confirmation_args()).then(function (result) {
                if (result.error) {
                    return this.submit_error(result.error.message);
                }
                this.set_nonce(result.setupIntent.payment_method);
                this.set_intent(result.setupIntent.id);
                this.payment_token_received = true;
                this.get_form().trigger('submit');
            }.bind(this));
        }.bind(this)).catch(function (error) {
            this.submit_error(error);
        }.bind(this)).finally(function () {
            this.unblock();
        }.bind(this))
    }

    LocalPayment.prototype.checkout_place_order = function (e) {
        if (!this.is_saved_method_selected() && !this.payment_token_received) {
            this.place_order.apply(this, arguments);
            return false;
        }
        return wc_stripe.CheckoutGateway.prototype.checkout_place_order.apply(this, arguments);
    }

    LocalPayment.prototype.process_order_pay = function (e) {
        if (this.is_gateway_selected()) {
            e.preventDefault();
            wc_stripe.CheckoutGateway.prototype.process_order_pay.apply(this, arguments);
        }
    }

    LocalPayment.prototype.show_payment_button = function () {
        this.show_place_order();
    }

    /**
     * [Leave empty so that the place order button is not hidden]
     * @return {[type]} [description]
     */
    LocalPayment.prototype.hide_place_order = function () {

    }

    LocalPayment.prototype.show_place_order = function () {
        wc_stripe.CheckoutGateway.prototype.show_place_order.apply(this, arguments);
        if (this.payment_token_received) {
            $('#place_order').text($('#place_order').data('value'));
        }
    }

    LocalPayment.prototype.getSourceArgs = function () {
        return {
            type: this.params.local_payment_type,
            amount: this.get_total_price_cents(),
            currency: this.get_currency(),
            owner: {
                name: this.get_customer_name(this.get_billing_prefix()),
                email: this.fields.get('billing_email', null)
            },
            redirect: {
                return_url: this.params.return_url
            }
        }
    }

    LocalPayment.prototype.updated_checkout = function () {
        this.mount_button();
        this.maybe_hide_gateway();
    }

    LocalPayment.prototype.mount_button = function () {
        var id = '#wc_stripe_local_payment_' + this.gateway_id;
        if ($(id).length && this.elementType != null) {
            $(id).empty();
            if (!this.element) {
                this.element = this.elements.create(this.elementType, this.params.element_params);
                this.element.on('change', this.handleElementChange.bind(this));
            }
            this.elementEmpty = true;
            this.element.mount(id);
        }

    }

    LocalPayment.prototype.handleElementChange = function (e) {
        this.elementEmpty = e.empty;
    }

    LocalPayment.prototype.load_external_script = function (url) {
        var script = document.createElement('script');
        script.type = "text/javascript";
        script.src = url;
        script.onload = function () {
            this.script_loaded = true;
        }.bind(this);
        document.body.appendChild(script);
    }

    LocalPayment.prototype.hashChange = function (e) {
        if (this.is_gateway_selected()) {
            var match = window.location.hash.match(/response=(.*)/);
            if (match) {
                history.pushState({}, '', window.location.pathname);
                var obj = JSON.parse(window.atob(decodeURIComponent(match[1])));
                this.processConfirmation(obj);
            }
        }
    }

    LocalPayment.prototype.handle_next_action = function (data) {
        this.processConfirmation(data);
    }

    LocalPayment.prototype.processConfirmation = function (obj) {
        if (obj.type === 'payment_intent') {
            this.stripe[this.confirmation_method](obj.client_secret, this.get_confirmation_args(obj)).then(function (result) {
                if (result.error) {
                    this.after_confirmation_error(result, obj);
                    this.confirmation_obj = obj;
                    this.payment_token_received = false;
                    return this.submit_error(result.error);
                }
                var redirect = decodeURI(obj.order_received_url);
                if (result.paymentIntent.status === 'processing') {
                    redirect += '&' + $.param({
                        '_stripe_local_payment': this.gateway_id,
                        payment_intent: result.paymentIntent.id,
                        payment_intent_client_secret: result.paymentIntent.client_secret
                    });
                }
                window.location.href = decodeURI(redirect);
            }.bind(this))
        } else {
            this.stripe[this.setupActionMethod](obj.client_secret, this.get_confirmation_args(obj)).then(function (result) {
                if (result.error) {
                    return this.submit_error(result.error.message);
                }
                this.set_nonce(result.setupIntent.payment_method);
                this.set_intent(result.setupIntent.id);
                return this.process_payment(obj.order_id, obj.order_key);
            }.bind(this));
        }
    }

    LocalPayment.prototype.after_confirmation_error = function (result, obj) {

    }

    LocalPayment.prototype.get_confirmation_args = function (obj) {
        obj = typeof obj === 'undefined' ? {} : obj;
        var args = {
            payment_method: {
                billing_details: this.get_billing_details()
            },
            return_url: obj.return_url
        };
        if (this.elementType) {
            args.payment_method[this.params.local_payment_type] = this.element;
        }
        return args;
    }

    LocalPayment.prototype.isValidElement = function () {
        if (this.element) {
            return !this.elementEmpty;
        }
        return true;
    }

    LocalPayment.prototype.delete_order_source = function () {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: this.params.routes.delete_order_source,
                method: 'DELETE',
                dataType: 'json',
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                resolve(response);
            }.bind(this)).fail(function () {
                reject();
            }.bind(this))
        }.bind(this))
    }

    LocalPayment.prototype.update_source = function (args) {
        return new Promise(function (resolve, reject) {
            if (this.updateSourceXhr) {
                this.updateSourceXhr.abort();
            }
            this.updateSourceXhr = $.ajax({
                url: this.params.routes.update_source,
                method: 'POST',
                dataType: 'json',
                data: {
                    _wpnonce: this.params.rest_nonce,
                    updates: args,
                    source_id: this.source.id,
                    client_secret: this.source.client_secret,
                    payment_method: this.gateway_id
                }
            }).done(function (response) {
                resolve(response.source);
            }.bind(this)).fail(function () {
                reject();
            });
        }.bind(this));
    }

    /*********** iDEAL ***********/
    function IDEAL(params) {
        this.elementType = 'idealBank';
        this.confirmation_method = 'confirmIdealPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    /*********** P24 ***********/
    function P24(params) {
        this.elementType = 'p24Bank';
        this.confirmation_method = 'confirmP24Payment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    /******* Sepa *******/
    function Sepa(params) {
        this.elementType = 'iban';
        this.confirmation_method = 'confirmSepaDebitPayment';
        this.setupActionMethod = 'confirmSepaDebitSetup';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    /****** Klarna ******/
    function Klarna(params) {
        this.confirmation_method = 'confirmKlarnaPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function FPX(params) {
        this.elementType = 'fpxBank';
        this.confirmation_method = 'confirmFpxPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function WeChat(params) {
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function BECS(params) {
        this.elementType = 'auBankAccount';
        this.confirmation_method = 'confirmAuBecsDebitPayment';
        this.setupActionMethod = 'confirmAuBecsDebitSetup';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function GrabPay(params) {
        this.confirmation_method = 'confirmGrabPayPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function Afterpay(params) {
        this.confirmation_method = 'confirmAfterpayClearpayPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function Boleto(params) {
        this.confirmation_method = 'confirmBoletoPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function OXXO(params) {
        this.confirmation_method = 'confirmOxxoPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function GiroPay(params) {
        this.confirmation_method = 'confirmGiropayPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function Bancontact(params) {
        this.confirmation_method = 'confirmBancontactPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function EPS(params) {
        this.elementType = 'epsBank';
        this.confirmation_method = 'confirmEpsPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function Alipay(params) {
        this.confirmation_method = 'confirmAlipayPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function Sofort(params) {
        this.confirmation_method = 'confirmSofortPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function Affirm(params) {
        this.confirmation_method = 'confirmAffirmPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function BLIK(params) {
        this.confirmation_method = 'confirmBlikPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
        $(document.body).on('keydown', '[name^="blik_code_"]', this.handle_keydown.bind(this));
        $(document.body).on('input', '[name^="blik_code_"]', this.handle_input.bind(this));
    }

    function Konbini(params) {
        this.confirmation_method = 'confirmKonbiniPayment';
        this.generateConfirmationNumber = false;
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    function PayNow(params) {
        this.confirmation_method = 'confirmPayNowPayment';
        LocalPayment.call(this, params);
        window.addEventListener('hashchange', this.hashChange.bind(this));
    }

    WeChat.prototype.updated_checkout = function () {
        if (!this.script_loaded && $(this.container).length) {
            this.load_external_script(this.params.qr_script);
        }
        LocalPayment.prototype.updated_checkout.apply(this, arguments);
    }

    WeChat.prototype.hashChange = function (e) {
        if (this.is_gateway_selected()) {
            var match = window.location.hash.match(/qrcode=(.*)/);
            if (match) {
                history.pushState({}, '', window.location.pathname);
                this.qrcode = JSON.parse(window.atob(decodeURIComponent(match[1])));
                this.get_form().unblock().removeClass('processing').addClass('wechat');
                var qrCode = new QRCode('wc_stripe_local_payment_stripe_wechat', {
                    text: this.qrcode.code,
                    width: parseInt(this.params.qr_size),
                    height: parseInt(this.params.qr_size),
                    colorDark: '#424770',
                    colorLight: '#f8fbfd',
                    correctLevel: QRCode.CorrectLevel.H,
                });
                $('#wc_stripe_local_payment_stripe_wechat').append('<p class="qrcode-message">' + this.params.qr_message + '</p>');
                this.payment_token_received = true;
                this.show_place_order();
            }
        }
    }

    WeChat.prototype.place_order = function (e) {
        if (this.qrcode && this.payment_token_received) {
            e.preventDefault();
            window.location = this.qrcode.redirect;
        } else {
            LocalPayment.prototype.place_order.apply(this, arguments);
        }
    }

    Afterpay.prototype.is_currency_supported = function () {
        return this.params.currencies.indexOf(this.get_currency()) > -1;
    }

    Afterpay.prototype.updated_checkout = function () {
        this.maybe_hide_gateway();
        if (this.has_gateway_data() && this.is_currency_supported()) {
            this.add_eligibility(this.container, parseFloat(this.get_total_price()));
            // re-insert the messaging
            // create new elements object since country code could have changed
            this.elements = this.stripe.elements(this.get_element_options());
            this.initialize_messaging();
        }
    }

    Afterpay.prototype.initialize = function () {
        if (this.has_gateway_data() && this.is_currency_supported()) {
            this.add_eligibility(this.container, parseFloat(this.get_total_price()));
            this.initialize_messaging();
        }
    }

    Afterpay.prototype.initialize_messaging = function () {
        this.msgElement = this.elements.create('afterpayClearpayMessage', $.extend({}, this.params.msg_options, {
            amount: this.get_total_price_cents(),
            currency: this.get_currency()
        }));
        this.mount_message();
    }

    Afterpay.prototype.mount_message = function (update) {
        if (update) {
            this.msgElement.update({
                amount: this.get_total_price_cents(),
                currency: this.get_currency()
            });
        }
        var $el = $('label[for="payment_method_stripe_afterpay"]').find('#wc-stripe-afterpay-msg');
        if (!$el.length) {
            $('label[for="payment_method_stripe_afterpay"]').append('<div id="wc-stripe-afterpay-msg"></div>');
        }
        this.msgElement.mount('#wc-stripe-afterpay-msg');
    }

    Afterpay.prototype.add_eligibility = function (selector, price) {
        wc_stripe.Afterpay.prototype.add_eligibility.apply(this, arguments);
        if (!this.is_eligible(price)) {
            $(this.container).find('.wc-stripe-afterpay__offsite').addClass('afterpay-ineligible');
        }
    }

    Boleto.prototype.get_confirmation_args = function (obj) {
        var args = LocalPayment.prototype.get_confirmation_args.call(this, obj);
        args.payment_method.boleto = {
            tax_id: this.get_tax_id()
        };
        return args;
    }

    Boleto.prototype.createSource = function () {
        var tax_id = this.get_tax_id();
        if (!tax_id || !tax_id.match(/^(\w{3}\.){2}\w{3}-\w{2}$|^(\w{11}|\w{14})$|^\w{2}\.\w{3}\.\w{3}\/\w{4}-\w{2}$/)) {
            return this.submit_error({code: 'incomplete_boleto_tax_id'});
        } else {
            this.payment_token_received = true;
            this.get_form().trigger('submit');
        }
    }

    Boleto.prototype.get_tax_id = function () {
        return $('#wc_stripe_boleto_tax_id').val();
    }

    Sepa.prototype.updated_checkout = function (e) {
        LocalPayment.prototype.updated_checkout.apply(this, arguments);
        var val = $('[name="billing_country"]').val();
        if (!!val && this.element) {
            this.element.update({placeholderCountry: val});
        }
    }

    Sofort.prototype.get_confirmation_args = function () {
        var args = LocalPayment.prototype.get_confirmation_args.apply(this, arguments);
        args.payment_method.sofort = {country: args.payment_method.billing_details.address.country};
        return args;
    }

    BLIK.prototype.get_confirmation_args = function () {
        this.render_timer();
        var args = LocalPayment.prototype.get_confirmation_args.apply(this, arguments);
        args.payment_method.blik = {};
        args.payment_method_options = {
            blik: {
                code: this.get_blik_code()
            }
        }
        return args;
    }

    BLIK.prototype.get_blik_code = function () {
        var result = document.querySelectorAll('[name^="blik_code_"]');
        var code = '';
        if (result && result.length) {
            $.each(result, function (idx, node) {
                code += $(node).val();
            });
        }
        return code;
    }

    BLIK.prototype.handle_keydown = function (e) {
        this.keyCode = e.keyCode;
        var value = $(e.currentTarget).val();
        if (this.keyCode === 8) {
            if (!value) {
                this.handle_input(e, true);
            }
        } else if (value) {
            this.handle_input(e, true);
        }
    }

    BLIK.prototype.handle_input = function (e, skip) {
        var idx = $(e.currentTarget).data('blik_index');
        idx = parseInt(idx);
        var next = 6;
        if (this.keyCode === 8) {
            if (idx > 0 && skip) {
                next = idx - 1;
            }
        } else if (idx < 5) {
            next = idx + 1;
        }
        if (next < 6) {
            $('#blik_code_' + next).focus();
        }
        this.keyCode = skip ? this.keyCode : null;
    }

    BLIK.prototype.render_timer = function (result, obj, redirect) {
        var end = Date.now() + 60 * 1000;
        var count = 60;
        $('.blik-timer-container').show().find('#blik_timer').text(count + 's');
        $('.wc-stripe-blik-code-container').hide();
        this.timer_id = setInterval(function () {
            if (Date.now() > end || count === 0) {
                clearInterval(this.timer_id);
                $('.blik-timer-container').hide();
                $('.wc-stripe-blik-code-container').show();
                return;
            }
            count += -1;
            $('#blik_timer').text(count + 's');
        }.bind(this), 1000);
    }

    BLIK.prototype.after_confirmation_error = function () {
        clearInterval(this.timer_id);
        $('.blik-timer-container').hide();
        $('.wc-stripe-blik-code-container').show();
    }

    Konbini.prototype.get_confirmation_args = function (obj) {
        var args = LocalPayment.prototype.get_confirmation_args.apply(this, arguments);
        args.payment_method_options = {
            konbini: {
                confirmation_number: this.generateConfirmationNumber ? obj.confirmation_number : obj.billing_phone
            }
        }
        return args;
    }

    Konbini.prototype.after_confirmation_error = function (result) {
        if (result.error && result.error.hasOwnProperty('code')) {
            if (result.error.code === 'payment_intent_konbini_rejected_confirmation_number') {
                this.generateConfirmationNumber = true;
            }
        }
    }

    PayNow.prototype.processConfirmation = function (obj) {
        if (obj.type === 'payment_intent') {
            this.stripe[this.confirmation_method](obj.client_secret, this.get_confirmation_args(obj)).then(function (result) {
                if (result.error) {
                    $('form.checkout').removeClass('processing');
                    return this.submit_error(result.error);
                }
                if (result.paymentIntent.status === 'requires_action') {
                    return this.unblock();
                } else if (result.paymentIntent.status === 'requires_payment_method') {
                    this.unblock();
                    return this.submit_error({code: result.paymentIntent.last_payment_error.code});
                }
                window.location.href = decodeURI(obj.order_received_url);
            }.bind(this))
        } else {
            LocalPayment.prototype.processConfirmation.apply(this, arguments);
        }
    }

    IDEAL.prototype = $.extend({}, LocalPayment.prototype, IDEAL.prototype);

    P24.prototype = $.extend({}, LocalPayment.prototype, P24.prototype);

    Sepa.prototype = $.extend({}, LocalPayment.prototype, Sepa.prototype);

    Klarna.prototype = $.extend({}, LocalPayment.prototype, Klarna.prototype);

    FPX.prototype = $.extend({}, LocalPayment.prototype, FPX.prototype);

    WeChat.prototype = $.extend({}, LocalPayment.prototype, WeChat.prototype);

    BECS.prototype = $.extend({}, LocalPayment.prototype, BECS.prototype);

    GrabPay.prototype = $.extend({}, LocalPayment.prototype, GrabPay.prototype);

    Afterpay.prototype = $.extend({}, LocalPayment.prototype, wc_stripe.Afterpay.prototype, Afterpay.prototype);

    Boleto.prototype = $.extend({}, LocalPayment.prototype, Boleto.prototype);

    OXXO.prototype = $.extend({}, LocalPayment.prototype, OXXO.prototype);

    GiroPay.prototype = $.extend({}, LocalPayment.prototype, GiroPay.prototype);

    Bancontact.prototype = $.extend({}, LocalPayment.prototype, Bancontact.prototype);

    EPS.prototype = $.extend({}, LocalPayment.prototype, EPS.prototype);

    Alipay.prototype = $.extend({}, LocalPayment.prototype, Alipay.prototype);

    Sofort.prototype = $.extend({}, LocalPayment.prototype, Sofort.prototype);

    Affirm.prototype = $.extend({}, LocalPayment.prototype, Affirm.prototype);

    BLIK.prototype = $.extend({}, LocalPayment.prototype, BLIK.prototype);

    Konbini.prototype = $.extend({}, LocalPayment.prototype, Konbini.prototype);

    PayNow.prototype = $.extend({}, LocalPayment.prototype, PayNow.prototype);

    /**
     * Local payment types that require JS integration
     * @type {Object}
     */
    var types = {
        'ideal': IDEAL,
        'p24': P24,
        'sepa_debit': Sepa,
        'klarna': Klarna,
        'fpx': FPX,
        'wechat': WeChat,
        'au_becs_debit': BECS,
        'grabpay': GrabPay,
        'afterpay_clearpay': Afterpay,
        'boleto': Boleto,
        'oxxo': OXXO,
        'giropay': GiroPay,
        'bancontact': Bancontact,
        'eps': EPS,
        'alipay': Alipay,
        'sofort': Sofort,
        'affirm': Affirm,
        'blik': BLIK,
        'konbini': Konbini,
        'paynow': PayNow
    }

    for (var i in wc_stripe_local_payment_params.gateways) {
        var params = wc_stripe_local_payment_params.gateways[i];
        if (types[params.local_payment_type]) {
            new types[params.local_payment_type](params);
        } else {
            new LocalPayment(params);
        }
    }

}(jQuery, window.wc_stripe))