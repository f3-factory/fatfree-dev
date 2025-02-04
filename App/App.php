<?php

namespace App;

use F3\Base;
use F3\Overdrive\AppInterface;

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
        ]);

        $f3->map('/','App\Controller\Env');
        $f3->map('/@controller','App\Controller\@controller');
    }

    public function run(): void
    {
        Base::instance()->run();
    }
}