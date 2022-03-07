<?php

ini_set('display_errors', 1);
error_reporting(-1);

require_once('lib/F3/Base.php');
$f3=\F3\Base::instance();

//if (extension_loaded('mongodb') && is_file($file='lib/MongoDB/functions.php'))
//	require($file);

//$f3->AUTOLOAD = 'lib/,./';
$f3->set('DEBUG',2);
$f3->set('UI','ui/');

$f3->set('menu',
	[
		'/'=>'Env',
		'/globals'=>'Globals',
		'/internals'=>'Internals',
		'/hive'=>'Hive',
		'/lexicon'=>'Lexicon',
		'/autoload'=>'Autoloader',
		'/redir'=>'Router',
		'/cli'=>'CLI',
		'/cache'=>'Cache Engine',
		'/config'=>'Config',
		'/view'=>'View',
		'/template'=>'Template',
		'/markdown'=>'Markdown',
		'/unicode'=>'Unicode',
		'/audit'=>'Audit',
		'/basket'=>'Basket',
		'/sql'=>'SQL',
		'/mongo'=>'MongoDB',
		'/jig'=>'Jig',
		'/auth'=>'Auth',
		'/log'=>'Log Engine',
		'/matrix'=>'Matrix',
		'/image'=>'Image',
		'/web'=>'Web',
		'/ws'=>'WebSocket',
		'/geo'=>'Geo',
		'/google'=>'Google',
		'/openid'=>'OpenID',
		'/pingback'=>'Pingback'
	]
);

$f3->map('/','App\Controller\Env');
$f3->map('/@controller','App\Controller\@controller');

$f3->run();
