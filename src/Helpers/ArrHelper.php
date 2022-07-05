<?php

namespace App\Helpers;

class ArrHelper
{
    /**
     * Объеденяет вложенные массивы
     *
     * Пример: [[1,2], [3,4]]  ==>  [1,2,3,4]
     *
     * @param array $elements
     * @return array
     */
    public static function flatten(array $elements): array
    {
        return array_reduce($elements, function ($a, $b) {
            return array_merge($a, (array)$b);
        }, []);
    }
}
