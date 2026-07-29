<?php

namespace Tests\Unit;

use F3\DB\Cursor;

class CursorMock extends Cursor
{
    protected array $fields = [];
    protected static array $data = [];

    public function dbtype(): string
    {
        return 'mock';
    }

    public function fields(): array
    {
        return array_keys($this->fields);
    }

    public function cast(?object $obj = null): array
    {
        $out = $this->fields;
        if ($obj) {
            foreach ($out as $key => $val) {
                $obj->$key = $val;
            }
        }
        return $out;
    }

    public function find(
        array|string|null $filter = null,
        ?array $options = null,
        int|array $ttl = 0,
    ): array {
        // Simple mock find that returns clones of itself if filter matches something
        if ($filter === 'empty') {
            return [];
        }
        $res = [];
        $count = $options['limit'] ?? 2;
        $offset = $options['offset'] ?? 0;
        for ($i = $offset; $i < $offset+$count; $i++) {

            $res[] = array_find(self::$data, fn($item) => $item->id === $i+1);
        }
        return $res;
    }

    public function count(
        array|string|null $filter = null,
        ?array $options = null,
        int|array $ttl = 0,
    ): int {
        if ($filter === 'empty') {
            return 0;
        }
        return count(self::$data);
    }

    public function insert(): static
    {
        if (isset($this->trigger['beforeInsert'])) {
            call_user_func($this->trigger['beforeInsert'], $this);
        }
        $this->fields['id'] = $id = count(self::$data) + 1;
        self::$data[$id] = clone $this;
        $this->query[] = $this;
        if (isset($this->trigger['afterInsert'])) {
            call_user_func($this->trigger['afterInsert'], $this);
        }
        return $this;
    }

    public function update(): static
    {
        if (isset($this->trigger['beforeUpdate'])) {
            call_user_func($this->trigger['beforeUpdate'], $this);
        }
        self::$data[$this->fields['id']] = clone $this;
        // mock update
        if (isset($this->trigger['afterUpdate'])) {
            call_user_func($this->trigger['afterUpdate'], $this);
        }
        return $this;
    }

    public function copyFrom(array|string $var, ?callable $func = null): void {}

    public function copyTo(string $key): void {}

    public function exists(string $key): bool
    {
        return isset($this->fields[$key][$key]);
    }

    public function set(string $key, mixed $val): mixed
    {
        return $this->fields[$key] = $val;
    }

    public function &get(string $key): mixed
    {
        $val =& $this->fields[$key];
        return $val;
    }

    public function clear(string $key): void
    {
        unset($this->fields[$key]);
    }

    // Helper to set query and ptr for testing protected properties
    public function setQuery(array $query)
    {
        $this->query = $query;
    }

    public function setPtr(int $ptr)
    {
        $this->ptr = $ptr;
    }

    public function getPtr(): int
    {
        return $this->ptr;
    }

    public function getTrigger(): array
    {
        return $this->trigger;
    }
}

beforeEach(function () {
    $this->cursor = new CursorMock();
});

it('returns dbtype', function () {
    expect($this->cursor->dbtype())->toBe('mock');
});

it('returns fields', function () {
    $this->cursor->a = 1;
    $this->cursor->b = 2;
    expect($this->cursor->fields())->toBe(['a', 'b']);
});

it('casts to array', function () {
    $this->cursor->a = 1;
    $this->cursor->b = 2;
    expect($this->cursor->cast())->toBe(['a' => 1, 'b' => 2]);
});

it('gets iterator', function () {
    $this->cursor->a = 1;
    $this->cursor->b = 2;;
    $iterator = $this->cursor->getIterator();
    expect($iterator)
        ->toBeInstanceOf(\ArrayIterator::class)
        ->and(iterator_to_array($iterator))
        ->toBe(['a' => 1, 'b' => 2]);
});

it('checks if dry', function () {
    expect($this->cursor->dry())->toBeTrue();
    $this->cursor->setQuery([['id' => 1]]);
    expect($this->cursor->dry())->toBeFalse();
});

it('finds one', function () {
    $this->cursor->a = 1;
    $this->cursor->b = 2;
    $this->cursor->insert();
    expect($this->cursor->id)->toBe(1);
    $res = $this->cursor->findOne();
    expect($res)
        ->toBeInstanceOf(CursorMock::class)
        ->and($res->id)->toBe(1);

    $res = $this->cursor->findOne('empty');
    expect($res)->toBeNull();
});

it('paginates', function () {
    for ($i = 0; $i < 27; $i++) {
        $this->cursor->insert();
    }

    $res = $this->cursor->paginate(0, 10);
    expect($res['total'])
        ->toBe(28)
        ->and($res['limit'])->toBe(10)
        ->and($res['count'])->toBe(3)
        ->and($res['pos'])->toBe(0)
        ->and($res['subset'])->toHaveCount(10)
        ->and($res['subset'][0]->id)->toBe(1);

    $res = $this->cursor->paginate(1, 10);
    expect($res['pos'])
        ->toBe(1)
        ->and($res['subset'][0]->id)->toBe(11);

    // test bounce
    $res = $this->cursor->paginate(15, 10);
    expect($res['pos'])->toBe(2, 'bounced to last page');

    $res = $this->cursor->paginate(15, 10, null, null, 0, false);
    expect($res['pos'])
        ->toBe(15)
        ->and($res['subset'])->toBe([]);
});

it('loads records', function () {
    $res = $this->cursor->load();
    expect($res)
        ->toBeInstanceOf(CursorMock::class)
        ->and($this->cursor->loaded())->toBe(2)
        ->and($this->cursor->getPtr())->toBe(0);

    $res = $this->cursor->load('empty');
    expect($res)
        ->toBeNull()
        ->and($this->cursor->loaded())
        ->toBe(0);
});

it('navigates through records', function () {
    $obj1 = new CursorMock();
    $obj1->fields = ['id' => 1];
    $obj2 = new CursorMock();
    $obj2->fields = ['id' => 2];
    $obj3 = new CursorMock();
    $obj3->fields = ['id' => 3];
    $this->cursor->setQuery([$obj1, $obj2, $obj3]);

    expect($this->cursor->first())->toBe($obj1);
    expect($this->cursor->getPtr())->toBe(0);

    expect($this->cursor->next())->toBe($obj2);
    expect($this->cursor->getPtr())->toBe(1);

    expect($this->cursor->last())->toBe($obj3);
    expect($this->cursor->getPtr())->toBe(2);

    expect($this->cursor->prev())->toBe($obj2);
    expect($this->cursor->getPtr())->toBe(1);

    expect($this->cursor->skip(1))->toBe($obj3);
    expect($this->cursor->skip(-2))->toBe($obj1);

    expect($this->cursor->skip(10))->toBeNull();
    expect($this->cursor->getPtr())->toBe(10);
});

it('checks validity', function () {
    expect($this->cursor->valid())->toBeFalse();
    $this->cursor->setQuery([['id' => 1]]);
    expect($this->cursor->valid())->toBeTrue();
});

it('saves records', function () {
    $c = $this->cursor->count();
    // Insert
    $this->cursor->save();
    expect($this->cursor->count())->toBe($c + 1);

    // Update
    $this->cursor->save();
    expect($this->cursor->count())->toBe($c + 1);
});

it('erases records', function () {
    $obj1 = new CursorMock();
    $obj1->id = 1;
    $obj2 = new CursorMock();
    $obj2->id = 2;
    $obj3 = new CursorMock();
    $obj3->id = 3;

    $this->cursor->setQuery([$obj1, $obj2, $obj3]);
    $this->cursor->setPtr(1); // Pointing to $obj2
    expect($this->cursor->dry())->toBeFalse();

    expect($this->cursor->erase())
        ->toBe(1)
        ->and($this->cursor->loaded())->toBe(2)
        ->and($this->cursor->dry())->toBeFalse()
        ->and($this->cursor->skip(0))->toBe($obj3)
        ->and($this->cursor->first())->toBe($obj1)
        ->and($this->cursor->next())->toBe($obj3);
});

it('resets cursor', function () {
    $this->cursor->setQuery([['id' => 1]]);
    $this->cursor->setPtr(1);
    $this->cursor->reset();
    expect($this->cursor->loaded())
        ->toBe(0)
        ->and($this->cursor->getPtr())->toBe(0);
});

it('sets triggers', function () {
    $this->cursor->beforeErase($cbbe = function () {});
    $this->cursor->beforeInsert($cbbi = function () {});
    $this->cursor->beforeUpdate($cbbu = function () {});

    $this->cursor->afterErase($cbae= function () {});
    $this->cursor->afterInsert($cbai = function () {});
    $this->cursor->afterUpdate($cbau = function () {});
    expect($this->cursor->getTrigger())
        ->tohaveKeys([
            'beforeErase', 'beforeInsert', 'beforeUpdate',
            'afterErase', 'afterInsert', 'afterUpdate'
        ])
        ->beforeErase->toBe($cbbe)
        ->afterErase->toBe($cbae)
        ->beforeInsert->toBe($cbbi)
        ->afterInsert->toBe($cbai)
        ->beforeUpdate->toBe($cbbu)
        ->afterUpdate->toBe($cbau);
});

test('trigger shortcuts', function () {
    $this->cursor->beforeSave($cbbs = function () {});
    $this->cursor->afterSave($cbas = function () {});
    expect($this->cursor->getTrigger())
        ->tohaveKeys([
            'beforeInsert', 'beforeUpdate',
            'afterInsert', 'afterUpdate'
        ])
        ->beforeInsert->toBe($cbbs)
        ->beforeUpdate->toBe($cbbs)
        ->afterInsert->toBe($cbas)
        ->afterUpdate->toBe($cbas);
});

test('trigger alias', function () {
    $this->cursor->onLoad($cbol = function () {});
    $this->cursor->onErase($cboe = function () {});
    $this->cursor->onInsert($cbai = function () {});
    $this->cursor->onUpdate($cbau = function () {});
    expect($this->cursor->getTrigger())
        ->tohaveKeys([
            'load', 'afterErase', 'afterInsert', 'afterUpdate'
        ])
        ->load->toBe($cbol)
        ->afterErase->toBe($cboe)
        ->afterInsert->toBe($cbai)
        ->afterUpdate->toBe($cbau);

    $this->cursor->onSave($cbas = function () {});
    expect($this->cursor->getTrigger())
        ->afterInsert->toBe($cbas)
        ->afterUpdate->toBe($cbas);
});
