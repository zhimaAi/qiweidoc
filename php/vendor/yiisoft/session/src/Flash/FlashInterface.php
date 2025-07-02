<?php

declare(strict_types=1);

namespace Yiisoft\Session\Flash;

/**
 * Helps working with flash messages, a special type of data, that is available only in the current request
 * and the next request. After that, it will be deleted automatically. Flash messages are particularly
 * useful for displaying confirmation messages.
 */
interface FlashInterface
{
    /**
     * Returns a flash message.
     *
     * @param string $key The key identifying the flash message.
     *
     * @return mixed The flash message or an array of messages if {@see add()} was used.
     */
    public function get(string $key);

    /**
     * Returns all flash messages.
     * Flash messages will be automatically deleted in the next request.
     *
     * @return array A set of flash messages. Key => message or key => [message1, message2] if {@see add()} was used.
     */
    public function getAll(): array;

    /**
     * Sets a flash message.
     * A flash message will be automatically deleted after it is accessed in a request and the deletion will happen
     * in the next request.
     * If there is already an existing flash message with the same key, it will be overwritten by the new one.
     *
     * @param string $key The key identifying the flash message.
     * @param mixed $value Flash message.
     * @param bool $removeAfterAccess Whether the flash message should be automatically removed only if
     * it is accessed. If false, the flash message will be automatically removed after the next request,
     * regardless if it is accessed or not. If true (default value), the flash message will remain until after
     * it is accessed.
     */
    public function set(string $key, $value = true, bool $removeAfterAccess = true): void;

    /**
     * Adds a flash message.
     * If there are existing flash messages with the same key, the new one will be appended to the existing message array.
     *
     * @param string $key The key identifying the flash message.
     * @param mixed $value Flash message.
     * @param bool $removeAfterAccess Whether the flash message should be automatically removed only if
     * it is accessed. If false, the flash message will be automatically removed after the next request,
     * regardless if it is accessed or not. If true (default value), the flash message will remain until after
     * it is accessed.
     */
    public function add(string $key, $value = true, bool $removeAfterAccess = true): void;

    /**
     * Removes a flash message.
     *
     * @param string $key The key identifying the flash message.
     */
    public function remove(string $key): void;

    /**
     * Removes all flash messages.
     */
    public function removeAll(): void;

    /**
     * Returns a value indicating whether there are flash messages associated with the specified key.
     *
     * @param string $key Key identifying the flash message type.
     *
     * @return bool Whether any flash messages exist under specified key.
     */
    public function has(string $key): bool;
}
