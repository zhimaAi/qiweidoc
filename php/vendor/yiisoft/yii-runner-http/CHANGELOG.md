# Yii Runner HTTP Change Log

## 2.3.0 March 10, 2024

- New #69: Add ability to set custom config merge plan file path, config and vendor directories (@vjik)
- Enh #58: Support stream output headers (@xepozz)
- Enh #64: Don't use buffered output in `SapiEmitter` when body size is less than buffer (@Gerych1984, @vjik)
- Enh #65: Add support for `psr/http-message` version `^2.0` (@vjik)

## 2.2.0 December 25, 2023

- New #49: Add ability to set custom config modifiers (@vjik)

## 2.1.0 July 10, 2023

- Chg #53: Add `RequestFactory` as a refactoring of `ServerRequestFactory`, mark `ServerRequestFactory` as deprecated (@vjik)
- Enh #50: Support stream output (@xepozz)

## 2.0.0 February 19, 2023

- New #40: In the `HttpApplicationRunner` constructor make parameter "environment" optional, default `null` (@vjik)
- New #42, #43: Add ability to configure all config group names (@vjik)
- New #43: Add parameter `$checkEvents` to `HttpApplicationRunner` constructor (@vjik)
- New #43: In the `HttpApplicationRunner` constructor make parameter "debug" optional, default `false` (@vjik)
- Chg #39, #43: Raise required version of `yiisoft/yii-runner` to `^2.0`, `yiisoft/log-target-file` to `^3.0`
  and `yiisoft/error-handler` to `^3.0` (@vjik)

## 1.1.2 November 10, 2022

- Enh #33: Add support for `yiisoft/definitions` version `^3.0` (@vjik)
- Bug #32: Add `psr/http-factory` and `psr/http-message` dependencies (@xepozz)

## 1.1.1 July 21, 2022

- Enh #27: Add support for `yiisoft/log-target-file` of version `^2.0` (@vjik)

## 1.1.0 June 17, 2022

- Chg #22: Raise packages version:`yiisoft/log` to `^2.0`, `yiisoft/log-target-file` to `^1.1` and 
  `yiisoft/error-handler` to `^2.1` (@rustamwin)

## 1.0.1 June 17, 2022

- Enh #23: Add support for `yiisoft/definitions` version `^2.0` (@vjik)

## 1.0.0 January 17, 2022

- Initial release.
