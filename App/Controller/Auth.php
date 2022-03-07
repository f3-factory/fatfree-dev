<?php

namespace App\Controller;

class Auth extends BaseController {

	function get($f3) {
		$test=new \F3\Test;
		$test->expect(
			is_null($f3->get('ERROR')),
			'No errors expected at this point'
		);
		if (!is_dir('tmp/'))
			mkdir('tmp/',\F3\Base::MODE,TRUE);
		$db=new \F3\DB\Jig('tmp/');
		$db->drop();
		$user=new \F3\DB\Jig\Mapper($db,'users');
		$user->set('user_id','admin');
		$user->set('password','secret');
		$user->save();
		$user->reset();
		$user->set('user_id','superadmin');
		$user->set('password',password_hash('supersecret',PASSWORD_BCRYPT));
		$user->save();
		$auth=new \F3\Auth($user,['id'=>'user_id','pw'=>'password']);
		$test->expect(
			$auth->basic(),
			'HTTP basic auth mechanism'
		);
		$test->expect(
			$auth->login('admin','secret') && !$auth->login('user','what'),
			'Login auth mechanism (Jig storage)'
		);
		$auth=new \F3\Auth($user,['id'=>'user_id','pw'=>'password'],function($pw,$hash){
			return password_verify($pw,$hash);
		});
		$test->expect(
			$auth->login('superadmin','supersecret') && !$auth->login('user','what'),
			'Login auth mechanism with password_verify (Jig storage)'
		);
		$db->drop();
		if (extension_loaded('mongo')) {
			try {
				$db=new \F3\DB\Mongo('mongodb://localhost:27017','test');
				$db->drop();
				$user=new \F3\DB\Mongo\Mapper($db,'users');
				$user->set('user_id','admin');
				$user->set('password','secret');
				$user->save();
				$auth=new \F3\Auth($user,
					['id'=>'user_id','pw'=>'password']);
				$test->expect(
					$auth->login('admin','secret') &&
					!$auth->login('user','what'),
					'Login auth mechanism (MongoDB storage)'
				);
			}
			catch (\Exception $x) {
			}
		}
		if (extension_loaded('pdo_sqlite')) {
			$db=new \F3\DB\SQL('sqlite::memory:');
			$db->exec(
				'CREATE TABLE users ('.
					'user_id VARCHAR(30),'.
					'password VARCHAR(30),'.
					'PRIMARY KEY(user_id)'.
				');'
			);
			$user=new \F3\DB\SQL\Mapper($db,'users',null, 0);
			$user->set('user_id','admin');
			$user->set('password','secret');
			$user->save();
			$auth=new \F3\Auth($user,
				['id'=>'user_id','pw'=>'password']);
			$test->expect(
				$auth->login('admin','secret') &&
				!$auth->login('user','what'),
				'Login auth mechanism (SQL storage)'
			);
		}
		$f3->set('results',$test->results());
	}

}
