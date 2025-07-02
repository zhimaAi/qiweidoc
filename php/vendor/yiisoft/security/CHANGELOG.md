# Yii Security Change Log

## 1.1.0 February 26, 2025

- Chg #66: Bump minimal required PHP version to 8.1 (@vjik)
- Chg #67: Change PHP constraint in `composer.json` to `8.1 - 8.4` (@vjik)
- Enh #62: Use `SensitiveParameter` attribute to mark sensitive parameters (@dehbka, @vjik)
- Enh #66: Mark readonly properties (@vjik)
- Bug #67: Explicitly mark nullable parameters (@vjik)
 
## 1.0.2 March 18, 2024

- Enh #53: Add more specific psalm type for result of `Random::string()` method (@vjik)
- Bug #35: Add missed `ext-hash` and `ext-openssl` dependencies (@vjik)

## 1.0.1 February 10, 2021

- Chg: Update `yiisoft/strings` dependency (@samdark)

## 1.0.0 November 1, 2020

- Initial release.
