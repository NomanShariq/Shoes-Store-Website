jQuery(function ($) {

    function Settings() {
        this.params = wc_stripe_setting_params;
        this.prefix = '#' + $('#wc_stripe_prefix').val();
        this.init();
    }

    /**
     * [init description]
     * @return {[type]} [description]
     */
    Settings.prototype.init = function () {
        $('[name^="woocommerce_stripe"]').on('change', this.display_children.bind(this));

        $('select.stripe-accepted-cards').on('select2:select', this.reorder_multiselect);

        $('.api-register-domain').on('click', this.register_domain.bind(this));

        $('.wc-stripe-create-webhook').on('click', this.manage_webhook.bind(this));

        $('.wc-stripe-connection-test').on('click', this.do_connection_test.bind(this));

        $('.stripe-delete-connection').on('click', this.do_delete_connection.bind(this));

        if (typeof (wc_stripe_admin_notices) != 'undefined') {
            this.display_notices();
        }

        this.display_children();

        if (window.location.search.match(/_stripe_connect_nonce/)) {
            history.pushState({}, '', window.location.pathname + '?page=wc-settings&tab=checkout&section=stripe_api');
        }
    }

    /**
     * [display_children description]
     * @param  {[type]} e [description]
     * @return {[type]}   [description]
     */
    Settings.prototype.display_children = function (e) {
        $('[data-show-if]').each(function (i, el) {
            var $this = $(el);
            var values = $this.data('show-if');
            var hidden = [];
            $.each(values, function (k, v) {
                var $key = $(this.prefix + k);
                if (hidden.indexOf($this.attr('id')) == -1) {
                    if ($key.is(':checkbox')) {
                        if ($key.is(':checked') == v) {
                            $this.closest('tr').show();
                        } else {
                            $this.closest('tr').hide();
                            hidden.push($this.attr('id'));
                        }
                    } else {
                        if ($key.val() == v) {
                            $this.closest('tr').show();
                        } else {
                            $this.closest('tr').hide();
                            hidden.push($this.attr('id'));
                        }
                    }
                } else {
                    $this.closest('tr').hide();
                    hidden.push($this.attr('id'));
                }
            }.bind(this));
        }.bind(this));
    }

    /**
     * [reorder_multiselect description]
     * @param  {[type]} e [description]
     * @return {[type]}   [description]
     */
    Settings.prototype.reorder_multiselect = function (e) {
        var element = e.params.data.element;
        var $element = $(element);
        $element.detach();
        $(this).append($element);
        $(this).trigger('change');
    }

    /**
     * [register_domain description]
     * @return {[type]} [description]
     */
    Settings.prototype.register_domain = function (e) {
        e.preventDefault();
        this.block();
        $.ajax({
            url: this.params.routes.apple_domain,
            dataType: 'json',
            method: 'POST',
            data: {_wpnonce: this.params.rest_nonce, hostname: window.location.hostname}
        }).done(function (response) {
            this.unblock();
            if (response.code) {
                window.alert(response.message);
            } else {
                window.alert(response.message);
            }
        }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
            this.unblock();
            window.alert(errorThrown);
        }.bind(this))
    }

    Settings.prototype.manage_webhook = function (e) {
        e.preventDefault();
        if ($(e.currentTarget).is('.wc-stripe-delete-webhook')) {
            this.delete_webhook();
        } else {
            this.create_webhook();
        }
    }

    Settings.prototype.create_webhook = function () {
        this.block();
        var env = $('#woocommerce_stripe_api_mode').val();
        $.ajax({
            url: this.params.routes.create_webhook,
            dataType: 'json',
            method: 'POST',
            data: {_wpnonce: this.params.rest_nonce, environment: env}
        }).done(function (response) {
            this.unblock();
            if (response.code) {
                window.alert(response.message);
            } else {
                $('#woocommerce_stripe_api_webhook_secret_' + env).val(response.secret);
                window.alert(response.message);
                window.location.reload();
            }
        }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
            this.unblock();
            window.alert(errorThrown);
        }.bind(this))
    }

    Settings.prototype.delete_webhook = function () {
        this.block();
        var mode = $('#woocommerce_stripe_api_mode').val();
        $.ajax({
            url: this.params.routes.delete_webhook,
            dataType: 'json',
            method: 'POST',
            data: {_wpnonce: this.params.rest_nonce, mode: mode}
        }).done(function (response) {
            this.unblock();
            if (response.code) {
                window.alert(response.message);
            } else {
                $('#woocommerce_stripe_api_webhook_secret_' + mode).val('');
                window.location.reload();
            }
        }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
            this.unblock();
            window.alert(errorThrown);
        }.bind(this))
    }

    Settings.prototype.do_connection_test = function (e) {
        e.preventDefault();
        this.block();
        var mode = $('#woocommerce_stripe_api_mode').val();
        $.ajax({
            url: this.params.routes.connection_test,
            dataType: 'json',
            method: 'POST',
            data: (function () {
                var data = {
                    _wpnonce: this.params.rest_nonce,
                    mode: mode
                };
                if (mode === 'test') {
                    data.secret_key = $('#woocommerce_stripe_api_secret_key_test').val();
                    data.publishable_key = $('#woocommerce_stripe_api_publishable_key_test').val();
                }
                return data;
            }.bind(this)())
        }).done(function (response) {
            this.unblock();
            if (response.code) {
                window.alert(response.message);
            } else {
                window.alert(response.message);
            }
        }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
            this.unblock();
            window.alert(errorThrown);
        }.bind(this))
    }

    Settings.prototype.display_notices = function () {
        $.each(wc_stripe_admin_notices, function (idx, notice) {
            $('.woo-nav-tab-wrapper').after(notice);
        }.bind(this))
    }

    /**
     * [block description]
     * @param  {[type]} $el [description]
     * @return {[type]}     [description]
     */
    Settings.prototype.block = function () {
        $('.wc-stripe-settings-container').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
    }

    /**
     * [unblock description]
     * @param  {[type]} $el [description]
     * @return {[type]}     [description]
     */
    Settings.prototype.unblock = function () {
        $('.wc-stripe-settings-container').unblock();
    }

    Settings.prototype.do_delete_connection = function (e) {
        e.preventDefault();
        if (confirm(this.params.messages.delete_connection)) {
            this.block();
            $.ajax({
                method: 'POST',
                url: this.params.routes.delete_connection,
                dataType: 'json',
                data: {_wpnonce: this.params.rest_nonce}
            }).done(function (response) {
                this.unblock();
                if (!response.code) {
                    window.location.reload();
                } else {
                    window.alert(response.message);
                }
            }.bind(this)).fail(function () {
                this.unblock();
            }.bind(this));
        }
    }

    new Settings();

});