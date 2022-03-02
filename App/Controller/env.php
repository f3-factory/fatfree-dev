<?php

namespace App;

class Env extends Controller
{
    function get($f3)
    {
        $classes = [
            'Base' =>
                [
                    'hash',
                    'json',
                    'session',
                    'mbstring'
                ],
            'Cache' =>
                [
                    'apcu',
                    'memcache',
                    'memcached',
                    'redis',
//                    'wincache',   // deprecated
//                    'xcache'      // deprecated
                ],
            'DB\SQL' =>
                [
                    'pdo',
                    'pdo_dblib',
//                    'pdo_mssql',  // deprecated
                    'pdo_mysql',
                    'pdo_odbc',
                    'pdo_pgsql',
                    'pdo_sqlite',
                    'pdo_sqlsrv'
                ],
            'DB\Jig' =>
                ['json'],
            'DB\Mongo' =>
                ['json', 'mongodb'],
            'Auth' =>
                ['ldap', 'pdo'],
            'Bcrypt' =>
                ['openssl'],
            'Image' =>
                ['gd'],
            'Lexicon' =>
                ['iconv'],
            'Matrix' =>
                ['calendar'],
            'SMTP' =>
                ['openssl'],
            'Web' =>
                ['curl', 'openssl', 'simplexml'],
            'Web\Geo' =>
                ['geoip', 'json'],
            'Web\OpenID' =>
                ['json', 'simplexml'],
            'Web\OAuth2' =>
                ['json'],
            'Web\Pingback' =>
                ['dom', 'xmlrpc'],
            'CLI\WS' =>
                ['pcntl']
        ];

        $test = new \Test;
        $test->expect(
            PHP_VERSION,
            'PHP version '.PHP_VERSION
        );
        foreach ($classes as $class => $modules) {
            foreach ($modules as $module) {
                $test->expect(
                    extension_loaded($module),
                    $class.' dependency: '.$module
                );
            }
        }
        $f3->set('results', $test->results());
    }

}
