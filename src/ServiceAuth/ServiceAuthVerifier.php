<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\ServiceAuth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use KaranShukla\PhpAtprotoIdentity\IdentityException;
use KaranShukla\PhpAtprotoIdentity\Key\SigningKeys;
use KaranShukla\PhpAtprotoIdentity\Key\VerificationKey;
use KaranShukla\PhpAtprotoIdentity\Resolution\DidDocumentResolver;

/**
 * Verifies an ATProto service-auth JWT.
 *
 * A service-auth token is signed by the *user's* signing key, published in
 * their DID document under the `#atproto` verification method, so verifying
 * one means resolving the issuer's DID and checking the signature against
 * every key it lists.
 *
 * @see \Tests\Unit\ServiceAuth\ServiceAuthVerifierTest::testToleratesATokenMintedWithoutAnLxmClaim()
 * @see \Tests\Unit\ServiceAuth\ServiceAuthVerifierTest::testRejectsATokenMintedForADifferentMethod()
 */
final readonly class ServiceAuthVerifier
{
    public function __construct(
        private DidDocumentResolver $resolver,
        private int $leeway = 60,
    ) {}

    /**
     * @param string $audience the service DID this endpoint answers to
     * @param string|null $lxm the NSID being called, or null to skip the method check
     *
     * @throws ServiceAuthException
     */
    public function verify(string $jwt, string $audience, ?string $lxm = null): VerifiedToken
    {
        $claims = self::decodeClaims($jwt);

        $issuer = $claims['iss'] ?? null;

        if (!\is_string($issuer) || !str_starts_with($issuer, 'did:')) {
            throw new ServiceAuthException('Token has no DID issuer');
        }

        if (($claims['aud'] ?? null) !== $audience) {
            throw new ServiceAuthException('Token audience does not match this service');
        }

        if (!isset($claims['exp'])) {
            throw new ServiceAuthException('Token has no expiry');
        }

        $tokenLxm = $claims['lxm'] ?? null;

        if ($lxm !== null && \is_string($tokenLxm) && $tokenLxm !== $lxm) {
            throw new ServiceAuthException("Token was minted for {$tokenLxm}, not {$lxm}");
        }

        $this->checkSignature($jwt, self::bareDid($issuer));

        return new VerifiedToken(
            issuer: $issuer,
            audience: $audience,
            lxm: \is_string($tokenLxm) ? $tokenLxm : null,
            claims: $claims,
        );
    }

    /**
     * Reads the issuer out of a token without verifying anything, for logs.
     */
    public static function unverifiedIssuer(string $jwt): ?string
    {
        try {
            $issuer = self::decodeClaims($jwt)['iss'] ?? null;
        } catch (ServiceAuthException) {
            return null;
        }

        return \is_string($issuer) ? $issuer : null;
    }

    /**
     * An `iss` may name a service (`did:plc:abc#atproto_labeler`); the document
     * to resolve is the bare DID's.
     *
     * @see \Tests\Unit\ServiceAuth\ServiceAuthVerifierTest::testResolvesTheBareDidWhenTheIssuerNamesAService()
     */
    private static function bareDid(string $issuer): string
    {
        return explode('#', $issuer, 2)[0];
    }

    /**
     * @see \Tests\Unit\ServiceAuth\ServiceAuthVerifierTest::testRefetchesTheDocumentOnceWhenTheSignatureDoesNotMatch()
     * @see \Tests\Unit\ServiceAuth\ServiceAuthVerifierTest::testDoesNotRefetchForAFailureAFreshDocumentCannotFix()
     */
    private function checkSignature(string $jwt, string $issuer): void
    {
        $lastError = null;

        foreach ([false, true] as $forceRefresh) {
            foreach ($this->signingKeys($issuer, $forceRefresh) as $key) {
                try {
                    $this->decodeWithCallersLeewayRestored($jwt, $key);

                    return;
                } catch (\Throwable $e) {
                    $lastError = $e;
                }
            }

            if (!self::aFresherDocumentCouldHelp($lastError)) {
                break;
            }
        }

        throw new ServiceAuthException(
            $lastError !== null ? $lastError->getMessage() : "No signing key published for {$issuer}",
            previous: $lastError,
        );
    }

    /**
     * A rotated signing key is the one failure a second resolution can fix.
     */
    private static function aFresherDocumentCouldHelp(?\Throwable $lastError): bool
    {
        return $lastError instanceof SignatureInvalidException;
    }

    /**
     * firebase/php-jwt reads its clock leeway from a mutable static.
     *
     * @see \Tests\Unit\ServiceAuth\ServiceAuthVerifierTest::testRestoresTheCallersJwtLeeway()
     */
    private function decodeWithCallersLeewayRestored(string $jwt, VerificationKey $key): void
    {
        $callerLeeway = JWT::$leeway;
        JWT::$leeway = $this->leeway;

        try {
            JWT::decode($jwt, new Key($key->pem(), $key->algorithm()));
        } finally {
            JWT::$leeway = $callerLeeway;
        }
    }

    /**
     * Resolution and key reading both raise the package's own exception; this
     * is the boundary where they become the one `verify()` documents.
     *
     * @see \Tests\Unit\ServiceAuth\ServiceAuthVerifierTest::testSkipsAVerificationMethodItCannotDecode()
     *
     * @return list<VerificationKey>
     */
    private function signingKeys(string $did, bool $forceRefresh): array
    {
        try {
            return SigningKeys::atproto($this->resolver->resolve($did, $forceRefresh));
        } catch (IdentityException $e) {
            throw new ServiceAuthException($e->getMessage(), previous: $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeClaims(string $jwt): array
    {
        $segments = explode('.', $jwt);

        if (\count($segments) !== 3) {
            throw new ServiceAuthException('Malformed token');
        }

        $json = base64_decode(strtr($segments[1], '-_', '+/'), true);
        $claims = $json === false ? null : json_decode($json, true);

        if (!\is_array($claims)) {
            throw new ServiceAuthException('Token payload is not a JSON object');
        }

        /** @var array<string, mixed> $claims */
        return $claims;
    }
}
