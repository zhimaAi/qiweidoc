<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http\Exception;

use Exception;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

use function headers_sent;

final class HeadersHaveBeenSentException extends Exception implements FriendlyExceptionInterface
{
    public function getName(): string
    {
        return 'HTTP headers have been sent.';
    }

    public function getSolution(): ?string
    {
        headers_sent($filename, $line);

        return "Headers already sent in $filename on line $line\n"
            . "Emitter can't send headers once the headers block has already been sent.";
    }
}
