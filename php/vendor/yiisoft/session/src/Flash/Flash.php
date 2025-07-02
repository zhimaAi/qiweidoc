<?php

declare(strict_types=1);

namespace Yiisoft\Session\Flash;

use Yiisoft\Session\SessionInterface;

use function is_array;

/**
 * Session-based implementation of flash messages, a special type of data, that is available only in the current request
 * and the next request. After that, it will be deleted automatically. Flash messages are particularly
 * useful for displaying confirmation messages.
 */
final class Flash implements FlashInterface
{
    private const COUNTERS = '__counters';
    private const FLASH_PARAM = '__flash';

    private ?string $sessionId = null;

    public function __construct(private SessionInterface $session)
    {
    }

    public function get(string $key)
    {
        $flashes = $this->fetch();

        if (!isset($flashes[$key], $flashes[self::COUNTERS][$key])) {
            return null;
        }

        if ($flashes[self::COUNTERS][$key] < 0) {
            // Mark for deletion in the next request.
            $flashes[self::COUNTERS][$key] = 1;
            $this->save($flashes);
        }

        return $flashes[$key];
    }

    public function getAll(): array
    {
        $flashes = $this->fetch();

        $list = [];

        foreach ($flashes as $key => $value) {
            if ($key === self::COUNTERS) {
                continue;
            }

            $list[$key] = $value;
            if ($flashes[self::COUNTERS][$key] < 0) {
                // Mark for deletion in the next request.
                $flashes[self::COUNTERS][$key] = 1;
            }
        }

        $this->save($flashes);

        return $list;
    }

    public function set(string $key, $value = true, bool $removeAfterAccess = true): void
    {
        $flashes = $this->fetch();

        /** @psalm-suppress MixedArrayAssignment */
        $flashes[self::COUNTERS][$key] = $removeAfterAccess ? -1 : 0;

        $flashes[$key] = $value;
        $this->save($flashes);
    }

    public function add(string $key, $value = true, bool $removeAfterAccess = true): void
    {
        $flashes = $this->fetch();

        /** @psalm-suppress MixedArrayAssignment */
        $flashes[self::COUNTERS][$key] = $removeAfterAccess ? -1 : 0;

        if (empty($flashes[$key])) {
            $flashes[$key] = [$value];
        } elseif (is_array($flashes[$key])) {
            $flashes[$key][] = $value;
        } else {
            $flashes[$key] = [$flashes[$key], $value];
        }

        $this->save($flashes);
    }

    public function remove(string $key): void
    {
        $flashes = $this->fetch();
        unset($flashes[self::COUNTERS][$key], $flashes[$key]);
        $this->save($flashes);
    }

    public function removeAll(): void
    {
        $this->save([self::COUNTERS => []]);
    }

    public function has(string $key): bool
    {
        $flashes = $this->fetch();
        return isset($flashes[$key], $flashes[self::COUNTERS][$key]);
    }

    /**
     * Updates the counters for flash messages and removes outdated flash messages.
     * This method should be called once after session initialization.
     */
    private function updateCounters(): void
    {
        $flashes = $this->session->get(self::FLASH_PARAM, []);
        if (!is_array($flashes)) {
            $flashes = [self::COUNTERS => []];
        }

        $counters = $flashes[self::COUNTERS] ?? [];
        if (!is_array($counters)) {
            $counters = [];
        }

        /** @var array<string, int> $counters */
        foreach ($counters as $key => $count) {
            if ($count > 0) {
                unset($counters[$key], $flashes[$key]);
            } elseif ($count === 0) {
                $counters[$key]++;
            }
        }

        $flashes[self::COUNTERS] = $counters;
        $this->save($flashes);
    }

    /**
     * Obtains flash messages. Updates counters once per session.
     *
     * @return array Flash messages array.
     *
     * @psalm-return array{__counters:array<string,int>}&array
     */
    private function fetch(): array
    {
        // Ensure session is active (and has id).
        $this->session->open();
        if ($this->sessionId !== $this->session->getId()) {
            $this->sessionId = $this->session->getId();
            $this->updateCounters();
        }

        /** @psalm-var array{__counters:array<string,int>}&array */
        return $this->session->get(self::FLASH_PARAM, []);
    }

    /**
     * Save flash messages into session.
     *
     * @param array $flashes Flash messages to save.
     */
    private function save(array $flashes): void
    {
        $this->session->set(self::FLASH_PARAM, $flashes);
    }
}
