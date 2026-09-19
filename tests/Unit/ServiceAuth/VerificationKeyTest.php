<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceAuth;

use Aazsamir\Libphpsky\ServiceAuth\ServiceAuthException;
use Aazsamir\Libphpsky\ServiceAuth\VerificationKey;
use Tests\Unit\TestCase;

/**
 * @internal
 */
final class VerificationKeyTest extends TestCase
{
    public function testRejectsAPointOfTheWrongLength(): void
    {
        $this->expectException(ServiceAuthException::class);

        new VerificationKey(VerificationKey::CURVE_SECP256K1, "\x02" . str_repeat("\x00", 16))->pem();
    }

    public function testRejectsAPointWithoutAParityPrefix(): void
    {
        $this->expectException(ServiceAuthException::class);

        // 0x04 is the uncompressed marker, not a parity bit.
        new VerificationKey(VerificationKey::CURVE_SECP256K1, "\x04" . str_repeat("\x00", 32))->pem();
    }

    /** x = 5 has no square root on secp256k1. */
    public function testRejectsAnXThatIsNotOnTheCurve(): void
    {
        $this->expectException(ServiceAuthException::class);

        new VerificationKey(
            VerificationKey::CURVE_SECP256K1,
            "\x02" . str_pad(pack('N', 5), 32, "\x00", \STR_PAD_LEFT),
        )->pem();
    }

    public function testProducesAPemOpensslCanRead(): void
    {
        $key = Stub\TestKey::p256()->verificationKey();
        $public = openssl_pkey_get_public($key->pem());

        self::assertNotFalse($public);
        self::assertSame(\OPENSSL_KEYTYPE_EC, openssl_pkey_get_details($public)['type'] ?? null);
    }
}
