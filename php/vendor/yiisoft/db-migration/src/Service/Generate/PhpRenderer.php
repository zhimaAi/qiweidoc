<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Service\Generate;

use Throwable;

use function extract;
use function ob_clean;
use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_implicit_flush;
use function ob_start;

/**
 * @internal
 */
final class PhpRenderer
{
    public function render(string $file, array $params = []): string
    {
        $obInitialLevel = ob_get_level();
        ob_start();
        ob_implicit_flush(false);

        try {
            $this->renderer($file, $params);
            return ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $obInitialLevel) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        }
    }

    private function renderer(): void
    {
        /** @psalm-suppress MixedArgument */
        extract(func_get_arg(1));
        /** @psalm-suppress UnresolvableInclude */
        require func_get_arg(0);
    }
}
