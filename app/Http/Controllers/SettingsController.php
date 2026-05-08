<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    private string $settingsPath = 'settings.json';

    private array $defaults = [
        'stop_keyword'    => 'STOP',
        'video_quality'   => '720p',
        'retention_days'  => 90,
        'watermark_pos'   => 'bottom',
    ];

    private function load(): array
    {
        if (Storage::exists($this->settingsPath)) {
            return array_merge(
                $this->defaults,
                json_decode(Storage::get($this->settingsPath), true) ?? []
            );
        }
        return $this->defaults;
    }

    public function index()
    {
        $settings = $this->load();

        // Disk space
        $storagePath = storage_path('app');
        $diskTotal   = disk_total_space($storagePath);
        $diskFree    = disk_free_space($storagePath);
        $diskUsed    = $diskTotal - $diskFree;

        $disk = [
            'total_gb' => round($diskTotal / (1024 ** 3), 1),
            'used_gb'  => round($diskUsed  / (1024 ** 3), 1),
            'free_gb'  => round($diskFree  / (1024 ** 3), 1),
            'percent'  => $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100) : 0,
        ];

        // Ukuran folder video
        $videoSize = $this->getFolderSize(storage_path('app/videos'));

        return inertia('Admin/Settings', compact('settings', 'disk', 'videoSize'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'stop_keyword'   => 'required|string|max:50',
            'video_quality'  => 'required|in:720p,480p,360p',
            'retention_days' => 'required|integer|min:1|max:365',
            'watermark_pos'  => 'required|in:bottom,top',
        ]);

        Storage::put($this->settingsPath, json_encode($data, JSON_PRETTY_PRINT));

        return back()->with('message', 'Pengaturan berhasil disimpan.');
    }

    private function getFolderSize(string $path): string
    {
        if (! is_dir($path)) return '0 MB';

        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
            if ($file->isFile()) $size += $file->getSize();
        }

        if ($size >= 1024 ** 3) return round($size / (1024 ** 3), 2) . ' GB';
        if ($size >= 1024 ** 2) return round($size / (1024 ** 2), 1) . ' MB';
        return round($size / 1024, 0) . ' KB';
    }
}
