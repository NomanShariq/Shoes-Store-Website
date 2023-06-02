<?php


namespace PaymentPlugins\CartFlows\Stripe\Routes;


abstract class AbstractRoute {

	protected $namespace = 'wc-stripe/v1/cartflows';

	/**
	 * @var \WC_Stripe_Gateway
	 */
	protected $client;

	/**
	 * AbstractRoute constructor.
	 *
	 * @param \WC_Stripe_Gateway $client
	 */
	public function __construct( \WC_Stripe_Gateway $client ) {
		$this->client = $client;
	}

	public function get_namespace() {
		return $this->namespace;
	}

	public abstract function get_route_args();

	public abstract function get_path();

	public function handle_request( \WP_REST_Request $request ) {
		$method = strtolower( 'handle_' . $request->get_method() . '_request' );

		if ( method_exists( $this, $method ) ) {
			try {
				$result = $this->{$method}( $request );

				return rest_ensure_response( $result );
			} catch ( \Exception $e ) {
				return new \WP_Error( 'wc-stripe-cartflow-error', $e->getMessage(), array(
					'status' => 200
				) );
			}
		}
	}
}