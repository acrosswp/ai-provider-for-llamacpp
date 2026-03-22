<?php

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
	 */
	protected static function createModel(
		ModelMetadata $modelMetadata,
		ProviderMetadata $providerMetadata
	): ModelInterface {
		$capabilities = $modelMetadata->getSupportedCapabilities();
		foreach ( $capabilities as $capability ) {
			if ( $capability->isTextGeneration() ) {
				return new LlamaCppTextGenerationModel( $modelMetadata, $providerMetadata );
			}
		}

		throw new RuntimeException(
			'Unsupported model capabilities: ' . implode( ', ', $capabilities )
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.0.1
	 */
	protected static function createProviderMetadata(): ProviderMetadata {
		$providerMetadataArgs = array(
			'llamacpp',
			'llama.cpp',
			ProviderTypeEnum::cloud(),
			null,
			RequestAuthenticationMethod::apiKey(),
		);

		// Provider description support was added in 1.2.0.
		if ( version_compare( AiClient::VERSION, '1.2.0', '>=' ) ) {
			if ( function_exists( '__' ) ) {
				$providerMetadataArgs[] = __( 'Text generation with llama.cpp, running locally or on a remote server.', 'ai-provider-for-llamacpp' );
			} else {
				$providerMetadataArgs[] = 'Text generation with llama.cpp, running locally or on a remote server.';
			}
		}

		return new ProviderMetadata( ...$providerMetadataArgs );
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
