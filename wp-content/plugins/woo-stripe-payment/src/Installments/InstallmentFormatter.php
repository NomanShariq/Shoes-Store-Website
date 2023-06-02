<?php

namespace PaymentPlugins\Stripe\Installments;

class InstallmentFormatter {

	const SEPARATOR = ':';

	/**
	 * @param array $plans
	 */
	public static function from_plans( $plans, $amount, $currency ) {
		if ( empty( $plans ) ) {
			$installments['none'] = (object) [
				'text' => __( 'No installment plans available.', 'woo-stripe-payment' )
			];
		} else {
			$formatter    = new self();
			$installments = [];
			$currency     = strtoupper( $currency );
			foreach ( $plans as $plan ) {
				$installment_amount  = $amount / (float) $plan->count;
				$id                  = $formatter->format_plan_id( $plan );
				$installments[ $id ] = (object) [
					'plan'               => $plan,
					'amount'             => $amount,
					'installment_amount' => $installment_amount,
					'text'               => $formatter->format_plan( $plan, $amount, $currency )
				];
			}
			$installments['none'] = [ 'text' => __( 'Do not pay with installment.', 'woo-stripe-payment' ) ];
		}

		return $installments;
	}

	public function format_plan( $plan, $amount, $currency ) {
		$amount = wc_stripe_remove_number_precision( $amount / (float) $plan->count, $currency );
		$text   = sprintf( __( '%1$s / %2$s for %3$s', 'woo-stripe-payment' ), $this->format_price( $amount, [ 'currency' => $currency ] ), $this->get_formatted_interval( $plan->interval ), $this->get_formatted_duration( $plan->interval, $plan->count ) );

		return apply_filters( 'wc_stripe_installment_format_plan', $text, compact( 'plan', 'amount', 'currency' ), $this );
	}

	private function get_formatted_intervals( $count ) {
		return [
			'day'   => _n( 'day', 'days', $count, 'woo-stripe-payment' ),
			'week'  => _n( 'week', 'weeks', $count, 'woo-stripe-payment' ),
			'month' => _n( 'month', 'months', $count, 'woo-stripe-payment' ),
		];
	}

	private function get_formatted_interval( $interval ) {
		return $this->get_formatted_intervals( 1 )[ $interval ];
	}

	private function get_formatted_duration( $interval, $count ) {
		return sprintf( '%1$s %2$s', $count, $this->get_formatted_intervals( $count )[ $interval ] );
	}

	public function format_plan_id( $plan ) {
		return sprintf( '%1$s%2$s%3$s%2$s%4$s', $plan->count, self::SEPARATOR, $plan->interval, $plan->type );
	}

	public function parse_plan_from_id( $id, $object = false ) {
		list( $count, $interval, $type ) = explode( self::SEPARATOR, $id );
		$plan = compact( 'count', 'interval', 'type' );

		return $object ? (object) $plan : $plan;
	}

	public function format_price( $price, $args ) {
		$price    = (float) $price;
		$defaults = [
			'currency'           => '',
			'decimal_separator'  => wc_get_price_decimal_separator(),
			'thousand_separator' => wc_get_price_thousand_separator(),
			'decimals'           => wc_get_price_decimals(),
			'price_format'       => get_woocommerce_price_format(),
		];
		$args     = array_merge( $defaults, $args );
		$price    = number_format( $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] );

		return sprintf( $args['price_format'], get_woocommerce_currency_symbol( $args['currency'] ), $price );
	}

	public function is_valid_plan( $plan_id ) {
		return ! empty( $plan_id ) && preg_match( sprintf( '/^(\d+)\%1$s(\w+)\%1$s(\w+)$/', self::SEPARATOR ), $plan_id ) == 1;
	}

}