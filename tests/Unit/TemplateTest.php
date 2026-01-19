<?php

beforeEach(function () {
    $this->tmpl = \F3\Template::instance();
    $this->f3->UI = 'ui/';
    $this->f3->set('foo', 'bar');
    $this->f3->set('cond', true);
    $this->f3->set('file', 'templates/test1.htm');
});

test('<include>', function () {
    expect($this->tmpl->render('templates/test2.htm'))
        ->toBe('bar');
});

test('double <include> doesn\'t double encode', function () {
    $this->f3->set('foo', 'barré');
    expect($this->tmpl->render('templates/test2b.htm'))
        ->toBe('barrébarré');
});

test('<exclude> and {* comment *}', function () {
    expect($this->tmpl->render('templates/test3.htm'))
        ->toBe('barbar');
});

test('<repeat>', function () {
    $this->f3->set('div', [
        'coffee' => ['arabica', 'barako', 'liberica', 'kopiluwak'],
        'tea' => ['darjeeling', 'pekoe', 'samovar'],
    ]);
    $out = $this->tmpl->render('templates/test4.htm');

    expect(preg_replace('/[\t\r\n]/', '', $out))
        ->toBe('<div>'.
            '<p><span><b>coffee</b></span></p>'.
            '<p>'.
            '<span class="odd">arabica</span>'.
            '<span class="even">barako</span>'.
            '<span class="odd">liberica</span>'.
            '<span class="even">kopiluwak</span>'.
            '</p>'.
            '</div>'.
            '<div>'.
            '<p><span><b>tea</b></span></p>'.
            '<p>'.
            '<span class="odd">darjeeling</span>'.
            '<span class="even">pekoe</span>'.
            '<span class="odd">samovar</span>'.
            '</p>'.
            '</div>'
        );
});

test('<check>', function ($c1, $c2, $expected) {
    $this->f3->set('cond1', $c1);
    $this->f3->set('cond2', $c2);
    expect(trim($this->tmpl->render('templates/test5.htm')))
        ->toBe($expected);
})->with([
    [true, true, 'c1:T,c2:T'],
    [true, false, 'c1:T,c2:F'],
    [false, true, 'c1:F,c2:T'],
    [false, false, 'c1:F,c2:F'],
]);

test('<check> nullable return', function () {
    $this->f3->set('cond1', true);
    expect(trim($this->tmpl->render('templates/test5.2.htm')))
        ->toEqual(10);
});

test('unmatching tag cases', function () {
    $this->f3->set('cond1', true);
    expect(trim($this->tmpl->render('templates/test5.3.htm')))
        ->toEqual(10);
});

test('multiline tags', function () {
    $this->f3->set('cond1', true);
    $out = trim($this->tmpl->render('templates/test5.4.htm'));
    $out = preg_replace('/(\s)*/', '', $out);
    expect($out)->toBe('1010');
});

test('<switch>, <case>, <default>', function () {
    $this->f3->set('test', ['string' => 'thin', 'int' => 123, 'bool' => false]);
    $out = trim($this->tmpl->render('templates/test11.htm'));
    $out = preg_replace('/(\s)*/', '', $out);
    expect($out)->toBe('<em>thin</em>-1failed123124');
});

test('<loop> with embedded <include>', function () {
    $out = $this->tmpl->render('templates/test6.htm');
    $out = preg_replace('/[\t\r\n]/', '', $out);
    expect($out)->toBe('<div>'.
        '<p class="odd">1</p>'.
        '<p class="even">2</p>'.
        '<p class="odd">3</p>'.
        '</div>'.
        'Temporary variable preserved across includes');
});

test('<set>', function () {
    $out = $this->tmpl->render('templates/test8.htm');
    $out = preg_replace('/[\t\r\n]/', '', $out);
    expect($out)->toBe('<span>3</span>'.
        '<span>6</span>'.
        '<span>xyz</span>'.
        '<span>1</span>'.
        '<span>[1,3,5]</span>'.
        '<span>a</span>'.
        '<span>b</span>'.
        '<span>c</span>'.
        'email@address.com');
});

test('<ignore>', function () {
    $out = $this->tmpl->render('templates/test9.htm');
    $out = preg_replace('/[\t\r\n]/', '', $out);
    expect($out)->toBe('<script type="text/javascript">var a=\'{{a}}\';</script>');
});

test('Custom tag', function ($line, $expected) {
    $this->tmpl->extend('foo', fn($node) => $this->f3->stringify($node));
    $out = $this->tmpl->render('templates/test10.htm');
    $lines = array_map('trim', explode("\n", $out));
    expect($lines)
        ->toHaveKey($line)
        ->and($lines[$line])
        ->toBe($this->f3->stringify($expected));
})->with([
    'No child nodes' => [
        0, ['@attrib' => ['bar' => '123', 'baz' => 'abc']]
    ],
    'with child nodes' => [
        1, ['@attrib' => ['bar' => 'test2'], 'test2']
    ],
    'value-less attribute' => [
        2, ['@attrib' => ['bar' => 'test3', 'disabled' => null], 'test3']
    ],
    'hyphenated attribute' => [
        3, ['@attrib' => ['data-foo' => 'baz'], 'test4']
    ],
    'attribute containing token' => [
        4, ['@attrib' => ['foo' => '{{ @t1 }}'], 'param with token']
    ],
    'mixed attributes' => [
        5, ['@attrib' => ['bar' => '{{ @baz }}', 'baz' => 'abc'], 'multiple params']
    ],
    'mixed attributes (switched)' => [
        6, ['@attrib' => ['bar' => 'baz', 'baz' => '{{ @abc }}'], 'multiple params switched']
    ],
    'attribute containing template engine formatting' => [
        7, [
            '@attrib' => ['bar' => 'baz', 'class' => '{{ @class | esc }}'],
            'token with format',
        ]
    ],
    'inline token' => [
        8, ['@attrib' => ['{{ @param }}'], 'tag with inline token']
    ],
    'attribute and inline token' => [
        9, ['@attrib' => ['bar' => 'test10', '{{ @param }}'], 'param, inline token']
    ],
    'attributes, inline token, and ignored space' => [
        10, [
            '@attrib' => ['bar' => 'test11', 'rel' => 'foo', '{{ @param }}'],
            'params, inline token and space',
        ]
    ],
    'attribute, inline token, and another attribute' => [
        11, [
            '@attrib' => ['bar' => 'test12', '{{ @param }}', 'rel' => 'foo'],
            'param, token, param',
        ]
    ],
    'simple' => [
        12, ['@attrib' => ['bar' => 'test13'], 'simple tag']
    ],
    'inner HTML containing template token' => [
        13, ['@attrib' => ['bar' => 'test14'], 'this {{ @token }} should NOT get resolved']
    ],
    'tag spanning multiple lines' => [
        14, ['@attrib' => ['bar' => 'test15', 'baz' => 'abc'], 'multi-line start tag']
    ],
    'Node attribute with 0 value' => [
        15, ['@attrib' => ['value' => '0']]
    ],
    'Node attribute with empty value' => [
        16, ['@attrib' => ['bar' => null, 'baz' => '1']]
    ],
    'Node with special attributes' => [
        17, [
            '@attrib' => [
                ':click' => 'alert',
                '@change' => 'changed',
                'v-on:submit.prevent' => 'onSubmit',
                "{{ 'ab-cd'.@x }}" => '{{@foo}}',
            ],
            'special attributes',
        ]
    ],
]);


test('<include> with extended hive', function () {
    $this->f3->set('foo', 'bar');
    $this->f3->set('file', 'templates/test14.htm');
    $exp = [
        'BAR',
        123,
        456.7,
        'quoted $string',
        'unquoted string',
        'partially \'quoted\' string',
        null,
        false,
        true,
        'NULL',
        'FALSE',
        'TRUE',
        '$HOST',
        $this->f3->HOST,
        'HOST',
        true,
        false,
        null,
        $this->f3->HOST,
        PHP_VERSION_ID,
        PHP_OS,
    ];
    $res = [];
    $this->f3->set('ex', function ($val) use (&$res) {
        $res[] = $val;
    });
    $this->tmpl->render('templates/test13.htm');
    $res1 = array_diff_assoc($res, $exp);
    expect($res1)->toBeEmpty();
});


test('escaped values', function () {
    $this->f3->set('string', '<test>');
    $obj = new \stdclass;
    $obj->content = '<ok>';
    $this->f3->set('object', $obj);
    $this->f3->set('ENV.content', $obj->content);
    expect($this->tmpl->render('templates/test12.htm'))
        ->toBe('&lt;test&gt;&lt;ok&gt;&lt;ok&gt;')
        ->and($this->f3->get('string'))->toBe('<test>')
        ->and($this->f3->get('object->content'))->toBe('<ok>')
        ->and($this->f3->get('ENV.content'))->toBe('<ok>');
});

test('render custom filter', function () {
    $this->tmpl->filter('pick', '\FilterHelper::instance()->pick');
    expect($this->tmpl->render('templates/test15.html'))
        ->toBe("apple"."\r\n"."cherry"."\r\n");
});

test('benchmark', function ($engine, $file) {
    $this->f3->set(
        'div',
        array_fill(0, 1000, array_combine(range('a', 'j'), range(0, 9))),
    );
    $now = microtime(true);
    $engine::instance()->render($file);
    expect(
        $val = round(1e3 * (microtime(true) - $now), 2).' msecs',
    )->toBeGreaterThan(0.1, $val);
})->with([
    'Raw PHP template' => [\F3\View::class, 'benchmark.htm'],
    'Use Preview engine' => [\F3\Preview::class, 'templates/benchmark_prev.htm'],
    'Use Template engine' => [\F3\Template::class, 'templates/benchmark.htm'],
]);

afterAll(function () {
    foreach (glob('tmp/*.*.php') as $file)
        unlink($file);
});


class FilterHelper
{
    use \F3\Prefab;

    public function pick($val, $match)
    {
        return preg_grep('/'.$match.'/', $val);
    }
}
