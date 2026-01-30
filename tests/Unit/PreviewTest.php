<?php

beforeEach(function () {
    $this->tmpl = \F3\Preview::instance();
    $this->f3->UI = 'ui/';
    $this->f3->set('foo', 'bar->baz');
});

test('Auto-escaping enabled', function () {
    expect($this->tmpl->render('templates/test1.htm'))
        ->toBe('bar-&gt;baz');
});

test('Auto-escaping disabled for non-html/xml documents', function () {
    expect($this->tmpl->render('templates/test1.htm', 'text/plain'))
        ->toBe('bar->baz');
});

describe('resolve strings', function () {
    test('resolve sandbox', function () {
        expect(
            $this->tmpl->resolve(
                $node = '<p>{{ json_encode(array_keys(get_defined_vars())) }}</p>',
                [],
            ),
        )
            ->toBe('<p>[]</p>');
        expect($this->tmpl->resolve($node, ['foo' => 'bar']))
            ->toBe('<p>["foo"]</p>');
    });

    test('sandbox $this', function () {
        expect($this->tmpl->resolve('<p>{{ @this->level }}x</p>', []))
            ->toBe('<p>0x</p>');
        $this->f3->foo = 'bar';
        expect($this->tmpl->resolve('<p>{{ @this->fw->foo }}</p>', []))
            ->toBe('<p>bar</p>');
    });

    it('resolves template strings', function () {
        $this->f3->set('string', '<test>');
        $this->f3->set('ENV.content', '<ok>');

        expect($this->tmpl->resolve('<p>{{ @string }}</p>'))
            ->toBe('<p>&lt;test&gt;</p>')
            ->and($this->tmpl->resolve('<p>{{ @ENV.content }}</p>'))
            ->toBe('<p>&lt;ok&gt;</p>')
            ->and($this->tmpl->resolve('{* hello *}'))
            ->toBe('');
    });

    it($d1 = 'persists results', function () {
        $this->f3->SEED = 'testing';
        $this->f3->set('string', '<test>');
        expect($this->tmpl->resolve('<p>{{ @string }}</p>', persist: true))
            ->toBe('<p>&lt;test&gt;</p>')
            ->and(file_exists($tmpFile = 'tmp/testing.20pataqbl3y8w.php'))
            ->toBeTrue();
        unlink($tmpFile);
    });

    it('caches results', function ($persist) {
        $this->f3->SEED = 'testing';
        $this->f3->CACHE = true;
        $this->f3->set('string', '<test>');
        $node = '<p>{{ @string }}</p>';
        $hash = $this->f3->hash($this->f3->serialize($node));
        $cache = \F3\Cache::instance();
        expect($cache->exists($hash))
            ->toBeFalse('not cached')
            ->and(
                $this->tmpl->resolve(
                    node: $node,
                    ttl: 10,
                    persist: $persist,
                ),
            )->toBe('<p>&lt;test&gt;</p>')
            ->and($cache->exists($hash))->toBeTruthy('cached');
        $mockedCache = new class() extends \F3\Cache {
            public function exists($hash, mixed &$val = null): false|array
            {
                $val = 'cached-'.$hash;
                return [time(), 10];
            }
        };
        \F3\Registry::set(\F3\Cache::class, new $mockedCache());
        expect($this->tmpl->resolve($node, ttl: 10))
            ->toBe('cached-'.$hash, 'cache hit');
        \F3\Registry::clear(\F3\Cache::class);
        $this->f3->CACHE = true;
        expect(\F3\Cache::instance()->clear($hash))->toBeTrue();
        if ($persist)
            unlink('tmp/testing.20pataqbl3y8w.php');
    })->with([
        'always' => [false],
        'persist' => [true],
    ])->depends('it '.$d1);

    it('escapes resolve hive', function (bool $escape, bool $hive, string $expected) {
        if ($hive)
            $this->f3->set('string', '<foo>');
        expect(
            $this->tmpl->resolve(
                node: '<p>{{ @string }}</p>',
                hive: $hive ? null : ['string' => '<foo>'],
                escape: $escape,
            ),
        )
            ->toBe($expected);
    })->with([
        'escape' => [true, false, '<p>&lt;foo&gt;</p>'],
        'no escape' => [false, false, '<p><foo></p>'],
        'escape, global hive' => [true, true, '<p>&lt;foo&gt;</p>'],
        'no escape, global hive' => [false, true, '<p><foo></p>'],
    ]);
});

test('Tokenize expression', function ($expr, $eval) {
    expect($this->tmpl->token($expr))->toBe($eval);
})->with([
    ['@foo.bar', '$foo[\'bar\']'],
    ['@foo[bar]', '$foo[bar]'],
    ['@foo[\'bar\']', '$foo[\'bar\']'],
    ['@foo["bar"]', '$foo["bar"]'],
    ['@foo.0', '$foo[0]'],
    ['@foo.@bar', '$foo.$bar'],
    ['@foo[@bar]', '$foo[$bar]'],
    ['@foo->bar.baz', '$foo->bar[\'baz\']'],
    ['@foo->bar[baz]', '$foo->bar[baz]'],
    ['@foo->bar.@baz', '$foo->bar.$baz'],
    ['@foo->bar[@baz]', '$foo->bar[$baz]'],
    ['@foo->@baz', '$foo->$baz'],
    ['@foo::bar.baz', '$foo::bar[\'baz\']'],
    ['@foo::bar[baz]', '$foo::bar[baz]'],
    ['@foo::bar.@baz', '$foo::bar.$baz'],
    ['@foo::@baz', '$foo::$baz'],
    ['@foo::bar[@baz]', '$foo::bar[$baz]'],
    ['@foo.bar->baz', '$foo[\'bar\']->baz'],
    ['@foo.bar->@baz', '$foo[\'bar\']->$baz'],
    ['@foo->bar[@qux.baz]', '$foo->bar[$qux[\'baz\']]'],
    ['@foo->bar[@qux.@baz]', '$foo->bar[$qux.$baz]'],
    ['@foo()', '$foo()'],
    ['@foo(1.732)', '$foo(1.732)'],
    ['@foo()->bar', '$foo()->bar'],
    ['@foo.zip()', '$foo[\'zip\']()'],
    ['@foo.zip(@bar)', '$foo[\'zip\']($bar)'],
    ['@foo.zip(@bar,@baz)', '$foo[\'zip\']($bar,$baz)'],
    ['@foo.zip(@bar,\'qux\')', '$foo[\'zip\']($bar,\'qux\')'],
    ['@foo.substr(@bar,3)', '$foo.substr($bar,3)'],
    [
        '@foo->zip(@bar,\'qux\',123,[\'a\'=>\'hello\'])',
        '$foo->zip($bar,\'qux\',123,[\'a\'=>\'hello\'])',
    ],
    ['@foo[@bar+1].baz', '$foo[$bar+1][\'baz\']'],
    ['@foo.baz[@bar+1]', '$foo[\'baz\'][$bar+1]'],
    ['@foo.\'hello, world\'', '$foo.\'hello, world\''],
    [
        '@foo.bar.baz->qux.corge[@gra::@ult()](@arp)',
        '$foo[\'bar\'][\'baz\']->qux[\'corge\'][$gra::$ult()]($arp)',
    ],
    ['@foo | esc', '$this->esc($foo)'],
]);


describe('Template filters', function () {
    it('lists all filters', function () {
        expect($this->tmpl->filter())
            ->toContain('esc')
            ->toContain('raw')
            ->toContain('alias')
            ->toContain('esc');
    });

    it($t1 = 'registers a new filter', function () {
        $this->tmpl->filter('pick', [Helper::class, 'pick']);
        expect($this->tmpl->filter('pick'))
            ->toBe([Helper::class, 'pick'])
            ->and($this->tmpl->filter())
            ->toContain('pick');
    });

    it($d2 = 'resolves custom filter', function ($func, $expected) {
        $this->tmpl->filter('pick', $func);
        expect($this->tmpl->token('@foo | pick'))
            ->toBe($expected);
    })->with([
        'string' => ['\Helper2::instance()->pick', '\Helper2::instance()->pick($foo)'],
        'callable' => [
            [Helper::class, 'pick'],
            'F3\Base::instance()->call($this->filter(\'pick\'),[$foo])',
        ],
    ])->depends('it '.$t1);

    it('resolves custom filter with arguments', function ($token, $expected) {
        $this->tmpl->filter('pick', '\Helper2::instance()->pick');
        expect($this->tmpl->token($token))
            ->toBe($expected);
    })->with([
        'arg1' => ["@foo, 'bar' | pick", '\Helper2::instance()->pick($foo, \'bar\')'],
        'arg2' => ["@foo, 'bar', @bar | pick", '\Helper2::instance()->pick($foo, \'bar\', $bar)'],
        'pipe' => ["@foo, 'bar|baz' | pick", '\Helper2::instance()->pick($foo, \'bar|baz\')'],
    ])->depends('it '.$d2);

    test('syntax', function ($token, $expected) {
        $this->tmpl->filter('pick', '\Helper2::instance()->pick');
        expect($this->tmpl->token($token))->toBe($expected);
    })->with([
        'Double pipe OR condition' => [
            "@foo || @bar",
            '$foo || $bar',
        ],
        'Ternary condition with filter' => [
            "(@foo && @bar)? @baz: @qux | esc",
            '$this->esc(($foo && $bar)? $baz: $qux)',
        ],
        'Double pipe OR condition with filter' => [
            "(@foo || @bar) ? @baz : @qux | esc",
            '$this->esc(($foo || $bar) ? $baz : $qux)',
        ],
        'Multiple filter' => [
            "@foo | pick, esc",
            '$this->esc(\Helper2::instance()->pick($foo))',
        ],
        'Multiple filter, multiple arguments' => [
            "@foo, @bar | pick, esc",
            '$this->esc(\Helper2::instance()->pick($foo, $bar))',
        ],
        'Existing php function' => [
            "@foo | json_encode",
            'json_encode($foo)',
        ],
    ]);

    test('overwrite existing filter', function () {
        $this->tmpl->filter('json_encode', '\Helper::instance()->json');
        expect($this->tmpl->filter('json_encode'))
            ->toBe('\Helper::instance()->json');
    });
});

test('whitespace interpolation', function () {
    $tmpl = <<<HTML
title1: {{@myvar}}
#
title2: {{@myvar}}
#
HTML;
    $withInterpolation = <<<HTML
title1: foo
#
title2: foo
#
HTML;
    $this->f3->set('myvar', 'foo');
    $this->tmpl->interpolation(true); // default
    expect($this->tmpl->resolve($tmpl))
        ->toBe($withInterpolation, 'with interpolation');

    $this->tmpl->interpolation(false);
    $noInterpolation = <<<HTML
title1: foo#
title2: foo#
HTML;
    expect($this->tmpl->resolve($tmpl))
        ->toBe($noInterpolation, 'no interpolation');
});

test('render cache', function ($val, $ttl, $sleep, $expected) {
    $this->f3->CACHE = 'folder=tmp/cache/';
    $file = 'templates/cache.htm';
    sleep($sleep);
    expect(
        $this->tmpl->render(
            file: $file,
            hive: ['value' => $val],
            ttl: $ttl,
        ),
    )->toBe($expected);
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


class Helper
{
    public static function pick($val, $match)
    {
        return preg_grep('/'.$match.'/', $val);
    }
}


class Helper2
{
    use \F3\Prefab;

    public function pick($val, $match)
    {
        return preg_grep('/'.$match.'/', $val);
    }
}
