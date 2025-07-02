# Yii Strings Change Log

## 2.6.0 February 09, 2025

- Chg #140: Bump minimal required PHP version to 8.1 and minor refactoring (@vjik)
- Chg #143: Change PHP constraint in `composer.json` to `~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0` (@vjik)
- Bug #138: Explicitly mark nullable parameters (@ferrumfist)
- Bug #141: `StringHelper::parsePath()` for empty string returns path `['']` instead of `[]` before (@vjik)
- Bug #142: Check string on a valid UTF-8 in `StringHelper` methods: `trim()`, `ltrim()` and `rtrim()` (@vjik)

## 2.5.0 January 19, 2025

- New #137: Add `StringHelper::matchAnyRegex()` method as a facade for `CombinedRegexp` (@vjik)
- Enh #128: Add more specific psalm type for result of `StringHelper::base64UrlEncode()` method (@vjik)

## 2.4.0 December 22, 2023

- New #118: Add `findBetween()`, `findBetweenFirst()` and `findBetweenLast()` methods to `StringHelper` to retrieve
  a substring that lies between two strings (@salehhashemi1992)
- Enh #121: Don't use regexp if there is no delimeter in the path in `StringHelper::parsePath()` (@viktorprogger)

## 2.3.1 October 30, 2023

- Enh #117: `WildcardPatters` uses memoization and accelerates ~2 times on repeated calls (@viktorprogger)

## 2.3.0 October 23, 2023

- Enh #114: Add stringable object support to `NumericHelper::normalize()` (@vjik)

## 2.2.0 September 20, 2023

- New #102, #106: Add `CombinedRegexp` class (@xepozz, @vjik)
- New #103: Add `MemoizedCombinedRegexp` decorator that caches results of `CombinedRegexp` (@xepozz)
- New #104: Add methods `StringHelper::trim()`, `StringHelper::ltrim()`, `StringHelper::rtrim()` (@olegbaturin)
- Enh #103: Raise required PHP version to `^8.0` (@xepozz)
- Enh #106: Using fully-qualified function calls to improve performance (@vjik)
- Enh #111: Minor refactoring (@Tigrov)
- Enh #83: Make minor refactoring with Rector help (@vjik)
- Enh #92: Add `$strict` parameter to `Inflector::toSnakeCase()` method (@arogachev)

## 2.1.2 July 27, 2023

- Bug #105: Fix incorrect split UTF-8 strings in `StringHelper::split()` method (@vjik)

## 2.1.1 April 28, 2023

- Enh #85: Improve `StringHelper::parsePath()` method annotation (@vjik)

## 2.1.0 August 20, 2022

- New #75: Add method `Inflector::toSnakeCase()` that convert word to "snake_case" (@soodssr)
- New #81: Add `StringHelper::parsePath()` method (@arogachev, @vjik)

## 2.0.0 February 10, 2021

- Chg #67: Remove `\Yiisoft\Strings\WildcardPattern::withoutEscape()` (@samdark)
- Chg #67: Remove `\Yiisoft\Strings\WildcardPattern::withExactLeadingPeriod()` (@samdark)
- Enh #67: Add `**`, match anything including `/`, to `\Yiisoft\Strings\WildcardPattern`, remove `withExactSlashes()` and `withEnding()` (@samdark)
- Enh #67: Allow specifying delimiters for `*` (@samdark)
- Enh #67: Add `\Yiisoft\Strings\WildcardPattern::isDynamic()` (@samdark)
- Enh #67: Add `\Yiisoft\Strings\WildcardPattern::quote()` (@samdark)

## 1.2.0 January 22, 2021

- Enh #62: Add method `StringHelper::split()` that split a string to array with non-empty lines (@vjik)
- Enh #63: Add method `NumericHelper::isInteger()` that checks whether the given string is an integer number (@vjik)
- Enh #64: Add support of a boolean values to `NumericHelper::normalize()` (@vjik)

## 1.1.0 November 13, 2020

- Enh #52: Allow turning off options in `WildcardPattern` (@vjik)
- Enh #51: Add an option `withEnding()` to `WildcardPattern` for match ending of testing string (@vjik)
- Bug #44: `NumericHelper::toOrdinal()` throws an error for numbers with fractional part (@vjik)

## 1.0.1 October 12, 2020

- Enh #40: Use `str_starts_with()` and `str_ends_with()` if available (@viktorprogger)
- Bug #43: `NumericHelper::normalize()` throws an error for `float` or `int` values in PHP 8 (@vjik)

## 1.0.0 August 31, 2020

- Initial release.
