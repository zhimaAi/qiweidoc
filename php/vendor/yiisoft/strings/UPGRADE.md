# Upgrading Instructions for Yii Strings

This file contains the upgrade notes. These notes highlight changes that could break your
application when you upgrade the package from one version to another.

> **Important!** The following upgrading instructions are cumulative. That is, if you want
> to upgrade from version A to version C and there is version B between A and C, you need
> to following the instructions for both A and B.

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
