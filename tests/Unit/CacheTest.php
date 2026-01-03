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

it('uses SEED to prefix cache keys', function ($backend) {
    $ttl = 30;
    $cache = new F3\Cache();
    $cache->load(dsn: $backend, seed: $s1 = 'abcd1234');
    $cache->set('baz','stringy', $ttl);

    $cache2 = new F3\Cache();
    $cache2->load(dsn: $backend, seed: 'xyz987');
    expect($cache->exists('baz'))->not->toBeFalse();
    expect($cache2->exists('baz'))->toBeFalse();

    $this->f3->SEED = $s1;
    $this->f3->CACHE = $backend;
    expect(F3\Cache::instance()->exists('baz'))->not->toBeFalse();
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

    // waiting for memcached async storage
    if (str_starts_with($this->f3->CACHE, 'memcache'))
        sleep(1);

    $cache->reset();

    expect($cache->exists('a'))->toBeFalse()
        ->and($cache->exists('b'))->toBeFalse()
        ->and($cache->exists('c'))->toBeFalse()
        ->and($cache->exists('d'))->toBeFalse();

})->with('backends');

test('reset with key suffix', function ($backend) {
    $this->f3->SEED = 'test';
    $this->f3->CACHE = $backend;
    $cache = \F3\Cache::instance();
    $ttl = 30;
    $cache->set('value1.42.user',1, $ttl);
    $cache->set('value2.42.user',2.54, $ttl);
    $cache->set('value2.66.user',3.1415, $ttl);
    $cache->set('main.contents.3.pages','Lorem Ipsun', $ttl);
    $cache->set('location1.maps.3.pages',[53.91517894633916, 10.973163105778042], $ttl);

    expect($cache->exists('value1.42.user'))->not->toBeFalse();
    expect($cache->exists('value2.42.user'))->not->toBeFalse();

    // waiting for memcached async storage
    if (str_starts_with($this->f3->CACHE, 'memcache'))
        sleep(1);

    $cache->reset('42.user');

    expect($cache->exists('value1.42.user'))->toBeFalse();
    expect($cache->exists('value2.42.user'))->toBeFalse();
    expect($cache->exists('value2.66.user'))->not->toBeFalse();
    expect($cache->exists('main.contents.3.pages'))->not->toBeFalse();
    expect($cache->exists('location1.maps.3.pages'))->not->toBeFalse();

    $cache->reset('user');
    expect($cache->exists('value2.66.user'))->toBeFalse();

    $cache->reset('contents.3.pages');
    expect($cache->exists('main.contents.3.pages'))->toBeFalse();
    expect($cache->exists('location1.maps.3.pages'))->not->toBeFalse();

    $cache->reset('pages');
    expect($cache->exists('location1.maps.3.pages'))->toBeFalse();

})->with('backends');


it('executes remember with disabled cache', function () {
    $this->f3->CACHE = false;
    $cache=\F3\Cache::instance();
    expect($cache->engine())->toBeFalse();
    $value = $cache->remember('message', fn() => 'Hallo world', 10);
    expect($value)->toBe('Hallo world');
});

test('cache tags', function () {
    $this->f3->CACHE = true;
    $cache=\F3\Cache::instance();

    $func = fn() => 'Hallo world '.rand(1,1000);
    $value1 = $cache->remember('message', $func, [10, 'test']);
    $value2 = $cache->remember('message', $func, [10, 'test']);
    $value3 = $cache->remember('message', $func, [10, 'foo']);
    expect($value1)->toContain('Hallo world');
    expect($value2)->toBe($value1, 'function not executed twice');
    expect($value3)->not->toBe($value1);
    expect($cache->exists('message.test'))->not->toBeFalse();

    $cache->reset('test');
    expect($cache->exists('message.test'))->toBeFalse();
    expect($cache->exists('message.foo'))->not->toBeFalse();
});

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

    beforeEach(function () {
        $this->f3->CACHE = true;
    });

    test('Session handler', function () {
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
        $this->f3->IP = '127.0.0.1';
        $session = new \F3\Session();
        $this->f3->set('SESSION.foo','hello world');
        session_write_close();
        expect($session->ip())->toBe('127.0.0.1');
        $this->f3->clear('SESSION');
    });

    test('Session details, Timestamp', function () {
        $time = time();
        $session = new \F3\Session();
        $this->f3->set('SESSION.foo','hello world');
        session_write_close();
        expect($session->stamp())->toBeString()
        ->and(date('d-m-Y-H-i', $time))->toBe(date('d-m-Y-H-i'));
    });

    test('Session details, User agent', function () {
        $this->f3->set('HEADERS.User-Agent', 'foobar');
        $session = new \F3\Session();
        $this->f3->set('SESSION.foo','hello world');
        session_write_close();
        expect($session->agent())->toBeString()
            ->toBe('foobar');
        $this->f3->clear('SESSION');
    });

    test('Session details, CSRF', function () {
        $session = new \F3\Session();
        $session->csrfKey = 'CSRFToken';
        $this->f3->set('SESSION.foo','hello world');
        session_write_close();
        expect($session->csrf())->toBeString();
        expect($this->f3->get('CSRFToken'))->toBe($session->csrf());
    });

    test('Suspicion check', function ($type) {
        $this->f3->clear('SESSION');
        // initialise session
        $session = new \F3\Session();
        $this->f3->set('SESSION.foo','hello world');
        // persist session
        session_write_close();

        // alter user props
        if ($type === 'agent') {
            $this->f3->set('HEADERS.User-Agent', 'foobar');
        } else {
            $this->f3->IP = '127.0.0.1';
        }

        // reboot session handler (simulates 2nd request)
        $session = new \F3\Session();
        $session->threatLevelThreshold = 1;
        $session->onRead = function ($handler, $threatLevel) {
            $this->f3->set('threatLevel', $threatLevel);
        };
        expect(function () {
            $this->f3->set('SESSION.foo','hello world');
        })->toThrow(\Exception::class, 'HTTP 403');

        expect($session->threatLevelThreshold)->toBe(1);

    })->with(['agent','ip']);

    test('Custom Suspicion handler', function ($type) {
        $this->f3->clear('SESSION');
        // initialise session
        $session = new \F3\Session();
        $this->f3->set('SESSION.foo','hello world');
        // persist session
        session_write_close();

        // alter user props
        if ($type === 'agent') {
            $this->f3->set('HEADERS.User-Agent', 'foobar');
        } else {
            $this->f3->IP = '127.0.0.1';
        }

        $called = false;
        // reboot session handler (simulates 2nd request)
        $session = new \F3\Session();
        $session->onSuspect = function () use (&$called) {
            $called = true;
        };
        $session->threatLevelThreshold = 1;
        $this->f3->set('SESSION.foo','hello world');
        expect($called)->toBeTrue('Custom onSuspect handler');
    })->with(['agent','ip']);

    test('Session destroyed and cookie expired', function () {
        $session = new \F3\Session();
        $this->f3->set('SESSION.foo','hello world');
        $sid=$session->sid();
        session_write_close();

        $before=$this->f3->get('COOKIE.PHPSESSID');
        $this->f3->clear('SESSION');
        $after=$this->f3->get('COOKIE.PHPSESSID');
        $cache=\F3\Cache::instance();
        expect(empty($this->f3->SESSION))
            ->toBeTrue()
            ->and($cache->exists($sid.'@'))->toBeFalse()
            ->and($before)->toBe($sid)
            ->and($after)->toBeNull()
            ->and(empty($this->f3->COOKIE[session_name()]))->toBeTrue();
    });

    it('sanitizes user-agent', function () {
        $this->f3->clear('SESSION');
        $this->f3->set('HEADERS.User-Agent', 'foo_0 + ☆ 🥸�� (bar-;.:/)');
        $session = new \F3\Session();
        expect($session->agent())->toBe('foo_0 + (bar-;.:/)');
    });

    it('validates session cookie', function () {
        $this->f3->clear('SESSION');
        $this->f3->set('COOKIE.PHPSESSID', 'abc<";\x20');
        $session = new \F3\Session();
        expect($this->f3->exists('COOKIE.PHPSESSID'))->toBeFalse();
        $this->f3->session_start();
        expect($this->f3->exists('COOKIE.PHPSESSID', $val))->not->toBeFalse()
            ->and(preg_match('/^[a-zA-Z0-9]{24,256}$/', $val))->not->toBeFalse();
    });
});