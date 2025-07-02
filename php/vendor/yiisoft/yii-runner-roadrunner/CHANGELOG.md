# Yii Runner RoadRunner Change Log

## 3.0.1 April 23, 2024

- Enh #82: Allow to use RoadRunner version 2024 and higher (@dependabot, @viktorprogger)

## 3.0.0 February 22, 2024

- Chg #23: Rename `RoadRunnerApplicationRunner` to `RoadRunnerHttpApplicationRunner` (@s1lver)
- Chg #61: Increased minimum PHP version to 8.1 (@s1lver)
- Enh #67, #76: Added runner for gRPC requests (@s1lver)
- Enh #74: Add support for `psr/http-message` of `^2.0` version (@vjik)

## 2.0.0 February 19, 2023

- Chg #49, 52: Raise required version of `yiisoft/error-handler` to `^3.0`, `yiisoft/log-target-file` to `^3.0`
  and `yiisoft/yii-runner` to `^2.0` 
- Enh #52: Add ability to configure all config group names (@vjik)
- Enh #52: Add parameter `$checkEvents` to `RoadRunnerApplicationRunner` constructor (@vjik)
- Enh #52: In the `RoadRunnerApplicationRunner` constructor make parameter "debug" optional, default `false` (@vjik)
- Enh #52: In the `RoadRunnerApplicationRunner` constructor make parameter "environment" optional,
  default `null` (@vjik)

## 1.1.2 November 20, 2022

- Chg #38: Add support for `yiisoft/definitions` `^3.0` (@samdark)
- Enh #35: Explicitly add transitive dependencies (@xepozz)

## 1.1.1 September 04, 2022

- Chg #31: Update the version of the `yiisoft/log-target-file` package to `^1.1|^2.0` in the `require` section of 
  `composer.json` (@razonyang)

## 1.1.0 June 17, 2022

- Chg #26: Raise packages version:`yiisoft/log` to `^2.0`, `yiisoft/log-target-file` to `^1.1` and
  `yiisoft/error-handler` to `^2.1` (@rustamwin)

## 1.0.1 June 17, 2022

- Enh #27: Add support for `yiisoft/definitions` version `^2.0` (@vjik)

## 1.0.0 January 26, 2022

- Initial release.
