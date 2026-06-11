<?php

namespace App\Http\Controllers;

use App\Models\Recording;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecordingController extends Controller
{
    // ─── Operator ─────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'video'        => 'required|file|mimes:webm,mp4',
            'order_number' => 'required|string',
            'recorded_at'  => 'required|date',
            'duration'     => 'nullable|integer',
        ]);

        $dateFolder = now()->format('Y-m-d');
        $fileName   = $request->order_number . '.webm';
        $nasPath    = "videos/{$dateFolder}/{$fileName}";

        // Simpan sementara di local agar response cepat
        $tempPath = $request->file('video')->storeAs(
            'temp_videos',
            $fileName,
            'local'
        );

        $recording = Recording::create([
            'order_number'     => $request->order_number,
            'user_id'          => auth()->id(),
            'station'          => auth()->user()->station,
            'video_path'       => $nasPath,
            'duration_seconds' => $request->duration,
            'upload_status'    => 'pending',
            'recorded_at'      => $request->recorded_at,
        ]);

        // Kirim file ke NAS di background setelah response dikirim ke user
        dispatch(function () use ($tempPath, $nasPath, $recording) {
            try {
                $fileContents = Storage::disk('local')->get($tempPath);
                Storage::disk('nas')->put($nasPath, $fileContents);
                Storage::disk('local')->delete($tempPath);
                
                $recording->update(['upload_status' => 'uploaded']);
            } catch (\Exception $e) {
                \Log::error('Gagal upload ke NAS: ' . $e->getMessage());
                $recording->update(['upload_status' => 'failed']);
            }
        })->afterResponse();

        return response()->json(['status' => 'ok']);
    }

    public function check($order_number)
    {
        $recording = Recording::with('user')
            ->where('order_number', $order_number)
            ->latest('recorded_at')
            ->first();

        if ($recording) {
            return response()->json([
                'exists' => true,
                'recorded_at' => $recording->recorded_at->format('d M Y H:i'),
                'operator' => $recording->user->name ?? 'Unknown',
            ]);
        }

        return response()->json(['exists' => false]);
    }

    // ─── Admin: Dashboard ─────────────────────────────────────────────────

    public function dashboard()
    {
        $today = now()->toDateString();
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        $query = Recording::query();
        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        }

        $stats = [
            'today'     => (clone $query)->whereDate('recorded_at', $today)->count(),
            'week'      => (clone $query)->whereBetween('recorded_at', [
                                now()->startOfWeek(), now()->endOfWeek()
                            ])->count(),
            'month'     => (clone $query)->whereMonth('recorded_at', now()->month)
                                ->whereYear('recorded_at', now()->year)->count(),
            'all_time'  => (clone $query)->count(),
            'uploaded'  => (clone $query)->where('upload_status', 'uploaded')->count(),
            'failed'    => (clone $query)->where('upload_status', 'failed')->count(),
            'pending'   => (clone $query)->where('upload_status', 'pending')->count(),
        ];

        // Operator stats
        $operatorStats = [];
        if ($isAdmin) {
            $operatorStats = User::withCount([
                    'recordings as today_count' => fn ($q) => $q->whereDate('recorded_at', $today),
                    'recordings as total_count',
                ])
                ->get(['id', 'name', 'station', 'today_count', 'total_count']);
        } else {
            $operatorStats = User::where('id', $user->id)
                ->withCount([
                    'recordings as today_count' => fn ($q) => $q->whereDate('recorded_at', $today),
                    'recordings as total_count',
                ])
                ->get(['id', 'name', 'station', 'today_count', 'total_count']);
        }

        $recent = (clone $query)->with('user')
            ->orderByDesc('recorded_at')
            ->limit(8)
            ->get();

        // Disk space
        $storagePath = storage_path('app');
        $diskTotal   = disk_total_space($storagePath);
        $diskFree    = disk_free_space($storagePath);
        $disk = [
            'total_gb' => round($diskTotal / (1024 ** 3), 1),
            'free_gb'  => round($diskFree  / (1024 ** 3), 1),
            'percent'  => $diskTotal > 0
                ? round((($diskTotal - $diskFree) / $diskTotal) * 100)
                : 0,
        ];

        return inertia('Admin/Dashboard', compact('stats', 'operatorStats', 'recent', 'disk'));
    }

    // ─── Admin: Arsip ─────────────────────────────────────────────────────

    public function search(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        $query = Recording::with('user')->orderByDesc('recorded_at');

        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        }

        if ($request->q) {
            $query->where('order_number', 'like', '%' . $request->q . '%');
        }
        if ($request->operator) {
            $query->where('user_id', $request->operator);
        }
        if ($request->status) {
            $query->where('upload_status', $request->status);
        }
        if ($request->date_from) {
            $query->whereDate('recorded_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('recorded_at', '<=', $request->date_to);
        }

        $recordings = $query->paginate(10)->withQueryString();
        $operators  = User::where('role', 'operator')->get(['id', 'name']);
        $filters    = $request->only(['q', 'operator', 'status', 'date_from', 'date_to']);

        $settings = \Illuminate\Support\Facades\Storage::exists('settings.json')
            ? json_decode(\Illuminate\Support\Facades\Storage::get('settings.json'), true)
            : [];
        $retentionDays = $settings['retention_days'] ?? 90;

        return inertia('Admin/Archive', compact('recordings', 'operators', 'filters', 'retentionDays'));
    }

    // ─── Admin: Detail Rekaman ────────────────────────────────────────────

    public function show($id)
    {
        $recording = Recording::with('user')->findOrFail($id);

        // Cek apakah file video benar-benar ada di NAS
        $videoExists = $recording->video_path && Storage::disk('nas')->exists($recording->video_path);
        $fileSize    = $videoExists ? Storage::disk('nas')->size($recording->video_path) : null;
        $videoUrl    = $videoExists ? Storage::disk('nas')->url($recording->video_path) : null;

        return inertia('Admin/Show', compact('recording', 'videoExists', 'fileSize', 'videoUrl'));
    }

    // ─── Admin: Stream Video ──────────────────────────────────────────────

    public function stream($id)
    {
        $recording = Recording::findOrFail($id);

        abort_unless(Storage::disk('nas')->exists($recording->video_path), 404, 'File video tidak ditemukan.');

        // Stream langsung melalui Laravel agar tidak terkena blokir CORS/PNA dari browser
        return Storage::disk('nas')->response($recording->video_path);
    }

    // ─── Admin: Download Video ────────────────────────────────────────────

    public function download($id)
    {
        $recording = Recording::findOrFail($id);
        abort_if(! auth()->user()->isAdmin(), 403);
        abort_unless(Storage::disk('nas')->exists($recording->video_path), 404, 'File video tidak ditemukan.');

        return Storage::disk('nas')->download(
            $recording->video_path,
            $recording->order_number . '.webm'
        );
    }

    // ─── Admin: Hapus Rekaman ─────────────────────────────────────────────

    public function destroy($id)
    {
        $recording = Recording::findOrFail($id);
        abort_if(! auth()->user()->isAdmin(), 403);

        if ($recording->video_path && Storage::disk('nas')->exists($recording->video_path)) {
            Storage::disk('nas')->delete($recording->video_path);
        }
        $recording->delete();

        return redirect()->route('admin.archive')
            ->with('message', "Rekaman {$recording->order_number} berhasil dihapus.");
    }
}
