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
    ['@foo::bar.@baz','$foo::bar.$baz'],
    ['@foo::@baz','$foo::$baz'],
    ['@foo::bar[@baz]','$foo::bar[$baz]'],
    ['@foo.bar->baz','$foo[\'bar\']->baz'],
    ['@foo.bar->@baz','$foo[\'bar\']->$baz'],
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
    ['@foo->zip(@bar,\'qux\',123,[\'a\'=>\'hello\'])', '$foo->zip($bar,\'qux\',123,[\'a\'=>\'hello\'])'],
    ['@foo[@bar+1].baz', '$foo[$bar+1][\'baz\']'],
    ['@foo.baz[@bar+1]', '$foo[\'baz\'][$bar+1]'],
    ['@foo.\'hello, world\'', '$foo.\'hello, world\''],
    ['@foo.bar.baz->qux.corge[@gra::@ult()](@arp)', '$foo[\'bar\'][\'baz\']->qux[\'corge\'][$gra::$ult()]($arp)'],
    ['@foo | esc', '$this->esc($foo)'],
]);


test('render cache', function ($val, $ttl, $sleep, $expected) {
    $this->f3->CACHE = 'folder=tmp/cache/';
    $file = 'templates/cache.htm';
    sleep($sleep);
    expect($this->tmpl->render(
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