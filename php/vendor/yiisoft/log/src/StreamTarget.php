<?php

declare(strict_types=1);

namespace Yiisoft\Log;

use InvalidArgumentException;
use RuntimeException;

use function error_get_last;
use function fclose;
use function flock;
use function fopen;
use function fwrite;
use function get_resource_type;
use function is_resource;
use function sprintf;
use function stream_get_meta_data;

use const LOCK_EX;
use const LOCK_UN;

/**
 * StreamTarget is the log target that writes to the specified output stream.
 */
final class StreamTarget extends Target
{
    /**
     * @param resource|string $stream A string stream identifier or a stream resource.
     */
    public function __construct(private $stream = 'php://stdout')
    {
        parent::__construct();
    }

    protected function export(): void
    {
        $stream = $this->createStream();
        flock($stream, LOCK_EX);

        if (fwrite($stream, $this->formatMessages("\n")) === false) {
            flock($stream, LOCK_UN);
            fclose($stream);
            throw new RuntimeException(sprintf(
                'Unable to export the log because of an error writing to the stream: %s',
                error_get_last()['message'] ?? '',
            ));
        }

        $this->stream = stream_get_meta_data($stream)['uri'];
        flock($stream, LOCK_UN);
        fclose($stream);
    }

    /**
     * Check and create a stream resource.
     *
     * @throws RuntimeException if the stream cannot be opened.
     * @throws InvalidArgumentException if the stream is invalid.
     *
     * @return resource The stream resource.
     */
    private function createStream()
    {
        $stream = $this->stream;

        if (is_string($stream)) {
            $stream = @fopen($stream, 'ab');
            if ($stream === false) {
                throw new RuntimeException(sprintf(
                    'The "%s" stream cannot be opened.',
                    (string) $this->stream,
                ));
            }
        }

        /** @psalm-suppress DocblockTypeContradiction */
        if (!is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException(sprintf(
                'Invalid stream provided. It must be a string stream identifier or a stream resource, "%s" received.',
                get_debug_type($stream),
            ));
        }

        return $stream;
    }
}
