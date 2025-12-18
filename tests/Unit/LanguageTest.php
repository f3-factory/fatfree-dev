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

it('detects language from header', function (
    string $header,
    string $fallback,
    string $expected
) {
    expect($this->f3->FALLBACK)->toBe('en');
    $this->f3->FALLBACK = $fallback;
    expect($this->f3->FALLBACK)->toBe($fallback, 'new fallback');

    $this->f3->route('GET /lang', function (\F3\Base $f3) {
        return $f3->LANGUAGE;
    });
    $out = $this->f3->mock(
        'GET /lang',
        headers: ['Accept-Language' => $header],
        sandbox: true,
    );
    expect($out)->toBe($expected);
})->with([
    ['de,en-US;q=0.7,en;q=0.3', 'en', 'de,en-US,en'],
    ['de,en-US;q=0.7,en;q=0.3', 'da', 'de,en-US,en,da'],
    ['fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5', 'it', 'fr-CH,fr,en,de,it']
]);

\date_default_timezone_set('Europe/Berlin');
$dateTime = strtotime('2025-09-12 13:37:14');

describe('format date/time', function () use ($frFR, $deDE, $enUS, $dateTime) {
    test(
        'date short (default)',
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

    test('date medium', function (string $locale, string $expected) use ($dateTime) {
        $this->f3->LANGUAGE = $locale;
        $this->f3->TZ = 'Europe/Berlin';
        $date = $this->f3->format('{0,date,medium}', $dateTime);
        expect($date)->toBe($expected);
    })->with([
        [$enUS, 'September 12, 2025'],
        [$deDE, '12. September 2025'],
        [$frFR, '12 septembre 2025'],
    ]);

    test('date full', function (string $locale, string $expected) use ($dateTime) {
        $this->f3->LANGUAGE = $locale;
        $this->f3->TZ = 'Europe/Berlin';
        $date = $this->f3->format('{0,date,full}', $dateTime);
        expect($date)->toBe($expected);
    })->with([
        [$enUS, 'Friday, September 12, 2025'],
        [$deDE, 'Freitag, 12. September 2025'],
        [$frFR, 'vendredi 12 septembre 2025'],
    ]);

    test(
        'time short (default)',
        function (string $locale, string $expected) use ($dateTime) {
            $this->f3->LANGUAGE = $locale;
            $this->f3->TZ = 'Europe/Berlin';
            $date = $this->f3->format('{0,time}', $dateTime);
            expect($date)->toBe($expected);
        }
    )->with([
        [$enUS, '1:37 PM'],
        [$deDE, '13:37'],
        [$frFR, '13:37'],
    ]);

    test('time medium', function (string $locale, string $expected) use ($dateTime) {
        $this->f3->LANGUAGE = $locale;
        $this->f3->TZ = 'Europe/Berlin';
        $date = $this->f3->format('{0,time,medium}', $dateTime);
        expect($date)->toBe($expected);
    })->with([
        [$enUS, '1:37:14 PM'],
        [$deDE, '13:37:14'],
        [$frFR, '13:37:14'],
    ]);

    test('time full', function (string $locale, string $expected) use ($dateTime) {
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
        ['America/Los_Angeles', 'Thursday, September 11, 2025 - 11:14:15 PM PDT'],
        ['Europe/Berlin', 'Friday, September 12, 2025 - 8:14:15 AM GMT+2'],
        ['Asia/Manila', 'Friday, September 12, 2025 - 2:14:15 PM GMT+8'],
    ]);

});


describe('locales', function () {

    test('load dictionary', function ($lang, $expected) {
        $this->f3->LOCALES = 'dict/';
        $this->f3->LANGUAGE = $lang;
        $l = $this->f3->LANGUAGE;
        expect($this->f3->get('tqbf'))->toBe($expected);
    })->with([
        [
            'fr-FR',
            'Les naïfs ægithales hâtifs pondant '."\n".
            'à Noël où il gèle sont sûrs d\'être '."\n".
            'déçus et de voir leurs drôles d\'œufs abîmés."'
        ],
        [
            'en-US',
            'Lorem ipsun'
        ],
        [
            'es-CL',
            'El pingüino Wenceslao hizo kilómetros bajo exhaustiva lluvia y frío, añoraba a su querido cachorro.'
        ],
        [
            'en',
            'The quick brown fox jumps over the lazy dog.',
        ],
    ]);

    it('caches locales', function () {
        $locales = 'dict/';
        $this->f3->SEED = 'test';
        $hash = $this->f3->hash('en'.$locales).'.dic';

        $this->f3->CACHE = true;
        $cache = \F3\Cache::instance();
        $cache->clear($hash);
        expect($cache->exists($hash))->toBeFalse();

        $this->f3->FALLBACK = 'en';
        $this->f3->LANGUAGE = 'en';
        $this->f3->LOCALES_TTL = 60;
        $this->f3->LOCALES = $locales;

        $tqbf = 'The quick brown fox jumps over the lazy dog.';
        expect($this->f3->tqbf)->toBe($tqbf);
        expect($cache->exists($hash))->not->toBeFalse();

        // set caching via Base->set $ttl parameter
        $hash = $this->f3->hash('de,'.$this->f3->FALLBACK.$locales).'.dic';
        $cache->clear($hash);
        expect($cache->exists($hash))->toBeFalse();
        $this->f3->LOCALES_TTL = 0;
        $this->f3->set('LANGUAGE', 'de', 60);
        expect($cache->exists($hash))->not->toBeFalse('locales ttl via set method');
        expect($this->f3->LOCALES_TTL)->toBe(60, 'LOCALES_TTL updated');
    });

    test('key prefix', function (string $prefix) {
        $this->f3->LOCALES = 'dict/';
        $this->f3->PREFIX = $prefix;
        $this->f3->LANGUAGE = 'en';
        $tqbf = 'The quick brown fox jumps over the lazy dog.';
        expect($this->f3->get($prefix.'tqbf'))->toBe($tqbf);
    })->with([
        'string' => ['prefix_'],
        'array' => ['prefix.'],
        'mixed' => ['prefix.prefix_'],
    ]);

    test('Pluralization', function (int $num, string $expected) {
        $this->f3->set(
            'foo',
            '{0, plural, '.
            'zero {There\'s nothing on the table.}, '.
            'one {A book is on the table.}, '.
            'other {There are # books on the table.}'.
            '}',
        );
        expect($this->f3->get('foo', $num))->toBe($expected);
    })->with([
        'zero' => [0, 'There\'s nothing on the table.'],
        'one' => [1, 'A book is on the table.'],
        'two' => [2, 'There are 2 books on the table.'],
        'other' => [9, 'There are 9 books on the table.'],
    ]);

    test('Pluralization - partly', function (int $num, string $expected) {
        $format = '{0, plural, zero {},one {1 result}, other {# results}}';
        expect($this->f3->format($format,$num))->toBe($expected);
    })->with([
        [0, ''],
        [1, '1 result'],
        [5, '5 results'],
    ]);

});
