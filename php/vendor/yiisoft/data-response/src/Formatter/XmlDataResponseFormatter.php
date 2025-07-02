<?php

declare(strict_types=1);

namespace Yiisoft\DataResponse\Formatter;

use DOMDocument;
use DOMElement;
use DOMException;
use DOMText;
use Psr\Http\Message\ResponseInterface;
use Traversable;
use Yiisoft\DataResponse\ResponseContentTrait;
use Yiisoft\Strings\NumericHelper;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFormatterInterface;

use function is_array;
use function is_float;
use function is_int;
use function is_object;

/**
 * XmlDataResponseFormatter formats the response data as XML.
 */
final class XmlDataResponseFormatter implements DataResponseFormatterInterface
{
    use ResponseContentTrait;

    private const DEFAULT_ITEM_TAG_NAME = 'item';

    /**
     * @var string The Content-Type header for the response.
     */
    private string $contentType = 'application/xml';

    /**
     * @var string The encoding for the Content-Type header.
     */
    private string $encoding = 'UTF-8';

    /**
     * @var string The XML version.
     */
    private string $version = '1.0';

    /**
     * @var string The name of the root element. If an empty value is set, the root tag should not be added.
     */
    private string $rootTag = 'response';

    /**
     * @inheritDoc
     */
    public function format(DataResponse $dataResponse): ResponseInterface
    {
        if ($dataResponse->hasData()) {
            $dom = new DOMDocument($this->version, $this->encoding);

            $data = $dataResponse->getData();

            if (!empty($this->rootTag)) {
                $root = new DOMElement($this->rootTag);
                $dom->appendChild($root);
                $this->buildXml($dom, $root, $data);
            } else {
                $this->buildXml($dom, $dom, $data);
            }

            $content = (string) $dom->saveXML();
        }

        /** @psalm-suppress MixedArgument */
        return $this->addToResponse($dataResponse->getResponse(), $content ?? null);
    }

    /**
     * Returns a new instance with the specified version.
     *
     * @param string $version The XML version. Default is "1.0".
     *
     * @return self
     */
    public function withVersion(string $version): self
    {
        $new = clone $this;
        $new->version = $version;
        return $new;
    }

    /**
     * Returns a new instance with the specified root tag.
     *
     * @param string $rootTag The name of the root element. Default is "response".
     * If an empty value is set, the root tag should not be added.
     *
     * @return self
     */
    public function withRootTag(string $rootTag): self
    {
        $new = clone $this;
        $new->rootTag = $rootTag;
        return $new;
    }

    /**
     * Builds the data to use in XML.
     *
     * @param DOMDocument $dom The root DOM document.
     * @param DOMDocument|DOMElement $element The current DOM element being processed.
     * @param mixed $data Data for building XML.
     */
    private function buildXml(DOMDocument $dom, $element, mixed $data): void
    {
        if (empty($data)) {
            return;
        }

        if (is_array($data) || ($data instanceof Traversable && !($data instanceof XmlDataInterface))) {
            /**
             * @var int|string $name
             */
            foreach ($data as $name => $value) {
                if (is_object($value)) {
                    $this->buildObject($dom, $element, $value, $name);
                    continue;
                }

                $child = $this->safeCreateDomElement($dom, $name);
                $element->appendChild($child);

                if (is_array($value)) {
                    $this->buildXml($dom, $child, $value);
                    continue;
                }

                /** @psalm-var scalar $value */

                $this->setScalarValueToDomElement($child, $value);
            }

            return;
        }

        if (is_object($data)) {
            $this->buildObject($dom, $element, $data);
            return;
        }

        /** @psalm-var scalar $data */

        $this->setScalarValueToDomElement($element, $data);
    }

    /**
     * Builds the object to use in XML.
     *
     * @param DOMDocument $dom The root DOM document.
     * @param DOMDocument|DOMElement $element The current DOM element being processed.
     * @param object $object To build.
     * @param int|string|null $tagName The tag name.
     */
    private function buildObject(DOMDocument $dom, $element, object $object, $tagName = null): void
    {
        if ($object instanceof XmlDataInterface) {
            $child = $this->safeCreateDomElement($dom, $object->xmlTagName());

            foreach ($object->xmlTagAttributes() as $name => $value) {
                $child->setAttribute($name, $value);
            }

            $element->appendChild($child);
            $this->buildXml($dom, $child, $object->xmlData());
            return;
        }

        $child = $this->safeCreateDomElement($dom, $tagName);
        $element->appendChild($child);

        if ($object instanceof Traversable) {
            $this->buildXml($dom, $child, $object);
            return;
        }

        $data = [];

        /**
         * @var string $property
         */
        foreach ($object as $property => $value) {
            $data[$property] = $value;
        }

        $this->buildXml($dom, $child, $data);
    }

    /**
     * Safely creates a DOMElement instance by the specified tag name if the tag name is not empty,
     * is not integer, and is valid. Otherwise {@see DEFAULT_ITEM_TAG_NAME} value is used.
     *
     * @see https://stackoverflow.com/questions/2519845/how-to-check-if-string-is-a-valid-xml-element-name/2519943#2519943
     *
     * @param DOMDocument $dom The root DOM document.
     * @param int|string|null $tagName The tag name.
     *
     * @return DOMElement
     */
    private function safeCreateDomElement(DOMDocument $dom, $tagName): DOMElement
    {
        if (empty($tagName) || is_int($tagName)) {
            return $dom->createElement(self::DEFAULT_ITEM_TAG_NAME);
        }

        try {
            if (!$element = $dom->createElement($tagName)) {
                throw new DOMException();
            }
            return $element;
        } catch (DOMException) {
            return $dom->createElement(self::DEFAULT_ITEM_TAG_NAME);
        }
    }

    /**
     * Sets the scalar value to DOM Element instance if the value is not empty.
     *
     * @param DOMDocument|DOMElement $element The current DOM element being processed.
     * @param bool|float|int|string|null $value
     */
    private function setScalarValueToDomElement($element, $value): void
    {
        $value = $this->formatScalarValue($value);

        if ($value !== '') {
            $element->appendChild(new DOMText($value));
        }
    }

    /**
     * Formats scalar value for use in XML node.
     *
     * @param bool|float|int|string|null $value To format.
     *
     * @return string The string representation of the value.
     */
    private function formatScalarValue($value): string
    {
        if ($value === true) {
            return 'true';
        }

        if ($value === false) {
            return 'false';
        }

        if (is_float($value)) {
            return NumericHelper::normalize($value);
        }

        return (string) $value;
    }
}
