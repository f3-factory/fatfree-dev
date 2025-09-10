<?php

namespace App\Controller;

use F3\Base;
use F3\ISO;
use F3\Matrix;
use F3\Registry;

class Internals extends BaseController
{

    function get($f3)
    {
        $test = new \F3\Test;
        $test->expect(
            is_null($f3->get('ERROR')),
            'No errors expected at this point',
        );
        $test->expect(
            PHP_VERSION,
            'PHP version '.PHP_VERSION,
        );
        $test->expect(
            PHP_SAPI,
            'SAPI: '.PHP_SAPI,
        );
        $f3->foo = 'bar';
        $test->expect(
            $f3 === Base::instance() && Base::instance()->foo == 'bar',
            'Same framework instance returned',
        );

        $test->expect(
            $f3->constants($f3, 'REQ_') ==
            ['SYNC' => Base::REQ_SYNC, 'AJAX' => Base::REQ_AJAX, 'CLI' => Base::REQ_CLI],
            'Fetch constants from a class (object)',
        );
        $test->expect(
            $f3->constants('F3\ISO', 'CC_') == ISO::instance()->countries(),
            'Fetch constants from a class (string)',
        );
        $locales = ['en-US', 'de-DE', 'fr-FR'];
        //		$f3->TZ = 'Europe/Berlin';
        foreach ($locales as $locale) {
            $f3->LANGUAGE = $locale;
            $language = $f3->LANGUAGE; // intentional, receive from hook
            $test->expect(
                strpos($language, $locale) !== false,
                '['.$locale.']: Language set: '.$language,
            );
            $date = $f3->format('{0,date}', time());
            $test->expect(
                $date,
                '['.$locale.']: Format date default (short): '.$date,
            );
            $date = $f3->format('{0,date,medium}', time());
            $test->expect(
                $date,
                '['.$locale.']: Format date medium: '.$date,
            );
            $date = $f3->format('{0,date,full}', time());
            $test->expect(
                $date,
                '['.$locale.']: Format date full: '.$date,
            );
            $date = $f3->format('{0,time}', time());
            $test->expect(
                $date,
                '['.$locale.']: Format time default (short): '.$date,
            );
            $date = $f3->format('{0,time,medium}', time());
            $test->expect(
                $date,
                '['.$locale.']: Format time medium: '.$date,
            );
            $date = $f3->format('{0,time,full}', time());
            $test->expect(
                $date,
                '['.$locale.']: Format time full: '.$date,
            );
        }
        $f3->set('results', $test->results());
    }

}
