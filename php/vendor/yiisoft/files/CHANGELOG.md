# Yii Files Change Log

## 2.0.0 July 05, 2022

- New #59: Add `beforeCopy`, `afterCopy` callbacks for `FileHelper::copyFile()` and `FileHelper::copyDirectory()` (@Gerych1984)
- Chg #64: Raise the minimum PHP version to 8.0 (@Gerych1984)
- Chg #64: Allow `RecursiveDirectoryIterator` as argument in `FileHelper::lastModifiedTime()` (@Gerych1984)
- Bug #58: Add missed return value and type for callback of `set_error_handler()` function (@vjik)

## 1.0.2 January 11, 2022

- Bug #57: Fix return type for callback of `set_error_handler()` function (@devanych)

## 1.0.1 December 18, 2021

- Bug #55: Directory separator was not normalized in patterns (@darkdef)

## 1.0.0 February 10, 2021

- Initial release.
