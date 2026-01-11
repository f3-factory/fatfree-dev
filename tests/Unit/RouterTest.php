<?php

use F3\Base;
use F3\Http\RequestType;
use F3\Http\Response;
use F3\Http\ServerRequest;
use F3\Http\Stream;
use F3\Http\Verb;

test('content returned', function ($sandbox) {
    $this->f3->route('GET /test', function() {
        return 'content returned';
    });
    $out = $this->f3->mock('GET /test', sandbox: $sandbox);
    expect($this->f3->RESPONSE)->toBeEmpty();
    expect($out)->toEqual('content returned');
})->with([true, false]);

test('content echoed', function ($sandbox) {
    $this->f3->route('GET /test', function() {
        echo 'content echoed';
    });
    $out = $this->f3->mock('GET /test', sandbox: $sandbox);
    expect($this->f3->RESPONSE)->toEqual('content echoed');
    expect($out)->toBeEmpty();
})->with([true, false]);

it('returns same framework instance in route handler', function () {
    $this->f3->route('GET /test', function (\F3\Base $f3) {
        expect($this->f3)->toBe($f3);
        return $f3->foo;
    });
    $this->f3->route('GET /test2', [TestRouter::class, 'simple']);
    $this->f3->foo = 'bar';
    $out = $this->f3->mock('GET /test');
    expect($out)->toEqual('bar');

    $out = $this->f3->mock('GET /test2');
    expect($out)->toEqual($this->f3);
    expect($out->foo)->toEqual($this->f3->foo);
});

it('creates reroute headers', function (){
    $this->f3->route('GET /test', function (\F3\Base $f3) {
        $f3->reroute('/rerouted', false, false);
    });
    $this->f3->mock('GET /test');

    $loc = \preg_grep('/Location: https?:\/\/.*?\/rerouted/', $this->f3->RESPONSE_HEADERS);
    expect($loc)->not()->toBeEmpty();
    $status = \preg_grep('/HTTP\/1.1 302 Found/', $this->f3->RESPONSE_HEADERS);
    expect($status)->not()->toBeEmpty();
});

it('can reroute permanently', function () {
    $this->f3->route('GET /test', function (\F3\Base $f3) {
        $f3->reroute('/rerouted', true, false);
    });
    $this->f3->mock('GET /test');
    $status = \preg_grep('/HTTP\/1.1 301 Moved Permanently/', $this->f3->RESPONSE_HEADERS);
    expect($status)->not()->toBeEmpty();
});

it('reroute halts execution', function () {
    $this->f3->route('GET /test', function (\F3\Base $f3) {
        $f3->reroute('/rerouted', exit: true);
        return 'foo-bar';
    });
    $out = $this->f3->mock('GET /test');
    $loc = \preg_grep('/Location: https?:\/\/.*?\/rerouted/', $this->f3->RESPONSE_HEADERS);
    expect($loc)->not()->toBeEmpty();
    expect($out)->not->toEqual('foo-bar');
    expect($this->f3->RESPONSE)->not->toEqual('foo-bar');
});

it('includes BASE in reroute uri', function () {
    $this->f3->BASE = '/subdir';
    $this->f3->route('GET /test', function (\F3\Base $f3) {
        $f3->reroute('/rerouted', true, false);
    });
    $this->f3->mock('GET /test');
    $loc = \preg_grep('/Location: https?:\/\/.*?\/subdir\/rerouted/', $this->f3->RESPONSE_HEADERS);
    expect($loc)->not()->toBeEmpty();
});

it('follow reroute in cli mode', function () {
    $this->f3->CLI = true;
    $this->f3->route('GET /test', function (\F3\Base $f3) {
        $f3->reroute('/rerouted');
    });
    $this->f3->route('GET /rerouted', function (\F3\Base $f3) {
        echo "rerouted";
    });
    $this->f3->mock('GET /test [cli]');
    expect($this->f3->RESPONSE)->toEqual('rerouted');
});

it('reroute trailing slash URIs', function () {
    $this->f3->route('GET /test', function (\F3\Base $f3) {
        return "ok";
    });
    $this->f3->mock('GET /test/');
    $loc = \preg_grep('/Location: https?:\/\/.*?\/test/', $this->f3->RESPONSE_HEADERS);
    expect($loc)->not()->toBeEmpty();
});

test('custom rerouting', function ($route, $p, $e) {
    $this->f3->ONREROUTE = function (string $url, bool $permanent, bool $exit) {
        $f3 = \F3\Base::instance();
        $f3->set('reroute', [$url, $permanent, $exit]);
    };
    $this->f3->reroute($route, $p, $e);
    expect($this->f3->get('reroute'))->toBe([$route, $p, $e]);
})->with([
    ['/foo?bar=baz', false, false],
    ['/foo?bar=baz', true, false],
    ['/foo?bar=baz', false, true],
]);

test('Route mock', function () {
    $mocked = false;
    $test_headers = [];
    $exp_headers = ['X-Foo' => 'Bar'];
    $th_uri = '';
    $ts_uri = '';
    $this->f3->route(
        'GET /mock',
        function (\F3\Base $f3) use (&$mocked, &$test_headers, &$th_uri, &$ts_uri) {
            $mocked = true;
            $f3->mocked = true;
            $test_headers = $f3->HEADERS;
            $th_uri = $f3->URI;
            $ts_uri = $f3->SERVER['REQUEST_URI'];
            $f3->header('X-Foo: Bar');
        },
    );
    $this->f3->mock('GET /mock', headers: $exp_headers);
    expect($mocked)->toBeTrue()
        ->and($this->f3->mocked)->toBeTrue();
    expect($test_headers)->toBe($exp_headers)
        ->and($this->f3->HEADERS)->toBe($exp_headers);
    expect($this->f3->URI)->toEqual('/mock')
        ->and($th_uri)->toEqual('/mock')
        ->and($ts_uri)->toEqual('/mock');
    expect($this->f3->RESPONSE_HEADERS)
        ->toContain('X-Foo: Bar');
});

test('Route mock in sandbox', function () {
    $mocked = false;
    $test_headers = [];
    $orig_headers = $this->f3->HEADERS;
    $exp_headers = ['X-Foo' => 'Bar'];
    $os_uri = $_SERVER['REQUEST_URI']; // $_SERVER intentional!
    $oh_uri = $this->f3->URI;
    $th_uri = '';
    $ts_uri = '';
    $this->f3->mocked = false;
    $this->f3->route(
        'GET|POST /mock',
        function (\F3\Base $f3) use (&$mocked, &$test_headers, &$th_uri, &$ts_uri) {
            $mocked = true;
            $f3->mocked = true;
            $test_headers = $f3->HEADERS;
            $th_uri = $f3->URI;
            $ts_uri = $f3->SERVER['REQUEST_URI'];
            $f3->header('X-Foo: Bar');
        },
    );
    $this->f3->mock('GET /mock', headers: $exp_headers, sandbox: true);
    expect($mocked)->toBeTrue()
        ->and($this->f3->mocked)->toBeFalse();
    expect($test_headers)->toBe($exp_headers)
        ->and($this->f3->HEADERS)->toBe($orig_headers);
    expect($this->f3->URI)->toEqual($oh_uri)
        ->and($th_uri)->toEqual('/mock')
        ->and($ts_uri)->toEqual('/mock');
    expect($this->f3->SERVER['REQUEST_URI'])
        ->toEqual($os_uri, 'REQUEST_URI should not be altered in sandbox mode');
    // Response headers hydrated
    expect($this->f3->RESPONSE_HEADERS)->toContain('X-Foo: Bar');
});

it('resolves named routes', function () {
    $this->f3->route('GET|POST @hello:/named', function (\F3\Base $f3) {
        $f3->set('bar', 'foo');
    });
    $this->f3->mock('GET @hello');
    expect($this->f3->get('bar'))->toEqual('foo');
    expect($this->f3->get('ALIASES.hello'))
        ->toBe('/named', 'Named route retrieved');
});

test('alias() resolves named routes', function () {
    $this->f3->route('GET @complex:/resize/@format/*/sep/*', 'App->nowhere');
    expect($this->f3->alias('complex', 'format=20x20,*=[foo/bar,baz.gif]'))
        ->toBe('/resize/20x20/foo/bar/sep/baz.gif');

    expect($this->f3->alias('complex', 'format=20x20,*=[foo,bar]', ['x' => 123, 'y' => ['z' => 2]]))
        ->toBe('/resize/20x20/foo/sep/bar?x=123&y%5Bz%5D=2');
});

test('alias() generation with BASE', function ($prepend, $baseHandling, $basePath, $expected) {
    $this->f3->ABSOLUTE_ALIAS = $prepend;
    $this->f3->BASE = $basePath;
    $this->f3->route('GET @simple:/test/route', 'App->nowhere');
    expect($this->f3->alias('simple', baseHandling: $baseHandling))->toBe($expected);
})->with([
    'prepend, no BASE path' =>       [true,  true,  '',        '/test/route'],
    'prepend, with BASE' =>          [true,  true,  '/subdir', '/subdir/test/route'],
    'no prepend, no BASE path' =>    [false, true,  '',        'test/route'],
    'no prepend, with BASE' =>       [false, true,  '/subdir', 'test/route'],
    'skip handling, no BASE' =>      [true,  false, '',        '/test/route'],
    'skip handling, with BASE' =>    [true,  false, '/subdir', '/test/route'],
    'skip handling #2, no BASE' =>   [false, false, '',        '/test/route'],
    'skip handling #2, with BASE' => [false, false, '/subdir', '/test/route'],
]);

test('rerouting to alias', function ($route, $expected) {
    $this->f3->route('GET @hello:/', 'App->somewhere');
    $this->f3->route('GET @complex:/resize/@format/*/sep/*', 'App->nowhere');
    $this->f3->ONREROUTE = function ($url) {
        $f3 = Base::instance();
        $f3->set('reroute', $url);
    };
    $this->f3->reroute($route);
    expect($this->f3->reroute)->toBe($expected);
})->with([
    ['@hello', '/'],
    ['@hello?x=789', '/?x=789'],
    ['@complex(format=20x20,*=[foo/bar,baz.gif])', '/resize/20x20/foo/bar/sep/baz.gif'],
    ['@complex(format=20x20,*=[foo/bar,baz.gif])?x=789', '/resize/20x20/foo/bar/sep/baz.gif?x=789'],
    [['complex', ['format' => '20x20', '*' => ['foo/bar', 'baz.gif']]], '/resize/20x20/foo/bar/sep/baz.gif'],
    // with page fragment
    ['@hello#foo', '/#foo'],
    ['@hello?x=789#foo', '/?x=789#foo'],
    ['@complex(format=20x20,*=[foo/bar,baz.gif])#foo', '/resize/20x20/foo/bar/sep/baz.gif#foo'],
    ['@complex(format=20x20,*=[foo/bar,baz.gif])?x=789#foo', '/resize/20x20/foo/bar/sep/baz.gif?x=789#foo'],
    [['complex', 'format=20x20,*=[foo/bar,baz.gif]', ['x' => 789], 'foo'], '/resize/20x20/foo/bar/sep/baz.gif?x=789#foo'],
]);

test('mock with payload', function () {
    $this->f3->route('GET|POST @hello:/', function (\F3\Base $f3) {
        $f3->set('payload', $f3->REQUEST);
    });

    $this->f3->mock('GET @hello', ['foo' => 'bar']);
    expect($this->f3->payload)->toEqual(['foo' => 'bar']);
    $this->f3->clear('payload');
    $this->f3->mock('POST @hello', ['bar' => 'baz']);
    expect($this->f3->payload)->toEqual(['bar' => 'baz']);
});

it('resolves wildcard routing pattern', function () {
    $this->f3->route(['GET /wild/*', 'GET /wild/*/page/*'], function () {});
    $this->f3->mock('GET /wild/dangerous/beast?at=large');
    expect($this->f3->get('PARAMS.*'))->toBe('dangerous/beast');
});

it('resolves multiple wildcard routing patterns', function () {
    $this->f3->route(['GET /wild/*', 'GET /wild/*/page/*'], function () {});
    $this->f3->mock('GET /wild/dangerous/beast/page/fourty/seven');
    expect($this->f3->get('PARAMS.*.0'))->toBe('dangerous/beast')
        ->and($this->f3->get('PARAMS.*.1'))->toBe('fourty/seven');
});

test('Alias generated with encoded default PARAMS', function () {
    $this->f3->route('GET @wildPage:/a/*/b/@c/*', function ($f3) {});
    $this->f3->mock('GET /a/foo%25bar/x/b/2/bäz');
    expect($this->f3->alias('wildPage'))->toBe('/a/foo%25bar/x/b/2/b%C3%A4z');
    expect($this->f3->get('PARAMS.*.0'))->toBe('foo%bar/x')
        ->and($this->f3->get('PARAMS.c'))->toBe('2')
        ->and($this->f3->get('PARAMS.*.1'))->toBe('bäz');
});

it('detects request type', function () {
    $this->f3->route('GET|POST / [ajax]', function ($f3) {
        $f3->set('type', 'ajax');
    });
    $this->f3->route('GET|POST / [sync]', function ($f3) {
        $f3->set('type', 'sync');
    });
    $this->f3->route('GET|POST / [cli]', function ($f3) {
        $f3->set('type', 'cli');
    });
    $this->f3->mock('GET /');
    expect($this->f3->type)->toBe('sync', 'Synchronous HTTP request');
    $this->f3->mock('GET / [ajax]');
    expect($this->f3->type)->toBe('ajax', 'AJAX request');
    $this->f3->mock('GET / [cli]');
    expect($this->f3->type)->toBe('cli', 'CLI request');
});

it('Routes to regular namespaced function', function () {
    $this->f3->route('GET /', __NAMESPACE__.'\please');
    $this->f3->mock('GET /');
    expect($this->f3->send)->toBe('money');
});

test('supported methods', function ($verb) {
    $this->f3->map('/dummy', \NS\C::class);
    $this->f3->mock($verb.' /dummy', ['a' => 'hello']);
    expect($this->f3->get('route'))->toBe($verb);
    if (($verb === 'GET' || $verb === 'HEAD')
        && $this->f3->get('body')) {
        expect(parse_url($this->f3->get('URI'), PHP_URL_QUERY))
            ->not->toBeEmpty();
    }
})->with(Verb::names());

test('Request body available', function () {
    $this->f3->map('/dummy', \NS\C::class);
    $this->f3->mock('PUT /dummy', body: '123');
    expect($this->f3->exists('body'))->toBeTrue()
        ->and($this->f3->body)->toBe('123');

    $this->f3->mock('PUT /dummy', ['foo' => 'bar', 'one' => 'two']);
    expect($this->f3->exists('body'))->toBeTrue()
        ->and($this->f3->body)->toBe('foo=bar&one=two');
});

test('HTTP OPTIONS request returns allowed methods', function () {
    $this->f3->map('/dummy', \NS\C::class);
    $this->f3->mock('OPTIONS /dummy');
    $found = array_first(preg_grep('/^Allow:/', $this->f3->RESPONSE_HEADERS));
    expect($found)->toBe('Allow: '.implode(',', Verb::names()));
});

test('405 returns allowed methods', function () {
    $this->f3->route('POST|PUT /dummy', function (\F3\Base$f3) {
        //
    });
    $this->f3->HALT = false;
    expect(function () {
        $this->f3->mock('GET /dummy', throw: true);
    })->toThrow(\Exception::class, 'HTTP 405', 'fetch 405 error');

    $headerlist = $this->f3->RESPONSE_HEADERS;
    expect($headerlist)
        ->toContain('Allow: POST,PUT')
        ->toContain('HTTP/1.1 405 Method Not Allowed');
});

test('405 returns allowed methods - map', function () {
    $this->f3->map('/dummy-map', TestRouterMap::class);
    $this->f3->HALT = false;

    expect(function () {
        $this->f3->mock('GET /dummy-map', throw: true);
    })->toThrow(\Exception::class, 'HTTP 405', 'fetch 405 error');

    $headerlist = $this->f3->RESPONSE_HEADERS;
    expect($headerlist)
        ->toContain('Allow: POST,PUT')
        ->toContain('HTTP/1.1 405 Method Not Allowed');
});

test('HTTP OPTIONS request returns user-specified methods', function () {
    $this->f3->route('OPTIONS /dummy', function (\F3\Base$f3) {
        $f3->header('X-App: FooBar 3.2');
    });
    $this->f3->mock('OPTIONS /dummy');
    expect($this->f3->RESPONSE_HEADERS)->toContain('X-App: FooBar 3.2');
});

it('captures parameters in route', function () {
    $this->f3->route('GET @grub:/food/@id', function ($f3, $args) {
        $f3->set('id', $args['id']);
    });
    $this->f3->set('PARAMS.id', 'fish');
    $this->f3->mock('GET @grub');

    expect($this->f3->get('PARAMS.id'))->toBe('fish')
        ->and($this->f3->id)->toBe('fish');

    $this->f3->mock('GET @grub(@id=bread)');
    expect($this->f3->id)->toBe('bread', 'Different parameter in route');

    $this->f3->route('GET|POST|PUT @grub:/food/@id/@quantity', function ($f3, $args) {
        $f3->set('id', $args['id']);
        $f3->set('quantity', $args['quantity']);
    });
    $this->f3->mock('GET @grub(@id=beef,@quantity=789)');
    expect($this->f3->id)->toBe('beef')
        ->and($this->f3->quantity)->toBe('789', 'Multiple parameters');
});

test('Query string mocked', function () {
    $this->f3->route('GET|POST|PUT @grub:/food/@id/@quantity', function ($f3, $args) {
        $f3->set('id', $args['id']);
        $f3->set('quantity', $args['quantity']);
    });
    $this->f3->mock('GET /food/macademia-nuts/253?a=1&b=3&c=5');
    expect($this->f3->get('PARAMS.id'))->toBe('macademia-nuts')
        ->and($qty = $this->f3->get('PARAMS.quantity'))->toBeNumeric()
        ->and($qty)->toBe('253');
    expect($this->f3->GET)->toBe(['a' => '1', 'b' => '3', 'c' => '5']);

    $this->f3->mock('GET /food/chicken/999?d=246&e=357', ['f' => 468]);
    expect($this->f3->GET)
        ->toBe(['d' => '246', 'e' => '357', 'f' => 468], 'Query string and mock arguments merged');
    expect($this->f3->get('id'))->toBe('chicken')
        ->and($this->f3->get('quantity'))
        ->toBe('999', 'Route parameters captured along with query');
});

test('mock POST request has correct body and GET, POST, REQUEST globals', function () {
    $this->f3->route('POST @grub:/food/@id/@quantity', function ($f3, $args) {
        $f3->set('id', $args['id']);
        $f3->set('quantity', $args['quantity']);
    });
    $this->f3->mock('POST /food/sushki/134?a=1', ['b' => 2]);

    expect($this->f3->GET)->toBe(['a' => '1'])
        ->and($this->f3->POST)->toBe(['b' => 2])
        ->and($this->f3->REQUEST)->toBe(['a' => '1', 'b' => 2])
        ->and($this->f3->BODY)->toBe('b=2');
});

test('mock PUT request has correct body and GET, POST, REQUEST globals', function () {
    $this->f3->route('PUT @grub:/food/@id/@quantity', function ($f3, $args) {
        $f3->set('id', $args['id']);
        $f3->set('quantity', $args['quantity']);
    });
    $this->f3->mock('PUT /food/sushki/134?a=1', ['b' => 2]);

    expect($this->f3->GET)->toBe(['a' => '1'])
        ->and($this->f3->POST)->toBe([])
        ->and($this->f3->REQUEST)->toBe(['a' => '1'])
        ->and($this->f3->BODY)->toBe('b=2');
});

test('Mocked request body precedence over arguments', function () {
    $this->f3->route('POST @grub:/food/@id/@quantity', function ($f3, $args) {
        $f3->set('id', $args['id']);
        $f3->set('quantity', $args['quantity']);
    });
    $this->f3->mock('POST /food/sushki/134?a=1', ['b' => 2], body: 'c=3');

    expect($this->f3->GET)->toBe(['a' => '1'])
        ->and($this->f3->POST)->toBe(['b' => 2])
        ->and($this->f3->REQUEST)->toBe(['a' => '1', 'b' => 2])
        ->and($this->f3->BODY)->toBe('c=3');
});

test('Unicode characters in URL (PCRE version: '.PCRE_VERSION.')', function () {
    $this->f3->route('GET @grub:/food/@id/@quantity', function ($f3, $args) {
        $f3->set('id', $args['id']);
        $f3->set('quantity', $args['quantity']);
    });
    $this->f3->mock('GET @grub(@id=%C3%B6%C3%A4%C3%BC,@quantity=123)');
    expect($this->f3->get('id'))->toBe('öäü')
        ->and($this->f3->quantity)->toBe('123');
});

test('Route precedence order', function () {
    $this->f3->route('GET /*', 'NS\C->get');
    $this->f3->route('GET /', 'NS\C->get');
    $this->f3->route('GET /@a', 'NS\C->get');
    $this->f3->route('GET /foo*', 'NS\C->get');
    $this->f3->route('GET /foo', 'NS\C->get');
    $this->f3->route('GET /foo/*', 'NS\C->get');
    $this->f3->route('GET /foo/@a.htm', 'NS\C->get');
    $this->f3->route('GET /foo/@b', 'NS\C->get');
    $this->f3->route('GET /foo/0', 'NS\C->get');
    $this->f3->route('GET /foo/bar', 'NS\C->get');
    $this->f3->mock('GET /dummy');
    expect(array_keys($this->f3->get('ROUTES')))
        ->toBe([
            '/foo/bar',
            '/foo/0',
            '/foo/@a.htm',
            '/foo/@b',
            '/foo/*',
            '/foo',
            '/foo*',
            '/',
            '/@a',
            '/*',
        ]);
});

test('Route throttling', function () {
    $mark = microtime(true);
    expect($this->f3->QUIET)->toBeFalse();
    expect($this->f3->NONBLOCKING)->toBeFalse('route throttling in worker mode is not supported');
    $msg = 'Even if you fail, try to learn something from it.';

    $this->f3->route('GET /nothrottle', function () use($msg) {
        echo $msg;
    });
    $this->f3->mock('GET /nothrottle');
    expect($nothrottle = microtime(true) - $mark)
        ->toBeGreaterThan(0, 'Page rendering baseline: '.sprintf('%.1f', $nothrottle * 1e3).'ms')
        ->and($this->f3->RESPONSE)->toBe($msg);

    $mark = microtime(true);
    $this->f3->route('GET /throttled', function () use($msg) {
            echo $msg;
        },
        ttl: 0, /* don't cache */
        kbps: $throttle = 4,
    );
    $this->f3->mock('GET /throttled');
    expect($throttled = microtime(true) - $mark)
        ->toBeGreaterThan($nothrottle, 'route throttled @'.$throttle.'Kbps (~'.(1000 / $throttle).'ms): '
            .sprintf('%.1f', $throttled * 1e3).'ms')
        ->and($throttled)->toBeGreaterThan(0.25)
        ->and($this->f3->RESPONSE)->toBe($msg);
});

it('uses DNSBL lookup', function () {
    $this->f3->DNSBL = ['bl.spamcop.net'];
//    $this->f3->DNSBL = ['bl.spamcop.net', 'bsb.spamlookup.net', 'multi.surbl.org'];
    $this->f3->set('blocked', true);
    $this->f3->route('GET /forum', function ($f3) {
        $f3->set('blocked', false);
    });
    $mark = microtime(true);
    $this->f3->mock('GET /forum');
    expect($this->f3->get('blocked'))
        ->toBeFalse()
        ->and(sprintf('%.1f', (microtime(true) - $mark) * 1e3))
        ->toBeGreaterThan(0.1);
});

describe('CORS', function () {

    beforeEach(function () {
        $this->f3->route('GET|POST /cors-test', function () {
            return 'cors';
        });
        $this->f3->CORS['origin'] = '*';
        $this->f3->CORS['credentials'] = true;
        $this->f3->CORS['expose'] = ['X-Version', 'Foo'];
        $this->f3->CORS['headers'] = ['X-App', 'X-Platform'];
        $this->f3->CORS['ttl'] = 60;
    });

    test('Preflight headers', function () {
        $test_headers = [
            'Access-Control-Request-Method' => 'GET',
            'Origin' => 'localhost',
        ];
        $this->f3->mock('OPTIONS /cors-test', null, $test_headers);
        $headerlist = $this->f3->RESPONSE_HEADERS;
        expect($headerlist)
            ->toContain('Access-Control-Allow-Origin: *')
            ->toContain('Access-Control-Allow-Methods: OPTIONS,GET,POST')
            ->toContain('Access-Control-Allow-Headers: X-App,X-Platform')
            ->toContain('Access-Control-Allow-Credentials: true')
            ->toContain('Access-Control-Max-Age: 60');
    });

    test('Ajax request', function () {
        $this->f3->route('GET|POST /cors-test-ajax [ajax]', function () {
            return 'cors';
        });
        // preflight request
        $out = $this->f3->mock(
            'OPTIONS /cors-test-ajax [ajax]',
            headers: [
                'Access-Control-Request-Method' => 'GET',
                'Origin' => 'localhost',
            ],
            sandbox: true
        );
        expect($out)->toBeFalse()
            ->and($this->f3->RESPONSE_HEADERS)
            ->toContain('Access-Control-Allow-Origin: *');

        // actual request
        $out = $this->f3->mock(
            'GET /cors-test-ajax [ajax]',
            headers: ['Origin' => 'localhost'],
            sandbox: true
        );
        expect($out)->toBe('cors');

        $out = $this->f3->mock(
            'GET /cors-test',
            headers: ['Origin' => 'localhost'],
            sandbox: true
        );
        expect($out)->toBe('cors')
            ->and($this->f3->RESPONSE_HEADERS)
            ->toContain('Access-Control-Allow-Origin: *')
            ->toContain('Access-Control-Expose-Headers: X-Version,Foo');
    });

});

describe('PSR7', function () {
    beforeEach(function () {
        $this->f3->CONTAINER = \F3\Service::instance();
        \F3\Http\MessageFactory::registerDefaults();
    });

    test('Route call with Container', function () {
        $this->f3->route('GET /psr7-test/@foo', [TestRouter::class, 'v4']);
        $this->f3->mock('GET /psr7-test/bar', sandbox: false);
        $args = $this->f3->get('args');
        expect($args[0])->toBeInstanceOf(Base::class)
            ->and($args[1])->toBeArray()
            ->and($args[1]['foo'])->toBe('bar')
            ->and($args[2])->toBeArray()
            ->and($args[2])->toBe([TestRouter::class, 'v4']);
    });

    test('Route call with Container, no types', function () {
        $this->f3->route('GET /psr7-test/@foo', [TestRouter::class, 'v3']);
        $this->f3->mock('GET /psr7-test/baz', sandbox: false);
        $args = $this->f3->get('args');
        expect($args[0])->toBeInstanceOf(Base::class)
            ->and($args[1])->toBeArray()
            ->and($args[1]['foo'])->toBe('baz')
            ->and($args[2])->toBeArray()
            ->and($args[2])->toBe([TestRouter::class, 'v3']);
    });

    test('Request & Response injected', function () {
        $this->f3->route('GET /psr7-test/@foo', [TestRouter::class, 'injectTest']);
        $this->f3->mock('GET /psr7-test/baz', headers: ['X-Test' => 'testing']);
        $args = $this->f3->get('args');

        expect($args[0])->toBeInstanceOf(ServerRequest::class)
            ->and($args[0]->getMethod())->toBe('GET')
            ->and($args[0]->getHeaderLine('X-Test'))->toBe('testing')
            ->and($args[1])->toBeInstanceOf(Response::class)
            ->and($args[2])->toBeArray()
            ->and($args[2]['foo'])->toBe('baz')
            ->and($args[3])->toBeArray()
            ->and($args[3])->toBe([TestRouter::class, 'injectTest']);
    });

    test('Response Message', function () {
        $this->f3->route('GET /test', function(ServerRequest $request, Response $response, Base $f3) {
            return $response->withBody(new Stream('foo-bar'));
        });

        $out = $this->f3->mock('GET /test');
        expect($out)->toBeInstanceOf(Response::class);

        $stream = $out->getBody();
        expect($stream)->toBeInstanceOf(Stream::class);
        /** @var \F3\Http\Stream $stream */
        $stream->rewind();
        expect($stream->getContents())->toBe('foo-bar');

        expect($this->f3->RESPONSE)->toBe($out);
    });

    test('Request Headers', function () {
        $this->f3->route('GET /test', function(ServerRequest $request) {
            expect($request->getMethod())->toBe('GET');
            expect($request->getRequestTarget())->toBe('/test?foo=bar');
            expect($request->getQueryParams())->toBe(['foo' => 'bar']);
        });
        $this->f3->mock('GET /test?foo=bar');
    });

    test('Request factory hydrates from Hive', function () {
        $this->f3->route('GET /test', function(ServerRequest $request, Base $f3) {
            expect($request->getRequestTarget())->toBe('/test?foo=bar');

            $r2 = $f3->make(ServerRequest::class);
            expect($r2->getRequestTarget())->toBe('/test?foo=bar');
        });
        $this->f3->mock('GET /test?foo=bar');
    });

    test('Response Headers', function () {
        $this->f3->route('GET /test', function(ServerRequest $request, Response $response, Base $f3) {
            return $response->withHeaders([
                'X-App' => 'foo-bar',
                'Location' => '/rerouted',
            ])->withStatus(301);
        });
        $out = $this->f3->mock('GET /test');

        expect($out)->toBeInstanceOf(Response::class);
        $headers = $out->getHeaders();
        expect($headers)
            ->toHaveKey('Location')
            ->and($headers['Location'][0])->toBe('/rerouted')
            ->and($headers)
            ->toHaveKey('X-App')
            ->and($headers['X-App'][0])->toBe('foo-bar');

        expect($this->f3->RESPONSE_HEADERS)
            ->toContain('Location: /rerouted')
            ->toContain('X-App: foo-bar')
            ->toContain('HTTP/1.1 301 Moved Permanently');
    });

});

describe('Middleware', function () {

    it('registers a middleware', function () {
        expect($this->f3->MIDDLEWARES)->toBeEmpty();
        $this->f3->middleware('GET /api*', 'Middleware::handle');
        expect($m=$this->f3->MIDDLEWARES)->not->toBeEmpty('middleware added');
        expect($this->f3->MIDDLEWARES)->toHaveCount(1);
        expect($this->f3->MIDDLEWARES)->toHaveKey('/api*');
        expect(isset($this->f3->MIDDLEWARES['/api*'][0]['GET']))->toBeTrue();

        $this->f3->middleware([
            'GET /api/user',
            'GET /api/notes',
        ], 'Middleware::handle');
        expect($this->f3->MIDDLEWARES)->toHaveCount(3);
    });

    test('register respects route type', function () {
        $this->f3->middleware('GET /migrate* [cli]', 'Middleware::handle');
        expect($this->f3->MIDDLEWARES)->toHaveCount(1);
        expect($this->f3->MIDDLEWARES)->toHaveKey('/migrate*');
        expect(isset($this->f3->MIDDLEWARES['/migrate*'][\F3\HTTP\RequestType::CLI->value]['GET']))->toBeTrue();
    });

    it('registers as tag', function () {
        $this->f3->middleware(['auth'], 'Middleware::handle');
        expect($this->f3->MIDDLEWARES)->toHaveCount(1);
        expect($this->f3->MIDDLEWARES)->toHaveKey('#auth');
    });

    it('calls a middleware', function () {
        expect($this->f3->MIDDLEWARES)->toBeEmpty();
        $executed = false;
        $this->f3->middleware([
            'GET /api*',
        ], function ($next) use (&$executed) {
            $executed = true;
            return $next();
        });
        expect($this->f3->MIDDLEWARES)->not->toBeEmpty('middleware added');

        $this->f3->route('GET /api', function () {});
        $this->f3->mock('GET /api');
        expect($executed)->toBeTrue('middleware was called');
    });

    it('respects routing precedence + tags', function () {
        $_SERVER['REQUEST_URI'] = '';
        $out = [];
        $this->f3->middleware('GET /*', function ($next) use (&$out) {
            $out[]="outer";
            return $next();
        });
        $this->f3->middleware('GET /api*', function ($next) use (&$out) {
            $out[]="inner";
            return $next();
        });
        $this->f3->middleware('auth', function ($next) use (&$out) {
            $out[]="auth";
            return $next();
        });
        $this->f3->middleware('api', function ($next) use (&$out) {
            $out[]="api";
            return $next();
        });
        $this->f3->route('GET /api/user', function () use (&$out) {
            $out[]="route";
        }, tags: ['auth', 'api']);

        $this->f3->route('GET /api/cars', function () use (&$out) {
            $out[]="route";
        }, tags: ['api', 'auth']);

        $this->f3->mock('GET /api/user');
        expect($out)->toBe(['auth', 'api', 'outer', 'inner', 'route']);

        $out = [];
        $this->f3->mock('GET /api/cars');
        expect($out)->toBe(
            ['api', 'auth', 'outer', 'inner', 'route'],
            'it respects route tag order'
        );
    });

    it('buffers output across all handlers', function () {
        $this->f3->middleware('GET /*', function ($next) {
            echo "outer";
            return $next();
        });
        $this->f3->middleware('GET /api', function ($next) {
            echo "inner";
            return $next();
        });
        $this->f3->route('GET /api', function () {
            echo "route";
            return "api-result";
        });
        $out = $this->f3->mock('GET /api');

        expect($out)->toBe('api-result', 'return contains return value');
        expect($this->f3->RESPONSE)->toBe('outerinnerroute');

        $this->f3->middleware('GET /pre*', function ($next) {
            return "pre".$next();
        });
        $this->f3->route('GET /pre/foo', function () {
            return "foo";
        });
        $out = $this->f3->mock('GET /pre/foo');
        expect($out)->toBe('prefoo', 'return value adds up');
    });

    test('pre run headers', function () {
        $this->f3->CONTAINER = \F3\Service::instance();
        $this->f3->middleware('GET /*', function (\Closure $next, \F3\Base $f3) {
            $f3->header('X-App: F3 v4');
            return $next();
        });
        $this->f3->route('GET /foo/bar', function () {
            echo "foo";
        });
        $this->f3->mock('GET /foo/bar');
        expect($this->f3->RESPONSE_HEADERS)->toContain('X-App: F3 v4');
    });

    it('respects CORS enabled within middleware', function () {
        $this->f3->CONTAINER = \F3\Service::instance();

        $this->f3->middleware('OPTIONS /api/*', function (\Closure $next, \F3\Base $f3) {
            $f3->CORS['origin'] = 'https://fatfreeframework.com';
            return $next();
        });

        $this->f3->route('GET /api/foo', function () {
            echo "foo";
        });
        $this->f3->mock('OPTIONS /api/foo', headers: [
            'Access-Control-Request-Method' => 'GET',
            'Origin' => 'localhost',
        ], throw: true);
        expect($this->f3->RESPONSE_HEADERS)
            ->toContain('Access-Control-Allow-Origin: https://fatfreeframework.com')
            ->toContain('Access-Control-Allow-Methods: OPTIONS,GET');
    });


    test('PSR7 request + response', function () {
        $this->f3->CONTAINER = \F3\Service::instance();
        \F3\Http\MessageFactory::registerDefaults();

        $this->f3->middleware('GET /*', function (\Closure $next, \F3\Base $f3, ServerRequest $request, Response $response) {
            expect($request->getHeader('X-Foo-1'))->toBe(['bar']);
            $response = $response->withHeader('X-Version', $f3->VERSION);
            // if a PSR7 Response is used, you have to pass it to the $next route executor
            return $next($response);
        });
        $this->f3->middleware('GET /api', function (\Closure $next, \F3\Base $f3, ServerRequest $request, Response $response) {
            expect($request->getHeader('X-Foo-1'))->toBe(['bar']);
            $response = $response->withHeader('X-App', 'F3 v4');
            return $next($response);
        });
        $this->f3->route('GET /api', function (\F3\Base $f3, Response $response) {
            return $response->withBody(new Stream('Hallo World!'));
        });

        /** @var Response $out */
        $out = $this->f3->mock('GET /api', headers: ['X-Foo-1' => 'bar']);
        $body = $out->getBody();
        $body->rewind();

        expect($out)
            ->toBeInstanceOf(Response::class)
            ->and($body->getContents())
                ->toBe('Hallo World!', 'response contains return value')
            ->and($out->getHeader('X-App'))->toBe(['F3 v4'])
            ->and($out->getHeader('X-Version'))->toBe([$this->f3->VERSION])
            // Response headers unpacked from PSR7
            ->and($this->f3->RESPONSE_HEADERS)
                ->toContain('X-App: F3 v4')
                ->toContain('X-Version: '.$this->f3->VERSION);
    });

});

class TestRouter {
    public function simple(\F3\Base $f3)
    {
        return $f3;
    }

    function v3($f3, $params, $handler)
    {
        \F3\Base::instance()->set('args', func_get_args());
    }

    function v4(\F3\Base $f3, array $params, array $handler)
    {
        \F3\Base::instance()->set('args', func_get_args());
    }

    function injectTest(ServerRequest $request, Response $response, array $params, array $handler)
    {
        \F3\Base::instance()->set('args', func_get_args());
    }

}

class TestRouterMap {
    public function post() {}
    public function put() {}
}

function please($f3)
{
    $f3->set('send', 'money');
}