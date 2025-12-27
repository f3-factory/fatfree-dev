<?php

namespace App\Controller;

use F3\Base;
use F3\Test;

class Router extends BaseController
{

    public function get(Base $f3): void
    {
        $test = new Test;
        $f3->copy('ROUTES', 'ROUTES_bak');

        $test->expect(
            is_null($f3->get('ERROR')),
            'No errors expected at this point',
        );
        $f3->foo = 'bar';
        $test->expect(
            $f3 === Base::instance() && Base::instance()->foo == 'bar',
            'Same framework instance returned',
        );
        $test->expect(
            $result = is_file($file = $f3->get('TEMP').'redir') &&
                $val = $f3->read($file),
            'Rerouted to this page'.($result ? (': '.
                sprintf('%.1f', (microtime(true) - (float) $val) * 1e3).'ms') : ''),
        );
        if (is_file($file))
            @unlink($file);
        $f3->set('ONREROUTE', function ($url, $permanent) {
            $f3 = Base::instance();
            $f3->set('reroute', $url);
        });
        $f3->reroute('/foo?bar=baz');
        $test->expect(
            $f3->get('reroute') == '/foo?bar=baz',
            'Custom rerouting',
        );
        $f3->clear('ROUTES');
        $f3->route(
            'GET|POST @hello:/',
            function ($f3) {
                $f3->set('bar', 'foo');
            },
        );
        $mocked = false;
        $test_headers = [];
        $orig_headers = $f3->HEADERS;
        $exp_headers = ['X-Foo' => 'Bar'];
        $os_uri = $_SERVER['REQUEST_URI']; // $_SERVER intentional!
        $oh_uri = $f3->URI;
        $th_uri = '';
        $ts_uri = '';
        $f3->route(
            'GET|POST /mock',
            function (Base $f3) use (&$mocked, &$test_headers, &$th_uri, &$ts_uri) {
                $mocked = true;
                $f3->mocked = true;
                $test_headers = $f3->HEADERS;
                $th_uri = $f3->URI;
                $ts_uri = $f3->SERVER['REQUEST_URI'];
            },
        );
        $f3->mock('GET /mock', headers: $exp_headers);
        $test->expect(
            $mocked === true
            && $f3->mocked === true
            && $test_headers === $exp_headers
            && $f3->HEADERS === $exp_headers
            && $f3->URI === '/mock'
            && $th_uri === '/mock'
            && $ts_uri === '/mock'
            ,
            'Route mock test',
        );
        // reset
        $mocked = false;
        $f3->mocked = false;
        $f3->HEADERS = $orig_headers;
        $f3->SERVER['REQUEST_URI'] = $os_uri;
        $f3->URI = $oh_uri;
        $th_uri = '';
        $ts_uri = '';
        $f3->mock('GET /mock', headers: $exp_headers, sandbox: true);
        $test->expect(
            $mocked === true
            && $f3->mocked === false
            && $test_headers === $exp_headers
            && $f3->HEADERS === $orig_headers
            && $f3->URI === $oh_uri
            && $th_uri === '/mock'
            && $ts_uri === '/mock'
            && $f3->SERVER['REQUEST_URI'] === $os_uri // should not be altered in sandbox mode
            ,
            'Route mock test in sandbox',
        );

        $f3->route('GET @complex:/resize/@format/*/sep/*', 'App->nowhere');

        $f3->set('ONREROUTE', null);

        $f3->route(
            'GET @grub:/food/@id',
            function ($f3, $args) {
                $f3->set('id', $args['id']);
            },
        );
        $f3->clear('ROUTES');
        $mark = microtime(true);
        $msg = 'Even if you fail, try to learn something from it.';
        $f3->route('GET /nothrottle', function ($f3) use($msg) {
            echo $msg;
        });
        ob_start();
        $f3->mock('GET /nothrottle');
        $out = ob_get_clean();
        $test->expect(
            ($elapsed = microtime(true) - $mark) || true,
            'Page rendering baseline: '.
            sprintf('%.3f', $elapsed * 1e3).'ms',
        );
        $f3->clear('ROUTES');
        $mark = microtime(true);
        $f3->route(
            'GET /throttled',
            function ($f3) use ($msg) {
                echo $msg;
            },
            0, /* don't cache */
            $throttle = 4, /* 8Kbps */
        );
        ob_start();
        $f3->mock('GET /throttled');
        $out = ob_get_clean();
        $elapsed = microtime(true) - $mark;
        $test->expect(
            $out === $msg,
            'Same page throttled @'.$throttle.'Kbps '.
            '(~'.(1000 / $throttle).'ms): '.
            sprintf('%.3f', $elapsed * 1e3).'ms',
        );

        $f3->set('results', $test->results());
        $f3->copy('ROUTES_bak', 'ROUTES');
    }

}
