<?php

test('ROOT (document root)', function () {
    expect(is_dir($root = $this->f3->ROOT))
        ->toBeTrue('ROOT is valid directoy: '.$root);
});

test('REALM (Full canonical URI)', function () {
    expect($realm = $this->f3->get('REALM'))
        ->not()->toBeEmpty($realm);
});

test('VERB (request method)', function () {
    expect($this->f3->get('VERB'))
        ->toBe($_SERVER['REQUEST_METHOD']);
});

test('SCHEME (Web protocol)', function () {
    expect($this->f3->get('SCHEME'))
        ->toBe('http');
});

test('HOST (Web host/domain)', function () {
    expect($this->f3->get('HOST'))
        ->not()->toBeEmpty();
});

test('BASE (path to index.php relative to ROOT)', function () {
    expect($this->f3->get('BASE'))
        ->toBe('');
});

test('URI (request URI)', function () {
    expect($this->f3->get('URI'))
        ->toBe($this->f3->SERVER['REQUEST_URI']);
});

test('AJAX', function () {
    expect($this->f3->get('AJAX'))
        ->toBeFalse();

    $a1 = false;
    $this->f3->route('GET /', function () use (&$a1) {
        $a1 = $this->f3->AJAX;
    });
    $this->f3->mock('GET / [ajax]');
    expect($a1)->toBeTrue('mocked ajax with pattern mode');

    $a2 = false;
    $this->f3->route('GET /', function () use (&$a2) {
        $a2 = $this->f3->AJAX;
    });
    $this->f3->mock(
        'GET /',
        headers: ['X-Requested-With' => 'XMLHttpRequest'],
    );
    expect($a2)->toBeTrue('mocked ajax with headers');
});

test('ENCODING (character set)', function () {
    expect($this->f3->get('ENCODING'))->toBe('UTF-8');
});

test('Multibyte encoding', function () {
    if (!extension_loaded('mbstring')) {
        $this->markTestSkipped('mbstring extension not available');
    }
    expect(mb_internal_encoding())->toBe('UTF-8');
});

test('LANGUAGE detected', function () {
    ini_set('display_errors', 1);
    $this->f3->set(
        'getLang',
        (function () {
            return $this->languages;
        })->bindTo($this->f3, $this->f3),
    );
    $l = $this->f3->getLang();
    expect($l)
        ->toBeArray()
        ->and($l)->toContain('en');
});

test('TZ (Timezone)', function () {
    expect($this->f3->get('TZ'))->toBe(date_default_timezone_get());
});

test('Time zone adjusted: ', function () {
    $this->f3->set('TZ', $tz = 'America/New_York');
    expect($this->f3->get('TZ'))
        ->toBe(date_default_timezone_get())
        ->and($tz)->toBe(date_default_timezone_get());
});

describe('SERIALIZER', function() {

    test('default', function () {
        $obj = new stdClass();
        $obj->foo = 'bar';
        expect($this->f3->SERIALIZER)
            ->toBe('php');
        $s1 = $this->f3->serialize($obj);
        expect($s1)
            ->toBe('O:8:"stdClass":1:{s:3:"foo";s:3:"bar";}')
            ->and($o1 = $this->f3->unserialize($s1))
            ->toHaveProperty('foo')
            ->and($o1->foo)->toBe('bar');
    });

    test('igbinary', function () {
        $obj = new stdClass();
        $obj->foo = 'bar';
        expect($this->f3->SERIALIZER)
            ->toBe('php');
        $s1 = $this->f3->serialize($obj);
        expect(extension_loaded('igbinary'))->toBe(true);

        $this->f3->SERIALIZER = 'igbinary';
        $s2 = $this->f3->serialize($obj);
        expect(base64_encode($s2))
            ->toBe('AAAAAhcIc3RkQ2xhc3MUAREDZm9vEQNiYXI=')
            ->and($o2 = $this->f3->unserialize($s2))
            ->toHaveProperty('foo')
            ->and($o2->foo)->toBe('bar');
    });

});
