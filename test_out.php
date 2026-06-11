<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

$req = Request::create('/records/14/download', 'GET');
$user = App\Models\User::first();
auth()->login($user);

$res = app()->handle($req);
ob_start();
$res->sendContent();
$content = ob_get_clean();

file_put_contents('test_download_14.webm', $content);
echo "Length: " . strlen($content) . "\n";
echo "First 10 bytes: " . bin2hex(substr($content, 0, 10)) . "\n";
