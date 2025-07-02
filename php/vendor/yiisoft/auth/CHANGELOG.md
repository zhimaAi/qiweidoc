# Yii Auth Change Log

## 3.1.1 May 06, 2024

- Enh #80: Add support for `psr/http-message` version `^2.0` (@vjik)

## 3.1.0 October 30, 2023

- New #66: Add debug collector for `yiisoft/yii-debug` (@xepozz)
- Chg #47: Update `yiisoft/http` dependency (@devanych)
- Chg #53, #76: Bump required PHP version to 8.0 (@rustamwin, @vjik)
- Enh #72: Add `Language` JetBrains attribute to `$pattern` property in `HttpHeader::withPattern()` that enables syntax
  highlighting for this property in PhpStorm (@vjik)
- Enh #74: Memoization for `WildcardPattern` in `Authentication` middleware (@viktorprogger)
- Bug #54: Add missed `psr/http-factory` dependency (@vjik)
- Bug #73, #75: Correctly processing non-ASCII paths in the `Authentication` middleware (@viktorprogger, @vjik)

## 3.0.1 February 10, 2021

- Chg: Update `yiisoft/strings` dependency (@samdark)

## 3.0.0 January 22, 2021

- Chg #39: `IdentityWithTokenRepositoryInterface` does not extend `IdentityRepositoryInterface` (@roxblnfk)

## 2.0.0 January 13, 2021

- Enh #36: Extract `IdentityRepositoryInterface::findIdentityByToken()` into `IdentityWithTokenRepositoryInterface`,
  make token type configurable (@armpogart, @roxblnfk)

## 1.0.2 September 1, 2020

- Use stable version of `yiisoft/http` (@samdark)

## 1.0.1 September 1, 2020

- Use stable version of `yiisoft/strings` (@samdark)

## 1.0.0 August 25, 2020

- Initial release.
