<?php

declare(strict_types=1);

namespace Yiisoft\Cookies;

use RuntimeException;
use Yiisoft\Security\DataIsTamperedException;
use Yiisoft\Security\Mac;

use function md5;
use function strlen;
use function substr;

/**
 * A CookieSigner signs the cookie value and validates whether the signed cookie value has been tampered with.
 *
 * @see Cookie
 */
final class CookieSigner
{
    /**
     * @var Mac The Mac instance.
     */
    private Mac $mac;

    /**
     * @var string The secret key used to sign and validate cookie values.
     */
    private string $key;

    /**
     * @param string $key The secret key used to sign and validate cookie values.
     */
    public function __construct(string $key)
    {
        $this->mac = new Mac();
        $this->key = $key;
    }

    /**
     * Returns a new cookie instance with the signed cookie value.
     *
     * @param Cookie $cookie The cookie with clean value.
     *
     * @throws RuntimeException If the cookie value is already signed.
     *
     * @return Cookie The cookie with signed value.
     */
    public function sign(Cookie $cookie): Cookie
    {
        if ($this->isSigned($cookie)) {
            throw new RuntimeException("The \"{$cookie->getName()}\" cookie value is already signed.");
        }

        $prefix = $this->prefix($cookie);
        $value = $this->mac->sign($prefix . $cookie->getValue(), $this->key);
        return $cookie->withValue($prefix . $value);
    }

    /**
     * Returns a new cookie instance with the clean cookie value or throws an exception if signature is not valid.
     *
     * @param Cookie $cookie The cookie with signed value.
     *
     * @throws RuntimeException If the cookie value is tampered with or not validly signed.
     * If you are not sure that the value of the cookie file was signed earlier, then first use the {@see isSigned()}.
     *
     * @return Cookie The cookie with unsigned value.
     */
    public function validate(Cookie $cookie): Cookie
    {
        if (!$this->isSigned($cookie)) {
            throw new RuntimeException("The \"{$cookie->getName()}\" cookie value is not validly signed.");
        }

        try {
            $value = $this->mac->getMessage(substr($cookie->getValue(), 32), $this->key);
            return $cookie->withValue(substr($value, 32));
        } catch (DataIsTamperedException $e) {
            throw new RuntimeException("The \"{$cookie->getName()}\" cookie value was tampered with.");
        }
    }

    /**
     * Checks whether the cookie value is validly signed.
     *
     * @param Cookie $cookie The cookie to check.
     *
     * @return bool Whether the cookie value is validly signed.
     */
    public function isSigned(Cookie $cookie): bool
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
