<?php

declare(strict_types=1);

namespace Yiisoft\Cookies;

use RuntimeException;
use Yiisoft\Security\AuthenticationException;
use Yiisoft\Security\Crypt;

use function md5;
use function rawurldecode;
use function rawurlencode;
use function strlen;
use function substr;

/**
 * A CookieEncryptor encrypts the cookie value and validates whether the encrypted cookie value has been tampered with.
 *
 * @see Cookie
 */
final class CookieEncryptor
{
    /**
     * @var Crypt The Crypt instance.
     */
    private Crypt $crypt;

    /**
     * @var string The secret key used to encrypt and decrypt cookie values.
     */
    private string $key;

    /**
     * @param string $key The secret key used to encrypt and decrypt cookie values.
     */
    public function __construct(string $key)
    {
        $this->crypt = new Crypt();
        $this->key = $key;
    }

    /**
     * Returns a new cookie instance with the encrypted cookie value.
     *
     * @param Cookie $cookie The cookie with clean value.
     *
     * @throws RuntimeException If the cookie value is already encrypted.
     *
     * @return Cookie The cookie with encrypted value.
     */
    public function encrypt(Cookie $cookie): Cookie
    {
        if ($this->isEncrypted($cookie)) {
            throw new RuntimeException("The \"{$cookie->getName()}\" cookie value is already encrypted.");
        }

        $value = $this->crypt->encryptByKey($cookie->getValue(), $this->key, $cookie->getName());
        return $cookie->withValue($this->prefix($cookie) . rawurlencode($value));
    }

    /**
     * Returns a new cookie instance with the decrypted cookie value.
     *
     * @param Cookie $cookie The cookie with encrypted value.
     *
     * @throws RuntimeException If the cookie value is tampered with or not validly encrypted. If you are not sure
     * that the value of the cookie file was encrypted earlier, then first use the {@see isEncrypted()}.
     *
     * @return Cookie The cookie with decrypted value.
     */
    public function decrypt(Cookie $cookie): Cookie
    {
        if (!$this->isEncrypted($cookie)) {
            throw new RuntimeException("The \"{$cookie->getName()}\" cookie value is not validly encrypted.");
        }

        try {
            /**
             * @psalm-suppress PossiblyFalseArgument Length of the cookie value is checked in the {@see isEncrypted()}
             * method and it is greater than 32, so `substr()` never returns false. This is actual for PHP 7.4 only.
             */
            $value = rawurldecode(substr($cookie->getValue(), 32));
            return $cookie->withValue($this->crypt->decryptByKey($value, $this->key, $cookie->getName()));
        } catch (AuthenticationException $e) {
            throw new RuntimeException("The \"{$cookie->getName()}\" cookie value was tampered with.");
        }
    }

    /**
     * Checks whether the cookie value is validly encrypted.
     *
     * @param Cookie $cookie The cookie to check.
     *
     * @return bool Whether the cookie value is validly encrypted.
     */
    public function isEncrypted(Cookie $cookie): bool
    {
        return strlen($cookie->getValue()) > 32 && strpos($cookie->getValue(), $this->prefix($cookie)) === 0;
    }

    /**
     * Returns a prefix for cookie.
     *
     * @param Cookie $cookie The cookie to prefix.
     *
     * @return string The prefix for cookie.
     */
    private function prefix(Cookie $cookie): string
    {
        return md5(self::class . $cookie->getName());
    }
}
