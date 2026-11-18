<?php

/**
 * Authenticated encryption and personal-key derivation for Toolbox Cloud.
 *
 * The user's password/recovery secret is never persisted. A random 32-byte
 * data-encryption key is wrapped separately by every enabled unlock method.
 */
final class toolbox_cloud_crypto
{
    private const PREFIX = 'v1:';
    private const CONTEXT = 'toolbox-cloud-v1';

    /** Ensure libsodium's required primitives are available. */
    public static function check_runtime(): void
    {
        if (!function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')
            || !function_exists('sodium_crypto_pwhash')) {
            throw new RuntimeException('Toolbox Cloud requires the PHP sodium extension');
        }
    }

    /** Generate a new independent, uniformly random vault encryption key. */
    public static function new_vault_key(): string
    {
        self::check_runtime();
        return random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES);
    }

    /**
     * Derive a key-encryption key from a human password with Argon2id.
     *
     * @param string $password User-supplied password, never logged/stored.
     * @param string $salt Raw 16-byte per-vault random salt.
     * @param int $ops Stored Argon2id operation cost.
     * @param int $memory Stored Argon2id memory cost, in bytes.
     */
    public static function password_key(string $password, string $salt, int $ops, int $memory): string
    {
        self::check_runtime();
        if ($password === '' || strlen($salt) !== SODIUM_CRYPTO_PWHASH_SALTBYTES
            || $ops < SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE
            || $memory < SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE) {
            throw new InvalidArgumentException('Invalid vault password or Argon2id parameters');
        }
        // Reject excessive attacker-controlled database parameters before allocation.
        if ($ops > 10 || $memory > 512 * 1024 * 1024) {
            throw new RuntimeException('Vault key-derivation parameters exceed safety limits');
        }
        return sodium_crypto_pwhash(
            SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
            $password, $salt, $ops, $memory, SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
        );
    }

    /**
     * Generate a printable 256-bit recovery secret, intended for offline storage.
     *
     * @return string URL-safe unpadded base64, 43 characters.
     */
    public static function new_recovery_secret(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * Convert a recovery secret directly to a domain-separated wrapping key.
     * The secret is 256 random bits; a slow password KDF is unnecessary.
     */
    public static function recovery_key(string $secret): string
    {
        self::check_runtime();
        if (!preg_match('/^[A-Za-z0-9_-]{43}$/D', $secret)) {
            throw new InvalidArgumentException('Invalid recovery-key format');
        }
        $bytes = base64_decode(strtr($secret, '-_', '+/') . '=', true);
        if ($bytes === false || strlen($bytes) !== 32
            || self::b64url($bytes) !== $secret) {
            throw new InvalidArgumentException('Invalid recovery-key encoding');
        }
        return sodium_crypto_generichash(self::CONTEXT . ':recovery:' . $bytes, '', 32);
    }

    /**
     * Seal plaintext with XChaCha20-Poly1305 and authenticated context.
     *
     * @param string $plaintext The confidential byte string.
     * @param string $key Exactly 32 bytes.
     * @param string $aad Domain-separated authenticated context.
     * @return string Versioned base64 envelope.
     */
    public static function seal(string $plaintext, string $key, string $aad): string
    {
        self::check_runtime();
        if (strlen($key) !== 32) {
            throw new InvalidArgumentException('Incorrect Cloud encryption-key length');
        }
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $plaintext, $aad, $nonce, $key
        );
        return self::PREFIX . base64_encode($nonce . $ciphertext);
    }

    /**
     * Verify and open a versioned ciphertext envelope, failing closed on edits.
     *
     * @throws RuntimeException For wrong password, wrong context or tampering.
     */
    public static function open(string $envelope, string $key, string $aad): string
    {
        self::check_runtime();
        if (!str_starts_with($envelope, self::PREFIX) || strlen($key) !== 32) {
            throw new RuntimeException('Unsupported or invalid Cloud encryption envelope');
        }
        $raw = base64_decode(substr($envelope, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) < SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES
            + SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES) {
            throw new RuntimeException('Malformed Cloud ciphertext');
        }
        $n = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        $result = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            substr($raw, $n), $aad, substr($raw, 0, $n), $key
        );
        if ($result === false) {
            throw new RuntimeException('Unable to unlock Cloud vault or verify ciphertext');
        }
        return $result;
    }

    /** Build a unique domain-separated context for a user's vault wrapper. */
    public static function vault_aad(int $user_id, string $method): string
    {
        return self::CONTEXT . ':vault:' . $user_id . ':' . $method;
    }

    /** Bind account credentials to the user, account ID and selected driver. */
    public static function account_aad(int $user_id, string $id, string $provider): string
    {
        return self::CONTEXT . ':account:' . $user_id . ':' . $id . ':' . $provider;
    }

    /** Encode raw data as URL-safe unpadded base64. */
    private static function b64url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}

// END OF FILE
