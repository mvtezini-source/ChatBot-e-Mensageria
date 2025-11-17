<?php
// Minimal front controller to attach Slim app
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../src/Api.php';
$app->run();
