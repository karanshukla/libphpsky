<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\ServiceAuth;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Resolves did:plc via a PLC directory and did:web via the domain's
 * .well-known/did.json.
 *
 * The two freshness bounds match @atproto/identity's MemoryCache.
 *
 * @see \Tests\Unit\ServiceAuth\HttpDidDocumentResolverTest::testServesAFreshCachedDocumentWithoutFetching()
 * @see \Tests\Unit\ServiceAuth\HttpDidDocumentResolverTest::testRefetchesACachedDocumentPastTheStaleBound()
 * @see \Tests\Unit\ServiceAuth\HttpDidDocumentResolverTest::testFallsBackToAStaleDocumentWhenTheFetchFails()
 * @see \Tests\Unit\ServiceAuth\HttpDidDocumentResolverTest::testGivesUpWhenTheFetchFailsAndTheCachedDocumentIsPastMaxAge()
 */
final readonly class HttpDidDocumentResolver implements DidDocumentResolver
{
    public const int SERVE_WITHOUT_FETCHING_UNDER = 3600;

    public const int SERVE_ON_FETCH_FAILURE_UNDER = 86400;

    public const string PLC_DIRECTORY = 'https://plc.directory';

    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private DidDocumentCache $cache = new NullDidDocumentCache(),
        private string $plcDirectory = self::PLC_DIRECTORY,
        private int $staleAfter = self::SERVE_WITHOUT_FETCHING_UNDER,
        private int $maxAge = self::SERVE_ON_FETCH_FAILURE_UNDER,
    ) {}

    public function resolve(string $did, bool $forceRefresh = false): array
    {
        $cached = $this->cache->get($did);

        if (!$forceRefresh && $cached !== null && $cached['age'] < $this->staleAfter) {
            return $cached['document'];
        }

        try {
            $document = $this->fetch($did);
        } catch (\Throwable $e) {
            if ($cached !== null && $cached['age'] < $this->maxAge) {
                return $cached['document'];
            }

            throw new ServiceAuthException("Could not resolve {$did}: {$e->getMessage()}", previous: $e);
        }

        $this->cache->put($did, $document);

        return $document;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetch(string $did): array
    {
        $response = $this->httpClient->sendRequest(
            $this->requestFactory->createRequest('GET', $this->documentUrl($did))
                ->withHeader('Accept', 'application/json'),
        );

        if ($response->getStatusCode() !== 200) {
            throw new ServiceAuthException("DID resolution returned HTTP {$response->getStatusCode()}");
        }

        $document = json_decode((string) $response->getBody(), true);

        if (!\is_array($document)) {
            throw new ServiceAuthException('DID document is not a JSON object');
        }

        /** @var array<string, mixed> $document */
        return $document;
    }

    private function documentUrl(string $did): string
    {
        if (str_starts_with($did, 'did:plc:')) {
            return rtrim($this->plcDirectory, '/') . '/' . $did;
        }

        if (str_starts_with($did, 'did:web:')) {
            $identifier = substr($did, 8);

            if (str_contains($identifier, ':')) {
                throw new ServiceAuthException('did:web with a path is not supported');
            }

            return 'https://' . urldecode($identifier) . '/.well-known/did.json';
        }

        throw new ServiceAuthException("Unsupported DID method in {$did}");
    }
}
