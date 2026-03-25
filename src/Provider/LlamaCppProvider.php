<?php
/**
 * Provider class for llama.cpp.
 *
 * @since 0.0.1
 *
 * @package WordPress\LlamaCppAiProvider
 */

declare(strict_types=1);

namespace WordPress\LlamaCppAiProvider\Provider;

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\LlamaCppAiProvider\Metadata\LlamaCppModelMetadataDirectory;
use WordPress\LlamaCppAiProvider\Models\LlamaCppTextGenerationModel;
use WordPress\LlamaCppAiProvider\Settings\LlamaCppSettings;

/**
 * Class for the llama.cpp provider.
 *
 * @since 0.0.1
 */
class LlamaCppProvider extends AbstractApiProvider {

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.0.1
	 */
	protected static function baseUrl(): string {
		return rtrim( LlamaCppSettings::get_base_url(), '/' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.0.1
	 *
	 * @param ModelMetadata    $model_metadata    The model metadata.
	 * @param ProviderMetadata $provider_metadata  The provider metadata.
	 * @return ModelInterface
	 * @throws RuntimeException When no supported capability is found.
	 */
	protected static function createModel(
		ModelMetadata $model_metadata,
		ProviderMetadata $provider_metadata
	): ModelInterface {
		$capabilities = $model_metadata->getSupportedCapabilities();
		foreach ( $capabilities as $capability ) {
			if ( $capability->isTextGeneration() ) {
				return new LlamaCppTextGenerationModel( $model_metadata, $provider_metadata );
			}
		}

		throw new RuntimeException(
			'Unsupported model capabilities: ' . esc_html( implode( ', ', $capabilities ) )
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.0.1
	 */
	protected static function createProviderMetadata(): ProviderMetadata {
		$provider_metadata_args = array(
			'llamacpp',
			'llama.cpp',
			ProviderTypeEnum::cloud(),
			null,
			RequestAuthenticationMethod::apiKey(),
		);

		// Provider description support was added in 1.2.0.
		if ( version_compare( AiClient::VERSION, '1.2.0', '>=' ) ) {
			if ( function_exists( '__' ) ) {
				$provider_metadata_args[] = __( 'Text generation with llama.cpp, running locally or on a remote server.', 'ai-provider-for-llamacpp' );
			} else {
				$provider_metadata_args[] = 'Text generation with llama.cpp, running locally or on a remote server.';
			}
		}

		return new ProviderMetadata( ...$provider_metadata_args );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.0.1
	 */
	protected static function createProviderAvailability(): ProviderAvailabilityInterface {
		return new ListModelsApiBasedProviderAvailability(
			static::modelMetadataDirectory()
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.0.1
	 */
	protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface {
		return new LlamaCppModelMetadataDirectory();
	}
}
