<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EncryptionService
{
    private const SESSION_DEK_KEY = 'cl_encrypted_dek';

    public function generateDek(): string
    {
        return random_bytes((int) config('conductor-ledger.encryption.dek_size', 32));
    }

    public function generateSalt(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function deriveKek(string $password, string $salt, ?array $kdfParams = null): string
    {
        $params = $kdfParams ?? config('conductor-ledger.encryption.kdf');

        if (defined('SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13')) {
            $saltBinary = hex2bin($salt);

            return sodium_crypto_pwhash(
                32,
                $password,
                $saltBinary,
                $params['time_cost'] ?? SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
                $params['memory_cost'] ?? SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
                SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
            );
        }

        return hash('sha512', $password.$salt.config('app.key'), true);
    }

    public function createUserKeyEnvelope(string $password): array
    {
        $dek = $this->generateDek();
        $salt = $this->generateSalt();
        $kdfParams = config('conductor-ledger.encryption.kdf');
        $kek = $this->deriveKek($password, $salt, $kdfParams);

        return [
            'dek' => $dek,
            'encrypted_dek' => $this->wrapDekWithKek($dek, $kek),
            'admin_wrapped_dek' => $this->wrapDekWithMasterKey($dek),
            'dek_salt' => $salt,
            'kdf_params' => $kdfParams,
        ];
    }

    public function unwrapUserDek(User $user, string $password): string
    {
        if (! $user->encrypted_dek || ! $user->dek_salt) {
            throw new RuntimeException('El usuario no tiene claves de cifrado configuradas.');
        }

        $kek = $this->deriveKek($password, $user->dek_salt, $user->kdf_params);

        return $this->unwrapDekWithKek($user->encrypted_dek, $kek);
    }

    public function unwrapUserDekWithMasterKey(User $user): string
    {
        if (! $user->admin_wrapped_dek) {
            throw new RuntimeException('No existe sobre de recuperación para este usuario.');
        }

        return $this->unwrapDekWithMasterKey($user->admin_wrapped_dek);
    }

    public function rewrapUserDek(User $user, string $dek, string $newPassword): array
    {
        $salt = $this->generateSalt();
        $kdfParams = config('conductor-ledger.encryption.kdf');
        $kek = $this->deriveKek($newPassword, $salt, $kdfParams);

        return [
            'encrypted_dek' => $this->wrapDekWithKek($dek, $kek),
            'admin_wrapped_dek' => $this->wrapDekWithMasterKey($dek),
            'dek_salt' => $salt,
            'kdf_params' => $kdfParams,
        ];
    }

    public function wrapDekWithKek(string $dek, string $kek): string
    {
        return $this->encrypt($dek, $kek);
    }

    public function unwrapDekWithKek(string $encryptedDek, string $kek): string
    {
        return $this->decrypt($encryptedDek, $kek);
    }

    public function wrapDekWithMasterKey(string $dek): string
    {
        return $this->encrypt($dek, $this->masterKey());
    }

    public function unwrapDekWithMasterKey(string $adminWrappedDek): string
    {
        return $this->decrypt($adminWrappedDek, $this->masterKey());
    }

    public function encryptPayload(string $dek, array $data): string
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        return $this->encrypt($json, $dek);
    }

    public function decryptPayload(string $dek, string $encryptedPayload): array
    {
        $json = $this->decrypt($encryptedPayload, $dek);
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : [];
    }

    public function storeDekInSession(Session $session, string $dek): void
    {
        $session->put(self::SESSION_DEK_KEY, encrypt(base64_encode($dek)));
    }

    public function getDekFromSession(Session $session): ?string
    {
        $stored = $session->get(self::SESSION_DEK_KEY);

        if (! $stored) {
            return null;
        }

        try {
            return base64_decode(decrypt($stored), true) ?: null;
        } catch (\Throwable) {
            Log::channel('security')->warning('No se pudo recuperar DEK de sesión.');

            return null;
        }
    }

    public function clearDekFromSession(Session $session): void
    {
        $session->forget(self::SESSION_DEK_KEY);
    }

    public function masterKeyConfigured(): bool
    {
        $key = config('conductor-ledger.encryption.master_key');

        return is_string($key) && strlen($key) >= 32;
    }

    private function masterKey(): string
    {
        $key = config('conductor-ledger.encryption.master_key');

        if (! is_string($key) || strlen($key) < 32) {
            if (! app()->environment('production')) {
                return hash('sha256', (string) config('app.key'), true);
            }

            throw new RuntimeException('MASTER_ENCRYPTION_KEY no está configurada.');
        }

        return hash('sha256', $key, true);
    }

    private function encrypt(string $plaintext, string $key): string
    {
        $cipher = config('conductor-ledger.encryption.cipher', 'aes-256-gcm');
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = random_bytes($ivLength);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            $cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Error al cifrar datos.');
        }

        return base64_encode(json_encode([
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'data' => base64_encode($ciphertext),
        ], JSON_THROW_ON_ERROR));
    }

    private function decrypt(string $encrypted, string $key): string
    {
        $cipher = config('conductor-ledger.encryption.cipher', 'aes-256-gcm');
        $payload = json_decode(base64_decode($encrypted), true, 512, JSON_THROW_ON_ERROR);

        $plaintext = openssl_decrypt(
            base64_decode($payload['data']),
            $cipher,
            $key,
            OPENSSL_RAW_DATA,
            base64_decode($payload['iv']),
            base64_decode($payload['tag'])
        );

        if ($plaintext === false) {
            throw new RuntimeException('Error al descifrar datos.');
        }

        return $plaintext;
    }
}
