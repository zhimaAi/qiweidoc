<?php

declare(strict_types=1);

namespace Yiisoft\I18n;

use InvalidArgumentException;

/**
 * Locale stores locale information created from BCP 47 formatted string.
 *
 * @link https://tools.ietf.org/html/bcp47
 */
final class Locale implements \Stringable
{
    /**
     * @var string|null Two-letter ISO-639-2 language code.
     *
     * @link http://www.loc.gov/standards/iso639-2/
     */
    private ?string $language = null;

    /**
     * @var string|null Extended language subtags.
     */
    private ?string $extendedLanguage = null;

    /**
     * @var string|null
     */
    private ?string $extension = null;

    /**
     * @var string|null Four-letter ISO 15924 script code.
     *
     * @link http://www.unicode.org/iso15924/iso15924-codes.html
     */
    private ?string $script = null;

    /**
     * @var string|null Two-letter ISO 3166-1 country code.
     *
     * @link https://www.iso.org/iso-3166-country-codes.html
     */
    private ?string $region = null;

    /**
     * @var string|null Variant of language conventions to use.
     */
    private ?string $variant = null;

    /**
     * @var string|null ICU currency.
     */
    private ?string $currency = null;

    /**
     * @var string|null ICU calendar.
     */
    private ?string $calendar = null;

    /**
     * @var string|null ICU case-first collation.
     *
     * @link https://unicode-org.github.io/icu/userguide/collation/customization/#casefirst
     * @link https://www.unicode.org/reports/tr35/tr35-61/tr35-collation.html#Collation_Settings
     */
    private ?string $colcasefirst = null;

    /**
     * @var string|null ICU collation.
     */
    private ?string $collation = null;

    /**
     * @var string|null ICU numeric collation.
     *
     * @link https://unicode-org.github.io/icu/userguide/collation/customization/#numericordering
     * @link https://www.unicode.org/reports/tr35/tr35-61/tr35-collation.html#Collation_Settings
     */
    private ?string $colnumeric = null;

    /**
     * @var string|null ICU numbers.
     */
    private ?string $numbers = null;

    /**
     * @var string|null Unicode hour cycle identifier.
     *
     * @link https://www.unicode.org/reports/tr35/#UnicodeHourCycleIdentifier
     */
    private ?string $hours = null;

    /**
     * @var string|null
     */
    private ?string $grandfathered = null;

    /**
     * @var string|null
     */
    private ?string $private = null;

    /**
     * Locale constructor.
     *
     * @param string $localeString BCP 47 formatted locale string.
     *
     * @link https://tools.ietf.org/html/bcp47
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $localeString)
    {
        if (!preg_match(self::getBCP47Regex(), $localeString, $matches)) {
            throw new InvalidArgumentException($localeString . ' is not valid BCP 47 formatted locale string.');
        }

        if (!empty($matches['language'])) {
            $this->language = strtolower($matches['language']);
        }

        if (!empty($matches['region'])) {
            $this->region = strtoupper($matches['region']);
        }

        if (!empty($matches['variant'])) {
            $this->variant = $matches['variant'];
        }

        if (!empty($matches['extendedLanguage'])) {
            $this->extendedLanguage = $matches['extendedLanguage'];
        }

        if (!empty($matches['extension'])) {
            $this->extension = $matches['extension'];
        }

        if (!empty($matches['script'])) {
            $this->script = ucfirst(strtolower($matches['script']));
        }

        if (!empty($matches['grandfathered'])) {
            $this->grandfathered = $matches['grandfathered'];
        }

        if (!empty($matches['private'])) {
            $this->private = preg_replace('~^x-~', '', $matches['private']);
        }

        if (!empty($matches['keywords'])) {
            foreach (explode(';', $matches['keywords']) as $pair) {
                [$key, $value] = explode('=', $pair);

                if ($key === 'calendar') {
                    $this->calendar = $value;
                }

                if ($key === 'colcasefirst') {
                    $this->colcasefirst = $value;
                }

                if ($key === 'collation') {
                    $this->collation = $value;
                }

                if ($key === 'colnumeric') {
                    $this->colnumeric = $value;
                }

                if ($key === 'currency') {
                    $this->currency = $value;
                }

                if ($key === 'numbers') {
                    $this->numbers = $value;
                }

                if ($key === 'hours') {
                    $this->hours = $value;
                }
            }
        }
    }

    /**
     * @return string Four-letter ISO 15924 script code.
     *
     * @link http://www.unicode.org/iso15924/iso15924-codes.html
     */
    public function script(): ?string
    {
        return $this->script;
    }

    /**
     * @param string|null $script Four-letter ISO 15924 script code.
     *
     * @link http://www.unicode.org/iso15924/iso15924-codes.html
     */
    public function withScript(?string $script): self
    {
        $new = clone $this;
        $new->script = $script;
        return $new;
    }

    /**
     * @return string Variant of language conventions to use.
     */
    public function variant(): ?string
    {
        return $this->variant;
    }

    /**
     * @param string|null $variant Variant of language conventions to use.
     */
    public function withVariant(?string $variant): self
    {
        $new = clone $this;
        $new->variant = $variant;
        return $new;
    }

    /**
     * @return string|null Two-letter ISO-639-2 language code.
     *
     * @link http://www.loc.gov/standards/iso639-2/
     */
    public function language(): ?string
    {
        return $this->language;
    }

    /**
     * @param string|null $language Two-letter ISO-639-2 language code.
     *
     * @link http://www.loc.gov/standards/iso639-2/
     */
    public function withLanguage(?string $language): self
    {
        $new = clone $this;
        $new->language = $language;
        return $new;
    }

    /**
     * @return string|null ICU calendar.
     */
    public function calendar(): ?string
    {
        return $this->calendar;
    }

    /**
     * @param string|null $calendar ICU calendar.
     */
    public function withCalendar(?string $calendar): self
    {
        $new = clone $this;
        $new->calendar = $calendar;
        return $new;
    }

    /**
     * @return string|null ICU case-first collation.
     *
     * @link https://unicode-org.github.io/icu/userguide/collation/customization/#casefirst
     * @link https://www.unicode.org/reports/tr35/tr35-61/tr35-collation.html#Collation_Settings
     */
    public function colcasefirst(): ?string
    {
        return $this->colcasefirst;
    }

    /**
     * @param string|null $colcasefirst ICU case-first collation.
     *
     * @link https://unicode-org.github.io/icu/userguide/collation/customization/#casefirst
     * @link https://www.unicode.org/reports/tr35/tr35-61/tr35-collation.html#Collation_Settings
     */
    public function withColcasefirst(?string $colcasefirst): self
    {
        $new = clone $this;
        $new->colcasefirst = $colcasefirst;
        return $new;
    }

    /**
     * @return string|null ICU collation.
     */
    public function collation(): ?string
    {
        return $this->collation;
    }

    /**
     * @param string|null $collation ICU collation.
     */
    public function withCollation(?string $collation): self
    {
        $new = clone $this;
        $new->collation = $collation;
        return $new;
    }

    /**
     * @return string|null ICU numeric collation.
     *
     * @link https://unicode-org.github.io/icu/userguide/collation/customization/#numericordering
     * @link https://www.unicode.org/reports/tr35/tr35-61/tr35-collation.html#Collation_Settings
     */
    public function colnumeric(): ?string
    {
        return $this->colnumeric;
    }

    /**
     * @param string|null $colnumeric ICU numeric collation.
     *
     * @link https://unicode-org.github.io/icu/userguide/collation/customization/#numericordering
     * @link https://www.unicode.org/reports/tr35/tr35-61/tr35-collation.html#Collation_Settings
     */
    public function withColnumeric(?string $colnumeric): self
    {
        $new = clone $this;
        $new->colnumeric = $colnumeric;
        return $new;
    }

    /**
     * @return string|null ICU numbers.
     */
    public function numbers(): ?string
    {
        return $this->numbers;
    }

    /**
     * @param string|null $numbers ICU numbers.
     */
    public function withNumbers(?string $numbers): self
    {
        $new = clone $this;
        $new->numbers = $numbers;
        return $new;
    }

    /**
     * @return string|null Unicode hour cycle identifier.
     *
     * @link https://www.unicode.org/reports/tr35/#UnicodeHourCycleIdentifier
     */
    public function hours(): ?string
    {
        return $this->hours;
    }

    /**
     * @param string|null $hours Unicode hour cycle identifier.
     *
     * @link https://www.unicode.org/reports/tr35/#UnicodeHourCycleIdentifier
     */
    public function withHours(?string $hours): self
    {
        $new = clone $this;
        $new->hours = $hours;
        return $new;
    }

    /**
     * @return string Two-letter ISO 3166-1 country code.
     *
     * @link https://www.iso.org/iso-3166-country-codes.html
     */
    public function region(): ?string
    {
        return $this->region;
    }

    /**
     * @param string|null $region Two-letter ISO 3166-1 country code.
     *
     * @link https://www.iso.org/iso-3166-country-codes.html
     */
    public function withRegion(?string $region): self
    {
        $new = clone $this;
        $new->region = $region;
        return $new;
    }

    /**
     * @return string ICU currency.
     */
    public function currency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string|null $currency ICU currency.
     */
    public function withCurrency(?string $currency): self
    {
        $new = clone $this;
        $new->currency = $currency;

        return $new;
    }

    /**
     * @return string|null Extended language subtags.
     */
    public function extendedLanguage(): ?string
    {
        return $this->extendedLanguage;
    }

    /**
     * @param string|null $extendedLanguage Extended language subtags.
     */
    public function withExtendedLanguage(?string $extendedLanguage): self
    {
        $new = clone $this;
        $new->extendedLanguage = $extendedLanguage;

        return $new;
    }

    public function private(): ?string
    {
        return $this->private;
    }

    public function withPrivate(?string $private): self
    {
        $new = clone $this;
        $new->private = $private;

        return $new;
    }

    /**
     * @return string Regular expression for parsing BCP 47.
     *
     * @link https://tools.ietf.org/html/bcp47
     */
    private static function getBCP47Regex(): string
    {
        $regular = '(?:art-lojban|cel-gaulish|no-bok|no-nyn|zh-guoyu|zh-hakka|zh-min|zh-min-nan|zh-xiang)';
        $irregular = '(?:en-GB-oed|i-ami|i-bnn|i-default|i-enochian|i-hak|i-klingon|i-lux|i-mingo|i-navajo|i-pwn|i-tao|i-tay|i-tsu|sgn-BE-FR|sgn-BE-NL|sgn-CH-DE)';
        $grandfathered = '(?<grandfathered>' . $irregular . '|' . $regular . ')';
        $private = '(?<private>x(?:-[A-Za-z0-9]{1,8})+)';
        $singleton = '[0-9A-WY-Za-wy-z]';
        $extension = '(?<extension>' . $singleton . '(?:-[A-Za-z0-9]{2,8})+)';
        $variant = '(?<variant>[A-Za-z0-9]{5,8}|[0-9][A-Za-z0-9]{3})';
        $region = '(?<region>[A-Za-z]{2}|[0-9]{3})';
        $script = '(?<script>[A-Za-z]{4})';
        $extendedLanguage = '(?<extendedLanguage>[A-Za-z]{3}(?:-[A-Za-z]{3}){0,2})';
        $language = '(?<language>[A-Za-z]{4,8})|(?<language>[A-Za-z]{2,3})(?:-' . $extendedLanguage . ')?';
        $icuKeywords = '(?:@(?<keywords>.*?))?';
        $languageTag = '(?:' . $language . '(?:-' . $script . ')?' . '(?:-' . $region . ')?' . '(?:-' . $variant . ')*' . '(?:-' . $extension . ')*' . '(?:-' . $private . ')?' . ')';
        return '/^(?J:' . $grandfathered . '|' . $languageTag . '|' . $private . ')' . $icuKeywords . '$/';
    }

    public function __toString(): string
    {
        return $this->asString();
    }

    /**
     * @return string Locale string.
     */
    public function asString(): string
    {
        if ($this->grandfathered !== null) {
            return $this->grandfathered;
        }

        $result = [];
        if ($this->language !== null) {
            $result[] = $this->language;

            if ($this->extendedLanguage !== null) {
                $result[] = $this->extendedLanguage;
            }

            if ($this->script !== null) {
                $result[] = $this->script;
            }

            if ($this->region !== null) {
                $result[] = $this->region;
            }

            if ($this->variant !== null) {
                $result[] = $this->variant;
            }

            if ($this->extension !== null) {
                $result[] = $this->extension;
            }
        }

        if ($this->private !== null) {
            $result[] = 'x-' . $this->private;
        }

        $keywords = [];
        if ($this->currency !== null) {
            $keywords[] = 'currency=' . $this->currency;
        }
        if ($this->colcasefirst !== null) {
            $keywords[] = 'colcasefirst=' . $this->colcasefirst;
        }
        if ($this->collation !== null) {
            $keywords[] = 'collation=' . $this->collation;
        }
        if ($this->colnumeric !== null) {
            $keywords[] = 'colnumeric=' . $this->colnumeric;
        }
        if ($this->calendar !== null) {
            $keywords[] = 'calendar=' . $this->calendar;
        }
        if ($this->numbers !== null) {
            $keywords[] = 'numbers=' . $this->numbers;
        }
        if ($this->hours !== null) {
            $keywords[] = 'hours=' . $this->hours;
        }

        $string = implode('-', $result);

        if ($keywords !== []) {
            $string .= '@' . implode(';', $keywords);
        }

        return $string;
    }

    /**
     * Returns fallback locale.
     *
     * @return self Fallback locale.
     */
    public function fallbackLocale(): self
    {
        $fallback = $this
            ->withCalendar(null)
            ->withColcasefirst(null)
            ->withCollation(null)
            ->withColnumeric(null)
            ->withCurrency(null)
            ->withExtendedLanguage(null)
            ->withNumbers(null)
            ->withHours(null)
            ->withPrivate(null);

        if ($fallback->variant() !== null) {
            return $fallback->withVariant(null);
        }

        if ($fallback->region() !== null) {
            return $fallback->withRegion(null);
        }

        if ($fallback->script() !== null) {
            return $fallback->withScript(null);
        }

        return $fallback;
    }
}
