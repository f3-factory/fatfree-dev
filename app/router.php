<?php

namespace App;

class Router extends Controller {

	function callee() {
		\Base::instance()->set('called',TRUE);
	}

	function get(\Base $f3) {
		$test=new \Test;
		$test->expect(
			is_null($f3->get('ERROR')),
			'No errors expected at this point'
		);
		$test->expect(
			$result=is_file($file=$f3->get('TEMP').'redir') &&
			$val=$f3->read($file),
			'Rerouted to this page'.($result?(': '.
				sprintf('%.1f',(microtime(TRUE)-(float)$val)*1e3).'ms'):'')
		);
		if (is_file($file))
			@unlink($file);
		$f3->set('ONREROUTE',function($url,$permanent) {
			$f3=\Base::instance();
			$f3->set('reroute',$url);
		});
		$f3->reroute('/foo?bar=baz');
		$test->expect(
			$f3->get('reroute')=='/foo?bar=baz',
			'Custom rerouting'
		);
		$f3->clear('ROUTES');
		$f3->route('GET|POST @hello:/',
			function($f3) {
				$f3->set('bar','foo');
			}
		);
		$f3->mock('GET @hello');
		$test->expect(
			$f3->get('bar')=='foo',
			'Named route'
		);
		$test->expect(
			$f3->get('ALIASES.hello')=='/',
			'Named route retrieved'
		);
		$f3->route('GET @complex:/resize/@format/*/sep/*','App->nowhere');
		$test->expect(
			$f3->alias('complex','format=20x20,*=[foo/bar,baz.gif]')=='/resize/20x20/foo/bar/sep/baz.gif' &&
			$f3->alias('complex','format=20x20,*=[foo,bar]',['x'=>123,'y'=>['z'=>2]])=='/resize/20x20/foo/sep/bar?x=123&y%5Bz%5D=2',
			'Alias() function'
		);
		$f3->reroute('@hello');
		$rr1=$f3->get('reroute');
		$f3->reroute('@hello?x=789');
		$rr2=$f3->get('reroute');
		$f3->reroute('@complex(format=20x20,*=[foo/bar,baz.gif])');
		$rr3=$f3->get('reroute');
		$f3->reroute('@complex(format=20x20,*=[foo/bar,baz.gif])?x=789');
		$rr4=$f3->get('reroute');
		$f3->reroute(['complex',['format'=>'20x20','*'=>['foo/bar','baz.gif']]]);
		$rr5=$f3->get('reroute');
		$test->expect(
			$rr1=='/' &&
			$rr2=='/?x=789' &&
			$rr3=='/resize/20x20/foo/bar/sep/baz.gif' &&
			$rr4=='/resize/20x20/foo/bar/sep/baz.gif?x=789' &&
			$rr5=='/resize/20x20/foo/bar/sep/baz.gif',
			'Rerouting to alias'
		);
		$f3->reroute('@hello#foo');
		$rr1=$f3->get('reroute');
		$f3->reroute('@hello?x=789#foo');
		$rr2=$f3->get('reroute');
		$f3->reroute('@complex(format=20x20,*=[foo/bar,baz.gif])#foo');
		$rr3=$f3->get('reroute');
		$f3->reroute('@complex(format=20x20,*=[foo/bar,baz.gif])?x=789#foo');
		$rr4=$f3->get('reroute');
		$f3->reroute(['complex','format=20x20,*=[foo/bar,baz.gif]',['x'=>789],'foo']);
		$rr5=$f3->get('reroute');
		$test->expect(
			$rr1=='/#foo' &&
			$rr2=='/?x=789#foo' &&
			$rr3=='/resize/20x20/foo/bar/sep/baz.gif#foo' &&
			$rr4=='/resize/20x20/foo/bar/sep/baz.gif?x=789#foo' &&
			$rr5=='/resize/20x20/foo/bar/sep/baz.gif?x=789#foo',
			'Rerouting to page fragment'
		);
		$f3->set('ONREROUTE',NULL);
		$f3->mock('GET /');
		$test->expect(
			$f3->get('bar')=='foo',
			'Routed to anonymous/lambda function'
		);
		$f3->clear('bar');
		$f3->mock('POST @hello');
		$test->expect(
			$f3->get('bar')=='foo',
			'Mixed request routing pattern'
		);
		$f3->clear('ROUTES');
		$f3->route(['GET /wild/*','GET /wild/*/page/*'],
			function($f3) {
			}
		);
		$f3->mock('GET /wild/dangerous/beast?at=large');
		$test->expect(
			$f3->get('PARAMS.*')=='dangerous/beast',
			'Wildcard routing pattern'
		);
		$f3->mock('GET /wild/dangerous/beast/page/fourty/seven');
		$test->expect(
			$f3->get('PARAMS.*.0')=='dangerous/beast'
			&& $f3->get('PARAMS.*.1')=='fourty/seven',
			'Wildcard routing pattern [multiple]'
		);
		$f3->route('GET @wildPage:/a/*/b/@c/*',
			function($f3) {
			}
		);
		$f3->mock('GET /a/foo%25bar/x/b/2/bäz');
		$test->expect(
			$f3->alias('wildPage')=='/a/foo%25bar/x/b/2/b%C3%A4z'
			&& $f3->get('PARAMS.*.0') === 'foo%bar/x'
			&& $f3->get('PARAMS.c') == 2
			&& $f3->get('PARAMS.*.1') === 'bäz',
			'Alias generated with encoded default PARAMS'
		);
		$f3->set('type','none');
		$f3->route('GET|POST / [ajax]',
			function($f3) {
				$f3->set('type','ajax');
			}
		);
		$f3->route('GET|POST / [sync]',
			function($f3) {
				$f3->set('type','sync');
			}
		);
		$f3->mock('GET /');
		$test->expect(
			$f3->get('type')=='sync',
			'Synchronous HTTP request'
		);
		$f3->mock('GET / [ajax]');
		$test->expect(
			$f3->get('type')=='ajax',
			'AJAX request'
		);
		$f3->clear('ROUTES');
		$f3->route('GET /',__NAMESPACE__.'\please');
			function please($f3) {
				$f3->set('send','money');
			}
		$f3->mock('GET /');
		$test->expect(
			$f3->get('send')=='money',
			'Routed to regular namespaced function'
		);
		$f3->clear('ROUTES');
		$f3->map('/dummy','NS\C');
		$ok=TRUE;
		$list='';
		foreach (explode('|',\Base::VERBS) as $verb) {
			$f3->mock($verb.' /dummy',['a'=>'hello']);
			if ($f3->get('route')!=$verb ||
				preg_match('/GET|HEAD/',strtoupper($verb)) &&
				$f3->get('body') && !parse_url($f3->get('URI'),PHP_URL_QUERY))
				$ok=FALSE;
			else
				$list.=($list?', ':'').$verb;
		}
		$test->expect(
			$ok,
			'Methods supported'.($list?(': '.$list):'')
		);
		$f3->set('BODY','');
		$f3->mock('PUT /dummy');
		$test->expect(
			$f3->exists('BODY'),
			'Request body available'
		);
		$f3->mock('OPTIONS /dummy');
		$test->expect(
			preg_grep('/Allow: '.
				str_replace('|',',',\Base::VERBS.'/'),headers_list()),
			'HTTP OPTIONS request returns allowed methods'
		);
		$f3->clear('ERROR');
		$f3->clear('ROUTES');
		$f3->route('OPTIONS /dummy',
			function($f3,$args) {
				header('Allow: GET, POST');
			}
		);
		$f3->mock('OPTIONS /dummy');
		$test->expect(
			preg_grep('/^Allow: GET, POST$/',headers_list()),
			'HTTP OPTIONS request returns user-specified methods'
		);
		$f3->clear('ERROR');
		$f3->clear('ROUTES');
		$f3->route('GET @grub:/food/@id',
			function($f3,$args) {
				$f3->set('id',$args['id']);
			}
		);
		$f3->set('PARAMS.id','fish');
		$f3->mock('GET @grub');
		$test->expect(
			$f3->get('PARAMS.id')=='fish' &&
			$f3->get('id')=='fish',
			'Parameter in route captured'
		);
		$f3->mock('GET @grub(@id=bread)');
		$test->expect(
			$f3->get('id')=='bread',
			'Different parameter in route'
		);
		$f3->route('GET|POST|PUT @grub:/food/@id/@quantity',
			function($f3,$args) {
				$f3->set('id',$args['id']);
				$f3->set('quantity',$args['quantity']);
			}
		);
		$f3->mock('GET @grub(@id=beef,@quantity=789)');
		$test->expect(
			$f3->get('PARAMS.id')=='beef' &&
			$f3->get('PARAMS.quantity')==789 &&
			$f3->get('id')=='beef' && $f3->get('quantity')==789,
			'Multiple parameters'
		);
		$f3->mock('GET /food/macademia-nuts/253?a=1&b=3&c=5');
		$test->expect(
			$f3->get('PARAMS.id')=='macademia-nuts' &&
			is_numeric($qty=$f3->get('PARAMS.quantity')) && $qty==253 &&
			$_GET==['a'=>1,'b'=>3,'c'=>5],
			'Query string mocked'
		);
		$f3->mock('GET /food/chicken/999?d=246&e=357',['f'=>468]);
		$test->expect(
			$_GET==['d'=>246,'e'=>357,'f'=>468],
			'Query string and mock arguments merged'
		);
		$test->expect(
			$f3->get('id')=='chicken' && $f3->get('quantity')==999,
			'Route parameters captured along with query'
		);
		$f3->mock('POST /food/sushki/134?a=1',['b'=>2]);
		$test->expect(
			$_GET==['a'=>1] && $_POST==['b'=>2] && $_REQUEST==['a'=>1,'b'=>2] && $f3->get('BODY')=='b=2',
			'Request body and superglobals $_GET, $_POST, $_REQUEST correctly set on mocked POST'
		);
		$f3->mock('PUT /food/sushki/134?a=1',['b'=>2]);
		$test->expect(
			$_GET==['a'=>1] && $_POST==[] && $_REQUEST==['a'=>1] && $f3->get('BODY')=='b=2',
			'Request body and superglobals $_GET, $_POST, $_REQUEST correctly set on mocked PUT'
		);
		$f3->mock('POST /food/sushki/134?a=1',['b'=>2],NULL,'c=3');
		$test->expect(
			$_GET==['a'=>1] && $_POST==['b'=>2] && $_REQUEST==['a'=>1,'b'=>2] && $f3->get('BODY')=='c=3',
			'Mocked request body precedence over arguments'
		);
		$f3->mock('GET @grub(@id=%C3%B6%C3%A4%C3%BC,@quantity=123)');
		$test->expect(
			$f3->get('id')=='öäü' && $f3->get('quantity')==123,
			'Unicode characters in URL (PCRE version: '.PCRE_VERSION.')'
		);
		$f3->clear('ROUTES');
		$f3->route('GET /*','NS\C->get');
		$f3->route('GET /','NS\C->get');
		$f3->route('GET /@a','NS\C->get');
		$f3->route('GET /foo*','NS\C->get');
		$f3->route('GET /foo','NS\C->get');
		$f3->route('GET /foo/*','NS\C->get');
		$f3->route('GET /foo/@a.htm','NS\C->get');
		$f3->route('GET /foo/@b','NS\C->get');
		$f3->route('GET /foo/0','NS\C->get');
		$f3->route('GET /foo/bar','NS\C->get');
		$f3->mock('GET /dummy');
		$test->expect(
			array_keys($f3->get('ROUTES'))==[
				'/foo/bar',
				'/foo/0',
				'/foo/@a.htm',
				'/foo/@b',
				'/foo/*',
				'/foo',
				'/foo*',
				'/',
				'/@a',
				'/*',
			],
			'Route precedence order'
		);
		$f3->clear('ROUTES');
		$mark=microtime(TRUE);
		$f3->route('GET /nothrottle',
			function($f3) {
				$f3->set('message','Perfect wealth becomes me');
			}
		);
		$f3->mock('GET /nothrottle');
		$test->expect(
			($elapsed=microtime(TRUE)-$mark) || TRUE,
			'Page rendering baseline: '.
				sprintf('%.1f',$elapsed*1e3).'ms'
		);
		$f3->clear('ROUTES');
		$mark=microtime(TRUE);
		$f3->route('GET /throttled',
			function($f3) {
				$f3->set('message','Perfect wealth becomes me');
			},
			0, /* don't cache */
			$throttle=16 /* 8Kbps */
		);
		$f3->mock('GET /throttled');
		$test->expect(
			$elapsed=microtime(TRUE)-$mark,
			'Same page throttled @'.$throttle.'Kbps '.
				'(~'.(1000/$throttle).'ms): '.
				sprintf('%.1f',$elapsed*1e3).'ms'
		);
		$f3->set('QUIET',TRUE);
		$f3->set('DNSBL','bl.spamcop.net');
		$f3->set('blocked',TRUE);
		$f3->route('GET /forum',
			function($f3) {
				$f3->set('blocked',FALSE);
			}
		);
		$mark=microtime(TRUE);
		$f3->mock('GET /forum');
		$test->expect(
			!$f3->get('blocked'),
			'DNSBL lookup: '.sprintf('%.1f',(microtime(TRUE)-$mark)*1e3).'ms'
		);
		$f3->set('QUIET',FALSE);
		$f3->clear('ROUTES');
		$f3->clear('called');
		$f3->call('App\Router->callee');
		$test->expect(
			$f3->get('called'),
			'Call method (NS\Class->method)'
		);
		$f3->clear('called');
		$obj=new Router;
		$f3->call([$obj,'callee']);
		$test->expect(
			$f3->get('called'),
			'Call method (PHP array format)'
		);
		$f3->clear('called');
		$f3->call('App\callee');
		$test->expect(
			$f3->get('called'),
			'Call PHP function'
		);
		$f3->clear('called');
		$f3->call(function() {
			\Base::instance()->set('called',TRUE);
		});
		$test->expect(
			$f3->get('called'),
			'Call lambda function'
		);
		$test->expect(
			$f3->chain('App\a,App\b,App\c',1)==[1,2,4],
			'Callback chain()'
		);
		$test->expect(
			$f3->relay('App\a,App\b,App\c',1)==8,
			'Callback relay()'
		);
		$f3->ONERROR = function() {};
		$f3->HALT = false;
		$f3->route('GET|POST /cors-test', function($f3) {
			return 'cors';
		});
		$f3->set('CORS.origin', '*');
		$f3->set('CORS.credentials', true);
		$f3->set('CORS.expose', ['X-Version', 'Foo']);
		$f3->set('CORS.ttl', 60);
		$headers = [
			'Access-Control-Request-Method' => 'GET',
			'Origin' => 'localhost',
		];

		$f3->mset($headers, 'HEADERS.');
		$f3->mock('OPTIONS /cors-test', null, $headers);
		$headerlist = headers_list();
		$test->expect(in_array('Access-Control-Allow-Origin: *', $headerlist), 'CORS Preflight Origin test');
		$test->expect(in_array('Access-Control-Allow-Methods: OPTIONS,GET,POST', $headerlist), 'CORS Preflight Methods');
		$test->expect(in_array('Access-Control-Allow-Credentials: true', $headerlist), 'CORS Preflight Credentials');
		$test->expect(in_array('Access-Control-Max-Age: 60', $headerlist), 'CORS Preflight Max Age');
		header_remove();

		$f3->route('GET|POST /cors-test-ajax [ajax]', function($f3) {
			return 'cors';
		});
		$f3->mock('OPTIONS /cors-test-ajax [ajax]', null, $headers);
		$headerlist = headers_list();
		header_remove();

		$headers = [
			'Origin' => 'localhost',
		];
		$f3->clear('HEADERS.Access-Control-Request-Method');

		$out = $f3->mock('GET /cors-test-ajax [ajax]', null, $headers);
		$test->expect($out === 'cors' && in_array('Access-Control-Allow-Origin: *', $headerlist), 'CORS Ajax Route test');
		header_remove();

		$out = $f3->mock('GET /cors-test', null, $headers);
		$headerlist = headers_list();
		$test->expect($out === 'cors' && in_array('Access-Control-Allow-Origin: *', $headerlist), 'CORS Request');
		$test->expect(in_array('Access-Control-Expose-Headers: X-Version,Foo', $headerlist), 'CORS Request Expose Headers');
		header_remove();

		$f3->ONERROR = null;
		$f3->HALT = true;

		$f3->set('results',$test->results());
	}

}

function callee() {
	\Base::instance()->set('called',TRUE);
}

function a($x) {
	return $x;
}

function b($y) {
	return $y*2;
}

function c($z) {
	return $z*4;
}
