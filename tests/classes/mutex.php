<?php

require_once('lib/F3/Base.php');
$f3 = \F3\Base::instance();
$f3->SEED = 'test';
$f3->CACHE = 'redis=f3-redis';

$cache = \F3\Cache::instance();
$cache->set('mutex-driver', 'default', 60);
$t = $f3->get('GET.t');

if ($f3->exists('GET.driver', $driver)) {
    if ($driver === 'cache') {
        $f3->MUTEX = $cache;
        $cache->set('mutex-driver', $driver, 60);
    }
}

$f3->mutex('mutex1', function () use($t) {
    // simulate expensive operation
    sleep(3);
    \F3\Cache::instance()->set('mutex'.$t, (string) microtime(true), 60);
}, block: 6);

