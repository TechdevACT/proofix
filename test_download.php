<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;

$stream = Storage::disk('nas')->readStream('videos/2026-06-11/JX9165120138JX9114294484JX9165120138.webm');
$out = fopen('downloaded.webm', 'w');
stream_copy_to_stream($stream, $out);
fclose($out);
fclose($stream);

echo "Downloaded successfully. File size: " . filesize('downloaded.webm') . "\n";
