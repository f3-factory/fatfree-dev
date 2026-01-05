<?php

use F3\UTF;

beforeEach(function () {
    $this->utf = new UTF();
});

test('strlen', function () {
    expect($this->utf->strlen('⠊⠀⠉⠁⠝⠀⠑⠁⠞⠀⠛⠇⠁⠎⠎⠀⠁⠝⠙⠀⠊⠞'))
        ->toBe(22);
});

test('substr (at zero offset)', function () {
    expect($this->utf->substr('Я можу їсти скло', 0, 6))
        ->toBe('Я можу');
});

test('strrev', function () {
    expect($this->utf->strrev('Я можу їсти скло', 0, 6))
        ->toBe('олкс итсї ужом Я');
});

test('substr (at zero offset RTL-language)', function () {
    expect($this->utf->substr('أنا قادر على أكل الزجاج و هذا لا يؤلمني', 0, 8))
        ->toBe('أنا قادر');
});

test('substr (non-zero offset)', function () {
    expect($this->utf->substr('나는 유리를 먹을 수 있어요. 그래도', 3, 3))
        ->toBe('유리를');
});

test('substr (empty string)', function () {
    expect($this->utf->substr('', 7))->toBeFalse();
});

test('substr (negative offset)', function () {
    expect($this->utf->substr('איך קען עסן גלאָז און עס טוט מיר נישט װײ', -7))
        ->toBe('נישט װײ');
});

test('substr (negative offset and specified length)', function () {
    expect($this->utf->substr('El pingüino Wenceslao hizo kilómetros', -10, 4))
        ->toBe('kiló');
});

test('strpos', function () {
    expect($this->utf->strpos('Góa ē-tàng chia̍h po-lê', 'h'))
        ->toBe(12);
    expect($this->utf->strpos('Góa ē-tàng chia̍h po-lê', 'H'))
        ->toBeFalse();
});

test('stripos', function () {
    expect($this->utf->stripos('Góa ē-tàng chia̍h po-lê', 'H'))
        ->toBe(12);
});

test('strpos with offset', function () {
    expect($this->utf->strpos('123 456 789 123 4', '123', 7))
        ->toBe(12);
});

test('strstr (before needle)', function () {
    $str = 'ᛋᚳᛖᚪᛚ᛫ᚦᛖᚪᚻ᛫ᛗᚪᚾᚾᚪ᛫ᚷᛖᚻᚹᛦᛚᚳ᛫ᛗᛁᚳᛚᚢᚾ᛫ᚻᛦᛏ᛫ᛞᚫᛚᚪᚾ';
    expect($this->utf->strstr($str, 'ᛁᚳᛚᚢᚾ', true))
        ->toBe('ᛋᚳᛖᚪᛚ᛫ᚦᛖᚪᚻ᛫ᛗᚪᚾᚾᚪ᛫ᚷᛖᚻᚹᛦᛚᚳ᛫ᛗ');
});

test('strstr (after needle)', function () {
    $str = 'ᛋᚳᛖᚪᛚ᛫ᚦᛖᚪᚻ᛫ᛗᚪᚾᚾᚪ᛫ᚷᛖᚻᚹᛦᛚᚳ᛫ᛗᛁᚳᛚᚢᚾ᛫ᚻᛦᛏ᛫ᛞᚫᛚᚪᚾ';
    expect($this->utf->strstr($str, 'ᛁᚳᛚᚢᚾ'))
        ->toBe('ᛁᚳᛚᚢᚾ᛫ᚻᛦᛏ᛫ᛞᚫᛚᚪᚾ');
});

test('stristr (after needle)', function () {
    $str = 'Góa ē-tàng chia̍h po-lê';
    expect($this->utf->stristr($str, 'CHIA̍H'))
        ->toBe('chia̍h po-lê');
});

test('substr_count', function () {
    expect($this->utf->substr_count('Можам да јадам стакло, а не ме штета.', 'д'))
        ->toBe(2);
});

test('ltrim', function () {
    $str = "\xe2\x80\x83\x20#string#\xc2\xa0\xe1\x9a\x80";
    expect($this->utf->ltrim($str))
        ->toBe("#string#\xc2\xa0\xe1\x9a\x80");
});

test('rtrim', function () {
    $str = "\xe2\x80\x83\x20#string#\xc2\xa0\xe1\x9a\x80";
    expect($this->utf->rtrim($str))
        ->toBe("\xe2\x80\x83\x20#string#");
});

test('trim', function () {
    $str = "\xe2\x80\x83\x20#string#\xc2\xa0\xe1\x9a\x80";
    expect($this->utf->trim($str))
        ->toBe('#string#');
});


test('bom', function () {
    expect($this->utf->bom())
        ->toBe("\xef\xbb\xbf");
});

test('translate', function () {
    expect($this->utf->translate('\u263a'))
        ->toBe("☺");
});

test('emojify', function ($in, $out) {
    expect($this->utf->emojify($in))
        ->toBe($out);
})->with([
    [':(', '☹'],
    [':)', '☺'],
    ['<3', '♥'],
    [':D', '😃'],
    ['XD', '😆'],
    [';)', '😉'],
    [':P', '😋'],
    [':,', '😏'],
    [':/', '😣'],
    ['8O', '😲'],
]);
