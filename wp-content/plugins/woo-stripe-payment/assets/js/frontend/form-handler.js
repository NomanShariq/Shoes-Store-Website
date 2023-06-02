jQuery(function ($) {

    /**
     * @constructor
     */
    function Handler() {
        this.init();
    }

    /**
     * Initialize functionality.
     * @return {[type]}
     */
    Handler.prototype.init = function () {

        // event emitted from WC checkout.js
        $(document.body).on('payment_method_selected', this.payment_method_selected.bind(this));
        $(document.body).on('change', '[name="payment_method"]', this.payment_method_selected.bind(this));
        $(document.body).on('updated_checkout', this.updated_checkout.bind(this));
        $(document.body).on('change', '.wc-stripe-payment-type', this.payment_type_change.bind(this));
        $(document.body).on('updated_wc_div, updated_cart_totals', this.cart_html.bind(this));

        this.saved_payment_methods();

        if ('MutationObserver' in window && null !== document.querySelector('form.checkout')) {
            var observer = new MutationObserver(this.observer_callback.bind(this));
            observer.observe(document.querySelector('form.checkout'), {
                attributes: false,
                childList: true,
                subtree: true
            });
        }

        if ($(document.body).is('.woocommerce-cart')) {
            $(window).on('resize', this.cart_html);
            this.cart_html();
        }
    }

    Handler.prototype.observer_callback = function (records, observer) {
        try {
            if (records.length > 0) {
                for (var i = 0; i < records.length; i++) {
                    if (records[i].addedNodes && records[i].addedNodes.length > 0) {
                        var nodes = records[i].addedNodes;
                        for (var n = 0; n < nodes.length; n++) {
                            if (nodes[n].nodeName === 'UL' && nodes[n].classList && nodes[n].classList.value.indexOf('payment_methods')) {
                                // destroy the select2 elements so they can be re-added since previous events are orphaned
                                $('select.wc-stripe-saved-methods').removeClass('enhanced');
                                $('select.wc-stripe-saved-methods + .select2').remove();
                                this.saved_payment_methods();
                            }
                        }
                    }
                }
            }
        } catch (err) {
            //fail gracefully
        }
    }

    /**
     * @return {[type]}
     */
    Handler.prototype.payment_method_selected = function () {
        var gateway = this.get_payment_method();
        $(document.body).triggerHandler('wc_stripe_payment_method_selected', gateway);
    }

    /**
     * @return {[type]}
     */
    Handler.prototype.updated_checkout = function () {
        this.saved_payment_methods();

        if ($(document.body).hasClass('woocommerce-checkout')) {
            if ($('.payment_methods').width() < 475) {
                $('.payment_methods').addClass('stripe-small');
            }
        }
    }

    /**
     * @return {[type]}
     */
    Handler.prototype.payment_type_change = function () {
        var gateway = this.get_payment_method();
        var $input = $('[name="' + gateway + '_payment_type_key"]:checked');
        if ($input.length === 0 || $input.val() === "new") {
            $('.wc-' + gateway + '-saved-methods-container').slideUp(400, function () {
                $('.wc-' + gateway + '-new-method-container').slideDown(400);
            });
            $(document.body).triggerHandler('wc_stripe_new_method_' + gateway);
        } else {
            $('.wc-' + gateway + '-new-method-container').slideUp(400, function () {
                $('.wc-' + gateway + '-saved-methods-container').slideDown(400);
            });
            $(document.body).triggerHandler('wc_stripe_saved_method_' + gateway);
        }
    }

    /**
     * @return {[type]}
     */
    Handler.prototype.saved_payment_methods = function () {
        if ($().selectWoo) {
            if ($('select.wc-stripe-saved-methods').length && !$('select.wc-stripe-saved-methods').hasClass('enhanced')) {
                $('select.wc-stripe-saved-methods').selectWoo({
                    width: "100%",
                    templateResult: this.output_template,
                    templateSelection: this.output_template,
                    language: {
                        noResults: function () {
                            return wc_stripe_form_handler_params.no_results;
                        }.bind(this)
                    }
                }).addClass('enhanced').trigger('change');
            }
        }
    }

    /**
     * @return {[type]}
     */
    Handler.prototype.get_payment_method = function () {
        return $('[name="payment_method"]:checked').val();
    }

    /**
     * @param  {[type]}
     * @return {[type]}
     */
    Handler.prototype.get_payment_type = function (gateway) {
        return $('[name="' + gateway + '"]:checked').val();
    }

    /**
     * @param  {[type]}
     * @param  {[type]}
     * @return {[type]}
     */
    Handler.prototype.output_template = function (data, container) {
        var classes = $(data.element).attr('class');
        $.each($(data.element).parent().find('option'), function () {
            $(container).removeClass($(this).attr('class'));
        })
        $(container).addClass('wc-stripe-select2-container ' + classes);
        $(document.body).triggerHandler('wc_stripe_payment_method_template', [data, container]);
        return data.text;
    }

    Handler.prototype.cart_html = function () {
        var $button = $('.checkout-button'),
            width = $button.outerWidth();
        if (width && $('.wc_stripe_cart_payment_methods').length) {
            $('.wc_stripe_cart_payment_methods ').width(width);
        }
        if ($button.css('float') !== 'none') {
            $('.wc_stripe_cart_payment_methods ').css('float', $button.css('float'));
        }
    }

    new Handler();

})