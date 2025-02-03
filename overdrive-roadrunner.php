<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

/**
 * FatFree Overdrive with Roadrunner Server example
 */

$overdrive = new F3\Overdrive(
    app: App\App::class,
    with: new \F3\Http\Server\RoadRunner()
);
$overdrive->run();
