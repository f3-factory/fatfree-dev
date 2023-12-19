#!/usr/bin/env php
<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use F3\Http\Factory\Psr17Factory;

$http = new Server("localhost", 9501);
$http->on("request", function (Request $request, Response $response) {
    switch ($request->server["request_uri"]) {
        case "/breakpoint":
            for ($i = 0; $i < 3; $i++) {
                echo $i, "\n"; // You can mark this line as a breakpoint in your IDE to debug with Xdebug.
            }
            $response->end("For debugging purpose, please open the server.php script and add a breakpoint in it.");
            break;
        case "/phpinfo":
            $response->header("Content-Type", "text/plain");
            ob_start();
            phpinfo();
            $response->end(ob_get_clean());
            break;
        default:
            $response->end("In this example! we use an Nginx server (at port 80) in front of Swoole (at port 9501).");
            break;
    }
});
$http->start();