<?php

namespace Common\DB;

use Doctrine\Common\Collections\ArrayCollection;

class ModelCollection extends ArrayCollection
{
    public function toArray(): array
    {
        $elements = parent::toArray();

        return array_map(function ($element) {
            if ($element instanceof BaseModel) {
                return $element->toArray();
            }
            if (is_array($element)) {
                return $element;
            }
            if (is_object($element) && method_exists($element, 'toArray')) {
                return $element->toArray();
            }

            return $element;
        }, $elements);
    }
}
