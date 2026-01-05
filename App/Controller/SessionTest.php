<?php

namespace App\Controller;

use F3\Base;

class SessionTest extends BaseController {

    public function __construct() {
        /**
         * NB: it's mandatory for swoole / roadrunner to not have a zero cookie lifetime
         * otherwise cookie are deleted on creation
         * TODO: check if this is a bug on our side
         */
        $f3 = Base::instance();
        $f3->set('JAR.lifetime', 1800);
        $f3->CACHE = true;
        $f3->DB = new \F3\DB\SQL('sqlite:tmp/sqlite.db');
        $f3->DB->exec(
            [
                'PRAGMA temp_store=MEMORY;',
                'PRAGMA journal_mode=MEMORY;',
                'PRAGMA foreign_keys=ON;',
            ],
        );
    }

    protected function initSessionHandler()
    {
        $db=new \F3\DB\SQL('sqlite:tmp/sqlite.db');
        $db->exec(
            [
                'PRAGMA temp_store=MEMORY;',
                'PRAGMA journal_mode=MEMORY;',
                'PRAGMA foreign_keys=ON;',
            ],
        );
        $session = new \F3\DB\SQL\Session(Base::instance()->DB);
//        $session = new \F3\Session();
        $session->threatLevelThreshold = 1;
        return $session;
    }

    public function test(Base $f3, $params = [])
    {
        $this->initSessionHandler();
        $f3->contents = \F3\Preview::instance()->render('templates/session-test.html', null);
    }

    public function start(Base $f3, $params = [])
    {
        $this->initSessionHandler();
        $f3->SESSION['user_id'] = rand(1, 100);
        $f3->reroute('/session-test');
    }

    public function fail(Base $f3, $params = [])
    {
        $f3->HEADERS['User-Agent'] = 'fooo';
        $f3->IP = '111.111.111.111';
        $session = $this->initSessionHandler();
        $session->threatLevelThreshold = 1;
        $session->onSuspect = function (\SessionHandlerInterface $session, $sid) use ($f3) {
            // $f3->clear('SESSION'); // NB: avoid this usage, instead do:
            $session->destroy($sid);
            $session->close();
            unset($f3->{'COOKIE.'.\session_name()});

            $f3->reroute('/session-test');
        };
        // starting session
        var_dump($f3->get('SESSION.user_id'));
        var_dump(session_status() === PHP_SESSION_ACTIVE);
    }

    public function addSessionValue(Base $f3, $params = [])
    {
        $this->initSessionHandler();
        $rnd = rand(1, 100);
        $f3->SESSION['foo'] = $rnd;
        $f3->set('SESSION.baz', $rnd*2);
        $f3->reroute('/session-test');
    }

    public function addCookieValue(Base $f3, $params = [])
    {
        $this->initSessionHandler();
        $rnd = rand(1, 100);
        $f3->set('COOKIE.foo', $rnd, 60);
        // this doesn't work, implicit modification cannot call the setter hook
        $f3->COOKIE['foo2'] = $rnd;
        $f3->reroute('/session-test');
    }

    public function removeSessionValue(Base $f3, $params = [])
    {
        $this->initSessionHandler();
        unset($f3->SESSION['foo']);
        $f3->reroute('/session-test');
    }

    public function removeCookieValue(Base $f3, $params = [])
    {
        $this->initSessionHandler();
        $f3->clear('COOKIE.foo');
        $f3->reroute('/session-test');
    }

    public function clear(Base $f3, $params = [])
    {
        $this->initSessionHandler();
        $f3->clear('SESSION');
        $f3->reroute('/session-test');
    }

}