(function ($) {
    function Product() {
        this.init();
    }

    Product.prototype.params = {
        loadingClass: 'woocommerce-input-toggle--loading',
        enabledClass: 'woocommerce-input-toggle--enabled',
        disabledClass: 'woocommerce-input-toggle--disabled'
    }

    Product.prototype.init = function () {
        $('table.wc_gateways').sortable({
            items: 'tr',
            axis: 'y',
            cursor: 'move',
            scrollSensitivity: 40,
            forcePlaceholderSize: true,
            helper: 'clone',
            opacity: 0.65,
            placeholder: 'wc-metabox-sortable-placeholder',
            start: function (event, ui) {
                ui.item.css('background-color', '#f6f6f6');
            },
            stop: function (event, ui) {
                ui.item.removeAttr('style');
            },
            change: function () {
                this.setting_changed();
            }.bind(this)
        });

        $('table.wc_gateways').find('.wc-move-down, .wc-move-up').on('click', this.move_gateway.bind(this));
        $('table.wc_gateways .wc-stripe-product-gateway-enabled').on('click', this.enable_gateway.bind(this));
        $('.wc-stripe-save-product-data').on('click', this.save.bind(this));
        $('#stripe_product_data select').on('change', this.setting_changed.bind(this));
    }

    /**
     * [Move the payment gateway up or down]
     * @return {[type]} [description]
     */
    Product.prototype.move_gateway = function (e) {
        var $this = $(e.currentTarget);
        var $row = $this.closest('tr');

        var moveDown = $this.is('.wc-move-down');

        if (moveDown) {
            var $next = $row.next('tr');
            if ($next && $next.length) {
                $next.after($row);
            }
        } else {
            var $prev = $row.prev('tr');
            if ($prev && $prev.length) {
                $prev.before($row);
            }
        }
        this.setting_changed();
    }

    Product.prototype.setting_changed = function () {
        $('#wc_stripe_update_product').val('true');
    }

    /**
     * [enable_gateway description]
     * @param  {[type]} e [description]
     * @return {[type]}   [description]
     */
    Product.prototype.enable_gateway = function (e) {
        e.preventDefault();
        var $el = $(e.currentTarget),
            $row = $el.closest('tr'),
            $toggle = $el.find('.woocommerce-input-toggle');
        $toggle.addClass(this.params.loadingClass);
        $.ajax({
            url: wc_stripe_product_params.routes.enable_gateway,
            method: 'POST',
            dataType: 'json',
            data: {
                _wpnonce: wc_stripe_product_params._wpnonce,
                product_id: $('#post_ID').val(),
                gateway_id: $row.data('gateway_id')
            }
        }).done(function (response) {
            $toggle.removeClass(this.params.loadingClass);
            if (response.enabled) {
                $toggle.addClass(this.params.enabledClass).removeClass(this.params.disabledClass);
            } else {
                $toggle.removeClass(this.params.enabledClass).addClass(this.params.disabledClass);
            }
        }.bind(this)).fail(function (xhr, errorStatus, errorThrown) {
            $toggle.removeClass(this.params.loadingClass);
        }.bind(this))
    }

    Product.prototype.save = function (e) {
        e.preventDefault();
        var $button = $(e.currentTarget);
        var gateways = [],
            charge_types = [];
        $('[name^="stripe_gateway_order"]').each(function (idx, el) {
            gateways.push($(el).val());
        });
        $('[name^="stripe_capture_type"]').each(function (idx, el) {
            charge_types.push({
                gateway: $(el).closest('tr').data('gateway_id'),
                value: $(el).val()
            });
        })
        $button.toggleClass('disabled').prop('disabled', true);
        $button.next('.spinner').toggleClass('is-active');
        $.ajax({
            url: wc_stripe_product_params.routes.save,
            method: 'POST',
            dataType: 'json',
            data: {
                _wpnonce: wc_stripe_product_params._wpnonce,
                gateways: gateways,
                charge_types: charge_types,
                product_id: $('#post_ID').val(),
                position: $('#_stripe_button_position').val()
            }
        }).done(function (response) {
            $button.toggleClass('disabled').prop('disabled', false);
            $button.next('.spinner').toggleClass('is-active');
        }).fail(function (xhr, errorStatus, errorthrown) {
            $button.toggleClass('disabled').prop('disabled', false);
            $button.next('.spinner').toggleClass('is-active');
        }.bind(this))
    }

    new Product();
}(jQuery))