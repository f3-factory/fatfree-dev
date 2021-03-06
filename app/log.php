<?php

namespace App;

class Log extends Controller {

	function get($f3) {
		$test=new \Test;
		$test->expect(
			is_null($f3->get('ERROR')),
			'No errors expected at this point'
		);
		$f3->set('LOGS',$tmp=$f3->get('TEMP'));
		$log=new \Log($name='test.log');
		if (is_file($file=$tmp.$name))
			$log->erase();
		$log->write('foo');
		$test->expect(
			count($contents=file($file))==1 && strpos($contents[0],'foo'),
			'Log created'
		);
		$log->write('bar');
		$test->expect(
			count($contents=file($file))==2 && strpos($contents[1],'bar'),
			'Write to log'
		);
		$log->write('baz');
		$test->expect(
			count($contents=file($file))==3 && strpos($contents[2],'baz'),
			'More log entries'
		);
		$f3->set('HEADERS.X-Forwarded-For','123.123.123.123');
		$log->write('narf');
		$test->expect(
			count($contents=file($file))==4 && strpos($contents[3],'123.123.123.123'),
			'Forwarded-IP logged'
		);
		$log->erase();
		$f3->set('results',$test->results());
	}

}
