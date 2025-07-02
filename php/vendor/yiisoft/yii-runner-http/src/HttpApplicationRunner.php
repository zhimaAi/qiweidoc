<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner\Http;

use ErrorException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Di\NotFoundException;
use Yiisoft\ErrorHandler\ErrorHandler;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\ErrorHandler\Renderer\HtmlRenderer;
use Yiisoft\Http\Method;
use Yiisoft\Log\Logger;
use Yiisoft\Log\Target\File\FileTarget;
use Yiisoft\Yii\Http\Application;
use Yiisoft\Yii\Http\Handler\ThrowableHandler;
use Yiisoft\Yii\Runner\ApplicationRunner;
use Yiisoft\Yii\Runner\Http\Exception\HeadersHaveBeenSentException;

use function microtime;

/**
 * `HttpApplicationRunner` runs the Yii HTTP application.
 */
final class HttpApplicationRunner extends ApplicationRunner
{
    private ?ErrorHandler $temporaryErrorHandler = null;

    /**
     * @param string $rootPath The absolute path to the project root.
     * @param bool $debug Whether the debug mode is enabled.
     * @param bool $checkEvents Whether to check events' configuration.
     * @param string|null $environment The environment name.
     * @param string $bootstrapGroup The bootstrap configuration group name.
     * @param string $eventsGroup The events' configuration group name.
     * @param string $diGroup The container definitions' configuration group name.
     * @param string $diProvidersGroup The container providers' configuration group name.
     * @param string $diDelegatesGroup The container delegates' configuration group name.
     * @param string $diTagsGroup The container tags' configuration group name.
     * @param string $paramsGroup The configuration parameters group name.
     * @param array $nestedParamsGroups Configuration group names that are included into configuration parameters group.
     * This is needed for recursive merging of parameters.
     * @param array $nestedEventsGroups Configuration group names that are included into events' configuration group.
     * This is needed for reverse and recursive merge of events' configurations.
     * @param object[] $configModifiers Modifiers for {@see Config}.
     * @param string $configDirectory The relative path from {@see $rootPath} to the configuration storage location.
     * @param string $vendorDirectory The relative path from {@see $rootPath} to the vendor directory.
     * @param string $configMergePlanFile The relative path from {@see $configDirectory} to merge plan.
     *
     * @psalm-param list<string> $nestedParamsGroups
     * @psalm-param list<string> $nestedEventsGroups
     * @psalm-param list<object> $configModifiers
     */
    public function __construct(
        string $rootPath,
        bool $debug = false,
        bool $checkEvents = false,
        ?string $environment = null,
        string $bootstrapGroup = 'bootstrap-web',
        string $eventsGroup = 'events-web',
        string $diGroup = 'di-web',
        string $diProvidersGroup = 'di-providers-web',
        string $diDelegatesGroup = 'di-delegates-web',
        string $diTagsGroup = 'di-tags-web',
        string $paramsGroup = 'params-web',
        array $nestedParamsGroups = ['params'],
        array $nestedEventsGroups = ['events'],
        array $configModifiers = [],
        string $configDirectory = 'config',
        string $vendorDirectory = 'vendor',
        string $configMergePlanFile = '.merge-plan.php',
    ) {
        parent::__construct(
            $rootPath,
            $debug,
            $checkEvents,
            $environment,
            $bootstrapGroup,
            $eventsGroup,
            $diGroup,
            $diProvidersGroup,
            $diDelegatesGroup,
            $diTagsGroup,
            $paramsGroup,
            $nestedParamsGroups,
            $nestedEventsGroups,
            $configModifiers,
            $configDirectory,
            $vendorDirectory,
            $configMergePlanFile,
        );
    }

    /**
     * Returns a new instance with the specified temporary error handler instance {@see ErrorHandler}.
     *
     * A temporary error handler is needed to handle the creation of configuration and container instances,
     * then the error handler configured in your application configuration will be used.
     *
     * @param ErrorHandler $temporaryErrorHandler The temporary error handler instance.
     */
    public function withTemporaryErrorHandler(ErrorHandler $temporaryErrorHandler): self
    {
        $new = clone $this;
        $new->temporaryErrorHandler = $temporaryErrorHandler;
        return $new;
    }

    /**
     * {@inheritDoc}
     *
     * @throws CircularReferenceException|ErrorException|HeadersHaveBeenSentException|InvalidConfigException
     * @throws ContainerExceptionInterface|NotFoundException|NotFoundExceptionInterface|NotInstantiableException
     */
    public function run(): void
    {
        $startTime = microtime(true);

        // Register temporary error handler to catch error while container is building.
        $temporaryErrorHandler = $this->createTemporaryErrorHandler();
        $this->registerErrorHandler($temporaryErrorHandler);

        $container = $this->getContainer();

        // Register error handler with real container-configured dependencies.
        /** @var ErrorHandler $actualErrorHandler */
        $actualErrorHandler = $container->get(ErrorHandler::class);
        $this->registerErrorHandler($actualErrorHandler, $temporaryErrorHandler);

        $this->runBootstrap();
        $this->checkEvents();

        /** @var Application $application */
        $application = $container->get(Application::class);

        /**
         * @var RequestFactory $requestFactory
         */
        $requestFactory = $container->get(RequestFactory::class);
        $request = $requestFactory->create();

        $request = $request->withAttribute('applicationStartTime', $startTime);
        try {
            $application->start();
            $response = $application->handle($request);
            $this->emit($request, $response);
        } catch (Throwable $throwable) {
            $handler = new ThrowableHandler($throwable);
            /**
             * @var ResponseInterface
             * @psalm-suppress MixedMethodCall
             */
            $response = $container
                ->get(ErrorCatcher::class)
                ->process($request, $handler);
            $this->emit($request, $response);
        } finally {
            $application->afterEmit($response ?? null);
            $application->shutdown();
        }
    }

    private function createTemporaryErrorHandler(): ErrorHandler
    {
        if ($this->temporaryErrorHandler !== null) {
            return $this->temporaryErrorHandler;
        }

        $logger = new Logger([new FileTarget("$this->rootPath/runtime/logs/app.log")]);
        return new ErrorHandler($logger, new HtmlRenderer());
    }

    /**
     * @throws HeadersHaveBeenSentException
     */
    private function emit(ServerRequestInterface $request, ResponseInterface $response): void
    {
        (new SapiEmitter())->emit($response, $request->getMethod() === Method::HEAD);
    }

    /**
     * @throws ErrorException
     */
    private function registerErrorHandler(ErrorHandler $registered, ErrorHandler $unregistered = null): void
    {
        $unregistered?->unregister();

        if ($this->debug) {
            $registered->debug();
        }

        $registered->register();
    }
}
