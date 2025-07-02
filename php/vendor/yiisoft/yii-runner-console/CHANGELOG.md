# Yii Runner Console Change Log

## 2.2.0 March 10, 2024

- New #50: Add ability to set custom config merge plan file path, config and vendor directories (@vjik)

## 2.1.1 December 26, 2023

- Enh #44: Add support for `symfony/console` of version `^7.0` (@vjik)

## 2.1.0 December 25, 2023

- New #43: Add ability to set custom config modifiers (@vjik)

## 2.0.0 February 19, 2023

- New #31, #32, #33, #34: Add ability to configure all config group names (@vjik)
- New #31, #32: In the `ConsoleApplicationRunner` constructor make parameter "environment" optional,
  default `null` (@vjik)
- New #34: Add parameter `$checkEvents` to `ConsoleApplicationRunner` constructor (@vjik)
- New #34: In the `ConsoleApplicationRunner` constructor make parameter "debug" optional, default `false` (@vjik)
- Chg #30, 34: Raise required version of `yiisoft/yii-console` to `^2.0` and `yiisoft/yii-runner` to `^2.0` (@vjik)

## 1.1.1 November 10, 2022

- Enh #24: Add support for `yiisoft/definitions` version `^3.0` (@vjik)
- Bug #23: Add `symfony/console` dependency (@vjik)

## 1.1.0 July 29, 2022

- Chg: #21: Add passing input aggregate to the console application (@xepozz)

## 1.0.1 June 17, 2022

- Enh #19: Add support for `yiisoft/definitions` version `^2.0` (@vjik)

## 1.0.0 January 17, 2022

- Initial release.
