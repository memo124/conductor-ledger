<?php

namespace Tests\Unit;

use App\Services\EncryptionService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EncryptionServiceTest extends TestCase
{
    #[Test]
    public function it_encrypts_and_decrypts_payload_with_envelope_keys(): void
    {
        $service = app(EncryptionService::class);
        $envelope = $service->createUserKeyEnvelope('Password123!');

        $payload = $service->encryptPayload($envelope['dek'], [
            'indrive' => 10.5,
            'alquiler' => 2.0,
        ]);

        $decrypted = $service->decryptPayload($envelope['dek'], $payload);

        $this->assertEquals(10.5, $decrypted['indrive']);
        $this->assertEquals(2.0, $decrypted['alquiler']);
    }

    #[Test]
    public function admin_master_key_can_unwrap_user_dek(): void
    {
        $service = app(EncryptionService::class);
        $envelope = $service->createUserKeyEnvelope('Password123!');

        $adminDek = $service->unwrapDekWithMasterKey($envelope['admin_wrapped_dek']);
        $userKek = $service->deriveKek('Password123!', $envelope['dek_salt'], $envelope['kdf_params']);
        $userDek = $service->unwrapDekWithKek($envelope['encrypted_dek'], $userKek);

        $this->assertSame(base64_encode($envelope['dek']), base64_encode($adminDek));
        $this->assertSame(base64_encode($envelope['dek']), base64_encode($userDek));
    }
}
