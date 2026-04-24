<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Application;

// Register the auto-loader.
require __DIR__.'/../vendor/autoload.php';

// Bootstrap the Laravel application.
$app = require_once __DIR__.'/../bootstrap/app.php';

// Create the kernel
$kernel = $app->make(Kernel::class);

// Handle the request
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
)->send();

$kernel->terminate($request, $response);
