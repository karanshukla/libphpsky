<?php

declare(strict_types=1);

namespace Tests\Integration;

use Aazsamir\Libphpsky\ServiceAuth\HttpDidDocumentResolver;
use Aazsamir\Libphpsky\ServiceAuth\ServiceAuthException;
use Aazsamir\Libphpsky\ServiceAuth\ServiceAuthVerifier;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;

/**
 * Verifies a token the network actually minted, against the signing key the
 * network actually publishes.
 *
 * The unit tests sign with a key they generated themselves, which proves the
 * point decompression round-trips but not that it agrees with what a PDS and
 * a PLC directory do in production.
 *
 * @internal
 */
class ServiceAuthTest extends TestCase
{
    /** Bluesky's own discover feed generator, a real did:web service DID. */
    private const string AUDIENCE = 'did:web:discover.bsky.app';
    private const string LXM = 'app.bsky.feed.getFeedSkeleton';

    public function testItVerifiesATokenMintedByARealPds(): void
    {
        $verified = $this->verifier()->verify($this->token(), self::AUDIENCE, self::LXM);

        self::assertStringStartsWith('did:', $verified->issuer);
        self::assertSame(self::LXM, $verified->lxm);
        self::assertSame(self::AUDIENCE, $verified->audience);
    }

    public function testItRejectsARealTokenAddressedToAnotherService(): void
    {
        $this->expectException(ServiceAuthException::class);

        $this->verifier()->verify($this->token(), 'did:web:someone.else', self::LXM);
    }

    public function testItRejectsARealTokenMintedForAnotherMethod(): void
    {
        $this->expectException(ServiceAuthException::class);

        $this->verifier()->verify($this->token(), self::AUDIENCE, 'app.bsky.feed.getFeed');
    }

    public function testItRejectsARealTokenWhoseSignatureWasTampered(): void
    {
        $segments = explode('.', $this->token());
        $signature = (string) base64_decode(strtr($segments[2], '-_', '+/'), true);

        // A byte in the middle of r, not the last base64 character: the final
        // character of an 86-character signature carries only two significant
        // bits, so editing it is a no-op three times in four.
        $signature[20] = \chr(\ord($signature[20]) ^ 0xFF);
        $segments[2] = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $this->expectException(ServiceAuthException::class);

        $this->verifier()->verify(implode('.', $segments), self::AUDIENCE, self::LXM);
    }

    public function testItRejectsARealTokenWithForgedClaims(): void
    {
        $segments = explode('.', $this->token());
        $claims = json_decode((string) base64_decode(strtr($segments[1], '-_', '+/'), true), true);
        self::assertIsArray($claims);
        // Claim to be someone whose DID document publishes a different key.
        $claims['iss'] = 'did:plc:z72i7hdynmk6r22z27h6tvur';
        $segments[1] = rtrim(strtr(base64_encode((string) json_encode($claims)), '+/', '-_'), '=');

        $this->expectException(ServiceAuthException::class);

        $this->verifier()->verify(implode('.', $segments), self::AUDIENCE, self::LXM);
    }

    private function token(): string
    {
        return $this->client
            ->comAtprotoServerGetServiceAuth()
            ->query(aud: self::AUDIENCE, lxm: self::LXM)
            ->token;
    }

    private function verifier(): ServiceAuthVerifier
    {
        return new ServiceAuthVerifier(
            new HttpDidDocumentResolver(
                httpClient: new GuzzleClient(['timeout' => 10, 'connect_timeout' => 5]),
                requestFactory: new HttpFactory(),
            ),
        );
    }
}
