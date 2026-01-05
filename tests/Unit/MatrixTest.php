<?php

use F3\Matrix;

beforeEach(function () {
    $this->matrix = Matrix::instance();
});

dataset('sales', [
    [[
        ['id' => 123, 'name' => 'paul', 'sales' => 0.35],
        ['id' => 456, 'name' => 'ringo', 'sales' => 0.13],
        ['id' => 345, 'name' => 'george', 'sales' => 0.57],
        ['id' => 234, 'name' => 'john', 'sales' => 0.79],
    ]]
]);

it('can sort a multi-dimensional array by a specified column', function ($array) {
    $this->matrix->sort($array, 'name');
    expect($array)->toEqual([
        ['id' => 345, 'name' => 'george', 'sales' => 0.57],
        ['id' => 234, 'name' => 'john', 'sales' => 0.79],
        ['id' => 123, 'name' => 'paul', 'sales' => 0.35],
        ['id' => 456, 'name' => 'ringo', 'sales' => 0.13],
    ]);
})->with('sales');

it('can sort a multi-dimensional array by another column', function ($array) {

    $this->matrix->sort($array, 'sales');

    expect($array)->toEqual([
        ['id' => 456, 'name' => 'ringo', 'sales' => 0.13],
        ['id' => 123, 'name' => 'paul', 'sales' => 0.35],
        ['id' => 345, 'name' => 'george', 'sales' => 0.57],
        ['id' => 234, 'name' => 'john', 'sales' => 0.79],
    ]);
})->with('sales');

it('can retrieve a specified column', function ($array) {
    expect($this->matrix->pick($array, 'name'))
        ->toEqual(['paul', 'ringo', 'george', 'john']);
})->with('sales');

it('can transpose a matrix', function (array $array) {
    $this->matrix->transpose($array);
    expect($array)->toEqual([
        'id' => [123, 456, 345, 234],
        'name' => ['paul', 'ringo', 'george', 'john'],
        'sales' => [0.35, 0.13, 0.57, 0.79],
    ]);
})->with('sales');

it('can change a row key', function () {
    $array = [
        'id' => [456, 123, 345, 234],
        'name' => ['ringo', 'paul', 'george', 'john'],
        'sales' => [0.13, 0.35, 0.57, 0.79],
    ];

    $this->matrix->changeKey($array, 'sales', 'percent');

    expect($array)->toEqual([
        'id' => [456, 123, 345, 234],
        'name' => ['ringo', 'paul', 'george', 'john'],
        'percent' => [0.13, 0.35, 0.57, 0.79],
    ]);
});

it('can select a subset of fields', function () {
    $data = [
        'id' => 123,
        'name' => 'paul',
        'sales' => 0.35,
        'other' => 'info'
    ];

    expect($this->matrix->select(['id', 'name'], $data))
        ->toEqual(['id' => 123, 'name' => 'paul']);

    expect($this->matrix->select('id,sales', $data))
        ->toEqual(['id' => 123, 'sales' => 0.35]);

    $this->f3->set('mydata', $data);
    expect($this->matrix->select(['id', 'other'], 'mydata'))
        ->toEqual(['id' => 123, 'other' => 'info']);
});

it('can walk through a subset of fields', function () {
    $data = [
        'id' => 123,
        'name' => 'paul',
        'sales' => 0.35,
        'other' => 'info'
    ];

    $result = $this->matrix->walk('id,name', $data, function (&$val, $key) {
        if ($key === 'name') {
            $val = strtoupper($val);
        }
    });

    expect($result)->toEqual(['id' => 123, 'name' => 'PAUL']);

    // verify that it receives the full data as third parameter
    $this->matrix->walk('id', $data, function ($val, $key, $full) use ($data) {
        expect($full)->toEqual($data);
    });
});

it('can generate a calendar', function () {
    expect($this->matrix->calendar('2001-09-11'))->toEqual([
        [6 => 1],
        [0 => 2, 1 => 3, 2 => 4, 3 => 5, 4 => 6, 5 => 7, 6 => 8],
        [0 => 9, 1 => 10, 2 => 11, 3 => 12, 4 => 13, 5 => 14, 6 => 15],
        [0 => 16, 1 => 17, 2 => 18, 3 => 19, 4 => 20, 5 => 21, 6 => 22],
        [0 => 23, 1 => 24, 2 => 25, 3 => 26, 4 => 27, 5 => 28, 6 => 29],
        [0 => 30],
    ]);
});
