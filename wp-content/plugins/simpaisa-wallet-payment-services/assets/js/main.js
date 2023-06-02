jQuery(function ($) {
	var payment_method = jQuery('input[name="payment_method"]:checked').val()
	// NAV CHANGING CLASSES ADDING
	jQuery(document.body).on('click', '.simpaisa-jazz-easy-nav-link', function () {
		if (jQuery(this).hasClass('easypaisa')) {
			jQuery(this).addClass('active');
			jQuery(".jazzcash").removeClass('active');
			jQuery(".sp_wallet_account_type").val("Easypaisa");
		}
		else if (jQuery(this).hasClass('jazzcash')) {
			jQuery(this).addClass('active');
			jQuery(".easypaisa").removeClass('active');
			jQuery(".sp_wallet_account_type").val("Jazzcash");
		}
	});

	// ON ACCOUNT NO KEYUP VALIDATION
	jQuery(document.body).on('keyup', '#sp_wallet_account', function (e) {
		if (payment_method == 'simpaisa_woo_jz_ep_wallet') {

			jQuery(this).val(jQuery(this).val().replace(/[^0-9]/g, ''));
			var mob = /((\03)|03)[0-9]{9}$/;
			var currentValue = jQuery(this).val();
			if (mob.test(currentValue) && currentValue.length <= 11) {
				jQuery("#sp_wallet_account").css('border', '1px solid green');
				jQuery(".simpaisa-jazz-easy-phone-err").html("");
			} else {
				jQuery("#sp_wallet_account").css('border', '1px solid red');
				jQuery(".simpaisa-jazz-easy-phone-err").html("Invalid phone number");
			}
		}
	});

	// ON PLACE ORDER BTN VALIDATION
	var checkout_form = jQuery('form.checkout');
	checkout_form.on('checkout_place_order', function () {
		var payment_method = jQuery('input[name="payment_method"]:checked').val();
		if (payment_method == 'simpaisa_woo_jz_ep_wallet') {
			jQuery("#sp_wallet_account").val(jQuery("#sp_wallet_account").val().replace(/[^0-9]/g, ''));
			var mob = /((\03)|03)[0-9]{9}$/;
			var currentValue = jQuery("#sp_wallet_account").val();
			if (mob.test(currentValue) && currentValue.length <= 11) {
				jQuery("#sp_wallet_account").css('border', '1px solid green');
				jQuery(".simpaisa-jazz-easy-phone-err").html("");
				return true;
			} else {
				jQuery("#sp_wallet_account").css('border', '1px solid red');
				jQuery(".simpaisa-jazz-easy-phone-err").html("Invalid phone number");
				return false;
			}
		}
	});

});