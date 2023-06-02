<?php

namespace PaymentPlugins\CheckoutWC\Stripe;

class Main {

	public static function init() {
		if ( self::is_active() ) {
			new AssetsController( stripe_wc()->version(), plugin_dir_url( __DIR__ ) );
		}
	}

	private static function is_active() {
		return defined( 'CFW_NAME' );
	}

}