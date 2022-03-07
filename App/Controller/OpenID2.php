<?php

namespace App\Controller;

class OpenID2 extends BaseController {

	function get($f3) {
		$test=new \F3\Test;
		$f3->set('results',$test->results());
		$test->expect(
			is_null($f3->get('ERROR')),
			'No errors expected at this point'
		);
		$openid=new \F3\Web\OpenID;
		$openid->set('endpoint','https://steamcommunity.com/openid');
		$test->expect(
			$openid->verified(),
			'OpenID '.$openid->get('identity').' verified'
		);
		$test->expect(
			$response=$openid->response(),
			'OpenID attributes in response: '.var_export($response,true)
		);
		$f3->set('results',$test->results());
	}

}

