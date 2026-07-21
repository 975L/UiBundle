<?= "<?php\n" ?>
/*
 * (c) 2026: 975L <contact@975l.com>
 * (c) 2026: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace <?= $namespace ?>;

use c975L\ConfigBundle\Service\ConfigServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

// Vectorizes a question for the semantic cache (see the generated AnswerRepository::findBestSemanticMatch()) -
// reuses "donovan-qa-llm-base-uri"/"donovan-qa-llm-api-key" (same product/token as the LLM client) but its
// own distinct model ("donovan-qa-embedding-model" - a chat model is never usable as an embedding model,
// e.g. Mistral-Small vs Qwen3-Embedding-8B). Only ever called through an OpenAI-compatible "/embeddings"
// endpoint (e.g. Euria/Infomaniak) - if "donovan-qa-llm-provider" is "anthropic" and no explicit base URI
// is set, isEnabled() stays false rather than guessing an endpoint (Anthropic has no embeddings API of its
// own; bring an OpenAI-compatible one via "donovan-qa-llm-base-uri" regardless of the chat provider)
class <?= $class_name ?>
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ConfigServiceInterface $configService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isEnabled(): bool
    {
        if (true !== $this->configService->get('donovan-qa-semantic-cache-enabled')) {
            return false;
        }

        return null !== $this->configService->get('donovan-qa-llm-api-key')
            && null !== $this->configService->get('donovan-qa-llm-base-uri')
            && null !== $this->configService->get('donovan-qa-embedding-model');
    }

    /**
     * @return float[]|null
     */
    public function embed(string $text): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $uri = rtrim((string) $this->configService->get('donovan-qa-llm-base-uri'), '/') . '/embeddings';
        $apiKey = (string) $this->configService->get('donovan-qa-llm-api-key');
        $model = (string) $this->configService->get('donovan-qa-embedding-model');

        try {
            $response = $this->httpClient->request('POST', $uri, [
                'auth_bearer' => $apiKey,
                'json' => [
                    'model' => $model,
                    'input' => $text,
                ],
                'timeout' => 10,
            ]);

            $embedding = $response->toArray()['data'][0]['embedding'] ?? [];

            return [] === $embedding ? null : array_map(floatval(...), $embedding);
        } catch (ExceptionInterface $e) {
            $this->logger->error('Donovan Q&A embedding request failed: {message}', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
