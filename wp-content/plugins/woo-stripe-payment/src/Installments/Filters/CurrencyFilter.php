<?php

namespace PaymentPlugins\Stripe\Installments\Filters;

class CurrencyFilter extends AbstractFilter {

	private $currency;

	/**
	 * @var string[]
	 */
	private $supported_currencies = [ 'MX' => 'MXN', 'BR' => 'BRL' ];

	/**
	 * @var string[]
	 */
	private $supported_countries = [ 'MX', 'BR' ];

	/**
	 * @var string
	 */
	private $account_country;

	public function __construct( $currency, $account_country ) {
		$this->currency        = $currency;
		$this->account_country = $account_country;
	}

	public function is_available() {
		$is_available = false;
		if ( $this->account_country ) {
			$is_available = \in_array( $this->account_country, $this->get_supported_countries() );
		}

		return $is_available
		       && \in_array( $this->currency, $this->get_supported_currencies() )
		       && $this->currency === $this->supported_currencies[ $this->account_country ];
	}

	public function get_supported_countries() {
		//return $this->supported_countries;
		return \array_keys( $this->supported_currencies );
	}

	public function get_supported_currencies() {
		return \array_values( $this->supported_currencies );
	}

}