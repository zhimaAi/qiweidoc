<?php

namespace Common;

abstract class RouterProvider
{
    abstract public function init(): void;

    abstract public function getBroadcastRouters(): array;

    abstract public function getMicroServiceRouters(): array;

    abstract public function getConsumerRouters(): array;

    abstract public function getCronRouters(): array;

    abstract public function getHttpRouters(): array;
}
