<?php

namespace App\Controller;

class OpenID extends BaseController {

	function get($f3) {
		$test=new \F3\Test;
		$test->expect(
			is_null($f3->get('ERROR')),
			'No errors expected at this point'
		);
		$openid=new \F3\Web\OpenID;
		$openid->set('endpoint','https://steamcommunity.com/openid');
		$openid->set('identity','http://specs.openid.net/auth/2.0/identifier_select');
		$openid->set('return_to',
			$f3->get('SCHEME').'://'.$f3->get('HOST').
			$f3->get('BASE').'/'.'openid2');
		// auth() should always redirect if successful; fail if displayed
		$test->expect(
			$openid->auth(),
			'OpenID authentication'
		);
		$f3->set('results',$test->results());
	}

}
