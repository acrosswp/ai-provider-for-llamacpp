<?php
/**
 * Text generation model class for llama.cpp.
 *
 * @since 0.0.1
 *
 * @package AcrossWP\AiProviderForLlamaCpp
 */

declare(strict_types=1);

namespace AcrossWP\AiProviderForLlamaCpp\Models;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;
use AcrossWP\AiProviderForLlamaCpp\Provider\LlamaCppProvider;

/**
 * Class for a llama.cpp text generation model using the OpenAI-compatible chat completions API.
 *
 * @since 0.0.1
 */
class LlamaCppTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel {

	/**
	 * {@inheritDoc}
	 *
	 * @since 0.0.1
	 *
	 * @param HttpMethodEnum        $method   HTTP method.
	 * @param string                $path     Request path.
	 * @param array<string, string> $headers  Optional headers.
	 * @param mixed                 $data     Optional request data.
	 * @return Request
	 */
	protected function createRequest(
		HttpMethodEnum $method,
		string $path,
		array $headers = array(),
		$data = null
	): Request {
		// llama.cpp exposes the OpenAI-compatible API directly under /v1/.
		$path = ltrim( (string) preg_replace( '#^v1/?#', '', ltrim( $path, '/' ) ), '/' );
		$path = 'v1/' . $path;

		return new Request(
			$method,
			LlamaCppProvider::url( $path ),
			$headers,
			$data,
			$this->getRequestOptions()
		);
	}
}
