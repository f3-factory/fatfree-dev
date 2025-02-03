#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * FatFree Overdrive with OpenSwoole Server example
 */

require __DIR__.'/vendor/autoload.php';

$overdrive = new F3\Overdrive(
    app: App\App::class,
    with: new \F3\Http\Server\OpenSwoole()
);
$overdrive->run();
