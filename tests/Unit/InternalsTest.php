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

describe('SERIALIZER', function () {
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

test('Relative links', function () {
    expect($this->f3->rel($this->f3->get('BASE').'/hello/world'))->toBe('/hello/world');
});

test('Coerce directory separators', function () {
    expect($this->f3->fixslashes('C:\xyz\abc.php'))
        ->toBe('C:/xyz/abc.php');
});

test('Split comma-, semi-colon, or pipe-separated string', function () {
    expect(
        $this->f3->split('a|bc;d,efg'),
    )->toBe(['a', 'bc', 'd', 'efg']);
});

describe('stringify', function () {
    test('Convert number to exportable string', function () {
        expect($this->f3->stringify(9))
            ->toBe('9')
            ->and($this->f3->stringify(1.5))->toBe('1.5')
            ->and($this->f3->stringify(-7))->toBe('-7')
            ->and((int) $this->f3->stringify(2e3))->toBe(2000);
    });

    test('Convert string to exportable string', function () {
        expect($this->f3->stringify('hello, world'))->toBe('\'hello, world\'');
    });

    test('Convert array to exportable string', function () {
        expect($this->f3->stringify([1, 'a', 0.5]))
            ->toBe('[1,\'a\',0.5]')
            ->and($this->f3->stringify(['x' => 'hello', 'y' => 'world']))
            ->toBe('[\'x\'=>\'hello\',\'y\'=>\'world\']');
    });

    test('Convert object to exportable string', function () {
        $obj = new \stdClass;
        $obj->hello = 'world';
        expect($this->f3->stringify($obj))
            ->toBe('stdClass::__set_state([\'hello\'=>\'world\'])');
    });
});

test('Flatten and convert array to CSV string', function () {
    expect($this->f3->csv([1, 'a', 0.5]))->toBe('1,\'a\',0.5');
});

test('Snake-case', function () {
    expect($this->f3->snakecase('helloWorld'))
        ->toBe('hello_world');
});

test('Camel-case', function () {
    expect($this->f3->camelcase('hello_world'))
        ->toBe('helloWorld');
});

test('No hash() collisions', function () {
    $hash = [];
    $found = false;
    for ($i = 0; $i < 100000; $i++) {
        $h = $this->f3->hash(str_shuffle(uniqid("", true)));
        if (array_key_exists($h, $hash)) {
            $found = true;
            break;
        }
        $hash[$h] = $i;
    }
    expect($hash)
        ->not()->toBeEmpty()
        ->and($found)->toBe(false);
});

describe('Scrub HTML', function () {
    test('Scrub all HTML tags', function () {
        $data = ['foo' => 'ok<h1>foo</h1><p>bar<span>baz</span></p>'];
        $this->f3->scrub($data);
        expect($data['foo'])->toBe('okfoobarbaz');
    });

    test('Scrub specific HTML tags', function () {
        $data = ['foo' => 'ok<h1>foo</h1><p>bar<span>baz</span></p>'];
        $this->f3->scrub($data, 'p,span');
        expect($data['foo'])->toBe('okfoo<p>bar<span>baz</span></p>');
    });

    test('Pass-thru HTML tags', function () {
        $data = ['foo' => 'ok<h1>foo</h1><p>bar<span>baz</span></p>'];
        $this->f3->scrub($data, '*');
        expect($data['foo'])->toBe('ok<h1>foo</h1><p>bar<span>baz</span></p>');
    });

    it('removes control characters', function () {
        $var = '"hello world", a'.chr(8).
            '<$20 or €20> donation helps improve'.chr(0).' this software';
        $this->f3->scrub($var);
        expect($var)->toBe('"hello world", a donation helps improve this software');
    });
});

describe('Encoding', function () {
    test($t1='Encode HTML entities', function () {
        expect($this->f3->encode('I\'ll "walk" the <b>dog</b> now™'))
            ->toBe($out='I\'ll &quot;walk&quot; the &lt;b&gt;dog&lt;/b&gt; now™');
        return $out;
    });
    test('Decode HTML entities', function ($str) {
        expect($this->f3->decode($str))
            ->toBe('I\'ll "walk" the <b>dog</b> now™');
    })->depends($t1);
});