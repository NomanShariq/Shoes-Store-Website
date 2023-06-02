<?php

class WC_Stripe_Admin_Welcome {

	public static function output() {
		$data = rawurlencode( wp_json_encode( array(
			'routes' => array(
				'signup' => WC_Stripe_Rest_API::get_admin_endpoint( stripe_wc()->rest_api->signup->rest_uri( 'contact' ) )
			)
		) ) );
		wp_add_inline_script(
			'wc-stripe-main-script',
			"var wcStripeSignupParams = wcStripeSignupParams || JSON.parse( decodeURIComponent( '"
			. esc_js( $data )
			. "' ) );",
			'before'
		);
		include_once dirname( __FILE__ ) . '/views/html-welcome-page.php';
	}

}