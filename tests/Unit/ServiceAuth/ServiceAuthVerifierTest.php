<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceAuth;

use Aazsamir\Libphpsky\ServiceAuth\ServiceAuthException;
use Aazsamir\Libphpsky\ServiceAuth\ServiceAuthVerifier;
use Tests\Unit\ServiceAuth\Stub\FakeDidDocumentResolver;
use Tests\Unit\ServiceAuth\Stub\TestKey;
use Tests\Unit\TestCase;

/**
 * @internal
 */
final class ServiceAuthVerifierTest extends TestCase
{
    private const string ISSUER = 'did:plc:requester';
    private const string AUDIENCE = 'did:web:feed.test';
    private const string LXM = 'app.bsky.feed.getFeedSkeleton';

    public function testAcceptsAWellFormedToken(): void
    {
        $key = TestKey::secp256k1();
        $verifier = self::verifierFor($key);

        $verified = $verifier->verify($this->tokenFrom($key), self::AUDIENCE, self::LXM);

        self::assertSame(self::ISSUER, $verified->issuer);
        self::assertSame(self::LXM, $verified->lxm);
    }

    public function testAcceptsAP256SignedToken(): void
    {
        $key = TestKey::p256();

        self::assertSame(
            self::ISSUER,
            self::verifierFor($key)->verify($this->tokenFrom($key), self::AUDIENCE, self::LXM)->issuer,
        );
    }

    public function testToleratesATokenMintedWithoutAnLxmClaim(): void
    {
        $key = TestKey::secp256k1();
        $token = $this->tokenFrom($key, lxm: null);

        self::assertSame(
            self::ISSUER,
            self::verifierFor($key)->verify($token, self::AUDIENCE, self::LXM)->issuer,
        );
    }

    public function testRejectsATokenMintedForADifferentMethod(): void
    {
        $key = TestKey::secp256k1();
        $token = $this->tokenFrom($key, lxm: 'app.bsky.feed.getFeed');

        $this->expectException(ServiceAuthException::class);

        self::verifierFor($key)->verify($token, self::AUDIENCE, self::LXM);
    }

    public function testRejectsATokenAddressedToAnotherService(): void
    {
        $key = TestKey::secp256k1();
        $token = $this->tokenFrom($key, audience: 'did:web:someone.else');

        $this->expectException(ServiceAuthException::class);

        self::verifierFor($key)->verify($token, self::AUDIENCE, self::LXM);
    }

    public function testRejectsAnExpiredToken(): void
    {
        $key = TestKey::secp256k1();
        $token = $this->tokenFrom($key, expiresAt: time() - 3600);

        $this->expectException(ServiceAuthException::class);

        self::verifierFor($key)->verify($token, self::AUDIENCE, self::LXM);
    }

    public function testRejectsATokenWithNoExpiry(): void
    {
        $key = TestKey::secp256k1();
        $token = $key->sign([
            'iss' => self::ISSUER,
            'aud' => self::AUDIENCE,
            'lxm' => self::LXM,
        ]);

        $this->expectException(ServiceAuthException::class);

        self::verifierFor($key)->verify($token, self::AUDIENCE, self::LXM);
    }

    public function testRejectsATokenSignedBySomeoneElse(): void
    {
        $published = TestKey::secp256k1();
        $impostor = TestKey::secp256k1();

        $this->expectException(ServiceAuthException::class);

        self::verifierFor($published)->verify($this->tokenFrom($impostor), self::AUDIENCE, self::LXM);
    }

    public function testRejectsAMalformedToken(): void
    {
        $this->expectException(ServiceAuthException::class);

        self::verifierFor(TestKey::secp256k1())->verify('not.a.jwt', self::AUDIENCE, self::LXM);
    }

    public function testRefetchesTheDocumentOnceWhenTheSignatureDoesNotMatch(): void
    {
        $rotated = TestKey::secp256k1();
        $resolver = new FakeDidDocumentResolver([
            TestKey::secp256k1()->didDocument(self::ISSUER),
            $rotated->didDocument(self::ISSUER),
        ]);

        $verified = new ServiceAuthVerifier($resolver)
            ->verify($this->tokenFrom($rotated), self::AUDIENCE, self::LXM);

        self::assertSame(self::ISSUER, $verified->issuer);
        self::assertSame(1, $resolver->refreshes);
    }

    public function testDoesNotRefetchForAFailureAFreshDocumentCannotFix(): void
    {
        $key = TestKey::secp256k1();
        $resolver = new FakeDidDocumentResolver([$key->didDocument(self::ISSUER)]);

        try {
            new ServiceAuthVerifier($resolver)
                ->verify($this->tokenFrom($key, expiresAt: time() - 3600), self::AUDIENCE, self::LXM);
            self::fail('Expected the expired token to be rejected');
        } catch (ServiceAuthException) {
            self::assertSame(0, $resolver->refreshes);
        }
    }

    public function testResolvesTheBareDidWhenTheIssuerNamesAService(): void
    {
        $key = TestKey::secp256k1();
        $resolver = new FakeDidDocumentResolver([$key->didDocument(self::ISSUER)]);
        $token = $key->sign([
            'iss' => self::ISSUER . '#atproto_labeler',
            'aud' => self::AUDIENCE,
            'exp' => time() + 60,
        ]);

        new ServiceAuthVerifier($resolver)->verify($token, self::AUDIENCE, self::LXM);

        self::assertSame([self::ISSUER], $resolver->dids);
    }

    public function testRestoresTheCallersJwtLeeway(): void
    {
        \Firebase\JWT\JWT::$leeway = 120;

        try {
            $key = TestKey::secp256k1();
            self::verifierFor($key)->verify($this->tokenFrom($key), self::AUDIENCE, self::LXM);

            self::assertSame(120, \Firebase\JWT\JWT::$leeway);
        } finally {
            \Firebase\JWT\JWT::$leeway = 0;
        }
    }

    public function testSkipsAVerificationMethodItCannotDecode(): void
    {
        $key = TestKey::secp256k1();
        $document = $key->didDocument(self::ISSUER);
        // Ed25519, a real key type but not one ATProto signs repos with.
        array_unshift($document['verificationMethod'], [
            'id' => self::ISSUER . '#atproto',
            'type' => 'Multikey',
            'controller' => self::ISSUER,
            'publicKeyMultibase' => 'z6MkhaXgBZDvotDkL5257faiztiGiC2QtKLGpbnnEGta2doK',
        ]);

        $verified = new ServiceAuthVerifier(new FakeDidDocumentResolver([$document]))
            ->verify($this->tokenFrom($key), self::AUDIENCE, self::LXM);

        self::assertSame(self::ISSUER, $verified->issuer);
    }

    public function testReadsTheIssuerOffAnUnverifiableTokenForLogging(): void
    {
        $token = TestKey::secp256k1()->sign(['iss' => self::ISSUER, 'aud' => 'wrong', 'exp' => 1]);

        self::assertSame(self::ISSUER, ServiceAuthVerifier::unverifiedIssuer($token));
        self::assertNull(ServiceAuthVerifier::unverifiedIssuer('garbage'));
    }

    private static function verifierFor(TestKey $key): ServiceAuthVerifier
    {
        return new ServiceAuthVerifier(
            new FakeDidDocumentResolver([$key->didDocument(self::ISSUER)]),
        );
    }

    private function tokenFrom(
        TestKey $key,
        ?string $lxm = self::LXM,
        string $audience = self::AUDIENCE,
        ?int $expiresAt = null,
    ): string {
        $claims = [
            'iss' => self::ISSUER,
            'aud' => $audience,
            'exp' => $expiresAt ?? time() + 60,
        ];

        if ($lxm !== null) {
            $claims['lxm'] = $lxm;
        }

        return $key->sign($claims);
    }
}
