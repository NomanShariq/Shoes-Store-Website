<?php

namespace PaymentPlugins\Stripe\Installments\Filters;

class OrderTotalFilter extends AbstractFilter {

	private $total;

	private $currency;

	private $limits = [
		'MXN' => 300,
		'BRL' => 1
	];

	public function __construct( $total, $currency ) {
		$this->total    = $total;
		$this->currency = $currency;
	}

	function is_available() {
		return $this->total >= $this->limits[ $this->currency ];
	}

}