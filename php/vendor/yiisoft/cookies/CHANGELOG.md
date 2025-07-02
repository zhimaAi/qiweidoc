# Yii Cookies Change Log

## 1.2.2 April 05, 2024

- Enh #52: Add support for `^2.0` version of `psr/http-message` (@vjik)

## 1.2.1 June 15, 2022

- Chg #31: Update `yiisoft/http` dependency (@devanych)
- Enh #35: Add support for `2.0`, `3.0` versions of `psr/log` (@rustamwin)

## 1.2.0 May 23, 2021

- Add #27: Add the parameter `$encodeValue` to the `Cookie` constructor and the `Cookie::withRawValue()` method 
  that creates a cookie copy with a new value that will not be encoded (@vjik)


## 1.1.0 May 05, 2021

- Add #19: Add the `Yiisoft\Cookies\CookieEncryptor` class to encrypt the value of the cookie and verify that it is tampered (@devanych)
- Add #19: Add the `Yiisoft\Cookies\CookieSigner` class to sign the value of the cookie and verify that it is tampered (@devanych)
- Add #22: Add the `Yiisoft\Cookies\CookieMiddleware` class to encrypt/sign the value of the cookie and verify that it is tampered (@devanych)

## 1.0.0 December 02, 2020

- Initial release.
