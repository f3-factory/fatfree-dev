<?php

namespace App;

class Config extends Controller {

	function get($f3) {
		$test=new \Test;
		$test->expect(
			is_null($f3->get('ERROR')),
			'No errors expected at this point'
		);
		$f3->config('app.ini');
		$test->expect(
			$f3->get('test')=='',
			'Empty string'
		);
		$test->expect(
			$f3->get('num')===123,
			'Integer'
		);
		$test->expect(
			$f3->get('str1')=='abc defg h ijk',
			'Unquoted string literal'
		);
		$test->expect(
			$f3->get('str2')=='abc',
			'Quoted string literal'
		);
		$test->expect(
			$f3->get('multi')=="this \nis a \nstring that spans \nseveral lines",
			'Multi-line string'
		);
		$test->expect(
			$f3->get('list')==[7,8,9],
			'Ordinary array'
		);
		$test->expect(
			$f3->get('hash')==['x'=>1,'y'=>2,'z'=>3],
			'Array with named keys'
		);
		$test->expect(
			$f3->get('mix')==['this',123.45,FALSE],
			'Array with mixed elements'
		);
		$test->expect(
			is_null($f3->get('const')) &&
			$f3->get('os')==PHP_OS,
			'PHP constants'
		);
		$test->expect(
			$f3->get('long')==='12345678901234567890' &&
			$f3->get('huge')===12345678901234567890,
			'Data types preserved'
		);
		$routes=array_keys($f3->get('ROUTES'));
		$test->expect(
			in_array('/go',$routes) &&
			in_array('/404',$routes) &&
			in_array('/inside/@series',$routes) &&
			in_array('/cached',$routes),
			'Routes declared'
		);
		$test->expect(
			$f3->get('ALIASES.named')=='/404',
			'Named route defined'
		);
		$test->expect(
			$f3->exists('ROUTES./404.0.POST'),
			'Named route defined with an existing name'
		);
		$test->expect(
			in_array('/map',$routes),
			'ReST map declared'
		);
		$test->expect(
			$f3->get('section1.myvar')=='myval1' &&
			$f3->get('section2.myvar')=='myval2' &&
			$f3->get('section3.dummy')=='HAIL THE CONQUERING HERO' &&
			$f3->get('section3.great')=='EXACTLY',
			'Custom section'
		);
		$test->expect(
			$f3->get('section4.dummy')=='HORROR, POWER, AND PUNISHMENT.' &&
			$f3->get('section5.dummy')=='"ETERNAL RELATIVITIES DEVELOPS MOST BLISSES."',
			'Custom section parser'
		);
		$test->expect(
			$f3->get('section6.Кольцо Урала.baz')==1234,
			'Custom section UTF8 support'
		);
		$cache=\Cache::instance();
		$test->expect(
			$cache->exists($hash=$f3->hash('num').'.var',$val) &&
			$val==123,
			'Primitive value cached'
		);
		$cache->clear($hash);
		$test->expect(
			$cache->exists($hash=$f3->hash('mix').'.var',$val) &&
			$val==["this",123.45,FALSE],
			'Array value cached'
		);
		$cache->clear($hash);
		$f3->set('results',$test->results());
	}

}

class ConfigParser extends \Prefab {

	/** @var static \Base */
	protected $f3;

	function __construct() {
		$this->f3 = \Base::instance();
	}

	public function parse1($key, $value, $scope) {
		$this->f3->set($scope.'.'.$key, strtoupper($value));
	}

	static public function parse2($key, $value, $scope) {
		\Base::instance()->set($scope.'.'.$key, strtoupper($value));
	}
}