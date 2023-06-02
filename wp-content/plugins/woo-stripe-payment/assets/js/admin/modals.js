(function ($) {

    $.fn.WCStripePayOrderBackboneModal = function (options) {
        return this.each(function () {
            new $.WCStripePayOrderBackboneModal($(this), options);
        })
    }

    $.WCStripePayOrderBackboneModal = function (element, options) {
        var settings = $.extend({}, $.WCBackboneModal.defaultOptions, options);

        if (settings.template) {
            new $.WCStripePayOrderBackboneModal.View({
                target: settings.template,
                string: settings.params
            });
        }
    }

    $.WCStripePayOrderBackboneModal.View = $.WCBackboneModal.View.extend({
        events: _.extend($.WCBackboneModal.View.prototype.events, {
            'click #pay-order': 'pay_order',
            'change [name="payment_type"]': 'payment_type'
        }),
        render: function () {
            $.WCBackboneModal.View.prototype.render.apply(this, arguments);
            this.$el.find('.wc-select2').select2();
            this.init_card_element();
            this.payment_type();
        },
        init_card_element: function () {
            this.stripe = Stripe(wc_stripe_order_pay_params.api_key);
            var elements = this.stripe.elements();
            this.card = elements.create('card', {
                style: {
                    'base': {
                        'color': '#32325d',
                        'fontFamily': '"Helvetica Neue", Helvetica, sans-serif',
                        'fontSmoothing': 'antialiased',
                        'fontSize': '18px',
                        '::placeholder': {
                            'color': '#aab7c4'
                        }

                    }
                },
                hidePostalCode: true
            });
            this.card.mount('#card-element');
        },
        pay_order: function (e) {
            e.preventDefault();
            if (!this.use_token()) {
                this.stripe.createPaymentMethod({
                    type: 'card',
                    card: this.card,
                    billing_details: {
                        name: $('#_billing_first_name').val() + ' ' + $('#_billing_last_name').val(),
                        email: $('#_billing_email').val(),
                        phone: $('#_billing_phone').val(),
                        address: {
                            city: $('#_billing_city').val(),
                            country: $('#_billing_country').val(),
                            line1: $('#_billing_address_1').val(),
                            line2: $('#_billing_address_2').val(),
                            state: $('#_billing_state').val(),
                            postal_code: $('#_billing_postcode').val()
                        }
                    }
                }).then(function (result) {
                    if (result.error) {
                        this.add_messages(result.error.message);
                    } else {
                        $('[name="payment_nonce"]').val(result.paymentMethod.id);
                        this.api_pay_order();
                    }
                }.bind(this));
            } else {
                this.api_pay_order()
            }
        },
        api_pay_order: function () {
            this.block();
            $.ajax({
                url: wc_stripe_order_metabox_params.routes.pay,
                dataType: 'json',
                method: 'POST',
                data: $('#wc-stripe-pay-order-form').serialize(),
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wc_stripe_order_metabox_params._wpnonce);
                }
            }).done(function (response) {
                if (response.code) {
                    this.unblock();
                    this.add_messages(response.message);
                } else {
                    window.location.href = window.location.href;
                }
            }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
                this.add_messages(errorThrown);
                this.unblock();
            }.bind(this))
        },
        payment_type: function (e) {
            var val = this.$el.find('[name="payment_type"]:checked').val();
            var show_if = '.show_if_' + val,
                hide_if = '.hide_if_' + val;
            this.$el.find(show_if).show();
            this.$el.find(hide_if).hide();
        },
        block: function () {
            this.$el.find('.wc-backbone-modal-content').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        },
        unblock: function () {
            this.$el.find('.wc-backbone-modal-content').unblock();
        },
        add_messages: function (messages) {
            this.$el.find('.woocommerce-error').remove();
            if (messages.indexOf('woocommerce-error') == -1) {
                messages = '<div class="woocommerce-error">' + messages + '</div>';
            }
            this.$el.find('form').prepend(messages);
        },
        use_token: function () {
            return this.$el.find('[name="payment_type"]:checked').val() === 'token';
        },
        set_nonce: function (value) {
            this.$el.find('[name="payment_nonce"]').val(value);
        }
    });
}(jQuery));