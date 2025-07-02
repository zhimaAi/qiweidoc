<?php

declare(strict_types=1);

namespace Yiisoft\Proxy;

use Exception;
use RuntimeException;
use Yiisoft\Files\FileHelper;

/**
 * @internal
 *
 * Local file system based cache used to store and retrieve contents of proxy objects. See {@see ProxyManager} for
 * usage.
 */
final class ClassCache
{
    public function __construct(
        /**
         * @var string Base directory for working with cache. It will be created automatically if it does not exist
         * ({@see getClassPath()}).
         */
        private string $cachePath
    ) {
    }

    /**
     * Writes proxy class contents to a file in {@see getClassPath()}.
     *
     * @param string $className The full name of user class or interface (with namespace). For example:
     * `Yiisoft\Proxy\Tests\Stub\GraphInterface` or `Yiisoft\Proxy\Tests\Stub\Graph`. You can use `::class` instead of
     * manually specifying a string.
     * @param string $baseProxyClassName The full name of {@see ObjectProxy} implementation (with namespace) which will
     * be the base class for proxy. For example: `MyProxy`.
     * @param string $classContents The whole class contents without opening PHP tag (it's prepended automatically).
     */
    public function set(string $className, string $baseProxyClassName, string $classContents): void
    {
        file_put_contents($this->getClassPath($className, $baseProxyClassName), "<?php\n\n" . $classContents, LOCK_EX);
    }

    /**
     * Reads proxy class contents.
     *
     * @param string $className The full name of user class or interface (with namespace). For example: `GraphInterface`
     * or `Graph`. You can use `::class` instead of manually specifying a string.
     * @param string $baseProxyClassName The full name of {@see ObjectProxy} implementation (with namespace) which will
     * be the base class for proxy. For example: `MyProxy`.
     *
     * @throws Exception When unable to write to a file in {@see getClassPath()}.
     *
     * @return string|null In case of presence data in cache the whole class contents (including PHP opening tag)
     * returned as a string. In case of its absence or other errors - `null` is returned.
     */
    public function get(string $className, string $baseProxyClassName): ?string
    {
        $classPath = $this->getClassPath($className, $baseProxyClassName);
        if (!file_exists($classPath)) {
            return null;
        }

        $content = file_get_contents($classPath);

        return $content === false ? null : $content;
    }

    /**
     * Gets full path to a class. For example: `/tmp/Yiisoft/Tests/Stub/GraphInterface.MyProxy.php` or
     * `/tmp/Yiisoft/Tests/Stub/Graph.MyProxy.php`. Additionally, checks and prepares (if needed) {@see $cachePath} for
     * usage (@see FileHelper::ensureDirectory()}.
     *
     * @param string $className The full name of user class or interface (with namespace). For example: `GraphInterface`
     * or `Graph`. You can use `::class` instead of manually specifying a string.
     * @param string $baseProxyClassName The full name of {@see ObjectProxy} implementation (with namespace) which will
     * be the base class for proxy. For example: `MyProxy`.
     *
     * @throws RuntimeException In case when it's impossible to use or create {@see $cachePath}.
     *
     * @return string
     */
    public function getClassPath(string $className, string $baseProxyClassName): string
    {
        [$classFileName, $classFilePath] = $this->getClassFileNameAndPath($className, $baseProxyClassName);

        try {
            FileHelper::ensureDirectory($classFilePath, 0777);
        } catch (RuntimeException) {
            throw new RuntimeException("Directory \"$classFilePath\" was not created.");
        }

        return $classFilePath . DIRECTORY_SEPARATOR . $classFileName;
    }

    /**
     * Gets class file name and path as separate elements:
     *
     * - For name, a combination of both class name and base proxy class name is used.
     * - For path, {@see $cachePath} used as a base directory and class namespace for subdirectories.
     *
     * @param string $className The full name of user class or interface (with namespace). For example: `GraphInterface`
     * or `Graph`. You can use `::class` instead of manually specifying a string.
     * @param string $baseProxyClassName The full name of {@see ObjectProxy} implementation (with namespace) which will
     * be the base class for proxy. For example: `MyProxy`.
     *
     * @return string[] Array with two elements, the first one is a file name and the second one is a path. For example:
     * `[`/tmp/Yiisoft/Proxy/Tests/Stub`, `GraphInterface.MyProxy.php`]` or
     * `[`/tmp/Yiisoft/Proxy/Tests/Stub`, `Graph.MyProxy.php`]`.
     */
    private function getClassFileNameAndPath(string $className, string $baseProxyClassName): array
    {
        $classParts = explode('\\', $className);
        if (count($classParts) === 1) {
            $classParts = ['Builtin', ...$classParts];
        }

        $parentClassParts = explode('\\', $baseProxyClassName);
        $classFileName = array_pop($classParts) . '.' . array_pop($parentClassParts) . '.php';
        $classFilePath = $this->cachePath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $classParts);

        return [$classFileName, $classFilePath];
    }
}
