<?php

class WC_Stripe_Admin_Support {

	public static function init() {
		add_action( 'wc_stripe_admin_section_support', array( __CLASS__, 'output' ) );
	}

	public static function output() {
		$report = self::get_status_report();
		$user   = wp_get_current_user();
		$data   = rawurlencode( wp_json_encode( array(
			'report' => $report,
			'name'   => $user->get( 'first_name' ) . ' ' . $user->get( 'last_name' ),
			'email'  => $user->get( 'user_email' )
		) ) );
		wp_add_inline_script(
			'wc-stripe-help-widget',
			"var wcStripeSupportParams = wcStripeSupportParams || JSON.parse( decodeURIComponent( '"
			. esc_js( $data )
			. "' ) );",
			'before'
		);
		include_once dirname( __FILE__ ) . '/views/html-support-page.php';
	}

	private static function get_status_report() {
		$report = wc()->api->get_endpoint_data( '/wc/v3/system_status' );
		if ( ! is_wp_error( $report ) ) {
			unset( $report['subscriptions']['payment_gateway_feature_support'] );
		} else {
			$report = array();
		}

		return $report;
	}

}

WC_Stripe_Admin_Support::init();