# Yii Config Change Log

## 1.6.0 February 05, 2025

- New #173: Allow to use option "config-plugin-file" in packages (@vjik)
- New #175: Add `yii-config-info` composer command (@vjik)
- Chg #175: Raise minimum Composer version to 2.3 (@vjik)
- Chg #187: Change PHP constraint in `composer.json` to `~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0` (@vjik)
- Enh #172, #173: Refactoring: extract config settings reader to separate class (@vjik)
- Enh #175: Minor refactoring of internal classes `Options` and `ProcessHelper` (@vjik)
- Enh #186: Raise the minimum PHP version to 8.1 and minor refactoring (@vjik)
- Bug #186: Explicitly mark nullable parameters (@vjik)

## 1.5.0 December 25, 2023

- New #155: Add ability to specify recursion depth for recursive modifier (@vjik)
- Enh #157: Remove unnecessary code in `PackagesListBuilder` (@vjik)
- Bug #153: Do not throw "Duplicate key…" exception when using nested groups (@vjik)
- Bug #163: References to another configs use reverse and recursive modifiers of root group now (@vjik)

## 1.4.0 November 17, 2023

- Enh #152: Add plugin option "package-types" that define package types for process, by default "library" and
  "composer-plugin" (@vjik)

## 1.3.1 November 17, 2023

- Bug #145: Use composer library and plugins only, instead of any packages before (@vjik)
- Bug #150: Empty configuration groups from packages were not added to merge plan (@vjik)

## 1.3.0 February 11, 2023

- Enh #131: Add ability to use `Config` without params (@vjik)

## 1.2.0 February 08, 2023

- Enh #119: Improve performance of collecting data for `ReverseMerge` and `RecursiveMerge` (@samdark)
- Enh #122: Raise minimal PHP version to 8.0 (@vjik, @xepozz)
- Enh #130: Add ability to change merge plan file path (@vjik)

## 1.1.1 January 05, 2022

- Enh #110: Improve the error message by displaying a name of the group where the error occurred when merging (@devanych)

## 1.1.0 December 31, 2021

- New #108: Add `Yiisoft\Config\ConfigInterface` to allow custom implementations of a config loader (@devanych)

## 1.0.0 December 17, 2021

- Initial release.
