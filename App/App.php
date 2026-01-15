<?php

namespace App;

use App\Controller\SessionTest;
use F3\Base;
use F3\Overdrive\AppInterface;

class App implements AppInterface
{

    public function init(): void
    {
        $f3 = Base::instance();

        ini_set('display_errors', 1);
        error_reporting(-1);

        $f3->DEBUG = 2;
        $f3->LOGGABLE = '500';
        $f3->UI = 'ui/';
        $f3->TZ = 'Europe/Berlin';

        // init DI container
        $f3->CONTAINER = \F3\Service::instance();

        // init PSR7 support
        \F3\Http\MessageFactory::registerDefaults();

        $f3->set('menu', [
            '/' => 'Env',
            '/globals' => 'Globals',
            '/redir' => 'Router',
            '/cache' => 'Cache Engine',
            '/template' => 'Template',
            '/markdown' => 'Markdown',
            '/basket' => 'Basket',
            '/sql' => 'SQL',
            '/mongo' => 'MongoDB',
            '/jig' => 'Jig',
            '/auth' => 'Auth',
            '/log' => 'Log Engine',
            '/image' => 'Image',
            '/web' => 'Web',
            '/ws' => 'WebSocket',
            '/geo' => 'Geo',
            '/google' => 'Google',
            '/openid' => 'OpenID',
            '/pingback' => 'Pingback',
            '/subdir' => 'Subdir',
            '/session-test' => 'Session',
        ]);

        $f3->map('/', 'App\Controller\Env');
        $f3->map('/@controller', 'App\Controller\@controller');

        $f3->route('GET /session-{@action}', SessionTest::class.'->@action');
        $f3->route('GET /subdir', function (\F3\Base $f3) {
            echo 'ERROR: Subdir routing is not possible in worker mode';
        });

    }

    public function run(): void
    {
        Base::instance()->run();
    }
}