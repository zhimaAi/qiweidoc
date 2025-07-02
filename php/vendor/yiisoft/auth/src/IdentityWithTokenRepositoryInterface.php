<?php

declare(strict_types=1);

namespace Yiisoft\Auth;

/**
 * Identity repository is identity storage that is able to retrieve identity given a token.
 */
interface IdentityWithTokenRepositoryInterface
{
    /**
     * Finds an identity by the given token.
     *
     * @param string $token The token to be looked for.
     * @param string|null $type The type of the token. The value of this parameter depends on the implementation
     * and should allow supporting multiple token types for a single identity.
     *
     * @return IdentityInterface|null The identity object that matches the given token. Null should be returned if such
     * an identity cannot be found or the identity is not in an active state (disabled, deleted, etc.)
     */
    public function findIdentityByToken(string $token, string $type = null): ?IdentityInterface;
}
