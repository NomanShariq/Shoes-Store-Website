(function ($, wc_stripe) {

    /**
     * Credit card class.
     *
     * @constructor
     */
    function CC() {
        this.elementStatus = {};
        wc_stripe.BaseGateway.call(this, wc_stripe_credit_card_params);
        wc_stripe.CheckoutGateway.call(this);
        this.message_container = this.params.notice_selector;
        window.addEventListener('hashchange', this.hashchange.bind(this));
        wc_stripe.credit_card = this;
        this.confirmedSetupIntent = false;
        this.has3DSecureParams();
        this.handle_create_account_change();
        $(document.body).on('change', '[name="stripe_cc_saved_method_key"]', this.maybe_initialize_installments.bind(this));
        $(document.body).on('wc_stripe_saved_method_' + this.gateway_id, this.maybe_initialize_installments.bind(this));
    }

    var elementClasses = {
        focus: 'focused',
        empty: 'empty',
        invalid: 'invalid'
    }

    CC.prototype = $.extend({}, wc_stripe.BaseGateway.prototype, wc_stripe.CheckoutGateway.prototype);

    CC.prototype.mappings = {
        cardNumber: '#stripe-card-number',
        cardExpiry: '#stripe-exp',
        cardCvc: '#stripe-cvv'
    }

    CC.prototype.handleActionMethod = 'handleCardAction';
    CC.prototype.setupActionMethod = 'confirmCardSetup';

    /**
     *
     */
    CC.prototype.initialize = function () {
        $(document.body).on('click', '#place_order', this.place_order.bind(this));
        $(document.body).on('change', '#createaccount', this.handle_create_account_change.bind(this));
        this.setup_card();

        if (this.has_gateway_data() && this.can_create_setup_intent()) {
            this.create_setup_intent();
        }
        this.maybe_initialize_installments();
    }

    /**
     *
     */
    CC.prototype.setup_card = function () {
        if (this.is_custom_form()) {
            var options = $.extend(true, {
                classes: elementClasses
            }, this.params.cardOptions);
            // create individual card sections
            ['cardNumber', 'cardExpiry', 'cardCvc'].forEach(function (type) {
                this[type] = this.elements.create(type, $.extend(true, {}, options, this.params.customFieldOptions[type]));
                this.elementStatus[type] = {};
                this[type].on('change', this.on_card_element_change.bind(this));
            }.bind(this));
            this.cardNumber.on('change', this.card_number_change.bind(this));
            this.cardNumber.on('change', this.on_input_change.bind(this));
            this.cardExpiry.on('change', this.on_input_change.bind(this));
            this.cardCvc.on('change', this.on_input_change.bind(this));
            if (this.fields.required('billing_postcode') && '' !== this.fields.get('billing_postcode')) {
                if ($('#stripe-postal-code').length > 0) {
                    $('#stripe-postal-code').val(this.fields.get('billing_postcode'));
                    this.validate_postal_field();
                }
            }
            $(document.body).on('change', '#billing_postcode', function (e) {
                var val = $('#billing_postcode').val();
                $('#stripe-postal-code').val(val).trigger('keyup');
            }.bind(this));
        } else {
            if (this.is_payment_element_enabled()) {
                this.card = this.elements.create('payment', {
                    fields: {
                        billingDetails: this.is_current_page('checkout') ? {address: 'never'} : 'auto'
                    },
                    wallets: {applePay: 'never', googlePay: 'never'}
                });
                this.elementStatus.payment = {};
            } else {
                this.card = this.elements.create('card', $.extend(true, {}, {
                    value: {
                        postalCode: this.fields.get('billing_postcode', '')
                    },
                    hidePostalCode: this.fields.required('billing_postcode'),
                    iconStyle: 'default'
                }, this.params.cardOptions));
                $(document.body).on('change', '#billing_postcode', function (e) {
                    if (this.card) {
                        this.card.update({value: $('#billing_postcode').val()});
                    }
                }.bind(this));
                this.elementStatus.card = {};
            }
            this.card.on('change', this.on_card_element_change.bind(this));
        }
        // setup a timeout so CC element is always rendered.
        setInterval(this.create_card_element.bind(this), 2000);
    }

    CC.prototype.validate_postal_field = function () {
        if ($('#billing_postcode').length && $('#stripe-postal-code').length) {
            // validate postal code
            if (this.params.postal_regex[this.fields.get('billing_country')]) {
                var regex = this.params.postal_regex[this.fields.get('billing_country')],
                    postal = $('#stripe-postal-code').val(),
                    regExp = new RegExp(regex, "i");
                if (postal !== '') {
                    if (regExp.exec(postal) !== null) {
                        $('#stripe-postal-code').addClass('StripeElement--complete').removeClass('invalid');
                    } else {
                        $('#stripe-postal-code').removeClass('StripeElement--complete').addClass('invalid');
                    }
                } else {
                    $('#stripe-postal-code').removeClass('StripeElement--complete').removeClass('invalid');
                }
            } else {
                if ($('#stripe-postal-code').val() != 0) {
                    $('#stripe-postal-code').addClass('StripeElement--complete');
                } else {
                    $('#stripe-postal-code').removeClass('StripeElement--complete');
                }
            }
        } else if ($('#stripe-postal-code').length) {
            if ($('#stripe-postal-code').val() != '') {
                $('#stripe-postal-code').addClass('StripeElement--complete');
            } else {
                $('#stripe-postal-code').removeClass('StripeElement--complete');
            }
        }
    }

    /**
     *
     */
    CC.prototype.create_card_element = function () {
        if (this.is_custom_form()) {
            if ($('#wc-stripe-cc-custom-form').length && $('#wc-stripe-cc-custom-form').find('iframe').length == 0) {
                if ($(this.mappings.cardNumber).length) {
                    this.cardNumber.mount(this.mappings.cardNumber);
                    $(this.mappings.cardNumber).prepend(this.params.html.card_brand);
                }
                if ($(this.mappings.cardExpiry).length) {
                    this.cardExpiry.mount(this.mappings.cardExpiry);
                }
                if ($(this.mappings.cardCvc).length) {
                    this.cardCvc.mount(this.mappings.cardCvc);
                }
                if ($('#stripe-postal-code').length) {
                    $('#stripe-postal-code, .postalCode').on('focus', function (e) {
                        $('#stripe-postal-code').addClass('focused');
                    }.bind(this));
                    $('#stripe-postal-code, .postalCode').on('blur', function (e) {
                        $('#stripe-postal-code').removeClass('focused').trigger('keyup');
                    }.bind(this));
                    $('#stripe-postal-code').on('keyup', function (e) {
                        if ($('#stripe-postal-code').val() == 0) {
                            $('#stripe-postal-code').addClass('empty');
                        } else {
                            $('#stripe-postal-code').removeClass('empty');
                        }
                    }.bind(this));
                    $('#stripe-postal-code').on('change', this.validate_postal_field.bind(this));
                    $('#stripe-postal-code').trigger('change');
                }
            }
        } else {
            if ($('#wc-stripe-card-element').length) {
                if ($('#wc-stripe-card-element').find('iframe').length == 0) {
                    this.card.unmount();
                    this.card.mount('#wc-stripe-card-element');
                    if (!this.is_payment_element_enabled()) {
                        this.card.update({
                            value: {
                                postalCode: this.fields.get('billing_postcode', '')
                            },
                            hidePostalCode: this.fields.required('billing_postcode')
                        });
                    }
                }
            }
        }
        if ($(this.container).outerWidth(true) < 450) {
            $(this.container).addClass('stripe-small-container');
        } else {
            $(this.container).removeClass('stripe-small-container');
        }
    }

    CC.prototype.place_order = function (e) {
        if (this.is_gateway_selected()) {
            this.fields.syncCheckoutFieldsWithDOM();
            if (this.can_create_setup_intent() && !this.is_saved_method_selected() && this.checkout_fields_valid()) {
                e.preventDefault();
                if (this.confirmedSetupIntent) {
                    return this.on_setup_intent_received(this.confirmedSetupIntent);
                }
                if (this.is_payment_element_enabled()) {
                    this.stripe.confirmSetup({
                        elements: this.elements,
                        clientSecret: this.client_secret,
                        confirmParams: (function () {
                            var params = {return_url: ''};
                            if (this.is_current_page('checkout')) {
                                params.payment_method_data = {
                                    billing_details: this.get_billing_details()
                                };
                            }
                            return params;
                        }.bind(this)()),
                        redirect: 'if_required'
                    }).then(function (result) {
                        if (result.error) {
                            return this.submit_card_error(result.error);
                        }
                        this.confirmedSetupIntent = result.setupIntent;
                        this.on_setup_intent_received(result.setupIntent);
                    }.bind(this));
                } else {
                    this.stripe.confirmCardSetup(this.client_secret, {
                        payment_method: {
                            card: this.is_custom_form() ? this.cardNumber : this.card,
                            billing_details: (function () {
                                if (this.is_current_page('checkout')) {
                                    return this.get_billing_details();
                                }
                                return $.extend({}, this.is_custom_form() ? {address: {postal_code: $('#stripe-postal-code').val()}} : {});
                            }.bind(this)())
                        }
                    }).then(function (result) {
                        if (result.error) {
                            this.submit_card_error(result.error);
                            return;
                        }
                        this.confirmedSetupIntent = result.setupIntent;
                        this.on_setup_intent_received(result.setupIntent);
                    }.bind(this))
                }
            } else {
                if (!this.payment_token_received && !this.is_saved_method_selected()) {
                    e.preventDefault();
                    if (this.checkout_fields_valid()) {
                        this.stripe.createPaymentMethod(this.get_create_payment_method_params()).then(function (result) {
                            if (result.error) {
                                return this.submit_card_error(result.error);
                            }
                            if (this.is_current_page('order_pay')) {
                                this.set_nonce(result.paymentMethod.id);
                                this.process_order_pay();
                            } else {
                                this.on_token_received(result.paymentMethod);
                            }
                        }.bind(this))
                    }
                }
            }
        }
    }

    /**
     * @since 3.1.8 - added to ensure 3rd party plugin's can't stop the tokenization process
     *                if e.preventDefault is called on place_order
     * @returns {boolean}
     */
    CC.prototype.checkout_place_order = function () {
        if (!this.is_saved_method_selected() && !this.payment_token_received) {
            this.place_order.apply(this, arguments);
            return false;
        }
        return wc_stripe.CheckoutGateway.prototype.checkout_place_order.apply(this, arguments);
    }

    /**
     *
     */
    CC.prototype.on_token_received = function (paymentMethod) {
        this.payment_token_received = true;
        if (paymentMethod.hasOwnProperty('id')) {
            this.set_nonce(paymentMethod.id);
        } else {
            this.set_nonce(paymentMethod);
        }
        this.get_form().trigger('submit');
    }

    /**
     *
     */
    CC.prototype.on_setup_intent_received = function (setup_intent) {
        this.payment_token_received = true;
        this.set_nonce(setup_intent.payment_method);
        this.set_intent(setup_intent.id);
        this.get_form().trigger('submit');
    }

    /**
     *
     */
    CC.prototype.updated_checkout = function (e, data) {
        if (typeof data !== 'undefined' && data.fragments && data.fragments.hasOwnProperty('.wc-stripe-element-options')) {
            if (this.is_payment_element_enabled()) {
                try {
                    var options = JSON.parse(window.atob(decodeURIComponent(data.fragments['.wc-stripe-element-options'])));
                    // if mode has changed, update elements
                    if (this.params.elementOptions.mode !== options.mode) {
                        this.params.elementOptions.mode = options.mode;
                        this.params.cardFormType = 'payment';
                        this.elements = this.create_stripe_elements();
                        this.setup_card();
                    }
                } catch (error) {

                }
            }
        }
        this.create_card_element();
        this.handle_create_account_change();
        if (this.has_gateway_data() && this.can_create_setup_intent() && !this.client_secret && !this.is_payment_element_enabled()) {
            this.create_setup_intent();
        }
    }

    /**
     *
     */
    CC.prototype.update_checkout = function () {
        this.clear_card_elements();
    }

    CC.prototype.show_payment_button = function () {
        wc_stripe.CheckoutGateway.prototype.show_place_order.apply(this, arguments);
    }

    /**
     * [Leave empty so that the place order button is not hidden]
     * @return {[type]} [description]
     */
    CC.prototype.hide_place_order = function () {

    }

    /**
     * Returns true if a custom form is being used.
     * @return {Boolean} [description]
     */
    CC.prototype.is_custom_form = function () {
        return this.params.custom_form === "1";
    }

    CC.prototype.get_postal_code = function () {
        if (this.is_custom_form()) {
            if ($('#stripe-postal-code').length > 0) {
                return $('#stripe-postal-code').val();
            }
        }
        return this.fields.get(this.get_billing_prefix() + '_postcode', null);
    }

    CC.prototype.card_number_change = function (data) {
        if (data.brand === "unknown") {
            $('#wc-stripe-card').removeClass('active');
        } else {
            $('#wc-stripe-card').addClass('active');
        }
        $('#wc-stripe-card').attr('src', this.params.cards[data.brand]);
    }

    CC.prototype.on_input_change = function (event) {
        if (event.complete) {
            var $elements = $('#wc-stripe-cc-custom-form').find('.StripeElement, #stripe-postal-code');
            var order = [];
            $elements.each(function (idx, el) {
                order.push('#' + $(el).attr('id'));
            }.bind(this));
            var selector = this.mappings[event.elementType];
            var idx = order.indexOf(selector);
            if (typeof order[idx + 1] !== 'undefined') {
                if (order[idx + 1] === '#stripe-postal-code') {
                    document.getElementById('stripe-postal-code').focus();
                } else {
                    for (var k in this.mappings) {
                        if (this.mappings[k] === order[idx + 1]) {
                            this[k].focus();
                        }
                    }
                }
            }
        }
    }

    CC.prototype.clear_card_elements = function () {
        var elements = ['cardNumber', 'cardExpiry', 'cardCvc'];
        for (var i = 0; i < elements.length; i++) {
            if (this[elements[i]]) {
                this[elements[i]].clear();
            }
        }
    }

    CC.prototype.checkout_error = function () {
        if (this.is_gateway_selected()) {
            this.payment_token_received = false;
        }
        wc_stripe.CheckoutGateway.prototype.checkout_error.call(this);
    }

    CC.prototype.get_billing_details = function () {
        var details = wc_stripe.BaseGateway.prototype.get_billing_details.call(this);
        details.address.postal_code = this.get_postal_code();
        return details;
    }

    CC.prototype.can_create_setup_intent = function () {
        return this.is_add_payment_method_page() || this.is_change_payment_method() ||
            (this.is_current_page('checkout') && this.cart_contains_subscription() && this.has_gateway_data() && this.get_total_price_cents() == 0) ||
            (this.is_current_page(['checkout', 'product']) && typeof wc_stripe_preorder_exists !== 'undefined') ||
            (this.is_current_page('order_pay') && 'pre_order' in this.get_gateway_data() && this.get_gateway_data().pre_order === true) ||
            (this.is_current_page('product') && this.get_total_price_cents() == 0);
    }

    CC.prototype.handle_create_account_change = function () {
        if ($('#createaccount').length) {
            if ($('#createaccount').is(':checked') && !this.linkPaymentEnabled) {
                this.show_save_card();
            } else {
                this.hide_save_card();
            }
        }
    }

    CC.prototype.hide_save_card = function () {
        $('.wc-stripe-save-source').hide();
    }

    CC.prototype.show_save_card = function () {
        $('.wc-stripe-save-source').show();
    }

    CC.prototype.submit_card_error = function (error) {
        if (this.params.notice_location === 'bcf') {
            $('.wc-stripe-card-notice').remove();
            $('.wc-stripe_cc-new-method-container').append('<div class="wc-stripe-card-notice"></div>');
        }
        wc_stripe.BaseGateway.prototype.submit_error.call(this, error, true);
    }

    CC.prototype.container_styles = function () {
        wc_stripe.CheckoutGateway.prototype.container_styles.apply(this, arguments);
        if (this.is_custom_form()) {
            $(this.container).find('.payment_box').addClass('custom-form__' + this.params.custom_form_name);
        }
    }

    CC.prototype.checkout_fields_valid = function () {
        var valid = this.is_valid_checkout();
        if (!valid) {
            this.submit_error(this.params.messages.terms);
        }
        return valid;
    }

    CC.prototype.is_installments_available = function () {
        var data = this.get_gateway_data();
        if (data) {
            return !!data.installments.enabled
        }
        return false;
    }

    CC.prototype.update_element_status = function (event) {
        this.elementStatus[event.elementType] = event;
    }

    CC.prototype.is_card_form_complete = function () {
        return Object.keys(this.elementStatus).filter(function (key) {
            return !!this.elementStatus[key].complete;
        }.bind(this)).length == Object.keys(this.elementStatus).length;
    }

    CC.prototype.on_card_element_change = function (event) {
        this.update_element_status(event);
        if (this.is_current_page(['checkout', 'order_pay']) && this.is_card_form_complete() && this.is_installments_available()) {
            this.initialize_installments();
        }
    }

    CC.prototype.initialize_installments = function (paymentMethodId) {
        if (this.installmentTimeoutId) {
            clearTimeout(this.installmentTimeoutId);
        }
        this.installmentTimeoutId = setTimeout(function (paymentMethodId) {
            if (paymentMethodId) {
                this.show_installment_loader();
                this.fetch_installment_plans(paymentMethodId).finally(function () {
                    this.hide_installment_loader();
                }.bind(this));
            } else {
                this.stripe.createPaymentMethod(this.get_create_payment_method_params()).then(function (result) {
                    if (!result.error) {
                        this.show_installment_loader();
                        this.fetch_installment_plans(result.paymentMethod.id).finally(function () {
                            this.hide_installment_loader();
                        }.bind(this));
                    } else {
                        this.hide_installment_loader();
                    }
                }.bind(this)).catch(this.hide_installment_loader.bind(this));

            }
        }.bind(this, paymentMethodId), 250);
    }

    CC.prototype.fetch_installment_plans = function (paymentMethodId) {
        return this.fetch_payment_intent(paymentMethodId).then(function (response) {
            if (response.installments_html) {
                $('.wc-stripe-installment-container').replaceWith(response.installments_html);
            }
        }.bind(this)).catch(function (response) {
            return this.submit_card_error(response);
        }.bind(this)).finally(function () {

        }.bind(this));
    }

    CC.prototype.fetch_payment_intent = function (payment_method_id) {
        return new Promise(function (resolve, reject) {
            var url = this.params.routes.create_payment_intent;
            var order_pay = false;
            if (this.is_current_page('order_pay')) {
                var url = this.params.routes.order_create_payment_intent;
                order_pay = true;
            }
            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                data: !order_pay ? $.extend({}, this.serialize_fields(), {
                    payment_method_id: payment_method_id,
                    payment_method: this.gateway_id,
                    page_id: this.get_page()
                }) : {
                    payment_method_id: payment_method_id,
                    payment_method: this.gateway_id,
                    order_id: this.get_gateway_data().order.id,
                    order_key: this.get_gateway_data().order.key
                },
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                if (response.code) {
                    reject(response);
                } else {
                    resolve(response);
                }
            }.bind(this)).fail(function (xhr) {
                reject()
            }.bind(this))
        }.bind(this))
    }

    CC.prototype.show_installment_loader = function () {
        $('.wc-stripe-installment-options').addClass('loading-installments');
        var $option = $('[name="_stripe_installment_plan"] option:selected').eq(0);
        $option.text(this.params.installments.loading);
        $('.wc-stripe-installment-loader').show();
    }

    CC.prototype.hide_installment_loader = function (has_error) {
        $('.wc-stripe-installment-options').removeClass('loading-installments');
        $('.wc-stripe-installment-loader').hide();
    }

    CC.prototype.maybe_initialize_installments = function () {
        if (this.is_installments_available() && this.is_saved_method_selected()) {
            this.initialize_installments(this.get_selected_payment_method());
        }
    }

    CC.prototype.is_payment_element_enabled = function () {
        return this.params.cardFormType === 'payment'
    }

    CC.prototype.get_element_options = function () {
        if (this.is_payment_element_enabled()) {
            var params = this.params.elementOptions.mode === 'payment' ? this.get_payment_element_params() : {};
            return $.extend({}, this.params.elementOptions, params);
        }
        return this.params.elementOptions;
    }

    CC.prototype.get_payment_element_params = function () {
        if (this.has_gateway_data()) {
            var data = {
                amount: this.get_total_price_cents(),
                currency: this.get_currency().toLowerCase()
            };
            if (data.amount <= 0) {
                data.amount = 100;
            }
            return data;
        }
        return {amount: 100, currency: this.params.currency.toLowerCase()};
    }

    CC.prototype.get_create_payment_method_params = function () {
        if (this.is_payment_element_enabled()) {
            return {
                elements: this.elements,
                params: {
                    billing_details: this.get_billing_details()
                }
            }
        } else {
            return {
                type: 'card',
                card: this.is_custom_form() ? this.cardNumber : this.card,
                billing_details: this.get_billing_details()
            }
        }
    }

    new CC();

}(jQuery, window.wc_stripe))