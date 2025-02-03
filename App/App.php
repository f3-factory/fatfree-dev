<?php

namespace App;

use F3\Base;
use F3\Overdrive\AppInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class App implements AppInterface {

    public function init(): void
    {
        $f3 = Base::instance();

        ini_set('display_errors', 1);
        error_reporting(-1);

        $f3->DEBUG = 0;
        $f3->LOGGABLE = '500';
        $f3->UI = 'ui/';
        $f3->TZ = 'Europe/Berlin';

        // init DI container
        $f3->CONTAINER = \F3\Service::instance();

        // init PSR7 support
        \F3\Http\MessageFactory::registerDefaults();

        $f3->set('menu', [
            '/'=>'Env',
            '/globals'=>'Globals',
            '/internals'=>'Internals',
            '/service'=>'Container',
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
            '/pingback'=>'Pingback',
            '/session-test'=>'Session Test',
        ]);

        $f3->map('/','App\Controller\Env');
        $f3->map('/@controller','App\Controller\@controller');


        $f3->route('GET|POST /page', [\App\Page::class,'view']);

        $f3->route('GET /test/@foo', [\App\Page::class,'test']);
        $f3->route('GET /test2/@foo', function(Base $f3, $params) {
            var_dump($f3->PARAMS);
            var_dump($params);
        });

        $f3->route('GET /hallo-world', function(Base $f3, RequestInterface $request, ResponseInterface $response, StreamFactoryInterface $streamFactory) {
            $agent = $request->getHeaderLine('User-Agent');
            return $response->withBody($streamFactory
                ->createStream('Your user agent is: '.$agent));

//            echo phpinfo();
//            return;
//            var_dump(\opcache_get_status()['jit']);
//            echo 'Hallo World';
            //	return $response->withBody(new Stream($msg));
        });

        $f3->route('GET /cookie-set', function(Base $f3, \F3\Http\Request $request, \F3\Http\Response $response) {
            var_dump(ini_get('session.use_strict_mode'));
            $f3->set('COOKIE.meins2', 'cookie eins2', 60);
            $f3->set('COOKIE.meins', 'foo', 60);
            $f3->set('COOKIE.testy', 'foobarnarf', 60);
            echo 'cookie was set';
        });

        $f3->route('GET /cookie-get', function(Base $f3, \F3\Http\Request $request, \F3\Http\Response $response) {
            $cookie = $f3->get('COOKIE');
            echo 'cookies is: '.var_export($cookie, true);
            $f3->clear('COOKIE.testy');
        });

        $f3->route( 'GET /swoole-app', [SwooleApp::class,'get']);
        $f3->route( 'POST /swoole-app', [SwooleApp::class,'post']);
        $f3->route( 'PUT /swoole-app', SwooleApp::class.'->put');

        $f3->map( '/foo', Page::class);

        $f3->route('PUT /foo2', function(Base $f3) {
            var_dump($f3->REALM);
            var_dump($f3->QUERY);
            $uri = $f3->make(UriFactoryInterface::class);
            $u = $uri->createUri($f3->REALM);
            var_dump($u->getPath());
            var_dump($u->getQuery());
        });

        $f3->route('GET /cookie-delete', function(Base $f3, \F3\Http\Request $request, \F3\Http\Response $response) {
            $f3->clear('COOKIE.meins');
            $f3->header('Content-Type: text/plain');
            $f3->header('Content-Encoding: utf-8');
            echo 'cookie gelöscht'.PHP_EOL;
            $cookie = $f3->get('COOKIE.meins');
            echo 'cookie is: '.var_export($cookie, true);
        });

        //$request = new \F3\Http\ServerRequest();
        //$request = $request->withMethod('GET')
        //    ->withCookieParams([
        //        'foo' => 'bar',
        //    ])
        //    ->withUri('http://f3v4.nginx.php82.localhost/page');
        //$request->setServerParams($f3->SERVER);
        //$f3->request($request);

        $f3->route('GET|POST /session-test', [\App\SessionTest::class,'view']);
        $f3->route('GET|POST /session-start', [\App\SessionTest::class,'start']);
        $f3->route('GET|POST /session-add', [\App\SessionTest::class,'add']);
        $f3->route('GET|POST /session-remove', [\App\SessionTest::class,'remove']);
        $f3->route('GET|POST /session-clear', [\App\SessionTest::class,'clearAll']);

    }

    public function run(): void
    {
        Base::instance()->run();
    }
}