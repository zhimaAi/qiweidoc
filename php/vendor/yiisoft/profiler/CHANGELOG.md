# Yii Profiler Change Log

## 3.0.0 February 15, 2023

- Chg #64: Adapt configuration group names to Yii conventions (@vjik)

## 2.0.0 September 03, 2022

- Chg #55: Raise the minimum version of PHP to 8 (@rustamwin)

## 1.0.5 August 28, 2022

- Enh #54: Implement `ProfilerAwareTrait` for `ProfilerAwareInterface` (@terabytesoftw)
- Bug #52: Fix definition resetter config (@alamagus)

## 1.0.4 July 26, 2022

- Chg #40: Add support for `yiisoft/files` of version `^2.0` (@DplusG)

## 1.0.3 May 23, 2022

- Bug #44: Add support for `psr/log` in versions 2 and 3 (PHP 8) (@tomaszkane)

## 1.0.2 January 17, 2022

- Bug #42: Forbid passing context with "beginMemory" or "beginTime" keys to `Profiler::end()` (@vjik)
- Bug #42: Check that category of a message is a string (@vjik)

## 1.0.1 November 12, 2021

- Chg #41: Replace usage of `yiisoft/yii-web` to `yiisoft/yii-http` in event config (@devanych)

## 1.0.0 May 04, 2021

- Initial release.
