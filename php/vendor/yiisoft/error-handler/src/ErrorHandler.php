<?php

declare(strict_types=1);

namespace Yiisoft\ErrorHandler;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Yiisoft\ErrorHandler\Event\ApplicationError;
use Yiisoft\ErrorHandler\Exception\ErrorException;
use Yiisoft\ErrorHandler\Renderer\PlainTextRenderer;
use Yiisoft\Http\Status;

use function error_get_last;
use function error_reporting;
use function function_exists;
use function http_response_code;
use function ini_set;
use function register_shutdown_function;
use function set_error_handler;
use function set_exception_handler;
use function str_repeat;

/**
 * `ErrorHandler` handles out of memory errors, fatals, warnings, notices and exceptions.
 */
final class ErrorHandler
{
    /**
     * @var int The size of the reserved memory. A portion of memory is pre-allocated so that
     * when an out-of-memory issue occurs, the error handler is able to handle the error with
     * the help of this reserved memory. If you set this value to be 0, no memory will be reserved.
     * Defaults to 256KB.
     */
    private int $memoryReserveSize = 262_144;
    private string $memoryReserve = '';
    private bool $debug = false;
    private ?string $workingDirectory = null;
    private bool $enabled = false;
    private bool $initialized = false;

    /**
     * @param LoggerInterface $logger Logger to write errors to.
     * @param ThrowableRendererInterface $defaultRenderer Default throwable renderer.
     * @param EventDispatcherInterface|null $eventDispatcher Event dispatcher for error events.
     * @param int $exitShutdownHandlerDepth Depth of the exit() shutdown handler to ensure it's executed last.
     */
    public function __construct(
        private LoggerInterface $logger,
        private ThrowableRendererInterface $defaultRenderer,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private int $exitShutdownHandlerDepth = 2
    ) {
    }

    /**
     * Handles throwable and returns error data.
     *
     * @param ThrowableRendererInterface|null $renderer
     * @param ServerRequestInterface|null $request
     */
    public function handle(
        Throwable $t,
        ThrowableRendererInterface $renderer = null,
        ServerRequestInterface $request = null
    ): ErrorData {
        $renderer ??= $this->defaultRenderer;

        try {
            $this->logger->error(PlainTextRenderer::throwableToString($t), ['throwable' => $t]);
            return $this->debug ? $renderer->renderVerbose($t, $request) : $renderer->render($t, $request);
        } catch (Throwable $t) {
            return new ErrorData((string) $t);
        }
    }

    /**
     * Enables and disables debug mode.
     *
     * Ensure that is is disabled in production environment since debug mode exposes sensitive details.
     *
     * @param bool $enable Enable/disable debugging mode.
     */
    public function debug(bool $enable = true): void
    {
        $this->debug = $enable;
    }

    /**
     * Sets the size of the reserved memory.
     *
     * @param int $size The size of the reserved memory.
     *
     * @see $memoryReserveSize
     */
    public function memoryReserveSize(int $size): void
    {
        $this->memoryReserveSize = $size;
    }

    /**
     * Register PHP exception and error handlers and enable this error handler.
     */
    public function register(): void
    {
        if ($this->enabled) {
            return;
        }

        if ($this->memoryReserveSize > 0) {
            $this->memoryReserve = str_repeat('x', $this->memoryReserveSize);
        }

        $this->initializeOnce();

        // Handles throwable that isn't caught otherwise, echo output and exit.
        set_exception_handler(function (Throwable $t): void {
            if (!$this->enabled) {
                return;
            }

            $this->renderThrowableAndTerminate($t);
        });

        // Handles PHP execution errors such as warnings and notices.
        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!$this->enabled) {
                return false;
            }

            if (!(error_reporting() & $severity)) {
                // This error code is not included in error_reporting.
                return true;
            }

            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            throw new ErrorException($message, $severity, $severity, $file, $line, null, $backtrace);
        });

        $this->enabled = true;
    }

    /**
     * Disable this error handler.
     */
    public function unregister(): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->memoryReserve = '';

        $this->enabled = false;
    }

    private function initializeOnce(): void
    {
        if ($this->initialized) {
            return;
        }

        // Disables the display of error.
        if (function_exists('ini_set')) {
            ini_set('display_errors', '0');
        }

        // Handles fatal error.
        register_shutdown_function(function (): void {
            if (!$this->enabled) {
                return;
            }

            $this->memoryReserve = '';
            $e = error_get_last();

            if ($e !== null && ErrorException::isFatalError($e)) {
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                $error = new ErrorException(
                    $e['message'],
                    $e['type'],
                    $e['type'],
                    $e['file'],
                    $e['line'],
                    null,
                    $backtrace
                );
                $this->renderThrowableAndTerminate($error);
            }
        });

        if (!(PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')) {
            $this->workingDirectory = getcwd();
        }

        $this->initialized = true;
    }

    /**
     * Renders the throwable and terminates the script.
     */
    private function renderThrowableAndTerminate(Throwable $t): void
    {
        if (!empty($this->workingDirectory)) {
            chdir($this->workingDirectory);
        }
        // Disable error capturing to avoid recursive errors while handling exceptions.
        $this->unregister();
        // Set preventive HTTP status code to 500 in case error handling somehow fails and headers are sent.
        http_response_code(Status::INTERNAL_SERVER_ERROR);

        echo $this->handle($t);
        $this->eventDispatcher?->dispatch(new ApplicationError($t));

        $handler = $this->wrapShutdownHandler(
            static function (): void {
                exit(1);
            },
            $this->exitShutdownHandlerDepth
        );

        register_shutdown_function($handler);
    }

    /**
     * Wraps shutdown handler into another shutdown handler to ensure it is called last after all other shutdown
     * functions, even those added to the end.
     *
     * @param callable $handler Shutdown handler to wrap.
     * @param int $depth Wrapping depth.
     * @return callable Wrapped handler.
     */
    private function wrapShutdownHandler(callable $handler, int $depth): callable
    {
        $currentDepth = 0;
        while ($currentDepth < $depth) {
            $handler = static function() use ($handler): void {
                register_shutdown_function($handler);
            };
            $currentDepth++;
        }
        return $handler;
    }
}
