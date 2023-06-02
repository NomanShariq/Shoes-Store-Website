<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since  3.1.0
 * @author Payment Plugins
 *
 */
class WC_Stripe_Constants {

	const CUSTOMER_ID = '_wc_stripe_customer';

	const PAYMENT_METHOD_TOKEN = '_payment_method_token';

	const PAYMENT_INTENT_ID = '_payment_intent_id';

	const PAYMENT_INTENT = '_payment_intent';

	const MODE = '_wc_stripe_mode';

	const CHARGE_STATUS = '_wc_stripe_charge_status';

	const SOURCE_ID = '_stripe_source_id';

	const STRIPE_INTENT_ID = '_stripe_intent_id';

	const STRIPE_CUSTOMER_ID = '_stripe_customer_id';

	const SUCCESS = 'success';

	const FAILURE = 'failure';

	const WOOCOMMERCE_STRIPE_ORDER_PAY = 'WOOCOMMERCE_STRIPE_ORDER_PAY';

	const PRODUCT_GATEWAY_ORDER = '_stripe_gateway_order';

	const BUTTON_POSITION = '_stripe_button_position';

	/**
	 *
	 * @var unknown
	 * @since 3.1.3
	 */
	const REDIRECT_HANDLER = 'redirect_handler';

	/**
	 * @since 3.2.11
	 */
	const PROCESSING_PAYMENT = 'processing_payment';

	/**
	 * @since 3.3.25
	 */
	const PROCESSING_ORDER_PAY = 'processing_order_pay';

	/**
	 * @since 3.3.3
	 */
	const REQUIRES_CONFIRMATION = 'requires_confirmation';

	const REQUIRES_ACTION = 'requires_action';

	const SUCCEEDED = 'succeeded';

	const REQUIRES_CAPTURE = 'requires_capture';

	const REQUIRES_PAYMENT_METHOD = 'requires_payment_method';

	const SETUP_INTENT_ID = '_setup_intent_id';

	const BALANCE_TRANSACTION = '_stripe_balance_transaction';

	const STRIPE_FEE = '_stripe_fee';

	const STRIPE_NET = '_stripe_net';

	const STRIPE_CURRENCY = '_stripe_currency';

	const PREV_STATUS = '_stripe_prev_status';

	const VOUCHER_PAYMENT = '_stripe_voucher_payment';

	const INSTALLMENT_PLAN = '_stripe_installment_plan';

	const LIVE = 'live';

	const TEST = 'test';

	const VERSION_KEY = 'stripe_wc_version';

	const INITIAL_INSTALL = 'wc_stripe_initialize_install';

	const AUTOMATIC = 'automatic';

	const MANUAL = 'manual';

	const CART_ARGS = '_stripe_cart_args';

}
