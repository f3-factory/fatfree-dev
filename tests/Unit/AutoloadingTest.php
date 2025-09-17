<?php

test('No errors expected at this point', function () {
    expect(is_null($this->f3->get('ERROR')))
        ->toBeTrue();
});

test('Namespace search path is defined', function () {
    expect($this->f3->AUTOLOAD)->not()->toBeEmpty('autoload path: '.$this->f3->AUTOLOAD);
});

describe('Namespace class autoloading', function () {
    test('NS\C', function () {
        expect(class_exists('NS\C'))->toBeTrue();
    });

    test('NS\NS1\C', function () {
        expect(class_exists('NS\NS1\C'))->toBeTrue();
    });

    test('NS\NS2\C', function () {
        expect(class_exists('NS\NS2\C'))->toBeTrue();
    });

    test('NS\NS3\C', function () {
        expect(class_exists('NS\NS3\C'))->toBeTrue();
    });

    test('NS\NS3\NS4\C', function () {
        expect(class_exists('NS\NS3\NS4\C'))->toBeTrue();
    });

    test('NS\NS3\NS5\C', function () {
        expect(class_exists('NS\NS3\NS5\C'))->toBeTrue();
    });

    test('NS\NS6\NS7\C', function () {
        expect(class_exists('NS\NS6\NS7\C'))->toBeTrue();
    });
});

test('Root namespaced framework class F3\Cache is autoloadable', function () {
    expect(class_exists('\F3\Cache'))->toBeTrue();
});
