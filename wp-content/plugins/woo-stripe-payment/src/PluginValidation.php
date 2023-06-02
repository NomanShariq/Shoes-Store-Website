<?php

namespace PaymentPlugins\Stripe;

class PluginValidation {

	public static function is_valid( $callback ) {
		try {
			self::assert_php_version();

			$callback();
		} catch ( \Exception $e ) {
			self::add_admin_notice( $e->getMessage() );
		}
	}

	private static function assert_php_version() {
		if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
			throw new \Exception( sprintf( __( 'Your PHP version is %s but Stripe requires version 5.6+.', 'woo-stripe-payment' ), PHP_VERSION ) );
		}
	}

	private static function add_admin_notice( $msg ) {
		add_action( 'admin_notices', function () use ( $msg ) {
			?>
            <div class="notice notice-error woocommerce-message">
                <h4>
					<?php echo $msg ?>
                </h4>
            </div>
			<?php
		} );
	}

}