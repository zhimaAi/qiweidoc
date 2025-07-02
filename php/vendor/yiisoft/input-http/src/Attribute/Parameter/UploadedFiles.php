<?php

declare(strict_types=1);

namespace Yiisoft\Input\Http\Attribute\Parameter;

use Attribute;
use Yiisoft\Hydrator\Attribute\Parameter\ParameterAttributeInterface;

/**
 * Take data for the property or attribute from uploaded files.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class UploadedFiles implements ParameterAttributeInterface
{
    /**
     * @param array|string|null $name The field in the uploaded files to get data from.
     * @psalm-param string|list<string>|null $name
     */
    public function __construct(
        private string|array|null $name = null
    ) {
    }

    /**
     * Get the name of the field in the uploaded files to get data from.
     *
     * @return array|string|null The field in the uploaded files to get data from.
     * @psalm-return string|list<string>|null
     */
    public function getName(): string|array|null
    {
        return $this->name;
    }

    public function getResolver(): string
    {
        return UploadedFilesResolver::class;
    }
}
