<?php

namespace PaymentPlugins\Stripe\GermanMarket;


use PaymentPlugins\Stripe\Assets\AssetsApi;

class Package {

	public static function init() {
		add_action( 'woocommerce_init', [ __CLASS__, 'initialize' ] );
	}

	public static function initialize() {
		if ( self::is_enabled() ) {
			$assets = new AssetsApi(
				dirname( __DIR__ ),
				trailingslashit( plugin_dir_url( __DIR__ ) ),
				stripe_wc()->version()
			);
			add_action( 'wp_enqueue_scripts', function () use ( $assets ) {
				if ( wc_post_content_has_shortcode( 'woocommerce_de_check' ) ) {
					$assets->register_style( 'wc-stripe-german-market', 'build/styles.css' );
					wp_enqueue_style( 'wc-stripe-german-market' );
				}
			} );
		}
	}

	private static function is_enabled() {
		return \class_exists( 'Woocommerce_German_Market' );
	}

}