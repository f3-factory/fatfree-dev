<?php


beforeEach(function () {
    $this->f3->config('app.ini');
});

it('reads variables', function () {
    expect($this->f3->get('test'))
            ->toBeEmpty('Empty string')
        ->and($this->f3->get('num'))
            ->toBe(123, 'Integer')
        ->and($this->f3->get('str1'))
            ->toBe('abc defg h ijk', 'Unquoted string literal')
        ->and($this->f3->get('str2'))
            ->toBe('abc', 'Quoted string literal')
        ->and($this->f3->get('multi'))
            ->toBe("this \nis a \nstring that spans \nseveral lines", 'Multi-line string')
        ->and($this->f3->get('list'))
            ->toBe([7, 8, 9], 'Ordinary array')
        ->and($this->f3->get('hash'))
            ->toBe(['x' => 1, 'y' => 2, 'z' => 3], 'Array with named keys')
        ->and($this->f3->get('mix'))
            ->toBe(['this', 123.45, false], 'Array with mixed elements');
});

it('preserves data types', function () {
    expect($this->f3->get('const'))
        ->toBeNull()
        ->and($this->f3->get('os'))
        ->toBe(PHP_OS, 'PHP constants')
        ->and($this->f3->get('long'))->toBe('12345678901234567890')
        ->and($this->f3->get('huge'))->toBe(12345678901234567890);
});

it('declares routes', function () {
    $routes = array_keys($this->f3->ROUTES);
    expect($routes)
        ->toContain('/go')
        ->toContain('/404')
        ->toContain('/inside/@series')
        ->toContain('/cached');

    expect($this->f3->get('ALIASES.named'))
        ->toBe('/404', 'Named route defined');

    expect($this->f3->exists('ROUTES./404.0.POST'))
        ->toBeTrue('Named route defined with an existing name');

    expect($this->f3->exists('ROUTES./tagged.0.GET'))->toBeTrue()
    ->and($this->f3->get('ROUTES./tagged.0.POST.4'))
        ->toBe(['tag1', 'tag2']);

    // ReST map declared
    expect($routes)->toContain('/map');
});

it('declares middlewares', function () {
    $middlewares = array_keys($this->f3->MIDDLEWARES);
    expect($middlewares)
        ->toContain('/api/*')
        ->toContain('#tag1')
        ->toContain('#tag2');
});

test('custom section', function () {
    expect($this->f3->get('section1.myvar'))
        ->toBe('myval1')
    ->and($this->f3->get('section2.myvar'))
        ->toBe('myval2')
    ->and($this->f3->get('section3.dummy'))
        ->toBe('HAIL THE CONQUERING HERO')
    ->and($this->f3->get('section3.great'))
        ->toBe('EXACTLY');

    expect($this->f3->get('section6.Кольцо Урала.baz'))
        ->toBe(1234, 'Custom section UTF8 support');
});

test('cached values', function () {
    $this->f3->CACHE = true;
    $cache = \F3\Cache::instance();
    $hash = $this->f3->hash('num').'.var';
    expect($cache->exists($hash, $val))
        ->toBeTruthy()
        ->and($val)
            ->toBe(123, 'Primitive value cached');
    $cache->clear($hash);

    $hash = $this->f3->hash('mix').'.var';
    expect($cache->exists($hash, $val))->toBeTruthy()
        ->and($val)->toBe(["this", 123.45, false], 'Array value cached');
    $cache->clear($hash);
});