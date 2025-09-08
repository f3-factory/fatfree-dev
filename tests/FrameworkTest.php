<?php

namespace Tests;

use F3\Base;
use F3\Registry;
use stdClass;

pest()->extend(CoreTestCase::class);

test('testing dir', function () {
    expect(getcwd())
        ->toBe('/var/www/html');
});

describe('Registry', function () {
    test($t1 = 'set and receive items', function () {
        expect(Registry::exists('Foo'))
            ->toBeFalse('item does not exist');
        Registry::set('Foo', new stdClass());
        expect(Registry::exists('Foo'))->toBeTrue('Object was set');
    });

    test($t2 = 'clear item', function () {
        Registry::set('Foo', new stdClass());
        Registry::clear('Foo');
        expect(Registry::exists('Foo'))->toBeFalse('Object cleared');
    })->depends($t1);

    test('reset storage', function () {
        Registry::set('A', 'foo');
        Registry::set('B', 'bar');
        expect(Registry::get('A'))
            ->toBe('foo')
            ->and(Registry::get('B'))->toBe('bar');

        Registry::reset();
        expect(Registry::exists('A'))
            ->toBeFalse()
            ->and(Registry::exists('B'))->toBeFalse();
    })->depends($t2);
});


test('Framework boots', function () {
    $fw = Base::instance();
    expect($fw)->toBeInstanceOf(Base::class);
    expect(Registry::exists(Base::class))->toBeTrue('Singleton instance registered');
})->depends('`Registry` → reset storage');


describe('error handling', function () {

    it('provides error information', function () {
        $fw = Base::instance();
        $fw->HALT = false;
        $fw->DEBUG = 0;

        expect($fw->CLI)->toBeTrue();
        $fw->error(500, 'foo bar');
        $err = '==================================='.PHP_EOL
            .'ERROR 500 - Internal Server Error'.PHP_EOL
            .'foo bar';
        expect(trim($fw->RESPONSE))->toBe($err);
    });

    it('default error handler formats', function () {
        $fw = Base::instance();
        $fw->HALT = false;
        $fw->DEBUG = 0;
        $fw->CLI = false;

        expect($fw->CLI)->toBeFalse();
        $fw->error(500, 'foo bar');

        $err = <<<HTML
<!DOCTYPE html>
<html>
<head><title>500 Internal Server Error</title></head>
<body>
<h1>Internal Server Error</h1>
<p>foo bar</p>
</body>
</html>
HTML;

        expect(trim($fw->RESPONSE))->toBe($err);
    });

    it('DEBUG information', function () {
        $fw = Base::instance();
        $fw->HALT = false;
        $fw->DEBUG = 1;
        $fw->CLI = false;

        expect($fw->CLI)->toBeFalse();
        $fw->error(500, 'foo bar');

        expect($fw->RESPONSE)
            ->toContain('<pre>')
            ->and($fw->RESPONSE)->toContain('F3\Base->error')
            ->and($fw->RESPONSE)->toContain(basename(__FILE__));
    });

    it('re-throws exception', function () {
        $fw = Base::instance();
        $fw->ONERROR = function(Base $fw) {
            throw new \Exception($fw->ERROR['text']);
        };
        $fw->error(500, 'foo bar');
    })->throws(\Exception::class, 'foo bar');

    it('re-throws explicit exception', function () {
        $fw = Base::instance();
        $fw->ONERROR = function(Base $fw) {
            if ($fw->EXCEPTION)
                throw $fw->EXCEPTION;
            throw new \Exception($fw->ERROR['text']);
        };
        $fw->route('GET /', function() {
            throw new \RuntimeException('not found');
        });
        $fw->mock('GET /');
    })->throws(\RuntimeException::class, 'not found');

    it('mock throws exception directly', function () {
        $fw = Base::instance();
        $fw->route('GET /', function() {
            throw new \RuntimeException('not found');
        });
        $fw->mock('GET /', throw: true);
    })->throws(\RuntimeException::class, 'not found');
});
