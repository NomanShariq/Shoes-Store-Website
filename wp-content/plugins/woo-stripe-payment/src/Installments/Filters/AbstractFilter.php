<?php

namespace PaymentPlugins\Stripe\Installments\Filters;

abstract class AbstractFilter {

	abstract function is_available();
}