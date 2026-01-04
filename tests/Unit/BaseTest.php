<?php

describe('method calls', function () {

    it('Calls methods (NS\Class->method)', function () {
        $this->f3->call(TestObj::class.'->callee');
        expect($this->f3->get('called'))->toBeTrue();
    });

    it('Calls methods (PHP array format)', function () {
        $obj = new TestObj();
        $this->f3->call([$obj, 'callee']);
        expect($this->f3->get('called'))->toBeTrue();
    });

    it('Calls methods (PHP callable)', function () {
        $this->f3->call([TestObj::class, 'callee']);
        expect($this->f3->get('called'))->toBeTrue();
    });

    it('Calls methods (PHP function)', function () {
        $this->f3->call('callee');
        expect($this->f3->get('called'))->toBeTrue();
    });

    it('Calls lambda function', function () {
        $this->f3->call(function () {
            \F3\Base::instance()::instance()->set('called', true);
        });
        expect($this->f3->get('called'))->toBeTrue();
    });

    it('Calls Closure with inject', function () {
        $this->f3->CONTAINER = \F3\Service::instance();
        $func = fn(TestObj $obj, int $num) => [$obj, $num];

        [$o, $n] = $this->f3->call($func, ['num' => 123]);
        expect($o)
            ->toBeInstanceOf(TestObj::class)
            ->and($n)->toBe(123);

        [$s, $n] = $this->f3->call(fn(string $str, int $num)
            => [$str, $num], ['baz', 456]);

        expect($s)
            ->toBe('baz')
            ->and($n)->toBe(456);

        [$o, $n] = $this->f3->call($func, [1 => 789]);
        expect($o)
            ->toBeInstanceOf(TestObj::class)
            ->and($n)->toBe(789);

        [$o, $n] = $this->f3->call(fn(int $num, TestObj $obj)
            => [$obj, $num], [321]);
        expect($o)
            ->toBeInstanceOf(TestObj::class)
            ->and($n)->toBe(321);
    });

    test('Callback chain', function () {
        expect($this->f3->chain('a,b,c', [1]))->toBe([1,2,4]);
    });

    test('Callback relay', function () {
        expect($this->f3->relay('a,b,c', [1]))->toBe(8);
    });
});

describe('mutex lock', function () {

    it('executes mutex callback', function () {
        $out = $this->f3->mutex('mutex1', function () {
            return 'testing';
        });
        expect($out)->toBe('testing');
    });

    test($c1='CACHE driver', function () {
        $cache = new \F3\Cache(true);
        $this->f3->MUTEX = $cache;

        $out = $this->f3->mutex('mutex1', function () {
            return 'testing';
        });
        expect($out)->toBe('testing');
    });

    it('waits on locks', function () {
        $this->f3->CACHE = true;
        $cache = \F3\Cache::instance();
        $this->f3->MUTEX = $cache;
        $id = 'mutex2';
        $lockKey = $this->f3->hash($id).'.'.$this->f3->SEED.'.lock';
        $cache->set($lockKey, $id, 2); // simulate existing lock

        $mark = microtime(true);
        $out = $this->f3->mutex($id, function () {
            return 'testing';
        }, block: 5);

        expect($out)->toBe('testing');
        expect(microtime(true))->toBeGreaterThan($mark+2)
            ->toBeLessThan($mark+5);
    })->depends($c1);

    it('cleans stall locks', function () {
        $this->f3->CACHE = true;
        $cache = \F3\Cache::instance();
        $this->f3->MUTEX = $cache;
        $id = 'mutex2';
        $lockKey = $this->f3->hash($id).'.'.$this->f3->SEED.'.lock';
        $cache->set($lockKey, $id, 60); // simulate existing lock

        $blockTimeInSec = 3;
        $mark = microtime(true);
        $out = $this->f3->mutex($id, function () {
            return 'testing';
        }, block: $blockTimeInSec);

        expect($out)->toBe('testing');
        expect(microtime(true))->toBeGreaterThan($mark+$blockTimeInSec);
    })->depends($c1);

    test('CACHE driver with failure', function () {
        $cache = new \F3\Cache(false); // disabled cache
        $this->f3->MUTEX = $cache;
        expect(function() {
            $this->f3->mutex('mutex1', function () {
                return 'testing';
            });
        })->toThrow(\RuntimeException::class, 'Unable to obtain lock');
    });

    test('Custom driver', function () {
        $clazz = new class implements \F3\MutexHandler
        {
            public function mutex(
                string $id,
                callable|string $func,
                array $args = [],
                int $block = 300
            ): mixed {
                return $func($id, $args, $block);
            }
        };
        $this->f3->MUTEX = new $clazz();
        $out = $this->f3->mutex('mutex2', function () {
            return func_get_args();
        }, ['a','b', 2.54, false], 42);
        expect($out)->toBe(['mutex2', ['a','b', 2.54, false], 42]);
    });

    it('blocks parallel processing', function ($driver) {
        $this->f3->SEED = 'test';
        $this->f3->CACHE = 'redis=f3-redis'; // used by mutex.php to store result

        $cache = \F3\Cache::instance();
        $cache->clear('mutex1');
        $cache->clear('mutex2');

        $mark = microtime(true);
        exec('php tests/classes/mutex.php --driver='.$driver.' --t=1 > /dev/null 2>&1 &');
        usleep(100*1000);
        exec('php tests/classes/mutex.php --driver='.$driver.' --t=2 > /dev/null 2>&1 &');

        expect(microtime(true))->toBeLessThan($mark + 0.5, 'exec should execute async');
        expect($cache->exists('mutex1'))->toBeFalse('cache entry 1 should be delayed');
        expect($cache->exists('mutex2'))->toBeFalse('cache entry 2 should be delayed');

        while(($m1=$cache->get('mutex1')) === false) {
            usleep(0.1*1e6);
            if (microtime(true) > $mark + 10)
                break;
        }
        expect($m1)->not->toBeFalse();

        while(($m2=$cache->get('mutex2')) === false) {
            usleep(0.1*1e6);
            if (microtime(true) > $mark + 20)
                break;
        }
        expect($m2)->toBeGreaterThan($m1);

        expect($cache->get('mutex-driver'))->toBe($driver);

    })->with([
        'default', 'cache',
    ]);

});

class TestObj {
    public function callee(): void
    {
        \F3\Base::instance()->set('called', true);
    }
}

function callee()
{
    \F3\Base::instance()->set('called', true);
}

function a($x)
{
    return $x;
}

function b($y)
{
    return $y * 2;
}

function c($z)
{
    return $z * 4;
}
