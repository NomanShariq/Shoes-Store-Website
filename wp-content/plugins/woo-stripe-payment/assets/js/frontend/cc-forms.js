(function($) {
	function minimalist() {
		this.index = 1;
		this.total_steps = $('.wc-stripe-steps').data('steps');
		this.updateSteppers();
		this.updateStyles();
		$(document.body).on('click', '.wc-stripe-back', this.prev.bind(this))
			.on('click', '.wc-stripe-next', this.next.bind(this))
			.on('updated_checkout', this.updated_checkout.bind(this));
	}

	minimalist.prototype.next = function(e) {
		e.preventDefault();
		this.index++;
		$('.wc-stripe-minimalist-form .field-container[data-index="' + this.index + '"]').removeClass('field-container--hidden');
		$('.wc-stripe-minimalist-form .field-container[data-index="' + (this.index - 1) + '"]').addClass('field-container--hidden');
		this.updateSteppers();
	}

	minimalist.prototype.prev = function(e) {
		e.preventDefault();
		this.index--;
		$('.wc-stripe-minimalist-form .field-container[data-index="' + (this.index + 1) + '"]').addClass('field-container--hidden');
		$('.wc-stripe-minimalist-form .field-container[data-index="' + this.index + '"]').removeClass('field-container--hidden');
		this.updateSteppers();
	}

	minimalist.prototype.updateText = function() {
		var text = $('.wc-stripe-step').data('text');
		$('.wc-stripe-step').text(text.replace('%s', this.index));
	}

	minimalist.prototype.updateSteppers = function() {
		if (this.index == 1) {
			$('.wc-stripe-back').hide();
		} else if (this.index == this.total_steps) {
			$('.wc-stripe-next').hide();
		} else {
			$('.wc-stripe-next').show();
			$('.wc-stripe-back').show();
		}
		this.updateText();
	}

	minimalist.prototype.updated_checkout = function() {
		$('.wc-stripe-minimalist-form .field-container[data-index="' + this.index + '"]').removeClass('field-container--hidden');
		this.updateSteppers();
		this.updateStyles();
	}

	minimalist.prototype.updateStyles = function() {
		if (wc_stripe.credit_card) {
			var width = $('ul.payment_methods').outerWidth();
			if ($('ul.payment_methods').outerWidth() < 400) {
				var options = {
					style: {
						base: {
							fontSize: '18px'
						}
					}
				};
				wc_stripe.credit_card.cardNumber.update(options);
				wc_stripe.credit_card.cardExpiry.update(options);
				wc_stripe.credit_card.cardCvc.update(options);
				$('ul.payment_methods').addClass('wc-stripe-sm');
			}
		}
	}

	new minimalist();
}(jQuery))