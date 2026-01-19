<?php

beforeEach(function () {
    $this->view = \F3\View::instance();
    $this->f3->UI = 'ui/';
});

$raw = '<&>"\'ä';

test('variable encoding', function () use($raw) {
    $escaped = "&lt;&amp;&gt;&quot;'ä";
    $escapedTwice = "&amp;lt;&amp;amp;&amp;gt;&amp;quot;'ä";
    expect($this->view->esc($raw))
        ->toBe($escaped, 'encoding')
        ->and($this->view->esc($this->view->esc($raw)))
        ->toBe($escapedTwice, 'Double encoding')
        ->and($this->view->raw($escaped))
        ->toBe($raw, 'decoding')
        ->and($this->view->raw($this->view->raw($escapedTwice)))
        ->toBe($raw, 'Double decoding')
        ->and($this->view->esc(['<foo>', 'foo' => ['<bar>']]))
        ->toBe(['&lt;foo&gt;', 'foo' => ['&lt;bar&gt;']], 'nested encoding');
});

$escaped = "&lt;&amp;&gt;&quot;'ä";

it('renders a template', function ($file, $expected) use($raw) {
    $this->f3->set('test', $raw);
    expect($this->view->render($file))
        ->toBe($expected);
})->with([
    'simple require' => [
        'view/test0.php', $escaped.'-'.$escaped]
    ,
    'embedded view with implicit HIVE' => [
        'view/test1.php', $escaped.'-'.$escaped
    ],
    'Embedded view with custom HIVE based on escaped HIVE' => [
        'view/test2.php', $escaped.'-'.$escaped
    ],
    'Embedded view with full custom HIVE' => [
        'view/test3.php', $escaped.'-'.$raw
    ],
]);

test('Default HIVE is not empty', function () {
    expect($this->view->render('view/hive_size.php', null, null))
        ->toBeGreaterThan(0);
});

test('Empty custom HIVE', function () {
    expect($this->view->render('view/hive_size.php', null, []))
        ->toEqual(0);
});

test('sandbox variables', function () {
    expect($this->view->render(
        'view/hive_content.php',
        null,
        ['fw' => 1, 'hive' => 2, 'implicit' => 3, 'mime' => 4]
    ))->toBe('a:4:{s:2:"fw";i:1;s:4:"hive";i:2;s:8:"implicit";i:3;s:4:"mime";i:4;}');
});

test('sandbox variables empty', function () {
    expect($this->view->render(
        'view/hive_content.php',
        null,
        []
    ))->toBe('a:0:{}');
});

test('sandbox $this', function () {
    expect($this->view->render('view/test4.php', null, []))
        ->toEqual('1x');
    $this->f3->foo = 'bar';
    expect($this->view->render('view/test5.php', null, []))
        ->toEqual('bar');
});

test('render cache', function ($val, $ttl, $sleep, $expected) {
    $this->f3->CACHE = 'folder=tmp/cache/';
    $file = 'view/cache.php';
    sleep($sleep);
    expect($this->view->render(
        file: $file,
        hive: ['value' => $val],
        ttl: $ttl
    ))->toBe($expected);
})->with([
    'don\'t cache' => ['nope', 0, 0, 'nope'],
    'check no cache' => ['yes', 0, 0, 'yes'],
    '2sec cache' => ['cold', 2, 0, 'cold'],
    'load cached' => ['warm', 2, 0, 'cold'],
    'replace outdated' => ['cold_again', 3, 2, 'cold_again'],
]);

afterAll(function () {
    foreach (glob('tmp/cache/*') as $file)
        unlink($file);
});