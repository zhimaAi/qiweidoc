# Upgrading Instructions

These notes highlight changes that could break your application when you upgrade package from one version to another.

Upgrading in general is as simple as updating your dependency in your `composer.json` and running `composer update`.
In a big application however there may be more things to consider, which are explained in the following.

> Note: The following upgrading instructions are cumulative. That is, if you want to upgrade from a version A to version
> C and there is a version B between A and C, you need to follow the instructions for both A and B.

## Upgrade from 1.2.0

`\Yiisoft\Strings\WildCardPattern` was changed.

- `\Yiisoft\Strings\WildcardPattern::withExactSlashes()` was removed. `*` now always doesn't match `/`.
- `**` was introduced to match anything including `/`.
- `\Yiisoft\Strings\WildcardPattern::withExactLeadingPeriod()` was removed. There is no replacement for old behavior.
- `\Yiisoft\Strings\WildcardPattern::withEnding()` was removed.
- `\Yiisoft\Strings\WildcardPattern::withoutEscape()` was removed.  

To fix possible issues:

- Remove `withExactSlashes()` calls.
- Replace `*` with `**` in patterns if you need to match `/` as well.
- If `withEnding()` was used, add `**` to the beginning of the pattern.
- If `withoutEscape()` was used, escape `\` in patterns with another `\`.
  Likely `\Yiisoft\Strings\WildcardPattern::quote()` may be of help.
