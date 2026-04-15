<?php
/**
 * Model metadata directory class for llama.cpp.
 *
 * @since 0.0.1
 *
 * @package AcrossWP\AiProviderForLlamaCpp
 */

declare(strict_types=1);

namespace AcrossWP\AiProviderForLlamaCpp\Metadata;

use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModelMetadataDirectory;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use AcrossWP\AiProviderForLlamaCpp\Provider\LlamaCppProvider;

/**
 * Class for the llama.cpp model metadata directory.
 *
 * Uses WordPress's wp_remote_get() directly (rather than the SDK's HTTP
 * transporter) so that the request is not subject to the stricter
 * reject_unsafe_urls checks enforced by wp_safe_remote_request(), which
 * would otherwise block connections to 127.0.0.1 and other local addresses.
 *
 * @since 0.0.1
 *
 * @phpstan-type ModelsResponseData array{
 *     data: list<array{id: string}>
 * }
 */
class LlamaCppModelMetadataDirectory extends AbstractApiBasedModelMetadataDirectory {

	/**
	 * {@inheritDoc}
	 *
	 * Fetches the model list from the llama.cpp /v1/models endpoint using
	 * wp_remote_get() so that local (127.x) addresses are not blocked.
	 *
	 * @since 0.0.1
	 *
	 * @return array<mixed>
	 * @throws RuntimeException  When the HTTP request fails or returns a non-200 status.
	 * @throws ResponseException When the response body is missing required data.
	 */
	protected function sendListModelsRequest(): array {
		$url = LlamaCppProvider::url( 'v1/models' );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 5,
				'sslverify' => wp_parse_url( $url, PHP_URL_HOST ) !== '127.0.0.1',
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException(
				'Failed to list llama.cpp models: ' . esc_html( $response->get_error_message() )
			);
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			throw new RuntimeException(
				esc_html( sprintf( 'Failed to list llama.cpp models: HTTP %d', $status_code ) )
			);
		}

		/**
		 * Decoded JSON response.
		 *
		 * @var ModelsResponseData
		 */
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['data'] ) || ! $body['data'] ) {
			throw ResponseException::fromMissingData( 'llama.cpp', 'data' );
		}

		$capabilities = array(
			CapabilityEnum::textGeneration(),
			CapabilityEnum::chatHistory(),
		);

		$options = array(
			new SupportedOption( OptionEnum::systemInstruction() ),
			new SupportedOption( OptionEnum::maxTokens() ),
			new SupportedOption( OptionEnum::temperature() ),
			new SupportedOption( OptionEnum::topP() ),
			new SupportedOption( OptionEnum::topK() ),
			new SupportedOption( OptionEnum::stopSequences() ),
			new SupportedOption( OptionEnum::outputMimeType(), array( 'text/plain', 'application/json' ) ),
			new SupportedOption( OptionEnum::outputSchema() ),
			new SupportedOption( OptionEnum::functionDeclarations() ),
			new SupportedOption( OptionEnum::customOptions() ),
			new SupportedOption(
				OptionEnum::inputModalities(),
				array( array( ModalityEnum::text() ) )
			),
			new SupportedOption(
				OptionEnum::outputModalities(),
				array( array( ModalityEnum::text() ) )
			),
		);

		$models_map = array();
		foreach ( (array) $body['data'] as $model_data ) {
			$model_id                = $model_data['id'];
			$models_map[ $model_id ] = new ModelMetadata(
				$model_id,
				$model_id,
				$capabilities,
				$options
			);
		}

		ksort( $models_map );

		return $models_map;
	}
}
