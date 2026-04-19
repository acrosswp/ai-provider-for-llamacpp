<?php
/**
 * Plugin initializer class.
 *
 * @since 0.0.1
 *
 * @package AcrossWP\AiProviderForLlamaCpp
 */

declare(strict_types=1);

namespace AcrossWP\AiProviderForLlamaCpp;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use AcrossWP\AiProviderForLlamaCpp\Provider\LlamaCppProvider;
use AcrossWP\AiProviderForLlamaCpp\Settings\LlamaCppSettings;

/**
 * Plugin class.
 *
 * @since 0.0.1
 */
class Plugin {

	/**
	 * Transient key used to cache the available model IDs from the llama.cpp server.
	 */
	private const MODEL_TRANSIENT_KEY = 'aipf_llamacpp_model_ids';

	/**
	 * Initializes the plugin.
	 *
	 * @since 0.0.1
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register_provider' ), 5 );
		add_action( 'init', array( $this, 'register_fallback_auth' ), 15 );
		add_action( 'init', array( $this, 'initialize_settings' ) );
		add_filter(
			'plugin_action_links_' . plugin_basename( AIPF_LLAMACPP_PLUGIN_FILE ),
			array( $this, 'plugin_action_links' )
		);
		add_filter( 'http_request_host_is_external', array( $this, 'allow_localhost_requests' ), 10, 3 );
		add_filter( 'http_allowed_safe_ports', array( $this, 'allow_llamacpp_ports' ), 10, 3 );
		add_filter( 'wpai_preferred_text_models', array( $this, 'add_preferred_text_models' ) );
		add_action( 'update_option_aipf_llamacpp_settings', array( $this, 'clear_model_transient' ) );
	}

	/**
	 * Gets the llama.cpp base URL.
	 *
	 * @since 0.0.1
	 *
	 * @return string
	 */
	private function get_base_url(): string {
		return LlamaCppSettings::get_base_url();
	}

	/**
	 * Registers the llama.cpp provider with the AI Client.
	 *
	 * @since 0.0.1
	 */
	public function register_provider(): void {
		if ( ! class_exists( AiClient::class ) ) {
			return;
		}

		$registry = AiClient::defaultRegistry();

		if ( $registry->hasProvider( LlamaCppProvider::class ) ) {
			return;
		}

		$registry->registerProvider( LlamaCppProvider::class );
	}

	/**
	 * Registers fallback authentication for the llama.cpp provider.
	 *
	 * Local llama.cpp instances work without an API key, so we register an
	 * empty key as a fallback when no credentials have been configured.
	 *
	 * @since 0.0.1
	 */
	public function register_fallback_auth(): void {
		if ( ! class_exists( AiClient::class ) ) {
			return;
		}

		$registry = AiClient::defaultRegistry();

		if ( ! $registry->hasProvider( 'llamacpp' ) ) {
			return;
		}

		$auth = $registry->getProviderRequestAuthentication( 'llamacpp' );
		if ( null !== $auth ) {
			return;
		}

		$registry->setProviderRequestAuthentication(
			'llamacpp',
			new ApiKeyRequestAuthentication( '' )
		);
	}

	/**
	 * Initializes the llama.cpp settings.
	 *
	 * @since 0.0.1
	 */
	public function initialize_settings(): void {
		$settings = new LlamaCppSettings();
		$settings->init();

	}

	/**
	 * Adds available llama.cpp models to the AI preferred text models list.
	 *
	 * On a cache hit the result is returned immediately with no HTTP request.
	 * On a cache miss (first use, or after the TTL expires) a short-timeout
	 * request is made to the llama.cpp server. A failed request is itself
	 * cached for a short time so the server is not polled on every page load
	 * while it is down.
	 *
	 * @since 0.0.1
	 *
	 * @param array<int, array{string, string}> $preferred_models The current preferred models.
	 * @return array<int, array{string, string}>
	 */
	public function add_preferred_text_models( array $preferred_models ): array {
		$cached = get_transient( self::MODEL_TRANSIENT_KEY );
		if ( is_array( $cached ) ) {
			return array_merge( $preferred_models, $cached );
		}

		$url = rtrim( $this->get_base_url(), '/' ) . '/v1/models';

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 2,
				'sslverify' => wp_parse_url( $url, PHP_URL_HOST ) !== '127.0.0.1',
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Cache the "server down" result briefly so we do not hammer the
			// server on every request while it is offline.
			set_transient( self::MODEL_TRANSIENT_KEY, array(), 30 );
			return $preferred_models;
		}

		$body        = json_decode( wp_remote_retrieve_body( $response ), true );
		$model_prefs = array();
		foreach ( (array) ( $body['data'] ?? array() ) as $model ) {
			if ( ! empty( $model['id'] ) ) {
				$model_prefs[] = array( 'llamacpp', $model['id'] );
			}
		}

		set_transient( self::MODEL_TRANSIENT_KEY, $model_prefs, HOUR_IN_SECONDS );

		return array_merge( $preferred_models, $model_prefs );
	}

	/**
	 * Clears the cached model list when the plugin settings are updated.
	 *
	 * This ensures that a URL change takes effect immediately rather than
	 * waiting for the transient to expire.
	 *
	 * @since 0.0.1
	 */
	public function clear_model_transient(): void {
		delete_transient( self::MODEL_TRANSIENT_KEY );
	}

	/**
	 * Adds a "Settings" link to the plugin's action links on the Plugins page.
	 *
	 * @since 0.0.1
	 *
	 * @param array<string> $links Existing action links.
	 * @return array<string> Modified action links.
	 */
	public function plugin_action_links( array $links ): array {
		$connectors_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			admin_url( 'options-connectors.php' ),
			esc_html__( 'Connectors', 'ai-provider-for-llamacpp' )
		);
		$settings_link   = sprintf(
			'<a href="%1$s">%2$s</a>',
			admin_url( 'admin.php?page=aipf-llamacpp' ),
			esc_html__( 'Settings', 'ai-provider-for-llamacpp' )
		);

		array_unshift( $links, $connectors_link );
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Allows localhost requests to the llama.cpp host.
	 *
	 * @since 0.0.1
	 *
	 * @param bool   $external Whether the request is considered external.
	 * @param string $host     The host of the request.
	 * @param string $url      The full request URL.
	 * @return bool
	 */
	public function allow_localhost_requests( bool $external, string $host, string $url ): bool {
		$request_host  = wp_parse_url( $url, PHP_URL_HOST );
		$base_url_host = wp_parse_url( $this->get_base_url(), PHP_URL_HOST );

		// Allow requests to the configured server host.
		if ( $request_host === $base_url_host ) {
			return true;
		}

		// Allow requests to localhost (sub-server ports in router mode).
		if ( in_array( $request_host, array( '127.0.0.1', 'localhost' ), true ) ) {
			return true;
		}

		return $external;
	}

	/**
	 * Allows the llama.cpp server port through WordPress's safe-ports list.
	 *
	 * @since 0.0.1
	 *
	 * @param array<int> $ports The currently allowed ports.
	 * @return array<int>
	 */
	public function allow_llamacpp_ports( array $ports, string $host = '', string $url = '' ): array {
		$base_port = wp_parse_url( $this->get_base_url(), PHP_URL_PORT );

		if ( $base_port ) {
			$ports[] = (int) $base_port;
		}

		// In router mode, sub-servers use random high ports on localhost.
		// Allow any port when the request target is localhost.
		if ( in_array( $host, array( '127.0.0.1', 'localhost' ), true ) ) {
			$request_port = (int) wp_parse_url( $url, PHP_URL_PORT );
			if ( $request_port > 0 ) {
				$ports[] = $request_port;
			}
		}

		return $ports;
	}
}
