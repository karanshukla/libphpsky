<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\ServiceAuth;

use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Primitives\GeneratorPoint;
use Mdanter\Ecc\Serializer\Point\CompressedPointSerializer;
use Mdanter\Ecc\Serializer\PublicKey\DerPublicKeySerializer;
use Mdanter\Ecc\Serializer\PublicKey\PemPublicKeySerializer;

/**
 * An elliptic-curve public key recovered from a DID document, in a form
 * OpenSSL will accept.
 *
 * ATProto publishes signing keys as compressed points inside a multibase
 * string, and firebase/php-jwt wants a PEM-encoded SubjectPublicKeyInfo over
 * an uncompressed one, so the point is decompressed on the way through.
 *
 * @see \Tests\Unit\ServiceAuth\VerificationKeyTest
 */
final readonly class VerificationKey
{
    public const string CURVE_SECP256K1 = 'secp256k1';
    public const string CURVE_P256 = 'p256';

    /** @param non-empty-string $compressedPoint 33 bytes: a 0x02/0x03 parity prefix and X */
    public function __construct(
        public string $curve,
        public string $compressedPoint,
    ) {}

    /**
     * The JWS algorithm identifier a token signed by this key must declare.
     */
    public function algorithm(): string
    {
        return $this->curve === self::CURVE_SECP256K1 ? 'ES256K' : 'ES256';
    }

    public function pem(): string
    {
        if (\strlen($this->compressedPoint) !== 33) {
            throw new ServiceAuthException('Compressed point must be 33 bytes');
        }

        $prefix = \ord($this->compressedPoint[0]);

        if ($prefix !== 0x02 && $prefix !== 0x03) {
            throw new ServiceAuthException(\sprintf('Unexpected point prefix 0x%02x', $prefix));
        }

        $adapter = EccFactory::getAdapter();
        $generator = $this->generator();

        try {
            $point = new CompressedPointSerializer($adapter)->unserialize(
                $generator->getCurve(),
                bin2hex($this->compressedPoint),
            );

            return new PemPublicKeySerializer(new DerPublicKeySerializer($adapter))->serialize(
                $generator->getPublicKeyFrom($point->getX(), $point->getY()),
            );
        } catch (\Throwable $e) {
            throw new ServiceAuthException("Could not read the published signing key: {$e->getMessage()}", previous: $e);
        }
    }

    private function generator(): GeneratorPoint
    {
        return $this->curve === self::CURVE_SECP256K1
            ? EccFactory::getSecgCurves()->generator256k1()
            : EccFactory::getNistCurves()->generator256();
    }
}
