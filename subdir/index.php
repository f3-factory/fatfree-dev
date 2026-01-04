<?php
require '../vendor/autoload.php';

$f3 = \F3\Base::instance();
$f3->TEMP = '../tmp/';
$f3->ABSOLUTE_ALIAS = true;
$f3->withBaseTag = false;

$f3->route('GET main: /', function(\F3\Base $f3) {
    echo \F3\Template::instance()->render('tmpl.html');
});
$f3->route('GET test1: /test1', function(\F3\Base $f3) {
    echo \F3\Template::instance()->render('tmpl.html');
});
$f3->route('GET test2: /test2', function(\F3\Base $f3) {
    echo \F3\Template::instance()->render('tmpl.html');
});
$f3->route('GET test3: /test1/test2/test3', function(\F3\Base $f3) {
    echo \F3\Template::instance()->render('tmpl.html');
});
$f3->route('GET test4: /test4/@sub/test', function(\F3\Base $f3) {
    echo \F3\Template::instance()->render('tmpl.html');
});
$f3->route('GET reroute: /reroute', function(\F3\Base $f3) {
    $f3->reroute(['test4', ['sub' => 'foo']]);
});

$f3->run();