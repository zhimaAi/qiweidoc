# Yii Cache Library - File Handler Change Log

## 3.2.0 January 13, 2025

- Enh #88: Don't create cache directory on `FileCache` initialization (@vjik)
- Bug #85: Clear stat cache in `FileCache::set()` (@samdark)
- Bug #86: Handle race condition when creating cache directory (@vjik)
- Bug #88: Set correct permissions for nested directories (@vjik)

## 3.1.0 October 09, 2023

- Chg #69: Add optional parameter `$directoryMode` to `FileCache` constructor and deprecate `withDirectoryMode()`
  method (@particleflux)
- Enh #70: Minor refactoring with PHP 8 features usage (@vjik)

## 3.0.0 February 15, 2023

- Chg #50: Do not throw exception on file delete when file not found (fix for high concurrency load) (@sartor)
- Chg #52: Do not throw exception on change mode and modification time of cache file (fix for high
  concurrency load) (@sartor)
- Chg #56: Adapt configuration group names to Yii conventions (@vjik)

## 2.0.1 September 18, 2022

- Bug #47: Set permissions for new directory via `chmod()` (@dehbka)

## 2.0.0 July 21, 2022

- Chg #44: Raise the minimum `psr/simple-cache` version to `^2.0|^3.0` and the minimum PHP version to `^8.0` (@dehbka)

## 1.0.1 March 23, 2021

- Chg: Adjust config for new config plugin (@samdark)

## 1.0.0 February 02, 2021

- Initial release.
