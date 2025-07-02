<?php

declare(strict_types=1);

namespace Yiisoft\Auth;

/**
 * Identity is what represents a "user" that can authenticate in the application.
 */
interface IdentityInterface
{
    /**
     * Returns an ID that can uniquely identify a user identity.
     *
     * @return string an ID that uniquely identifies a user identity.
     */
    public function getId(): ?string;
}
