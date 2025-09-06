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