# Yii Hydrator Change Log

## 1.6.0 March 14, 2025

- New #63: Add nested mapping support via new `ObjectMap` class (@vjik)
- Chg #103: Change PHP constraint in `composer.json` to `8.1 - 8.4` (@vjik)
- Enh #99: Improve psalm annotation in `HydratorInterface::create()` method (@vjik)

## 1.5.0 September 17, 2024

- New #96: Add `EnumTypeCaster` (@vjik)
- Bug #95: Fix populating readonly properties from parent classes (@vjik)

## 1.4.0 August 23, 2024

- New #94: Add `ToArrayOfStrings` parameter attribute (@vjik)
- Enh #93: Add backed enumeration support to `Collection` (@vjik)

## 1.3.0 August 07, 2024

- New #49: Add `Collection` PHP attribute (@arogachev)
- New #49: Add hydrator dependency and `withHydrator()` method to `ParameterAttributesHandler` (@arogachev)
- New #49: Add hydrator dependency and `getHydrator()` method to `ParameterAttributeResolveContext` (@arogachev)
- Enh #85: Allow to hydrate non-initialized readonly properties (@vjik)

## 1.2.0 April 03, 2024

- New #77: Add `ToDateTime` parameter attribute (@vjik)
- New #79: Add `Trim`, `LeftTrim` and `RightTrim` parameter attributes (@vjik)
- Enh #76: Raise the minimum version of PHP to 8.1 (@vjik)

## 1.1.0 February 09, 2024

- New #74: Add `NullTypeCaster` (@vjik)

## 1.0.0 January 29, 2024

- Initial release.
