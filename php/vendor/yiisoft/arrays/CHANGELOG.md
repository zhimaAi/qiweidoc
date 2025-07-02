# Yii Arrays Change Log

## 3.2.0 February 01, 2025

- Chg #167: Change PHP constraint in `composer.json` to `~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0` (@vjik)
- Enh #156: Improve psalm types in `ArrayHelper::getObjectVars()`, `ArrayableInterface`, `ArrayableTrait` and 
  `ArrayAccessTrait` (@vjik)
- Enh #165: Raise the minimum PHP version to 8.1 (@vjik)
- Bug #165: Explicitly mark nullable parameters (@vjik)
- Bug #166: `ArrayHelper::getValue()` returns default value on empty array key (@vjik)
- Bug #166: `ArrayHelper::keyExists()` returns false on empty array key (@vjik)

## 3.1.0 April 04, 2024

- New #139: Add `ArrayHelper::parametrizedMerge()` method that allows to merge two or more arrays recursively with
  specified depth (@vjik)
- New #149, #152: Add `ArrayrHelper::renameKey()` (@vjik, @Tigrov)
- Enh #140: Remove `null` from return type of `ArrayHelper::getObjectVars()` method (@Tigrov)
- Enh #152: Minor `ArrayableTrait` refactoring (@Tigrov)

## 3.0.0 January 12, 2023

- Enh #115: Raise required PHP version to `^8.0`, move union type hints from annotations
  to methods' signatures (@xepozz, @vjik)
- Enh #122: Add getters' support (keys like "getMyProperty()") to `ArrayHelper` (@vjik)
- Bug #103: `ArrayableTrait::toArray()` returned an invalid result when no fields were specified (@ganigeorgiev)
- New #137: Add methods `ArrayHelper::addValue()` and `ArrayHelper::addValueByPath()` (@Kutuzovska)

## 2.1.0 August 20, 2022

- Enh #111: Add support for escaping delimiter while parsing path (@arogachev, @vjik)

## 2.0.0 October 23, 2021

- New #91: Add `ArrayHelper::group()` that groups the array according to a specified key (@sagittaracc)
- New #96: Add support for iterable objects to `ArrayHelper::map()`, `ArrayHelper::index()`, `ArrayHelper::group()`,
  `ArrayHelper::htmlEncode()` and `ArrayHelper::htmlDecode` (@vjik)
- Chg #99: Finalize `ArrayHelper` and `ArraySorter` (@vjik)
- Bug #101: Fix incorrect default value returned from `ArrayHelper::getValue()` when key does not exist and
  default is array (@vjik)

## 1.0.1 February 10, 2021

- Chg: Update `yiisoft/strings` dependency (@samdark)

## 1.0.0 February 02, 2021

- Initial release
