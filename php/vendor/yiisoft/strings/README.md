<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii Strings</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/strings/v)](https://packagist.org/packages/yiisoft/strings)
[![Total Downloads](https://poser.pugx.org/yiisoft/strings/downloads)](https://packagist.org/packages/yiisoft/strings)
[![Build status](https://github.com/yiisoft/strings/actions/workflows/build.yml/badge.svg)](https://github.com/yiisoft/strings/actions/workflows/build.yml)
[![Code coverage](https://codecov.io/gh/yiisoft/strings/graph/badge.svg?token=GEPMBAHNCX)](https://codecov.io/gh/yiisoft/strings)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fstrings%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/strings/master)
[![static analysis](https://github.com/yiisoft/strings/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/strings/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/strings/coverage.svg)](https://shepherd.dev/github/yiisoft/strings)

The package provides:

- `StringHelper` that has static methods to work with strings;
- `NumericHelper` that has static methods to work with numeric strings;
- `Inflector` provides methods such as `toPlural()` or `toSlug()` that derive a new string based on the string given;
- `WildcardPattern` is a shell wildcard pattern to match strings against;
- `CombinedRegexp` is a wrapper that optimizes multiple regular expressions matching and
  `MemoizedCombinedRegexp` is a decorator that caches results of `CombinedRegexp` to speed up matching.

## Requirements

- PHP 8.1 or higher.
- `mbstring` PHP extension.

## Installation

The package could be installed with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/strings
```

## StringHelper usage

String helper methods are static so usage is like the following:

```php
echo \Yiisoft\Strings\StringHelper::countWords('Strings are cool!'); // 3
```

Overall the helper has the following method groups.

### Bytes

- byteLength
- byteSubstring

### File paths

- baseName
- directoryName

### Substrings

- substring
- replaceSubstring
- startsWith
- startsWithIgnoringCase
- endsWith
- endsWithIgnoringCase
- findBetween
- findBetweenFirst
- findBetweenLast

### Truncation

- truncateBegin
- truncateMiddle
- truncateEnd
- truncateWords
- trim
- ltrim
- rtrim

### Counting

- length
- countWords

### Lowercase and uppercase

- lowercase
- uppercase
- uppercaseFirstCharacter
- uppercaseFirstCharacterInEachWord

### URL friendly base64

- base64UrlEncode
- base64UrlDecode

### Other

- matchAnyRegex
- parsePath
- split

## NumericHelper usage

Numeric helper methods are static so usage is like the following:

```php
echo \Yiisoft\Strings\NumericHelper::toOrdinal(3); // 3rd
```

The following methods are available:

- toOrdinal
- normalize
- isInteger

## Inflector usage

```php
echo (new \Yiisoft\Strings\Inflector())
    ->withoutIntl()
    ->toSlug('Strings are cool!'); // strings-are-cool
```

Overall the inflector has the following method groups.

### Plurals and singulars

- toPlural
- toSingular

### Transliteration

- toTransliterated

### Case conversion

- pascalCaseToId
- toPascalCase
- toCamelCase

### Words and sentences

- toSentence
- toWords
- toHumanReadable

### Classes and database tables

- classToTable
- tableToClass

### URLs

- toSlug

## WildcardPattern usage

`WildcardPattern` allows a simple POSIX-style string matching.

```php
use \Yiisoft\Strings\WildcardPattern;

$startsWithTest = new WildcardPattern('test*');
if ($startsWithTest->match('testIfThisIsTrue')) {
    echo 'It starts with "test"!';
}
```

The following characters are special in the pattern:

- `\` escapes other special characters if usage of escape character is not turned off.
- `*` matches any string, including the empty string, except delimiters (`/` and `\` by default).
- `**` matches any string, including the empty string and delimiters.
- `?` matches any single character.
- `[seq]` matches any character in seq.
- `[a-z]` matches any character from a to z.
- `[!seq]` matches any character not in seq.
- `[[:alnum:]]` matches [POSIX style character classes](https://www.php.net/manual/en/regexp.reference.character-classes.php).

`ignoreCase()` could be called before doing a `match()` to get a case-insensitive match:

```php
use \Yiisoft\Strings\WildcardPattern;

$startsWithTest = new WildcardPattern('test*');
if ($startsWithTest
    ->ignoreCase()
    ->match('tEStIfThisIsTrue')) {
    echo 'It starts with "test"!';
}
```

## CombinedRegexp usage

`CombinedRegexp` optimizes matching multiple regular expressions.

```php
use \Yiisoft\Strings\CombinedRegexp;

$patterns = [
    'first',
    'second',
    '^a\d$',
];
$regexp = new CombinedRegexp($patterns, 'i');
$regexp->matches('a5'); // true – matches the third pattern
$regexp->matches('A5'); // true – matches the third pattern because of `i` flag that is applied to all regular expressions
$regexp->getMatchingPattern('a5'); // '^a\d$' – the pattern that matched
$regexp->getMatchingPatternPosition('a5'); // 2 – the index of the pattern in the array
$regexp->getCompiledPattern(); // '~(?|first|second()|^a\d$()())~'
```

## MemoizedCombinedRegexp usage

`MemoizedCombinedRegexp` caches results of `CombinedRegexp` in memory.
It is useful when the same incoming string are matching multiple times or different methods of `CombinedRegexp` are called.

```php
use \Yiisoft\Strings\CombinedRegexp;
use \Yiisoft\Strings\MemoizedCombinedRegexp;

$patterns = [
    'first',
    'second',
    '^a\d$',
];
$regexp = new MemoizedCombinedRegexp(new CombinedRegexp($patterns, 'i'));
$regexp->matches('a5'); // Fires `preg_match` inside the `CombinedRegexp`.
$regexp->matches('first'); // Fires `preg_match` inside the `CombinedRegexp`.
$regexp->matches('a5'); // Does not fire `preg_match` inside the `CombinedRegexp` because the result is cached.
$regexp->getMatchingPattern('a5'); // The result is cached so no `preg_match` is fired.
$regexp->getMatchingPatternPosition('a5'); // The result is cached so no `preg_match` is fired.

// The following code fires only once matching mechanism.
if ($regexp->matches('second')) {
    echo sprintf(
        'Matched the pattern "%s" which is on the position "%s" in the expressions list.',
        $regexp->getMatchingPattern('second'),
        $regexp->getMatchingPatternPosition('second'),
    );
}
```

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii Strings is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
