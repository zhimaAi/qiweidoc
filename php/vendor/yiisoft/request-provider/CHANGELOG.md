# Yii Request Provider Change Log

## 1.2.0 January 08, 2025

- New #12: Add `RequestHeaderProvider` class that provides convenient access to request headers (@uzdevid)
- Chg #18: Add `RequestCookieProvider` instead of `RequestCookies`, which is marked as deprecated (@vjik)
- Enh #17: Get request from provider into `RequestCookies` every time `get()` and `has()` methods are called (@vjik)

## 1.1.0 October 28, 2024

- New #11: Add `RequestCookies` class that provides convenient access to request cookies (@hacan359)
- Enh #10: Add `yiisoft/middleware-dispatcher` event listener to keep `Request` always available (@xepozz)

## 1.0.0 March 02, 2024

- Initial release.
