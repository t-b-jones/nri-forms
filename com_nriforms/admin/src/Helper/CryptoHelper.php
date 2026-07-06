<?php
namespace NRI\Component\Nriforms\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

abstract class CryptoHelper
{
    private const PREFIX = 'enc:v1:';

    private static function key(): string
    {
        $secret = (string) Factory::getApplication()->get('secret');

        return sodium_crypto_generichash($secret, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }

    public static function encrypt(string $plain): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return self::PREFIX . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, self::key()));
    }

    /** Transparently returns legacy plaintext unchanged. */
    public static function decrypt(string $stored): string
    {
        if (strncmp($stored, self::PREFIX, \strlen(self::PREFIX)) !== 0) {
            return $stored;
        }

        $raw   = base64_decode(substr($stored, \strlen(self::PREFIX)), true) ?: '';
        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, self::key());

        return $plain === false ? '' : $plain;
    }
}
