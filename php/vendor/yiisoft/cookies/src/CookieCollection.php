<?php

declare(strict_types=1);

namespace Yiisoft\Cookies;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Exception;
use InvalidArgumentException;
use IteratorAggregate;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Header;

use function count;
use function in_array;

/**
 * A CookieCollection helps to work with many cookies at once and to read / modify response cookies.
 *
 * @see Cookie
 *
 * @implements IteratorAggregate<string,Cookie>
 * @implements ArrayAccess<string,Cookie>
 */
final class CookieCollection implements IteratorAggregate, ArrayAccess, Countable
{
    /**
     * @var Cookie[] The cookies in this collection (indexed by the cookie name).
     *
     * @psalm-var array<string, Cookie>
     */
    private array $cookies = [];

    /**
     * CookieCollection constructor.
     *
     * @param array|Cookie[] $cookies The cookies that this collection initially contains.
     */
    public function __construct(array $cookies = [])
    {
        foreach ($cookies as $cookie) {
            if (!($cookie instanceof Cookie)) {
                throw new InvalidArgumentException('CookieCollection can contain only Cookie instances.');
            }

            $this->cookies[$cookie->getName()] = $cookie;
        }
    }

    /**
     * Returns the collection as a PHP array.
     * The array keys are cookie names, and the array values are the corresponding cookie objects.
     *
     * @return Cookie[]
     */
    public function toArray(): array
    {
        return $this->cookies;
    }

    /**
     * Returns an iterator for traversing the cookies in the collection.
     * This method is required by the SPL interface {@see \IteratorAggregate}.
     * It will be implicitly called when you use `foreach` to traverse the collection.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->cookies);
    }

    /**
     * Returns whether there is a cookie with the specified name.
     * This method is required by the SPL interface {@see \ArrayAccess}.
     * It is implicitly called when you use something like `isset($collection[$name])`.
     * This is equivalent to {@see has()}.
     *
     * @param string $name The cookie name.
     *
     * @return bool Whether the named cookie exists.
     */
    public function offsetExists($name): bool
    {
        return $this->has($name);
    }

    /**
     * Returns the cookie with the specified name.
     * This method is required by the SPL interface {@see \ArrayAccess}.
     * It is implicitly called when you use something like `$cookie = $collection[$name];`.
     * This is equivalent to {@see get()}.
     *
     * @param string $name The cookie name.
     *
     * @return Cookie|null The cookie with the specified name, null if the named cookie does not exist.
     */
    public function offsetGet($name): ?Cookie
    {
        return $this->get($name);
    }

    /**
     * Adds the cookie to the collection.
     * This method is required by the SPL interface {@see \ArrayAccess}.
     * It is implicitly called when you use something like `$collection[$name] = $cookie;`.
     * This is equivalent to {@see add()}.
     *
     * @param string $name The cookie name.
     * @param Cookie $cookie The cookie to be added.
     *
     * @psalm-suppress ParamNameMismatch After drop PHP 7.4 support need to remove this suppress.
     */
    public function offsetSet($name, $cookie): void
    {
        $this->add($cookie);
    }

    /**
     * Removes the named cookie.
     * This method is required by the SPL interface {@see \ArrayAccess}.
     * It is implicitly called when you use something like `unset($collection[$name])`.
     * This is equivalent to {@see remove()}.
     *
     * @param string $name The cookie name.
     */
    public function offsetUnset($name): void
    {
        $this->remove($name);
    }

    /**
     * Returns the number of cookies in the collection.
     * This method is required by the SPL {@see \Countable} interface.
     * It will be implicitly called when you use `count($collection)`.
     *
     * @return int The number of cookies in the collection.
     */
    public function count(): int
    {
        return count($this->cookies);
    }

    /**
     * Returns the cookie with the specified name.
     *
     * @param string $name The cookie name.
     *
     * @return Cookie|null The cookie with the specified name. Null if the named cookie does not exist.
     *
     * @see getValue()
     */
    public function get(string $name): ?Cookie
    {
        return $this->cookies[$name] ?? null;
    }

    /**
     * Returns the value of the named cookie.
     *
     * @param string $name The cookie name.
     * @param string|null $defaultValue The value that should be returned when the named cookie does not exist.
     *
     * @return string|null The value of the named cookie or the default value if cookie is not set.
     *
     * @see get()
     */
    public function getValue(string $name, ?string $defaultValue = null): ?string
    {
        return isset($this->cookies[$name]) ? $this->cookies[$name]->getValue() : $defaultValue;
    }

    /**
     * Adds a cookie to the collection.
     * If there is already a cookie with the same name in the collection, it will be removed first.
     *
     * @param Cookie $cookie The cookie to be added.
     */
    public function add(Cookie $cookie): void
    {
        $this->cookies[$cookie->getName()] = $cookie;
    }

    /**
     * Returns whether there is a cookie with the specified name.
     *
     * @param string $name The cookie name.
     *
     * @return bool Whether the named cookie exists.
     *
     * @see remove()
     */
    public function has(string $name): bool
    {
        return isset($this->cookies[$name]);
    }

    /**
     * Removes a cookie.
     *
     * @param string $name The name of the cookie to be removed.
     *
     * @return Cookie|null Cookie that was removed.
     */
    public function remove(string $name): ?Cookie
    {
        if (!isset($this->cookies[$name])) {
            return null;
        }

        $removed = $this->cookies[$name];
        unset($this->cookies[$name]);

        return $removed;
    }

    /**
     * Removes all cookies.
     */
    public function clear(): void
    {
        $this->cookies = [];
    }

    /**
     * Returns whether the collection already contains the cookie.
     *
     * @param Cookie $cookie The cookie to check for.
     *
     * @return bool Whether cookie exists.
     *
     * @see has()
     */
    public function contains(Cookie $cookie): bool
    {
        return in_array($cookie, $this->cookies, true);
    }

    /**
     * Tests for the existence of the cookie that satisfies the given predicate.
     *
     * @param callable $p The predicate.
     *
     * @psalm-param callable(Cookie, string):bool $p
     *
     * @return bool Whether the predicate is true for at least on cookie.
     */
    public function exists(callable $p): bool
    {
        foreach ($this->cookies as $name => $cookie) {
            if ($p($cookie, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Expire the cookie with the specified name.
     *
     * @param string $name The cookie name.
     */
    public function expire(string $name): void
    {
        if (!isset($this->cookies[$name])) {
            return;
        }

        $this->cookies[$name] = $this->cookies[$name]->expire();
    }

    /**
     * Apply user supplied function to every cookie in the collection.
     *
     * Function signature is
     *
     * ```php
     * function (Cookie $cookie, string $key): void
     * ```
     *
     * If you want to modify the cookie in the collection, specify the first
     * parameter of the callback as reference.
     *
     * @param callable $callback
     *
     * @psalm-param callable(Cookie, string):void $callback
     */
    public function walk(callable $callback): void
    {
        /** @psalm-suppress MixedPropertyTypeCoercion */
        array_walk($this->cookies, $callback);
    }

    /**
     * Gets all keys/indices of the collection.
     *
     * @return string[] The keys/indices of the collection.
     */
    public function getKeys(): array
    {
        return array_keys($this->cookies);
    }

    /**
     * Gets all cookies of the collection as an indexed array.
     *
     * @return Cookie[] The cookies in the collection, in the order they appear in the collection.
     */
    public function getValues(): array
    {
        return array_values($this->cookies);
    }

    /**
     * Checks whether the collection is empty (contains no cookies).
     *
     * @return bool Whether the collection is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->cookies);
    }

    /**
     * Populates the cookie collection from an array of 'name' => 'value' pairs.
     *
     * @param array $array The cookies 'name' => 'value' array to populate from.
     *
     * @return static Collection created from array.
     */
    public static function fromArray(array $array): self
    {
        if (empty($array)) {
            return new self();
        }

        // Check if associative array with 'name' => 'value' pairs is passed.
        if (count(array_filter(array_keys($array), 'is_string')) !== count($array)) {
            throw new InvalidArgumentException('Invalid array format. It must be "name" => "value" pairs.');
        }

        /** @psalm-var array<string,string> $array */
        return new self(array_map(
            static fn (string $name, string $value) => new Cookie($name, $value),
            array_keys($array),
            $array
        ));
    }

    /**
     * Adds the cookies in the collection to response and returns it.
     *
     * @param ResponseInterface $response Response to add cookies to.
     *
     * @return ResponseInterface Response with added cookies.
     */
    public function addToResponse(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->cookies as $cookie) {
            $response = $cookie->addToResponse($response);
        }

        return $response;
    }

    /**
     * Creates a copy of the response with cookies set from the collection.
     *
     * @param ResponseInterface $response Response to set cookies to.
     *
     * @return ResponseInterface Response with new cookies.
     */
    public function setToResponse(ResponseInterface $response): ResponseInterface
    {
        $response = $response->withoutHeader(Header::SET_COOKIE);
        return $this->addToResponse($response);
    }

    /**
     * Populates the cookie collection from a ResponseInterface.
     *
     * @param ResponseInterface $response The response object to populate from.
     *
     * @throws Exception
     *
     * @return static Collection created from response.
     */
    public static function fromResponse(ResponseInterface $response): self
    {
        $collection = new self();
        foreach ($response->getHeader(Header::SET_COOKIE) as $setCookieString) {
            $cookie = Cookie::fromCookieString($setCookieString);
            $collection->add($cookie);
        }
        return $collection;
    }
}
