<?php

namespace App\Controller;

class WS extends BaseController {

	function get($f3) {
		$test=new \F3\Test;
		$f3->set('JS',\F3\Preview::instance()->render('ws.htm'));
		$f3->set('results',$test->results());
	}

}
