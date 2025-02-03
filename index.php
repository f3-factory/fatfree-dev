<?php

require 'vendor/autoload.php';

\F3\Base::instance();

$app = new \App\App();
$app->init();
$app->run();