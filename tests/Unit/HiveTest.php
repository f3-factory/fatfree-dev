<?php

use App\Hive\HookedDto;

describe('dynamic data', function () {
    it('assigned and retrieved a value', function () {
        $this->f3->set('i', 123);
        expect($this->f3->get('i'))
            ->toBe(123)
            ->and($this->f3->i)->toBe(123);
    });

    it('clears a value', function () {
        $this->f3->set('i', 123);
        $this->f3->clear('i');
        $this->f3->exists('i');
        expect($this->f3->exists('i'))
            ->toBeFalse('check by exists method')
            ->and(empty($this->f3->i))->toBeTrue('check by property access');
    });

    it('sets float value', function () {
        $this->f3->set('f', 3.14);
        expect($this->f3->get('f'))
            ->toBe(3.14)
            ->and($this->f3->get('f'))
            ->toBe($this->f3->f);
    });

    it('sets boolean value', function () {
        $this->f3->set('b', true);
        expect($this->f3->get('b'))
            ->toBeTrue()
            ->and($this->f3->get('b'))
            ->toBe($this->f3->b);
    });

    it($t1 = 'sets array value', function () {
        $this->f3->set(
            'array',
            $arr = [
                'w' => false,
                'x' => 'abc',
                'y' => 123,
                'z' => 4.56,
            ],
        );
        expect($this->f3->get('array'))
            ->toBe($arr)
            ->and($this->f3->get('array'))
            ->toBe($this->f3->array);
        return $arr;
    });

    it('gets Boolean element', function ($arr) {
        $this->f3->set('array', $arr);
        expect($this->f3->get('array.w'))
            ->toBeFalse()
            ->and($this->f3->get('array[w]'))->toBeFalse()
            ->and($this->f3->get('array[\'w\']'))->toBeFalse()
            ->and($this->f3->get('array["w"]'))->toBeFalse()
            ->and($this->f3->array['w'])->toBeFalse();
    })->depends('it '.$t1);

    it('gets String element', function ($arr) {
        $this->f3->set('array', $arr);
        expect($this->f3->get('array.x'))
            ->toBe('abc')
            ->and($this->f3->get('array[x]'))->toBe('abc')
            ->and($this->f3->get('array[\'x\']'))->toBe('abc')
            ->and($this->f3->get('array["x"]'))->toBe('abc')
            ->and($this->f3->array['x'])->toBe('abc');
    })->depends('it '.$t1);

    it('gets Integer element', function ($arr) {
        $this->f3->set('array', $arr);
        expect($this->f3->get('array.y'))
            ->toBe(123)
            ->and($this->f3->get('array[y]'))->toBe(123)
            ->and($this->f3->get('array[\'y\']'))->toBe(123)
            ->and($this->f3->get('array["y"]'))->toBe(123)
            ->and($this->f3->array['y'])->toBe(123);
    })->depends('it '.$t1);

    it('gets Float element', function ($arr) {
        $this->f3->set('array', $arr);
        expect($this->f3->get('array.z'))
            ->toBe(4.56)
            ->and($this->f3->get('array[z]'))->toBe(4.56)
            ->and($this->f3->get('array[\'z\']'))->toBe(4.56)
            ->and($this->f3->get('array["z"]'))->toBe(4.56)
            ->and($this->f3->array['z'])->toBe(4.56);
    })->depends('it '.$t1);

    it($t2 = 'alters the array', function ($arr) {
        $this->f3->set('array', $arr);
        $this->f3->set('array', $arr2 = ['w' => false, 'x' => 'qrs', 'y' => 123, 'z' => 4.56]);
        expect($this->f3->get('array'))->toBe($arr2);
    })->depends('it '.$t1);

    it($t3 = 'replaced value; now a multidimensional array', function ($arr) {
        $this->f3->set('array', $arr);
        $this->f3->array = $arr2 = ['a' => ['b' => ['c' => 'hello']]];
        expect($this->f3->get('array'))
            ->toBe($arr2)
            ->and($this->f3->array)->toBe($arr2);
        return $arr2;
    })->depends('it '.$t2);

    test('Array access; array literal, and mixed', function ($arr) {
        $this->f3->set('array', $arr);
        expect($this->f3->get('array.a'))
            ->toBe(['b' => ['c' => 'hello']])
            ->and($this->f3->get('array[a]'))->toBe(['b' => ['c' => 'hello']])
            ->and($this->f3->get('array.a[b]'))->toBe(['c' => 'hello'])
            ->and($this->f3->get('array[a].b'))->toBe(['c' => 'hello'])
            ->and($this->f3->get('array[a][\'b\']'))->toBe(['c' => 'hello'])
            ->and($this->f3->get('array.a.b.c'))->toBe('hello')
            ->and($this->f3->get('array[a][b][c]'))->toBe('hello')
            ->and($this->f3->get('array["a"]'))->toBe(['b' => ['c' => 'hello']])
            ->and($this->f3->get('array["a"]["b"]'))->toBe(['c' => 'hello'])
            ->and($this->f3->get('array["a"][\'b\']["c"]'))->toBe('hello')
            ->and($this->f3->get('array["a"]["b"]["c"]'))->toBe('hello')
            ->and($this->f3->array['a'])->toBe($this->f3->get('array.a'))
            ->and($this->f3->array['a']['b'])->toBe($this->f3->get('array.a.b'))
            ->and($this->f3->array['a']['b']['c'])->toBe($this->f3->get('array.a.b.c'));
    })->depends('it '.$t3);

    test('Closure assigned', function () {
        $this->f3->set('a', function () {
            return 'hello, world';
        });
        expect(get_class($func = $this->f3->get('a')))
            ->toBe('Closure')
            ->and(get_class($this->f3->a))->toBe('Closure')
            ->and($func())
            ->toBe('hello, world')
            ->and($this->f3->a())->toBe('hello, world');
    });

    it('replaced a value', function () {
        $this->f3->set('a', ['a', 'b', 'c']);
        $this->f3->set('a', new \stdClass);
        $this->f3->a->hello = 'world';
        expect($this->f3->get('a'))
            ->toBeObject()
            ->and($this->f3->a)->toBeObject()
            ->and($this->f3->get('a')->hello)->toBe('world')
            ->and($this->f3->get('a->hello'))->toBe('world')
            ->and($this->f3->a->hello)->toBe('world');
    });

    it('confirms value existence', function () {
        $this->f3->set('a', new \stdClass);
        $this->f3->a->hello = 'world';
        expect($this->f3->exists('a'))
            ->toBeTrue()
            ->and($this->f3->exists('a->hello', $hello))->toBeTrue()
            ->and($hello)->toBe('world')
            ->and(isset($this->f3->a))->toBeTrue()
            ->and(isset($this->f3->a->hello))->toBeTrue();
    });

    test('Object property containing array', function () {
        $this->f3->set('a->z.x', 'foo');
        expect($this->f3->get('a->z'))
            ->toBeArray()
            ->and($this->f3->get('a->z.x'))->toBe('foo')
            ->and($this->f3->a->z)->toBeArray()
            ->and($this->f3->a->z['x'])->toBe('foo');
    });

    test('Object property containing closure´', function () {
        $this->f3->set('a->z.qux', function () {
            return 'hello';
        });
        expect($this->f3->get('a->z.qux'))
            ->toBeCallable()
            ->and($this->f3->a->z['qux'])->toBeCallable();
    });

    test('object style value', function () {
        $this->f3->set('foo', 'bar->baz');
        expect($this->f3->get('foo'))
            ->toBe('bar->baz');
    });

    test('Multilevel array', function () {
        $this->f3->set('i.j', 'bar');
        expect($this->f3->get('i'))
            ->toBeArray()
            ->and($this->f3->get('i.j'))->toBe('bar')
            ->and($this->f3->i)->toBeArray()
            ->and($this->f3->i['j'])->toBe('bar');

        $this->f3->clear('i');
        $this->f3->set('i.j.k', 'foo');

        expect($this->f3->get('i'))
            ->toBeArray()
            ->and($this->f3->i)->toBeArray()
            ->and($this->f3->get('i.j'))->toBeArray()
            ->and($this->f3->i['j'])->toBeArray()
            ->and($this->f3->get('i.j.k'))->toBe('foo')
            ->and($this->f3->i['j']['k'])->toBe('foo');
    });

    test('Non-existent array', function () {
        expect($this->f3->get('l.m.n'))
            ->toBeNull()
            ->and($this->f3->l)->toBeNull()
            ->and($this->f3->l)->not()->toBeArray()
            ->and($this->f3->get('l'))->not()->toBeArray()
            ->and($this->f3->get('l.m'))->not()->toBeArray();
    });

    test('Array keys containing dot symbol', function () {
        $this->f3->set(
            'domains',
            $arr = [
                'google.com' => 'Google',
                'yahoo.com' => 'Yahoo',
            ],
        );
        expect($this->f3->get('domains'))
            ->toBe($arr)
            ->and($this->f3->domains)->toBe($arr)
            ->and($this->f3->get('domains[google.com]'))->toBe('Google')
            ->and($this->f3->domains['google.com'])->toBe('Google')
            ->and($this->f3->get('domains[yahoo.com]'))->toBe('Yahoo')
            ->and($this->f3->domains['yahoo.com'])->toBe('Yahoo');
    });

    test('Non-existent variable', function () {
        expect($this->f3->exists('j'))
            ->toBeFalse()
            ->and($this->f3->j)->toBeEmpty();
    });

    test('Reference to variable', function () {
        $this->f3->set('x', 'open sesame!');
        $x =& $this->f3->ref('x');
        expect($x)
            ->toBe('open sesame!')
            ->and($this->f3->get('x'))->toBe('open sesame!')
            ->and($this->f3->x)->toBe('open sesame!');

        $x = 123;
        expect($this->f3->get('x'))
            ->toBe(123, 'Indirect assignment')
            ->and($this->f3->x)->toBe(123);
    });

    test('Reference to custom array', function () {
        $array = ['foo' => ['bar' => 456]];
        $this->f3->set('array', $array);
        $bar2 = $this->f3->ref('array.foo->bar', false);
        $bar = $this->f3->ref('array.foo.bar', false);
        expect($bar)
            ->toBe(456)
            ->and($bar2)->toBeNull();

        $baz = &$this->f3->ref('array.foo.baz');
        $baz = 'test';
        $array = $this->f3->get('array');
        expect($array)
            ->toBeArray()
            ->and($array)->toHaveKey('foo', message: 'Indirect assignment on custom array')
            ->and($array['foo'])->toBeArray()
            ->and($array['foo'])->toHaveKey('baz', message: 'Indirect assignment on custom array')
            ->and($array['foo']['baz'])->toBe('test');
    });

    test('Reference to custom object', function () {
        $obj = (object) ['foo' => (object) ['bar' => 456]];
        $this->f3->set('obj', $obj);
        $bar = $this->f3->ref('obj.foo->bar', false);
        $bar2 = $this->f3->ref('obj.foo.bar', false);
        expect($bar)
            ->toBe(456)
            ->and($bar2)->toBe(456);

        $baz = &$this->f3->ref('obj.foo->baz');
        $baz = 'test';

        expect($obj)
            ->toBeObject()
            ->and($obj)->toHaveProperty('foo')
            ->and($obj->foo)->toBeObject()
            ->and($obj->foo)->toHaveProperty('baz')
            ->and($obj->foo->baz)->toBe('test', 'Indirect assignment on custom object');
    });
});

describe('typed hive', function () {
    test('toArray fetches initialized props', function () {
        $customer = new App\Hive\Customer();
        expect($customer->toArray())->toBe([
            'first_name' => '',
            'phone' => null,
            'arr3' => null,
            'arr4' => [],
        ]);
    });

    it($t1 = 'sets properties', function () {
        $customer = new App\Hive\Customer();
        $customer->first_name = 'John';
        $customer->last_name = 'Doe';
        $customer->email = 'john.doe@domain.com';
        $customer->phone = '0123456789';
        $customer->set('meta.foo', 'bar');
        $customer->set('obj->foo', 'bar');
        $arr = ['bar' => 123];
        $customer->foo = $arr; // no typed property
        $customer->arr1 = $arr;
        $customer->arr2 = $arr;
        $customer->arr3 = $arr;
        $customer->arr4 = $arr;

        expect($customer->first_name)
            ->toBe('John')
            ->and($customer->get('first_name'))->toBe('John')
            ->and($customer->last_name)->toBe('Doe')
            ->and($customer->get('last_name'))->toBe('Doe')
            ->and($customer->email)->toBe('john.doe@domain.com')
            ->and($customer->phone)->toBe('0123456789')
            ->and($customer->get('meta.foo'))->toBe('bar')
            ->and($customer->meta['foo'])->toBe('bar')
            ->and($customer->obj->foo)->toBe('bar')
            ->and(isset($customer->foo))->toBeTrue()
            ->and($customer->get('foo.bar'))->toBe(123);
        return $customer;
    });

    it('handles clearing class properties', function ($customer) {
        $customer = clone $customer;
        $customer->clear('first_name');
        unset($customer->last_name);
        $customer->clear('meta.foo');
        $customer->clear('obj->foo');
        $customer->clear('obj');
        $customer->clear('foo');
        $customer->clear('arr1');
        $customer->clear('arr2');
        $customer->clear('arr3');
        $customer->clear('arr4');

        expect($customer->first_name)
            ->toBe('')
            ->and($customer->last_name)->toBeNull()
            ->and($customer->arr1)->toBe([])
            ->and($customer->arr2)->toBeNull()
            ->and($customer->arr3)->toBeNull()
            ->and($customer->arr4)->toBe([])
            ->and($customer->exists('obj->foo'))->toBeFalse()
            ->and($customer->exists('obj'))->toBeFalse();
    })->depends('it '.$t1);

    test('access nested hive', function ($customer) {
        $this->f3->customer = $customer;
        expect($this->f3->customer->first_name)
            ->toBe('John')
            ->and($this->f3->get('customer')->first_name)->toBe('John')
            ->and($this->f3->get('customer->first_name'))->toBe('John');
    })->depends('it '.$t1);

    test('property access with dot notation', function ($customer) {
        $this->f3->customer = $customer;
        expect($this->f3->get('customer.first_name'))->toBe('John');
    })->depends('it '.$t1);

    test('reserved props test', function () {
        $hooked = new HookedDto();
        expect($hooked->exists('_hive_data'))->toBeFalse();
        $hooked->_hive_data = 'foo'; // intentionally wrong type as well
        expect($hooked->exists('_hive_data'))
            ->toBeTrue()
            ->and($hooked->_hive_data)->toBe('foo');
    });

    test('nullable string access', function () {
        $hooked = new HookedDto();
        expect($hooked->exists('nullableString'))->toBeFalse();
        $hooked->nullableString = 'foo';
        expect($hooked->exists('nullableString'))->toBeTrue();
        $hooked->clear('nullableString');
        expect($hooked->exists('nullableString'))
            ->toBeFalse()
            ->and($hooked->nullableString)->toBeNull();
    });

    test('access with property hooks', function () {
        $hooked = new HookedDto();
        $hooked->stringWithGetter = 'Johnny';
        $hooked->narf = 'array item';
        expect($hooked->stringWithGetter)
            ->toBe('JOHNNY', 'access via property')
            ->and($hooked->get('stringWithGetter'))->toBe('JOHNNY', 'access via getter');
        $this->f3->set('hooked', $hooked);
        expect($this->f3->get('hooked')->stringWithGetter)
            ->toBe('JOHNNY', 'nested access via property')
            ->and($this->f3->get('hooked->stringWithGetter'))->toBe(
                'JOHNNY',
                'access via getter, full path',
            )
            ->and($this->f3->get('hooked.stringWithGetter'))->toBe(
                'JOHNNY',
                'access via getter, w/ dot notation',
            )
            ->and($this->f3->get('hooked.narf'))->toBe('array item');
    });
});

it('retrieves locales from dictionary', function () {
    $this->f3->set('LOCALES', 'dict/');
    expect($this->f3->exists('tqbf'))
        ->toBeTrue()
        ->and(isset($this->f3->tqbf))->toBeTrue();
});

it('copies a hive variable', function () {
    $this->f3->set('x', 'foo');
    $this->f3->copy('x', 'y');
    expect($this->f3->x)->toBe($this->f3->y);
});

it('format with get', function () {
    $this->f3->LANGUAGE = 'de-DE';
    $this->f3->TZ = 'Europe/Berlin';
    $this->f3->set('msg', 'Hallo {0}. The time is: {1, time}');
    $time = time();
    $out = $this->f3->get('msg', ['John', $time]);
    expect($out)->toBe('Hallo John. The time is: '.date('H:i', $time));
});

test('String concatenation', function () {
    $this->f3->set('y', 'foo');
    $this->f3->concat('y', ' bar');
    expect($this->f3->y)->toBe('foo bar');
});

test('Array push create', function () {
    expect($this->f3->exists('z'))->toBeFalse();
    $this->f3->push('z', 1);
    expect($this->f3->exists('z'))
        ->toBeTrue()
        ->and($this->f3->z)->toBe([1])
        ->and($this->f3->push('z', 2))->toBe(2)
        ->and($this->f3->z)->toBe([1, 2]);
});

test('Array push', function () {
    expect($this->f3->exists('z'))->toBeFalse();
    $this->f3->set('z', [1, 2, 3]);
    $this->f3->push('z', 4);
    expect($this->f3->get('z'))
        ->toBe([1, 2, 3, 4]);
});

test('Array pop', function () {
    $this->f3->set('z', [1, 2, 3, 4]);
    expect($this->f3->pop('z'))
        ->toBe(4)
        ->and($this->f3->get('z'))
        ->toBe([1, 2, 3]);
});

test('Array unshift', function () {
    $this->f3->set('z', [1, 2, 3]);
    $this->f3->unshift('z', 0);
    expect($this->f3->get('z'))
        ->toBe([0, 1, 2, 3]);
});

test('Array shift', function () {
    $this->f3->set('z', [0, 1, 2, 3]);
    expect($this->f3->shift('z'))
        ->toBe(0)
        ->and($this->f3->z)->toBe([1, 2, 3]);
});

test('Array flip', function () {
    $this->f3->set('q', ['a' => 2, 'b' => 5, 'c' => 7]);
    $this->f3->flip('q');
    expect($this->f3->q)
        ->toBe([2 => 'a', 5 => 'b', 7 => 'c']);
});

describe('Cookie JAR', function () {

    test('default settings', function () {
        expect($this->f3->JAR->lifetime)
            ->toBe(0);
    });

    test('lifetime adjustment by property', function () {
        $expected = strtotime('2 days') - \time();
        $this->f3->JAR->lifetime = '2 days';
        expect($this->f3->JAR->lifetime)
            ->toBeGreaterThanOrEqual($expected);
    });

    test('lifetime adjustment by hive setter', function () {
        $expected = strtotime('2 days') - \time();
        $this->f3->JAR->lifetime = '2 days';
        $this->f3->set('JAR.lifetime', '2 days');

        expect($this->f3->JAR->lifetime)
            ->toBeGreaterThanOrEqual($expected);

        $this->f3->set('JAR.lifetime', 7200);
        expect($this->f3->JAR->lifetime)
            ->toBeGreaterThanOrEqual(7200 - 1);
    });

});

test('request time', function () {
    expect((int) $this->f3->TIME)
        ->toBeLessThanOrEqual(time());
});