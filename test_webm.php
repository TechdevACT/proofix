<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

$file = Storage::disk('nas')->get('videos/2026-06-11/JX9165120138JX9114294484JX9165120138.webm');
if ($file === null) {
    echo "File not found\n";
    exit;
}

echo "First 10 bytes (hex): " . bin2hex(substr($file, 0, 10)) . "\n";
echo "First 10 bytes (string): " . substr($file, 0, 10) . "\n";
