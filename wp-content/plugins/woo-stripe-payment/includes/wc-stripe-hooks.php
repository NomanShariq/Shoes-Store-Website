<?php

defined( 'ABSPATH' ) || exit();

/**
 * Actions
 */
add_action( 'woocommerce_payment_token_deleted', 'wc_stripe_woocommerce_payment_token_deleted', 10, 2 );
add_action( 'woocommerce_order_status_cancelled', 'wc_stripe_order_cancelled', 10, 2 );
add_action( 'woocommerce_order_status_completed', 'wc_stripe_order_status_completed', 10, 2 );
add_action( 'woocommerce_order_status_processing', 'wc_stripe_order_status_completed', 10, 2 );
add_action( 'wc_stripe_remove_order_locks', 'wc_stripe_remove_order_locks' );
add_action( 'wc_stripe_retry_source_chargeable', 'wc_stripe_retry_source_chargeable' );

/**
 * * Webhook Actions ***
 */
add_action( 'wc_stripe_webhook_source_chargeable', 'wc_stripe_process_source_chargeable', 10, 2 );
add_action( 'wc_stripe_webhook_charge_succeeded', 'wc_stripe_process_charge_succeeded', 10, 2 );
add_action( 'wc_stripe_webhook_charge_failed', 'wc_stripe_process_charge_failed', 10, 2 );
add_action( 'wc_stripe_webhook_charge_pending', 'wc_stripe_process_charge_pending', 10, 1 );
add_action( 'wc_stripe_webhook_payment_intent_succeeded', 'wc_stripe_process_payment_intent_succeeded', 10, 2 );
add_action( 'wc_stripe_webhook_payment_intent_requires_action', 'wc_stripe_process_requires_action', 10, 1 );
add_action( 'wc_stripe_webhook_charge_refunded', 'wc_stripe_process_create_refund' );
add_action( 'wc_stripe_webhook_charge_dispute_created', 'wc_stripe_charge_dispute_created', 10, 1 );
add_action( 'wc_stripe_webhook_charge_dispute_closed', 'wc_stripe_charge_dispute_closed', 10, 1 );
add_action( 'wc_stripe_webhook_review_opened', 'wc_stripe_review_opened', 10, 1 );
add_action( 'wc_stripe_webhook_review_closed', 'wc_stripe_review_closed', 10, 1 );

/**
 * Filters
 */
add_filter( 'wc_stripe_api_options', 'wc_stripe_api_options' );
add_filter( 'woocommerce_payment_gateways', 'wc_stripe_payment_gateways' );
add_filter( 'woocommerce_available_payment_gateways', 'wc_stripe_available_payment_gateways' );
add_action( 'woocommerce_process_shop_subscription_meta', 'wc_stripe_process_shop_subscription_meta', 10, 2 );
add_filter( 'woocommerce_payment_complete_order_status', 'wc_stripe_payment_complete_order_status', 10, 3 );
add_filter( 'woocommerce_get_customer_payment_tokens', 'wc_stripe_get_customer_payment_tokens', 10, 3 );
add_filter( 'woocommerce_credit_card_type_labels', 'wc_stripe_credit_card_labels' );
