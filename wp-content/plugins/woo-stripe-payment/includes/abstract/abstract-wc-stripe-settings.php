<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author PaymentPlugins
 * @package Stripe/Abstract
 *
 */
abstract class WC_Stripe_Settings_API extends WC_Settings_API {

	use WC_Stripe_Settings_Trait;

	public function __construct() {
		$this->init_form_fields();
		$this->init_settings();
		$this->hooks();
	}

	public function hooks() {
		add_action( 'wc_stripe_localize_' . $this->id . '_settings', array( $this, 'localize_settings' ) );
	}

	public function localize_settings() {
		return $this->settings;
	}
}
