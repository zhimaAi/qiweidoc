# Yii Definitions Change Log

## 3.4.0 March 02, 2025

- Chg #105: Change PHP constraint in `composer.json` to `~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0` (@vjik)
- Chg #106: Bump minimal required PHP version to 8.1 (@vjik)
- Enh #105: Improve definition validation for readonly properties and properties with asymmetric visibility (@vjik)
- Enh #106: Minor performance optimization: use FQN for PHP functions, remove unnecessary conditions (@vjik)
- Enh #106: Mark readonly properties (@vjik)
- Bug #105: Explicitly mark nullable parameters (@vjik)

## 3.3.1 December 16, 2024

- Enh #101: Minor performance improvement by removing unnecessary code in `DefinitionStorage` (@vjik)
- Bug #100: Fix resolving `ParameterDefinition` for optional parameters with union types (@vjik)

## 3.3.0 March 16, 2024

- New #90: Allow multiple method call in array definition (@vjik)
- Bug #86: Fix crash when intersection types are used (@vjik)

## 3.2.0 February 12, 2023

- Enh #68: Add ability to use any allowed definitions in `DynamicReferencesArray::from()` (@vjik)

## 3.1.0 February 04, 2023

- New #67: Add `ArrayDefinitionHelper::merge()` method that merge array definitions (@vjik)

## 3.0.2 December 02, 2022

- Enh #57: Improve validation messages (@xepozz)

## 3.0.1 November 08, 2022

- Bug #53: Fixed error on use in array definition methods that should be work via magic `__call()` method (@vjik)

## 3.0.0 November 04, 2022

- Chg #49: Change result format of `DefinitionStorage::getBuildStack()` method to definition IDs array (@vjik)
- Enh #41: Raise minimum PHP version to 8.0 and refactor code (@xepozz, @vjik)
- Enh #44: In methods of array definitions add autowiring and improve variadic arguments support (@vjik)
- Enh #46: In definition validator add a check of method name in array definitions (@vjik)
- Bug #48: Definition validator returns false positive result on empty string (@vjik)

## 2.1.0 October 25, 2022

- Enh #43: Add `Reference::optional()` method that returns `null` when there is no dependency defined
  in container (@vjik)

## 2.0.0 June 17, 2022

- New #37: Make method `DefinitionValidator::validateArrayDefinition()` public (@vjik)
- Chg #30: Rename method `ArrayDefinition::setReferenceContainer()` to `withReferenceContainer()` and make it
  immutable (@vjik)
- Chg #37: Remove method `ParameterDefinition::isBuiltin()` (@vjik)

## 1.0.2 April 01, 2022

- Bug #32: Throw exception instead of returning default value if optional dependency exists but there is an exception
  when getting it (@vjik)
- Bug #34: In one of edge cases don't throw exception if container returned result of incorrect type (@vjik)

## 1.0.1 December 19, 2021

- Bug #31: Add support for objects as default parameter values (@vjik)

## 1.0.0 November 30, 2021

- Initial release.
