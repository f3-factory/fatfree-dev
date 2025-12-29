<?php

beforeEach(function () {
    $this->binary = null;
    if (function_exists('exec'))
        foreach (['php', 'php-cli'] as $cmd) {
            exec($cmd.' -v 2>&1', $out, $ret);
            if ($ret == 0 && preg_match('/cli/', @$out[0], $out)) {
                $this->binary = $cmd;
                break;
            }
        }

    $this->exec = function($str) {
        exec($this->binary.' tests/classes/cli.php '.$str, $out, $ret);
        return $ret == 0 ? $out[0] : false;
    };
});

it('detects php cli binary', function () {
    expect($this->binary)->not->toBeNull();
});

test('Web-style argument (HTTP request)', function () {
    $x = $this->exec;
    expect($x('/web?foo=bar'))
        ->toBe('<h1>Web</h1>foo=bar');
});

test('Console-style arguments', function () {
    $x = $this->exec;
    expect($x('log show'))
        ->toBe('show');
});

test('Console-style options', function () {
    $x = $this->exec;
    expect($x('debug uri'))
        ->toBe('/debug/uri')
    ->and($x('debug uri -a=1 --name=foo'))
        ->toBe('/debug/uri?a=1&name=foo')
    ->and($x('debug get -a=1 --name=foo'))
        ->toBe('a:1,name:foo');
});

test('Console-style flags', function () {
    $x = $this->exec;
    expect($x('debug uri -a -b --force'))
        ->toBe('/debug/uri?a=&b=&force=');
});

test('Console-style combined flags', function () {
    $x = $this->exec;
    expect($x('debug uri -abc=1 -d=2'))
        ->toBe('/debug/uri?a=&b=&c=1&d=2');
});

test('The position of options doesn\'t matter', function () {
    $x = $this->exec;
    expect($x('debug -a=1 uri -b=2'))
        ->toBe('/debug/uri?a=1&b=2');
});

test('Default route', function () {
    $x = $this->exec;
    expect($x(''))
        ->toBe('Home')
    ->and($x('--color=blue'))
        ->toBe('Home is blue');
});