# Yii Aliases Change Log

## 3.0.0 February 13, 2023

- New #44: Add method `Aliases::getArray()` that bulk translates path aliases into actual paths (@vjik)
- Chg #59: Adapt configuration group names to Yii conventions (@vjik)

## 2.0.0 April 13, 2021

- Chg #40: Remove magic `__set()` that was leading to weird side-effects (@samdark)

## 1.1.4 April 13, 2021

- Chg: Adjust config for yiisoft/factory changes (@vjik, @samdark)

## 1.1.3 March 23, 2021

- Chg: Adjust config for new config plugin (@samdark)

## 1.1.2 December 24, 2020

- Bug #35: Don't throw TypeError from method `getAll()`, when alias is nested (@Fantom409)

## 1.1.1 November 06, 2020

- Bug #28: Add `params` to `config-plugin` section in `composer.json` (@vjik)

## 1.1.0 November 4, 2020

- Chg #26: Change Yii configuration `$params['aliases']` to `$params['yiisoft/aliases']['aliases']` (@vjik)

## 1.0.3 October 6, 2020

- Enh: Don't error on config assembly in case aliases aren't set (@samdark)

## 1.0.2 October 5, 2020

- Enh: Add a config for composer-config-plugin (@xepozz)

## 1.0.1 August 21, 2020

- Enh: Allow installing on PHP 8 (@samdark)

## 1.0.0 June 7, 2020

- Initial release.
