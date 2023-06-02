(function (window, $) {
    window.wc_stripe = {};
    var stripe = null;

    if (typeof wc_stripe_checkout_fields === 'undefined') {
        window.wc_stripe_checkout_fields = [];
    }

    wc_stripe.BaseGateway = function (params, container) {
        this.params = params;
        this.gateway_id = this.params.gateway_id;
        this.container = typeof container === 'undefined' ? 'li.payment_method_'.concat(this.gateway_id) : container;

        if (!$(this.container).length) {
            this.container = '.payment_method_'.concat(this.gateway_id);
        }

        this.token_selector = this.params.token_selector;
        this.saved_method_selector = this.params.saved_method_selector;
        this.payment_token_received = false;
        this.stripe = stripe;
        this.fields = checkoutFields;
        this.elements = this.create_stripe_elements();
        this.initialize();
    };

    wc_stripe.BaseGateway.prototype.create_stripe_elements = function () {
        return stripe.elements($.extend({}, {
            locale: 'auto'
        }, this.get_element_options()));
    }

    wc_stripe.BaseGateway.prototype.is_user_logged_in = function () {
        return this.params.user_id > 0;
    }

    wc_stripe.BaseGateway.prototype.get_page = function () {
        var page = wc_stripe_params_v3.page;
        if ('cart' === page && $(document.body).is('.woocommerce-checkout')) {
            page = 'checkout';
        }
        return page;
    };

    wc_stripe.BaseGateway.prototype.set_nonce = function (value) {
        this.fields.set(this.gateway_id + '_token_key', value);
        $(this.token_selector).val(value);
    };

    wc_stripe.BaseGateway.prototype.set_intent = function (value) {
        this.fields.set(this.gateway_id + '_payment_intent_key', value);
        $('#' + this.gateway_id + '_payment_intent_key').val(value);
    };

    wc_stripe.BaseGateway.prototype.get_element_options = function () {
        return this.params.elementOptions;
    };

    wc_stripe.BaseGateway.prototype.initialize = function () {
    };


    wc_stripe.BaseGateway.prototype.create_button = function () {
    };

    wc_stripe.BaseGateway.prototype.is_gateway_selected = function () {
        return $('[name="payment_method"]:checked').val() === this.gateway_id;
    };

    wc_stripe.BaseGateway.prototype.is_saved_method_selected = function () {
        return this.is_gateway_selected() && $('[name="' + this.gateway_id + '_payment_type_key"]:checked').val() === 'saved';
    };

    wc_stripe.BaseGateway.prototype.has_checkout_error = function () {
        return $('#wc_stripe_checkout_error').length > 0 && this.is_gateway_selected();
    };

    wc_stripe.BaseGateway.prototype.submit_error = function (error, skip_form) {
        var message = this.get_error_message(error);

        if (message.indexOf('</ul>') < 0) {
            var classes = (function () {
                var classes = 'woocommerce-NoticeGroup';
                if (this.is_current_page('checkout')) {
                    classes += ' woocommerce-NoticeGroup-checkout';
                }
                return classes;
            }.bind(this)());
            message = '<div class="' + classes + '"><ul class="woocommerce-error"><li>' + message + '</li></ul></div>';
        }
        var custom_message = $(document.body).triggerHandler('wc_stripe_submit_error', [message, error, this]);
        message = typeof custom_message === 'undefined' ? message : custom_message;
        this.submit_message(message, skip_form);
    };

    wc_stripe.BaseGateway.prototype.submit_error_code = function (code) {
        console.log(code);
    };

    wc_stripe.BaseGateway.prototype.get_error_message = function (message) {
        if (typeof message == 'object') {
            if (message.hasOwnProperty('message')) {
                if (message.message.indexOf('server_side_confirmation_beta=v1') > -1) {
                    message.code = 'server_side_confirmation_beta';
                } else if (message.message.indexOf('params.payment_method_data.billing_details.phone') > -1) {
                    message.code = 'phone_required';
                }
            }
            if (message.code && wc_stripe_messages[message.code]) {
                message = wc_stripe_messages[message.code];
            } else {
                message = message.message;
            }
        }

        return message;
    };

    wc_stripe.BaseGateway.prototype.submit_message = function (message, skip_form) {
        $('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
        var $container = $(this.message_container);

        if (!$container.length || (!skip_form && $container.closest('form').length)) {
            if (!$container.length) {
                $container = $(this.container);
            }
            $container = $container.closest('form');
        }

        $container.prepend(message);
        $container.removeClass('processing')
        if ($().unblock) {
            $container.unblock();
        }
        $container.find('.input-text, select, input:checkbox').trigger('blur');

        if ($.scroll_to_notices) {
            $.scroll_to_notices($container);
        } else {
            $('html, body').animate({
                scrollTop: $container.offset().top - 100
            }, 1000);
        }
    };

    wc_stripe.BaseGateway.prototype.get_billing_details = function () {
        var prefix = this.get_billing_prefix();
        var details = {
            name: this.get_customer_name(prefix),
            address: {
                city: this.fields.get(prefix + '_city', null),
                country: this.fields.get(prefix + '_country', null),
                line1: this.fields.get(prefix + '_address_1', null),
                line2: this.fields.get(prefix + '_address_2', null),
                postal_code: this.fields.get(prefix + '_postcode', null),
                state: this.fields.get(prefix + '_state', null)
            }
        }
        if (!details.name || details.name === ' ') {
            delete details.name;
        }
        if (this.fields.get('billing_email') != '') {
            details.email = this.fields.get('billing_email');
        }
        if (this.fields.get('billing_phone') != '') {
            details.phone = this.fields.get('billing_phone');
        }
        return details;
    }

    wc_stripe.BaseGateway.prototype.get_first_name = function (prefix) {
        return $('#' + prefix + '_first_name').val();
    };

    wc_stripe.BaseGateway.prototype.get_last_name = function (prefix) {
        return $('#' + prefix + '_last_name').val();
    };

    wc_stripe.BaseGateway.prototype.get_shipping_prefix = function () {
        if (this.needs_shipping() && $('[name="ship_to_different_address"]').length > 0 && $('[name="ship_to_different_address"]').is(':checked')) {
            return 'shipping';
        }
        return 'billing';
    }

    /**
     * Some 3rd party plugins give priority to the shipping address over the billing address
     */
    wc_stripe.BaseGateway.prototype.get_billing_prefix = function () {
        var prefix = 'billing';
        if ($('[name="billing_same_as_shipping"]').length && $('[name="billing_same_as_shipping"]').is(':checked')) {
            prefix = 'shipping';
        }
        if ($('[name="bill_to_different_address"]').length) {
            if ($('[name="bill_to_different_address"]').length > 1) {
                if ($('[name="bill_to_different_address"]:checked').val() === 'same_as_shipping') {
                    prefix = 'shipping';
                }
            } else if (!$('[name="bill_to_different_address"]').is(':checked')) {
                prefix = 'shipping';
            }
        }
        var filtered_prefix = $(document.body).triggerHandler('wc_stripe_get_billing_prefix', [prefix]);
        return typeof filtered_prefix === 'undefined' ? prefix : filtered_prefix;
    }

    wc_stripe.BaseGateway.prototype.should_save_method = function () {
        return $('#' + this.gateway_id + '_save_source_key').is(':checked');
    };

    wc_stripe.BaseGateway.prototype.is_add_payment_method_page = function () {
        return this.get_page() === 'add_payment_method' || $(document.body).hasClass('woocommerce-add-payment-method');
    };

    wc_stripe.BaseGateway.prototype.is_change_payment_method = function () {
        return this.get_page() === 'change_payment_method';
    };

    wc_stripe.BaseGateway.prototype.get_selected_payment_method = function () {
        return $(this.saved_method_selector).val();
    };

    wc_stripe.BaseGateway.prototype.needs_shipping = function () {
        return this.get_gateway_data().needs_shipping;
    };

    wc_stripe.BaseGateway.prototype.get_currency = function () {
        return this.get_gateway_data().currency;
    };

    wc_stripe.BaseGateway.prototype.get_gateway_data = function () {
        var data = $(this.container).find(".woocommerce_".concat(this.gateway_id, "_gateway_data")).data('gateway');
        if (typeof data === 'undefined' && this.is_current_page('checkout')) {
            data = $('form.checkout').find(".woocommerce_".concat(this.gateway_id, "_gateway_data")).data('gateway');
            if (typeof data === 'undefined') {
                data = $('.woocommerce_' + this.gateway_id + '_gateway_data').data('gateway');
            }
        }
        return data;
    };

    wc_stripe.BaseGateway.prototype.set_gateway_data = function (data) {
        $(this.container).find(".woocommerce_".concat(this.gateway_id, "_gateway_data")).data('gateway', data);
    };

    wc_stripe.BaseGateway.prototype.has_gateway_data = function () {
        var data = this.get_gateway_data();
        return typeof data !== 'undefined';
    }

    wc_stripe.BaseGateway.prototype.get_customer_name = function (prefix) {
        return this.fields.get(prefix + '_first_name') + ' ' + this.fields.get(prefix + '_last_name');
    };

    wc_stripe.BaseGateway.prototype.get_customer_email = function () {
        return this.fields.get('billing_email');
    };

    wc_stripe.BaseGateway.prototype.get_address_field_hash = function (prefix) {
        var params = ['_first_name', '_last_name', '_address_1', '_address_2', '_postcode', '_city', '_state', '_country'];
        var hash = '';

        for (var i = 0; i < params.length; i++) {
            hash += this.fields.get(prefix + params[i]) + '_';
        }

        return hash;
    };

    wc_stripe.BaseGateway.prototype.block = function () {
        if ($().block) {
            $.blockUI({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        }
    }

    wc_stripe.BaseGateway.prototype.unblock = function () {
        if ($().block) {
            $.unblockUI();
        }
    };

    wc_stripe.BaseGateway.prototype.get_form = function () {
        return $(this.token_selector).closest('form');
    };

    wc_stripe.BaseGateway.prototype.get_total_price = function () {
        return this.get_gateway_data().total;
    };

    wc_stripe.BaseGateway.prototype.get_total_price_cents = function () {
        return this.get_gateway_data().total_cents;
    };

    wc_stripe.BaseGateway.prototype.set_total_price = function (total) {
        var data = this.get_gateway_data();
        data.total = total;
        this.set_gateway_data(data);
    };

    wc_stripe.BaseGateway.prototype.set_total_price_cents = function (total) {
        var data = this.get_gateway_data();
        data.total_cents = total;
        this.set_gateway_data(data);
    };

    wc_stripe.BaseGateway.prototype.set_payment_method = function (payment_method) {
        $('[name="payment_method"][value="' + payment_method + '"]').prop("checked", true).trigger('click');
    };

    wc_stripe.BaseGateway.prototype.set_selected_shipping_methods = function (shipping_methods) {
        this.fields.set('shipping_method', shipping_methods);

        if (shipping_methods && $('[name^="shipping_method"]').length) {
            for (var i in shipping_methods) {
                var method = shipping_methods[i];
                $('[name="shipping_method[' + i + ']"][value="' + method + '"]').prop("checked", true).trigger('change');
            }
        }
    };

    wc_stripe.BaseGateway.prototype.on_token_received = function (paymentMethod) {
        this.payment_token_received = true;
        this.set_nonce(paymentMethod.id);
        this.process_checkout();
    };

    wc_stripe.BaseGateway.prototype.createPaymentRequest = function () {
        try {
            this.payment_request_options = this.get_payment_request_options();
            this.paymentRequest = stripe.paymentRequest(this.payment_request_options);
            if (this.payment_request_options.requestShipping) {
                this.paymentRequest.on('shippingaddresschange', this.update_shipping_address.bind(this));
                this.paymentRequest.on('shippingoptionchange', this.update_shipping_method.bind(this));
            }

            this.paymentRequest.on('paymentmethod', this.on_payment_method_received.bind(this));
        } catch (err) {
            this.submit_error(err.message);
            return;
        }
    };

    wc_stripe.BaseGateway.prototype.get_payment_request_options = function () {
        var options = {
            country: this.params.country_code,
            currency: this.get_currency().toLowerCase(),
            total: {
                amount: this.get_total_price_cents(),
                label: this.params.total_label,
                pending: true
            },
            requestPayerName: (function () {
                if (this.is_current_page('checkout')) {
                    // if billing address is already filled out, then don't request it in wallet
                    return !this.is_valid_address(this.get_address_object('billing'), 'billing', ['email', 'phone']);
                }
                return true;
            }.bind(this)()),
            requestPayerEmail: this.fields.requestFieldInWallet('billing_email'),
            requestPayerPhone: this.fields.requestFieldInWallet('billing_phone'),
            requestShipping: (function () {
                if (this.needs_shipping()) {
                    var prefix = this.get_shipping_prefix();
                    if ((this.is_current_page('checkout') && !this.is_valid_address(this.get_address_object(prefix), prefix, ['email', 'phone'])) || !this.is_current_page('checkout')) {
                        return true;
                    }
                }
                return false;
            }.bind(this))()
        };
        var displayItems = this.get_display_items(),
            shippingOptions = this.get_shipping_options();

        if (displayItems) {
            options.displayItems = displayItems;
        }

        if (options.requestShipping && shippingOptions) {
            options.shippingOptions = shippingOptions;
        }

        return options;
    };

    wc_stripe.BaseGateway.prototype.get_payment_request_update = function (data) {
        var options = {
            currency: this.get_currency().toLowerCase(),
            total: {
                amount: parseInt(this.get_total_price_cents()),
                label: this.params.total_label,
                pending: true
            }
        };
        var displayItems = this.get_display_items(),
            shippingOptions = this.get_shipping_options();

        if (displayItems) {
            options.displayItems = displayItems;
        }

        if (this.payment_request_options.requestShipping && shippingOptions) {
            options.shippingOptions = shippingOptions;
        }

        if (data) {
            options = $.extend(true, {}, options, data);
        }

        return options;
    };

    wc_stripe.BaseGateway.prototype.get_display_items = function () {
        return this.get_gateway_data().items;
    };

    wc_stripe.BaseGateway.prototype.set_display_items = function (items) {
        var data = this.get_gateway_data();
        data.items = items;
        this.set_gateway_data(data);
    };

    wc_stripe.BaseGateway.prototype.get_shipping_options = function () {
        return this.get_gateway_data().shipping_options;
    };

    wc_stripe.BaseGateway.prototype.set_shipping_options = function (items) {
        var data = this.get_gateway_data();
        data.shipping_options = items;
        this.set_gateway_data(data);
    };

    wc_stripe.BaseGateway.prototype.map_address = function (address) {
        return {
            city: address.city,
            postcode: address.postalCode,
            state: address.region,
            country: address.country
        };
    };

    wc_stripe.BaseGateway.prototype.on_payment_method_received = function (paymentResponse) {
        try {
            this.payment_response = paymentResponse;
            this.populate_checkout_fields(paymentResponse);
            paymentResponse.complete("success");
            this.on_token_received(paymentResponse.paymentMethod);
        } catch (err) {
            window.alert(err);
        }
    };

    wc_stripe.BaseGateway.prototype.populate_checkout_fields = function (data) {
        this.set_nonce(data.paymentMethod.id);
        this.update_addresses(data);
    };

    wc_stripe.BaseGateway.prototype.update_addresses = function (data) {
        if (data.payerName) {
            this.fields.set('name', data.payerName, 'billing');
        }

        if (data.payerEmail) {
            this.fields.set('email', data.payerEmail, 'billing');
        }

        if (data.payerPhone) {
            this.fields.set('phone', data.payerPhone, 'billing');
            if (this.fields.exists('shipping_phone') && this.fields.isEmpty('shipping_phone')) {
                this.fields.set('shipping_phone', data.payerPhone);
            }
        }

        if (data.shippingAddress) {
            this.populate_shipping_fields(data.shippingAddress);
        }

        if (data.paymentMethod.billing_details.address) {
            this.populate_billing_fields(data.paymentMethod.billing_details.address);
        }
    };

    wc_stripe.BaseGateway.prototype.populate_address_fields = function (address, prefix) {
        for (var k in address) {
            if (null !== address[k]) {
                this.fields.set(k, address[k], prefix);
            }
        }
    }

    wc_stripe.BaseGateway.prototype.populate_billing_fields = function (address) {
        this.populate_address_fields(address, 'billing');
    }

    wc_stripe.BaseGateway.prototype.populate_shipping_fields = function (address) {
        this.populate_address_fields(address, 'shipping');
    }

    wc_stripe.BaseGateway.prototype.get_address_fields = function () {
        return ['first_name', 'last_name', 'country', 'address_1', 'address_2', 'city', 'state', 'postcode', 'phone', 'email'];
    }

    wc_stripe.BaseGateway.prototype.get_address_object = function (prefix) {
        var address = {};
        this.get_address_fields().forEach(function (k) {
            address[k] = this.fields.get(k, prefix);
        }.bind(this));
        return address;
    }

    wc_stripe.BaseGateway.prototype.is_current_page = function (page) {
        if (Array.isArray(page)) {
            return page.indexOf(this.get_page()) > -1;
        }
        return this.get_page() === page;
    }

    wc_stripe.BaseGateway.prototype.is_valid_address = function (address, prefix, exclude) {
        if ($.isEmptyObject(address)) {
            return false;
        }

        var mappings = this.get_address_fields();
        if (typeof exclude !== 'undefined') {
            exclude.forEach(function (k) {
                if (mappings.indexOf(k) > -1) {
                    mappings.splice(mappings.indexOf(k), 1);
                }
            });
        }
        for (var i = 0; i < mappings.length; i++) {
            var k = mappings[i];
            var required = this.fields.required(prefix + '_' + k);
            if (required) {
                if (!address[k] || typeof address[k] === 'undefined' || !this.fields.isValid(k, address[k], address)) {
                    return false;
                }
            }
        }
        return true;
    }

    wc_stripe.BaseGateway.prototype.ajax_before_send = function (xhr) {
        if (this.is_user_logged_in()) {
            xhr.setRequestHeader('X-WP-Nonce', this.params.rest_nonce);
        }
    };

    wc_stripe.BaseGateway.prototype.process_checkout = function () {
        return new Promise(function () {
            this.block();
            $.ajax({
                url: this.params.routes.checkout,
                method: 'POST',
                dataType: 'json',
                data: $.extend({}, this.serialize_fields(), {
                    payment_method: this.gateway_id,
                    page_id: this.get_page(),
                    currency: this.get_currency(),
                }),
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (result) {
                if (result.reload) {
                    window.location.reload();
                    return;
                }

                if (result.result === 'success') {
                    window.location = result.redirect;
                } else {
                    if (result.messages) {
                        this.submit_error(result.messages);
                    }

                    this.unblock();
                }
            }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
                this.unblock();
                this.submit_error(errorThrown);
            }.bind(this));
        }.bind(this));
    };

    wc_stripe.BaseGateway.prototype.process_payment = function (order_id, order_key) {
        $.ajax({
            method: 'POST',
            url: this.params.routes.checkout_payment,
            dataType: 'json',
            data: $.extend({}, this.fields.toJson(), {order_id: order_id, order_key: order_key}),
            beforeSend: this.ajax_before_send.bind(this)
        }).done(function (result) {
            if (result.result && result.result === 'success') {
                window.location = result.redirect;
            } else {
                if (result.reload) {
                    return window.location.reload();
                }
                if (result.messages) {
                    this.payment_token_received = false;
                    this.submit_error(result.messages);
                } else {
                    this.submit_error(wc_checkout_params.i18n_checkout_error);
                }
            }
        }.bind(this)).fail(function () {

        }.bind(this))
    }

    wc_stripe.BaseGateway.prototype.handle_next_action = function (obj) {
        try {
            this.stripe[this.handleActionMethod](obj.client_secret).then(function (result) {
                if (result.error) {
                    this.unblock();
                    this.payment_token_received = false;
                    return this.submit_error(result.error);
                }
                if (this.is_current_page('order_pay')) {
                    this.get_form().trigger('submit');
                } else {
                    this.process_payment(obj.order_id, obj.order_key);
                }
            }.bind(this)).catch(function (error) {
                this.submit_error(error.message);
            }.bind(this))
            return false;
        } catch (err) {

        }
    }

    wc_stripe.BaseGateway.prototype.handle_payment_method_setup = function (obj) {
        try {
            this.stripe[this.setupActionMethod](obj.client_secret).then(function (result) {
                if (result.error) {
                    this.payment_token_received = false;
                    return this.submit_error(result.error);
                }
                if (this.is_current_page('order_pay')) {
                    this.get_form().trigger('submit');
                } else {
                    this.process_payment(obj.order_id, obj.order_key);
                }
            }.bind(this)).catch(function (error) {
                this.submit_error(error.message);
            }.bind(this))
            return false;
        } catch (err) {

        }
    }

    wc_stripe.BaseGateway.prototype.hashchange = function (e) {
        var match = window.location.hash.match(/response=(.*)/);
        if (match) {
            try {
                var obj = JSON.parse(window.atob(decodeURIComponent(match[1])));
                if (obj && obj.hasOwnProperty('client_secret') && obj.gateway_id === this.gateway_id) {
                    history.pushState({}, '', window.location.pathname);
                    if (obj.type === 'intent') {
                        this.handle_next_action(obj);
                    } else {
                        this.handle_payment_method_setup(obj);
                    }
                }
            } catch (err) {

            }
        }
        return true;
    }

    /**
     * [sync_payment_intent description]
     * @param  {[type]} order_id      [description]
     * @param  {[type]} client_secret [description]
     * @return {[type]}               [description]
     */
    wc_stripe.BaseGateway.prototype.sync_payment_intent = function (order_id, client_secret) {
        return new Promise(function (resolve, reject) {
            // call intent api
            $.ajax({
                method: 'POST',
                dataType: 'json',
                url: this.params.routes.sync_intent,
                data: {order_id: order_id, client_secret: client_secret},
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                if (response.code) {
                    reject(response);
                } else {
                    resolve(response);
                }
            }).fail(function (xhr, textStatus, errorThrown) {
                this.submit_error(errorThrown);
            }.bind(this));
        }.bind(this))
    }

    wc_stripe.BaseGateway.prototype.create_setup_intent = function () {
        return new Promise(function (resolve, reject) {
            $.ajax({
                method: 'POST',
                dataType: 'json',
                data: {payment_method: this.gateway_id},
                url: this.params.routes.setup_intent,
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                if (response.code) {
                    reject(response.message);
                } else {
                    this.client_secret = response.intent.client_secret;
                    resolve(response);
                }
            }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
                this.submit_error(errorThrown);
            }.bind(this));
        }.bind(this))
    }

    wc_stripe.BaseGateway.prototype.serialize_form = function ($form) {
        var formData = $form.find('input').filter(function (i, e) {
                if ($(e).is('[name^="add-to-cart"]')) {
                    return false;
                }

                return true;
            }.bind(this)).serializeArray(),
            data = {};

        for (var i in formData) {
            var obj = formData[i];
            data[obj.name] = obj.value;
        }

        data.payment_method = this.gateway_id;
        return data;
    };

    wc_stripe.BaseGateway.prototype.serialize_fields = function () {
        return $.extend({}, this.fields.toJson(), $(document.body).triggerHandler('wc_stripe_process_checkout_data', [this, this.fields]));
    };

    wc_stripe.BaseGateway.prototype.map_shipping_methods = function (shippingData) {
        var methods = {};

        if (shippingData !== "default") {
            var matches = shippingData.match(/^([\w+]):(.+)$/);

            if (matches.length > 1) {
                methods[matches[1]] = matches[2];
            }
        }

        return methods;
    };

    wc_stripe.BaseGateway.prototype.maybe_set_ship_to_different = function () {
        // if shipping and billing address are different,
        // set the ship to different address option.
        if ($('[name="ship_to_different_address"]').length) {
            $('[name="ship_to_different_address"]').prop('checked', this.get_address_field_hash("billing") !== this.get_address_field_hash("shipping")).trigger('change');
        }
    };

    wc_stripe.BaseGateway.prototype.update_shipping_address = function (ev, data) {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: this.params.routes.shipping_address,
                method: 'POST',
                dataType: 'json',
                data: $.extend({}, {
                    address: this.map_address(ev.shippingAddress),
                    payment_method: this.gateway_id,
                    page_id: this.get_page(),
                    currency: this.get_currency()
                }, data),
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                if (response.code) {
                    ev.updateWith(response.data.newData);
                    reject(response.data);
                } else {
                    ev.updateWith(response.data.newData);
                    this.fields.set('shipping_method', response.data.shipping_method);
                    resolve(response.data);
                }
            }.bind(this)).fail(function () {
            }.bind(this));
        }.bind(this));
    };

    wc_stripe.BaseGateway.prototype.update_shipping_method = function (ev, data) {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: this.params.routes.shipping_method,
                method: 'POST',
                dataType: 'json',
                data: $.extend({}, {
                    shipping_method: ev.shippingOption.id,
                    payment_method: this.gateway_id,
                    currency: this.get_currency(),
                    page_id: this.get_page()
                }, data),
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                if (response.code) {
                    ev.updateWith(response.data.newData);
                    reject(response.data);
                } else {
                    this.set_selected_shipping_methods(response.data.shipping_methods);
                    ev.updateWith(response.data.newData);
                    resolve(response.data);
                }
            }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
                this.submit_error(errorThrown);
            }.bind(this));
        }.bind(this));
    };
    /********** Checkout Gateway ********/

    wc_stripe.CheckoutGateway = function () {
        this.message_container = 'li.payment_method_' + this.gateway_id;
        this.banner_container = 'li.banner_payment_method_' + this.gateway_id;
        $(document.body).on('update_checkout', this.update_checkout.bind(this));
        $(document.body).on('updated_checkout', this.updated_checkout.bind(this));
        $(document.body).on('updated_checkout', this.container_styles.bind(this));
        $(document.body).on('checkout_error', this.checkout_error.bind(this));
        $(this.token_selector).closest('form').on('checkout_place_order_' + this.gateway_id, this.checkout_place_order.bind(this)); // events for showing gateway payment buttons

        $(document.body).on('wc_stripe_new_method_' + this.gateway_id, this.on_show_new_methods.bind(this));
        $(document.body).on('wc_stripe_saved_method_' + this.gateway_id, this.on_show_saved_methods.bind(this));
        $(document.body).on('wc_stripe_payment_method_selected', this.on_payment_method_selected.bind(this));

        if (this.banner_enabled()) {
            if ($('.woocommerce-billing-fields').length) {
                $('.wc-stripe-banner-checkout').css('max-width', $('.woocommerce-billing-fields').outerWidth(true));
            }
        }

        this.container_styles();

        this.hasOrderReviewParams();
    };

    wc_stripe.CheckoutGateway.prototype.container_styles = function () {
        if (!this.params.description) {
            $(this.container).addClass('wc-stripe-no-desc');
        }
        if (!$(this.container).find('.wc-stripe-saved-methods').length) {
            $(this.container).find('.payment_box').addClass('wc-stripe-no-methods');
        }
    }

    wc_stripe.CheckoutGateway.prototype.hasOrderReviewParams = function () {
        var params = window.location.search;
        var match = params.match(/_stripe_order_review=(.+)/);

        if (match && match.length > 1) {
            try {
                var obj = JSON.parse(window.atob(decodeURIComponent(match[1])));
                if (this.gateway_id === obj.payment_method) {
                    $(function () {
                        this.payment_token_received = true;
                        this.set_nonce(obj.payment_nonce);
                        this.set_use_new_option(true);
                    }.bind(this));
                    history.pushState({}, '', window.location.pathname);
                }
            } catch (err) {
            }
        }
    };

    wc_stripe.CheckoutGateway.prototype.has3DSecureParams = function () {
        if (this.is_current_page('order_pay') || this.is_current_page('checkout') || this.is_change_payment_method()) {
            if (window.location.hash && typeof window.location.hash === 'string') {
                var match = window.location.hash.match(/response=(.*)/);
                if (match) {
                    try {
                        var obj = JSON.parse(window.atob(decodeURIComponent(match[1])));
                        if (obj && obj.hasOwnProperty('client_secret') && obj.gateway_id === this.gateway_id) {
                            $(function () {
                                this.set_payment_method(this.gateway_id);
                                this.set_use_new_option(true);
                                this.set_nonce(obj.pm);
                                if (obj.save_method === true) {
                                    this.set_save_payment_method(true);
                                }
                                $('[name="terms"]').prop('checked', true);
                            }.bind(this));
                            history.pushState({}, '', window.location.pathname + window.location.search);
                            this.handle_next_action(obj);
                        }
                    } catch (err) {

                    }
                }
            }
        }
    }

    wc_stripe.CheckoutGateway.prototype.update_shipping_address = function () {
        return wc_stripe.BaseGateway.prototype.update_shipping_address.apply(this, arguments).then(function (data) {
            // populate the checkout fields with the address
            this.populate_address_fields(data.address, this.get_shipping_prefix());
            this.fields.toFormFields({update_shipping_method: false});
        }.bind(this));
    }

    /**
     * Called on the WC updated_checkout event
     */
    wc_stripe.CheckoutGateway.prototype.updated_checkout = function () {
    };

    /**
     * Called on the WC update_checkout event
     */
    wc_stripe.CheckoutGateway.prototype.update_checkout = function () {
    };
    /**
     * Called on the WC checkout_error event
     */


    wc_stripe.CheckoutGateway.prototype.checkout_error = function () {
        if (this.has_checkout_error()) {
            this.payment_token_received = false;
            this.payment_response = null;
            this.show_payment_button();
            this.hide_place_order();
        }
    };

    wc_stripe.CheckoutGateway.prototype.is_valid_checkout = function () {
        if ($('[name="terms"]').length && $('[name="terms"]').is(':visible')) {
            if (!$('[name="terms"]').is(':checked')) {
                return false;
            }
        }

        return true;
    };

    wc_stripe.CheckoutGateway.prototype.get_payment_method = function () {
        return $('[name="payment_method"]:checked').val();
    };

    wc_stripe.CheckoutGateway.prototype.set_use_new_option = function (bool) {
        $('#' + this.gateway_id + '_use_new').prop('checked', bool).trigger('change');
    };

    wc_stripe.CheckoutGateway.prototype.checkout_place_order = function () {
        if (!this.is_valid_checkout()) {
            this.submit_error(this.params.messages.terms);
            return false;
        } else if (this.is_saved_method_selected()) {
            return true;
        }

        return this.payment_token_received;
    };

    wc_stripe.CheckoutGateway.prototype.on_token_received = function (paymentMethod) {
        this.payment_token_received = true;
        this.set_nonce(paymentMethod.id);
        this.hide_payment_button();
        this.show_place_order();
    };

    wc_stripe.CheckoutGateway.prototype.block = function () {
        if ($().block) {
            this.get_form().block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        }

    };

    wc_stripe.CheckoutGateway.prototype.unblock = function () {
        if ($().block) {
            this.get_form().unblock();
        }
    };

    wc_stripe.CheckoutGateway.prototype.hide_place_order = function () {
        $('#place_order').addClass('wc-stripe-hide');
    };

    wc_stripe.CheckoutGateway.prototype.show_place_order = function () {
        $('#place_order').removeClass('wc-stripe-hide');
    };

    wc_stripe.CheckoutGateway.prototype.on_show_new_methods = function () {
        if (this.payment_token_received) {
            this.show_place_order();
            this.hide_payment_button();
        } else {
            this.hide_place_order();
            this.show_payment_button();
        }
    };

    wc_stripe.CheckoutGateway.prototype.on_show_saved_methods = function () {
        this.hide_payment_button();
        this.show_place_order();
    };

    wc_stripe.CheckoutGateway.prototype.show_payment_button = function () {
        if (this.$button) {
            this.$button.show();
        }
    };

    wc_stripe.CheckoutGateway.prototype.hide_payment_button = function () {
        if (this.$button) {
            this.$button.hide();
        }
    };

    wc_stripe.CheckoutGateway.prototype.trigger_payment_method_selected = function () {
        this.on_payment_method_selected(null, $('[name="payment_method"]:checked').val());
    };

    wc_stripe.CheckoutGateway.prototype.show_new_payment_method = function () {
        $('[name="' + this.gateway_id + '_payment_type_key"][value="new"').trigger('click');
    }

    wc_stripe.CheckoutGateway.prototype.on_payment_method_selected = function (e, payment_method) {
        if (payment_method === this.gateway_id) {
            if (this.payment_token_received || this.is_saved_method_selected()) {
                this.hide_payment_button();
                this.show_place_order();
            } else {
                this.show_payment_button();
                this.hide_place_order();
            }
        } else {
            this.hide_payment_button();

            if (payment_method && payment_method.indexOf('stripe_') < 0) {
                this.show_place_order();
            }
        }
    };

    wc_stripe.CheckoutGateway.prototype.banner_enabled = function () {
        return this.params.banner_enabled === '1';
    };

    wc_stripe.CheckoutGateway.prototype.checkout_fields_valid = function () {
        if (['checkout', 'order_pay'].indexOf(this.get_page()) < 0) {
            return true;
        }

        var valid = true;

        if (!(valid = this.fields.validateFields('billing'))) {
            this.submit_error(this.params.messages.required_field);
        } else if (this.needs_shipping() && $('#ship-to-different-address-checkbox').is(':checked')) {
            if (!(valid = this.fields.validateFields('shipping'))) {
                this.submit_error(this.params.messages.required_field);
            }
        } else if (!(valid = this.is_valid_checkout())) {
            this.submit_error(this.params.messages.terms);
        }

        return valid;
    };

    wc_stripe.CheckoutGateway.prototype.cart_contains_subscription = function () {
        return typeof wc_stripe_cart_contains_subscription !== 'undefined' && wc_stripe_cart_contains_subscription === true;
    }

    wc_stripe.CheckoutGateway.prototype.set_save_payment_method = function (bool) {
        $('[name="' + this.gateway_id + '_save_source_key' + '"]').prop('checked', bool);
    }

    wc_stripe.CheckoutGateway.prototype.process_order_pay = function () {
        var data = this.get_form().serializeArray();
        data.push({name: '_wpnonce', value: this.params.rest_nonce});
        data.push({name: 'order_id', value: this.params.order_id});
        data.push({name: 'order_key', value: this.params.order_key});
        this.block();
        $.ajax({
            url: this.params.routes.order_pay,
            method: 'POST',
            dataType: 'json',
            data: $.param(data)
        }).done(function (response) {
            if (response.success) {
                if (response.needs_confirmation) {
                    if (response.data) {
                        this.handle_next_action(response.data);
                    } else {
                        window.location.href = response.redirect;
                    }
                } else {
                    this.get_form().trigger('submit');
                }
            } else {
                this.submit_error(response.message);
            }
        }.bind(this)).fail(function (jqXHR, textStatus, errorThrown) {
            this.unblock();
            this.submit_error(errorThrown);
        }.bind(this))
    }

    /************** Product Gateway ***************/

    wc_stripe.ProductGateway = function () {
        this.message_container = 'div.product'; // events

        $('form.cart').on('found_variation', this.found_variation.bind(this));
        $('form.cart').on('reset_data', this.reset_variation_data.bind(this));
        this.buttonWidth = $('form.cart div.quantity').outerWidth(true) + $('.single_add_to_cart_button').outerWidth();
        var marginLeft = $('.single_add_to_cart_button').css('marginLeft');

        if (marginLeft) {
            this.buttonWidth += parseInt(marginLeft.replace('px', ''));
        }

        $(this.container).css('max-width', this.buttonWidth + 'px');
    };

    wc_stripe.ProductGateway.prototype.update_shipping_address = function (ev) {
        return wc_stripe.BaseGateway.prototype.update_shipping_address.call(this, ev, this.get_add_to_cart_data());
    }

    wc_stripe.ProductGateway.prototype.update_shipping_method = function (ev) {
        return wc_stripe.BaseGateway.prototype.update_shipping_method.call(this, ev, this.get_add_to_cart_data());
    }

    wc_stripe.ProductGateway.prototype.get_quantity = function () {
        return parseInt($('[name="quantity"]').val());
    };

    wc_stripe.ProductGateway.prototype.set_rest_nonce = function (e, nonce) {
        this.params.rest_nonce = nonce;
    };

    wc_stripe.ProductGateway.prototype.found_variation = function (e, variation) {
        var data = this.get_gateway_data();
        data.product.price = variation.display_price;
        data.product.price_cents = variation.display_price_cents;
        data.needs_shipping = !variation.is_virtual;
        data.product.variation = variation;
        this.set_gateway_data(data);
    };

    wc_stripe.ProductGateway.prototype.reset_variation_data = function () {
        var data = this.get_product_data();
        data.variation = false;
        this.set_product_data(data);
        this.disable_payment_button();
    };

    wc_stripe.ProductGateway.prototype.disable_payment_button = function () {
        if (this.$button) {
            this.get_button().prop('disabled', true).addClass('disabled');
        }
    };

    wc_stripe.ProductGateway.prototype.enable_payment_button = function () {
        if (this.$button) {
            this.get_button().prop('disabled', false).removeClass('disabled');
        }
    };

    wc_stripe.ProductGateway.prototype.get_button = function () {
        return this.$button;
    };

    wc_stripe.ProductGateway.prototype.is_variable_product = function () {
        var variation = this.get_product_data().variation;
        return !!variation || $('[name="variation_id"]').length > 0;
    };

    wc_stripe.ProductGateway.prototype.variable_product_selected = function () {
        var variation = this.get_product_data().variation
        var val = $('input[name="variation_id"]').val();
        return !!variation || (!!val && "0" != val);
    };

    wc_stripe.ProductGateway.prototype.get_product_data = function () {
        return this.get_gateway_data().product;
    };

    wc_stripe.ProductGateway.prototype.set_product_data = function (product) {
        var data = this.get_gateway_data();
        data.product = product;
        this.set_gateway_data(data);
    };

    wc_stripe.ProductGateway.prototype.get_form = function () {
        return $(this.container).closest('form');
    }

    wc_stripe.ProductGateway.prototype.get_add_to_cart_data = function () {
        return {
            product_id: this.get_product_data().id,
            variation_id: this.is_variable_product() ? $('[name="variation_id"]').val() : 0,
            qty: $('[name="quantity"]').val(),
            payment_method: this.gateway_id,
            currency: this.get_currency(),
            page_id: this.get_page()
        }
    }

    wc_stripe.ProductGateway.prototype.add_to_cart = function () {
        return new Promise(function (resolve, reject) {
            this.block();
            var data = this.get_add_to_cart_data();
            var fields = this.get_form().find(':not([name="add-to-cart"],[name="quantity"],[name^="attribute_"],[name="variation_id"])').serializeArray();
            if (fields) {
                for (var i in fields) {
                    data[fields[i].name] = fields[i].value;
                }
            }
            $.ajax({
                url: this.params.routes.add_to_cart,
                method: 'POST',
                dataType: 'json',
                data: $.extend({}, data, this.get_product_variations()),
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                this.unblock();

                if (response.code) {
                    this.submit_error(response.message);
                    reject(response);
                } else {
                    this.set_total_price(response.data.total);
                    this.set_total_price_cents(response.data.totalCents);
                    this.set_display_items(response.data.displayItems);
                    resolve(response.data);
                }
            }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
                this.unblock();
                this.submit_error(errorThrown);
            }.bind(this));
        }.bind(this));
    };

    wc_stripe.ProductGateway.prototype.cart_calculation = function () {
        if (wc_stripe.processing_calculation) {
            return wc_stripe.processing_calculation;
        }
        wc_stripe.processing_calculation = new Promise(function (resolve, reject) {
            var variation = this.get_product_data().variation;
            $.ajax({
                url: this.params.routes.cart_calculation,
                method: 'POST',
                dataType: 'json',
                data: $.extend({}, this.get_add_to_cart_data(), this.get_product_variations(), {
                    variation_id: variation.variation_id
                }),
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                if (response.code) {
                    reject(response);
                } else {
                    resolve(response.data);
                }
            }.bind(this)).fail(function () {
                reject();
            })
        }.bind(this)).finally(function () {
            wc_stripe.processing_calculation = false;
        }.bind(this));
        return wc_stripe.processing_calculation;
    };

    wc_stripe.ProductGateway.prototype.update_from_cart_calculation = function (data) {
        if (data && data[this.gateway_id]) {
            this.set_total_price(data[this.gateway_id].total);
            this.set_total_price_cents(data[this.gateway_id].totalCents);
            this.set_display_items(data[this.gateway_id].displayItems);
        }
    }

    wc_stripe.ProductGateway.prototype.get_product_variations = function () {
        var variations = {};
        if (this.is_variable_product()) {
            $('.variations [name^="attribute_"]').each(function (index, el) {
                var $el = $(el);
                var name = $el.data('attribute_name') || $el.attr('name');
                variations[name] = $el.val();
            });
        }
        return variations;
    }

    /************* Cart Gateway *************/

    wc_stripe.CartGateway = function () {
        this.message_container = 'div.woocommerce';

        $(document.body).on('updated_wc_div', this.updated_html.bind(this));
        $(document.body).on('updated_cart_totals', this.updated_html.bind(this));
        $(document.body).on('wc_cart_emptied', this.cart_emptied.bind(this));
    };

    wc_stripe.CartGateway.prototype.submit_error = function (message) {
        this.submit_message(this.get_error_message(message));
    };

    wc_stripe.CartGateway.prototype.updated_html = function (e) {
    };

    wc_stripe.CartGateway.prototype.cart_emptied = function (e) {
    };

    wc_stripe.CartGateway.prototype.add_cart_totals_class = function () {
        $('.cart_totals').addClass('stripe_cart_gateway_active');
    };

    /************* Google Pay Mixins **************/

    wc_stripe.GooglePay = function () {
    };

    wc_stripe.GooglePay.prototype.handleActionMethod = 'handleCardAction';
    wc_stripe.GooglePay.prototype.setupActionMethod = 'confirmCardSetup';

    var googlePayBaseRequest = {
        apiVersion: 2,
        apiVersionMinor: 0
    };

    var baseCardPaymentMethod = {
        type: 'CARD',
        parameters: {
            allowedAuthMethods: ["PAN_ONLY"],
            allowedCardNetworks: ["AMEX", "DISCOVER", "INTERAC", "JCB", "MASTERCARD", "VISA"],
            assuranceDetailsRequired: true
        }
    };

    /**
     * Populate the WC checkout fields.
     * @param  {[type]}
     * @return {[type]}
     */
    wc_stripe.GooglePay.prototype.update_addresses = function (paymentData) {
        if (paymentData.paymentMethodData.info.billingAddress) {
            var billing_address = paymentData.paymentMethodData.info.billingAddress;
            if (this.is_current_page('checkout') && this.is_valid_address(this.billing_address_object, 'billing', ['phone', 'email'])) {
                billing_address = {phoneNumber: billing_address.phoneNumber};
            }
            this.populate_billing_fields(billing_address);
            if (billing_address.phoneNumber && this.fields.exists('shipping_phone') && this.fields.isEmpty('shipping_phone')) {
                this.fields.set('shipping_phone', billing_address.phoneNumber);
            }
        }

        if (paymentData.shippingAddress) {
            this.populate_shipping_fields(paymentData.shippingAddress);
        }

        if (paymentData.email) {
            this.fields.set('email', paymentData.email, 'billing');
        }
    };

    wc_stripe.GooglePay.prototype.map_address = function (address) {
        return {
            city: address.locality,
            postcode: address.postalCode,
            state: address.administrativeArea,
            country: address.countryCode
        };
    };

    wc_stripe.GooglePay.prototype.update_payment_data = function (data, extraData) {
        return new Promise(function (resolve, reject) {
            var shipping_method = data.shippingOptionData.id == 'default' ? null : data.shippingOptionData.id;
            $.when($.ajax({
                url: this.params.routes.payment_data,
                dataType: 'json',
                method: 'POST',
                data: $.extend({}, {
                    address: this.map_address(data.shippingAddress),
                    shipping_method: shipping_method,
                    page_id: this.get_page(),
                    currency: this.get_currency()
                }, extraData),
                beforeSend: this.ajax_before_send.bind(this)
            })).done(function (response) {
                if (response.code) {
                    reject(response.data.data);
                } else {
                    resolve(response.data);
                }
            }.bind(this)).fail(function () {
                reject();
            }.bind(this));
        }.bind(this));
    };

    wc_stripe.GooglePay.prototype.on_payment_data_changed = function (data) {
        return new Promise(function (resolve) {
            this.update_payment_data(data).then(function (response) {
                resolve(response.paymentRequestUpdate);
                this.set_selected_shipping_methods(response.shipping_methods);
                this.payment_data_updated(response, address);
            }.bind(this))['catch'](function (data) {
                resolve(data);
            }.bind(this));
        }.bind(this));
    };

    wc_stripe.GooglePay.prototype.payment_data_updated = function (response) {
    };

    wc_stripe.GooglePay.prototype.get_merchant_info = function () {
        var options = {
            merchantId: this.params.merchant_id,
            merchantName: this.params.merchant_name
        };

        if (this.params.environment === 'TEST') {
            delete options.merchantId;
        }

        return options;
    };

    wc_stripe.GooglePay.prototype.get_payment_options = function () {
        var options = {
            environment: this.params.environment,
            merchantInfo: this.get_merchant_info(),
            paymentDataCallbacks: {
                onPaymentAuthorized: function onPaymentAuthorized() {
                    return new Promise(function (resolve) {
                        resolve({
                            transactionState: "SUCCESS"
                        });
                    }.bind(this));
                }
            }
        };

        if (this.needs_shipping()) {
            var prefix = this.get_shipping_prefix();
            if ((this.is_current_page('checkout') && !this.is_valid_address(this.get_address_object(prefix), prefix, ['email', 'phone'])) || !this.is_current_page('checkout')) {
                options.paymentDataCallbacks.onPaymentDataChanged = this.on_payment_data_changed.bind(this);
            }
        }

        return options;
    };

    wc_stripe.GooglePay.prototype.build_payment_request = function () {
        var request = $.extend({}, googlePayBaseRequest, {
            emailRequired: this.fields.requestFieldInWallet('billing_email'),
            merchantInfo: this.get_merchant_info(),
            allowedPaymentMethods: [$.extend({
                type: "CARD",
                tokenizationSpecification: {
                    type: "PAYMENT_GATEWAY",
                    parameters: {
                        gateway: 'stripe',
                        "stripe:version": "2018-10-31",
                        "stripe:publishableKey": this.params.api_key
                    }
                }
            }, baseCardPaymentMethod)],
            shippingAddressRequired: (function () {
                if (this.needs_shipping()) {
                    var prefix = this.get_shipping_prefix();
                    if ((this.is_current_page('checkout') && !this.is_valid_address(this.get_address_object(prefix), prefix, ['email', 'phone'])) || !this.is_current_page('checkout')) {
                        return true;
                    }
                }
                return false;
            }.bind(this))(),
            transactionInfo: {
                countryCode: this.params.processing_country,
                currencyCode: this.get_currency(),
                totalPriceStatus: "ESTIMATED",
                totalPrice: this.get_total_price().toString(),
                displayItems: this.get_display_items(),
                totalPriceLabel: this.params.total_price_label
            }
        });
        request.allowedPaymentMethods[0].parameters.billingAddressRequired = (function () {
            if (this.is_current_page('checkout')) {
                var address = this.billing_address_object = this.get_address_object('billing');
                // phone is in address object, so if phone is required and empty, request address. If address is not filled out, then request it.
                if (this.fields.requestFieldInWallet('billing_phone') || !this.is_valid_address(address, 'billing', ['email'])) {
                    return true;
                }
                return false;
            }
            return true;
        }.bind(this)());
        if (request.allowedPaymentMethods[0].parameters.billingAddressRequired) {
            request.allowedPaymentMethods[0].parameters.billingAddressParameters = {
                format: "FULL",
                phoneNumberRequired: this.fields.requestFieldInWallet('billing_phone')
            };
        }

        if (request.shippingAddressRequired) {
            request.shippingAddressParameters = {};
            request.shippingOptionRequired = true;
            request.shippingOptionParameters = {
                shippingOptions: this.get_shipping_options()
            };
            request.callbackIntents = ["SHIPPING_ADDRESS", "SHIPPING_OPTION", "PAYMENT_AUTHORIZATION"];
        } else {
            request.callbackIntents = ["PAYMENT_AUTHORIZATION"];
        }
        this.payment_request_options = request;
        return request;
    };

    wc_stripe.GooglePay.prototype.createPaymentsClient = function () {
        this.paymentsClient = new google.payments.api.PaymentsClient(this.get_payment_options());
    };

    wc_stripe.GooglePay.prototype.isReadyToPay = function () {
        return new Promise(function (resolve) {
            var isReadyToPayRequest = $.extend({}, googlePayBaseRequest);
            isReadyToPayRequest.allowedPaymentMethods = [baseCardPaymentMethod];
            this.paymentsClient.isReadyToPay(isReadyToPayRequest).then(function () {
                this.can_pay = true;
                this.create_button();
                resolve();
            }.bind(this))["catch"](function (err) {
                if (err && err.message && err.message.indexOf('ShadowRoot') > -1) {
                    console.log('GPay is not supported on this browser.');
                } else {
                    this.submit_error(err);
                }
            }.bind(this));
        }.bind(this));
    };

    wc_stripe.GooglePay.prototype.create_button = function () {
        if (this.$button) {
            this.$button.remove();
        }

        this.$button = $(this.paymentsClient.createButton(this.get_button_options()));
        this.$button.addClass('gpay-button-container');
        if (this.is_rectangle_button()) {
            this.$button.find('button').removeClass('new_style');
        }
    };

    wc_stripe.GooglePay.prototype.is_rectangle_button = function () {
        return this.params.button_shape === 'rect';
    }

    wc_stripe.GooglePay.prototype.get_button_options = function () {
        var options = {
            onClick: this.start.bind(this),
            buttonColor: this.params.button_color,
            buttonType: this.params.button_style,
            buttonSizeMode: this.params.button_size_mode
        };
        if (this.params.button_locale !== null) {
            options.buttonLocale = this.params.button_locale;
        }
        return options;
    }

    wc_stripe.GooglePay.prototype.start = function () {
        // always recreate the paymentClient to ensure latest data is used.
        this.createPaymentsClient();
        this.paymentsClient.loadPaymentData(this.build_payment_request()).then(function (paymentData) {
            var data = JSON.parse(paymentData.paymentMethodData.tokenizationData.token);
            this.update_addresses(paymentData);
            // convert token to payment method
            this.stripe.createPaymentMethod({
                type: 'card',
                card: {token: data.id},
                billing_details: this.get_billing_details()
            }).then(function (result) {
                if (result.error) {
                    return this.submit_error(result.error);
                }
                this.on_token_received(result.paymentMethod);
            }.bind(this));
        }.bind(this))["catch"](function (err) {
            if (err.statusCode === "CANCELED") {
                return;
            }

            if (err.statusMessage && err.statusMessage.indexOf("paymentDataRequest.callbackIntent") > -1) {
                this.submit_error_code("DEVELOPER_ERROR_WHITELIST");
            } else {
                this.submit_error(err.statusMessage);
            }
        }.bind(this));
    };

    wc_stripe.ApplePay = function () {
    };

    wc_stripe.ApplePay.prototype.handleActionMethod = 'handleCardAction';
    wc_stripe.ApplePay.prototype.setupActionMethod = 'confirmCardSetup';

    wc_stripe.ApplePay.prototype.initialize = function () {
        this.createPaymentRequest();
        this.canMakePayment();
    };

    wc_stripe.ApplePay.prototype.get_payment_request_options = function () {
        return $.extend({}, wc_stripe.BaseGateway.prototype.get_payment_request_options.apply(this, arguments), {
            disableWallets: ['googlePay']
        });
    }

    wc_stripe.ApplePay.prototype.create_button = function () {
        if (this.$button) {
            this.$button.remove();
        }

        this.$button = $(this.params.button);
        this.$button.on('click', this.start.bind(this));
        this.append_button();
    };

    wc_stripe.ApplePay.prototype.canMakePayment = function () {
        return new Promise(function (resolve) {
            this.paymentRequest.canMakePayment().then(function (result) {
                if (result && result.applePay) {
                    this.can_pay = true;
                    this.create_button();
                    $(this.container).show();
                    resolve(result);
                }
            }.bind(this));
        }.bind(this));
    };

    wc_stripe.ApplePay.prototype.start = function (e) {
        e.preventDefault();
        this.paymentRequest.update(this.get_payment_request_update({
            total: {
                pending: false
            }
        }));
        this.paymentRequest.show();
    };

    /*********** PaymentRequest *********/
    wc_stripe.PaymentRequest = function () {
    };

    wc_stripe.PaymentRequest.prototype.handleActionMethod = 'handleCardAction';
    wc_stripe.PaymentRequest.prototype.setupActionMethod = 'confirmCardSetup';

    wc_stripe.PaymentRequest.prototype.initialize = function () {
        this.createPaymentRequest();
        this.createPaymentRequestButton();
        this.canMakePayment();
        this.paymentRequestButton.on('click', this.button_click.bind(this));
    };

    wc_stripe.PaymentRequest.prototype.button_click = function (event) {
    };

    wc_stripe.PaymentRequest.prototype.createPaymentRequestButton = function () {
        if (this.paymentRequestButton) {
            this.paymentRequestButton.destroy();
        }
        this.paymentRequestButton = this.elements.create("paymentRequestButton", {
            paymentRequest: this.paymentRequest,
            style: {
                paymentRequestButton: {
                    type: this.params.button.type,
                    theme: this.params.button.theme,
                    height: this.params.button.height
                }
            }
        });
    };

    wc_stripe.PaymentRequest.prototype.canMakePayment = function () {
        return new Promise(function (resolve) {
            var paymentRequest = this.paymentRequest;
            this.paymentRequest.canMakePayment().then(function (result) {
                if (result && !result.applePay && !result.link && paymentRequest === this.paymentRequest) {
                    this.can_pay = true;
                    this.create_button();
                    $(this.container).show();
                    resolve(result);
                }
            }.bind(this));
        }.bind(this));
    };

    wc_stripe.PaymentRequest.prototype.create_button = function () {
        this.paymentRequestButton.mount('#wc-stripe-payment-request-container');
    };

    wc_stripe.Afterpay = function () {
    };

    wc_stripe.Afterpay.prototype.is_eligible = function (price) {
        return (price >= this.get_min() && price <= this.get_max());
    }

    wc_stripe.Afterpay.prototype.get_min = function () {
        var currency = this.get_currency();
        var params = this.params.requirements[currency];
        return params ? params[1] : 0;
    }

    wc_stripe.Afterpay.prototype.get_max = function () {
        var currency = this.get_currency();
        var params = this.params.requirements[currency];
        return params ? params[2] : 0;
    }

    wc_stripe.Afterpay.prototype.add_eligibility = function (selector, price) {
        if ($(selector).length) {
            if (this.is_eligible(price)) {
                $(selector).removeClass('afterpay-ineligible');
            } else {
                if (this.hide_ineligible_active()) {
                    $(selector).addClass('afterpay-ineligible');
                }
            }
        }
    }

    wc_stripe.Afterpay.prototype.hide_ineligible_active = function () {
        return this.params.hide_ineligible === 'yes';
    }

    wc_stripe.CheckoutFields = function (params, page) {
        this.params = params;
        this.page = page;
        this.session_values = this.supportsSessionStorage() && sessionStorage.getItem('wc_stripe_checkout_fields') ? JSON.parse(sessionStorage.getItem('wc_stripe_checkout_fields')) : {};
        this.fields = new Map(Object.keys(this.params).map(function (k) {
            if (this.params[k].value === null) {
                if (k in this.session_values && this.session_values[k] !== null) {
                    this.params[k].value = this.session_values[k];
                } else {
                    this.params[k].value = "";
                }
            }
            return [k, this.params[k].value];
        }.bind(this)));

        if ('checkout' === page || ('cart' === page && $(document.body).is('.woocommerce-checkout')) || $('form.woocommerce-checkout').length > 0) {
            $(document.body).on('updated_checkout', this.updated_checkout.bind(this));
            $(document.body).on('change', '[name^="billing_"], [name^="shipping_"]', this.onChange.bind(this));
            $('form.checkout').on('change', '.input-text:not([name^="billing_"], [name^="shipping_"], [name="account_password"]), select:not([name^="billing_"], [name^="shipping_"])', this.onChange.bind(this));
            $('form.checkout').on('change', '[name="ship_to_different_address"]', this.on_ship_to_address_change.bind(this));
            this.init_i18n();
            if ($('[name="ship_to_different_address"]').is(':checked')) {
                this.update_required_fields($('#shipping_country').val(), 'shipping_country');
            } else {
                this.update_required_fields($('#billing_country').val(), 'billing_country');
            }
        }
    };

    wc_stripe.CheckoutFields.prototype.supportsSessionStorage = function () {
        if ('sessionStorage' in window && window.sessionStorage !== null) {
            return ['getItem', 'setItem'].reduce(function (exists, method) {
                return !exists ? exists : method in sessionStorage;
            }.bind(this), true);
        }
        return false;
    }

    wc_stripe.CheckoutFields.prototype.init_i18n = function () {
        if (typeof wc_address_i18n_params !== 'undefined') {
            this.locales = JSON.parse(wc_address_i18n_params.locale.replace(/&quot;/g, '"'));
        } else {
            this.locales = null;
        }
    };

    /**
     * Triggered when the WC checkout.js updated_checkout event is fired.
     */
    wc_stripe.CheckoutFields.prototype.updated_checkout = function () {
        this.syncCheckoutFieldsWithDOM();
    }

    wc_stripe.CheckoutFields.prototype.syncCheckoutFieldsWithDOM = function () {
        for (var k in this.params) {
            if ($('#' + k).length) {
                this.fields.set(k, $('#' + k).val());
            }
        }
    }

    wc_stripe.CheckoutFields.prototype.onChange = function (e) {
        try {
            var name = e.currentTarget.name,
                value = e.currentTarget.value;
            this.fields.set(name, value);

            if (name === 'billing_country' || name === 'shipping_country') {
                this.update_required_fields(value, name);
            }
            if (this.supportsSessionStorage()) {
                sessionStorage.setItem('wc_stripe_checkout_fields', JSON.stringify(this.toJson()));
            }
        } catch (err) {
            console.log(err);
        }
    };

    wc_stripe.CheckoutFields.prototype.update_required_fields = function (country, name) {
        if (this.locales) {
            var prefix = name.indexOf('billing_') > -1 ? 'billing_' : 'shipping_';
            var locale = typeof this.locales[country] !== 'undefined' ? this.locales[country] : this.locales['default'];
            var fields = $.extend(true, {}, this.locales['default'], locale);

            for (var k in fields) {
                var k2 = prefix + k;

                if (this.params[k2]) {
                    this.params[k2] = $.extend(true, {}, this.params[k2], fields[k]);
                }
            }
        }
    };

    wc_stripe.CheckoutFields.prototype.on_ship_to_address_change = function (e) {
        if ($(e.currentTarget).is(':checked')) {
            this.update_required_fields($('#shipping_country').val(), 'shipping_country');
        }
    };

    wc_stripe.CheckoutFields.prototype.requestFieldInWallet = function (key) {
        if ('checkout' === this.page) {
            return this.required(key) && this.isEmpty(key);
        } else if ('order_pay' === this.page) {
            return false;
        }

        return this.required(key);
    };

    wc_stripe.CheckoutFields.prototype.set = function (k, v, prefix) {
        if (this[k] && typeof this[k] === 'function') {
            this[k]().set.call(this, v, prefix);
        } else {
            this.fields.set(k, v);
        }
    };

    wc_stripe.CheckoutFields.prototype.get = function (k, prefix) {
        var value;
        if (this[k] && typeof this[k] === 'function') {
            value = this[k]().get.call(this, prefix);
        } else {
            value = this.fields.get(k);

            if (typeof value === 'undefined' || value === null || value === '') {
                if (typeof prefix !== 'undefined') {
                    value = prefix;
                }
            }
        }

        return typeof value === 'undefined' ? '' : value;
    };

    wc_stripe.CheckoutFields.prototype.required = function (k) {
        if (this.params[k]) {
            if (typeof this.params[k].required !== 'undefined') {
                return !!this.params[k].required;
            }
        }

        return false;
    };

    wc_stripe.CheckoutFields.prototype.exists = function (k) {
        return k in this.params;
    }

    wc_stripe.CheckoutFields.prototype.isEmpty = function (k) {
        if (this.fields.has(k)) {
            var value = this.fields.get(k);
            return typeof value === 'undefined' || value === null || typeof value === 'string' && value.trim().length === 0;
        }

        return true;
    };

    wc_stripe.CheckoutFields.prototype.isValid = function (k) {
        if (this[k] && typeof this[k] === 'function') {
            return this[k]().isValid.apply(this, Array.prototype.slice.call(arguments, 1));
        }
    }

    wc_stripe.CheckoutFields.prototype.first_name = function () {
        return {
            set: function (v, prefix) {
                this.fields.set(prefix + '_first_name', v);
            },
            get: function (prefix) {
                return this.fields.get(prefix + '_first_name');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        }
    }

    wc_stripe.CheckoutFields.prototype.last_name = function () {
        return {
            set: function (v, prefix) {
                this.fields.set(prefix + '_last_name', v);
            },
            get: function (prefix) {
                return this.fields.get(prefix + '_last_name');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        }
    }

    wc_stripe.CheckoutFields.prototype.address_1 = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_address_1', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_address_1');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.address_2 = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_address_2', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_address_2');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.name = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_full_name', v);
                var name = v.split(" ");
                if (name.length > 1) {
                    var last_name = name.pop();
                    this.fields.set(prefix + '_first_name', name.join(' '));
                    this.fields.set(prefix + '_last_name', last_name);
                } else if (name.length == 1) {
                    this.fields.set(prefix + '_first_name', name[0]);
                }
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_first_name') + ' ' + this.fields.get(prefix + '_last_name');
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.email = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_email', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_email');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.phone = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_phone', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_phone');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.country = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_country', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_country');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length === 2;
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.state = function () {
        return {
            set: function set(v, prefix) {
                v = v.toUpperCase();
                if (v.length > 2 && this.page === 'checkout') {
                    $('#' + prefix + '_state option').each(function () {
                        var $option = $(this);
                        var state = $option.text().toUpperCase();
                        if (v === state) {
                            v = $option.val();
                        }
                    });
                }
                this.fields.set(prefix + '_state', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_state');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.city = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_city', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_city');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.postcode = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_postcode', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_postcode');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.recipient = function () {
        return wc_stripe.CheckoutFields.prototype.name.apply(this, arguments);
    }

    wc_stripe.CheckoutFields.prototype.payerName = function () {
        return wc_stripe.CheckoutFields.prototype.name.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.payerEmail = function () {
        return wc_stripe.CheckoutFields.prototype.email.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.payerPhone = function () {
        return wc_stripe.CheckoutFields.prototype.phone.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.phoneNumber = function () {
        return wc_stripe.CheckoutFields.prototype.phone.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.countryCode = function () {
        return wc_stripe.CheckoutFields.prototype.country.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.address1 = function () {
        return wc_stripe.CheckoutFields.prototype.address_1.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.address2 = function () {
        return wc_stripe.CheckoutFields.prototype.address_2.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.line1 = function () {
        return wc_stripe.CheckoutFields.prototype.address_1.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.line2 = function () {
        return wc_stripe.CheckoutFields.prototype.address_2.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.addressLine = function () {
        return {
            set: function set(v, prefix) {
                if (v.length > 0) {
                    this.fields.set(prefix + '_address_1', v[0]);
                }

                if (v.length > 1) {
                    this.fields.set(prefix + '_address_2', v[1]);
                }
            },
            get: function get(prefix) {
                return [this.fields.get(prefix + '_address_1'), this.fields.get(prefix + '_address_2')];
            },
            isValid: function (v) {
                if (v.length > 0) {
                    return typeof v[0] === 'string' && v[0].length > 0;
                }
                return false;
            }
        };
    };

    wc_stripe.CheckoutFields.prototype.region = function () {
        return wc_stripe.CheckoutFields.prototype.state.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.administrativeArea = function () {
        return wc_stripe.CheckoutFields.prototype.state.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.locality = function () {
        return wc_stripe.CheckoutFields.prototype.city.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.postal_code = function () {
        return wc_stripe.CheckoutFields.prototype.postcode.apply(this, arguments);
    }

    wc_stripe.CheckoutFields.prototype.postalCode = function () {
        return wc_stripe.CheckoutFields.prototype.postcode.apply(this, arguments);
    };

    wc_stripe.CheckoutFields.prototype.toJson = function () {
        var data = {};
        this.fields.forEach(function (value, key) {
            data[key] = value;
        });
        return data;
    };

    wc_stripe.CheckoutFields.prototype.toFormFields = function (args) {
        var changes = [];
        this.fields.forEach(function (value, key) {
            var name = '[name="' + key + '"]';

            if ($(name).length && value !== '') {
                if ($(name).val() !== value && $(name).is('select')) {
                    changes.push(name);
                }

                $(name).val(value);
            }
        });
        if (changes.length > 0) {
            $(changes.join(',')).trigger('change');
        }
        if (typeof args !== 'undefined') {
            $(document.body).trigger('update_checkout', args);
        }
    };

    wc_stripe.CheckoutFields.prototype.validateFields = function (prefix) {
        for (var k in this.params) {
            var field = this.params[k];
            if (k.indexOf(prefix) > -1 && field.required) {
                if ($('#' + k).length && $('#' + k).is(':visible')) {
                    var val = $('#' + k).val();
                    if (typeof val === 'undefined' || val === null || val.length === 0) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    try {
        stripe = Stripe(wc_stripe_params_v3.api_key, wc_stripe_params_v3.stripeParams);
        wc_stripe.stripe = stripe;
    } catch (error) {
        //window.alert(error);
        console.log(error);
    }

    var checkoutFields = new wc_stripe.CheckoutFields(wc_stripe_checkout_fields, wc_stripe_params_v3.page);
})(window, jQuery);
