(function ($) {

    /**
     * @constructor
     */
    function Settings() {
        $(document.body).on('change', '.gpay-button-option', this.update_button.bind(this));
        this.init();
    }

    Settings.prototype.init = function () {
        this.create_payments_client();
        this.update_button();
    }

    Settings.prototype.create_payments_client = function () {
        this.paymentsClient = new google.payments.api.PaymentsClient({environment: "TEST"});
    }

    /**
     * @return {[type]}
     */
    Settings.prototype.update_button = function () {
        if (this.$button) {
            this.$button.remove();
        }
        this.$button = $(this.paymentsClient.createButton({
            onClick: function () {
            },
            buttonColor: $('.button-color').val(),
            buttonType: $('.button-style').val()
        }));
        $('#gpay-button').append(this.$button);

        if ($('.gpay-button-shape').val() === 'rect') {
            this.$button.find('button').removeClass('new_style');
        }
    }

    new Settings();

}(jQuery))