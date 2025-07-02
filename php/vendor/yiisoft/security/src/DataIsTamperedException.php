<?php

declare(strict_types=1);

namespace Yiisoft\Security;

final class DataIsTamperedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Data does not match signature.');
    }
}
