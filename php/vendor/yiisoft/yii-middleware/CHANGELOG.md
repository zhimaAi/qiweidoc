# Yii Middleware Change Log

## 1.0.4 September 03, 2024

- Enh #121: Add `network-utilities` dependency and use it instead of `validator` for `IpFilter` (@arogachev)
- Enh #121: Add support for `validator` of version 2.0, mark it as deprecated (@arogachev)

## 1.0.3 June 06, 2024

- Enh #117: Add support for `psr/http-message` version `^2.0` (@bautrukevich)

## 1.0.2 October 06, 2023

- Enh #103: Add `Access-Control-Expose-Headers: *` to `CorsAllowAll` (@xepozz)
- Bug #105: Fire `SetLocaleEvent` and prepare URL generator in `Locale` before handle request (@vjik)
- Bug #112: Check ignored requests earlier and do not set default locale (@g-rodigy)

## 1.0.1 June 04, 2023

- Chg #95: Remove unused network utilities dependency (@arogachev)
- Bug #96: Fix unexpected redirects from `Locale` middleware on GET requests (@vjik)
- Bug #97: Don't search locale in cookies when `$cookieDuration` is null (@vjik)

## 1.0.0 May 22, 2023

- Initial release.
