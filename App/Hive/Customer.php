<?php

namespace App\Hive;

use \F3\Hive;

class Customer extends Hive {

	public ?string $first_name = '';
	public ?string $last_name;
	public string $email;
	public ?string $phone = NULL;
	public ?array $meta;
	public ?object $obj;

}
