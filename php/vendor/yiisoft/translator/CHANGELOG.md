# Yii Message Translator Change Log

## 3.1.0 December 26, 2024

- Enh #131: Throw `InvalidArgumentException` when missed "one" plural key (@vjik)
- Bug #132: Fix incorrect locale usage when category source is not exist and specified fallback locale (@vjik)
- Bug #148: Fix the nullable parameter declarations for compatibility with PHP 8.4 (@martio)

## 3.0.0 February 17, 2023

- Chg #92: Implement fluent interface into `TranslatorInterface`: methods `addCategorySources()`, `setLocale()`,
  `withDefaultCategory()` and `withLocale()` returns `static` (@vjik)
- Chg #111: Adapt configuration group names to Yii conventions (@vjik)

## 2.2.1 January 07, 2023

- Enh #99: In `SimpleMessageFormatter` add support of messages where used parameters with plural modifier that contain 
  non-supported keys (@vjik)
- Bug #110: Changed default value for locale to BCP-47 compatible (@darkdef)

## 2.2.0 November 28, 2022

- New #94: Add `getMessages()` method to `CategorySource` (@xepozz)
- New #95: Add `write()` method to `CategorySource` (@xepozz)

## 2.1.1 November 23, 2022

- Bug #93: Throw exceptions on empty and not passed parameters, cast `null` to empty string in `SimpleMessageFormatter`
  (@arogachev)

## 2.1.0 November 15, 2022

- New #91: Add `IdMessageReader` that returns ID as message and doesn't support getting all messages at once (@vjik)

## 2.0.0 November 08, 2022

- New #75: Add `NullMessageFormatter` that returns message as is (@vjik)
- New #78: Add `IntlMessageFormatter` that utilizes PHP intl extension message formatting capabilities (@vjik)
- New #82: Add parameter `$defaultCategory` to `Translator` constructor (@vjik)
- Chg #81: Make category parameter in `TranslatorInterface::addCategorySources()` variadic, and remove 
 `TranslatorInterface::addCategorySource()` method (@vjik)
- Chg #84: In `TranslatorInterface` rename method `withCategory()` to `withDefaultCategory()` (@vjik)
- Chg #87: Fix package configuration, remove default category source, change default locale to `en_US` (@vjik)
- Chg #90: Simplified category sources config (@rustamwin)
- Enh #69: Raise minimum PHP version to 8.0 (@xepozz, @vjik)
- Enh #70: Add `Stringable` type support for `$id` argument in `Translator::translate()` (@xepozz)
- Enh #72, #75: Format messages when missing translation category (@vjik)
- Enh #73: Set `en_US` as default locale for translator (@vjik)
- Enh #74: Dispatch `MissingTranslationCategoryEvent` once per category (@vjik)
- Enh #76: Make message formatter in category source optional (@vjik)

## 1.1.1 September 09, 2022

- Bug #67: Exclude number from "plural" formatted message, handle missing options' keys (@arogachev)

## 1.1.0 September 07, 2022

- Chg #25: Move `SimpleMessageFormatter` from `yiisoft/translator-formatter-simple` package (@DAGpro, @vjik)
- Enh #63: Add "plural" support to `SimpleMessageFormatter` (@arogachev)

## 1.0.2 July 26, 2022

- Enh #59: Add support for `yiisoft/files` of version `^2.0` (@vjik)

## 1.0.1 August 30, 2021

- Chg #54: Use definitions from `yiisoft/definitions` in configuration (@vjik)

## 1.0.0 May 13, 2021

- Initial release.
