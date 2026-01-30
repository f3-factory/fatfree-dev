<?php

use F3\Test;

it('initializes with default reporting level', function () {
    $test = new Test();
    expect($test->passed())
        ->toBeTrue()
        ->and($test->results())->toBeArray()->toBeEmpty();
});

it('logs successful expectations', function () {
    $test = new Test();
    $test->expect(true, 'success');

    expect($test->passed())->toBeTrue();
    $results = $test->results();
    expect($results)
        ->toHaveCount(1)
        ->and($results[0]['status'])->toBeTrue()
        ->and($results[0]['text'])->toBe('success')
        ->and($results[0]['source'])->toContain('TestTest.php');
});

it('logs failed expectations with FLAG_Both', function () {
    $test = new Test(Test::FLAG_Both);
    $test->expect(false, 'failure');
    $test->expect(true, 'success');

    expect($test->passed())->toBeFalse();
    $results = $test->results();
    expect($results)
        ->toHaveCount(2)
        ->and($results[0]['status'])->toBeFalse()
        ->and($results[0]['text'])->toBe('failure')
        ->and($results[1]['status'])->toBeTrue()
        ->and($results[1]['text'])->toBe('success');
});

it('logs only successes with FLAG_True', function () {
    $test = new Test(Test::FLAG_True);
    $test->expect(true, 'success');
    $test->expect(false, 'failure');
    
    expect($test->passed())->toBeFalse();
    $results = $test->results();
    expect($results)
        ->toHaveCount(1)
        ->and($results[0]['status'])->toBeTrue()
        ->and($results[0]['text'])->toBe('success');
});

it('logs only failures with FLAG_False', function () {
    $test = new Test(Test::FLAG_False);
    $test->expect(true, 'success');
    $test->expect(false, 'failure');
    
    expect($test->passed())->toBeFalse();
    $results = $test->results();
    expect($results)
        ->toHaveCount(1)
        ->and($results[0]['status'])->toBeFalse()
        ->and($results[0]['text'])->toBe('failure');
});

it('appends messages as successful expectations', function () {
    $test = new Test();
    $test->message('some message');
    
    expect($test->passed())->toBeTrue();
    $results = $test->results();
    expect($results)
        ->toHaveCount(1)
        ->and($results[0]['status'])->toBeTrue()
        ->and($results[0]['text'])->toBe('some message');
});

it('tracks overall pass status', function () {
    $test = new Test();
    $test->expect(true);
    expect($test->passed())->toBeTrue();
    
    $test->expect(false);
    expect($test->passed())->toBeFalse();
    
    $test->expect(true);
    expect($test->passed())->toBeFalse(); // Should remain false
});

it('returns self from expect() for chaining', function () {
    $test = new Test();
    $result = $test->expect(true, 'chained');
    expect($result)->toBe($test);
});
