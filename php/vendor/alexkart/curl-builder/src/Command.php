<?php declare(strict_types=1);

namespace Alexkart\CurlBuilder;

use Psr\Http\Message\RequestInterface;

final class Command
{
    /**
     * Template part which represents command name
     */
    public const TEMPLATE_COMMAND_NAME = '{name}';

    /**
     * Template part which represents command line options
     */
    public const TEMPLATE_OPTIONS = '{options}';

    /**
     * Template part which represents url
     */
    public const TEMPLATE_URL = '{url}';

    /**
     * Single quote character
     */
    public const QUOTE_SINGLE = "'";

    /**
     * Double quote character
     */
    public const QUOTE_DOUBLE = '"';

    /**
     * No quote character
     */
    public const QUOTE_NONE = '';

    /**
     * Command name
     */
    private const COMMAND_NAME = 'curl';

    /**
     * Header names that can use %x2C (",") character, so multiple header fields can't be folded into a single header field
     */
    private const HEADER_EXCEPTIONS = [
        'set-cookie',
        'www-authenticate',
        'proxy-authenticate',
    ];

    /**
     * @var string built command
     */
    private $command = '';

    /**
     * @var string url
     */
    private $url = '';

    /**
     * @var string command template
     */
    private $template;

    /**
     * @var array<mixed> command line options
     */
    private $options = [];

    /**
     * Character used to quote arguments
     * @var string
     */
    private $quoteCharacter;

    /**
     * @var RequestInterface|null
     */
    private $request;

    public function __construct()
    {
        $this->initTemplate();
        $this->initQuoteCharacter();
    }

    /**
     * Generates command
     * @return string
     */
    public function build(): string
    {
        $this->setCommand($this->getTemplate());
        $this->buildName();
        $this->buildOptions();
        $this->buildUrl();
        return $this->getCommand();
    }

    /**
     * @param string $url
     * @return Command
     */
    public function setUrl(string $url): Command
    {
        $this->url = $url;
        return $this;
    }


    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $template
     * @return Command
     */
    public function setTemplate(string $template): Command
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @param array<mixed> $options
     * @return Command
     */
    public function setOptions(array $options): Command
    {
        $this->options = $this->toInternalFormat($options);
        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param string $option
     * @param string|null $argument
     * @return Command
     */
    public function addOption(string $option, $argument = null): Command
    {
        $this->options[$option][] = $argument;
        return $this;
    }

    /**
     * @param array<mixed> $options
     */
    public function addOptions(array $options): void
    {
        $options = $this->toInternalFormat($options);
        foreach ($options as $option => $arguments) {
            foreach ($arguments as $argument) {
                $this->addOption((string)$option, $argument);
            }
        }
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    /**
     * Inits default command template
     */
    private function initTemplate(): void
    {
        $this->setTemplate(self::TEMPLATE_COMMAND_NAME . self::TEMPLATE_OPTIONS . self::TEMPLATE_URL);
    }

    /**
     * Inits default quote character
     */
    private function initQuoteCharacter(): void
    {
        $this->setQuoteCharacter(self::QUOTE_SINGLE);
    }

    /**
     * Builds command name
     */
    private function buildName(): void
    {
        $this->buildTemplatePart(self::TEMPLATE_COMMAND_NAME, self::COMMAND_NAME);
    }

    /**
     * Builds command line options
     */
    private function buildOptions(): void
    {
        $optionsString = '';
        $options = $this->getOptions();
        if (!empty($options)) {
            foreach ($options as $option => $arguments) {
                foreach ($arguments as $argument) {
                    $optionsString .= ' ' . $option;
                    if ($argument !== null) {
                        $optionsString .= ' ' . $this->quote($argument);
                    }
                }
            }
        }
        $optionsString = trim($optionsString);

        $this->buildTemplatePart(self::TEMPLATE_OPTIONS, $optionsString);
    }

    /**
     * Builds URL
     */
    private function buildUrl(): void
    {
        $this->buildTemplatePart(self::TEMPLATE_URL, $this->getUrl());
    }

    /**
     * Builds command part
     * @param string $search
     * @param string $replace
     */
    private function buildTemplatePart(string $search, string $replace): void
    {
        if ($replace === '') {
            // remove extra space
            $this->setCommand((string)preg_replace($this->getTemplatePartPattern($search), $search, $this->getCommand()));
        } else {
            // add space
            $replace = ' ' . $replace;
        }

        $this->setCommand(trim(str_replace($search, $replace, $this->getCommand())));
    }

    /**
     * Generates regular expression in order to remove extra spaces from the command template if the part is empty
     * @param string $search
     * @return string
     */
    private function getTemplatePartPattern(string $search): string
    {
        return '/ ?' . preg_quote($search, '/') . ' ?/';
    }

    /**
     * @param string $quoteCharacter
     * @return Command
     */
    public function setQuoteCharacter(string $quoteCharacter): Command
    {
        $this->quoteCharacter = $quoteCharacter;
        return $this;
    }

    /**
     * @return string
     */
    public function getQuoteCharacter(): string
    {
        return $this->quoteCharacter;
    }

    /**
     * Quotes argument
     * @param string $argument
     * @return string
     */
    private function quote(string $argument): string
    {
        $quoteCharacter = $this->getQuoteCharacter();

        if ($quoteCharacter === '') {
            return $this->escapeSpaces($argument);
        }

        if (strpos($argument, $quoteCharacter) !== false) {
            if ($quoteCharacter === self::QUOTE_SINGLE) {
                return '$' . $quoteCharacter . $this->escapeQuotes($argument) . $quoteCharacter;
            }

            return $quoteCharacter . $this->escapeQuotes($argument) . $quoteCharacter;
        }

        return $quoteCharacter . $argument . $quoteCharacter;
    }

    /**
     * Escapes quotes in the argument
     * @param string $argument
     * @return string
     */
    private function escapeQuotes(string $argument): string
    {
        return str_replace($this->getQuoteCharacter(), '\\' . $this->getQuoteCharacter(), $argument);
    }

    /**
     * Escapes spaces in the argument when no quoting is used
     * @param string $argument
     * @return string
     */
    private function escapeSpaces(string $argument): string
    {
        return str_replace(' ', '\\ ', $argument);
    }

    /**
     * Converts option from user-friendly format ot internal format
     * @param array<mixed> $options
     * @return array<mixed>
     */
    private function toInternalFormat(array $options): array
    {
        $formattedOptions = [];
        foreach ($options as $option => $arguments) {
            $option = trim((string)$option);

            if (strpos($option, '-') !== 0) {
                // ['-L', '-v']
                $option = (string)$arguments;
                $arguments = [null];
            } elseif (!is_array($arguments)) {
                // ['-L' => null, '-v' => null]
                $arguments = [$arguments];
            }

            foreach ($arguments as $argument) {
                $formattedOptions[$option][] = $argument;
            }
        }

        return $formattedOptions;
    }


    /**
     * Sets request. If $parse = true gets data from request
     * @param RequestInterface|null $request
     * @param bool $parse
     * @return Command
     */
    public function setRequest(?RequestInterface $request, bool $parse = true): Command
    {
        $this->request = $request;
        if ($parse) {
            $this->parseRequest();
        }

        return $this;
    }

    /**
     * @return RequestInterface|null
     */
    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }


    /**
     * Gets data from request
     * @return bool
     */
    public function parseRequest(): bool
    {
        $request = $this->getRequest();
        if ($request === null) {
            return false;
        }

        // URL
        $this->setUrl((string)$request->getUri());

        // headers
        foreach (array_keys($request->getHeaders()) as $name) {
            $name = (string)$name;
            if (strtolower($name) === 'host') {
                continue;
            }
            if (in_array(strtolower($name), self::HEADER_EXCEPTIONS, true)) {
                foreach ($request->getHeader($name) as $value) {
                    $this->addOption('-H', $name . ': ' . $value);
                }
            } else {
                $this->addOption('-H', $name . ': ' . $request->getHeaderLine($name));
            }
        }

        // data
        $data = (string)$request->getBody();
        if (!empty($data)) {
            $this->addOption('-d', (string)$request->getBody());
        }

        return true;
    }
}
