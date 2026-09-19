<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceAuth;

use Aazsamir\Libphpsky\ServiceAuth\DidKey;
use Aazsamir\Libphpsky\ServiceAuth\ServiceAuthException;
use Aazsamir\Libphpsky\ServiceAuth\VerificationKey;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Tests\Unit\ServiceAuth\Stub\TestKey;
use Tests\Unit\TestCase;

/**
 * @internal
 */
final class DidKeyTest extends TestCase
{
    public function testDerivesAUsableSecp256k1KeyFromItsMultibaseForm(): void
    {
        $testKey = TestKey::secp256k1();
        $decoded = DidKey::fromMultibase($testKey->multibase);

        self::assertSame(VerificationKey::CURVE_SECP256K1, $decoded->curve);
        self::assertSame('ES256K', $decoded->algorithm());

        $token = $testKey->sign(['iss' => 'did:plc:test', 'exp' => time() + 60]);
        $claims = JWT::decode($token, new Key($decoded->pem(), $decoded->algorithm()));

        self::assertSame('did:plc:test', $claims->iss);
    }

    public function testDerivesAUsableP256KeyFromItsMultibaseForm(): void
    {
        $testKey = TestKey::p256();
        $decoded = DidKey::fromMultibase($testKey->multibase);

        self::assertSame(VerificationKey::CURVE_P256, $decoded->curve);
        self::assertSame('ES256', $decoded->algorithm());

        $token = $testKey->sign(['iss' => 'did:plc:test', 'exp' => time() + 60]);

        self::assertSame(
            'did:plc:test',
            JWT::decode($token, new Key($decoded->pem(), $decoded->algorithm()))->iss,
        );
    }

    public function testAcceptsTheDidKeyForm(): void
    {
        $multibase = TestKey::secp256k1()->multibase;

        self::assertEquals(
            DidKey::fromMultibase($multibase),
            DidKey::fromDidKey('did:key:' . $multibase),
        );
    }

    public function testRejectsAMultibaseThatIsNotBase58btc(): void
    {
        $this->expectException(ServiceAuthException::class);

        DidKey::fromMultibase('mAQIDBA');
    }

    public function testRejectsAKeyTypeAtprotoDoesNotSignWith(): void
    {
        $this->expectException(ServiceAuthException::class);

        // Ed25519 (multicodec 0xed 0x01): a real key type, but not one used
        // for ATProto repo signatures.
        DidKey::fromMultibase('z6MkhaXgBZDvotDkL5257faiztiGiC2QtKLGpbnnEGta2doK');
    }
}
