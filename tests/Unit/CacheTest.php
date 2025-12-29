<?php

test('Cache disabled', function () {
    expect($this->f3->CACHE)->toBeFalse();
});

test('Cache backend detected', function () {
    $this->f3->CACHE = true;
    expect($this->f3->CACHE)->toBeString()
        ->not->toBe('invalid');
});

test('Invalid backend specified (fallback invoked)', function () {
    $this->f3->CACHE = 'invalid';
    expect($this->f3->CACHE)->toBeString()
        ->not->toBe('invalid');
});

test('Same Cache instance returned', function () {
    $this->f3->CACHE = true;
    $cache=\F3\Cache::instance();
    expect($cache)
        ->toBeInstanceOf(\F3\Cache::class)
        ->and($cache)->toBe(\F3\Cache::instance());
});

it('disables the active CACHE', function () {
    $this->f3->CACHE = true;
    $cache=\F3\Cache::instance();
    $cache->set('foo', 'bar', 1);
    expect($cache->engine())->not->toBeFalse();
    expect($cache->get('foo'))->toBe('bar');
    $cache->load(false);
    expect($cache->engine())->toBeFalse();
    expect($cache->exists('foo'))->toBeFalse();
    $cache->load(true);
    expect($cache->exists('foo'))->not->toBeFalse();
});

dataset('backends', function () {
    $backends = [
        'folder' => 'folder=tmp/cache/'
    ];
    if (extension_loaded('apcu')) {
        $backends['apcu'] = 'apcu';
    }
    if (extension_loaded('memcache')) {
        $backends['memcache'] = 'memcache=f3-memcached';
    }
    if (extension_loaded('memcached')) {
        $backends['memcached'] = 'memcached=f3-memcached';
    }
    if (extension_loaded('memcached')) {
        $backends['redis'] = 'redis=f3-redis';
    }
    return $backends;
});

test('Cache backend loaded', function ($backend) {
    $cache = new \F3\Cache();
    expect($cache)->toBeInstanceOf(\F3\Cache::class);
    $cache->load($backend);
    $parts = explode('=', $backend, 2);
    $found = preg_match('/^'.$parts[0].'=?$/', $this->f3->CACHE);
    expect($found)->not->toBeFalse();
    expect($cache->engine())->toBe($parts[0]);
})->with('backends');

test('Cache backend loaded via CACHE var', function ($backend) {
    $this->f3->CACHE = $backend;
    $parts = explode('=', $backend, 2);
    $found = preg_match('/^'.$parts[0].'=?$/', $this->f3->CACHE);
    expect($found)->not->toBeFalse();
})->with('backends');

test('Retrieve previously cached entry', function ($backend) {
    $this->f3->clear('CACHE');
    $this->f3->CACHE = $backend;
    $cache=\F3\Cache::instance();

    $cache->set($this->f3->hash('foo').'.var','bar',1);
    expect($this->f3->get('foo'))
        ->toBe('bar');
})->with('backends');

test('Retrieve cache entry details', function ($backend) {
    $this->f3->clear('CACHE');
    $this->f3->CACHE = $backend;
    $cache=\F3\Cache::instance();

    $cache->set($this->f3->hash('foo').'.var','bar',1);
    $inf=$this->f3->exists('foo',$val);
    expect($val)
        ->toBe('bar');
    expect($inf)->toBeArray()
        ->and($inf[0])->toBeFloat()
        ->and($inf[1])->toBe(1);
})->with('backends');

test('cache entry datatypes', function ($backend, $value) {
    $this->f3->clear('CACHE');
    $this->f3->CACHE = $backend;
    $cache=\F3\Cache::instance();
    $ttl = 1;
    $cache->set('value',$value, $ttl);
    if (is_object($value)) {
        expect($cache->get('value'))
            ->toBeInstanceOf($value::class);
    } else {
        expect($cache->get('value'))
            ->toBe($value);
    }
})->with('backends')->with([
    'Integer' => [1],
    'Float' => [2.34],
    'Boolean' => [true],
    'Array' => [['hello','world']],
    'String' => ['bar'],
    'Object' => [new \stdClass()],
]);

test('cache entry updated', function ($backend) {
    $this->f3->clear('CACHE');
    $this->f3->CACHE = $backend;
    $cache=\F3\Cache::instance();
    $ttl = 1;
    $cache->set('foo','bar', $ttl);
    expect($cache->get('foo'))->toBe('bar');
    $cache->set('foo','baz', $ttl);
    expect($cache->get('foo'))->toBe('baz');

})->with('backends');

test('cache entry cleaned', function ($backend) {
    $this->f3->clear('CACHE');
    $this->f3->CACHE = $backend;
    $cache=\F3\Cache::instance();
    $ttl = 1;
    $cache->set('foo','bar', $ttl);
    expect($cache->get('foo'))->toBe('bar');
    $cache->clear('foo');
    expect($cache->exists('foo'))->toBeFalse();
    expect($cache->get('foo'))->toBeFalse();
})->with('backends');

test('cache key expired', function ($backend) {
    $this->f3->clear('CACHE');
    $this->f3->CACHE = $backend;
    $cache=\F3\Cache::instance();
    $ttl = 1;
    $cache->set('foo','bar', $ttl);
    expect($cache->get('foo'))->toBe('bar');
    usleep(1.1e6*$ttl);
    expect($cache->exists('foo'))->toBeFalse();
})->with('backends');

test('Cache reset', function ($backend) {
    $this->f3->CACHE = $backend;
    $cache=\F3\Cache::instance();
    $ttl = 30;
    $cache->set('a',1, $ttl);
    $cache->set('b',2.54, $ttl);
    $cache->set('c',true, $ttl);
    $cache->set('d','stringy', $ttl);

    expect($cache->get('a'))->toBe(1)
        ->and($cache->get('b'))->toBe(2.54)
        ->and($cache->get('c'))->toBe(true)
        ->and($cache->get('d'))->toBe('stringy');

    if (str_starts_with($this->f3->CACHE, 'memcache')) {
        // waiting for memcached async storage
        sleep(1);
    }
    $cache->reset();

    if (str_starts_with($this->f3->CACHE, 'memcache')) {
        // waiting for memcached async deletion
        sleep(1);
    }
    expect($cache->exists('a'))->toBeFalse()
        ->and($cache->exists('b'))->toBeFalse()
        ->and($cache->exists('c'))->toBeFalse()
        ->and($cache->exists('d'))->toBeFalse();

})->with('backends');

it('caches a route', function ($backend) {
    $this->f3->CACHE = $backend;
    $cache=\F3\Cache::instance();
    $ttl = 1;
    $this->f3->route('GET /dummy', function (\F3\Base $f3) {
        echo "Message of the day at: ".$f3->format('{0, time, full}', time());
    }, $ttl);
    $hash=$this->f3->hash('GET '.$this->f3->get('BASE').'/dummy').'.url';

    $this->f3->mock('GET /dummy', sandbox: TRUE);
    $response1 = $this->f3->RESPONSE;
    expect($cache->exists($hash))->not->toBeFalse();
    $this->f3->mock('GET /dummy', sandbox: TRUE);
    $response2 = $this->f3->RESPONSE;

    expect($response1)->toBe($response2);
    expect($cache->exists($hash))->not->toBeFalse('Cached route still fresh');
    usleep(1.1e6*$ttl);

    expect($cache->exists($hash))->toBeFalse('Cached route expired');

    $this->f3->mock('GET /dummy', sandbox: TRUE);
    $response3 = $this->f3->RESPONSE;
    expect($cache->exists($hash))->not->toBeFalse('cache refreshed');
    expect($response3)->not->toBe($response2);

})->with('backends');

describe('Cache-Based Session Handler', function () {

    test('Session handler', function () {
        $this->f3->CACHE = true;
        $session = new \F3\Session();
        expect($session->sid())->toBeNull('Cache-based session instantiated but not started');

        $this->f3->set('SESSION.foo','hello world');
        expect($sid=$session->sid())->toBeString('Cache-based session started: '.$sid);

        expect(session_status())->toBe(PHP_SESSION_ACTIVE);
        expect($this->f3->get('SESSION.foo'))->toBe('hello world');
        session_write_close();
        expect($session->sid())->toBeNull('Cache-based session written and closed');
        expect(session_status())->toBe(PHP_SESSION_NONE);

        $_SESSION=[];
        expect($this->f3->get('SESSION.foo'))->toBe('hello world', 'Session variable retrieved from cache');
    })->with('backends');

    test('Session handler fails without cache', function () {
        $this->f3->CACHE = false;
        expect(function () {
            new \F3\Session();
        })->toThrow(\Exception::class, \F3\Session::E_NO_CACHE);
    });

    test('Session details, IP address', function () {
        $this->f3->CACHE = true;
        $this->f3->IP = '127.0.0.1';
        $session = new \F3\Session();
        $this->f3->set('SESSION.foo','hello world');
        session_write_close();
        expect($session->ip())->toBe('127.0.0.1');
        $this->f3->clear('SESSION');
    });

    test('Session details, Timestamp', function () {
        $time = time();
        $this->f3->CACHE = true;
        $session = new \F3\Session();
        $this->f3->set('SESSION.foo','hello world');
        session_write_close();
        expect($session->stamp())->toBeString()
        ->and(date('d-m-Y-H-i', $time))->toBe(date('d-m-Y-H-i'));
    });

    test('Session details, User agent', function () {
        $this->f3->CACHE = true;
        $this->f3->set('HEADERS.User-Agent', 'foobar');
        $session = new \F3\Session();
        $this->f3->set('SESSION.foo','hello world');
        session_write_close();
        expect($session->agent())->toBeString()
            ->toBe('foobar');
        $this->f3->clear('SESSION');
    });

    test('Session details, CSRF', function () {
        $this->f3->CACHE = true;
        $session = new \F3\Session();
        $this->f3->set('SESSION.foo','hello world');
        session_write_close();
        expect($session->csrf())->toBeString();
    });

    test('Session destroyed and cookie expired', function () {
        $this->f3->CACHE = true;
        $session = new \F3\Session();
        $this->f3->set('SESSION.foo','hello world');
        $sid=$session->sid();
        session_write_close();

        $before=$this->f3->get('COOKIE.PHPSESSID');
        $this->f3->clear('SESSION');
        $after=$this->f3->get('COOKIE.PHPSESSID');
        $cache=\F3\Cache::instance();
        expect(empty($this->f3->SESSION))->toBeTrue();
        expect($cache->exists($sid.'@'))->toBeFalse();
        expect($before)->toBe($sid);
        expect($after)->toBeNull();
        expect(empty($this->f3->COOKIE[session_name()]))->toBeTrue();
    });

});