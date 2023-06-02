<?php

namespace PaymentPlugins\Blocks\Stripe\StoreApi;

class EndpointData {

	private $endpoint;

	private $namespace;

	private $data_callback;

	private $schema_callback;

	private $schema_type = ARRAY_A;

	/**
	 * @return mixed
	 */
	public function get_endpoint() {
		return $this->endpoint;
	}

	/**
	 * @param mixed $endpoint
	 */
	public function set_endpoint( $endpoint ): void {
		$this->endpoint = $endpoint;
	}

	/**
	 * @return mixed
	 */
	public function get_namespace() {
		return $this->namespace;
	}

	/**
	 * @param mixed $namespace
	 */
	public function set_namespace( $namespace ): void {
		$this->namespace = $namespace;
	}

	/**
	 * @return mixed
	 */
	public function get_data_callback() {
		return $this->data_callback;
	}

	/**
	 * @param mixed $data_callback
	 */
	public function set_data_callback( $data_callback ): void {
		$this->data_callback = $data_callback;
	}

	/**
	 * @return mixed
	 */
	public function get_schema_callback() {
		return $this->schema_callback;
	}

	/**
	 * @param mixed $schema_callback
	 */
	public function set_schema_callback( $schema_callback ): void {
		$this->schema_callback = $schema_callback;
	}

	/**
	 * @return string
	 */
	public function get_schema_type(): string {
		return $this->schema_type;
	}

	/**
	 * @param string $schema_type
	 */
	public function set_schema_type( string $schema_type ): void {
		$this->schema_type = $schema_type;
	}

	public function to_array() {
		return [
			'endpoint'        => $this->endpoint,
			'namespace'       => $this->namespace,
			'data_callback'   => $this->data_callback,
			'schema_callback' => $this->schema_callback,
			'schema_type'     => $this->schema_type
		];
	}

}