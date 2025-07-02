<?php

declare(strict_types=1);

namespace Yiisoft\Session;

use SessionHandlerInterface;
use Throwable;

/**
 * Session provides session data management and the related configurations.
 *
 * @psalm-type SessionOptions = array{
 *     name?: string,
 * }&array<string,mixed>
 */
final class Session implements SessionInterface
{
    private const DEFAULT_OPTIONS = [
        'cookie_secure' => 1,
        'use_only_cookies' => 1,
        'cookie_httponly' => 1,
        'use_strict_mode' => 1,
        'sid_bits_per_character' => 5,
        'sid_length' => 48,
        'cookie_samesite' => 'Lax',
    ];

    private ?string $sessionId = null;

    /**
     * @psalm-var SessionOptions
     */
    private array $options;

    /**
     * @param array $options Session options. See {@link https://www.php.net/manual/en/session.configuration.php}.
     * @param SessionHandlerInterface|null $handler Session handler. If not specified, default PHP handler is used.
     *
     * @psalm-param SessionOptions $options
     */
    public function __construct(array $options = [], SessionHandlerInterface $handler = null)
    {
        if ($handler !== null) {
            session_set_save_handler($handler, true);
        }

        // We set cookies using SessionMiddleware.
        $options['use_cookies'] = 0;

        // Prevent PHP to send headers.
        unset($options['cache_limiter']);

        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);
    }

    public function get(string $key, $default = null)
    {
        if ($this->getId() === null) {
            return $default;
        }

        $this->open();
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->open();
        $_SESSION[$key] = $value;
    }

    public function close(): void
    {
        if ($this->isActive()) {
            try {
                session_write_close();
            } catch (Throwable $e) {
                throw new SessionException('Unable to close session.', (int) $e->getCode(), $e);
            }
        }
    }

    /**
     * @throw SessionException When start session is failed.
     */
    public function open(): void
    {
        if ($this->isActive()) {
            return;
        }

        if ($this->sessionId !== null) {
            session_id($this->sessionId);
        }

        try {
            session_start($this->options);
            $this->sessionId = session_id();
        } catch (Throwable $e) {
            throw new SessionException('Failed to start session.', (int)$e->getCode(), $e);
        }
    }

    public function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function getId(): ?string
    {
        return $this->sessionId === '' ? null : $this->sessionId;
    }

    public function regenerateId(): void
    {
        if ($this->isActive()) {
            try {
                if (session_regenerate_id(true)) {
                    $this->sessionId = session_id();
                }
            } catch (Throwable $e) {
                throw new SessionException('Failed to regenerate ID.', (int)$e->getCode(), $e);
            }
        }
    }

    public function discard(): void
    {
        if ($this->isActive()) {
            session_abort();
        }
    }

    public function getName(): string
    {
        if ($this->isActive()) {
            return session_name();
        }

        return $this->options['name'] ?? session_name();
    }

    public function all(): array
    {
        if ($this->getId() === null) {
            return [];
        }

        $this->open();
        /** @var array $_SESSION */

        return $_SESSION;
    }

    public function remove(string $key): void
    {
        if ($this->getId() === null) {
            return;
        }

        $this->open();
        unset($_SESSION[$key]);
    }

    public function has(string $key): bool
    {
        if ($this->getId() === null) {
            return false;
        }

        $this->open();
        return isset($_SESSION[$key]);
    }

    public function pull(string $key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->remove($key);
        return $value;
    }

    public function clear(): void
    {
        if ($this->getId() !== null) {
            $this->open();
            $_SESSION = [];
        }
    }

    public function destroy(): void
    {
        if ($this->isActive()) {
            session_destroy();
            $this->sessionId = null;
        }
    }

    public function getCookieParameters(): array
    {
        return session_get_cookie_params();
    }

    public function setId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }
}
