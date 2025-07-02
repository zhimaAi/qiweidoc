<?php

declare(strict_types=1);

namespace Yiisoft\Json;

/**
 * Json is a helper class providing JSON data encoding and decoding.
 * It enhances the PHP built-in functions `json_encode()` and `json_decode()`
 * by throwing exceptions when decoding fails.
 */
final class Json
{
    /**
     * Encodes the given value into a JSON string.
     *
     * Note that data encoded as JSON must be UTF-8 encoded according to the JSON specification.
     * You must ensure strings passed to this method have proper encoding before passing them.
     *
     * @param mixed $value The data to be encoded.
     * @param int $options The encoding options. For more details please refer to
     * {@see http://www.php.net/manual/en/function.json-encode.php}.
     * Default is `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR`.
     * @param int $depth The maximum depth.
     * @return string The encoding result.
     * @throws \JsonException if there is any encoding error.
     */
    public static function encode(
        $value,
        int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        int $depth = 512
    ): string {
        $value = self::processData($value);
        return json_encode($value, JSON_THROW_ON_ERROR | $options, $depth);
    }

    /**
     * Encodes the given value into a JSON string HTML-escaping entities so it is safe to be embedded in HTML code.
     *
     * Note that data encoded as JSON must be UTF-8 encoded according to the JSON specification.
     * You must ensure strings passed to this method have proper encoding before passing them.
     *
     * @param mixed $value The data to be encoded.
     * @return string The encoding result.
     * @throws \JsonException If there is any encoding error.
     */
    public static function htmlEncode($value): string
    {
        return self::encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_THROW_ON_ERROR
        );
    }

    /**
     * Decodes the given JSON string into a PHP data structure.
     * @param string $json The JSON string to be decoded.
     * @param bool $asArray Whether to return objects in terms of associative arrays.
     * @param int $depth The recursion depth.
     * @param int $options The decode options.
     * @return mixed The PHP data.
     * @throws \JsonException If there is any decoding error.
     */
    public static function decode(
        string $json,
        bool $asArray = true,
        int $depth = 512,
        int $options = JSON_THROW_ON_ERROR
    ) {
        if ($json === '') {
            return null;
        }
        return json_decode($json, $asArray, $depth, JSON_THROW_ON_ERROR | $options);
    }

    /**
     * Pre-processes the data before sending it to `json_encode()`.
     * @param mixed $data The data to be processed.
     * @return mixed The processed data.
     */
    private static function processData($data)
    {
        if (\is_object($data)) {
            if ($data instanceof \JsonSerializable) {
                return self::processData($data->jsonSerialize());
            }

            if ($data instanceof \DateTimeInterface) {
                return static::processData((array)$data);
            }

            if ($data instanceof \SimpleXMLElement) {
                $data = (array)$data;
            } else {
                $result = [];
                foreach ($data as $name => $value) {
                    $result[$name] = $value;
                }
                $data = $result;
            }
            if ($data === []) {
                return new \stdClass();
            }
        }
        if (\is_array($data)) {
            foreach ($data as $key => $value) {
                if (\is_array($value) || \is_object($value)) {
                    $data[$key] = self::processData($value);
                }
            }
        }
        return $data;
    }
}
