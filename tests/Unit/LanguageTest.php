<?php

$enUS = 'en-US';
$deDE = 'de-DE';
$frFR = 'fr-FR';

dataset($locales = 'locales', [$enUS, $deDE, $frFR]);

it('sets the language', function (string $locale) {
    $this->f3->LANGUAGE = $locale;
    $language = $this->f3->LANGUAGE; // intentional, received from property hook
    expect($language)
        ->not()->toBe($locale)
        ->and($language)->toContain($locale);
})->with($locales);

\date_default_timezone_set('Europe/Berlin');
$dateTime = strtotime('2025-09-12 13:37:14');

test(
    'Format date short (default)',
    function (string $locale, string $expected) use ($dateTime) {
        $this->f3->LANGUAGE = $locale;
        $this->f3->TZ = 'Europe/Berlin';

        $date = $this->f3->format('{0,date}', $dateTime);
        expect($date)->toBe($expected);
    },
)->with([
    [$enUS, '09/12/2025'],
    [$deDE, '12.09.2025'],
    [$frFR, '12/09/2025'],
]);

test('Format date medium', function (string $locale, string $expected) use ($dateTime) {
    $this->f3->LANGUAGE = $locale;
    $this->f3->TZ = 'Europe/Berlin';
    $date = $this->f3->format('{0,date,medium}', $dateTime);
    expect($date)->toBe($expected);
})->with([
    [$enUS, 'September 12, 2025'],
    [$deDE, '12. September 2025'],
    [$frFR, '12 septembre 2025'],
]);

test('Format date full', function (string $locale, string $expected) use ($dateTime) {
    $this->f3->LANGUAGE = $locale;
    $this->f3->TZ = 'Europe/Berlin';
    $date = $this->f3->format('{0,date,full}', $dateTime);
    expect($date)->toBe($expected);
})->with([
    [$enUS, 'Friday, September 12, 2025'],
    [$deDE, 'Freitag, 12. September 2025'],
    [$frFR, 'vendredi 12 septembre 2025'],
]);

test('Format time short (default)', function (string $locale, string $expected) use ($dateTime) {
    $this->f3->LANGUAGE = $locale;
    $this->f3->TZ = 'Europe/Berlin';
    $date = $this->f3->format('{0,time}', $dateTime);
    expect($date)->toBe($expected);
})->with([
    [$enUS, '1:37 PM'],
    [$deDE, '13:37'],
    [$frFR, '13:37'],
]);

test('Format time medium', function (string $locale, string $expected) use ($dateTime) {
    $this->f3->LANGUAGE = $locale;
    $this->f3->TZ = 'Europe/Berlin';
    $date = $this->f3->format('{0,time,medium}', $dateTime);
    expect($date)->toBe($expected);
})->with([
    [$enUS, '1:37:14 PM'],
    [$deDE, '13:37:14'],
    [$frFR, '13:37:14'],
]);

test('Format time full', function (string $locale, string $expected) use ($dateTime) {
    $this->f3->LANGUAGE = $locale;
    $this->f3->TZ = 'Europe/Berlin';
    $date = $this->f3->format('{0,time,full}', $dateTime);
    expect($date)->toBe($expected);
})->with([
    [$enUS, '1:37:14 PM GMT+2'],
    [$deDE, '13:37:14 MESZ'],
    [$frFR, '13:37:14 UTC+2'],
]);

test('UTC test', function (string $locale, string $expected) {
    $this->f3->TZ = 'UTC';
    expect(date_default_timezone_get())->toBe('UTC');
    $dateTime = strtotime('2025-09-12 13:14:15');
    expect($dateTime)->toBe(1757682855);
    $this->f3->LANGUAGE = $locale;
    $date = $this->f3->format('{0,date,full} - {0,time,full}', $dateTime);
    expect($date)->toBe($expected);
})->with([
    [$enUS, 'Friday, September 12, 2025 - 1:14:15 PM UTC'],
    [$deDE, 'Freitag, 12. September 2025 - 13:14:15 UTC'],
    [$frFR, 'vendredi 12 septembre 2025 - 13:14:15 UTC'],
]);

test('UTC + TZ', function (string $timezone, string $expected) {
    $this->f3->TZ = 'UTC';
    $dateTime = strtotime('2025-09-12 06:14:15');
    $this->f3->TZ = $timezone;
    $date = $this->f3->format('{0,date,full} - {0,time,full}', $dateTime);
    expect($date)->toBe($expected);
})->with([
    ['America/Los_Angeles','Thursday, September 11, 2025 - 11:14:15 PM PDT'],
    ['Europe/Berlin', 'Friday, September 12, 2025 - 8:14:15 AM GMT+2'],
    ['Asia/Manila', 'Friday, September 12, 2025 - 2:14:15 PM GMT+8'],
]);