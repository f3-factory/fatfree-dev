<?php

namespace App\Controller;

use F3\Prefab;

class Helper {

	use Prefab;

	function pick($val,$match) {
		return preg_grep('/'.$match.'/',$val);
	}

}
