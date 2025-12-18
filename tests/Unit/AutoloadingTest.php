<?php

test('Namespace search path is defined', function () {
    expect($this->f3->AUTOLOAD)->toBe('./');
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

    test('App Customer', function () {
        expect(class_exists('DTOs\Customer'))->toBeFalse();

        $this->f3->AUTOLOAD = './,./ns2/';

        expect(class_exists('DTOs\Customer'))->toBeTrue();
    });

    test('Customer Loader', function () {
        expect(class_exists('DTOs\User'))->toBeFalse();

        $this->f3->AUTOLOAD = ['./ns2/', function(string $namespace) {
            return $namespace.'.class';
        }];
        expect(class_exists('DTOs\User'))->toBeTrue();
    });

});

test('Root namespaced framework class F3\Cache is autoloadable', function () {
    expect(class_exists('\F3\Cache'))->toBeTrue();
});
