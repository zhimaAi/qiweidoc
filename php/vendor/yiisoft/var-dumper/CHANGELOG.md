# Yii VarDumper

## 1.7.0 October 08, 2023

- New #94: Add `dump` function (@xepozz)
- New #95: Add `StreamHandler` and `CompositeHandler` (@xepozz)
- Enh #94: Add a middleware handler to control dumps' output destination (@xepozz)

## 1.6.0 June 30, 2023

- Enh #88: Remove leading spaces in closure dumps (@xepozz)
- Enh #89: Add individual dumps for `DateTime` and `DateTimeImmutable` objects (@xepozz)

## 1.5.0 January 16, 2023

- Chg #81: Allow using yiisoft/arrays ^3.0 (@samdark)
- Enh #76: Add a variable type to output when it can't be exported more (@xepozz)

## 1.4.0 October 28, 2022

- Enh #74: Add support for integer property names (@xepozz)
- Enh #75: Add method `asPrimitives` that exports a variable like destructed json (@xepozz)

## 1.3.0 September 22, 2022

- New #64, #68: Add `VarDumper::asJson()` method that provide exporting value as JSON string (@dood-, @xepozz)

## 1.2.0 October 24, 2021

- Enh #59: Add `\Yiisoft\VarDumper\VarDumper::withOffset()` to adjust generated code nesting offset (@WinterSilence)
- Enh #60: Add support for `yiisoft/arrays` version `^2.0` (@vjik)

## 1.1.0 April 08, 2021

- Enh #54: Add support for exporting objects with closures (@yiiliveext)

## 1.0.5 March 17, 2021

- Bug #53: Fix `\Yiisoft\VarDumper\ClosureExporter::export()` method fail when exporting a static method call (@devanych)

## 1.0.4 March 15, 2021

- Bug #51: Add a line break in the `d()` and `dd()` functions for highlight mode (@devanych)

## 1.0.3 February 16, 2021

- Bug #50: Fix the issue of exporting closures with use long namespace under PHP 7.4 (@devanych)

## 1.0.2 February 15, 2021

- Bug #49: Fix the issue of exporting closures when using PHP 8 (@devanych)

## 1.0.1 February 11, 2021

- Bug #48: Remove the unused `objects` property from the `\Yiisoft\VarDumper\VarDumper` class. (@devanych)

## 1.0.0 February 10, 2021

Initial release.
