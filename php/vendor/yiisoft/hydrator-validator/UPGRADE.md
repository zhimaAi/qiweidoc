# Upgrading Instructions for Yii Validating Hydrator

This file contains the upgrade notes. These notes highlight changes that could break your
application when you upgrade the package from one version to another.

> **Important!** The following upgrading instructions are cumulative. That is, if you want
> to upgrade from version A to version C and there is version B between A and C, you need
> to following the instructions for both A and B.

## Upgrade from 1.x to 2.x

If you use `ValidatedInputInterface::getValidatedInput()` on non-validated inputs or form models, wrap it with
`try ... catch`:

```php
$result = $input->getValidatedInput();
// ↓
try {
    $result = $input->getValidatedInput();
} catch (LogicException) {
    $result = null;
}
```
