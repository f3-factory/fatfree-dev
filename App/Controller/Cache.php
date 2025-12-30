<?php

namespace App\Controller;

use F3\Base;
use F3\Test;

class Cache extends BaseController {

    function get(Base $f3) {
        $test=new Test();
        $f3->copy('ROUTES', 'ROUTES_bak');
        $f3->set('CACHE',TRUE);

        $test->expect(
            is_null($f3->get('ERROR')),
            'No errors expected at this point'
        );

        $test->expect(
            $backend=$f3->get('CACHE'),
            '>> Cache backend '.$f3->stringify($backend).' detected'
        );
        $repeat=TRUE;
        while ($repeat) {
            usleep(1.1e3*2);
            $cache=\F3\Cache::instance();

            $session=new \F3\Session();
            $test->expect(
                $session->sid()===NULL,
                'Cache-based session instantiated but not started'
            );
            $f3->set('SESSION.foo','hello world');
            $test->expect(
                $sid=$session->sid(),
                'Cache-based session started: '.$sid
            );
            session_status() === PHP_SESSION_ACTIVE && session_write_close();
            $test->expect(
                $session->sid()===NULL && session_status() !== PHP_SESSION_ACTIVE,
                'Cache-based session written and closed'
            );
            $_SESSION=[];
            $test->expect(
                $f3->get('SESSION.foo')=='hello world',
                'Session variable retrieved from cache'
            );
            $test->expect(
                $ip=$session->ip(),
                'IP address: '.$ip
            );
            $test->expect(
                $stamp=$session->stamp(),
                'Timestamp: '.date('r',$stamp)
            );
            $test->expect(
                $agent=$session->agent(),
                'User agent: '.$agent
            );
            $test->expect(
                $csrf=$session->csrf(),
                'Anti-CSRF token: '.$csrf
            );
            $before=$after='';
            if (preg_match('/^Set-Cookie: '.session_name().'=(\w+)/m',
                implode(PHP_EOL,array_reverse(headers_list() ?: $f3->RESPONSE_HEADERS)),$m))
                $before=$m[1];
            $f3->clear('SESSION');
            if (preg_match('/^Set-Cookie: '.session_name().'=(\w+)/m',
                implode(PHP_EOL,array_reverse(headers_list() ?: $f3->RESPONSE_HEADERS)),$m))
                $after=$m[1];
            $test->expect(
                empty($f3->SESSION) && !$cache->exists($sid.'@') &&
                $before==$sid && $after=='deleted' &&
                empty($f3->COOKIE[session_name()]),
                'Session destroyed and cookie expired'
            );
            $backend=$f3->get('CACHE');
            $f3->clear('CACHE');
            if (!preg_match('/folder=/',$backend) &&
                !preg_match('/memcached=/',$backend) &&
                !preg_match('/redis=/',$backend)) {
                $f3->set('CACHE','folder=tmp/cache/');
                if (preg_match('/folder=/',$backend=$f3->get('CACHE'))) {
                    $test->expect(
                        $backend,
                        '>> Cache backend '.$f3->stringify($backend).' specified'
                    );
                    continue;
                }
            }
            if (extension_loaded('memcached') &&
                !preg_match('/memcached=/',$backend) &&
                !preg_match('/redis=/',$backend)) {
                $f3->set('CACHE','memcached=f3-memcached');
                if (preg_match('/memcached=/',$backend=$f3->get('CACHE'))) {
                    $test->expect(
                        $backend,
                        '>> Cache backend '.$f3->stringify($backend).' specified'
                    );
                    continue;
                }
            }
            if (extension_loaded('redis') &&
                !preg_match('/redis=/',$backend)) {
                $f3->set('CACHE','redis=f3-redis');
                if (preg_match('/redis=/',$backend=$f3->get('CACHE'))) {
                    $test->expect(
                        $backend,
                        '>> Cache backend '.$f3->stringify($backend).' specified'
                    );
                    continue;
                }
            }

            $repeat=FALSE;
        }
        $f3->copy('ROUTES_bak', 'ROUTES');
        $f3->set('results',$test->results());
    }

}
