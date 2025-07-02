# Yii Router Change Log

## 3.1.0 February 20, 2024

- New #203, #237: Add `RouteArgument` attribute for Yii Hydrator (@vjik)
- Enh #202: Add support for `psr/http-message` version `^2.0` (@vjik)

## 3.0.0 February 17, 2023

- Chg #187: Adapt configuration group names to Yii conventions (@vjik)

## 2.1.0 January 09, 2023

- Chg #185: Update `yiisoft/middleware-dispatcher` version to `^5.0` (@rustamwin)

## 2.0.0 November 12, 2022

- Chg #178: Move type hints from annotations to methods signature (@vjik)
- Enh #173: Raise minimum PHP version to 8.0 (@xepozz, @rustamwin)
- Enh #175: Add `$queryParameters` parameter to `UrlGeneratorInterface::generateFromCurrent()` method (@rustamwin)
- Enh #176: Add support for `yiisoft/middleware-dispatcher` version `^4.0` (@vjik)

## 1.2.0 September 07, 2022

- Chg #172: Upgrade the `yiisoft/middleware-dispatcher` dependency to version `3.0` (@rustamwin)

## 1.1.0 June 27, 2022

- Chg #167: Move `psr/container` dependency to dev requirements (@vjik)
- Chg #167: Add `psr/event-dispatcher` dependency (@vjik)
- Enh #163: Allow multiple separate hosts with new `Route::hosts()` method (@Gerych1984)
- Enh #168: Allow multiple separate hosts with new `Group::hosts()` method (@rustamwin)

## 1.0.0 December 30, 2021

- Initial release.
