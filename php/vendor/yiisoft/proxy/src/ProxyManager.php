<?php

declare(strict_types=1);

namespace Yiisoft\Proxy;

use Exception;
use Yiisoft\Proxy\Config\ClassConfig;

final class ProxyManager
{
    /**
     * @var ClassRenderer A class renderer dependency.
     */
    private ClassRenderer $classRenderer;
    /**
     * @var ClassConfigFactory A class config factory dependency.
     */
    private ClassConfigFactory $classConfigFactory;
    /**
     * @var ClassCache|null A class cache dependency (optional).
     */
    private ?ClassCache $classCache;

    /**
     * A suffix appended to proxy class names / files.
     */
    public const PROXY_SUFFIX = 'Proxy';

    /**
     * @param string|null $cachePath Cache path, optional, {@see ClassCache::$cachePath}.
     */
    public function __construct(string $cachePath = null)
    {
        $this->classCache = $cachePath ? new ClassCache($cachePath) : null;
        $this->classRenderer = new ClassRenderer();
        $this->classConfigFactory = new ClassConfigFactory();
    }

    /**
     * Creates object proxy based on an interface / a class and parent proxy class.
     *
     * @param string $baseStructure Either or an interface or a class for proxying method calls.
     * @param string $parentProxyClass A base proxy class which acts like a parent for dynamically created proxy.
     * {@see ObjectProxy} or a class extended from it must be used.
     * @param array $proxyConstructorArguments A list of arguments passed to proxy constructor
     * ({@see ObjectProxy::__construct}).
     *
     * @psalm-param class-string $baseStructure
     *
     * @throws Exception In case of error during creation or working with cache / requiring PHP code.
     *
     * @return ObjectProxy A subclass of {@see ObjectProxy}.
     */
    public function createObjectProxy(
        string $baseStructure,
        string $parentProxyClass,
        array $proxyConstructorArguments
    ): ObjectProxy {
        $className = $baseStructure . self::PROXY_SUFFIX;
        /** @psalm-var class-string $shortClassName */
        $shortClassName = self::getProxyClassName($className);

        if (class_exists($shortClassName)) {
            /**
             * @var ObjectProxy
             * @psalm-suppress MixedMethodCall
             */
            return new $shortClassName(...$proxyConstructorArguments);
        }

        $classDeclaration = $this->classCache?->get($className, $parentProxyClass);
        if (!$classDeclaration) {
            $classConfig = $this->classConfigFactory->getClassConfig($baseStructure);
            $classConfig = $this->generateProxyClassConfig($classConfig, $parentProxyClass);
            $classDeclaration = $this->classRenderer->render($classConfig);
            $this->classCache?->set($baseStructure, $parentProxyClass, $classDeclaration);
        }
        if (!$this->classCache) {
            /** @psalm-suppress UnusedFunctionCall Bug https://github.com/vimeo/psalm/issues/8406 */
            eval(str_replace('<?php', '', $classDeclaration));
        } else {
            $path = $this->classCache->getClassPath($baseStructure, $parentProxyClass);
            /** @psalm-suppress UnresolvableInclude */
            require $path;
        }

        /**
         * @var ObjectProxy
         * @psalm-suppress MixedMethodCall
         */
        return new $shortClassName(...$proxyConstructorArguments);
    }

    /**
     * Generates class config for using with proxy from a regular class config.
     *
     * @param ClassConfig $classConfig Initial class config.
     * @param string $parentProxyClass A base proxy class which acts like a parent for dynamically created proxy.
     * {@see ObjectProxy} or a class extended from it must be used.
     *
     * @return ClassConfig Modified class config ready for using with proxy.
     */
    private function generateProxyClassConfig(ClassConfig $classConfig, string $parentProxyClass): ClassConfig
    {
        if ($classConfig->isInterface) {
            $classConfig->isInterface = false;
            $classConfig->interfaces = [$classConfig->name];
        }

        $classConfig->parent = $parentProxyClass;
        $classConfig->name .= self::PROXY_SUFFIX;
        $classConfig->shortName = self::getProxyClassName($classConfig->name);

        foreach ($classConfig->methods as $methodIndex => $method) {
            if ($method->name === '__construct') {
                unset($classConfig->methods[$methodIndex]);

                continue;
            }

            foreach ($method->modifiers as $index => $modifier) {
                if ($modifier === 'abstract') {
                    unset($classConfig->methods[$methodIndex]->modifiers[$index]);
                }
            }
        }

        return $classConfig;
    }

    /**
     * Transforms full class / interface name with namespace to short class name for using in proxy. For example:
     *
     * - `Yiisoft\Proxy\Tests\Stub\GraphInterfaceProxy` becomes `Yiisoft_Proxy_Tests_Stub_GraphInterfaceProxy`.
     * - `Yiisoft\Proxy\Tests\Stub\GraphProxy` becomes `Yiisoft_Proxy_Tests_Stub_GraphProxy`.
     *
     * and so on.
     *
     * @param string $fullClassName Initial class name.
     *
     * @return string Proxy class name.
     */
    private static function getProxyClassName(string $fullClassName): string
    {
        return str_replace('\\', '_', $fullClassName);
    }
}
