(function ($) {
    var params = wcStripeSignupParams;
    $(document.body).on('click', '#wc-stripe-signup', function (e) {
        e.preventDefault();
        submitSigupData($(e.currentTarget));
    }).on('click', '.wc-stripe-notice .dismiss', removeNotice);

    function getLoaderHtml() {
        return '<div class="wc-stripe-loader">' +
            '<div></div>' +
            '<div></div>' +
            '<div></div>' +
            '</div>';
    }

    function removeLoader(el) {
        $(el).find('.wc-stripe-loader').remove();
    }

    function addSuccessNotice(msg) {
        addNotice('<span class="dashicons dashicons-yes"></span><div>' + msg + '</div>', 'success');
    }

    function addErrorNotice(msg, className) {
        addNotice('<span class="dashicons dashicons-info"></span><div>' + msg + '</div>', 'error');
    }

    function addNotice(msg, className) {
        $(document.body).append('<div class="wc-stripe-notice ' + className + '">' + msg + '<div class="dismiss"><span class="dashicons dashicons-dismiss"></span></div></div>');
        setTimeout(removeNotice.bind(null, {
            currentTarget: $('.wc-stripe-notice').last()[0]
        }), 5000);
    }

    function removeNotice(e) {
        $(e.currentTarget).closest('.wc-stripe-notice').remove();
    }

    function submitSigupData(el) {
        el.prop('disabled', true).prepend(getLoaderHtml());
        var data = el.closest('form').serialize();
        $.ajax({
            url: params.routes.signup,
            dataType: 'json',
            method: 'POST',
            data: data
        }).done(function (response) {
            el.prop('disabled', false);
            removeLoader(el);
            if (response.code) {
                addErrorNotice(response.message);
            } else {
                addSuccessNotice(response.message);
                $('.wc-stripe-signup-container').remove();
            }
        }).fail(function (xhr, textStatus, errorThrown) {
            el.prop('disabled', false);
            removeLoader(el);
            if (xhr.hasOwnProperty('responseJSON')) {
                addErrorNotice(xhr.responseJSON.message);
            } else {
                addErrorNotice(errorThrown);
            }
        });
    }

}(jQuery));