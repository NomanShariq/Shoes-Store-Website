<?php


namespace PaymentPlugins\CartFlows\Stripe;


use PaymentPlugins\CartFlows\Stripe\Routes\PaymentIntentRoute;

class RoutesApi {

	private $routes = array();

	public function __construct() {
		$this->initialize();
		add_action( 'rest_api_init', array( $this, 'add_rest_routes' ) );
	}

	private function initialize() {
		$this->routes = array(
			'paymentIntent' => new PaymentIntentRoute( \WC_Stripe_Gateway::load() )
		);
	}

	public function add_rest_routes() {
		foreach ( $this->routes as $route ) {
			register_rest_route( $route->get_namespace(), $route->get_path(), $route->get_route_args() );
		}
	}
}