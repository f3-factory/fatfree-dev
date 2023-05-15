<?php

namespace App\Hive;

use \F3\Hive;

class Customer extends Hive {

    const ProxyProps = ['last_name'];

    public ?string $first_name = '';
    public ?string $last_name;
    public string $email;
    public ?string $phone = NULL;
    public ?array $meta;
    public ?object $obj;
    public array $arr1;
    public ?array $arr2;
    public ?array $arr3 = null;
    public ?array $arr4 = [];

}
