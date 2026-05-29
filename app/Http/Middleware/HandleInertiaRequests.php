<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id'      => $request->user()->id,
                    'name'    => $request->user()->name,
                    'email'   => $request->user()->email,
                    'role'    => $request->user()->role,
                    'station' => $request->user()->station,
                ] : null,
            ],
            'flash' => [
                'message' => session('message'),
            ],
            'app_settings' => \Illuminate\Support\Facades\Storage::exists('settings.json')
                ? json_decode(\Illuminate\Support\Facades\Storage::get('settings.json'), true)
                : [
                    'stop_keyword'    => 'STOP',
                    'video_quality'   => '720p',
                    'retention_days'  => 90,
                    'watermark_pos'   => 'bottom',
                ],
        ];
    }
}
