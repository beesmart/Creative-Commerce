<?php
/**
 * @package   Barn2\woocommerce-product-filters
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

namespace Barn2\Plugin\WC_Filters;

use Barn2\Plugin\WC_Filters\Integrations\Custom_Fields_Provider;

/**
 * Helper class that handles all supported custom fields provider.
 *
 * Default fields providers are:
 * - ACF
 * - Easy Post Types & Fields
 */
class Meta_Fields {

	/**
	 * Api Instance.
	 *
	 * @var Api
	 */
	public $api;

	/**
	 * List of providers that provide custom fields.
	 *
	 * @var array
	 */
	public $providers = [];

	/**
	 * Get things started.
	 *
	 * @param array $services the list of service providers of the plugin.
	 */
	public function __construct( $services ) {
		$this->api = $services['api'];
		$this->collect_providers( $services );
		$this->register_api();
	}

	/**
	 * Determine if at least one provider has been registered.
	 *
	 * @return boolean
	 */
	public function has_providers() {
		return count( $this->providers ) > 0;
	}

	/**
	 * Find all providers.
	 *
	 * @param array $services the list of service providers of the plugin.
	 */
	private function collect_providers( $services ) {
		foreach ( $services as $service ) {
			if ( $service instanceof Custom_Fields_Provider ) {
				$this->providers[] = $service;
			}
		}
	}

	/**
	 * Get list of providers but only the registered ones.
	 *
	 * @return array
	 */
	public function get_providers() {

		$providers = [];

		foreach ( $this->providers as $service ) {
			if ( $service instanceof Custom_Fields_Provider && $service->is_registered() ) {
				$providers[] = $service;
			}
		}

		return $providers;

	}

	/**
	 * Register route specific for custom fields.
	 *
	 * @return void
	 */
	public function register_api() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register cf api route.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->api::API_NAMESPACE,
			'/cf/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'print_fields' ],
				'permission_callback' => [ $this->api, 'check_permissions' ],
			]
		);
	}

	/**
	 * Get the list of fields.
	 *
	 * @return array
	 */
	public function get_fields() {

		$fields = [];

		foreach ( $this->get_providers() as $provider ) {
			$fields = array_merge( $fields, $provider->get_fields() );
		}

		return $fields;

	}

	/**
	 * Get the provider of a given field.
	 *
	 * @param string $meta_key
	 * @return Custom_Fields_Provider
	 */
	public function get_field_provider( string $meta_key ) {
		foreach ( $this->get_providers() as $provider ) {
			$fields = $provider->get_fields();

			foreach ( $fields as $field ) {
				if ( $field['value'] === $meta_key ) {
					return $provider;
				}
			}
		}

		return false;
	}

	/**
	 * Get the value of a field from it's provider.
	 *
	 * @param string $meta_key
	 * @param string|int $post_id
	 * @return mixed
	 */
	public function get_field_value( string $meta_key, $post_id ) {
		$provider = $this->get_field_provider( $meta_key );

		if ( $provider instanceof Custom_Fields_Provider ) {
			return $provider->get_field_value( $meta_key, $post_id );
		}

		return false;
	}

	/**
	 * Get the display value of a field from it's provider.
	 *
	 * @param string $meta_key
	 * @param string $option
	 * @return mixed
	 */
	public function get_field_display_value( string $meta_key, $option ) {

		$provider = $this->get_field_provider( $meta_key );

		if ( $provider instanceof Custom_Fields_Provider ) {
			return $provider->get_field_display_value( $meta_key, $option );
		}

		return false;

	}

	/**
	 * Returns all fields via the rest api.
	 *
	 * @return \WP_REST_Response
	 */
	public function print_fields() {

		return new \WP_REST_Response(
			[
				'success' => true,
				'fields'  => $this->get_fields(),
			],
			200
		);

	}

}
