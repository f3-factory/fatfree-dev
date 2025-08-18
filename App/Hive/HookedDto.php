<?php

namespace App\Hive;

use F3\Hive;

class HookedDto extends Hive {

    public string $string;
    public ?string $nullableString;
    public ?string $nullableDefaultedString = 'foo';

    public string $stringWithGetter {
        get {
            return strtoupper($this->stringWithGetter);
        }
    }

    public array $array;
    public ?array $nullableArray;
    public ?array $nullableDefaultedArray = null;
    public ?array $hookedGetNullableArray = null {
        set => $this->hookedGetNullableArray ? array_map(fn($item) => strtoupper($item), $this->hookedGetNullableArray) : null;
    }

}
